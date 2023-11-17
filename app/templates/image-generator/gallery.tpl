<figure class="image-history">
<?php foreach ($lastimages as $image): ?>
	<a href="<?=$image['path']?>" data-pswp-width="<?=$image['width']?>" data-pswp-height="<?=$image['height']?>" target="_blank">
		<img width="<?=$image['width']?>" height="<?=$image['height']?>" loading="lazy" src="<?=$image['path']?>">
	</a>
<?php endforeach ?>
</figure>

<link rel="stylesheet" type="text/css" media="all" href="/styles/gallery/photoswipe.css" />
<script src="/styles/gallery/photoswipe.umd.min.js"></script>
<script src="/styles/gallery/photoswipe-lightbox.umd.min.js"></script>
<script src="/styles/gallery/magic-grid.js"></script>
<script>
let magicGrid = new MagicGrid({
	container: '.image-history',
	animate: true,
	gutter: 30,
	static: true,
});

magicGrid.listen();

let lightbox = new PhotoSwipeLightbox({
	gallery: '.image-history',
	children: 'a',
	initialZoomLevel: 'fit',
	bgOpacity: .8,
	showHideAnimationType: 'zoom',
	pswpModule: PhotoSwipe
});
lightbox.init();
</script>

<!--
<style>
.pswp img {max-width: none; object-fit: contain !important;}
</style>
-->
