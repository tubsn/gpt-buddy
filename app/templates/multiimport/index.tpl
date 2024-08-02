<main id="multiImportApp">

<h1><?=$page['title']?></h1>

<div class="box parameters">

<label>Funktion auswählen:
<select v-model="prompt" data-name="prompt">
	<?php foreach ($prompts as $prompt): ?>
	<option value="<?=$prompt['id']?>" data-description="<?=$prompt['description'] ?? ''?>" data-advanced="<?=$prompt['advanced'] ?? ''?>"><?=$prompt['title']?></option>
	<?php endforeach ?>
</select>
</label>

<label>Ressort auswählen:
<select v-model="ressort" data-name="ressort">
	<?php foreach (IMPORT_RESSORTS as $ressort): ?>
	<option value="<?=$ressort?>"><?=$ressort?></option>
	<?php endforeach ?>
</select>
</label>

</div>


<?php if (isset($data)): ?>
<table class="fancy">
<thead>
	<tr>
		<th>Vorname</th>
		<th>Nachname</th>
		<th>Ort</th>
		<th>Geburtstag</th>
		<th>Alter</th>
	</tr>
</thead>
<tbody>
<?php foreach ($data as $jubi): ?>
<tr>
	<td><?=$jubi['Vorname']?></td>
	<td><?=$jubi['Nachname']?></td>
	<td><?=$jubi['Ort']?></td>
	<td><?=$jubi['Geburtstag']?></td>
	<td><?=$jubi['Alter']?></td>
</tr>	
<?php endforeach ?>
</tbody>
</table>
<?php endif ?>



<button @click.prevent="openFileSelector();resetResults()" @drop.prevent="dropped" class="button">
	Dateien Hochladen
	<div v-if="loading" class="loadIndicator white"><div></div><div></div><div></div></div>
</button>
<input ref="fileSelector" multiple style="display:none" type="file" @change="gatherFiles">

<hr>

<section class="flex" style="gap:1em; flex-wrap: wrap;">

<div class="box" v-for="result in results">

	<h3>{{result.name}}
		<span v-if="loaders[result.index]">wird verarbeitet</span> <div v-if="loaders[result.index]" class="loadIndicator"><div></div><div></div><div></div></div>
	</h3>

	<table v-if="result.status" class="fancy">
	<thead>
		<tr>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Ort</th>
			<th>Geburtstag</th>
			<th>Alter</th>
		</tr>
	</thead>
	<tbody>

	<tr v-for="entry in result.status">
		<td>{{entry.firstname}}</td>
		<td>{{entry.lastname}}</td>
		<td>{{entry.location}}</td>
		<td>{{entry.birthday}}</td>
		<td>{{entry.age}}</td>
	</tr>	

	</tbody>
	</table>
</div>
</section>




<section class="box">
	<h3>Heute Importiert</h3>

	<table v-if="imported" class="fancy">
	<thead>
		<tr>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Ort</th>
			<th>Datum</th>
			<th>Alter</th>
			<th>Import Ressort</th>
			<th>Prompt</th>
		</tr>
	</thead>
	<tbody>

	<tr v-for="entry in imported">
		<td>{{entry.firstname}}</td>
		<td>{{entry.lastname}}</td>
		<td>{{entry.location}}</td>
		<td>{{entry.birthday}}</td>
		<td>{{entry.age}}</td>
		<td>{{entry.ressort}}</td>
		<td>{{entry.type}}</td>
	</tr>	

	</tbody>
	</table>
</section>

<a class="button" href="/export/cue">im Cue Zeigen</a>


</main>
