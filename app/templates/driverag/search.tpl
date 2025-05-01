<main>

<h1>Drive - RAG Search Beta</h1>

<form method="post" action="">
	<input type="text" name="query" placeholder="Suchbegriff..." value="<?=$query?>">
	<button type="submit">Suchen</button>
</form>

<?php if ($result): ?>
<?php foreach ($result as $article): ?>
<article class="box">
<h3><a href="<?=$article['url']?>"><?=$article['article_title']?></a></h3>
<p><b>Pubdate: <?=date('Y-m-d H:i', strtotime($article['publish_timestamp']))?> | Score: <?=round($article['score'],3)?> | ID: <?=$article['article_id']?></b></p>
<p><?=nl2br($article['article_text'])?></p>
</article>
<?php endforeach ?>


<details><summary>Json einblenden</summary>
<?=json_encode($result);?>	
</details>
<?php endif ?>



</main>