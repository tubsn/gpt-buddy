<main>

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

	<table class="fancy history" style="font-size:1em">
		<?php foreach ($conversation as $message): ?>
		<tr> 
			<td class="ucfirst"><?=$message['role']?></td>
			<td><pre><?=$message['content']?></pre></td>
		</tr>
		<?php endforeach ?>
	</table>

</main>