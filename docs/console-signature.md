# Icey console signature

Updated: 2026-10-26. Published; GitHub and production verified.

Uses the exact console CSS from https://globerts.icey.se/js/credit.js?v=142: Menlo, Consolas, monospace; 11px; line-height 1.2; white-space pre, applied through console.log %c. The original six-row Unicode mark and Icey.se caption remain, without leading indentation or blank rows. The in-game SVG is not needed for these console-only signatures. No page layout, account, data or API behavior changes.

- [x] User confirmed Drive synchronization and sole writer on 2026-10-26.
- [x] Inspect reference and preserve the original mark.
- [x] PHP/JavaScript syntax and exact seven-line console text/style verification.
- [x] Commit/push and verify GitHub.
- [x] Deploy and verify production bytes/HTTPS.

Browser scope: Chrome executed the new signature with the exact text and style arguments; the public page loaded without submitting its form. Safari, Firefox and Edge visual rendering are not independently verified on this Windows session.

Verification before publication: all five signatures were executed in Node and their two console arguments matched exactly. Secret/WhoIs existing 22 encryption, language and ordering regressions passed. PHP syntax passed for both index files, the WhoIs configuration example and the URL view; all changed JavaScript files passed node --check.

## Publication evidence

2026-10-26: local HEAD matched fetched origin/main. Live HTTPS returned 200 and the exact new signature. No production form was submitted. This documentation-only closure does not change application bytes.

- public_html/js/scripts.js: commit d83c55e3bbe405bda5a1ff642f529d12affa0b20, 11692 bytes, SHA-256 48b93c68fffde7a1dd2c953dd207f1672c4fe7d41ac4556384c48cbe21796ce6.
- app/view.php: commit d83c55e3bbe405bda5a1ff642f529d12affa0b20, 14201 bytes, SHA-256 8771728776bf192e6946b3b90c78c6b1a696488ae525283adcb4f2de457aaa0a.
