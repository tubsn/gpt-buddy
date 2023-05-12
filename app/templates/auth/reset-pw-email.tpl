<html>
<head>
	<style>
		body {font-family:sans-serif; width:80%;}
		table {width:70%; background:#f8f8f8; border:1px solid #eee; border-radius:3px;}
		table thead {background:#046ab4; color:white; font-size:0.9em; cursor:pointer;}
		table tr:nth-child(even) {background:white;}
		table tr:hover {background:#f1f1f1;}
		table thead tr:hover {background:#046ab4;}
		table thead td:hover {background:#1f7bd5;}
		hr {border:none; border-bottom: 1px dashed #000000;}
		.btn {padding: 10px; background-color:#046ab4; margin:10px 0px; font-weight:bold; border-radius:3px; color:white; text-decoration:none;}		
	</style>
</head>
<body>

<h1><?=MAIL_SENDER_NAME?> - Passwort zurücksetzen</h1>

<p>Hallo, <?=$email?></p>

<p>wir haben eine Anfrage erhalten, Ihr Passwort zurückzusetzen.<br />
Klicken Sie dazu auf den folgenden Link:
</p>
<br/>
<a class="btn" href="<?=PAGEURL?>/password-change/<?=$token?>">Hier klicken - zum Passwort zurücksetzen</a>
<br/>
<br/>
<p>Hinweis: Dieser Link ist ca. 30 Minuten lang gültig.</p>

<hr />

<h3>Zur Sicherheit:</h3>
<p>Die Anfrage wurde von folgendem Absender gestellt:</p>

<table>
	<tr>
		<td>Zeitpunkt:</td>
		<td><?=$date?>, <?=$time?> Uhr</td>
	</tr>
	<tr>
		<td>IP-Adresse:</td>
		<td><?=$ip?></td>
	</tr>
	<tr>
		<td>Browser Infos:</td>
		<td><?=$browser?></td>
	</tr>
	</tr>
</table>

<p>Für den Fall, dass Sie diese Anfrage nicht gestellt haben, ignorieren Sie diese E-Mail. Normalerweise liegt kein Sicherheits-Problem vor.
<br />
Möglicherweise hat jemand eine falsche E-Mail Adresse eingetragen. Falls Sie dennoch von Missbrauch ausgehen informieren Sie bitte den technischen Support.</p>

<hr>
<p>Falls Sie Probleme beim Klick auf den Link haben, können Sie den folgenden Link auch in ihre Browser Addresszeile einfügen und aufrufen.
<br />
<?=PAGEURL?>/password-change/<?=$token?>
<br />Beachten Sie, das darin keine Leerzeichen vorkommen dürfen.<br />
Sie können die Passwort zurücksetzen Funktion jederzeit <a href="<?=PAGEURL?>/password-reset/">hier wiederholen</a>.
</p>

<hr>

<p>
<br />
Vielen Dank<br />
Ihr <?=MAIL_SENDER_NAME?>-Team
<br /><br />
<a href="<?=PAGEURL?>"><?=MAIL_SENDER_NAME?></a>
</p>

</body>
</html>
