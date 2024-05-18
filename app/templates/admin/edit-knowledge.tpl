<main>


<h1>Knowledge editieren: <?=$knowledge['title']?></h1>
<p>
Hier lassen sich Grunddaten zum Unternehmen wie z.B. Audiencedaten hinterlegen, die über die Callback Option in einen Prompt injiziert werden können.
</p>

<form class="form-container" method="post" action="">

<label>Knowledge Bezeichnung:
	<input name="title" type="text" placeholder="Der Name wird als im Callback Aufruf verwendet" value="<?=$knowledge['title'] ?? null?>">
</label>

<label>Beschreibung:
	<textarea name="description" type="text" placeholder="Optionale Infos zu den Angaben"><?=$knowledge['description'] ?? null?></textarea>
</label>

<label>Inhalt:
	<textarea class="settings-textarea" name="content" placeholder="z.B. Infos zu Audiences"><?=$knowledge['content'] ?? null?></textarea>
</label>

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