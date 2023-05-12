<div class="options">
	<label>Sprache wählen:
	<select v-model="action" ref="selectElement" name="action" @change="wipeHistory()">
		<option value="translate-en">nach Englisch</option>
		<option value="translate-de">nach Deutsch</option>
		<option value="translate-pl">nach Polnisch</option>
		<option value="translate-sorb">nach Sorbisch</option>
		<option value="translate-fr">nach Französisch</option>
		<option value="translate-cz">nach Tschechish</option>
		<option value="translate-ru">nach Russisch</option>
		<option value="translate-ukr">nach Ukrainisch</option>
		<option value="translate-spain">nach Spanisch</option>
		<option value="translate-klingon">Klingonisch</option>
	</select>
	</label>
</div>