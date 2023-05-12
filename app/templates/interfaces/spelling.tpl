<div class="options">
	<label>Textkorrigieren:
	<select v-model="action" ref="selectElement" name="action" @change="wipeHistory()">
		<option value="spelling-grammar">Rechtschreibung, Gramatik und Lesbarkeit</option>
		<option value="spelling-only">Nur Rechtschreibung</option>
		<option value="spelling-comma">Nur Kommasetzung</option>
	</select>
	</label>
</div>