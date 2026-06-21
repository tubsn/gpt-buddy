export default Vue.defineComponent({
data() {return {
	payload: '',
	timestamps: false,
	loading: false,
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

watch: {
	timestamps(value) {localStorage.timestamps = value;},
},

mounted: function() {
	this.initPasteUpload()
	this.initDragAndDropUpload()
	if (localStorage.timestamps == 'true') {this.timestamps = true}
},

unmounted() {
	this.destroyDragAndDropUpload()
},

methods: {

	uploadFile(event) {
		let fileObject = event

		if (event.target && event.target.files && event.target.files[0]) {
			fileObject = event.target.files[0]
		}

		this.loading = true

		let formData = new FormData()
		formData.append('file', fileObject)
		formData.append('timestamps', this.timestamps)

		fetch('/import/file', {
			method: 'POST',
			body: formData
		})
		.then(response => response.text())
		.then(responseText => {
			try {
				let parsedResponse = JSON.parse(responseText)

				if (parsedResponse.payload) {
					this.payload = parsedResponse.payload
					return
				}
			} catch (parseError) {
			}

			this.$root.input = responseText
		})
		.catch(error => {
			console.error(error)
		})
		.finally(() => {
			this.loading = false
		})
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

		this.uploadFile(uploadedFiles[0])
	},

	preventBrowserFileOpen(event) {
		if (!this.isFileDragEvent(event)) {return}

		event.preventDefault()
		event.stopPropagation()
	},


}, // End Methods
})