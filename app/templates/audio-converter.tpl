<main>


<div class="fright">
<fl-upload id="attachment-upload" destination="">[+] Neue Audiodatei hochladen</fl-upload>
</div>


<h1><?=$page['title']?></h1>

<hr>

<p>Mithilfe des Audio Converters können größere Audiodateien transkribiert werden. Diese werden neu komprimiert und im Anschluss an die KI gesendet. Hierbei werden Gespräche von maximal 45 Minuten unterstützt. Das Transkribieren kann, je nach Aufnahmelänge, mehrere Minuten dauern. Längere Gespräche müssen im Vorfeld über einen <a href="/import/splitter">Datei Splitter</a> in mehrere Dateien aufgeteilt werden.</p>

<?php if ($files): ?>
<?php endif ?>

<section style="display:flex; gap:1em; align-items: start;">


<textarea class="box js-input" style="min-height:600px" placeholder="Bitte zunächst eine Datei hochladen und rechts auf transkribieren drücken ..."><?=session('tts')?></textarea>


<?php foreach ($files as $index => $file): ?>
<figure class="box" style="display:flex; align-items: center; flex-direction: column; gap:0.5em; ">

	<a href="/<?=$urlpath?><?=$file?>" download title="komprimierte Audiodatei herunterladen"><?=$file?>

	<div style="margin:0 auto; text-align:center;">
	<div class="svg">
		<svg id="Flat" style="width:100px; height:100px;" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g fill="<?=CHART_COLOR ?? '#1d5e55'?>"><path d="m22 15.75c-.4141 0-.75-.3359-.75-.75v-6c0-.4141.3359-.75.75-.75s.75.3359.75.75v6c0 .4141-.3359.75-.75.75z"/><path d="m18.6665 17.75c-.4141 0-.75-.3359-.75-.75v-10c0-.4141.3359-.75.75-.75s.75.3359.75.75v10c0 .4141-.3359.75-.75.75z"/><path d="m15.3335 14.75c-.4141 0-.75-.3359-.75-.75v-4c0-.4141.3359-.75.75-.75s.75.3359.75.75v4c0 .4141-.3359.75-.75.75z"/><path d="m12 18.75c-.4141 0-.75-.3359-.75-.75v-12c0-.4141.3359-.75.75-.75s.75.3359.75.75v12c0 .4141-.3359.75-.75.75z"/><path d="m8.6665 20.75c-.4141 0-.75-.3359-.75-.75v-16c0-.4141.3359-.75.75-.75s.75.3359.75.75v16c0 .4141-.3359.75-.75.75z"/><path d="m5.3335 16.75c-.4141 0-.75-.3359-.75-.75v-8c0-.4141.3359-.75.75-.75s.75.3359.75.75v8c0 .4141-.3359.75-.75.75z"/><path d="m2 15.75c-.4141 0-.75-.3359-.75-.75v-6c0-.4141.3359-.75.75-.75s.75.3359.75.75v6c0 .4141-.3359.75-.75.75z"/></g></svg>
	</div>
	</a>
	
	<audio controls src="/<?=$urlpath?><?=$file?>"></audio>
	</div>

<a href="/import/converter/tts/<?=$index?>" class="button js-transcribe" style="">Transkribieren
	<span class="loader-wrapper hidden">
		<div class="loadIndicator white"><div></div><div></div><div></div></div>
	</span>
</a>
</figure>

<?php endforeach ?>
</section>

<script>
	const loader = document.querySelector('.loader-wrapper');
	const transcribeBtn = document.querySelector('.js-transcribe');
	if (transcribeBtn) {
		transcribeBtn.addEventListener('click', (e) => {
			loader.classList.remove('hidden');
		})
	}

	const transcribeTextarea = document.querySelector('.js-input');
	sessionStorage.setItem('input', transcribeTextarea.value);
	if (transcribeTextarea) {
		transcribeTextarea.addEventListener('change', (e) => {
			sessionStorage.setItem('input', transcribeTextarea.value);
		})
	}

</script>

<a href="/redaktion#125" class="button">Text im Ai Buddy weiteverarbeiten</a>

</main>