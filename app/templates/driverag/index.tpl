<main>
<h1>Retrieval Augmented Generation | RAG-Buddy</h1>

<p>Das Drive-RAG System durchsucht ein Vektorisiertes Artikelarchiv und fertigt auf Grundlage der relevantesten Ergebnisse einen Artikel. <br><b>Hinweis: die Generierung dauert einige Zeit</b>.</p>

<form method="post" action="" class="box">

	<script type="text/javascript" src="/styles/js/moment.js"></script>

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

	<label>Userneed für Ausgabe wählen:
	<select name="userneed">
		<option selected><?=$userneed?></option>
		<?php foreach ($userneedOptions as $option): ?>
		<?php if ($option === $userneed) continue; ?>
		<option><?=$option?></option>
		<?php endforeach; ?>
	</select>
	</label>

	<label>Textlänge für Ausgabe wählen:
	<select name="length">
		<option selected><?=$length?></option>
		<?php foreach ($lengthOptions as $option): ?>
		<?php if ($option === $length) continue; ?>
		<option><?=$option?></option>
		<?php endforeach; ?>		
	</select>
	</label>
	</fieldset>

<fieldset class="grid-4-wide">
	<label>Zeitraum im Archiv wählen:
	<select class="js-timeframe-select" name="timeframe">
		<?php foreach ($timeframeOptions as $option): ?>
		<?php if ($option === $timeframe): ?>
		<option selected>Zeitraum wählen</option>				
		<?php endif ?>
		<option><?=$option?></option>
		<?php endforeach; ?>		
	</select>
	</label>

	<label>Von: <input type="date" value="<?=$from ?? null?>" class="js-from" name="from"></label>
	<label>Bis: <input type="date" value="<?=$to ?? null?>"class="js-to"name="to"></label>

	<label>Exakte Suche:
	<select name="exact">
		<option  value="0">inaktiv</option>				
		<option <?php if ($exact ?? null): ?>selected<?php endif ?> value="1">aktiv</option>				
	</select>
	</label>
</fieldset>

<script>
(function () {
	const elementSelect = document.querySelector('.js-timeframe-select');
	const elementFrom = document.querySelector('.js-from');
	const elementTo = document.querySelector('.js-to');
	if (!elementSelect || !elementFrom || !elementTo || typeof moment === 'undefined') return;

	moment.updateLocale('de', { week: { dow: 1 } });

	const toDateString = date => date.format('YYYY-MM-DD');
	const setRange = (start, end) => {
		elementFrom.value = toDateString(start);
		elementTo.value = toDateString(end);
	};

	const actions = {
		'diese woche': () => setRange(moment().subtract(6, 'days').startOf('day'), moment().endOf('day')),
		'dieser monat': () => setRange(moment().startOf('month'), moment().endOf('month')),
		'3 monate': () => setRange(moment().subtract(3, 'months').startOf('day'), moment().endOf('day')),
		'alles': () => { elementFrom.value = ''; elementTo.value = ''; }
	};

	const apply = value => (actions[(value || '').trim().toLowerCase()] || (() => {}))();

	elementSelect.addEventListener('change', event => apply(event.target.value));
	apply(elementSelect.value);

})();
</script>


	<fieldset class="grid-3-wide">
		
		<label>Artikel importieren:
			<input class="js-import-article" type="text" placeholder="Url vom Artikel eintragen">
		</label>

		<!--
		<fieldset><label>Ressorts filtern: <span class="label-help">(freilassen für alle)</label><br>
			<div class="check-group">
				<label><input type="checkbox" value="Karlsruhe" name="ressorts[]"> Karlsruhe</label>
				<label><input type="checkbox" value="Mittelbaden" name="ressorts[]"> Mittelbaden</label>
				<label><input type="checkbox" value="Kraichgau" name="ressorts[]"> Kraichgau</label>
				<label><input type="checkbox" value="Pforzheim" name="ressorts[]"> Pforzheim</label>
			</div>
		</fieldset>
		-->

		<label>Rubrik filtern: <span class="label-help"></span>
		<input type="text" name="ressorts" list="bnn-ressorts" placeholder="z.B. Karlsruhe oder Ettlingen" value="<?=$ressorts ?? ''?>"></label>
		<?php include tpl('driverag/ressorts');?>
		<label>Tags filtern: <span class="label-help">(mehrere Kommasepariert)</span>
		<input type="text" name="tags" list="bnn-tags" placeholder="z.B. Restaurants, Wandern" 
		<?php if (isset($tags) && !empty($tags)): ?>value="<?= implode(", ", $tags)?>" <?php endif ?>
		></label>

		<datalist id="bnn-tags">
			<?php foreach ($taglist as $tag): ?>
			<option><?=$tag?></option>
			<?php endforeach ?>
		</datalist>

		<style>
			.check-group {margin-top:-0.05em; background:white;padding:0.125em 0.3em; box-sizing:border-box; border: 1px solid #ddd; display:flex; gap:0.5em;}
			.check-group label {cursor:pointer;  padding:0.1em 0.3em; border-radius:0.3em;}
			.check-group label:hover {background:#efefef;}
			.label-help {opacity:0.6; font-size:0.9em;}
		</style>

	</fieldset>


<hr>

	<label>Texteingabe: 
	<textarea class="mb js-input-area" style="height:120px;" name="query" placeholder="Worüber möchten sie schreiben?"><?=$query?></textarea>
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

		<script>
			(function () {

				const inputField = document.querySelector('.js-import-article');
				if (!inputField) {return}

				let output = document.querySelector('.js-input-area');
				if (!output) {return}

				inputField.addEventListener('input', async event => {
					await importArticle(event);
				});


				async function importArticle(event) {

					let loading = document.querySelector('.loadIndicator');
					loading.style.display = 'inline-block';

					let value = event.target.value || null;
					if (!value) {this.loading = false; return}

					let formData = new FormData()
					formData.append('url', value)

					let response = await fetch('/import/article', {method: "POST", body: formData})
					if (!response.ok) {output.value = 'URL ungültig oder Artikel nicht gefunden'; loading.style.display = 'none'; return}

					let json = await response.json()
					.catch(error => {output.value = error; loading.style.display = 'none'; return})
					output.value = json.content || ''
					loading.style.display = 'none';

				}

		})();

		</script>

<?php if ($query): ?>

<hr>

<h3>Suchparameter:</h3>

<div class="box">
	<p>Anfrage: <?=$query ?? null?></p>
	<p>Userneed: <?=$userneed ?? null?> | RAG Keywords: <?=$phrase ?? null?></p>
	<details>
		<summary>Relevante Artikel (<?=count($rag)?>):</summary>
		<?php if ($rag): ?>
		<?php $result = $rag;?>
		<?php include tpl('driverag/article-table');?>
		<?php else: ?>
		Keine relevanten Artikeldaten gefunden.
		<?php endif ?>
	</details>
</div>

<hr> 
<h3>Ausgabe:</h3>
<article class="box output mb"><?=$article ?? null?></article>
<script>sessionStorage.input = document.querySelector(".output").innerText;</script>
<a class="button" href="/">Text weiterverarbeiten</a>
<?php endif ?>

</main>