<main id="content">
<h1><?=$error['code'] ? 'Fehler '.$error['code'].':' : '';?> <?=$error['message'];?> </h1>

<?php if ($error['code'] == 404): ?>
<p>Bitte überprüfen Sie die Adresszeile in Ihrem Browser, wahrscheinlich haben Sie sich vertippt.</p>
<?php endif; ?>

<?php if (isset($error['trace'])): ?>
<hr />
File: <b><?=$error['file'];?></b> - Line: <b><?=$error['line'];?></b>
<pre>
<?=$error['trace'];?></pre>
<?php endif; ?>


<br>
<a class="button" href="/">Zurück zur Startseite</a>
</main>