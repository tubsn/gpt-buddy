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
		// Die Anwendung funktioniert auch ohne verfügbaren Browser-Speicher.
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
		data() {
			return {
				...initialImageSettings,
				input: '',
				output: '',
				payload: '',
				image: '',
				loading: false,
				uploading: false,
				responseSeconds: 0,
				stopWatchStartTime: null,
				errormessages: '',
				abortController: null,
				uploadAbortController: null,
				eventAbortController: null,
				settingWatchStops: [],
			}
		},

		components: {
			dropdown: Dropdown,
		},

		computed: {
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
					const stopWatching = this.$watch(
						() => this[settingName],
						settingValue => {
							writeStorage('localStorage', settingName, settingValue)
						}
					)

					this.settingWatchStops.push(stopWatching)
				})
			},

			autofocus() {
				this.$nextTick(() => {
					this.$refs.autofocusElement?.focus()
				})
			},

			getHistory() {
				const savedInput = readStorage('sessionStorage', 'input')

				if (savedInput) {
					this.input = savedInput.slice(0, 4000)
				}
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
				if (!this.stopWatchStartTime) {
					return 0
				}

				return Math.round((Date.now() - this.stopWatchStartTime) / 100) / 10
			},

			showError(message) {
				this.errormessages = message
			},

			handleGeneratorKeydown(event) {
				if (event.key !== 'Escape' || !this.loading) {
					return
				}

				event.preventDefault()
				this.abortController?.abort()
			},

			async generateImage() {
				if (this.loading || this.uploading || !this.input.trim()) {
					return
				}

				this.loading = true
				this.resetMetaInfo()
				this.startClock()

				const requestController = new AbortController()
				this.abortController = requestController

				const formData = new FormData()
				formData.append('question', this.input)
				formData.append('image', this.image)

				Object.keys(initialImageSettings).forEach(settingName => {
					formData.append(settingName, this[settingName])
				})

				try {
					const response = await fetch('/image/generate', {
						method: 'POST',
						body: formData,
						signal: requestController.signal,
					})

					const responseText = await response.text()
					let responseData

					try {
						responseData = JSON.parse(responseText)
					} catch (error) {
						throw new Error(
							response.ok
								? 'Der Server hat keine gültige JSON-Antwort geliefert.'
								: `Serverfehler: HTTP ${response.status}`
						)
					}

					if (responseData?.error) {
						throw new Error(responseData.error)
					}

					if (!response.ok) {
						throw new Error(`Serverfehler: HTTP ${response.status}`)
					}

					const generatedImage = responseData?.payload ?? responseData

					if (typeof generatedImage !== 'string' || !generatedImage.trim()) {
						throw new Error('Der Server hat keinen gültigen Bildpfad geliefert.')
					}

					this.output = generatedImage
					this.image = generatedImage
				} catch (error) {
					if (error.name !== 'AbortError') {
						this.showError(error.message || 'Die Bildgenerierung ist fehlgeschlagen.')
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

				if (!dropArea || !fileElement) {
					return
				}

				const listenerOptions = {
					capture: true,
					signal: this.eventAbortController.signal,
				}

				// Beim Ziehen eines Galeriebildes dessen Bildpfad mitgeben.
				document.addEventListener('dragstart', event => {
					const draggedImage = event.target

					if (
						!(draggedImage instanceof HTMLImageElement) ||
						!draggedImage.closest('.gallery-container, .image-history, .generated-image') ||
						!event.dataTransfer
					) {
						return
					}

					event.dataTransfer.setData(
						'application/x-image-generator',
						draggedImage.currentSrc || draggedImage.src
					)
				}, listenerOptions)

				dropArea.addEventListener('click', event => {
					if (
						event.target === fileElement ||
						this.loading ||
						this.uploading
					) {
						return
					}

					fileElement.click()
				}, listenerOptions)

				fileElement.addEventListener('change', event => {
					const selectedFile = event.target.files?.[0]

					if (selectedFile) {
						this.uploadFile(selectedFile)
					}

					fileElement.value = ''
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

					if (!this.loading && !this.uploading) {
						dropArea.classList.add('dragging')
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

					if (this.loading || this.uploading || !event.dataTransfer) {
						return
					}

					const transferData = event.dataTransfer
					const galleryImage = transferData.getData('application/x-image-generator')
					const droppedFile = transferData.files?.[0]

					if (!galleryImage && droppedFile) {
						this.uploadFile(droppedFile)
						return
					}

					const uriList = transferData.getData('text/uri-list')
					const droppedUri = uriList
						.split(/\r?\n/)
						.map(line => line.trim())
						.find(line => line && !line.startsWith('#'))

					const imagePath = galleryImage ||
						droppedUri ||
						transferData.getData('text/plain').trim()

					try {
						if (!imagePath) {
							throw new Error('Kein Bildpfad vorhanden.')
						}

						const imageUrl = new URL(imagePath, window.location.href)

						if (
							imageUrl.origin !== window.location.origin ||
							!/^\/(uploads|generated)\//.test(imageUrl.pathname)
						) {
							throw new Error('Nicht erlaubter Bildpfad.')
						}

						this.image = imageUrl.pathname
						this.errormessages = ''
					} catch (error) {
						this.showError(
							'Bitte eine Bilddatei oder ein Bild aus der eigenen Galerie hierher ziehen.'
						)
					}
				}, listenerOptions)
			},

			async uploadFile(file) {
				if (!file || this.loading || this.uploading) {
					return
				}

				const allowedMimeTypes = [
					'image/jpeg',
					'image/png',
					'image/webp',
				]

				if (!allowedMimeTypes.includes(file.type)) {
					this.showError('Bitte ein JPG-, PNG- oder WebP-Bild auswählen.')
					return
				}

				if (file.size > 25 * 1024 * 1024) {
					this.showError('Das Bild darf maximal 25 MB groß sein.')
					return
				}

				this.uploading = true
				this.errormessages = ''

				const requestController = new AbortController()
				this.uploadAbortController = requestController

				const formData = new FormData()
				formData.append('imagedata', file)

				try {
					const response = await fetch('/image/upload', {
						method: 'POST',
						body: formData,
						signal: requestController.signal,
					})

					const responseText = await response.text()
					let responseData

					try {
						responseData = JSON.parse(responseText)
					} catch (error) {
						throw new Error(
							response.ok
								? 'Der Upload-Server hat keine gültige JSON-Antwort geliefert.'
								: `Upload fehlgeschlagen: HTTP ${response.status}`
						)
					}

					if (responseData?.error) {
						throw new Error(responseData.error)
					}

					if (!response.ok) {
						throw new Error(`Upload fehlgeschlagen: HTTP ${response.status}`)
					}

					const uploadedImage = responseData?.payload ?? responseData

					if (typeof uploadedImage !== 'string' || !uploadedImage.trim()) {
						throw new Error('Der Server hat keinen gültigen Bildpfad geliefert.')
					}

					this.image = uploadedImage
				} catch (error) {
					if (error.name !== 'AbortError') {
						this.showError(error.message || 'Der Upload ist fehlgeschlagen.')
					}
				} finally {
					this.uploading = false
					this.uploadAbortController = null
				}
			},

			getContentFromPasteEvent(event) {
				const clipboardData = event.clipboardData || event.originalEvent?.clipboardData

				if (!clipboardData) {
					return ''
				}

				for (const clipboardItem of Array.from(clipboardData.items || [])) {
					if (clipboardItem.kind === 'file') {
						return clipboardItem.getAsFile()
					}
				}

				return clipboardData.getData('text')
			},

			async copyPasteUpload(file) {
				if (!file || this.loading || this.uploading) {
					return
				}

				if (!confirm('Möchten Sie Ihren Screenshot hochladen?')) {
					return
				}

				await this.uploadFile(file)
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

	if (colorModeIcon) {
		colorModeIcon.addEventListener('click', toggleDarkmode)
	}
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', setupDarkmodeToggle, { once: true })
} else {
	setupDarkmodeToggle()
}