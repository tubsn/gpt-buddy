<main>

<h1>Neue Prompt Aktion anlegen</h1>
<p><b>Hinweise:</b>
Die besten Ergebnisse erhälst du mit einfachen aber detaillierten Instruktionen. <b>Unterteile komplexe Aufgaben in einzelne Arbeitsschritte!</b><br>
Füge Beispiele ein wie dein gewünschtes Ergebnis aussehen soll. Prompts mit Formatierungen verbrauchen geringfügig mehr Tokens.<br>
Die <b>Temperatur</b> regelt die Antwortenvarianz niedrige Werte erzeugen bei gleicher Frage immer die selbe Antwort. Hohe Werte eignen sich z.B. für Brainstorming. 
</p>

<form class="form-container" method="post" action="">


<fieldset class="grid-2-wide">
<div>
	<fieldset class="grid-2-col">
	<label>Aktionstitel:
		<input name="title" type="text" placeholder="sichtbarer Name">
	</label>

	<label>Kategorie:
		<select name="category" >
			<!--<option value="user">User (Meine Prompts)</option>-->
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

	<label>Prompt Sichtbar:
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

	<label>Direktprompt:
		<select name="direct" >
			<option value="0" selected>nein</option>
			<option value="1">Ja</option>
		</select>
	</label>

	<label>GPT4 erzwingen:
		<select name="advanced" >
			<option value="0" selected>nein</option>
			<option value="1">ja</option>
		</select>
	</label>

	<label>Temperatur:
		<input name="temperature" type="number" lang="en" step="0.1" min="0" max="2" placeholder="Standard 0.7">
	</label>

	<label>Callback / Knowledgebase:
		<input name="callback" type="text" placeholder="z.B. current-date">
	</label>
	
	</fieldset>
</div>
</fieldset>

	<hr class="black">

	<button class="submit">Prompt anlegen</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>
</form>

</main>