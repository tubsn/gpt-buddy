<main class="conversation-detail">

<h1 class="text-center"><?=APP_NAME?> | <?=$page['title']?></h1>

	<table class="fancy wide conversation" style="font-size:.9em">
		<?php foreach ($conversation as $message): ?>
		<tr class="<?=strtolower($message['role'])?>"> 
			<td class="ucfirst"><?=$message['role']?></td>
			<td style="padding:20px"><?=$message['content']?></td>
		</tr>
		<?php endforeach ?>
	</table>
	<script>hljs.highlightAll();</script>

</main>