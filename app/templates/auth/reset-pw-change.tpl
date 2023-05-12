<main class="auth-layout">

	<h1><?=$page['title'] ?? 'Passwort zurücksetzen'?></h1>

	<form class="pw-reset-form" action="" method="post">
		<input type="hidden" name="CSRFToken" value="<?=session('CSRFToken');?>">
		<input type="hidden" name="changeToken" value="<?=$changeToken?>">

		<fieldset>
			<legend>Neues Passwort festlegen</legend>
			<p>Hinweis: Sie werden nach der Eingabe automatisch eingeloggt.</p>
			<?=(isset($message) ? '<div class="infoMessage fade-in">'.$message."</div>\n" : "\n")?>
			<label>Neues Passwort (mindestens 5 Zeichen):<input name="password" type="password" required autofocus></label>
			<button type="submit">Passwort ändern</button>
			&ensp;<a href="/login">zurück zum Login</a>
		</fieldset>
	</form>

</main>
