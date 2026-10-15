# Status — 2026-10-15

- [x] Confirm requirements, SQLite and abuse policy with user.
- [x] Confirm Drive synchronization and exclusive writer with user.
- [x] Inspect initial local/remote state; create Git metadata outside Drive; fetch empty remote.
- [x] Inspect reference CSS/markup and confirm local PDO SQLite availability.
- [x] Save project rules and initial documentation.
- [x] Implement bilingual interface, alias checks, creation and redirects.
- [x] Implement quotas, URL restrictions, reports and operator CLI.
- [x] Verify integration behavior and concurrency: 112 service assertions and 37 HTTP assertions.
- [x] Verify desktop/mobile, language, keyboard and clipboard behavior.
- [x] Verify GitHub write access, scan tracked content and publish verified source.
- [x] Verify SFTP access and isolated target; inspect PHP Selector (8.5, PDO SQLite enabled).
- [x] Save private .env.local/.env.production and verified operational details in Uppgifter.txt.
- [x] Verify production web PHP execution, rewrites, concurrent SQLite insertion and cPanel cron execution.
- [x] Publish at https://url.icey.se and pass 21 production HTTP checks.
- [x] Verify production desktop/mobile, language persistence and keyboard focus.
- [x] Disable three release test links, review the test report, verify a consistent backup and isolated restore.
- [x] Test the maintenance command and save daily 03:17 server-time scheduling with seven rotating backups.
- [x] Update release documentation and Uppgifter.txt; compare all 12 deployed source files using SHA-256.
- [ ] Push the release record and verify GitHub's final commit.

Resolved: after the user authorized the existing key, icey_cpanel works over SFTP. Interactive shell is disabled, which does not block deployment. cPanel cron and PHP 8.5 successfully executed the project's private CLI. Production is live; changes were scoped to /home/icey/url.icey.se and its own cron entry. Other projects and their existing jobs were preserved.

The application source was committed as `335ff2faa5ae8ac5b00175aeed3329803ed4656a` and pushed to main. git ls-remote confirmed the same remote SHA. Existing Credential Manager access worked; no new token or 2FA was needed. Before commit, staged contents were checked for both real environment keys, private-key material and common GitHub token formats; neither private environment file was tracked. .gitattributes enforces LF text files. Follow-up documentation records this verification.

Local preview data contains only example.com/example.org test links and must never be deployed. Temporary HTTP/service test databases were isolated under the OS temporary directory. The preview process is stopped at handover; restart it using operations.md.

Current release uses the verified application from 81ffd4ce92eb4bc9452e2368d4f61656d967cde4 plus a privacy-text clarification about seven-day backups. All 12 deployed app/bin/public_html files match the reviewed worktree byte-for-byte. The commit containing this release record identifies the final version. Private deployment verification artifacts are ignored under artifacts/. Temporary restore snapshots were removed after verification; the ordinary weekday backup remains.

Ongoing operations: check the daily cron log and pending abuse reports via SFTP, investigate reports and apply CLI moderation as needed. Backups rotate on the hosting account; independent off-site storage and automatic notifications are not configured. Public creation cannot guarantee malware detection without external services, which remain unapproved. No application work is blocked by SSH shell restrictions.
