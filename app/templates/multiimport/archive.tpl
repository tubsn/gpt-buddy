<main id="multiImportApp">

<div class="fright">
<a href="/multiimport" class="button">Daten importieren</a>&ensp;
<a href="/multiimport/new" class="button">Eintrag anlegen</a>
</div>

<h1><?=$page['title']?></h1>

<div class="box parameters">
<form method="get" action="" onchange="this.submit()">
<label>Ressort Filtern:
<select name="ressort">
	<option value="">Alle</option>
	<?php foreach (IMPORT_RESSORTS as $ressort): ?>
	<?php if ($selectedRessort == $ressort): ?>
		<option selected value="<?=$ressort?>"><?=$ressort?>
		<?php if (isset($stats['ressort'][$ressort])): ?>
		(<?=$stats['ressort'][$ressort] ?? ''?>)
		<?php endif ?>
		</option>
		<?php continue;?>
	<?php endif ?>
	<option value="<?=$ressort?>"><?=$ressort?>
	<?php if (isset($stats['ressort'][$ressort])): ?>
	(<?=$stats['ressort'][$ressort] ?? ''?>)
	<?php endif ?>
	</option>
	<?php endforeach ?>
</select>
</label>

<label>Orte Filtern:
<select name="location" >
	<option value="">Alle</option>
	<?php foreach ($locations as $location): ?>
	<?php if ($selectedLocation == $location): ?>
		<option selected value="<?=$location?>"><?=$location?>
		<?php if (isset($stats['location'][$location])): ?>
		(<?=$stats['location'][$location] ?? ''?>)
		<?php endif ?>
		</option>
		<?php continue;?>
	<?php endif ?>
	<option value="<?=$location?>"><?=$location?>
	<?php if (isset($stats['location'][$location])): ?>
	(<?=$stats['location'][$location] ?? ''?>)
	<?php endif ?>
	</option>
	<?php endforeach ?>
</select>
</label>

<!--
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
-->

<label>Geburtstag von:
	<input type="date" name="from" value="<?=$from?>">
</label>

<label>Geburtstag bis:
	<input type="date" name="to" value="<?=$to?>">
</label>

<button @click="resetFilters">Filter löschen</button>

</form>



</div>

<hr>

<?php if (!empty($events)): ?>
<section>

<div class="box">

	<table class="fancy wide js-sortable">
	<thead>
		<tr>
			<th>Geburtstag</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Ort</th>
			<th>Alter</th>
			<th style="text-align:right">Ressort</th>
			<th style="text-align:right">Importiert</th>
			<th style="text-align:right">⚙</th>
		</tr>
	</thead>
	<tbody>

	<?php foreach ($events as $event): ?>
	<tr data-id="<?=$event['id']?>" data-event-url="/multiimport/<?=$event['id']?>">
		<td><?=$event['birthday']?></td>
		<td><a href="/multiimport/<?=$event['id']?>"><?=$event['firstname']?></a></td>
		<td><a href="/multiimport/<?=$event['id']?>"><?=$event['lastname']?></a></td>
		<td><?php if (!empty($location)): ?><?=$event['location']?>
		<?php else: ?>-<?php endif ?></td>
		<td><?=$event['age']?></td>
		<td class="text-right"><?=$event['ressort']?></td>
		<td class="text-right"><?=formatDate($event['created'],'d.m.y')?></td>

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


<div>
	<button class="button">Duplikate Entfernen (in Arbeit)</button> &nbsp;
	<button id="btn-delete-obsolete" class="button danger"><img class="icon-delete" src="/styles/flundr/img/icon-delete-white.svg"> Alte Geburtstage löschen</button> 

	<?php if (auth('level') == 'Admin'): ?>
	&nbsp;
	<button id="btn-delete-all" class="button danger"><img class="icon-delete" src="/styles/flundr/img/icon-delete-white.svg"> Daten komplett löschen</button> &nbsp;
	<?php endif ?>

	<fl-dialog selector="#btn-delete-all" href="/multiimport/wipe_all">
	<h1>Datenbank löschen Bestätigen</h1>
	<p>Möchten Sie wirklich die komplette Datenbank löschen?</p>
	</fl-dialog>

	<fl-dialog selector="#btn-delete-obsolete" href="/multiimport/wipe_old">
	<h1>Vergangene Daten löschen</h1>
	<p>Möchten Sie abgelaufene Geburtstage (heute vor einer Woche) löschen?</p>
	</fl-dialog>
</div>

</section>
<?php else: ?>
<p>- Keine Einträge verfügbar - </p>
<?php endif ?>

</main>
