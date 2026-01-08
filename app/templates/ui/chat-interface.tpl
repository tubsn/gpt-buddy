<main id="chatapp">

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

<p v-if="infotext" class="hide-mobile" v-html="'Hinweis: ' + infotext"></p>
<p v-else class="hide-mobile">Hinweis: mit der Tastenkombination <b>TAB + Leertaste</b>, lässt sich eine Nachricht schnell abschicken.</p>

<section class="box <?php if ($rag ?? false == true): ?>rag-layout<?php endif ?>" :class="{'advanced-model' : reasoning}">

<p v-if="errormessages" v-cloak class="error-message" v-html="errormessages"></p>

<div class="ui-header">

	<div class="options">	
		<?php 
			$selectOptions = '';
			if ($category['hideDefault'] ?? false) {$selectOptions .= ' hide-default="true"';}
			if ($category['promptless'] ?? false) {$selectOptions .= ' promptless="true"';}
			if ($category['name'] ?? false) {$selectOptions .= ' category="' . $category['name'] . '"';}
			if (auth_rights('chatgpt')) {$selectOptions .= ' editable="true"';}
		?>

		<prompt-selector <?=$selectOptions?> @change="promptID = $event" @forcemodel="model = $event" ref="prompts">
			<?php foreach ($prompts as $prompt): ?>
			<option value="<?=$prompt['id']?>" data-description="<?=$prompt['description'] ?? ''?>" data-advanced="<?=$prompt['advanced'] ?? ''?>" data-model="<?=$prompt['model'] ?? ''?>"><?=$prompt['title']?></option>
			<?php endforeach ?>
		</prompt-selector>
	</div>

	<div class="center-options">
		<url-importer @change="input = $event"></url-importer>
	</div>

	<?php $directPrompts = array_filter($prompts, function($prompt) {return $prompt['direct'] == true;});?>

	<?php if ($directPrompts): ?>
	<div class="direct-actions hide-mobile">
	<label>direkt Aktionen:</label>
	<div class="button-group small mtsmall">
	<?php foreach ($directPrompts as $prompt): ?>
		<button type="button" class="button light" @click="directPrompt" data-direct-id="<?=$prompt['id']?>"><?=$prompt['title']?></button> 
	<?php endforeach ?>
	</div>
	</div>
	<?php endif ?>

	<div class="meta-options">
		<model-selector ref="model" :forcedmodel="model">
			<?php foreach ($aimodels as $speakingname => $modeldata): ?>
			<option data-description="<?=$modeldata['description']?>"><?=$speakingname?></option>	
			<?php endforeach ?>			
		</model-selector>
	</div>

	<?php if ($rag ?? false == true): ?>
	<div class="rag-settings">
	<rag-settings ref="parameters"></rag-settings>
	<?php include tpl('ui/ressorts');?>
	<?php include tpl('ui/taglist');?>
	</div>
	<?php endif ?>

</div>


<div class="grid-2-col">

	<section class="user-input">

		<tts-button></tts-button>
		<file-upload ref="payload"></file-upload>

		<label class="hide-mobile">Eingabe:</label>
		<textarea autofocus ref="autofocusElement" tabindex="1" v-model="input" class="io-textarea" placeholder="Text oder Frage eingeben"></textarea>
	
		<div class="ui-buttongroup">
		<button class="button" tabindex="2" @click.prevent="send" v-if="!loading">Absenden</button>
		<button class="button stop" @click.prevent="stopStream" v-if="loading">Generierung abbrechen</button>
		<button class="button light mlsmall del-historie hide-mobile" tabindex="-1" type="button" @click="removeHistory" :disabled="loading">Chatverlauf löschen</button>
		<button class="button light mlsmall del-historie" tabindex="-1" type="button" @click="removeInput" :disabled="loading"><span class="hide-mobile">Eingabe löschen</span><span class="show-mobile"><img class="mobile-icon icon-delete" src="/styles/flundr/img/icon-delete-black.svg"></span></button>
		</div>

	</section>

	<section class="gpt-output">

		<search-toggle ref="searchtoggle"></search-toggle>
		<tts-button></tts-button>

		<div class="float-button" title="in Zwischenablage kopieren" @click="copyOutputToClipboard"><img src="/styles/img/copy-icon.svg"></div>

		<label><span class="">Ausgabe:</span> <span class="output-info" v-if="modelmode">{{modelmode}}</span></label>
			<textarea tabindex="3" v-if="loading" class="io-textarea" v-html="output" contenteditable="true" tabindex="0"></textarea>
			<div v-else class="io-textarea io-output-div" tabindex="3" v-html="output" contenteditable="true" tabindex="0" ></div>

		<div class="usage-stats fright small">
			<span class="ml loading-wrapper" v-if="loading">
				<div class="loadIndicator"><div></div><div></div><div></div></div> generiere - abbrechen <b>[ESC]</b>
				<img class="mini-robot" src="/styles/img/ai-buddy.svg">
			</span>
			<span v-if="responsetime" class="ml">Antwortzeit: <b>{{ responsetime }}&thinsp;s</b> | Tokens  in: <b>{{ usage.input_tokens }}</b> / out: <b>{{ usage.output_tokens }}</b> | Zeichen: <b>{{ chars }}</b></span>
		</div>

		<button class="button light del-historie show-mobile" tabindex="-1" type="button" @click="removeHistory" :disabled="loading">Chatverlauf löschen</button>

	</section>

</div>


</section> <!-- Main Form Section -->

<?php if (auth_rights('debug')): ?>
<debug-modal ref="debug"></debug-modal>
<?php endif ?>

<chat-history ref="history" :current-response="responseID"></chat-history>

<?php include tpl('navigation/drop-down-menu')?>

</main>