<main>

<a href="/settings/new" class="fright button">Neue Aktion / Prompt anlegen</a>

<h1><?=APP_NAME?> | Settings</h1>
<p>Einstellungen für Aktionen und Prompts</p>


<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<?=$usageChart?>


<section class="grid-3-col">
<?php foreach ($prompts as $internalName => $prompt): ?>

<?php if (isset($prompt['inactive']) && $prompt['inactive'] == 1): ?>
<div class="box inactive">
<?php else: ?>	
<div class="box">
<?php endif ?>
	<div class="fright">
		<a id="del-match-<?=$internalName?>" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-match-<?=$internalName?>" href="/settings/<?=$internalName?>/delete">
		<h1><?=$prompt['name']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
	</div>

	<h3><a class="noline" href="/settings/<?=$internalName?>"><?=$prompt['name']?> <small>(editieren)</small></a></h3>
	<pre><?=$prompt['content']?></pre>
	<?php if (isset($prompt['hits'])): ?>
	<small><?=$prompt['hits'] ?? ''?>x eingesetzt</small>
		<?php if (isset($prompt['inactive']) && $prompt['inactive'] == 1): ?>
			[INAKTIV]
		<?php endif ?>
	<?php endif ?>
	<small class="fright"><?=date('d.m.Y', $prompt['edited'])?></small>
</div>
<?php endforeach ?>
</section>

</main>
