<main v-cloak id="gptInterface">

<!--
<div class="fun-fact hide-mobile">
<b>Buddy-News:</b> ...
</div>
-->

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

<!--
<p><span style="font-weight:bold; color:#b00;">Testweise wurde das neue GPT-4 Modell aktiviert. Antworten dauern heute etwas länger.</span></p>
-->

<p v-if="description" v-html="'Hinweis: ' + description"></p>
<p v-else class="hide-mobile">Hinweis: mit der Tastenkombination <b>TAB + Leertaste</b>, lässt sich eine Nachricht schnell abschicken.</p>

<form method="post" @submit.prevent="ask" action="" ref="form" class="form-container" :class="{'advanced-model' : gpt4}">

<p v-if="error" class="error-message" v-html="error"></p>

<div class="ui-header">
	<div class="options">
		<label>Anfrage Typ:	
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
		<label class="hide-mobile">Artikel importieren:
			<input type="text" @input="importArticle" placeholder="ID oder URL eintragen">
		</label>
		<?php endif ?>	

	</div>


	<?php $directPrompts = array_filter($prompts, function($prompt) {return $prompt['direct'] == true;});?>

	<div class="direct-actions hide-mobile">
	<?php if ($directPrompts): ?>
	<label>direkt Aktionen:</label>
	<div class="button-group small mtsmall">
	<?php foreach ($directPrompts as $prompt): ?>
		<button class="light" @click.prevent="ask" data-direct-id="<?=$prompt['id']?>"><?=$prompt['title']?></button> 
	<?php endforeach ?>
	</div>
	<?php endif ?>
	</div>

	<div class="meta-options hide-mobile">
		<div class="force-gpt4">
			<label><input v-model="gpt4" type="checkbox"> GPT-4 aktivieren
			</label>
			<p v-if="gpt4">Antworten genauer, <br/>Wartezeit länger.</p>
		</div>
	</div>

	<!--<button class="show-mobile send-mobile-btn" type="submit" @click.prevent="ask" :disabled="loading">Senden</button>	-->
</div>


<div class="grid-2-col">

	<section class="user-input">
		<label>Eingabe:
		<textarea v-model="input" ref="autofocusElement" class="io-textarea" :disabled="loading" placeholder="Text oder Frage eingeben - Anweisungen werden im Verlauf gespeichert und müssen nicht mehrmals übergeben werden"></textarea>
		</label>
		<button type="submit" @click.prevent="ask" :disabled="loading">Absenden</button>
		<button class="light mlsmall del-historie" tabindex="-1" type="button" @click="wipeHistory()" :disabled="loading">Neuer Chat <small>(Verlauf löschen)</small></button>
	</section>

	<section class="gpt-output">
		<div v-if="output" class="copy-button no-select" @click="copyOutputToClipboard">Text kopieren</div>
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
			<td><pre>{{entry.content}}</pre></td>
		</tr>
	</table>

	<div class="text-right small">
	<button class="button light" type="button" @click="copyHistoryToClipboard">Chatverlauf kopieren</button>
	<!--&ensp;<a class="button light" target="_blank" :href="'/conversation/' + conversationID">Chatverlauf teilen</a>-->
	</div>

</details>

</main>