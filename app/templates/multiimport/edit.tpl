<main>
<?php if (isset($event['id'])): ?>
<h1><?=$event['firstname'] ?? '' ?> <?=$event['lastname'] ?? ''?> - ID: <?=$event['id']?></h1>
<?php else: ?>
<h1>Eintrag anlegen</h1>
<?php endif ?>


<form class="form-container" method="post" action="">

<fieldset class="col-3">
<label>Vorname:
	<input name="firstname" type="text" placeholder="Vorname" value="<?=$event['firstname'] ?? null?>">
</label>

<label>Nachname:
	<input name="lastname" type="text" placeholder="Nachname" value="<?=$event['lastname'] ?? null?>">
</label>

<label>Ressort:
<select name="ressort">
	<?php foreach (IMPORT_RESSORTS as $ressort): ?>
	<option value="<?=$ressort?>" <?php if ($event['ressort'] == $ressort): ?>selected<?php endif ?>><?=$ressort?></option>
	<?php endforeach ?>
</select>
</label>
</fieldset>

<fieldset class="col-3">
<label>Wohnort:
	<input name="location" type="text" placeholder="Ort" value="<?=$event['location'] ?? null?>">
</label>
<label>Geburtstag:
	<input name="birthday" type="date" placeholder="Geburtstag" value="<?=$event['birthday'] ?? null?>">
</label>

<label>Alter:
	<input name="age" type="text" placeholder="Alter" value="<?=$event['age'] ?? null?>">
</label>

</fieldset>

<?php if (isset($event['id'])): ?>
<details>
	<summary>importierte Rohdaten zeigen</summary>
	<?=$event['raw'] ?? null?>

<small class="fright">
Importiert am: <?=formatDate($event['created'],'d.m.Y H:i')?> 
</small>

</details>
<?php endif ?>

<hr class="black">

<button class="submit">Angaben speichern</button>&ensp;
<a class="button light" href="/multiimport/archive">zurück zur Übersicht</a>

<?php if (isset($event['id'])): ?>
<a id="del-event-<?=$event['id']?>"  title="löschen" class="noline button light danger fright pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"> Eintrag Löschen</a>
<fl-dialog selector="#del-event-<?=$event['id']?>" href="/multiimport/<?=$event['id']?>/delete">
<h1><?=$event['firstname'] ?? 'Entrag'?> <?=$event['lastname'] ?? $event['id']?> - löschen?</h1>
<p>Möchten Sie den Eintrag wirklich löschen?</p>
</fl-dialog>
<?php endif ?>

</form>

<?php if (isset($event['edited'])): ?>
<small class="fright">
Bearbeitet: <?=formatDate($event['edited'],'d.m.Y H:i')?>
</small>
<?php endif ?>


</main>