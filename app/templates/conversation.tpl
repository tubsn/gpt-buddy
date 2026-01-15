<main class="conversation-detail">
<div class="fright">
	<a class="button light" href="<?=$id?>/json">Json Anzeigen</a>
	<a class="button light js-apply" href="">Übernehmen</a>
</div>

<script>
document.querySelector('.js-apply').addEventListener('click', async (e) => {
	e.preventDefault()
	sessionStorage.responseID = '<?=$id?>'
	window.location = '/'
});
</script>

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