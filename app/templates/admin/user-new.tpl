<main class="auth-layout">

	<h1><?=$page['title'] ?? 'Create new User'?></h1>

	<form class="box" action="/admin" method="post">

	<input type="hidden" name="CSRFToken" value="<?=$CSRFToken;?>">
	<fieldset><legend>Nutzerdaten</legend>
		<label>Rolle:
			<select name="level">
				<option>User</option>
				<option>Admin</option>
			</select>
		</label>
		<label>E-Mail: <input type="email" required placeholder="max@muster.de" name="email"></label>
		<label>Passwort: <input type="password" placeholder="Mindestens 5 Zeichen" required name="password"></label>
	</fieldset>
	<fieldset><legend>Zusatzinfos</legend>
		<label>Vorname: <input name="firstname" placeholder="Vorname"></label>
		<label>Nachname: <input name="lastname" placeholder="Nachname"></label>
		<label>Gruppen: <input name="groups" placeholder="z.B. Editor, User"></label>
		<label>Rechte: <input name="rights" placeholder="z.B. Delete, Edit"></label>
	</fieldset>

	<button type="submit">neuen Nutzer anlegen</button>&ensp;
	<a class="button light" href="/admin">zurück zur Übersicht</a>

	</form>

</main>
