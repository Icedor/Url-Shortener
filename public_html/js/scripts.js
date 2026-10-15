'use strict';

(() => {
	const messages = {
		sv: {
			checking: 'Kontrollerar…', available: 'Ledigt – namnet kan användas.', alias_taken: 'Upptaget – välj ett annat namn.',
			alias_invalid: 'Använd 3–48 tecken: a–z, 0–9 och bindestreck mellan tecken.', alias_reserved: 'Det här namnet är reserverat.',
			url_invalid: 'Ange en giltig, fullständig http://- eller https://-adress utan inloggningsuppgifter.',
			url_private: 'Länken måste leda till en offentlig webbadress.', url_self: 'Du kan inte förkorta länkar till den här tjänsten.',
			url_blocked: 'Länkar till den här destinationen är spärrade.',
			quota: 'Nätverkets gräns för nya länkar är nådd.', rate_limit: 'För många försök på kort tid.',
			retry: 'Du kan försöka igen', session: 'Sessionen har löpt ut. Ladda om sidan och försök igen.',
			request: 'Kontrollera uppgifterna och försök igen.', method: 'Det här anropet stöds inte.',
			not_found: 'Kortlänken finns inte. Kontrollera namnet.', disabled: 'Kortlänken är spärrad.',
			unavailable: 'Tjänsten är tillfälligt otillgänglig. Försök igen om en stund.',
			network: 'Kunde inte nå tjänsten. Kontrollera anslutningen och försök igen.',
			creating: 'Skapar…', copied: 'Länken är kopierad!', copy_manual: 'Markera och kopiera länken manuellt med Ctrl+C eller mobilens kopieringsmeny.',
			reported: 'Tack! Din anmälan har sparats för granskning.', sending: 'Skickar…',
			report_invalid: 'Ange en kortlänk från den här tjänsten eller bara dess namn.',
		},
		en: {
			checking: 'Checking…', available: 'Available – you can use this name.', alias_taken: 'Taken – choose another name.',
			alias_invalid: 'Use 3–48 characters: a–z, 0–9 and hyphens between characters.', alias_reserved: 'This name is reserved.',
			url_invalid: 'Enter a valid, complete http:// or https:// address without login credentials.',
			url_private: 'The link must lead to a public web address.', url_self: 'You cannot shorten links to this service.',
			url_blocked: 'Links to this destination are blocked.',
			quota: 'Your network has reached its new-link limit.', rate_limit: 'Too many attempts in a short time.',
			retry: 'You can try again', session: 'Your session has expired. Reload the page and try again.',
			request: 'Check the details and try again.', method: 'This request is not supported.',
			not_found: 'This short link does not exist. Check the name.', disabled: 'This short link has been disabled.',
			unavailable: 'The service is temporarily unavailable. Please try again shortly.',
			network: 'Could not reach the service. Check your connection and try again.',
			creating: 'Creating…', copied: 'Link copied!', copy_manual: 'Select and copy the link manually with Ctrl+C or your mobile copy menu.',
			reported: 'Thank you! Your report has been saved for review.', sending: 'Sending…',
			report_invalid: 'Enter a short link from this service or just its name.',
		},
	};
	let language = document.documentElement.lang === 'sv' ? 'sv' : 'en';
	const translatedMessages = new Map();
	const text = (key) => messages[language][key] ?? messages[language].request;

	function showMessage(element, key, retryAfter = 0) {
		if (!element) return;
		translatedMessages.set(element, { key, retryAt: retryAfter ? Date.now() + retryAfter * 1000 : 0 });
		renderMessage(element);
	}

	function renderMessage(element) {
		const message = translatedMessages.get(element);
		if (!message) return;
		element.hidden = !message.key;
		element.textContent = message.key ? text(message.key) : '';
		if (message.retryAt) {
			const date = new Intl.DateTimeFormat(language === 'sv' ? 'sv-SE' : 'en-GB', { dateStyle: 'short', timeStyle: 'medium' }).format(message.retryAt);
			element.textContent += ` ${text('retry')} ${date}.`;
		}
	}

	function setLanguage(next, save = true) {
		language = next;
		document.documentElement.lang = language;
		document.title = language === 'sv' ? 'Icey URL – Korta länkar, enkelt.' : 'Icey URL – Short links, simply.';
		document.querySelectorAll('[data-language]').forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.language === language)));
		document.querySelectorAll('[data-placeholder-sv]').forEach((input) => { input.placeholder = input.dataset[language === 'sv' ? 'placeholderSv' : 'placeholderEn']; });
		document.querySelectorAll('[data-text-sv]').forEach((element) => { element.textContent = element.dataset[language === 'sv' ? 'textSv' : 'textEn']; });
		translatedMessages.forEach((value, element) => renderMessage(element));
		if (save) document.cookie = `icey_language=${language}; Path=/; Max-Age=31536000; SameSite=Lax${location.protocol === 'https:' ? '; Secure' : ''}`;
	}
	document.querySelectorAll('[data-language]').forEach((button) => button.addEventListener('click', () => setLanguage(button.dataset.language)));
	setLanguage(language, false);

	async function request(endpoint, data, signal) {
		let response;
		try {
			response = await fetch(endpoint, {
				method: 'POST', credentials: 'same-origin', signal,
				headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content },
				body: JSON.stringify(data),
			});
		} catch (error) {
			if (error.name === 'AbortError') throw error;
			throw { reason: 'network' };
		}
		let result;
		try { result = await response.json(); } catch { throw { reason: 'unavailable' }; }
		if (!response.ok) throw { reason: result.error ?? 'unavailable', retryAfter: result.retryAfter ?? 0 };
		return result;
	}

	const form = document.querySelector('#create-form');
	if (form) {
		const aliasInput = document.querySelector('#alias');
		const aliasField = document.querySelector('#alias-field');
		const aliasStatus = document.querySelector('#alias-status');
		const submitButton = document.querySelector('#create-button');
		const errorBox = document.querySelector('#create-error');
		let timer;
		let checkController;
		let checkVersion = 0;
		let aliasState = '';
		let busy = false;

		function setAliasState(state, key = '', retryAfter = 0) {
			aliasState = state;
			aliasField.dataset.state = state;
			aliasStatus.dataset.state = state;
			aliasInput.setAttribute('aria-invalid', String(state === 'invalid'));
			showMessage(aliasStatus, key, retryAfter);
			submitButton.disabled = busy || (aliasInput.value.trim() !== '' && state !== 'available');
		}

		aliasInput.addEventListener('input', () => {
			clearTimeout(timer);
			checkController?.abort();
			const version = ++checkVersion;
			const alias = aliasInput.value.trim().toLowerCase();
			showMessage(errorBox, '');
			if (!alias) { setAliasState(''); return; }
			if (!/^[a-z0-9][a-z0-9-]{1,46}[a-z0-9]$/.test(alias)) { setAliasState('invalid', 'alias_invalid'); return; }
			setAliasState('checking', 'checking');
			timer = setTimeout(async () => {
				checkController = new AbortController();
				try {
					const result = await request('/api/availability', { alias }, checkController.signal);
					if (version !== checkVersion) return;
					setAliasState(result.available ? 'available' : 'invalid', result.available ? 'available' : 'alias_taken');
				} catch (error) {
					if (error.name === 'AbortError' || version !== checkVersion) return;
					setAliasState('invalid', error.reason ?? 'unavailable', error.retryAfter);
				}
			}, 400);
		});

		form.addEventListener('submit', async (event) => {
			event.preventDefault();
			if (busy || (aliasInput.value.trim() && aliasState !== 'available')) return;
			busy = true;
			showMessage(errorBox, '');
			const label = submitButton.innerHTML;
			const pending = document.createElement('span');
			submitButton.replaceChildren(pending);
			showMessage(pending, 'creating');
			submitButton.disabled = true;
			form.setAttribute('aria-busy', 'true');
			aliasInput.disabled = true;
			const destination = document.querySelector('#destination');
			destination.disabled = true;
			try {
				const result = await request('/api/create', { url: destination.value.trim(), alias: aliasInput.value.trim() });
				document.querySelector('#short-link').value = result.url;
				document.querySelector('#result-destination').textContent = result.destination;
				document.querySelector('#create-view').hidden = true;
				document.querySelector('#result-view').hidden = false;
				document.querySelector('#result-heading').focus();
			} catch (error) {
				showMessage(errorBox, error.reason ?? 'unavailable', error.retryAfter);
				if (error.reason === 'alias_taken') setAliasState('invalid', 'alias_taken');
			} finally {
				busy = false;
				translatedMessages.delete(pending);
				submitButton.innerHTML = label;
				aliasInput.disabled = false;
				destination.disabled = false;
				form.removeAttribute('aria-busy');
				submitButton.disabled = aliasInput.value.trim() !== '' && aliasState !== 'available';
			}
		});

		document.querySelector('#copy-button').addEventListener('click', async () => {
			const input = document.querySelector('#short-link');
			try {
				await navigator.clipboard.writeText(input.value);
				showMessage(document.querySelector('#copy-status'), 'copied');
			} catch {
				input.focus();
				input.select();
				showMessage(document.querySelector('#copy-status'), 'copy_manual');
			}
		});
		document.querySelector('#reset-button').addEventListener('click', () => {
			form.reset();
			checkVersion++;
			clearTimeout(timer);
			checkController?.abort();
			setAliasState('');
			showMessage(errorBox, '');
			showMessage(document.querySelector('#copy-status'), '');
			document.querySelector('#result-view').hidden = true;
			document.querySelector('#create-view').hidden = false;
			document.querySelector('#destination').focus();
		});
	}

	const reportForm = document.querySelector('#report-form');
	if (reportForm) {
		reportForm.addEventListener('submit', async (event) => {
			event.preventDefault();
			const input = document.querySelector('#report-link');
			const status = document.querySelector('#report-status');
			const button = reportForm.querySelector('button[type="submit"]');
			if (button.disabled) return;
			let alias = input.value.trim();
			if (/^https?:\/\//i.test(alias)) {
				try {
					const url = new URL(alias);
					if (url.origin !== new URL(input.dataset.baseUrl).origin || url.search || url.hash || url.username || url.password) throw new Error();
					alias = url.pathname.slice(1);
				} catch {
					status.classList.add('error');
					showMessage(status, 'report_invalid');
					return;
				}
			}
			button.disabled = true;
			status.classList.remove('error');
			showMessage(status, 'sending');
			try {
				await request('/api/report', { alias, reason: document.querySelector('#report-reason').value });
				showMessage(status, 'reported');
				reportForm.reset();
			} catch (error) {
				status.classList.add('error');
				showMessage(status, error.reason ?? 'unavailable', error.retryAfter);
			} finally { button.disabled = false; }
		});
	}
})();
