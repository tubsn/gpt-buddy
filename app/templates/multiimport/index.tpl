<main id="multiImportApp">

<h1><?=$page['title']?></h1>


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
			<th>Datum</th>
			<th>Typ</th>
		</tr>
	</thead>
	<tbody>

	<tr v-for="entry in result.status">
		<td>{{entry.Vorname}}</td>
		<td>{{entry.Nachname}}</td>
		<td>{{entry.Ort}}</td>
		<td>{{entry.Datum}}</td>
		<td>{{entry.Typ}}</td>
	</tr>	

	</tbody>
	</table>
</div>
</section>

</main>
