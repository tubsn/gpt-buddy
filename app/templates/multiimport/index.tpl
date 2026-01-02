<main id="multiImportApp">


<a class="fright button" href="/multiimport/archive">Importierte Daten anzeigen</a>


<h1><?=$page['title']?></h1>

<div v-if="errorMessage" class="error-message mb">
	{{ errorMessage }}
</div>

<p>Bitte beachten: Die Datenverarbeitung von <b>PDF's mit gescannten Inhalten / Bilddaten ist nicht möglich</b>. In dem Fall einfach die PDF Datei öffnen, einen Screenshot der Daten machen (SHIFT+WIN+S) und diesen über Strg+V in den Importassistenten einfügen. Dies gilt auch für mehrseitige PDF Daten ohne Textinhalt.</p>


<div class="box parameters">

<label>Import Funktion:
<select v-model="prompt" data-name="prompt">
	<?php foreach ($prompts as $prompt): ?>
	<option value="<?=$prompt['id']?>" data-description="<?=$prompt['description'] ?? ''?>" data-advanced="<?=$prompt['advanced'] ?? ''?>"><?=$prompt['title']?></option>
	<?php endforeach ?>
</select>
</label>

<label>Ressort / Importkreis auswählen:
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
<label>Direktimport:
<textarea v-model="input" class="large" placeholder="Rohtext zum Importieren einfügen z.B. Text aus einer E-Mail, oder Word Datei"></textarea>
</label>



<button type="button" @click="importText" class="button">Text importieren</button>&nbsp; 

<button @click.prevent="openFileSelector();resetResults()" @drop.prevent="dropped" class="button">
	<img class="cloud" src="/styles/img/upload-icon-white.svg">
	Dateien Hochladen <small>(jpg, png, doc, pdf, xls)</small>
</button>

<input ref="fileSelector" multiple style="display:none" type="file" @change="gatherFiles">

<div v-if="loading" class="loadIndicator"><div></div><div></div><div></div></div>

</div>

<hr>
<?php include tpl('multiimport/import-preview');?>

</main>
