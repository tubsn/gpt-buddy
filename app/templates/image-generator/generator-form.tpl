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
            >
                {{ image ? 'Bild verbessern' : 'Bild generieren' }}
            </button>
        </div>

        <div class="image-generator-prompt">
            <label for="imagePrompt" class="image-generator-label">
                <span>Bildbeschreibung</span>
                <span>{{ inputChars }}/4000 Zeichen</span>
            </label>

            <textarea
                id="imagePrompt"
                v-model="input"
                ref="autofocusElement"
                class="io-textarea image-generator-input"
                :disabled="loading"
                maxlength="4000"
                placeholder="z.B. Eine Astronauten-Kuh repariert ein Solarpanel an der ISS Raumstation"
            ></textarea>

            <div class="image-generator-upload">
                <div
                    id="drop-area"
                    class="upload-drop-area"
                    :class="{'loading': uploading}"
                >
                    <input
                        type="file"
                        id="fileElem"
                        accept="image/jpeg,image/png,image/webp"
                        :disabled="loading || uploading"
                    >

                    <input
                        type="text"
                        id="imageUrl"
                        name="image"
                        :value="uploading ? 'Bild wird hochgeladen …' : image"
                        readonly
                        aria-label="Referenzbild hochladen"
                        placeholder="Bild hochladen oder aus der Galerie hierher ziehen"
                    >
                </div>

                <button
                    type="button"
                    class="light image-unlink-button"
                    @click="image = ''"
                    :disabled="!image || loading || uploading"
                >
                    Verknüpfung aufheben
                </button>
            </div>
        </div>

        <div class="image-options">
            <?php foreach ($imageFields as $fieldName => $field): ?>
                <label>
                    <span><?= $field['label'] ?></span>

                    <select
                        name="<?= $fieldName ?>"
                        v-model="<?= $fieldName ?>"
                        data-image-setting
                        :disabled="loading"
                    >
                        <?php foreach ($field['options'] as $optionValue => $optionLabel): ?>
                            <option
                                value="<?= $optionValue ?>"
                                <?= $selectedImageOptions[$fieldName] === $optionValue ? 'selected' : '' ?>
                            ><?= $optionLabel ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            <?php endforeach; ?>
        </div>

        <div
            v-if="loading || uploading || responsetime"
            class="image-generator-status"
            aria-live="polite"
        >
            <span class="loading-wrapper" v-if="loading">
                <span class="loadIndicator">
                    <span></span><span></span><span></span>
                </span>
                generiere – abbrechen <b>[ESC]</b>
                <img class="mini-robot" src="/styles/img/ai-buddy.svg" alt="">
            </span>

            <span v-else-if="uploading">
                Bild wird hochgeladen …
            </span>

            <span v-else-if="responsetime">
                Antwortzeit: <b>{{ responsetime }}&thinsp;s</b>
            </span>
        </div>
    </fieldset>
</form>