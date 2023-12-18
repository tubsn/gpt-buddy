<main>

<h1>Prompt editieren: <?=$prompt['title']?></h1>
<p><b>Hinweise:</b>
Die besten Ergebnisse erhälst du mit einfachen aber detaillierten Instruktionen. <b>Unterteile komplexe Aufgaben in einzelne Arbeitsschritte!</b><br>
Füge Beispiele ein wie dein gewünschtes Ergebnis aussehen soll. Prompts mit Formatierungen verbrauchen geringfügig mehr Tokens.<br>
Die <b>Temperatur</b> regelt die Antwortenvarianz niedrige Werte erzeugen bei gleicher Frage immer die selbe Antwort. Hohe Werte eignen sich z.B. für Brainstorming. 
</p>

<form class="form-container" method="post" action="">
	<?php if (isset($prompt['hits'])): ?>
	<input name="hits" type="hidden" value="<?=$prompt['hits'] ?? 0?>">
	<?php endif ?>

	<div class="fright">
		<a id="del-match-<?=$prompt['id']?>" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-match-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/delete">
		<h1><?=$prompt['title']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
	</div>

	<fieldset class="grid-4-col">
	<label>Name:
		<input name="title" type="text" placeholder="sichtbarer Name" value="<?=$prompt['title'] ?? null?>">
	</label>

	<label>Kategorie:
		<select name="category" >
			<option value="alle">Alle</option>
			<?php if ($prompt['category']): ?>
			<option value="<?=$prompt['category']?>" selected><?=ucfirst($prompt['category'])?></option>
			<?php endif ?>
			<?php foreach ($categories as $category): ?>
			<?php if ($prompt['category'] == $category) {continue;} ?>				
			<option value="<?=$category?>"><?=ucfirst($category)?></option>
			<?php endforeach ?>
		</select>
	</label>

	<label>Prompt Sichtbar:
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

	<fieldset>
		<label>Prompt Inhalte:
			<textarea class="settings-textarea" name="content" placeholder="z.B. Korrigiere meine Rechtschreibung nach Duden mit ostfriesischem Dialekt"><?=$prompt['content'] ?? null?></textarea>
		</label>
		<?php if ($prompt['history']): ?>
		<details>
			<summary>Prompt Historie anzeigen</summary>
			<?php foreach ($prompt['history'] as $index => $old): ?>
			<div class="box" style="position:relative; background-color: white;">
				<small style="font-size:0.7em;position:absolute; right:0; top:-2.2em">vom <?=$old['edited']?> Uhr</small>
				<code><?=$old['content']?></code>

			</div>
			<hr>
			<?php endforeach ?>
		</details>
		<?php endif ?>

	</fieldset>

	<fieldset class="grid-5-back-wide">

	<label>Formatierung:
		<select name="format" >
			<option value="0">keine Formatierung</option>
			<?php if ($prompt['format']): ?>
			<option value="1" selected>Formatierung aktiv</option>
			<?php else: ?>
			<option value="1">Formatierung aktiv</option>
			<?php endif ?>
		</select>
	</label>

	<label>Direktprompt:
		<select name="direct" >
			<option value="0">nein</option>
			<?php if ($prompt['direct']): ?>
			<option value="1" selected>Ja</option>
			<?php else: ?>
			<option value="1">ja</option>
			<?php endif ?>
		</select>
	</label>

	<label>GPT4 erzwingen:
		<select name="advanced" >
			<option value="0">nein</option>
			<?php if ($prompt['advanced']): ?>
			<option value="1" selected>Ja</option>
			<?php else: ?>
			<option value="1">ja</option>
			<?php endif ?>
		</select>
	</label>

	<label>Temperatur:
		<input name="temperature" type="number" lang="en" step="0.1" min="0" max="2" placeholder="Standard 0.7" value="<?=$prompt['temperature'] ?? null?>">
	</label>

	<label>Callback Funktion (muss im Backend hinterlegt sein):
		<input name="callback" type="text" placeholder="z.B. current-date" value="<?=$prompt['callback'] ?? null?>">
	</label>

	</fieldset>

	<hr class="black">

	<button class="submit">Angaben speichern</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>
</form>

<?php if ($prompt['edited']): ?>
<small class="fright">
Editiert: <?=formatDate($prompt['edited'],'d.m.Y H:i')?>
</small>
<?php endif ?>


</main>