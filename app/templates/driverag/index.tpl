<main>
<h1>Retrieval Augmented Generation | RAG-Buddy</h1>

<p>Das Drive-RAG System durchsucht ein Vektorisiertes Artikelarchiv und fertigt auf Grundlage der relevantesten Ergebnisse einen Artikel. <br><b>Hinweis: die Generierung dauert einige Zeit</b>.</p>

<form method="post" action="" class="box">

	<fieldset class="grid-3-col">
	<label>Prompt wählen:
	<select name="prompt">
		<option selected value="<?=$prompt['id']?>"><?=$prompt['title']?></option>
		<?php foreach ($promptOptions as $option): ?>
		<?php if ($option['title'] === $prompt['title']) continue; ?>
		<option value="<?=$option['id']?>"><?=$option['title']?></option>
		<?php endforeach; ?>
	</select>
	</label>

	<label>Userneed wählen:
	<select name="userneed">
		<option selected><?=$userneed?></option>
		<?php foreach ($userneedOptions as $option): ?>
		<?php if ($option === $userneed) continue; ?>
		<option><?=$option?></option>
		<?php endforeach; ?>
	</select>
	</label>

	<label>Textlänge wählen:
	<select name="length">
		<option selected><?=$length?></option>
		<?php foreach ($lengthOptions as $option): ?>
		<?php if ($option === $length) continue; ?>
		<option><?=$option?></option>
		<?php endforeach; ?>		
	</select>
	</label>	
	</fieldset>

	<label>Texteingabe: 
	<textarea class="mb" style="height:120px;" name="query" placeholder="Worüber möchten sie schreiben?"><?=$query?></textarea>
	</label>

	<button class="js-submit" type="submit">Archiv durchsuchen und Artikel generieren<div class="loadIndicator white"><div></div><div></div><div></div></div></button>

	<?php if ($errorMessage): ?>
	<div class="error-message"><?=$errorMessage?></div>
	<?php endif ?>
	
</form>

<style>.loadIndicator {display: none}</style>
<script>
document.querySelector('.js-submit').addEventListener('click', (event) => {
event.preventDefault();
document.querySelector('.loadIndicator').style.display = 'inline-block';
document.querySelector('form').submit();
});
</script>

<?php if ($query): ?>

<hr>

<h3>Suchparameter:</h3>

<div class="box">
	<p>Userneed: <?=$userneed ?? null?> | RAG Keywords: <?=$phrase ?? null?></p>
	<details>
		<summary>Relevante Artikel (<?=count($rag)?>):</summary>
		<?php if ($rag): ?>
		<?=table_dump($rag);?>
		<?php else: ?>
		Keine relevanten Artikeldaten gefunden.
		<?php endif ?>
	</details>
</div>

<hr> 
<h3>Ausgabe:</h3>
<article class="box mb"><?=$article ?? null?></article>
<?php endif ?>

</main>