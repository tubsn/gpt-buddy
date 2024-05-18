<main>

<h1>Neue Knowledgebase anlegen</h1>
<p><b>Hinweise:</b>
Hier lassen sich Grunddaten zum Unternehmen wie z.B. Audiencedaten hinterlegen, die über die Callback Option in einen Prompt injiziert werden können.
</p>

<form class="form-container" method="post" action="">

<label>Knowledge Bezeichnung:
	<input name="title" type="text" placeholder="Der Name wird als im Callback Aufruf verwendet">
</label>

<label>Beschreibung:
	<textarea name="description" type="text" placeholder="Optionale Infos zu den Angaben"></textarea>
</label>

<label>Inhalt:
	<textarea class="settings-textarea" name="content" placeholder="z.B. Infos zu Audiences"></textarea>
</label>

<hr class="black">

<button class="submit">Knowledge anlegen</button>&ensp;
<a class="button light" href="/settings/knowledge">zurück zur Übersicht</a>

</form>

</main>