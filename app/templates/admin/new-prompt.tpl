<main>

<h1>Neue Prompt Aktion anlegen</h1>
<p><b>Hinweis:</b> Prompts mit Formatierungen verbrauchen geringfügig mehr Tokens</p>

<form class="form-container" method="post" action="">

	<fieldset class="grid-4-back-wide">

	<label>Aktionstitel:
		<input name="name" type="text" placeholder="sichtbarer Name">
	</label>

	<label>Interner Titel (benötigt):
		<input name="internalname" required type="text" placeholder="keine Sonderzeichen!">
	</label>

	<label>Formatierung?:
		<select name="markdown" >
			<option value="0">keine Formatierung</option>
			<option value="1" selected>Formatierung aktiv</option>
		</select>
	</label>

	<label>Hilfetext:
		<input name="description" type="text" placeholder="Hilfestellung zur Eingabe" value="<?=$prompt['description']?>">
	</label>

	</fieldset>

	<label>Prompt Inhalte:
		<textarea class="settings-textarea" name="content" placeholder="z.B. Korrigiere meine Rechtschreibung nach Duden mit ostfriesischem Dialekt"></textarea>
	</label>

	<button class="submit">Prompt anlegen</button>&ensp;
	<a class="button light" href="/settings">zurück zur Übersicht</a>
</form>

</main>