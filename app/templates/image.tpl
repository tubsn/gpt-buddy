<main>
<h1><?=APP_NAME?> | Image Assistent</h1>

<form method="post" action="" class="form-container" style="display:flex; gap:1em; align-items:center">
<input type="text" name="question" placeholder="Bildbeschreibung eingeben" value="<?=$question ?? ''?>">
<button type="submit">Bilder&nbsp;generieren</button>
</form>


<figure class="generated-images grid-3-col">
<img src="/generated-image-0.jpg?cachebust=<?=time()?>">
<img src="/generated-image-1.jpg?cachebust=<?=time()?>">
<img src="/generated-image-2.jpg?cachebust=<?=time()?>">
</figure>

</main>