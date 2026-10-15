import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { mkdtemp, writeFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join, resolve } from 'node:path';
import { setTimeout as delay } from 'node:timers/promises';

const directory = await mkdtemp(join(tmpdir(), 'icey-url-http-'));
const root = resolve(import.meta.dirname, '..');
const php = process.env.ICEY_PHP_BINARY || 'php';
const options = process.platform === 'win32' ? ['-d', 'extension=php_pdo_sqlite.dll'] : [];
const origin = 'http://127.0.0.1:8094';
const environment = join(directory, '.env.test');
await writeFile(environment, `APP_ENV=local\nAPP_URL=${origin}\nDB_PATH=${join(directory, 'http.sqlite').replaceAll('\\', '/')}\nIP_HASH_KEY=${'a'.repeat(64)}\nCREATE_LIMIT_DAY=10\nCREATE_LIMIT_MINUTE=5\n`);
const env = { ...process.env, ICEY_ENV_FILE: environment };
const init = spawn(php, [...options, 'bin/manage.php', 'init'], { cwd: root, env, windowsHide: true, stdio: 'pipe' });
const initialized = await new Promise((done) => { init.on('error', () => done(false)); init.on('exit', (code) => done(code === 0)); });
assert(initialized, 'Database initialization');
const server = spawn(php, [...options, '-S', '127.0.0.1:8094', '-t', 'public_html', 'bin/router.php'], { cwd: root, env, windowsHide: true, stdio: 'pipe' });
let logs = '';
server.stderr.on('data', (chunk) => { logs += chunk; });
let assertions = 0;
const check = (value, message) => { assert(value, message); assertions++; };

try {
	let ready = false;
	for (let attempt = 0; attempt < 30; attempt++) {
		try { if ((await fetch(origin)).status === 200) { ready = true; break; } } catch {}
		await delay(100);
	}
	check(ready, 'HTTP server ready');
	let response = await fetch(origin, { headers: { 'Accept-Language': 'sv-SE,sv;q=0.9,en;q=0.8' } });
	const html = await response.text();
	check(html.includes('<html lang="sv">'), 'Swedish initial rendering');
	check(response.headers.get('content-security-policy')?.includes("frame-ancestors 'none'"), 'CSP header');
	check(response.headers.get('cache-control') === 'no-store', 'No caching');
	const session = response.headers.getSetCookie().find((cookie) => cookie.startsWith('icey_url='));
	check(session?.includes('HttpOnly') && session.includes('SameSite=Lax'), 'Session cookie protection');
	const cookie = session.split(';')[0];
	const csrf = html.match(/name="csrf-token" content="([a-f0-9]+)"/)[1];
	const call = (path, data, extraHeaders = {}) => fetch(origin + path, {
		method: 'POST', headers: { 'Content-Type': 'application/json', Cookie: cookie, 'X-CSRF-Token': csrf, ...extraHeaders }, body: JSON.stringify(data),
	});
	response = await call('/api/create', { url: 'https://example.com/', alias: '' }, { 'X-CSRF-Token': 'invalid' });
	check(response.status === 403 && (await response.json()).error === 'session', 'CSRF rejection');
	response = await fetch(origin + '/api/create');
	check(response.status === 405 && response.headers.get('allow') === 'POST', 'GET cannot create');
	response = await call('/api/create', { url: ['https://example.com/'] });
	check(response.status === 400, 'Reject array payload fields');
	response = await call('/api/create', { url: 'x'.repeat(9000) });
	check(response.status === 413, 'Request body limit');
	response = await call('/api/availability', { alias: 'http-test' });
	check((await response.json()).available === true, 'Available alias');
	response = await call('/api/create', { url: 'https://example.com/a?q=1#fragment', alias: 'http-test' });
	check(response.status === 201, 'Create status');
	const link = await response.json();
	check(link.url === origin + '/http-test', 'Configured canonical origin');
	response = await call('/api/availability', { alias: 'HTTP-TEST' });
	check((await response.json()).available === false, 'Case-insensitive taken status');
	response = await call('/api/create', { url: 'https://example.org/', alias: 'http-test' });
	check(response.status === 409, 'Alias conflict');
	response = await fetch(link.url, { redirect: 'manual' });
	check(response.status === 302 && response.headers.get('location') === 'https://example.com/a?q=1#fragment', 'Redirect target preserved');
	check(response.headers.get('cache-control') === 'no-store', 'Redirect never permanently cached');
	response = await fetch(link.url, { method: 'HEAD', redirect: 'manual' });
	check(response.status === 302 && (await response.text()) === '', 'HEAD redirect');
	response = await call('/api/report', { alias: 'http-test', reason: 'phishing' });
	check(response.status === 201 && (await response.json()).reported, 'Report API');
	for (let index = 0; index < 4; index++) {
		response = await call('/api/create', { url: 'https://example.com/', alias: '' });
		check(response.status === 201, 'Random creation');
	}
	response = await call('/api/create', { url: 'https://example.com/', alias: '' }, { 'X-Forwarded-For': '203.0.113.77' });
	check(response.status === 429 && Number(response.headers.get('retry-after')) > 0, 'Quota and Retry-After; forwarded IP cannot bypass');
	check((await response.json()).error === 'quota', 'Structured quota error');
	for (const path of ['/.env.local', '/app/bootstrap.php', '/bin/manage.php', '/.git/config', '/no-such-link']) {
		response = await fetch(origin + path);
		check(response.status === 404, 'Private or missing path rejected: ' + path);
		check(!(await response.text()).includes('IP_HASH_KEY='), 'No private source leaked');
	}
	response = await fetch(origin + '/privacy', { headers: { Cookie: 'icey_language=en', 'Accept-Language': 'sv' } });
	check((await response.text()).includes('<html lang="en">'), 'Manual language cookie wins');
	check(response.headers.get('x-robots-tag') === 'noindex, nofollow', 'Utility page not indexed');
	check(!/Fatal error|Warning:|service unavailable/i.test(logs), 'No runtime warnings');
	console.log(`PASS: ${assertions} HTTP assertions.`);
} finally {
	const exited = new Promise((done) => server.once('exit', done));
	server.kill();
	await exited;
	// Only remove this uniquely generated test directory under the OS temporary root.
	if (!directory.startsWith(join(tmpdir(), 'icey-url-http-'))) throw new Error('Unexpected test directory');
	await rm(directory, { recursive: true, force: true });
}
