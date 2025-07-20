<form method="post" action="" class="form-container" :class="{'advanced-model' : quality == 'hd'}">

<fieldset class="image-generator">

<div style="display:flex; gap:0.5em; align-items: center;">
	<button style="flex-grow: 1; height:90%;" @click="generateImage()" tabindex="2" type="button">Bild<br>

		{{ image ? 'verbessern' : 'generieren'}}
	</button>
<!--	<button style="flex-grow: .3;  height:90%; " class="light mlsmall del-historie" type="button" @click="wipeInput()" :disabled="loading">Eingabe<br>löschen</button>-->
</div>

<div>
	<label>Bildbeschreibung - Was soll generiert werden? ({{ inputChars }}/4000 Zeichen):
	<textarea v-model="input" tabindex="1" ref="autofocusElement" class="io-textarea image-generator-input" :disabled="loading" placeholder="z.B. Eine Astronauten Kuh repariert ein Solarpanel an der ISS Raumstation"></textarea>
	</label>


	<div class="flex" style="gap:0.5em; align-items:baseline">

		<div id="drop-area" class="upload-drop-area" :class="{'loading' : uploading}">
		<input type="file" id="fileElem" accept="image/*">
		<input type="text" style="cursor:pointer;" name="image" v-model="image" id="imageUrl" placeholder="Bild hochladen oder auf diese Fläche ziehen zum verbessern.">
		</div>

<button type="button" class="light nowrap" @click="this.image = ''">Verknüpfung aufheben</button>


	</div>
</div>

<div class="image-options">


	<label>Format/Qualität
	<select tabindex="3" v-model="resolution">
		<option value="1536x1024">Querformat</option>
		<option value="1024x1536">Hochformat</option>
		<option value="1024x1024">Quadratisch</option>
	</select>
	<select tabindex="4" v-model="quality">
		<option value="low">niedrige Qualität</option>
		<option value="medium">normale Qualität</option>
		<option value="high">hohe Qualität</option>
	</select>
	</label>

	<label>Hintergrund
	<select tabindex="5" v-model="background">
		<option value="auto">Auto</option>
		<option value="transparent">transparent</option>
		<option value="opaque">gefüllt</option>
	</select>
	</label>

	<div>
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