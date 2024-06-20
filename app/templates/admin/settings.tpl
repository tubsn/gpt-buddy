<main>

<div class="fright">
<a href="/settings/knowledge" class="button">Knowledgebase editieren</a>
<a href="/settings/new" class="button">Prompt anlegen</a>
</div>

<h1><?=APP_NAME?> | Prompt Einstellungen</h1>

<?php if (empty($categories)): ?>
<h3>Zur Zeit sind keine Prompts angelegt</h3>
<p>Nutze den Button oben rechts um neue Prompts zu hinterlegen.</p>
<?php endif ?>

<p>Jeder Prompt ist <b>einer Kategorie</b> zugeordnet. Diese Kategorien lassen sich individuell im Hauptmenü anbinden. Bei mehreren Portalen ist es sinnvoll unterschiedliche Hauptmenüs zu nutzen. 
<em>Beispiel:</em> Portal1 kann einen Menupunkt für "Sport" haben, und Portal2 nicht. Auf der Ai-Buddy Startseite wird immer die Kombination aus den <b>Prompts der Kategorie "alle"</b> und der <b>Portal-Kategorie</b> angezeigt. Das Menü (Konfiguration unter: app > templates > navigation) lässt sich auch mit externen Links bestücken!</p>

<hr style="margin-bottom:3em;">


<h2>Kategorie Übersicht</h2>
<p>Kategorien können über die config.php angelegt werden. (Sofern noch keine Prompts für die Kategorie angelegt sind wird diese nicht aufgeführt!)</p>

<ul class="category-list">
<?php foreach ($categories as $category => $prompts): ?>
	<li><a href="#<?=$category?>"><?=ucfirst($category)?></a></li>
<?php endforeach ?>
</ul>

<hr class="large">

<?php foreach ($categories as $category => $prompts): ?>

<a href="/settings/new?category=<?=$category?>" class="fright button">Prompt anlegen</a>

<h3 id="<?=$category?>"><?=ucfirst($category)?></h3>

<table class="fancy promptlist wide" style="table-layout: fixed;">
<thead>
	<tr>
		<th style="width:100px">Erstellt</th>
		<th style="width:40%">Titel</th>
		<th style="width:60%">Inhalt</th>
		<th style="width:80px">Temp.</th>
		<th style="width:50px; text-align:right;">Hits</th>
		<th style="width:50px; text-align:right;">⚙</th>
	</tr>
</thead>

<tbody>
<?php foreach ($prompts as $prompt): ?>
<tr class="<?=($prompt['inactive']) ? 'inactive' : ''?>">
	<td style="white-space:nowrap;"><?=formatDate($prompt['created'],'Y-m-d')?></td>
	<td><a class="noline" href="/settings/<?=$prompt['id']?>"><?=$prompt['title']?></a></td>
	<td style="white-space:nowrap; overflow: hidden; text-overflow: ellipsis;"><?=substr($prompt['content'],0,150)?> ...</td>
	<td><?=($prompt['temperature']) ? $prompt['temperature'] : '-'?></td>
	<td class="text-right"><?=$prompt['hits'] ?? '-'?></td>


<td class="text-right">
		<a id="copy-prompt-<?=$prompt['id']?>" title="kopieren" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-copy.svg"></a>
		<fl-dialog selector="#copy-prompt-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/copy">
		<h1><?=$prompt['title']?> - kopieren?</h1>
		<p>Möchten Sie den Prompt wirklich kopieren?</p>
		</fl-dialog>

		<a id="del-prompt-<?=$prompt['id']?>"  title="löschen" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-prompt-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/delete">
		<h1><?=$prompt['title']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
</td>
	

</tr>
<?php endforeach ?>
</tbody>
</table>

<hr style="margin: 3em 0;">
<?php endforeach ?>


<h1>Prompt-Nutzungsstatistik</h1>
<p>Prompts müssen mindestens 10 mal benutzt werden, um hier gelistet zu sein.</p>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div class="chartbox">
<?=$usageChartgrouped?>
</div>


<div class="chartbox">
<?=$usageChart?>
</div>



</main>
