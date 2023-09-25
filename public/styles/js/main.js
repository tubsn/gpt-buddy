
const {createApp} = Vue

createApp({
data() {
	return {
		action: null,
		description: null, // Helptext in UI
		markdown: false,
		gpt4: false,
		gpt4forced: false,
		input: '',
		output: '',
		history: '', // Conversion History
		historyExpanded: true,
		conversationID: null,
		loading: false,
		responseSeconds: 0,
		tokens: 0,
		chars: 0,
		errormessages: '',
		stopWatchStartTime: null,
		loadbalancer: false, // Activate if using multiple Api Keys or PHP instances
		availableServers: ['//chatapi1.lr-digital.de','//chatapi2.lr-digital.de','//chatapi3.lr-digital.de'],
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
	historyExpanded(value) {localStorage.historyExpanded = value;},
	gpt4(value) {localStorage.gpt4 = value;},
	conversationID(value) {sessionStorage.conversationID = value},
	action(value) {sessionStorage.action = value},
	markdown(value) {sessionStorage.markdown = value},
},

mounted() {
	this.autofocus()
	this.getHistory()
	this.getUserSettings()
	this.autoSelectBox()
	this.setPromptSettings()
	this.preselectActionByHash()
},

methods: {

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

		this.action = selectbox.children[0].value || 'general'
	},

	preselectActionByHash() {

		if (!this.$refs.selectElement) {return}
		let selectbox = this.$refs.selectElement
		
		Vue.nextTick(() => {
			if (location.hash) {
				let hash = decodeURI(location.hash.substr(1))
				let options = [...selectbox].map(el => el.value);
				if (options.includes(hash)) {selectbox.value = hash}
			}
		})

	},


	uploadFile(event) {
		let file = event.target.files[0]

		this.loading = true

		console.log(this.loading)

		let formData = new FormData();
		formData.append('file', file);

		fetch('/import/file', {
			method: 'POST',
			body: formData
		})

		.then(response => response.text())
		.then(data => {
			this.input = data
			this.loading = false
		})
		.catch(error => {
			console.error(error)
			this.loading = false
		});

	},


	resetMetaInfo() {
		this.responseSeconds = 0
		this.errormessages = ''
	},

	showError(message) {
			this.errormessages = message
			this.loading = false
			this.stopClock()
	},

	startClock() {this.stopWatchStartTime = Date.now()},
	stopClock() {this.responseSeconds = this.elapsedTime()},
	elapsedTime() {
		if (!this.stopWatchStartTime) {return 0}
		return (Date.now() - this.stopWatchStartTime) / 1000
	},

	setPromptSettings() {
		if (!this.$refs.selectElement) {return ''}

		let selectbox = this.$refs.selectElement
		let advancedMode = false
		if (selectbox.options[selectbox.selectedIndex]) {
			this.description = selectbox.options[selectbox.selectedIndex].getAttribute('data-description')
			advancedMode = selectbox.options[selectbox.selectedIndex].getAttribute('data-advanced') || advancedMode
			if (selectbox.value != 'default') {
				window.location.hash = selectbox.value;	
			}
			
		}
		
		// Swaps back to 3.5 if the prompt doesnet force gpt4
		if (this.gpt4forced) {
			this.gpt4 = false
			this.gpt4forced = false
		}

		if (advancedMode == true) {
			this.gpt4 = true
			this.gpt4forced = true
		}



	},

	wipeInput() {this.input = ''},

	wipeHistory() {
		this.history = ''
		this.output = ''
		this.conversationID = ''
		this.markdown = false
	},

	getHistory() {
		this.conversationID = sessionStorage.conversationID
		this.action = sessionStorage.action
		this.fetchConversation()
	},

	getUserSettings() {

		if (localStorage.historyExpanded == 'true') {this.historyExpanded = true}
		else {this.historyExpanded = false}

		if (localStorage.gpt4 == 'true') {this.gpt4 = true}
		else {this.gpt4 = false}

	},

	async fetchConversation() {
		if (!this.conversationID) {return}
		if (this.conversationID === 'undefined') {return}
		let response = await fetch('/conversation/' + this.conversationID + '/json')
		if (!response.ok) {return}

		let json = await response.json()
		this.history = json
	},

	copyHistoryToClipboard() {
		let historyData = JSON.parse(JSON.stringify(this.history));
		let history = '';
		historyData.forEach(entry => {
			history = history + `[${entry.role}] ${entry.content}\n\n`
		}) 
		navigator.clipboard.writeText(history);
	},

	copyOutputToClipboard() {
		let element = document.querySelector('.gpt-output .io-textarea')
		let text = element.innerText || element.value
		navigator.clipboard.writeText(text);
	},


	async bestServer() {
		if (!this.loadbalancer) {return ''}
		let servers = this.availableServers
		let availableServer = false

		for (let server of servers) {
			await this.checkResponseTime(server + '/ping', 120)
			.then(responseTime => {availableServer = server})
			.catch(error => {});	
			if (availableServer) {break}
		}

		if (!availableServer) {return servers[0]} // Default Server if none available
		return availableServer
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


	async importArticle(event) {

		this.loading = true
		let value = event.target.value || null;
		if (!value) {this.input = ''; return}

		let id = value.match(/-(\d{8}).html/);
		if (id) {id = id[1]}
		else {id = value}

		let portal = 'LR'
		if (value.includes('moz.de')) {portal = 'MOZ'}
		if (value.includes('swp.de')) {portal = 'SWP'}

		let response = await fetch('/import/article/' + portal + '/' + id)
		if (!response.ok) {this.input = 'URL ungültig oder Artikel nicht gefunden'; return}

		let json = await response.json()
		.catch(error => {this.input = error; return})
		this.input = json.content
		this.loading = false

	},

	async ask(event) {

		this.loading = true
		this.resetMetaInfo()
		this.startClock()
		let element = event.target

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('action', this.action)
		formData.append('conversationID', this.conversationID)

		if (element.dataset.directId) {formData.append('directPromptID', element.dataset.directId)}

		let response = await fetch('/ask', {method: "POST", body: formData})
		if (!response.ok) {this.showError('API Network Connection Error: ' + response.status); return}

		// Own PHP Errors
		response = await response.text()
		let json // no Idea why but it has to be defined first
		try {json = JSON.parse(response);}
		catch (error) {this.showError('PHP Error: ' + response); return}
	
		// PHP Api Handling Errors
		if (json.error) {this.showError(json.error); return}

		this.tokens = json.tokens || 0
		if (json.conversationID) {
			this.conversationID = json.conversationID
			this.stream(this.conversationID)
		}

		this.chars = this.output.length

	},


	async stream(conversionID) {
		this.output = ''
		this.startClock()
		this.markdown = false

		let apiurl = await this.bestServer()
		//console.log('Asking on :' + apiurl)

		let gpt4path = ''
		if (this.gpt4) {gpt4path = 'force4/'}

		let eventSource = new EventSource(apiurl + '/stream/' + gpt4path + conversionID);

		eventSource.addEventListener('message', (event) => {
			this.output += JSON.parse(event.data)
		})

		eventSource.addEventListener('stop', (event) => {
			this.stopStream(eventSource)
			this.fetchConversation()
		})

		eventSource.addEventListener("error", (event) => {
			this.errormessages = 'API Zugriff: SSE Network Error'
			this.stopStream(eventSource)
		});

		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				this.stopStream(eventSource)
				this.removeLastHistoryEntry()
			}
		});

	},

	stopStream(stream) {
		stream.close()

		//if (this.isOutputUrl() == true) {window.open(this.output, '_blank')}

		this.markdown = true		
		marked.use({breaks: true, mangle:false, headerIds: false,});
		this.output = marked.parse(this.output);
		this.chars = this.output.length

		Vue.nextTick(() => {hljs.highlightAll();})

		this.loading = false
		this.stopClock()
		this.autofocus()
	},

	isOutputUrl() {
		try { return Boolean(new URL(this.output)) }
		catch(e){ return false }
	},

	async removeLastHistoryEntry() {

		if (!this.conversationID) {return}
		let response = await fetch('/conversation/' + this.conversationID + '/pop')
		if (!response.ok) {return}

		let json = await response.json()
		this.history = json

	},

},

}).mount('#gptInterface')


// Darkmode
function toggleDarkmode() {

	let cssLink = document.querySelector('#dark-mode-css-link')
	
	if (cssLink) {
		cssLink.remove()
		document.cookie = 'darkmode = 0; path=/; expires=Fri, 31 Dec 1970 23:59:59 GMT'
		return
	}

	let link = document.createElement('link')
	link.id = 'dark-mode-css-link'
	link.rel = 'stylesheet'
	link.type = 'text/css'
	link.href = '/styles/css/darkmode.css'
	document.getElementsByTagName("head")[0].appendChild(link)
	document.cookie = 'darkmode = 1;path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT'
}

document.addEventListener("DOMContentLoaded", function(){
	let colorModeIcon = document.querySelector('.color-mode')
	if (colorModeIcon) {colorModeIcon.addEventListener('click', event => {toggleDarkmode()})}
});

