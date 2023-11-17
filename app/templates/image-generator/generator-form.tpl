<form method="post" action="" class="form-container" :class="{'advanced-model' : quality == 'hd'}">

<fieldset class="image-generator">

<div style="display:flex;  align-items: center;">
	<button style="flex-grow: 1; height:90%;" @click="generateImage()" tabindex="2" type="button">Bild<br>generieren</button>
<!--	<button style="flex-grow: .3;  height:90%; " class="light mlsmall del-historie" type="button" @click="wipeInput()" :disabled="loading">Eingabe<br>löschen</button>-->
</div>

<div>
	<label>Bildbeschreibung - Was soll generiert werden? ({{ inputChars }}/4000 Zeichen):
	<textarea v-model="input" tabindex="1" ref="autofocusElement" class="io-textarea image-generator-input" :disabled="loading" placeholder="z.B. Eine Astronauten Kuh repariert ein Solarpanel an der ISS Raumstation"></textarea>
	</label>


</div>

<div>
<div class="image-options">


	<label>Format/Qualität
	<select tabindex="3" v-model="resolution">
		<option value="1792x1024">Querformat</option>
		<option value="1024x1792">Hochformat</option>
		<option value="1024x1024">Quadratisch</option>
	</select>
	<select tabindex="4" v-model="quality">
		<option value="standard">Normale Qualität</option>
		<option value="hd">Hohe Qualität</option>
	</select>

	</label>

	<label>Farbschema
	<select tabindex="5" v-model="style">
		<option value="vivid">Belebt und Bunt</option>
		<option value="natural">Neutral</option>
	</select>
	</label>

	</div>
		<div class="fright small">
			<span class="ml loading-wrapper" v-if="loading">
				<div class="loadIndicator"><div></div><div></div><div></div></div> generiere - abbrechen <b>[ESC]</b>
				<img class="mini-robot" src="/styles/img/ai-buddy.svg">
			</span>
			<span v-if="responsetime" class="ml">Antwortzeit: <b>{{ responsetime }}&thinsp;s</b></span>
		</div>
	</div>
</fieldset>

</form>