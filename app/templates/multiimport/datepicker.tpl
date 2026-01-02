<form action="" method="get" class="filter-picker-form" onchange="this.submit()">
	
	<label>Ausgabe filtern:
	<select name="filter" class="ressort-picker">
		<option value="">Alle</option>
		<?php foreach (IMPORT_RESSORTS as $ressort): ?>
		<?php if ($filter == $ressort): ?>
		<option selected value="<?=$ressort?>"><?=$ressort?>
		<!--<?php if (isset($stats['ressort'][$ressort])): ?>
		[<?=$stats['ressort'][$ressort] ?? ''?>]
		<?php endif ?>-->
		</option>
		<?php else: ?>
		<option value="<?=$ressort?>"><?=$ressort?>
		<!--<?php if (isset($stats['ressort'][$ressort])): ?>
		[<?=$stats['ressort'][$ressort] ?? ''?>]
		<?php endif ?>-->
		</option>
		<?php endif ?>
		<?php endforeach ?>
	</select>
	</label>



	<label>Kalenderwoche wählen:
	<select name="kw" class="kw-picker">

		<?php foreach ($weeks as $week): ?>
		<?php if ($week['week'] == $currentWeek): ?>
		<?php $class='current-week'; $selected = '';?>
		<?php else: ?>
		<?php $class=''; $selected = '';?>			
		<?php endif ?>

		<?php if ($week['week'] == $selectedWeek): ?>
		<?php $selected = 'selected';?>
		<?php endif ?>

		<option <?=$selected?> value="<?=$week['week']?>" class="<?=$class?>">
			KW <?=$week['week']?> - Einträge: [<?=$week['entries']?>]
		</option>


		<?php endforeach ?>
	</select>
	</label>

</form>