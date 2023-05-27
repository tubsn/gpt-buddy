
const {createApp} = Vue

createApp({
data() {
	return {
		action: null,
		description: null,
		options: 400,
		markdown: false,
		input: '',
		output: '',
		history: '',
		loading: false,
		responseSeconds: 0,
		tokens: 0,
		errormessages: '',
		jwt: null,
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

watch: {
	history(content) {sessionStorage.history = JSON.stringify(content)},
	action(value) {sessionStorage.action = value},
	markdown(value) {sessionStorage.markdown = value},
},

mounted() {
	this.jwtToken()
	this.autofocus()
	this.getHistory()
	this.autoSelectBox()
},

methods: {

	async bestServer() {

		let servers = ['//chatapi.lr-digital.de','//chatapi2.lr-digital.de','//chatapi3.lr-digital.de']
		let availableServer = false

		for (let server of servers) {
			
			await this.checkResponseTime(server + '/ping', 120)
			.then(responseTime => {
				availableServer = server
			})
			.catch(error => {});	

			if (availableServer) {break}
		}

		if (!availableServer) {return servers[0]}
		return availableServer

		/*
		await this.checkResponseTime(this.apiurl + '/ping', 50)
		.then(responseTime => {
			console.log(`Die Seite hat in ${responseTime} ms geantwortet.`);
		})
		.catch(error => {
			console.error(`Fehler beim Überprüfen der Seite: ${error.message}`);
		});
		*/

	},

	autofocus() {
		if (!this.$refs.autofocusElement) {return}
		Vue.nextTick(() => {this.$refs.autofocusElement.focus()})
	},

	jwtToken() {
		let form = this.$refs.form
		this.jwt = form.dataset.token
	},

	autoSelectBox() {
		if (!this.$refs.selectElement) {return}
		let selectbox = this.$refs.selectElement

		if (this.action != null) {
			let options = [...selectbox].map(el => el.value);
			if (options.includes(this.action)) {selectbox.value = this.action; return}
		}		

		this.action = selectbox.children[0].value			
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

	wipeHistory() {
		this.history = ''
		this.output = ''
		this.markdown = false
	},

	getHistory() {
		if (sessionStorage.history) {
			this.history = JSON.parse(sessionStorage.history)
		}
		this.action = sessionStorage.action
		this.markdown = sessionStorage.markdown
	},

	copyHistoryToClipboard() {
		let historyData = JSON.parse(JSON.stringify(this.history));
		let history = '';
		historyData.forEach(entry => {
			history = history + `[${entry.role}] ${entry.content}\n\n`
		}) 
		navigator.clipboard.writeText(history);
	},

	async importArticle(event) {
		let value = event.target.value || null;
		if (!value) {this.input = ''; return}

		let id = value.match(/-(\d{8}).html/);
		if (id) {id = id[1]}
		else {id = value}

		let response = await fetch('/import/article/'+id)
		if (!response.ok) {
			this.input = 'URL ungültig oder Artikel nicht gefunden'
			return
		}

		let json = await response.json()
		.catch(error => {this.input = error; return})

		this.input = json.content

	},

	async ask() {

		this.loading = true
		this.resetMetaInfo()
		const start = Date.now()

		let apiurl = await this.bestServer()
		console.log('Asking on :' + apiurl)

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('action', this.action)
		formData.append('markdown', this.markdown)
		formData.append('history', JSON.stringify(this.history))
		formData.append('token', this.jwt)

		let response = await fetch(apiurl + '/ask', {method: "POST", body: formData})
		if (!response.ok) {
			this.errormessages = 'Fehler beim Zugriff auf die API'
			this.loading = false
			this.responseSeconds = this.elapsedTime(start)
			return
		}

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
			this.tokens = json.tokens || 0
			this.markdown = json.markdown
			if (json.history) {this.history = json.history}
		}
		
		this.loading = false
		this.responseSeconds = this.elapsedTime(start)
		this.autofocus()
	},


	async checkResponseTime(url, timeout) {
		let startTime = Date.now();

		return new Promise((resolve, reject) => {
		let timer = setTimeout(() => {
			reject(new Error('Timeout'))
		}, timeout)

		fetch(url)
			.then(response => {
				clearTimeout(timer)
				resolve(Date.now() - startTime)
			}).catch(error => {
				clearTimeout(timer)
				reject(error)
			});
		});
	},

},


}).mount('#gptInterface')