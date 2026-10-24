export default Vue.defineComponent({
data() {return {
	payload: '',
	timestamps: false,
	loading: false,
	uploadController: null,
	transcribing: false,
	dragActive: false,
	dragDepth: 0,
	dropTargetElement: null
}},

emits: ['change'],

template: `
<figure v-if="payload" title="Klicken zum entfernen" @click="payload = ''" class="input-payload">
	<img :src="payload">
</figure>

<div v-if="dragActive" class="file-drop-overlay">
	Inhalt hochladen
</div>

<div class="float-button file-button no-select" :class="{timestamps: timestamps}" onclick="event.preventDefault(); document.querySelector('#pdfupload').click()"><img v-if="!loading" class="cloud" src="/styles/img/upload-icon.svg"> <span>Datei hochladen (Mp3, Word, PDF, JPG, PNG)</span>
<div v-if="loading" class="loadIndicator" style="top:0px; width:14px; height:8px; padding:0; margin-left:0.2em"><div></div><div></div><div></div></div></div>
<input style="display:none" id="pdfupload" type="file" name="file" @change="uploadFile">
`,

mounted: function() {
	this.initPasteUpload()
	this.initDragAndDropUpload()
},

unmounted() {
	this.stopUpload()
	this.destroyDragAndDropUpload()
},

methods: {

	async uploadFile(event) {
		if (this.loading || this.$root.loading) {return}
		let fileObject = event

		if (event.target && event.target.files && event.target.files[0]) {
			fileObject = event.target.files[0]
		}

		this.loading = true
		this.transcribing = fileObject.type.startsWith('audio/') || /\.(mp3|mp4|mpeg|mpga|m4a|aac|wav|webm|ogg)$/i.test(fileObject.name || '')
		this.uploadController = new AbortController()
		if (this.transcribing) {
			this.$root.loading = true
			this.$root.modelmode = 'Transkribiere Audio'
			document.addEventListener('keydown', this.stopUploadOnEscape)
		}

		let formData = new FormData()
		formData.append('file', fileObject)
		formData.append('timestamps', this.timestamps)

		try {
			const response = await fetch('/import/file', {method: 'POST', body: formData, signal: this.uploadController.signal})
			if (!response.ok) {throw new Error('Datei-Upload fehlgeschlagen')}
			const contentType = response.headers.get('Content-Type') || ''
			if (contentType.includes('text/event-stream')) {
				await this.readTranscriptionStream(response)
				return
			}
			const responseText = await response.text()
			try {
				let parsedResponse = JSON.parse(responseText)

				if (parsedResponse.payload) {
					this.payload = parsedResponse.payload
					return
				}
			} catch (parseError) {
			}

			this.$root.input = responseText
		} catch (error) {
			if (error.name === 'AbortError') {return}
			console.error(error)
			this.$root.errormessages = error.message || 'Transkription fehlgeschlagen'
		} finally {
			this.loading = false
			if (this.transcribing) {
				this.$root.loading = false
				this.$root.modelmode = ''
				document.removeEventListener('keydown', this.stopUploadOnEscape)
			}
			this.transcribing = false
			this.uploadController = null
		}
	},

	stopUploadOnEscape(event) {
		if (event.key === 'Escape') {this.stopUpload()}
	},

	stopUpload() {
		if (!this.transcribing || !this.uploadController) {return false}
		this.uploadController.abort()
		return true
	},

	async readTranscriptionStream(response) {
		const reader = response.body.getReader()
		const decoder = new TextDecoder()
		let buffer = ''
		let finished = false
		let confirmedText = ''
		let currentPart = ''
		this.$root.input = ''

		while (true) {
			const {value, done} = await reader.read()
			if (done) {break}
			buffer += decoder.decode(value, {stream: true}).replace(/\r\n/g, '\n')
			let boundary
			while ((boundary = buffer.indexOf('\n\n')) !== -1) {
				const block = buffer.slice(0, boundary)
				buffer = buffer.slice(boundary + 2)
				for (const line of block.split('\n')) {
					if (!line.startsWith('data:')) {continue}
					const event = JSON.parse(line.slice(5).trim())
					if (event.type === 'delta') {
						currentPart += event.text
						this.$root.input = confirmedText + currentPart
					}
					if (event.type === 'part_done') {
						confirmedText = event.text + (this.timestamps ? '\n' : '\n\n')
						currentPart = ''
						this.$root.input = event.text
					}
					if (event.type === 'done') {
						this.$root.input = event.text
						finished = true
					}
					if (event.type === 'error') {throw new Error(event.message)}
				}
			}
		}
		if (!finished) {throw new Error('Transkription wurde vorzeitig beendet')}
	},

	initPasteUpload() {
		document.onpaste = (event) => {
			const content = this.getContentFromPasteEvent(event);
			if (typeof content === 'object') {this.copyPasteUpload(content);}
		}
	},

	getContentFromPasteEvent(event) {

		const items = (event.clipboardData || event.originalEvent.clipboardData).items;

		for (let index in items) {
			const item = items[index];

			if (item.kind === 'file') {
				return item.getAsFile();
			}

		}

		return (event.clipboardData || window.clipboardData).getData("text")
	},

	async copyPasteUpload(file) {
		if (!confirm('Möchten Sie ihren Screenshot hochladen?')) {return}
		this.uploadFile(file)
	},

	initDragAndDropUpload() {
		Vue.nextTick(() => {
			this.dropTargetElement = document.querySelector('.user-input .input-area')

			// Verhindert global, dass der Browser Dateien öffnet
			window.addEventListener('dragover', this.preventBrowserFileOpen)
			window.addEventListener('drop', this.preventBrowserFileOpen)

			if (!this.dropTargetElement) {return}

			this.dropTargetElement.addEventListener('dragenter', this.handleDragEnter)
			this.dropTargetElement.addEventListener('dragover', this.handleDragOver)
			this.dropTargetElement.addEventListener('dragleave', this.handleDragLeave)
			this.dropTargetElement.addEventListener('drop', this.handleDrop)
		})
	},

	destroyDragAndDropUpload() {
		window.removeEventListener('dragover', this.preventBrowserFileOpen)
		window.removeEventListener('drop', this.preventBrowserFileOpen)

		if (!this.dropTargetElement) {return}

		this.dropTargetElement.removeEventListener('dragenter', this.handleDragEnter)
		this.dropTargetElement.removeEventListener('dragover', this.handleDragOver)
		this.dropTargetElement.removeEventListener('dragleave', this.handleDragLeave)
		this.dropTargetElement.removeEventListener('drop', this.handleDrop)

		this.dropTargetElement.classList.remove('file-drop-active')
		this.dropTargetElement = null
	},

	isFileDragEvent(event) {
		if (!event.dataTransfer || !event.dataTransfer.types) {return false}
		return Array.from(event.dataTransfer.types).includes('Files')
	},

	handleDragEnter(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()

		this.dragDepth++
		this.dragActive = true
		this.dropTargetElement.classList.add('file-drop-active')
	},

	handleDragOver(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()

		event.dataTransfer.dropEffect = 'copy'
		this.dragActive = true
		this.dropTargetElement.classList.add('file-drop-active')
	},

	handleDragLeave(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()

		this.dragDepth--

		if (this.dragDepth <= 0) {
			this.dragDepth = 0
			this.dragActive = false
			this.dropTargetElement.classList.remove('file-drop-active')
		}
	},

	handleDrop(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()

		this.dragDepth = 0
		this.dragActive = false
		this.dropTargetElement.classList.remove('file-drop-active')

		const uploadedFiles = event.dataTransfer.files

		if (!uploadedFiles || !uploadedFiles.length) {return}

		const file = uploadedFiles[0]
		if (file.name.startsWith('resp_')) {
			this.applyConversation(file)
			return
		}

		this.uploadFile(file)
	},

	async applyConversation(file) {
		if (this.$root.loading) {return}

		const responseID = file.name
		const formData = new FormData()
		formData.append('file', file)
		const response = await fetch('/conversation/' + encodeURIComponent(responseID) + '/json', {
			method: 'POST',
			body: formData
		})
		if (!response.ok) {
			this.$root.errormessages = 'Chatlogfile konnte nicht übernommen werden'
			return
		}

		if (this.$root.$refs.history.responseID !== responseID) {
			await this.$root.$refs.history.kill()
		}
		this.$root.input = ''
		this.$root.output = ''
		sessionStorage.removeItem('input')
		sessionStorage.removeItem('output')
		this.$root.responseID = responseID
		this.$root.$refs.history.responseID = responseID
		sessionStorage.responseID = responseID
		await this.$root.$refs.history.fetchHistory()
	},

	preventBrowserFileOpen(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()
	},


}, // End Methods
})
