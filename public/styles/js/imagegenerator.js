import Dropdown from "./components/dropdown-menu.js"

const { createApp } = Vue

const imageGeneratorElement = document.getElementById('imageGenerator')
const initialImageSettings = {}

function readStorage(storageName, settingName) {
	try {
		return window[storageName].getItem(settingName)
	} catch (error) {
		return null
	}
}

function writeStorage(storageName, settingName, settingValue) {
	try {
		window[storageName].setItem(settingName, settingValue)
	} catch (error) {
		// Browser-Speicher ist optional.
	}
}

if (imageGeneratorElement) {
	imageGeneratorElement.querySelectorAll('[data-image-setting]').forEach(selectElement => {
		const availableValues = Array.from(
			selectElement.options,
			optionElement => optionElement.value
		)
		const storedValue = readStorage('localStorage', selectElement.name)

		initialImageSettings[selectElement.name] = availableValues.includes(storedValue)
			? storedValue
			: selectElement.value
	})

	createApp({
		components: {
			dropdown: Dropdown,
		},

		data() {
			return {
				...initialImageSettings,
				input: '',
				output: '',
				payload: '',
				images: [],
				maxImages: 10,
				loading: false,
				uploading: false,
				uploadProgress: '',
				responseSeconds: 0,
				stopWatchStartTime: null,
				errormessages: '',
				abortController: null,
				uploadAbortController: null,
				eventAbortController: null,
				settingWatchStops: [],
			}
		},

		computed: {
			// Kompatibilität mit vorhandenen Galerieaktionen.
			image: {
				get() {
					return this.images[0] || ''
				},
				set(imagePath) {
					if (this.loading || this.uploading) return

					if (!imagePath) {
						this.images = []
						return
					}

					try {
						this.addImage(imagePath)
					} catch (error) {
						this.showError(error.message)
					}
				},
			},

			responsetime() {
				return this.responseSeconds > 0 ? this.responseSeconds : ''
			},

			error() {
				return this.errormessages ? `Fehler: ${this.errormessages}` : ''
			},

			inputChars() {
				return this.input.length
			},
		},

		watch: {
			input(content) {
				writeStorage('sessionStorage', 'input', content)
			},
		},

		mounted() {
			this.eventAbortController = new AbortController()
			this.setupSettingWatchers()
			this.getHistory()
			this.dragDropSetup()
			this.autofocus()

			document.addEventListener('keydown', this.handleGeneratorKeydown, {
				signal: this.eventAbortController.signal,
			})
		},

		beforeUnmount() {
			this.eventAbortController?.abort()
			this.abortController?.abort()
			this.uploadAbortController?.abort()
			this.settingWatchStops.forEach(stopWatching => stopWatching())
		},

		methods: {
			setupSettingWatchers() {
				Object.keys(initialImageSettings).forEach(settingName => {
					this.settingWatchStops.push(this.$watch(
						() => this[settingName],
						settingValue => writeStorage('localStorage', settingName, settingValue)
					))
				})
			},

			autofocus() {
				this.$nextTick(() => this.$refs.autofocusElement?.focus())
			},

			getHistory() {
				const savedInput = readStorage('sessionStorage', 'input')
				if (savedInput) this.input = savedInput.slice(0, 4000)
			},

			resetMetaInfo() {
				this.responseSeconds = 0
				this.errormessages = ''
			},

			startClock() {
				this.stopWatchStartTime = Date.now()
			},

			stopClock() {
				this.responseSeconds = this.elapsedTime()
				this.stopWatchStartTime = null
			},

			elapsedTime() {
				return this.stopWatchStartTime
					? Math.round((Date.now() - this.stopWatchStartTime) / 100) / 10
					: 0
			},

			showError(message) {
				this.errormessages = message
			},

			handleGeneratorKeydown(event) {
				if (event.key !== 'Escape' || !this.loading) return
				event.preventDefault()
				this.abortController?.abort()
			},

			normalizeImagePath(imagePath) {
				if (typeof imagePath !== 'string' || !imagePath.trim()) {
					throw new Error('Kein gültiger Bildpfad vorhanden.')
				}

				const imageUrl = new URL(imagePath, window.location.href)

				if (
					imageUrl.origin !== window.location.origin ||
					!/^\/(uploads|generated)\//.test(imageUrl.pathname)
				) {
					throw new Error('Bitte ein Bild aus den eigenen Uploads oder der Galerie verwenden.')
				}

				return imageUrl.pathname
			},

			addImage(imagePath) {
				const normalizedPath = this.normalizeImagePath(imagePath)

				if (this.images.includes(normalizedPath)) return

				if (this.images.length >= this.maxImages) {
					throw new Error(`Maximal ${this.maxImages} Referenzbilder erlaubt.`)
				}

				this.images.push(normalizedPath)
			},

			removeImage(imageIndex) {
				if (!this.loading && !this.uploading) {
					this.images.splice(imageIndex, 1)
				}
			},

			async readResponse(response) {
				const responseText = await response.text()
				let responseData

				try {
					responseData = JSON.parse(responseText)
				} catch (error) {
					throw new Error(response.ok
						? 'Der Server hat keine gültige JSON-Antwort geliefert.'
						: `Serverfehler: HTTP ${response.status}`
					)
				}

				if (responseData?.error) throw new Error(responseData.error)
				if (!response.ok) throw new Error(`Serverfehler: HTTP ${response.status}`)

				const imagePath = responseData?.payload ?? responseData
				return this.normalizeImagePath(imagePath)
			},

			async generateImage() {
				if (this.loading || this.uploading || !this.input.trim()) return

				this.loading = true
				this.resetMetaInfo()
				this.startClock()

				const requestController = new AbortController()
				this.abortController = requestController

				const formData = new FormData()
				formData.append('question', this.input)

				this.images.forEach(imagePath => {
					formData.append('images[]', imagePath)
				})

				Object.keys(initialImageSettings).forEach(settingName => {
					formData.append(settingName, this[settingName])
				})

				try {
					const response = await fetch('/image/generate', {
						method: 'POST',
						body: formData,
						signal: requestController.signal,
					})

					const generatedImage = await this.readResponse(response)
					this.output = generatedImage

					// Wie bisher: Ergebnis wird Referenz für die nächste Bearbeitung.
					this.images = [generatedImage]
				} catch (error) {
					if (error.name === 'AbortError') {
						this.showError(
							'Anfrage im Browser abgebrochen. Die Generierung auf dem Server kann weiterlaufen.'
						)
					} else {
						this.showError(error.message || 'Bildgenerierung fehlgeschlagen.')
					}
				} finally {
					this.stopClock()
					this.loading = false
					this.abortController = null
				}
			},

			dragDropSetup() {
				const dropArea = imageGeneratorElement.querySelector('#drop-area')
				const fileElement = imageGeneratorElement.querySelector('#fileElem')

				if (!dropArea || !fileElement) return

				const listenerOptions = {
					signal: this.eventAbortController.signal,
				}

				document.addEventListener('dragstart', event => {
					const draggedImage = event.target

					if (
						!(draggedImage instanceof HTMLImageElement) ||
						!draggedImage.closest('.gallery-container, .image-history, .generated-image') ||
						!event.dataTransfer
					) return

					event.dataTransfer.setData(
						'application/x-image-generator',
						draggedImage.currentSrc || draggedImage.src
					)
				}, listenerOptions)



				fileElement.addEventListener('change', event => {
					const selectedFiles = Array.from(event.target.files || [])
					fileElement.value = ''
					this.uploadFiles(selectedFiles)
				}, listenerOptions)

				dropArea.addEventListener('dragenter', event => {
					event.preventDefault()
					event.stopPropagation()

					if (!this.loading && !this.uploading) {
						dropArea.classList.add('dragging')
					}
				}, listenerOptions)

				dropArea.addEventListener('dragover', event => {
					event.preventDefault()
					event.stopPropagation()

					if (event.dataTransfer) {
						event.dataTransfer.dropEffect = this.loading || this.uploading
							? 'none'
							: 'copy'
					}
				}, listenerOptions)

				dropArea.addEventListener('dragleave', event => {
					if (!dropArea.contains(event.relatedTarget)) {
						dropArea.classList.remove('dragging')
					}
				}, listenerOptions)

				dropArea.addEventListener('drop', event => {
					event.preventDefault()
					event.stopPropagation()
					dropArea.classList.remove('dragging')

					if (this.loading || this.uploading || !event.dataTransfer) return

					const transferData = event.dataTransfer
					const galleryImage = transferData.getData('application/x-image-generator')
					const droppedFiles = Array.from(transferData.files || [])

					if (!galleryImage && droppedFiles.length) {
						this.uploadFiles(droppedFiles)
						return
					}

					const rawPaths = galleryImage ||
						transferData.getData('text/uri-list') ||
						transferData.getData('text/plain')

					const imagePaths = rawPaths.split(/\r?\n/)
						.map(imagePath => imagePath.trim())
						.filter(imagePath => imagePath && !imagePath.startsWith('#'))

					try {
						if (!imagePaths.length) throw new Error('Kein Bildpfad vorhanden.')

						this.errormessages = ''
						imagePaths.forEach(imagePath => this.addImage(imagePath))
					} catch (error) {
						this.showError(error.message)
					}
				}, listenerOptions)
			},

			async uploadFiles(fileList) {
				if (this.loading || this.uploading) return

				const files = Array.from(fileList || [])
				if (!files.length) return

				if (this.images.length + files.length > this.maxImages) {
					this.showError(`Bitte insgesamt maximal ${this.maxImages} Bilder auswählen.`)
					return
				}

				const allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp']

				for (const file of files) {
					if (!allowedMimeTypes.includes(file.type)) {
						this.showError(`${file.name}: Nur JPG, PNG oder WebP erlaubt.`)
						return
					}

					if (file.size > 25 * 1024 * 1024) {
						this.showError(`${file.name}: Maximal 25 MB pro Bild erlaubt.`)
						return
					}
				}

				this.uploading = true
				this.errormessages = ''

				const requestController = new AbortController()
				this.uploadAbortController = requestController

				try {
					// Einzelrequests: bestehender Upload-Controller bleibt unverändert.
					for (const [fileIndex, file] of files.entries()) {
						this.uploadProgress = `${fileIndex + 1} / ${files.length}`

						const formData = new FormData()
						formData.append('imagedata', file)

						const response = await fetch('/image/upload', {
							method: 'POST',
							body: formData,
							signal: requestController.signal,
						})

						const uploadedImage = await this.readResponse(response)
						this.addImage(uploadedImage)
					}
				} catch (error) {
					if (error.name !== 'AbortError') {
						this.showError(
							`${error.message || 'Upload fehlgeschlagen.'} Bereits hochgeladene Bilder bleiben erhalten.`
						)
					}
				} finally {
					this.uploading = false
					this.uploadProgress = ''
					this.uploadAbortController = null
				}
			},

			async uploadFile(file) {
				await this.uploadFiles(file ? [file] : [])
			},

			handlePaste(event) {
				const clipboardData = event.clipboardData
				if (!clipboardData) return

				const files = Array.from(clipboardData.items || [])
					.filter(clipboardItem => (
						clipboardItem.kind === 'file' &&
						clipboardItem.type.startsWith('image/')
					))
					.map(clipboardItem => clipboardItem.getAsFile())
					.filter(Boolean)

				if (!files.length) return

				event.preventDefault()

				if (this.loading || this.uploading) return

				if (confirm(`${files.length} Bild(er) aus der Zwischenablage hochladen?`)) {
					this.uploadFiles(files)
				}
			},

			getContentFromPasteEvent(event) {
				const clipboardData = event.clipboardData || event.originalEvent?.clipboardData
				if (!clipboardData) return ''

				for (const clipboardItem of Array.from(clipboardData.items || [])) {
					if (clipboardItem.kind === 'file') {
						return clipboardItem.getAsFile()
					}
				}

				return clipboardData.getData('text')
			},

			async copyPasteUpload(file) {
				if (!file || this.loading || this.uploading) return

				if (confirm('Möchten Sie Ihren Screenshot hochladen?')) {
					await this.uploadFile(file)
				}
			},
		},
	}).mount(imageGeneratorElement)
}

// Darkmode
function toggleDarkmode() {
	const existingStylesheet = document.querySelector('#dark-mode-css-link')

	if (existingStylesheet) {
		existingStylesheet.remove()
		document.cookie = 'darkmode=0; path=/; SameSite=Lax; max-age=31536000'
		return
	}

	const stylesheet = document.createElement('link')
	stylesheet.id = 'dark-mode-css-link'
	stylesheet.rel = 'stylesheet'
	stylesheet.href = '/styles/css/darkmode.css'
	document.head.appendChild(stylesheet)

	document.cookie = 'darkmode=1; path=/; SameSite=Lax; max-age=31536000'
}

function setupDarkmodeToggle() {
	const colorModeIcon = document.querySelector('.color-mode')
	colorModeIcon?.addEventListener('click', toggleDarkmode)
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', setupDarkmodeToggle, { once: true })
} else {
	setupDarkmodeToggle()
}