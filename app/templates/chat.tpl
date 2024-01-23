<main v-cloak id="gptInterface" data-portal="<?=PORTAL?>">

<!--
<div class="fun-fact hide-mobile">
<b>ChatGPT4-Turbo und Dall-E3 stehen zur Verfügung!</b><br>Das neue Model bietet schnellere Antwortzeiten und eine Datenbasis bis April 2023</b>
</div>
-->

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

<p v-if="description" v-html="'Hinweis: ' + description"></p>
<p v-else class="hide-mobile">Hinweis: mit der Tastenkombination <b>TAB + Leertaste</b>, lässt sich eine Nachricht schnell abschicken.</p>

<form method="post" @submit.prevent="ask" action="" ref="form" class="form-container" :class="{'advanced-model' : gpt4}">

<p v-if="error" v-cloak class="error-message" v-html="error"></p>

<div class="ui-header">
	<div class="options">
		<label>Prompt auswählen:	
		<?php if (auth_rights('chatgpt')): ?><a v-if="!isNaN(action)" :href="'/settings/'+action">(Prompt editieren)</a><?php endif ?>

		<select v-model="action" ref="selectElement" name="action" @change="wipeHistory(); setPromptSettings();">
			<?php if (!$category['hideDefault']): ?>
			<option value="default">Standard Chat</option>
			<?php endif ?>
			<?php if ($category['promptless'] ?? false): ?>
			<option value="unbiased">ChatGPT ohne Prompt</option>
			<?php endif ?>
			<?php foreach ($prompts as $prompt): ?>
			<?php if ($prompt['direct']) {continue;} ?>	
			<option value="<?=$prompt['id']?>" data-description="<?=$prompt['description'] ?? ''?>" data-advanced="<?=$prompt['advanced'] ?? ''?>"><?=$prompt['title']?></option>
			<?php endforeach ?>
		</select>
		</label>

		<?php if ($category['articleImport'] ?? false): ?>
		<label class="hide-mobile">Importieren:
			<input type="text" @input="importArticle" placeholder="Artikel URL eintragen">
		</label>
		<?php endif ?>
	</div>


	<?php $directPrompts = array_filter($prompts, function($prompt) {return $prompt['direct'] == true;});?>

	<div class="direct-actions hide-mobile">
	<?php if ($directPrompts): ?>
	<label>direkt Aktionen:</label>
	<div class="button-group small mtsmall">
	<?php foreach ($directPrompts as $prompt): ?>
		<button type="button" class="light" @click.prevent="ask" data-direct-id="<?=$prompt['id']?>"><?=$prompt['title']?></button> 
	<?php endforeach ?>
	</div>
	<?php endif ?>
	</div>

	<div class="meta-options hide-mobile">
		<div class="force-gpt4">
			<label><input v-model="gpt4" type="checkbox"> GPT-4-Turbo aktivieren
			</label>
			<p v-if="gpt4">Antworten genauer, <br/>Kosten höher.</p>
		</div>
	</div>

	<!--<button class="show-mobile send-mobile-btn" type="submit" @click.prevent="ask" :disabled="loading">Senden</button>	-->
</div>


<div class="grid-2-col">

	<section class="user-input">
		<div class="file-button no-select" onclick="event.preventDefault(); document.querySelector('#pdfupload').click()">Datei einfügen (Mp3,Word,PDF,Excel)</div>
		<input style="display:none" id="pdfupload" type="file" name="file" @change="uploadFile">

		<label>Eingabe:
		<textarea v-model="input" ref="autofocusElement" class="io-textarea" :disabled="loading" placeholder="Text oder Frage eingeben - Anweisungen werden im Verlauf gespeichert und müssen nicht mehrmals übergeben werden"></textarea>
		</label>
		
		<button type="submit" @click.prevent="ask" v-if="!loading">Absenden</button>
		<button type="submit" class="stop" @click.prevent="stopStream" v-if="loading">generierung abbrechen</button>

		<button class="light mlsmall del-historie" tabindex="-1" type="button" @click="wipeHistory()" :disabled="loading">Chatverlauf löschen</small></button>
		<button class="light mlsmall del-historie" tabindex="-1" type="button" @click="wipeInput()" :disabled="loading">Eingabe löschen</button>
	</section>

	<section class="gpt-output">
		<div class="copy-button no-select" @click="copyOutputToClipboard">Text kopieren</div>
		<label v-if="markdown == true" class="no-select">Ausgabe:</label>
		<div v-if="markdown == true" v-html="output" class="io-textarea io-output-div" placeholder=""></div>
		<label v-else>Ausgabe:
			<textarea v-model="output" class="io-textarea" placeholder=""></textarea>
		</label>
		<div class="fright small">
			<span class="ml loading-wrapper" v-if="loading">
				<div class="loadIndicator"><div></div><div></div><div></div></div> generiere - abbrechen <b>[ESC]</b>
				<img class="mini-robot" src="/styles/img/ai-buddy.svg">
			</span>
			<span v-if="responsetime" class="ml">Antwortzeit: <b>{{ responsetime }}&thinsp;s</b> | Tokens: <b>{{ tokens }}</b> | Zeichen: <b>{{ chars }}</b></span>
		</div>
	</section>

</div>

</form>

<details v-if="history" :open="historyExpanded">
	<summary @click.self.prevent="historyExpanded = !historyExpanded">Chatverlauf einblenden</summary>
	<table class="fancy history wide">
		<tr :class="entry.role.toLowerCase()" v-for="entry in history"> 
			<td class="ucfirst">{{entry.role}}</td>
			<td><pre @click="copyToInput">{{entry.content}}</pre></td>
		</tr>
	</table>

	<div class="text-right small">
	<button class="button light" type="button" @click="copyHistoryToClipboard">Chatverlauf kopieren</button>
	<!--&ensp;<a class="button light" target="_blank" :href="'/conversation/' + conversationID">Chatverlauf teilen</a>-->
	</div>

</details>

</main>