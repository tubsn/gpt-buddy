<main>

<h1>Audio Splitter</h1>

<p>Hier kann eine Audiodatei in mehrere Dateien aufgeteilt werden. Dazu die Datei auswählen und "Datei aufteilen" klicken. Im Anschluß können die Einzelteile wieder heruntergeladen werden.</p>

<hr>

<form action="" method="post" enctype="multipart/form-data">
<input type="file" name="audio">
<button type="submit">Datei aufteilen</button>
</form>

<hr>

<h3>zuletzt gesplittet</h3>
<?php foreach ($files as $file): ?>
<a target="_blank" href="/<?=$urlpath?><?=$file?>"><?=$file?></a><br>
<?php endforeach ?>


</main>