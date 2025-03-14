<main id="textToSpeechApp">

<a class="fright button" href="/">zurück zur Textgenerierung</a>


<h1><?=$page['title']?></h1>

<div v-if="errorMessage" class="error-message mb">
	{{ errorMessage }}
</div>

<div class="grid-3-col">
<div class="box parameters">
	<label>TTS Voice:
	<select v-model="voice" data-name="voice">
		<option value="nova">Nova - Frau, Lebenig, Sympathisch</option>
		<option value="shimmer">Shimmer - Frau, Ruhig, Nachrichtlich</option>
		<option value="fable">Fable - Neutral, Nachrichtlich</option>		
		<option value="echo">Echo - Mann, Ruhig</option>
		<option value="onyx">Onyx - Mann, Gelangweilt</option>
		<optgroup label="US-Akzent">
			<option value="coral">Coral - Frau, Nachrichtlich, Akzent</option>		
			<option value="alloy">Alloy - Frau, Neutral, Akzent</option>		
			<option value="ash">Ash - Mann, Ausdrucksvoll, Akzent</option>		
			<option value="sage">Sage - Frau, Aufgeregt, Akzent</option>
		</optgroup>
	</select>
	</label>
</div>

<div class="box parameters">
	<label>TTS Model:
	<select v-model="ttsmodel" data-name="ttsmodel">
		<option>Open AI Whisper</option>
	</select>
	</label>
</div>

<div class="box parameters">
	<label>Qualität:
	<select v-model="audioquality" data-name="audioquality">
		<option value="0">Normale Qualität</option>
		<option value="1">Hohe Qualität</option>
	</select>
	</label>
</div>

</div>

<div class="box">
<label>Eingabe:
<textarea v-model="input" ref="inputarea" class="large" placeholder="Bitte hier den Text einfügen, welcher als Audio ausgegeben werden soll" style="margin-bottom:1em"
data-initial-value="<?=$input ?? ''?>"
></textarea>
</label>

<div class="flex" style="gap:1em; align-items:center">
	<button class="button" @click="generate">
		Audio generieren <div v-if="loading" class="loadIndicator white"><div></div><div></div><div></div></div>
	</button>
	<audio v-if="audiofile" controls :src="audiofile"></audio>
	<a v-if="audiofile" :href="audiofile" download>herunterladen</a>
</div>

</div>

</main>
