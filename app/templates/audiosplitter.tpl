<main>

<h1>Audio Splitter</h1>

<form action="" method="post" enctype="multipart/form-data">
<input type="file" name="audio">
<button type="submit">Los</button>
</form>

<hr>

<h3>zuletzt gesplittet</h3>
<?php foreach ($files as $file): ?>
<a target="_blank" href="/audio/<?=$file?>"><?=$file?></a><br>
<?php endforeach ?>


</main>