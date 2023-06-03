<main>

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

	<table class="fancy" style="font-size:1em">
		<tr>
			<th>Day</th>
			<th>Conversations</th>
		</tr>
		<?php foreach ($conversationsByDay as $day => $amount): ?>
		<tr> 
			<td><?=$day?></td>
			<td><?=$amount?></td>
		</tr>
		<?php endforeach ?>
	</table>

	<table class="fancy" style="font-size:1em">
		<tr>
			<th>Day</th>
			<th>Time</th>
			<th>ID</th>
		</tr>
		<?php foreach ($conversations as $conversation): ?>
		<tr> 
			<td><?=$conversation['day']?></td>
			<td><?=$conversation['time']?></td>
			<td><a href="/conversation/<?=$conversation['id']?>"><?=$conversation['id']?></a></td>
		</tr>
		<?php endforeach ?>
	</table>

</main>