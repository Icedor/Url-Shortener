# Requirements and decisions

Approved on 2026-10-15:

- Public, account-free branded URL shortener at `https://url.icey.se`.
- Native PHP, HTML, CSS and JavaScript; no package manager, build step, CDN or external service.
- SQLite through PHP PDO, with the database outside public access on a local server filesystem.
- Match the sibling WhoIs and Secret services: system font, dark blue background, moving colored boxes, green/yellow/pink accents, rounded forms and By Icey footer. Reference-project integrations such as Matomo are not approved here.
- Swedish and English; first visit follows browser language, manual choice is preserved, switch at upper right.
- Optional custom alias with red/green availability feedback plus accessible text; random code otherwise. No user editing or user accounts.
- No automatic link expiration. Disabled aliases remain reserved forever.
- 10 successful creations per IP per rolling 24 hours and 5 per rolling minute, configurable. Invalid attempts do not consume successful-creation quota but have a separate request throttle.
- Reject non-HTTP(S), local/private literal addresses, local hostnames and own-domain destinations. No destination page fetches.
- Reserve internal routes, throttle availability checks, accept abuse reports, and provide server-side operator blocking of links and domains.

Rationale: SQLite gives transactions and uniqueness without a database server. JSON would require custom concurrency and recovery logic. Quotas are a starting policy, not a guarantee against distributed bots; shared networks share a quota. URL validation is not malware detection. No external reputation checking or CAPTCHA is currently approved.

Implemented details: ASCII lowercase aliases, 3–48 characters with internal hyphens; 8-character random codes; 302 redirects with no-store so operator blocking takes effect without permanent redirect caches. No click tracking. Abuse reports go to a private SQLite queue reviewed using the operator CLI. The hosting account has SFTP but no interactive shell; PHP commands can instead be invoked through its cPanel cron facility, pending production execution verification.
