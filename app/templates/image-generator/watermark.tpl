<link rel="stylesheet" type="text/css" media="all" href="/styles/css/imageprocessor.css" />

<main id="chatapp">

<h1><?=$page['title']?></h1>

<image-processor
data-image="<?=$image?>"
data-padding-horizontal="3%" data-padding-vertical="3%" data-opacity="0.7" data-suffix="-ai">

<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI_white.svg" data-label="Ai | weiß" data-default-size="50"></watermark>
<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI_black.svg" data-label="Ai | schwarz" data-default-size="50"></watermark>

<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI GENERATED_white.svg" data-label="Ai generated | weiß" data-default-size="150"></watermark>
<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI GENERATED_black.svg" data-label="Ai generated | schwarz" data-default-size="150"></watermark>
<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI MODIFIED_white.svg" data-label="Ai modified | weiß" data-default-size="150"></watermark>
<watermark data-url="/styles/wasm/image-processor/icons/LABEL_AI MODIFIED_black.svg" data-label="Ai modified | schwarz" data-default-size="150"></watermark>
</image-processor>

</main>