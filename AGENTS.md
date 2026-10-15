# Project rules

Read this file at every project start. User decisions take precedence. Propose improvements with reasons and alternatives; obtain approval before deviating from agreed rules.

## Implementation and design

- Use modern native HTML, CSS, JavaScript and PHP, with standard libraries. No external dependencies, frameworks, build tools, CDN resources or services without explicit approval.
- Use tabs for indentation and English identifiers and comments. Preserve reference design and working behavior. Ask if design evidence is missing.
- Reference projects guide design and code only; their permissions, dependencies and project rules do not transfer here.
- Match WhoIs and Secret under the sibling project directories. Serve at https://url.icey.se, in Swedish and English, with browser-language detection and an upper-right language switch.
- Use the visible heading "URL-förkortare" / "URL shortener". Keep the form introduction concise without promotional filler. "Shorten another" uses the established primary button style.
- SQLite is approved. Public creation, no accounts and no user editing. Optional custom alias, otherwise random. Show availability in text and color and enforce uniqueness at insertion.
- Links have no automatic expiration. Never reuse a previously allocated alias. Operators can disable links and block destination domains through a server-side CLI.
- Default creation quota: 10 successful creations per IP in a rolling 24 hours, and 5 per rolling minute. Throttle availability checks and attempts separately. Explain shared-IP quotas and retry timing.
- Accept only HTTP(S) public destinations. Reject local/private address literals, local hostnames and own-domain links. No remote content fetching. Reserve internal aliases. Provide abuse reporting. No external security service or CAPTCHA without approval.

## Documentation

- Write small, relevant English Markdown documents, indexed in docs/README.md. Maintain requirements, decisions and rationale, architecture, operations, verification and current status as needed.
- Distinguish implemented, planned and outstanding work. Keep a resumable checklist covering every requirement and necessary check. Only check verified items.
- Respond briefly and honestly in Swedish: completed, verified and outstanding. Never claim testing, synchronization or publishing without evidence.

## Drive and Git

- GitHub is the version source for tracked files; Drive is the source for other project files and the working directory.
- Git metadata MUST stay outside Drive and all cloud-synced folders. Use a verified local Git directory; never create .git inside this working directory (including a pointer file).
- At every startup verify Drive has finished syncing and no other computer is writing. If unverifiable, ask and wait before changing working files.
- Fetch without changing working files. Compare Drive, local Git and GitHub against their last common version before transferring changes.
- Remote-only changes: update tracked work without affecting private files. Local-only changes: preserve and continue. Both changed or uncertain baseline: present differences and wait for the user's decision before merging or replacing.
- Secure unpublished work before updating. Never choose a version using timestamps alone. No blind overwrite, destructive mirroring or force push.
- Aim to hand over current work on GitHub; document anything unpublished. One writing computer at a time; complete Drive synchronization before switching.
- See docs/operations.md for commands and startup evidence.

## Secrets and production

- Store secrets in environment-specific .env files on Drive and only where required in production, outside public access. Never put secrets in Git, frontend code, chat or logs.
- Maintain a secret-free .env.example and ignore private files, runtime data, caches and build residue. Check tracked files too.
- The user's private URL/Dokument/Uppgifter.txt stores operational details; do not print existing credentials. Keep new application secrets in the .env file and document its location.
- Do not modify other projects on the shared Icey cPanel account.
- cPanel use is authorized. Explain the intended check or change before opening it; restrict changes to this project. Production maintenance uses private CLI commands through cPanel cron, with daily cleanup and seven rotating backups.
- When the user says "pusha live", verify, update docs, commit and push relevant files, deploy the same version, and verify GitHub and production. Do not request the same approval again. Report failed steps.

## Execution

- Work autonomously within scope. Avoid unrelated edits and overbuilding. Additional requests extend the ongoing task unless explicitly redirected.
- Test changed functions and preserved behaviors; visually check desktop and mobile, keyboard operation and all existing themes. Do not present placeholders as finished features.
- Before handover reconcile the complete checklist and record blockers. Keep enough context for the next session.
