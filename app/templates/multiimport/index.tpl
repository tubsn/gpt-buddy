<main id="multiImportApp">


<a class="fright button" href="/multiimport/archive">Alle Importierten Daten zeigen</a>


<h1><?=$page['title']?></h1>

<div v-if="errorMessage" class="error-message mb">
	{{ errorMessage }}
</div>

<div class="box parameters">

<label>Import Funktion auswählen:
<select v-model="prompt" data-name="prompt">
	<?php foreach ($prompts as $prompt): ?>
	<option value="<?=$prompt['id']?>" data-description="<?=$prompt['description'] ?? ''?>" data-advanced="<?=$prompt['advanced'] ?? ''?>"><?=$prompt['title']?></option>
	<?php endforeach ?>
</select>
</label>

<label>Importkreis auswählen:
<select v-model="ressort" data-name="ressort" @change="remember($event, 'ressort')">
	<?php foreach (IMPORT_RESSORTS as $ressort): ?>
	<option value="<?=$ressort?>"><?=$ressort?></option>
	<?php endforeach ?>
</select>
</label>

<div>
</div>


</div>

<div class="box">
<label>Text zum Direktimport:
<textarea v-model="input" class="large" placeholder="Rohtext zum Importieren einfügen"></textarea>
</label>
<button type="button" @click="importText" class="button">Text importieren</button>&nbsp; 

<button @click.prevent="openFileSelector();resetResults()" @drop.prevent="dropped" class="button">
	<img class="cloud" src="/styles/img/upload-icon-white.svg">
	Dateien Hochladen
</button>
<input ref="fileSelector" multiple style="display:none" type="file" @change="gatherFiles">

<div v-if="loading" class="loadIndicator"><div></div><div></div><div></div></div>

</div>


<hr>
<?php include tpl('multiimport/import-preview');?>




<section v-if="imported.length > 0" class="box">

	<h3>heute Importiert</h3>

	<table v-if="imported.length > 0" class="fancy">
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


</main>
