<main style="max-width:1800px; width:90%; margin:0 auto; margin-top:2em"> 

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<h1>Ai-Buddy Stats | <span class="primary-highlight"><?=$usage?></span> Gesamtgespräche mit im Schnitt <span class="secondary-highlight"><?=gnum($length,2)?></span> Nachrichten</h1>

<p>Gespräche mit Prompts: <?=gnum($promptusage)?> | Gespräche ohne Prompt / Standard Chat: <?=gnum($usage-$promptusage)?></p>

<section class="box" >

<figure class="chartbox grid-2-1">
<div><h3>Tagesentwicklung</h3>
<?=$dailyChart?>
</div>
<div>
	<h3>Monatsentwicklung</h3>
<?=$monthlyChart?>
</div>
</figure>

</section>

</main>
