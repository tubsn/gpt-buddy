export default Vue.defineComponent({
data() {return {
	payload: '',
	timestamps: false,
	loading: false
}},

emits: ['change'],

template: `
<figure v-if="payload" title="Klicken zum entfernen" @click="payload = ''" class="input-payload">
	<img :src="payload">
</figure>

<div class="float-button file-button no-select" :class="{timestamps: timestamps}" onclick="event.preventDefault(); document.querySelector('#pdfupload').click()"><img v-if="!loading" class="cloud" src="/styles/img/upload-icon.svg"> <span>Datei hochladen (Mp3, Word, PDF, JPG, PNG)</span>
<div v-if="loading" class="loadIndicator" style="top:0px; width:14px; height:8px; padding:0; margin-left:0.2em"><div></div><div></div><div></div></div></div>
<input style="display:none" id="pdfupload" type="file" name="file" @change="uploadFile">
`,

watch: {
	timestamps(value) {localStorage.timestamps = value;},
},

mounted: function() {
	this.initPasteUpload()
	if (localStorage.timestamps == 'true') {this.timestamps = true}
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


}, // End Methods
})