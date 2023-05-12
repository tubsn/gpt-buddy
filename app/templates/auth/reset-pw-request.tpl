<main class="auth-layout">

	<h1><?=$page['title'] ?? 'Passwort zurücksetzen'?></h1>

	<p>Um ein neues Passwort zu erstellen senden wir Ihnen zur Überprüfung eine E-Mail mit einem Link zu.</p>

	<form class="pw-reset-form" action="" method="post">
		<input type="hidden" name="CSRFToken" value="<?=session('CSRFToken');?>">
		<fieldset><legend>Tragen Sie hier die E-Mail Adresse ihres Zugangs ein</legend><?=(isset($message) ? '<div class="infoMessage fade-in">'.$message."</div>\n" : "\n")?>
			<label>E-Mail Adresse:<input name="email" type="email" required autofocus></label>
			<button type="submit">E-Mail zusenden</button>
			&ensp;<a href="/login">zurück zum Login</a>
		</fieldset>
	</form>

</main>
