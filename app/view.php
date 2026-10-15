<?php
declare(strict_types=1);

function escape(string $value): string
{
	return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bilingual(string $swedish, string $english): string
{
	return '<span class="lang sv">' . escape($swedish) . '</span><span class="lang en">' . escape($english) . '</span>';
}

$baseUrl = $config['APP_URL'] ?? 'https://url.icey.se';
$displayHost = (string) parse_url($baseUrl, PHP_URL_HOST);
$dayLimit = $config['CREATE_LIMIT_DAY'] ?? 10;
$title = $language === 'sv' ? 'Icey URL – Korta länkar, enkelt.' : 'Icey URL – Short links, simply.';
$description = $language === 'sv' ? 'Förkorta en lång länk eller välj ett eget länknamn. Gratis, utan konto och utan automatisk utgångstid.' : 'Shorten a long link or choose your own link name. Free, no account and no automatic expiry.';
?>
<!DOCTYPE html>
<html lang="<?= escape($language) ?>">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<title><?= escape($title) ?></title>
	<meta name="description" content="<?= escape($description) ?>">
	<meta name="theme-color" content="#0c1016">
	<meta name="csrf-token" content="<?= escape($token ?? '') ?>">
	<link rel="icon" href="/favicon.png" type="image/png">
	<link rel="stylesheet" href="/css/style.css">
	<?php if ($page === 'create'): ?>
	<link rel="canonical" href="<?= escape($baseUrl) ?>/">
	<?php endif; ?>
	<script src="/js/scripts.js" defer></script>
	<script src="/js/background.js" defer></script>
</head>
<body>
	<a class="skip-link" href="#main"><?= bilingual('Hoppa till innehåll', 'Skip to content') ?></a>
	<nav class="language-switch" aria-label="Language / Språk">
		<button type="button" data-language="sv" lang="sv" aria-label="Svenska" aria-pressed="<?= $language === 'sv' ? 'true' : 'false' ?>">SV</button>
		<span aria-hidden="true">/</span>
		<button type="button" data-language="en" lang="en" aria-label="English" aria-pressed="<?= $language === 'en' ? 'true' : 'false' ?>">EN</button>
	</nav>
	<main class="container" id="main" tabindex="-1">
		<h1><a href="/">Icey URL</a></h1>
		<?php if ($page === 'create'): ?>
		<section class="panel" id="create-view">
			<p class="intro"><strong><?= bilingual('Långa länkar. Kort och enkelt.', 'Long links. Short and simple.') ?></strong><?= bilingual('Klistra in din länk och gör den redo att delas.', 'Paste your link and get it ready to share.') ?></p>
			<form id="create-form" method="post">
				<label for="destination"><?= bilingual('Länken du vill förkorta', 'The link you want to shorten') ?></label>
				<input id="destination" name="url" type="url" placeholder="https://example.com/…" required maxlength="4096" autocomplete="url" inputmode="url" autocapitalize="none" spellcheck="false" aria-describedby="url-hint">
				<p class="hint" id="url-hint"><?= bilingual('En fullständig webbadress som börjar med https:// eller http://.', 'A full web address starting with https:// or http://.') ?></p>
				<label for="alias"><?= bilingual('Eget länknamn', 'Custom link name') ?> <span class="optional"><?= bilingual('(valfritt)', '(optional)') ?></span></label>
				<div class="alias-field" id="alias-field">
					<span class="alias-prefix" aria-hidden="true"><?= escape($displayHost) ?>/</span>
					<input id="alias" name="alias" type="text" maxlength="48" autocomplete="off" autocapitalize="none" spellcheck="false" aria-describedby="alias-hint alias-status" data-placeholder-sv="ditt-namn" data-placeholder-en="your-name" placeholder="<?= $language === 'sv' ? 'ditt-namn' : 'your-name' ?>">
				</div>
				<p class="hint" id="alias-hint"><?= bilingual('Lämna tomt för en slumpad länk. Annars 3–48 tecken: a–z, 0–9 och bindestreck mellan tecken.', 'Leave empty for a random link. Otherwise use 3–48 characters: a–z, 0–9 and hyphens between characters.') ?></p>
				<p class="alias-status" id="alias-status" role="status" aria-live="polite"></p>
				<p class="message error" id="create-error" role="alert" hidden></p>
				<div class="form-actions"><button type="submit" class="primary" id="create-button"><span><?= bilingual('Förkorta länken', 'Shorten link') ?></span></button></div>
			</form>
			<p class="small-info"><?= bilingual('Utan konto. Ingen automatisk utgångstid.', 'No account. No automatic expiry.') ?><br><?= bilingual("Max {$dayLimit} länkar per IP-adress under 24 timmar. Personer på samma nätverk delar kvoten.", "Up to {$dayLimit} links per IP address over 24 hours. People on the same network share the allowance.") ?></p>
		</section>
		<section class="panel" id="result-view" hidden aria-labelledby="result-heading">
			<p class="eyebrow"><?= bilingual('Klart!', 'All done!') ?></p>
			<h2 id="result-heading" tabindex="-1"><?= bilingual('Redo att delas', 'Ready to share') ?></h2>
			<label for="short-link"><?= bilingual('Din korta länk', 'Your short link') ?></label>
			<div class="link-display"><input id="short-link" type="text" readonly><button class="button-small" type="button" id="copy-button"><?= bilingual('Kopiera', 'Copy') ?></button></div>
			<p id="copy-status" class="hint" role="status"></p>
			<p class="destination-caption"><?= bilingual('Leder till', 'Leads to') ?><br><span id="result-destination" dir="auto"></span></p>
			<p class="small-info"><?= bilingual('Ingen automatisk utgångstid. Spara länken – den kan inte ändras i efterhand.', 'No automatic expiry. Save your link – it cannot be edited afterwards.') ?></p>
			<button class="text-button" type="button" id="reset-button"><?= bilingual('Förkorta en till', 'Shorten another') ?> <span aria-hidden="true">↗</span></button>
		</section>
		<?php elseif ($page === 'report'): ?>
		<section class="panel">
			<h2><?= bilingual('Anmäl en länk', 'Report a link') ?></h2>
			<p><?= bilingual('Hjälp oss att stoppa nätfiske, skadlig kod och spam. Anmälan granskas manuellt och spärrar inte länken automatiskt.', 'Help us stop phishing, malware and spam. Reports are reviewed manually and do not automatically disable a link.') ?></p>
			<form id="report-form" method="post">
				<label for="report-link"><?= bilingual('Kortlänken eller dess namn', 'The short link or its name') ?></label>
				<input id="report-link" name="alias" required maxlength="256" placeholder="<?= escape($baseUrl) ?>/…" autocomplete="off" autocapitalize="none" spellcheck="false" data-base-url="<?= escape($baseUrl) ?>">
				<label for="report-reason"><?= bilingual('Orsak', 'Reason') ?></label>
				<select id="report-reason" name="reason">
					<option value="phishing" data-text-sv="Nätfiske eller bedrägeri" data-text-en="Phishing or fraud"><?= $language === 'sv' ? 'Nätfiske eller bedrägeri' : 'Phishing or fraud' ?></option>
					<option value="malware" data-text-sv="Skadlig kod" data-text-en="Malware"><?= $language === 'sv' ? 'Skadlig kod' : 'Malware' ?></option>
					<option value="spam" data-text-sv="Spam" data-text-en="Spam">Spam</option>
					<option value="other" data-text-sv="Annat missbruk" data-text-en="Other abuse"><?= $language === 'sv' ? 'Annat missbruk' : 'Other abuse' ?></option>
				</select>
				<p id="report-status" class="message" role="status" hidden></p>
				<div class="form-actions"><button type="submit" class="primary"><span><?= bilingual('Skicka anmälan', 'Submit report') ?></span></button></div>
			</form>
			<a class="back-link" href="/"><?= bilingual('Tillbaka', 'Back') ?></a>
		</section>
		<?php elseif ($page === 'privacy'): ?>
		<section class="panel prose">
			<h2><?= bilingual('Om länkar och integritet', 'About links and privacy') ?></h2>
			<p><?= bilingual('Kortlänkar är offentliga. Dela inte privata länkar, lösenord eller åtkomstnycklar här. Alla med kortlänken kan öppna destinationen, och egna länknamn kan gissas.', 'Short links are public. Do not share private links, passwords or access keys here. Anyone with a short link can open its destination, and custom link names can be guessed.') ?></p>
			<p><?= bilingual('Vi sparar destinationen, kortnamnet och skapandetiden för att länken ska fungera. Länkar har ingen automatisk utgångstid, men kräver fortsatt drift. Missbrukslänkar kan spärras och deras namn återanvänds inte.', 'We store the destination, short name and creation time so the link can work. Links have no automatic expiry but depend on continued operation. Abusive links may be disabled and their names are never reused.') ?></p>
			<p><?= bilingual('För att begränsa missbruk använder appen en nycklad identifierare baserad på din IP-adress. Den sparas i tillfälliga kvotposter och rensas efter 24 timmar vid nästa skrivning eller schemalagda rensning. Ingen klickstatistik samlas in av appen. Webbhotellet kan ha separata åtkomstloggar.', 'To limit abuse, the app uses a keyed identifier derived from your IP address. It is stored in temporary quota records and removed after 24 hours on the next write or scheduled cleanup. The app does not collect click statistics. The hosting provider may keep separate access logs.') ?></p>
			<p><?= bilingual('Vi använder en sessionscookie för formulärsäkerhet och, om du väljer språk, en cookie som minns det valet i ett år. Inga externa analysverktyg laddas.', 'We use a session cookie for form security and, if you choose a language, a cookie that remembers it for one year. No external analytics are loaded.') ?></p>
			<p><?= bilingual('Dagliga säkerhetskopior roteras över sju dagar. Uppgifter som rensats ur den aktiva databasen kan finnas kvar i en säkerhetskopia tills den ersätts.', 'Daily backups rotate over seven days. Records removed from the active database may remain in a backup until it is replaced.') ?></p>
			<p><?= bilingual('Anmälningar innehåller länknamnet, vald orsak och tidpunkten. De sparas för manuell granskning. Du kan anmäla missbruk via länken nedan.', 'Reports contain the link name, selected reason and submission time. They are stored for manual review. You can report abuse using the link below.') ?></p>
			<a class="back-link" href="/"><?= bilingual('Tillbaka', 'Back') ?></a>
		</section>
		<?php else: ?>
		<section class="panel">
			<h2><?= $errorReason === 'disabled' ? bilingual('Länken har spärrats', 'This link has been disabled') : ($errorReason === 'unavailable' ? bilingual('Tillfälligt otillgänglig', 'Temporarily unavailable') : bilingual('Länken kunde inte öppnas', 'The link could not be opened')) ?></h2>
			<p><?= $errorReason === 'disabled' ? bilingual('Den här kortlänken är inte längre tillgänglig.', 'This short link is no longer available.') : ($errorReason === 'unavailable' ? bilingual('Tjänsten kan inte nås just nu. Försök igen om en stund.', 'The service is unavailable right now. Please try again shortly.') : bilingual('Kontrollera att hela länken är rätt kopierad.', 'Check that you have copied the complete link correctly.')) ?></p>
			<a class="back-link" href="/"><?= bilingual('Till startsidan', 'Go to homepage') ?></a>
		</section>
		<?php endif; ?>
		<noscript><p class="message error"><?= bilingual('Aktivera JavaScript för att skapa eller anmäla länkar och byta språk. Befintliga kortlänkar fungerar utan JavaScript.', 'Enable JavaScript to create or report links and switch languages. Existing short links work without JavaScript.') ?></p></noscript>
		<footer class="service-footer"><a href="/abuse"><?= bilingual('Anmäl en länk', 'Report a link') ?></a><span aria-hidden="true">·</span><a href="/privacy"><?= bilingual('Om tjänsten & integritet', 'About & privacy') ?></a></footer>
	</main>
	<a class="icey-made-by" href="https://icey.se" target="_blank" rel="noopener noreferrer">By Icey</a>
	<canvas id="icey-bg-canvas" aria-hidden="true"></canvas>
	<div class="background-overlay" aria-hidden="true"></div>
</body>
</html>
