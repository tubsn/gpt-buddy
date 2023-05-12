<main class="auth-layout">

	<h1><?=$page['title'] ?? 'Edit Profile'?></h1>

	<form class="auth-profile-edit" action="" method="post">
		<input type="hidden" name="CSRFToken" value="<?=$CSRFToken;?>">
		<fieldset><legend>Nutzerdaten</legend>
			<label>E-Mail: <input name="email" value="<?=auth('email');?>"></label>
			<label>Passwort: <input type="password" placeholder="*****" name="password"></label>
		</fieldset>
		<fieldset><legend>Zusatzinfos</legend>
			<label>Vorname: <input name="firstname" value="<?=auth('firstname');?>"></label>
			<label>Nachname: <input name="lastname" value="<?=auth('lastname');?>"></label>
		</fieldset>

		<button type="submit">Nutzerdaten speichern</button>
		&ensp;<a class="button light" href="/profile">abbrechen und zurück</a>

	</form>

</main>
