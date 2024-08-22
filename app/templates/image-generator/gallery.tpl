<figure class="image-history">
<?php foreach ($lastimages as $image): ?>
	<a href="<?=$image['path']?>" data-pswp-width="<?=$image['width']?>" data-pswp-height="<?=$image['height']?>" target="_blank" title="<?=$image['prompt']?>">
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

lightbox.on('uiRegister', function() {
	lightbox.pswp.ui.registerElement({
		name: 'custom-caption',
		order: 9,
		isButton: false,
		appendTo: 'root',
		html: 'Caption text',
		onInit: (el, pswp) => {
			lightbox.pswp.on('change', () => {
				const currSlideElement = lightbox.pswp.currSlide.data.element;
				let captionHTML = 'Prompt';
				if (currSlideElement) {captionHTML = currSlideElement.title}
				el.classList.remove('hidden')
				if (!captionHTML) {el.classList.add('hidden')}
				el.innerHTML = captionHTML || 'kein Prompt erkannt';
			});
		}
	});
});
lightbox.init();
</script>


<style>
.pswp__custom-caption {background: rgba(0, 0, 0, .2); color: #fff; padding: .5em 1em; 
	 width:100%; text-align:center; font-size:0.9em; position: absolute; bottom: 0px;}
.pswp__custom-caption.hidden {display:none;}
</style>