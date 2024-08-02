<main>

<style>
	.cue-article {max-width:370px;}
</style>


<h3>Geburtstage</h3>

<section class="cue-article">
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

</main>