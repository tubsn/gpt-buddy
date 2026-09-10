<form
	method="post"
	action=""
	class="form-container image-generator-form"
	@submit.prevent="generateImage"
>
	<fieldset class="image-generator">
		<div class="image-generator-action">
			<button
				type="submit"
				class="image-generate-button"
				:disabled="loading || uploading || !input.trim()"
				:aria-busy="loading || uploading"
			>
				<span class="image-button-icon" aria-hidden="true">
					<span v-if="loading || uploading" class="image-button-spinner"></span>

					<svg v-else viewBox="0 0 24 24" fill="none">
						<path
							d="m12 3 2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4L12 3Z"
							stroke="currentColor"
							stroke-width="1.6"
							stroke-linejoin="round"
						/>
					</svg>
				</span>

				<span class="image-button-title">
					{{ loading
						? 'Generiere …'
						: uploading
							? 'Upload …'
							: images.length ? 'Bilder verarbeiten' : 'Bild generieren'
					}}
				</span>

				<small class="image-button-hint">
					{{ loading ? 'ESC: abbrechen' : uploading ? uploadProgress : 'Mit KI erstellen' }}
				</small>
			</button>

			<div class="image-action-status" aria-live="polite">
				<span v-if="loading">Bild wird generiert</span>
				<span v-else-if="uploading">{{ uploadProgress }}</span>
				<span v-else-if="responsetime">{{ responsetime }}&thinsp;s</span>
			</div>
		</div>

		<div class="image-generator-prompt">
			<div
				id="drop-area"
				class="image-prompt-drop-area"
				:class="{loading: uploading}"
			>
				<textarea
					id="imagePrompt"
					v-model="input"
					ref="autofocusElement"
					class="io-textarea image-generator-input"
					:disabled="loading"
					maxlength="4000"
					aria-label="Bildbeschreibung"
					aria-describedby="imagePromptCounter"
					placeholder="Beschreibe dein Bild oder ziehe Referenzbilder hier hinein. Du kannst dich auf Bild 1, Bild 2 usw. beziehen."
					@paste="handlePaste"
				></textarea>

				<input
					type="file"
					id="fileElem"
					ref="imageFileInput"
					multiple
					hidden
					accept="image/jpeg,image/png,image/webp"
					:disabled="loading || uploading || images.length >= maxImages"
				>

				<div class="image-prompt-toolbar">
					<button
						type="button"
						class="image-add-button"
						@click="$refs.imageFileInput.click()"
						:disabled="loading || uploading || images.length >= maxImages"
					>
						<svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
							<rect x="3" y="3" width="18" height="18" rx="4"/>
							<path d="M12 8v8M8 12h8"/>
						</svg>

						<span>{{ uploading ? 'Wird hochgeladen …' : 'Bilder hinzufügen' }}</span>
						<small>{{ images.length }}/{{ maxImages }}</small>
					</button>

					<button
						v-if="images.length"
						type="button"
						class="image-clear-references"
						@click="images = []"
						:disabled="loading || uploading"
					>
						Alle entfernen
					</button>

					<span id="imagePromptCounter" class="image-character-count">
						{{ inputChars }}/4000 Zeichen
					</span>
				</div>
			</div>

			<div v-if="images.length" class="image-reference-list">
				<button
					v-for="(imagePath, imageIndex) in images"
					:key="imagePath"
					type="button"
					class="image-reference"
					@click="removeImage(imageIndex)"
					:disabled="loading || uploading"
					:aria-label="`Referenzbild ${imageIndex + 1} entfernen`"
					title="Klicken zum Entfernen"
				>
					<img :src="imagePath" alt="" draggable="false">
					<span class="image-reference-number">Bild {{ imageIndex + 1 }}</span>
					<span class="image-reference-remove" aria-hidden="true">×</span>
				</button>
			</div>

			<p v-if="error" class="image-form-error" role="alert">{{ error }}</p>
		</div>

		<div class="image-options">
			<?php foreach ($imageFields as $fieldName => $field): ?>
				<label>
					<span><?= htmlspecialchars($field['label'], ENT_QUOTES, 'UTF-8') ?></span>

					<select
						name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
						v-model="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
						data-image-setting
						:disabled="loading"
					>
						<?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
							<option value="<?= htmlspecialchars($optionValue, ENT_QUOTES, 'UTF-8') ?>">
								<?= htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8') ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
			<?php endforeach; ?>
		</div>

	</fieldset>
</form>