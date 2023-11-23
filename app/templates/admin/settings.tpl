<main>

<a href="/settings/new" class="fright button">Neue Aktion / Prompt anlegen</a>

<h1><?=APP_NAME?> | Prompt Einstellungen</h1>

<?php if (empty($categories)): ?>
<h3>Zur Zeit sind keine Prompts angelegt</h3>
<p>Nutze den Button oben rechts um neue Prompts zu hinterlegen.</p>
<?php endif ?>

<p>Jeder Prompt ist <b>einer Kategorie</b> zugeordnet. Diese Kategorien lassen sich individuell, je Portal (LR,MOZ,SWP) in dem Hauptmenu anbinden.
<em>Beispiel:</em> LR kann einen Menupunkt für "Sport" haben, und SWP nicht. Auf der Ai-Buddy Startseite wird immer die Kombination aus den <b>Prompts der Kategorie "alle"</b> und der <b>Portal-Kategorie (lr, moz bzw. swp)</b> angezeigt.<br> Das Menu selbst lässt sich bei Bedarf vollkommen frei Konfigurieren!</p>

<hr style="margin-bottom:3em;">


<h2>Kategorie Übersicht</h2>
<p>Neue Kategorien können über das Funnelteam angemeldet werden. (Sofern noch keine Prompts für die Kategorie angelegt sind wird sie nicht aufgeführt)</p>

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
		<th style="width:80px">Direkt</th>
		<th style="width:50px; text-align:right;">Hits</th>
		<th style="width:50px; text-align:right;">⚙</th>
	</tr>
</thead>

<tbody>
<?php foreach ($prompts as $prompt): ?>
<tr class="<?=($prompt['inactive']) ? 'inactive' : ''?>">
	<td><?=formatDate($prompt['created'],'Y-m-d')?></td>
	<td><a class="noline" href="/settings/<?=$prompt['id']?>"><?=$prompt['title']?></a></td>
	<td style="white-space:nowrap; overflow: hidden; text-overflow: ellipsis;"><?=substr($prompt['content'],0,150)?> ...</td>
	<td><?=($prompt['direct']) ? 'ja' : '-'?></td>
	<td class="text-right"><?=$prompt['hits'] ?? '-'?></td>


<td class="text-right">
			<a id="del-match-<?=$prompt['id']?>" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-match-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/delete">
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


<h1>Prompt Nutzungs Statistik</h1>

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<div class="chartbox">
<?=$usageChartgrouped?>
</div>


<div class="chartbox">
<?=$usageChart?>
</div>



</main>
