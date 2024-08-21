<main>

<div class="fright">
<a href="/multiimport" class="light button">zum Importer</a>&ensp;
<a href="/multiimport/new" class="button">Neuer Eintrag</a>
</div>

<h1><?=$page['title']?></h1>




<div class="box parameters">
<form method="get" action="" onchange="this.submit()">
<label>Ressort Filtern:
<select name="ressort" >
	<option value="">Alle</option>
	<?php foreach (IMPORT_RESSORTS as $ressort): ?>
	<?php if ($selectedRessort == $ressort): ?>
		<option selected value="<?=$ressort?>"><?=$ressort?></option>
		<?php continue;?>
	<?php endif ?>
	<option value="<?=$ressort?>"><?=$ressort?></option>
	<?php endforeach ?>
</select>
</label>

<label>Import Monat:
<select name="date" class="month-picker">
	<option value="">Alle</option>
	<?php foreach ($months as $month => $date): ?>
	<?php $class=''; $selected = '';?>
	<?php if (str_contains($month, $currentMonth)): ?>
	<?php $class='current-month';?>
	<?php endif ?>
	<?php if ($month == $selectedDate): ?><?php $selected = 'selected';?><?php endif ?>
	<option <?=$selected?> value="<?=$date['start']?>" class="<?=$class?>"><?=$month?></option>
	<?php endforeach ?>
</select>
</label>

<label>Orte Filtern:
<select name="location" >
	<option value="">Alle</option>
	<?php foreach ($locations as $location): ?>
	<?php if ($selectedLocation == $location): ?>
		<option selected value="<?=$location?>"><?=$location?></option>
		<?php continue;?>
	<?php endif ?>
	<option value="<?=$location?>"><?=$location?></option>
	<?php endforeach ?>
</select>
</label>

</form>

</div>

<hr>

<section>

<div class="box">

	<table class="fancy wide js-sortable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Geburtstag</th>
			<th>Ressort</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Ort</th>
			<th>Alter</th>
			<th>Importiert</th>
			<th style="text-align:right">⚙</th>
		</tr>
	</thead>
	<tbody>

	<?php foreach ($events as $event): ?>
	<tr data-id="<?=$event['id']?>" data-event-url="/multiimport/<?=$event['id']?>">
		<td><?=$event['id']?></td>
		<td><?=$event['birthday']?></td>
		<td><?=$event['ressort']?></td>
		<td><a href="/multiimport/<?=$event['id']?>"><?=$event['firstname']?></a></td>
		<td><a href="/multiimport/<?=$event['id']?>"><?=$event['lastname']?></a></td>
		<td><?=$event['location']?></td>
		<td><?=$event['age']?></td>
		<td><?=formatDate($event['created'],'d.m.y')?></td>

		<td class="text-right">
		<a id="del-event-<?=$event['id']?>"  title="löschen" class="noline pointer"><img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></a>
		<fl-dialog selector="#del-event-<?=$event['id']?>" href="/multiimport/<?=$event['id']?>/delete">
		<h1><?=$event['firstname'] ?? 'Entrag'?> <?=$event['lastname'] ?? $event['id']?> - löschen?</h1>
		<p>Möchten Sie den Eintrag wirklich löschen?</p>
		</fl-dialog>
		</td>
	</tr>	
	<?php endforeach ?>

	</tbody>
	</table>
</div>
</section>

</main>
