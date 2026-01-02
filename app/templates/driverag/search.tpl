<main>


<h1>Drive - RAG Search Beta <?php if ($result): ?>[Ergebnisse: <?=count($result)?>]<?php endif ?></h1>


<?php if ($query && empty($result)): ?>
<div class="error-message mb">Suche ergab - Keine Ergebnisse -</div>
<?php endif ?>


<form method="post" action="" class="box">
	
<script type="text/javascript" src="/styles/js/moment.js"></script>

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

	<fieldset class="grid-2-col">
		
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

		<label>Ressort filtern: <span class="label-help"></span>
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

	<label>Texteingabe:</label>
	<textarea class="mb" style="height:120px;" name="query" placeholder="was möchten sie suchen??"><?=$query?></textarea>
	</label>

	<button type="submit">Suchen</button>

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

</form>

<?php if ($result): ?>
<?php include tpl('driverag/article-table');?>
<?php endif ?>

</main>