<?php foreach ($result as $entry): ?>
<article class="box">
<h3><a target="_blank" href="<?=$entry['url']?>"><?=$entry['article_title']?></a></h3>
<p><b>Pubdate: <?=date('Y-m-d H:i', strtotime($entry['publish_timestamp']))?> | Score: <?=round($entry['score'],3)?> | ID: <?=$entry['article_id']?></b></p>
<p><?=nl2br($entry['article_text'])?></p>
</article>
<?php endforeach ?>

<details><summary>Json einblenden</summary>
<?=json_encode($result);?>	
</details>