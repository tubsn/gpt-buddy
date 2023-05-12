<main class="auth-layout">

	<h1><?=$page['title'] ?? 'User-Profile'?></h1>

	<table class="auth-profile fancy wide mb2">
		<tr>
			<th>ID:</th><th><?=auth('id');?></th>
		</tr>
		<tr>
			<td>E-Mail:</td>
			<td><?=auth('email');?></td>
		</tr>
		<tr>
			<td>Vorname:</td>
			<td><?=auth('firstname');?></td>
		</tr>
		<tr>
			<td>Nachname:</td>
			<td><?=auth('lastname');?></td>
		</tr>
		<tr>
			<td>Gruppen:</td>
			<td><?=auth('groups');?></td>
		</tr>
		<tr>
			<td>Rechte:</td>
			<td><?=auth('rights');?></td>
		</tr>
	</table>

	<?php if (auth('level') == 'Admin'): ?>
	<details class="mb2 fancy">
		<summary>Login Historie</summary>
		<table class="fancy wide mb">
			<tr>
				<th>Datum</th>
				<th>Zeit</th>
				<th>IP</th>
				<th>Useragent</th>
			</tr>
		<?php foreach ($logins as $login): ?>
			<tr>
				<td><?=formatDate($login['date'],"d.m.Y")?></td>
				<td><?=formatDate($login['date'],"H:i")?> Uhr</td>
				<td><?=$login['ip']?></td>
				<td><?=$login['userinfo']?></td>
			</tr>
		<?php endforeach; ?>
		</table>
	</details>
	<?php endif; ?>

	<a class="button" href="/profile/edit/">Benutzer bearbeiten</a>
	&ensp;<a class="button light" href="/logout">Benutzer ausloggen</a>

</main>
