export default Vue.defineComponent({
	name: 'ImageProcessor',

	inheritAttrs: false,

	data() {
		return {
			processor: null,
			wasmReady: false,
			loading: false,
			errorMessage: '',
			saveMessage: '',
			saveMessageTimer: null,

			hasImage: false,
			sideLayout: false,
			sourceFilename: 'image',
			sourceFormat: 'png',

			watermarks: [],
			selectedWatermarkIndex: 0,

			selectedPosition: 'bottom-right',
			opacity: 0.6,
			watermarkSize: 5,

			paddingHorizontal: 3,
			paddingVertical: 5,
			filenameSuffix: '-ai',

			outputFormat: 'original',
			outputQuality: 90,

			pointerActive: false,
		}
	},

	template: `
<section
	class="image-processor"
	:class="{ 'image-processor--side': sideLayout }"
>
	<div ref="configuration" hidden aria-hidden="true">
		<slot></slot>
	</div>

	<p v-if="errorMessage" class="image-processor-error">
		{{ errorMessage }}
	</p>

	<p v-if="saveMessage" class="image-processor-success">
		{{ saveMessage }}
	</p>

	<div class="image-processor-canvas-wrapper">
		<canvas
			v-show="hasImage"
			ref="canvas"
			class="image-processor-canvas"
			@pointerdown="pointerDown"
			@pointermove="pointerMove"
			@pointerup="pointerUp"
			@pointercancel="pointerUp"
		></canvas>

		<div
			v-if="!hasImage"
			class="image-processor-empty"
			role="button"
			tabindex="0"
			aria-label="Bild auswählen"
			@click="openFileDialog"
			@keydown.enter.prevent="openFileDialog"
			@keydown.space.prevent="openFileDialog"
		>
			<div class="image-processor-empty-content">
				<strong>Bild auswählen</strong>
				<span>Hier klicken, um ein Bild von Ihrem Gerät zu öffnen.</span>
				<small>JPEG, PNG oder WebP</small>
			</div>
		</div>

		<div v-if="loading" class="image-processor-loading">
			Bild wird verarbeitet …
		</div>
	</div>


	<form class="image-processor-controls" @submit.prevent="saveImage">
		<label>
			Bild auswählen

			<input
				ref="fileInput"
				type="file"
				accept="image/jpeg,image/png,image/webp"
				@change="selectLocalImage"
			>
		</label>

		<label v-if="watermarks.length">
			Wasserzeichen

			<select
				v-model.number="selectedWatermarkIndex"
				:disabled="!hasImage || loading"
				@change="changeWatermark"
			>
				<option
					v-for="(watermark, watermarkIndex) in watermarks"
					:key="watermark.url"
					:value="watermarkIndex"
				>
					{{ watermark.label }}
				</option>
			</select>
		</label>

		<fieldset :disabled="!hasImage || loading">
			<legend>Position</legend>

			<label>
				<input
					v-model="selectedPosition"
					type="radio"
					value="top-left"
					@change="changePosition"
				>
				Oben links
			</label>

			<label>
				<input
					v-model="selectedPosition"
					type="radio"
					value="top-right"
					@change="changePosition"
				>
				Oben rechts
			</label>

			<label>
				<input
					v-model="selectedPosition"
					type="radio"
					value="bottom-left"
					@change="changePosition"
				>
				Unten links
			</label>

			<label>
				<input
					v-model="selectedPosition"
					type="radio"
					value="bottom-right"
					@change="changePosition"
				>
				Unten rechts
			</label>
		</fieldset>

		<label>
			Transparenz: {{ Math.round(opacity * 100) }} %

			<input
				v-model.number="opacity"
				type="range"
				min="0"
				max="1"
				step="0.01"
				:disabled="!hasImage || loading"
				@input="changeOpacity"
			>
		</label>

		<label>
			Wasserzeichenbreite: {{ watermarkSize }} %

			<input
				v-model.number="watermarkSize"
				type="range"
				min="1"
				max="100"
				step="1"
				:disabled="!hasImage || loading"
				@input="changeWatermarkSize"
			>
		</label>

		<label>
			Abstand zum seitlichen Rand: {{ paddingHorizontal }} %

			<input
				v-model.number="paddingHorizontal"
				type="range"
				min="0"
				max="25"
				step="0.5"
				:disabled="!hasImage || loading"
				@input="changePadding"
			>
		</label>

		<label>
			Abstand zum oberen/unteren Rand: {{ paddingVertical }} %

			<input
				v-model.number="paddingVertical"
				type="range"
				min="0"
				max="25"
				step="0.5"
				:disabled="!hasImage || loading"
				@input="changePadding"
			>
		</label>

		<button
			class="button"
			type="submit"
			:disabled="!hasImage || !wasmReady || loading"
		>
			Bild speichern
		</button>
	</form>
</section>
`,

	async mounted() {
		this.readConfiguration()
		await this.initializeWasm()

		window.addEventListener('resize', this.updateLayout)

		await this.$nextTick()
		this.updateLayout()
	},

	beforeUnmount() {
		window.removeEventListener('resize', this.updateLayout)

		if (this.saveMessageTimer) {
			window.clearTimeout(this.saveMessageTimer)
		}
	},

	methods: {
		readConfiguration() {
			this.opacity = this.clampNumber(
				parseFloat(this.$attrs['data-opacity'] || '0.6'),
				0,
				1
			)

			this.paddingHorizontal = this.clampNumber(this.parsePercentage(
				this.$attrs['data-padding-horizontal'] || '3%'
			) * 100, 0, 25)

			this.paddingVertical = this.clampNumber(this.parsePercentage(
				this.$attrs['data-padding-vertical'] || '5%'
			) * 100, 0, 25)

			this.filenameSuffix = this.$attrs['data-suffix'] || '-ai'
			const configuredFormat = this.$attrs['data-output-format'] || 'original'
			this.outputFormat = ['original', 'jpeg', 'png', 'webp'].includes(configuredFormat)
				? configuredFormat
				: 'original'
			this.outputQuality = this.clampNumber(
				parseInt(this.$attrs['data-output-quality'] || '90', 10) || 90,
				1,
				100
			)

			const configurationElement = this.$refs.configuration
			const watermarkElements = configurationElement.querySelectorAll('watermark')

			this.watermarks = Array.from(watermarkElements).map((watermarkElement) => {
				const watermarkUrl = watermarkElement.dataset.url || ''
				const configuredSize = String(
					watermarkElement.dataset.defaultSize || '0'
				).trim()

				return {
					url: watermarkUrl,
					label: watermarkElement.dataset.label || this.getAssetName(watermarkUrl),
					defaultSize: configuredSize,
				}
			}).filter((watermark) => watermark.url)
		},

		async initializeWasm() {
			this.loading = true
			this.errorMessage = ''

			try {
				const wasmModule = await import(
					'/styles/wasm/image-processor/image_processor.js'
				)

				await wasmModule.default(
					'/styles/wasm/image-processor/image_processor_bg.wasm'
				)

				this.processor = Vue.markRaw(
					new wasmModule.ImageProcessor(this.$refs.canvas)
				)

				this.processor.set_padding(
					this.paddingHorizontal / 100,
					this.paddingVertical / 100
				)

				this.processor.set_opacity(this.opacity)
				this.processor.set_position(this.selectedPosition)

				this.wasmReady = true
			} catch (error) {
				console.error(error)
				this.errorMessage = 'Die Bildbearbeitung konnte nicht geladen werden.'
				this.loading = false
				return
			}

			const initialImageUrl = String(
				this.$attrs['data-image'] || ''
			).trim()

			if (initialImageUrl) {
				try {
					await this.loadImageFromUrl(initialImageUrl)

					if (this.watermarks.length) {
						await this.loadSelectedWatermark()
					}
				} catch (error) {
					console.error(error)

					this.hasImage = false
					this.errorMessage = ''
				}
			}

			this.loading = false
		},

		async loadImageFromUrl(imageUrl) {
			const imageAsset = await this.fetchAsset(imageUrl)

			this.processor.load_image(
				imageAsset.bytes,
				imageAsset.mimeType
			)

			this.sourceFilename = this.getAssetName(imageUrl) || 'image'
			this.sourceFormat = this.detectFormat(
				imageAsset.mimeType,
				this.sourceFilename
			)

			this.hasImage = true
			this.updateImageDimensions()
		},

		async selectLocalImage(event) {
			const selectedFile = event.target.files[0]

			if (!selectedFile || !this.processor) {
				return
			}

			this.loading = true
			this.errorMessage = ''

			try {
				const fileBuffer = await selectedFile.arrayBuffer()
				const fileBytes = new Uint8Array(fileBuffer)

				this.processor.load_image(
					fileBytes,
					selectedFile.type
				)

				this.sourceFilename = selectedFile.name
				this.sourceFormat = this.detectFormat(
					selectedFile.type,
					selectedFile.name
				)

				this.hasImage = true
				this.updateImageDimensions()

				if (this.watermarks.length) {
					await this.loadSelectedWatermark()
				}
			} catch (error) {
				console.error(error)
				this.errorMessage = 'Das ausgewählte Bild konnte nicht geladen werden.'
			} finally {
				this.loading = false
				event.target.value = ''
			}
		},

		async changeWatermark() {
			if (!this.processor) {
				return
			}

			this.loading = true
			this.errorMessage = ''

			try {
				await this.loadSelectedWatermark()
			} catch (error) {
				console.error(error)
				this.errorMessage = 'Das Wasserzeichen konnte nicht geladen werden.'
			} finally {
				this.loading = false
			}
		},

		async loadSelectedWatermark() {
			const watermark = this.watermarks[this.selectedWatermarkIndex]

			if (!watermark) {
				return
			}

			const watermarkAsset = await this.fetchAsset(watermark.url)
			const defaultSize = this.resolveWatermarkSize(
				watermark.defaultSize
			)

			this.processor.load_watermark(
				watermarkAsset.bytes,
				watermarkAsset.mimeType,
				defaultSize
			)

			const detectedWidth = this.processor.watermark_width()

			const referenceSize = this.getWatermarkReferenceSize()
			const watermarkWidth = defaultSize > 0 ? defaultSize : detectedWidth
			this.watermarkSize = this.clampNumber(
				Math.round(watermarkWidth / referenceSize * 100),
				1,
				100
			)

			this.processor.set_watermark_width(
				Math.max(1, Math.round(referenceSize * this.watermarkSize / 100))
			)
			this.processor.render()
		},

		openFileDialog() {
			if (!this.wasmReady || this.loading) {
				return
			}

			this.$refs.fileInput?.click()
		},

		updateLayout() {
			if (!this.processor || !this.hasImage) {
				this.sideLayout = true
				return
			}

			const imageWidth = this.processor.image_width()
			const imageHeight = this.processor.image_height()
			const processorElement = this.$el

			if (!imageWidth || !imageHeight || !processorElement) {
				this.sideLayout = false
				return
			}

			const containerWidth = processorElement.parentElement
				?.getBoundingClientRect()
				.width || window.innerWidth

			const viewportHeight = window.innerHeight
			const viewportWidth = window.innerWidth
			const availableHeight = Math.max(400, viewportHeight - 48)

			const expectedFullWidthHeight = containerWidth
				* (imageHeight / imageWidth)

			const minimumImageColumnWidth = 420
			const controlColumnWidth = 350

			const hasEnoughBrowserWidth = viewportWidth >= 900
			const hasEnoughContainerWidth = containerWidth
				>= minimumImageColumnWidth + controlColumnWidth

			const imageWouldBecomeTall = expectedFullWidthHeight
				> availableHeight * 0.48

			this.sideLayout = hasEnoughBrowserWidth
				&& hasEnoughContainerWidth
				&& imageWouldBecomeTall
		},

		updateImageDimensions() {
			this.changePadding()

			this.$nextTick(() => {
				this.updateLayout()
			})
		},

		changePosition() {
			this.processor.set_position(this.selectedPosition)
			this.processor.render()
		},

		changeOpacity() {
			this.processor.set_opacity(this.opacity)
			this.processor.render()
		},

		changeWatermarkSize() {
			this.processor.set_watermark_width(
				Math.max(1, Math.round(this.getWatermarkReferenceSize() * this.watermarkSize / 100))
			)
			this.processor.render()
		},

		changePadding() {
			this.processor.set_padding(
				this.paddingHorizontal / 100,
				this.paddingVertical / 100
			)
			this.processor.render()
		},

		pointerDown(event) {
			if (!this.processor || !this.hasImage) {
				return
			}

			const pointerPosition = this.getCanvasPointerPosition(event)

			this.$refs.canvas.setPointerCapture(event.pointerId)
			this.pointerActive = true

			const selectedPosition = this.processor.pointer_down(
				pointerPosition.imageCoordinateX,
				pointerPosition.imageCoordinateY
			)

			if (selectedPosition) {
				this.selectedPosition = selectedPosition
			}
		},

		pointerMove(event) {
			if (!this.pointerActive || !this.processor) {
				return
			}

			const pointerPosition = this.getCanvasPointerPosition(event)

			this.processor.pointer_move(
				pointerPosition.imageCoordinateX,
				pointerPosition.imageCoordinateY
			)
		},

		pointerUp(event) {
			if (!this.pointerActive || !this.processor) {
				return
			}

			this.pointerActive = false
			this.processor.pointer_up()

			if (this.$refs.canvas.hasPointerCapture(event.pointerId)) {
				this.$refs.canvas.releasePointerCapture(event.pointerId)
			}
		},

		getCanvasPointerPosition(event) {
			const canvasElement = this.$refs.canvas
			const canvasBounds = canvasElement.getBoundingClientRect()

			const imageCoordinateX = (
				event.clientX - canvasBounds.left
			) * (canvasElement.width / canvasBounds.width)

			const imageCoordinateY = (
				event.clientY - canvasBounds.top
			) * (canvasElement.height / canvasBounds.height)

			return {
				imageCoordinateX,
				imageCoordinateY,
			}
		},

		async saveImage() {
			if (!this.processor || !this.hasImage) {
				return
			}

			this.loading = true
			this.errorMessage = ''
			this.saveMessage = ''

			try {
				const outputFormat = this.resolveOutputFormat()
				const outputMimeType = this.getOutputMimeType(outputFormat)
				const outputFilename = this.createOutputFilename(outputFormat)

				const encodedBytes = this.processor.export_image(
					outputFormat,
					this.outputQuality
				)

				const outputBlob = new Blob(
					[encodedBytes],
					{ type: outputMimeType }
				)

				const downloadUrl = window.URL.createObjectURL(outputBlob)
				const downloadLink = document.createElement('a')

				downloadLink.href = downloadUrl
				downloadLink.download = outputFilename

				document.body.appendChild(downloadLink)
				downloadLink.click()
				downloadLink.remove()

				window.setTimeout(() => {
					window.URL.revokeObjectURL(downloadUrl)
				}, 1000)

				this.showSaveMessage(
					'Die Bilddatei wurde in Ihren Downloads gespeichert.'
				)
			} catch (error) {
				console.error(error)
				this.errorMessage = 'Das fertige Bild konnte nicht gespeichert werden.'
			} finally {
				this.loading = false
			}
		},

		showSaveMessage(message) {
			if (this.saveMessageTimer) {
				window.clearTimeout(this.saveMessageTimer)
			}

			this.saveMessage = message

			this.saveMessageTimer = window.setTimeout(() => {
				this.saveMessage = ''
				this.saveMessageTimer = null
			}, 2000)
		},

		async fetchAsset(assetUrl) {
			const response = await fetch(assetUrl, {
				credentials: 'same-origin',
			})

			if (!response.ok) {
				throw new Error('Asset konnte nicht geladen werden: ' + assetUrl)
			}

			const assetBuffer = await response.arrayBuffer()
			const responseMimeType = response.headers
				.get('content-type')
				?.split(';')[0] || ''

			return {
				bytes: new Uint8Array(assetBuffer),
				mimeType: responseMimeType || this.guessMimeType(assetUrl),
			}
		},

		resolveOutputFormat() {
			if (this.outputFormat !== 'original') {
				return this.outputFormat
			}

			if (['jpeg', 'png', 'webp'].includes(this.sourceFormat)) {
				return this.sourceFormat
			}

			return 'png'
		},

		createOutputFilename(outputFormat) {
			const filenameWithoutExtension = this.sourceFilename.replace(
				/\.[^.]+$/,
				''
			)

			const outputExtension = outputFormat === 'jpeg'
				? 'jpg'
				: outputFormat

			return filenameWithoutExtension
				+ this.filenameSuffix
				+ '.'
				+ outputExtension
		},

		detectFormat(mimeType, filename) {
			if (mimeType === 'image/jpeg') {
				return 'jpeg'
			}

			if (mimeType === 'image/png') {
				return 'png'
			}

			if (mimeType === 'image/webp') {
				return 'webp'
			}

			const filenameExtension = filename
				.split('.')
				.pop()
				.toLowerCase()

			if (filenameExtension === 'jpg' || filenameExtension === 'jpeg') {
				return 'jpeg'
			}

			if (filenameExtension === 'webp') {
				return 'webp'
			}

			return 'png'
		},

		getOutputMimeType(outputFormat) {
			if (outputFormat === 'jpeg') {
				return 'image/jpeg'
			}

			if (outputFormat === 'webp') {
				return 'image/webp'
			}

			return 'image/png'
		},

		guessMimeType(assetUrl) {
			const cleanUrl = assetUrl.split('?')[0].toLowerCase()

			if (cleanUrl.endsWith('.jpg') || cleanUrl.endsWith('.jpeg')) {
				return 'image/jpeg'
			}

			if (cleanUrl.endsWith('.webp')) {
				return 'image/webp'
			}

			if (cleanUrl.endsWith('.svg')) {
				return 'image/svg+xml'
			}

			return 'image/png'
		},

		getAssetName(assetUrl) {
			try {
				const parsedUrl = new URL(assetUrl, window.location.href)
				const pathnameParts = parsedUrl.pathname.split('/')
				.filter(Boolean)

				return decodeURIComponent(
					pathnameParts[pathnameParts.length - 1] || 'image'
				)
			} catch (error) {
				return 'image'
			}
		},

		parsePercentage(value) {
			const parsedValue = parseFloat(String(value).replace('%', ''))

			if (!Number.isFinite(parsedValue)) {
				return 0
			}

			return String(value).includes('%')
				? parsedValue / 100
				: parsedValue
		},

		resolveWatermarkSize(configuredSize) {
			const sizeValue = String(configuredSize).trim()
			const parsedSize = parseFloat(sizeValue)

			if (!Number.isFinite(parsedSize) || parsedSize <= 0) {
				return 0
			}

			if (sizeValue.endsWith('%')) {
				return Math.round(
					this.getWatermarkReferenceSize() * parsedSize / 100
				)
			}

			return Math.round(parsedSize)
		},

		getWatermarkReferenceSize() {
			return Math.max(
				this.processor.image_width(),
				this.processor.image_height()
			)
		},

		clampNumber(value, minimum, maximum) {
			return Math.min(maximum, Math.max(minimum, value))
		},
	},
})
