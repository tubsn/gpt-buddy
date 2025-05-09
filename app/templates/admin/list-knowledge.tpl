<style>
.icon-trashcan {cursor:pointer; display:inline-block; position:relative;
width:18px; height:18px; margin-left:0.3em; top:3px; background-image:url('/styles/flundr/img/icon-delete-black.svg'); opacity:0.2; background-size:cover;}
.icon-trashcan:hover {opacity:0.6;}
</style>

<script src="/styles/flundr/components/fl-dialog.js"></script>

<main class="" style="margin-bottom:2em; margin-top:2em;">

	<div class="fright">
	<a href="/settings" class="button">Zurück zu den Prompteinstellungen</a>
	</div>

	<h1><?=$page['title'] ?? 'Knowledge'?></h1>

	<p>Im Bereich Knowledgebase lassen sich Informationen ablegen, die als Datengrundlage für einen oder mehrere Prompts dienen können. Um einen Prompt mit einer entsprechenden Knowledgebase zu verknüpfen muss der Name der Knowledgebase bei den Prompteinstellungen in das Feld "Callback" eingetragen werden.</p>

	<table class="fancy wide">
		<tr>
			<th>ID</th>
			<th>Title</th>
			<th>URL</th>
			<th>Beschreibung</th>
			<th>im Prompt</th>
			<th>zuletzt editiert</th>
			<th style="text-align: right;">Löschen</th>
		</tr>
	<?php foreach ($knowledges as $knowledge): ?>
	<?php  
		if ($knowledge['used_by']) {$knowledge['used_by'] = explode(';',$knowledge['used_by']);}
		else {$knowledge['used_by'] = [];}
	?>
		<tr>
			<td><?=$knowledge['id']?></td>
			<td><a href="/settings/knowledge/<?=$knowledge['id']?>"><?=$knowledge['title']?></td>
			<td class="text-center"><?php if ($knowledge['url']): ?><small>✔</small><?php else: ?>-<?php endif ?></td>
			<td><?php if (empty($knowledge['description'])): ?>-<?php endif ?>
			<?=$knowledge['description'] ?? '...'?></td>
			<td class="narrow" style="max-width:250px; overflow:hidden; white-space: nowrap;"><?php foreach ($knowledge['used_by'] as $usedBy): ?>
				<small style="background-color:var(--primary-highlight); padding:0px 2px;border-radius:0.2em;"><?=$usedBy?></small>
			<?php endforeach ?></td>
			<td><?=$knowledge['edited']?></td>
			<td class="text-right">
				<span id="dialog-delete-<?=$knowledge['id']?>" class="icon-trashcan"></span>
				<fl-dialog selector="#dialog-delete-<?=$knowledge['id']?>" href="/settings/knowledge/<?=$knowledge['id']?>/delete">
				<h1>Bestätigen</h1>
				<p>Möchten Sie den Container <i><?=$knowledge['title']?> <b>(ID: <?=$knowledge['id']?>)</b></i> wirklich löschen?</p>
				</fl-dialog>
			</td>
		</tr>
	<?php endforeach; ?>

	</table>

	<a class="button mt" href="/settings/knowledge/new">Neue Knowledgebase anlegen</a>

</main>

