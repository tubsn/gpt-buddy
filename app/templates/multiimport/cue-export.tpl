<main id="multiImportApp">

<style>
	.cue-article {max-width:370px;}
</style>

<?php include tpl('multiimport/datepicker');?>

<h1>Geburtstage für Kalenderwoche <?=$selectedWeek?> </h1>
<h3>vom <?=formatDate($from,'d.m.Y')?> - <?=formatDate($to,'d.m.Y')?>
<?php if (!empty($filter)): ?>
 - für <?=$filter?>	
<?php endif ?></h3>


<?php if ($events): ?>
<section class="cue-article box">
<?php foreach ($events as $day => $set): ?>
<b><?=$day?></b>
<?php foreach ($set as $key => $event): ?>
<?=$event['firstname']?> 
<?=$event['lastname']?> 
(<?=$event['age']?>),
<?=$event['location']?><?php if ($key != array_key_last($set)): ?>; <?php else: ?>. <?php endif ?>
<?php endforeach ?>
<br>
<?php endforeach ?>
</section>

<?php else: ?>
<div class="box">
	<h3>Keine Termine:</h3>
	Für diesen Zeitraum sind keine Termine vorhanden. <br>Bitte wählen Sie ein anderes Datum (oben rechts), oder <a href="/multiimport">importieren</a> Sie neue Inhalte!</div>
<?php endif ?>

</main>