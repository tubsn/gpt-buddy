<main>

<h1>Neue Prompt Aktion anlegen</h1>
<p><b>Hinweise:</b>
Die besten Ergebnisse erhälst du mit einfachen aber detaillierten Instruktionen. <b>Unterteile komplexe Aufgaben in einzelne Arbeitsschritte!</b><br>
Füge Beispiele ein wie dein gewünschtes Ergebnis aussehen soll.
Die <b>Temperatur</b> regelt die Antwortenvarianz niedrige Werte erzeugen bei gleicher Frage immer die selbe Antwort. Hohe Werte eignen sich z.B. für Brainstorming. Du kannst mit {{{ rot | grün }}} <b>einen Zufallsgenerator</b> nutzen oder Tokens wie: {{{date}}} {{{time}}} {{{now}}} <b>Datum und Uhrzeit</b> bzw. beides einfügen.</p>

<form class="form-container" method="post" action="">


<fieldset class="grid-2-wide">
<div>
	<fieldset class="grid-3-front-wide">
	<label>Name:
		<input name="title" type="text" placeholder="sichtbarer Name">
	</label>

	<label>Kategorie:
		<select name="category" >
			<option value="alle">Alle</option>
			<?php foreach ($categories as $category): ?>
			<?php if ($selectedCategory == $category): ?>
			<option value="<?=$category?>" selected><?=ucfirst($category)?></option>
			<?php else: ?>					
			<option value="<?=$category?>"><?=ucfirst($category)?></option>
			<?php endif ?>
			<?php endforeach ?>
		</select>
	</label>

	<label>Direktprompt:
		<select name="direct" >
			<option value="0" selected>nein</option>
			<option value="1">Ja</option>
		</select>
	</label>	
	</fieldset>

	<label>Hilfetext:
		<textarea name="description" type="text" placeholder="Hilfestellung zur Eingabe"></textarea>
	</label>

	<label>Prompt Inhalte:
		<textarea class="settings-textarea" name="content" placeholder="z.B. Korrigiere meine Rechtschreibung nach Duden mit ostfriesischem Dialekt"></textarea>
	</label>

</div>

<div>

	<fieldset>	

	<div class="grid-2-col">
	<label>Sichtbarkeit:
		<select name="inactive" >
			<option value="0" selected>aktiv</option>
			<option value="1">gesperrt</option>
		</select>
	</label>

	<label>Formatierung:
		<select name="format" >
			<option value="0">keine Formatierung</option>
			<option value="1" selected>Formatierung aktiv</option>
		</select>
	</label>
	</div>

	<div class="grid-2-col">
	<label>Datum im Prompt:
		<select name="withdate" >
			<option value="1">Ja</option>
			<option value="0" selected>Nein</option>
		</select>
	</label>

	<label>Temperatur:
		<input name="temperature" type="number" lang="en" step="0.1" min="0" max="2" placeholder="Standard 0.7">
	</label>

	</div>

	<label>Model erzwingen:
		<select name="model">
			<option value="0">Standard Model</option>
			<?php foreach ($aimodels as $modelName => $modelMeta): ?>
			<option><?=$modelName?></option>
			<?php endforeach ?>
		</select>
	</label>

	<label>Kontrollprompt:
		<textarea style="height:118px" name="afterthought" placeholder="Zusatzprompt nach Usereingabe"></textarea>
	</label>

	<label>Nachbearbeitungs PromptID:
		<input list="postprocessprompts" name="postprocess" type="text" placeholder="PromptID auswählen">
		<datalist id="postprocessprompts">
			<?php foreach ($postProcessPrompts as $postProcessPromptID => $postProcessPrompt): ?>
				<option value="<?=$postProcessPromptID?>"><?=$postProcessPrompt?> [ID: <?=$postProcessPromptID?>]</option>
			<?php endforeach ?>
		</datalist>
	</label>

	<label>Knowledgebases <small>(mehrere durch Komma trennen):</small>
		<input list="knowledges" name="callback" type="text" placeholder="Callback oder Knowledgebase Titel eingeben">
		<datalist id="knowledges">
			<?php foreach ($knowledges as $knowledge): ?>
				<option><?=$knowledge?></option>
			<?php endforeach ?>
		</datalist>		
	</label>
	</fieldset>
</div>
</fieldset>

	<small><b>Hinweis:</b> Du kannst mit {{{ rot | grün }}} einen Zufallsgenerator nutzen oder mit Tokens wie: date, time, now - Datum und Uhrzeit einfügen. Beispiel: {{{ now }}} wird 2025-04-16 11:45</small>

	<hr class="black">

	<button class="submit">Prompt anlegen</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>
</form>

</main>