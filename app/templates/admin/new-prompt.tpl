<main>

<h1>Neue Prompt Aktion anlegen</h1>

<form class="form-container" method="post" action="">

	<fieldset class="grid-3-back-wide">
	<label>Interner Titel (benötigt):
		<input name="internalname" required type="text" placeholder="keine Sonderzeichen!">
	</label>

	<label>Aktionstitel:
		<input name="name" type="text" placeholder="sichtbarer Name">
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