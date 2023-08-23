<main>

<a href="/settings/new" class="fright button">Neue Aktion / Prompt anlegen</a>

<h1><?=APP_NAME?> | Settings</h1>

<?php if (!empty($prompts)): ?>
<p>Einstellungen für Aktionen und Prompts</p>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div class="chartbox">
<?=$usageChart?>
</div>

<section class="grid-4-col">
<?php foreach ($prompts as $prompt): ?>

<?php if (isset($prompt['inactive']) && $prompt['inactive'] == 1): ?>
<div class="box inactive">
<?php else: ?>	
<div class="box">
<?php endif ?>
	<div class="fright">
		<a id="del-match-<?=$prompt['id']?>" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-match-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/delete">
		<h1><?=$prompt['title']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
	</div>

	<h3><a class="noline" href="/settings/<?=$prompt['id']?>"><?=$prompt['title']?></a></h3>
	<pre><?=$prompt['content']?></pre>
	<?php if (isset($prompt['hits'])): ?>
	<small><?=$prompt['hits'] ?? ''?>x eingesetzt</small>
		<?php if (isset($prompt['inactive']) && $prompt['inactive'] == 1): ?>
			[INAKTIV]
		<?php endif ?>
	<?php endif ?>
	<small class="fright"><?=formatDate($prompt['edited'],'Y-m-d')?></small>
</div>
<?php endforeach ?>
</section>

<?php else: ?>
<h3>Zur Zeit sind keine Prompts angelegt</h3>
<p>Nutze den Button oben rechts um neue Prompts zu hinterlegen.</p>
<?php endif ?>

</main>
