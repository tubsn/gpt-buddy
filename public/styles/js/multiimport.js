const multiImportApp = Vue.createApp({
	data() {return {
		loading: false,
		ignoredFiles: [],
		results: [],
		loaders: [],
		maxfilesizemb: 50,
		output: '111',
	}},

	computed: {
		maxsize() {return (this.maxfilesizemb || 50) * 1024 * 1024},
	},

	mounted: function() {},

	methods: {

		resetResults() {
			this.results = []
		},

		dropped(event) {

			let files = [];
			[...event.dataTransfer.items].forEach((item, i) => {
				// If dropped items aren't files, reject them
				if (item.kind === "file") {
					const file = item.getAsFile()
					files.push(file)
				}
			})
			this.gatherFiles(files)
		},

		checkIntegrity(files) {
			this.ignoredFiles = files.filter(file => file.size > this.maxsize)
			files = files.filter(file => file.size < this.maxsize)
			return files
		},

		openFileSelector() {
			this.$refs.fileSelector.click()
		},


		async gatherFiles(input) {

			this.loading = true
			let files = null

			// Drag and Drop detection
			if (input.target) {files = Array.from(event.target.files)}
			else {files = input}

			files = this.checkIntegrity(files)
			if (files.length < 1) {this.uploadDone(); return}

			for (const [index, file] of files.entries()) {
			    this.results.push({name: file.name, status: null, index: index});
			    this.loaders[index] = true;
			    await this.upload(file, index);
			    this.loaders[index] = false;
			}

			this.uploadDone()
		},



		async upload(file, index) {
			
			let formData = new FormData()
			formData.append('file', file)

			let response = await fetch('', {method: 'POST', body: formData})
			if (!response.ok) {this.connectionError(response.status); this.uploadDone(); return}
		
			let json = await response.json();
			this.results[index].status = json
			

			/*
			let text; text = await response.text(); // Recieves any Server output

			try { // Parse Text as JSON
				let json = JSON.parse(text)
				this.consumeServerJson(json)
				this.results.push({name: file.name, status: json})
				
			} catch {this.consumeServerText(text)}
			*/
			//this.results.push({name: file.name, status: text})
			
		},


		uploadDone() {
			this.loading = false
			//this.$emit('done')
		},




	}, // End Methods
}).mount('#multiImportApp')