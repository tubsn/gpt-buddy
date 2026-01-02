export default Vue.defineComponent({
data() {return {
	payload: '',
	loading: false
}},

emits: ['change'],

template: `
<figure v-if="payload" title="Klicken zum entfernen" @click="payload = ''" class="input-payload">
	<img :src="payload">
</figure>

<div class="float-button file-button no-select" onclick="event.preventDefault(); document.querySelector('#pdfupload').click()"><img class="cloud" src="/styles/img/upload-icon.svg"> <span>Datei hochladen (Mp3, Word, PDF, JPG, PNG)</span></div>
<input style="display:none" id="pdfupload" type="file" name="file" @change="uploadFile">
`,

mounted: function() {
	this.initPasteUpload()
},

methods: {

	uploadFile(event) {

		let file = event
		if (event.target) {
			file = event.target.files[0]
		}

		this.loading = true

		let formData = new FormData();
		formData.append('file', file);

		fetch('/import/file', {
			method: 'POST',
			body: formData
		})

		.then(response => response.text())
		.then(data => {
			try {
				let jsondata = JSON.parse(data)
				if (jsondata.payload) {
					this.payload = jsondata.payload
					this.loading = false
					return
				}
			} catch (error) {
				console.log(error)
			}
			
			this.$root.input = data
			this.loading = false
		})
		.catch(error => {
			console.error(error)
			this.loading = false
		});

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