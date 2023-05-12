<main class="auth-layout">

	<h1><?=$page['title'] ?? 'Edit User'?></h1>

	<form class="box" action="" method="post">
	<input type="hidden" name="CSRFToken" value="<?=$CSRFToken;?>">
	<fieldset><legend>Nutzerdaten</legend>
		<label>Rolle:
			<select name="level">
				<option<?= $user['level'] == 'User' ? ' selected' : '' ?>>User</option>
				<option<?= $user['level'] == 'Admin' ? ' selected' : '' ?>>Admin</option>
			</select>
		</label>
		<label>E-Mail: <input name="email" value="<?=$user['email']?>"></label>
		<label>Passwort: <input placeholder="******" type="password" name="password"></label>
	</fieldset>
	<fieldset><legend>Zusatzinfos</legend>
		<label>Vorname: <input name="firstname" value="<?=$user['firstname']?>"></label>
		<label>Nachname: <input name="lastname" value="<?=$user['lastname']?>"></label>
		<label>Gruppen: <input name="groups" value="<?=$user['groups']?>"></label>
		<label>Rechte: <input name="rights" value="<?=$user['rights']?>"></label>
	</fieldset>

	<button type="submit">Daten speichern</button>&ensp;
	<a class="button light" href="/admin">zurück zur Übersicht</a>

	</form>

</main> <!-- End Main Content -->
