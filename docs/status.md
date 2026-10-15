# Status — 2026-10-15

## Canvas, console credit and metadata correction

The initial background was an unauthorized simplified interpretation of the reference animation. It changed geometry, movement and drawing order, omitted collision/particle behavior and used a 73.3% black overlay instead of 70%. It has now been replaced with the original shared Secret/WhoIs implementation. The original console signature is restored. See [verification](verification.md) for the exact adaptations and test limits.

- [x] User reconfirmed synchronized Drive and exclusive writer. Fetch showed a clean worktree and matching HEAD/origin/main at 136fb5c.
- [x] Compare identical Secret/WhoIs background sources and restore the original rendering, physics and overlay.
- [x] Restore the exact Icey console signature and verify it in the browser log.
- [x] Rewrite SV/EN page titles and descriptions; add matching homepage social metadata; keep metadata synchronized with language selection.
- [x] PHP/JS syntax, 37 existing HTTP assertions, 240 reference animation frame comparisons and eight page/language metadata checks passed.
- [x] In-app browser verified SV/EN switching, keyboard activation, saved preference on reload, and no horizontal overflow at 1280px/320px.
- [ ] Visual screenshot comparison remains blocked: both screenshot APIs fail and the CDP screenshot request times out. DOM and console inspection work in the in-app browser; Chrome still fails its request-header policy connection.
- [ ] Commit, push and verify the deployed correction.

## Interface refinement

Follow-up correction: the user requires Icey to remain in the visible heading. The only runtime change is restoring that prefix: "Icey URL-förkortare" / "Icey URL shortener". Page titles already include Icey and are unchanged.

- [x] Restore Icey in both heading translations.
- [x] Verify and publish the heading correction: PHP lint, deployed file hash, live SV/EN headings, 320px mobile layout and keyboard language switching passed. Only app/view.php changed at runtime.

- [x] Fetch and compare: worktree and GitHub matched be996835d7c9cfffb11f1f3996c74b77352d4428. Continue the same session under the user's Drive sync/exclusive-writer confirmation.
- [x] Remove the introductory slogans in both languages; translate the main heading and document title to URL-förkortare / URL shortener.
- [x] Reuse the existing primary button treatment for "Shorten another"; remove unused text-button/intro styles.
- [x] Measure storage: production SQLite and its existing backup were 36 KiB each; workspace was 278,340 bytes before this refinement. Only URLs and metadata are stored, not destination content.
- [x] Verify desktop/mobile, both languages and keyboard reset behavior; PHP/JS syntax checks passed.
- [x] Publish the refinement as 471d887; downloaded all three changed files and verified hashes against that commit. Live SV/EN headings, titles, removed intro and primary reset-button markup verified. No browser console errors at inspection.

## Initial release

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
- [x] Push the release record and verify GitHub's final commit.

Resolved: after the user authorized the existing key, icey_cpanel works over SFTP. Interactive shell is disabled, which does not block deployment. cPanel cron and PHP 8.5 successfully executed the project's private CLI. Production is live; changes were scoped to /home/icey/url.icey.se and its own cron entry. Other projects and their existing jobs were preserved.

The application source was committed as `335ff2faa5ae8ac5b00175aeed3329803ed4656a` and pushed to main. git ls-remote confirmed the same remote SHA. Existing Credential Manager access worked; no new token or 2FA was needed. Before commit, staged contents were checked for both real environment keys, private-key material and common GitHub token formats; neither private environment file was tracked. .gitattributes enforces LF text files. Follow-up documentation records this verification.

Local preview data contains only example.com/example.org test links and must never be deployed. Temporary HTTP/service test databases were isolated under the OS temporary directory. The preview process is stopped at handover; restart it using operations.md.

Current application release includes the interface refinement from 471d887 and the one-line Icey heading restoration recorded above. The initial 12 deployed app/bin/public_html files were verified byte-for-byte; the three refined files and the subsequent heading correction were verified again after deployment. The commit containing this correction identifies the current release. Private deployment verification artifacts and previous source copies are ignored under artifacts/. Temporary restore snapshots were removed after verification; the ordinary weekday backup remains.

Ongoing operations: check the daily cron log and pending abuse reports via SFTP, investigate reports and apply CLI moderation as needed. Backups rotate on the hosting account; independent off-site storage and automatic notifications are not configured. Public creation cannot guarantee malware detection without external services, which remain unapproved. No application work is blocked by SSH shell restrictions.
