<main style="max-width:1800px; width:90%; margin:0 auto; margin-top:2em"> 

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<h1>Ai-Buddy Stats | <span class="primary-highlight"><?=$usage?></span> Gesamtgespräche mit im Schnitt <span class="secondary-highlight"><?=gnum($length,2)?></span> Nachrichten</h1>

<p>Gespräche mit Prompts: <?=gnum($promptusage)?> | Gespräche ohne Prompt / Standard Chat: <?=gnum($usage-$promptusage)?></p>

<section class="box" >


<figure class="grid-2-1">
<div style="margin-top:-4em; margin-bottom:-6em; margin-left:-10em; margin-right:-5em; align-self: center;">
<?=$typeChart?>
</div>

<div style="align-self: center; font-size:0.9em; line-height:140%; position:relative; left:-5em;">
<h3>Anfragen nach Rubriken</h3>
<p >
<b>Wissensaufbau:</b> Recherchefragen zu verschiedenen Themen wie Geschichte, Geographie, Wissenschaft, Popkultur usw.<br>
<b>Empfehlungen:</b> Fragen nach Empfehlungen für Filme, Bücher, Restaurants, Reiseziele, Produkte usw.<br>
<b>Problemlösungen:</b> Fragen zu technischen Schwierigkeiten, zwischenmenschlichen Beziehungen, persönlichen Herausforderungen usw.<br>
<b>Sprachliche Unterstützung:</b> Fragen zur Rechtschreibung, Grammatik, Übersetzungen oder allgemeine sprachliche Unterstützung.<br>
<b>Kreativität:</b> Inspiration für Schreibprojekte, Grafiken, Anzeigen oder Ideen für neue Kreationen.<br>
<b>Unterhaltung:</b> Plaudern, Witze erzählen oder Zeit vertreiben.<br>
<small style="font-size:0.6em;position:relative; top:0.5em">*Hinweis: in der Grafik wurde Wissensaufbau entfernt, da hier >40% der Anfragen landen.</small>
</p>


</div>

</figure>


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
