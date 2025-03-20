<main>

<h1>Audio Splitter</h1>

<p>Hier kann eine Audiodatei in mehrere Dateien aufgeteilt werden. Dazu die Datei auswählen und "Datei aufteilen" klicken. Im Anschluß können die Einzelteile wieder heruntergeladen werden.</p>

<hr>

<form action="" method="post" enctype="multipart/form-data">
<input type="file" name="audio" style="max-width:600px">

<button type="submit" class="mt button">Datei aufteilen</button>

</form>

<hr>

<?php if ($files): ?>
<div class="box">
<h3>zuletzt gesplittet</h3>
<?php foreach ($files as $file): ?>
<a target="_blank" href="/<?=$urlpath?><?=$file?>"><?=$file?></a><br>
<?php endforeach ?>
<a href="/import/splitter/delete" class="mt button light danger">alle Dateien löschen</a>
</div>
<?php endif ?>


</main>