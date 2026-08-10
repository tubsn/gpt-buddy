<link rel="stylesheet" type="text/css" media="all" href="/styles/css/imageprocessor.css" />

<main id="chatapp">

<h1><?=$page['title']?></h1>

<image-processor
data-image="<?=$image?>"
data-padding-horizontal="3%" data-padding-vertical="3%" data-opacity="0.7" data-suffix="-ai">

<watermark data-url="/assets/image-processor/icons/LABEL_AI_white.svg" data-label="KI involviert | hell" data-default-size="50"></watermark>
<watermark data-url="/assets/image-processor/icons/LABEL_AI GENERATED_white.svg" data-label="komplett erzeugt von KI | hell" data-default-size="150"></watermark>
<watermark data-url="/assets/image-processor/icons/LABEL_AI MODIFIED_white.svg" data-label="nur bearbeitet per KI | hell" data-default-size="150"></watermark>
<watermark data-url="/assets/image-processor/icons/LABEL_AI_black.svg" data-label="KI involviert | dunkel" data-default-size="50"></watermark>
<watermark data-url="/assets/image-processor/icons/LABEL_AI GENERATED_black.svg" data-label="komplett erzeugt von KI | dunkel" data-default-size="150"></watermark>
<watermark data-url="/assets/image-processor/icons/LABEL_AI MODIFIED_black.svg" data-label="nur bearbeitet per KI | dunkel" data-default-size="150"></watermark>
</image-processor>

</main>