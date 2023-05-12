<div class="options">
	<label>Anfrage Typ:
	<select v-model="action" ref="selectElement" name="action" @change="wipeHistory(); setDescription();">
		<option value="general">Standard Chat</option>
		<?php foreach ($prompts as $internalPromptName => $prompt): ?>
		<option value="<?=$internalPromptName?>" data-description="<?=$prompt['description'] ?? ''?>" ><?=$prompt['name']?></option>
		<?php endforeach ?>
	</select>
	</label>

	<!--
	<label v-if="action == 'shorten'">Anzahl zeichen:
	<input type="number" v-model="options" placeholder="400">
	</label>
	-->
</div>