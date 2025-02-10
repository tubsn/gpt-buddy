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

<fieldset class="grid-2-wide">
<div>
	<fieldset class="grid-2-col">
		<label>Name:
			<input name="title" type="text" placeholder="sichtbarer Name" value="<?=$prompt['title'] ?? null?>">
		</label>

		<label>Kategorie:
			<select name="category" >
				<?php if ($prompt['category'] != 'alle'): ?>
				<option value="alle">Alle</option>
				<?php endif ?>
				<?php if ($prompt['category']): ?>
				<option value="<?=$prompt['category']?>" selected><?=ucfirst($prompt['category'])?></option>
				<?php endif ?>
				<?php foreach ($categories as $category): ?>
				<?php if ($prompt['category'] == $category) {continue;} ?>				
				<option value="<?=$category?>"><?=ucfirst($category)?></option>
				<?php endforeach ?>
			</select>
		</label>
	</fieldset>

	<label>Hilfetext:
		<textarea name="description" type="text" placeholder="Hilfestellung zur Eingabe"><?=$prompt['description'] ?? null?></textarea>
	</label>

	<label>Prompt Inhalte:
		<textarea class="settings-textarea" name="content" placeholder="z.B. Korrigiere meine Rechtschreibung nach Duden mit ostfriesischem Dialekt"><?=$prompt['content'] ?? null?></textarea>
	</label>
</div>

<div>

	<fieldset>
	<label>Sichtbarkeit:
		<select name="inactive" >
			<option value="0">aktiv</option>
			<?php if ($prompt['inactive']): ?>
			<option value="1" selected>gesperrt</option>
			<?php else: ?>
			<option value="1">gesperrt</option>
			<?php endif ?>
		</select>
	</label>

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

	<label>Model erzwingen:
		<select name="model">
			<option value="0">Standard Model</option>
			<?php if ($prompt['model']): ?>
			<option selected><?=$prompt['model']?></option>
			<?php endif ?>
			<?php foreach ($aimodels as $modelName => $modelMeta): ?>
			<?php if ($prompt['model'] == $modelName) {continue;} ?>
			<option><?=$modelName?></option>
			<?php endforeach ?>
		</select>
	</label>

	<label>Temperatur:
		<input name="temperature" type="number" lang="en" step="0.1" min="0" max="2" placeholder="Standard 0.7" value="<?=$prompt['temperature'] ?? null?>">
	</label>

	<!--
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
	-->

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


	<label>Nachbearbeitungs PromptID:
		<input list="postprocessprompts" name="postprocess" type="text" placeholder="PromptID auswählen" value="<?=$prompt['postprocess'] ?? null?>">
		<datalist id="postprocessprompts">
			<?php foreach ($postProcessPrompts as $postProcessPromptID => $postProcessPrompt): ?>
				<option value="<?=$postProcessPromptID?>"><?=$postProcessPrompt?> [ID: <?=$postProcessPromptID?>]</option>
			<?php endforeach ?>
		</datalist>
	</label>


	<label>Knowledgebases <small>(mehrere durch Komma trennen):</small>
		<input list="knowledges" name="callback" type="text" placeholder="Callback oder Knowledgebase Titel eingeben" value="<?=$prompt['callback'] ?? null?>">
		<datalist id="knowledges">
			<?php foreach ($knowledges as $knowledge): ?>
				<option><?=$knowledge?></option>
			<?php endforeach ?>
		</datalist>
	</label>
	<small></small>
	</fieldset>
</div>
</fieldset>

	<fieldset>
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


	<button class="submit">Angaben speichern</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>

	<?php if (isset($prompt['id'])): ?>
	<div class="fright">
		
		<a id="copy-prompt-<?=$prompt['id']?>" title="kopieren" class="button light"><img class="icon-delete" src="/styles/flundr/img/icon-copy.svg"> kopieren</a>
		<fl-dialog selector="#copy-prompt-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/copy">
		<h1><?=$prompt['title']?> - kopieren?</h1>
		<p>Möchten Sie den Prompt wirklich kopieren?</p>
		</fl-dialog>



		<a id="del-match-<?=$prompt['id']?>" class="button light"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"> löschen</a>
		<fl-dialog selector="#del-match-<?=$prompt['id']?>" href="/settings/<?=$prompt['id']?>/delete">
		<h1><?=$prompt['title']?> - löschen?</h1>
		<p>Möchten Sie den Prompt wirklich löschen?</p>
		</fl-dialog>
	</div>
	<?php endif ?>


</form>

<?php if (isset($prompt['edited'])): ?>
<small class="fright">
Editiert: <?=formatDate($prompt['edited'],'d.m.Y H:i')?>
</small>
<?php endif ?>


</main>