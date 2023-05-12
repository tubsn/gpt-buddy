<style>
.icon-trashcan {cursor:pointer; display:inline-block; position:relative;
width:18px; height:18px; margin-left:0.3em; top:3px; background-image:url('/styles/flundr/img/icon-delete-black.svg'); opacity:0.2; background-size:cover;}
.icon-trashcan:hover {opacity:0.6;}
</style>

<script src="/styles/flundr/components/fl-dialog.js"></script>

<main class="auth-layout">

	<h1><?=$page['title'] ?? 'User-Profiles'?></h1>

	<table class="fancy wide">
		<tr>
			<th>ID</th>
			<th>E-Mail</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Gruppen</th>
			<th>Rechte</th>
			<th>Löschen</th>
		</tr>
	<?php foreach ($users as $user): ?>
		<tr>
			<td><?=$user['id']?></td>
			<td><a href="/admin/<?=$user['id']?>"><?=$user['email']?></td>
			<td><?=$user['firstname']?></td>
			<td><?=$user['lastname']?></td>
			<td><?=$user['groups'] ?? '-'?></td>
			<td><?=$user['rights'] ?? '-'?></td>
			<td>
				<span id="dialog-delete-<?=$user['id']?>" class="icon-trashcan"></span>
				<fl-dialog selector="#dialog-delete-<?=$user['id']?>" href="/admin/<?=$user['id']?>/delete/<?=$CSRFToken?>">
				<h1>Bestätigen</h1>
				<p>Möchten Sie <i><?=$user['firstname']?> <?=$user['lastname']?> <b>(<?=$user['email']?>)</b></i> wirklich löschen?</p>
				</fl-dialog>
			</td>
		</tr>
	<?php endforeach; ?>

	</table>

	<a class="button mt" href="/admin/new">Neuen User anlegen</a>

</main>
