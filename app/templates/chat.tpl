<main id="gptInterface">

<h1><?=APP_NAME?> | <?=$page['title']?></h1>

<p v-if="description" v-html="'Hinweis: ' + description"></p>
<p v-else class="hide-mobile">Hinweis: mit der Tastenkombination <b>TAB + Leertaste</b>, lässt sich eine Nachricht schnell abschicken.</p>


<form method="post" @submit.prevent="ask" action="" class="form-container">

<p v-if="error" class="error-message">{{error}}</p>

<?php if ($interface): ?>
<div class="ui-header">
<?php include tpl('interfaces/' . $interface); ?>
<button class="show-mobile send-mobile-btn" type="submit" @click.prevent="ask" :disabled="loading">Senden</button>	
</div>
<?php endif ?>



<div class="grid-2-col">

	<section class="user-input">
		<label>Eingabe:
		<textarea v-model="input" ref="autofocusElement" class="io-textarea" :disabled="loading" placeholder="Text oder Frage eingeben"></textarea>
		</label>
		<button class="hide-mobile" type="submit" @click.prevent="ask" :disabled="loading">Absenden</button>
		<button class="light mlsmall del-historie" tabindex="-1" type="button" @click="wipeHistory()" :disabled="loading">Chat Historie löschen</button>
	</section>

	<section class="gpt-output">
		<label>Ausgabe:
		<textarea v-model="output" class="io-textarea" placeholder=""></textarea>	
		</label>
		<div class="fright small">
			<span class="ml" v-if="loading">
				<div class="loadIndicator"><div></div><div></div><div></div></div> Antwort wird generiert
			</span>
			<span v-if="responsetime" class="ml">Antwortzeit: <b>{{ responsetime }}&thinsp;s</b> | Tokens: <b>{{ tokens }}</b></span>		
		</div>
	</section>

</div>

</form>

<details v-if="history">
	<summary>Chatverlauf einblenden</summary>
	<table class="fancy history">
		<tr v-for="entry in history"> 
			<td class="ucfirst">{{entry.role}}</td>
			<td><pre>{{entry.content}}</pre></td>
		</tr>
	</table>
</details>

</main>