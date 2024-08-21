<form action="" method="get" class="filter-picker-form" onchange="this.submit()">
	
	<label>Ausgabe filtern:
	<select name="filter" class="ressort-picker">
		<option value="">Alle</option>
		<?php foreach (IMPORT_RESSORTS as $ressort): ?>
		<?php if ($filter == $ressort): ?>
		<option selected value="<?=$ressort?>"><?=$ressort?></option>
		<?php else: ?>
		<option value="<?=$ressort?>"><?=$ressort?></option>
		<?php endif ?>
		<?php endforeach ?>
	</select>
	</label>

	<label>Kalenderwoche wählen:
	<select name="date" class="kw-picker">

		<?php foreach ($months as $month => $date): ?>

		<?php if (str_contains($month, $currentWeek)): ?>
		<?php $class='current-week'; $selected = 'selected';?>
		<?php else: ?>
		<?php $class=''; $selected = '';?>			
		<?php endif ?>

		<?php if ($from != 'today' && $date['start'] == $from): ?>
		<option selected value="<?=$date['start']?>" class="<?=$class?>"><?=$month?></option>
		
		<?php else: ?>
		<?php if ($from != 'today'): ?>
		<option value="<?=$date['start']?>" class="<?=$class?>"><?=$month?></option>
		<?php else: ?>
		<option <?=$selected?> value="<?=$date['start']?>" class="<?=$class?>"><?=$month?></option>
		<?php endif ?>
		<?php endif ?>

		<?php endforeach ?>
	</select>
	</label>

</form>