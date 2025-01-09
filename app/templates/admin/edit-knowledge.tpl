<main>


<h1>Knowledge editieren: <?=$knowledge['title']?></h1>
<p>
Hier lassen sich Grunddaten zum Unternehmen wie z.B. Audiencedaten hinterlegen, die über die Callback Option in einen Prompt injiziert werden können.<br>
Knowledge Daten werden in folgender Reihenfolge an das Modell übergeben: <b>Knowledge Inhalt -> Import Inhalt -> Prompt Inhalt -> Nutzereingaben</b>
</p>

<form class="form-container" method="post" action="">

<div class="grid-3-col">
	<label>Knowledge Bezeichnung:
		<input name="title" type="text" placeholder="Der Name wird als im Callback Aufruf verwendet" value="<?=$knowledge['title'] ?? null?>">
	</label>

	<label>Import URL:
		<input name="url" type="text" placeholder="Webseite von der Daten importiert werden können" value="<?=$knowledge['url'] ?? null?>">
	</label>
	<label>Import CSS-Selektor:
		<input name="selector" type="text" placeholder="z.B. main-container oder article.main" value="<?=$knowledge['selector'] ?? null?>">
	</label>		
</div>

<label>Beschreibung:
	<textarea name="description" type="text" placeholder="Optionale Infos zu den Angaben"><?=$knowledge['description'] ?? null?></textarea>
</label>

<div class="grid-2-col">
<label>Inhalt:
	<textarea class="settings-textarea" name="content" placeholder="z.B. Infos zu Audiences"><?=$knowledge['content'] ?? null?></textarea>
</label>

<label>Importierter Inhalt (nicht editierbar):
	<textarea class="settings-textarea" disabled placeholder="Um Inhalte zu importieren bitte eine URL angeben und einen CSS-Selektor"><?=$import?></textarea>
</label>
</div>


<hr class="black">

<button class="submit">Angaben speichern</button>&ensp;
<a class="button light" href="/settings/knowledge">zurück zur Übersicht</a>


</form>

<?php if (isset($knowledge['edited'])): ?>
<small class="fright">
Editiert: <?=formatDate($knowledge['edited'],'d.m.Y H:i')?>
</small>
<?php endif ?>


</main>