<div class="options">
	<label>Eingabe anpassen auf:
	<select v-model="action" ref="selectElement" name="action" @change="wipeHistory()">
		<option value="shorten-s">Textlänge S</option>
		<option value="shorten-m">Textlänge M</option>
		<option value="shorten-l">Textlänge L</option>
		<option value="shorten-xl">Textlänge XL</option>
	</select>
	</label>
</div>