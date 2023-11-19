<main v-cloak id="gptInterface" data-portal="<?=PORTAL?>">
<!--
<div class="fun-fact hide-mobile" style="position:relative; top:-20px;">
Bitte sparsam mit dem Bildgenerator umgehen.<br>Die Generierung kostet zwischen 4 und 12 Cent
</div>
-->

<h1><?=APP_NAME?> | Bildgenerator</h1>

<p v-if="error" v-cloak class="error-message" v-html="errormessages"></p>
<?php include tpl('image-generator/generator-form');?>

<figure v-if="output" class="generated-image" :class="{'vertical' : resolution == '1024x1792' || resolution == '1024x1024'}">
	<img :src="output">
</figure>

</main>

<div style="margin-bottom:-1em;">
<?=$pager?>
</div>

<main class="gallery-container">
<?php include tpl('image-generator/gallery');?>
</main>

<?=$pager?>
