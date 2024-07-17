const flundrVueUpload = Vue.defineComponent({
data() {return {
	loading: false,
	ignoredFiles: [],
}},

props: ['action', 'multiple', 'maxfilesizemb'],
emits: ['done','stored','failed'],

computed: {
	maxsize() {return (this.maxfilesizemb || 50) * 1024 * 1024},
},

template: `
<button @click.prevent="openFileSelector" @drop.prevent="dropped" class="fl-upload-button">
	<slot></slot>
	<div v-if="loading" class="loadIndicator white"><div></div><div></div><div></div></div>
</button>
<input ref="fileSelector" :multiple=multiple style="display:none" type="file" @change="upload">
`,

mounted: function() {

	const events = ['dragenter', 'dragover', 'dragleave', 'drop']
	events.forEach((eventName) => {
		document.body.addEventListener(eventName, (event) => {event.preventDefault()})
			
	})


},

methods: {

	dropped(event) {

		let files = [];
		[...event.dataTransfer.items].forEach((item, i) => {
			// If dropped items aren't files, reject them
			if (item.kind === "file") {
				const file = item.getAsFile()
				//console.log(`${file}`)
				files.push(file)
			}
		})
		this.upload(files)
	},


	openFileSelector() {
		this.$refs.fileSelector.click()
	},

	uploadReport() {
		if (this.ignoredFiles.length < 1) {return}
		let filenames = ''
		this.ignoredFiles.forEach((file) => {
			filenames += `${file.name} (${(file.size/1024/1024).toFixed(2)}mb)`
		})
		console.warn('Folgende Dateien waren zu groß: ' + filenames)
		this.loading = false
	},

	checkIntegrity(files) {
		this.ignoredFiles = files.filter(file => file.size > this.maxsize)
		files = files.filter(file => file.size < this.maxsize)
		return files
	},

	connectionError(statusCode) {
		console.log('FL-Upload: Connection Error ('+statusCode+')')
		this.uploadDone()
	},

	consumeServerText(message) {
		console.log('FL-Upload: ' + message)
	},

	consumeServerJson(json) {
		if (!json.stored || !json.failed) {return}

		this.$emit('stored', json.stored)
		this.$emit('failed', json.failed)
	
		if (json.failed.length <= 0) {return}
		let text = 'Folgende Dateien konnten nicht hochgeladen werden: \n' 
		json.failed.forEach((file,index) => {
			 text = text + `${file.name} - ${file.error}`
		})
		alert(text)
	},

	async upload(input) {
		
		this.loading = true
		let files = null

		if (input.target) {
			// Fileupload Event
			files = Array.from(event.target.files)
		}
		else {files = input}

		files = this.checkIntegrity(files)
		if (files.length < 1) {this.uploadDone(); return}

		let formData = new FormData()
		files.forEach((file,index) => {formData.append('file'+index, file)})

		// Geht Los
		let response = await fetch(this.action, {method: 'POST', body: formData})
		if (!response.ok) {this.connectionError(response.status); this.uploadDone(); return}
	
		let text; text = await response.text(); // Recieves any Server output

		try { // Parse Text as JSON
			let json = JSON.parse(text)
			this.consumeServerJson(json)
		} catch {this.consumeServerText(text)}

		this.uploadDone()
	},

	uploadDone() {
		this.loading = false
		this.$emit('done')
	},

}, // End Methods
})