<main style="max-width:1800px; width:90%; margin:0 auto; margin-top:2em"> 

<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<h1>Ai-Buddy Stats | <?=$page['title']?></h1>

<section class="box" >
	<figure class="chartbox ">
	<?=$chart?>
	</figure>


<details>
	<summary>Details anzeigen</summary>
	<table class="fancy" style="font-size:1em">
		<tr>
			<th>Zeit</th>
			<th>Conversations</th>
		</tr>
		<?php foreach ($data as $key => $amount): ?>
		<tr> 
			<td><?=$key?></td>
			<td><?=$amount?></td>
		</tr>
		<?php endforeach ?>
	</table>
</details>

</section>




</main>
