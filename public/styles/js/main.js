
const {createApp} = Vue

createApp({
data() {
	return {
		action: null,
		description: null,
		options: 400,
		input: '',
		output: '',
		history: '',
		loading: false,
		responseSeconds: 0,
		tokens: 0,
		errormessages: '',
	}
},

computed: {
	responsetime() {
		if (this.responseSeconds <= 0) {return ''}
		return this.responseSeconds
	},

	error() {
		if (!this.errormessages) {return ''}
		return `Fehler: ${this.errormessages}`
	},

},

mounted() {
	this.getHistory()
	this.autofocus()
	this.autoSelectBox()
},

methods: {

	autofocus() {
		if (this.$refs.autofocusElement) {this.$refs.autofocusElement.focus()}
	},

	autoSelectBox() {
		if (!this.$refs.selectElement) {return}
		let element = this.$refs.selectElement
		if (!element.value) {this.action = element.children[0].value}
	},

	resetMetaInfo() {
		this.responseSeconds = 0
		this.errormessages = ''
	},

	elapsedTime(start) {return (Date.now() - start) / 1000},


	setDescription() {

		if (!this.$refs.selectElement) {return ''}
		let selectbox = this.$refs.selectElement
		this.description = selectbox.options[selectbox.selectedIndex].getAttribute('data-description')

	},

	async wipeHistory() {
		let response = await fetch('/wipe', {method: "POST"})
		this.history = ''
		this.output = ''
	},

	async getHistory() {
		let response = await fetch('/history', {method: "POST"})
		let json = await response.json()
		this.history = json.history
		if (json.action) {this.action = json.action}
	},

	async ask() {

		this.resetMetaInfo()
		const start = Date.now()

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('action', this.action)

		this.loading = true

		let response = await fetch('/ask', {method: "POST", body: formData})
		if (!response.ok) {console.warn(`Network Error when querying API`);	return}

		let json = await response.json()
		.catch(error => {
			console.log(error.message)
			this.errormessages = 'Server Error: ' + error.message
			this.loading = false
			this.responseSeconds = this.elapsedTime(start)
		})

		if (json.error) {this.errormessages = 'ChatGPT API Error: ' + json.errormessage}

		if (!json.answer) {this.output = JSON.stringify(json)}
		else {
			this.output = json.answer
			this.history = json.history
			this.tokens = json.tokens
		}
		
		this.loading = false
		this.responseSeconds = this.elapsedTime(start)
		this.autofocus()

	},
},


}).mount('#gptInterface')
