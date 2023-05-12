<main>



<h1>Prompt editieren: <?=ucfirst($internalName)?></h1>

<form class="form-container" method="post" action="">
	<?php if (isset($prompt['hits'])): ?>
	<input name="hits" type="hidden" value="<?=$prompt['hits'] ?? 0?>">
	<?php endif ?>

	<div class="fright">
		<a id="del-match-<?=$internalName?>" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-match-<?=$internalName?>" href="/settings/<?=$internalName?>/delete">
		<h1><?=$prompt['name']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
	</div>

	<fieldset class="grid-3-back-wide">
	<label>Aktionsname:
		<input name="name" type="text" placeholder="sichtbarer Name" value="<?=$prompt['name'] ?? null?>">
	</label>

	<label>Prompt Sichtbar?:
		<select name="inactive" >
			<option value="0">aktiv</option>
			<?php if ($prompt['inactive']): ?>
			<option value="1" selected>gesperrt</option>
			<?php else: ?>
			<option value="1">gesperrt</option>
			<?php endif ?>
		</select>
	</label>

	<label>Hilfetext:
		<input name="description" type="text" placeholder="Hilfestellung zur Eingabe" value="<?=$prompt['description'] ?? null?>">
	</label>

	</fieldset>

	<label>Prompt Inhalte:
		<textarea class="settings-textarea" name="content" placeholder="z.B. Korrigiere meine Rechtschreibung nach Duden mit ostfriesischem Dialekt"><?=$prompt['content'] ?? null?></textarea>
	</label>

	<button class="submit">Angaben speichern</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>
</form>

<small class="fright">
Editiert: <?=date('d.m.Y H:i', $prompt['edited'])?>
</small>


</main>