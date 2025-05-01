
const {createApp} = Vue

createApp({
data() {
	return {
		action: null,
		description: null, // Helptext in UI
		markdown: false,
		portal: null,
		gpt4: false,
		gpt4forced: false,
		model: '',
		userSelectedModel: '',
		modelDescription: '',
		input: '',
		output: '',
		payload: '',
		history: '', // Conversion History
		historyExpanded: true,
		conversationID: null,
		resolution: '1536x1024',
		quality: 'medium',
		background: 'auto',
		image: '',
		loading: false,
		eventSource: null,
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

	inputChars() {
		if (!this.input) {return 0}
		return this.input.length
	},

	advancedModel() {
		if (this.model.includes('o3') || this.model.includes('o4')) {return true;}
	}
},

watch: {
	history(content) {sessionStorage.history = JSON.stringify(content)},
	input(content) {sessionStorage.input = content},
	historyExpanded(value) {localStorage.historyExpanded = value;},
	gpt4(value) {localStorage.gpt4 = value;},
	resolution(value) {localStorage.resolution = value;},
	quality(value) {localStorage.quality = value;},
	style(value) {localStorage.style = value;},
	conversationID(value) {sessionStorage.conversationID = value},
	action(value) {sessionStorage.action = value},
	markdown(value) {sessionStorage.markdown = value},
	model(value) {localStorage.model = value},
	userSelectedModel(value) {localStorage.userSelectedModel = value},
},

mounted() {
	this.portalConfig()
	this.autofocus()
	this.getUserSettings()
	this.autoSelectBox()
	this.getHistory()
	this.setPromptSettings()
	this.preselectModelBox()
	this.preselectActionByHash()
	this.initCopyPaste()
},

methods: {

	getPortal() {return this.$el.parentElement.dataset.portal},

	portalConfig() {
		this.portal = this.getPortal()

		if (this.portal == 'MOZ') {
			this.availableServers = ['//chatapi-moz-1.lr-digital.de','//chatapi-moz-2.lr-digital.de','//chatapi-moz-3.lr-digital.de']
		}
		
		if (this.portal == 'SWP') {
			this.availableServers = ['//chatapi-swp-1.lr-digital.de','//chatapi-swp-2.lr-digital.de','//chatapi-swp-3.lr-digital.de']
		}

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

		this.action = selectbox.children[0].value || 'general'
	},

	preselectModelBox() {
		if (!this.$refs.modelpicker) {return}
		let selectbox = this.$refs.modelpicker
		let options = [...selectbox].map(el => el.value);
		if (options.includes(this.model)) {
			selectbox.value = this.model
			this.setModelSettings(selectbox.selectedOptions[0])
			return
		}
		this.setModelSettings(selectbox[0])
	},

	setModelSettings(options) {

		if (!options) {
			let selectbox = this.$refs.modelpicker
			this.setModelSettings(selectbox[0])
			return
		}
		let selectedOption = options.target?.selectedOptions[0] ?? options
		this.modelDescription = selectedOption.dataset.description
		this.model = selectedOption.value
	},


	detectBestValidModel(modelname) {
		// This is important, to select a Model based on prompt settings
		// and if no model is defined swap back to the default model
		if (modelname) {
			let modelpicker = this.$refs.modelpicker
			let options = [...modelpicker].map(el => el.value)
		
			if (options.includes(modelname)) {
				modelpicker.value = modelname
				this.setModelSettings(modelpicker.selectedOptions[0])
			}
		} else {
			this.setModelSettings(this.$refs.modelpicker[this.userSelectedModel ?? 0])
		}

	},


	setUserSelectedModel(event) {
		let selectBox = event.target
		this.userSelectedModel = selectBox.selectedIndex
	},

	preselectActionByHash() {

		if (!this.$refs.selectElement) {return}
		let selectbox = this.$refs.selectElement
		
		Vue.nextTick(() => {
			if (location.hash) {
				let hash = decodeURI(location.hash.substr(1))
				let options = [...selectbox].map(el => el.value);
				if (options.includes(hash)) {
					this.action = hash
					selectbox.value = hash
					this.description = selectbox.options[selectbox.selectedIndex].getAttribute('data-description')					
					this.detectBestValidModel(selectbox.options[selectbox.selectedIndex].getAttribute('data-model'))
				}
			}

		})

	},

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
					this.gpt4 = true,
					this.loading = false
					return
				}
			} catch (error) {
				console.log(error)
			}
			
			this.input = data
			this.loading = false
		})
		.catch(error => {
			console.error(error)
			this.loading = false
		});

	},

	filterInstructions(node) {
		// Removes OpenAI instrucational Arrays e.g. for Vision Uploads
		if (node[0].text) {return node[0].text}
		else {return node}
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
		this.description = selectbox.options[0].getAttribute('data-description')

		let selectedOption = selectbox.options[selectbox.selectedIndex]
		if (selectedOption) {

			// shall only happen when something is actively selected
			if (selectbox.value != 'default') {
				window.location.hash = selectbox.value
			} else {
				window.location.hash = ''
				history.replaceState(null, null, window.location.pathname)
			}

			this.description = selectedOption.getAttribute('data-description')
			this.detectBestValidModel(selectedOption.getAttribute('data-model'))

		}
		
		if (sessionStorage.action != 'undefined') {
			if (sessionStorage.action != this.action) {
				this.wipeHistory() 
			}
		}

	},

	wipeInput() {
		this.input = ''
		this.payload = ''		
	},

	wipeHistory() {
		this.history = ''
		this.output = ''
		this.conversationID = ''
		this.markdown = false
	},

	getHistory() {
		if (sessionStorage.input) {this.input = sessionStorage.input}
		if (this.action == sessionStorage.action) {
			this.conversationID = sessionStorage.conversationID
			this.fetchConversation()
		}
	},

	getUserSettings() {

		if (localStorage.historyExpanded == 'true') {this.historyExpanded = true}
		else {this.historyExpanded = false}

		if (localStorage.gpt4 == 'true') {this.gpt4 = true}
		else {this.gpt4 = false}

		if (localStorage.model) {this.model = localStorage.model}
		if (localStorage.userSelectedModel) {this.userSelectedModel = localStorage.userSelectedModel}
		if (localStorage.resolution && localStorage.resolution != 0) {this.resolution = localStorage.resolution}
		if (localStorage.quality && localStorage.quality != 0) {this.quality = localStorage.quality}
		if (localStorage.style && localStorage.style != 0) {this.style = localStorage.style}

	},

	createTTS(event) {

		const container = event.target.parentElement.parentElement
		let text = ''

		if (container.classList.contains('user-input')) {text = this.input}
		else {text = this.output}

		// HTML has to be striped
		const HTMLcleaner = document.createElement('div');
		HTMLcleaner.innerHTML = text;
		text = HTMLcleaner.textContent || HTMLcleaner.innerText || '';

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = '/tts';

		const data = { input: text};
		for (const key in data) {
			const input = document.createElement('input');
			input.type = 'hidden'; input.name = key; input.value = data[key];
			form.appendChild(input);
		}

		document.body.appendChild(form);
		form.submit();

	},

	async fetchConversation() {
		if (!this.conversationID) {return}
		if (this.conversationID === 'undefined') {return}
		let response = await fetch('/conversation/' + this.conversationID + '/json')
		if (!response.ok) {return}

		let json = await response.json()
		this.history = json
	},

	copyHistoryToClipboard(index) {
		let historyData = JSON.parse(JSON.stringify(this.history));

		if (!index.target) {
			navigator.clipboard.writeText(historyData[index].content)
			return	
		}

		let history = '';
		historyData.forEach(entry => {
			history = history + `[${entry.role}] ${entry.content}\n\n`
		}) 
		navigator.clipboard.writeText(history);
	},

	copyHistoryResultToClipboard() {
		let historyData = JSON.parse(JSON.stringify(this.history));

		let history = '';
		historyData.forEach(entry => {
			if (entry.role == 'assistant') {
				history = history + `${entry.content}\n\n`
			}
		}) 
		navigator.clipboard.writeText(history);
	},



	copyOutputToClipboard() {
		let element = document.querySelector('.gpt-output .io-textarea')
		let text = element.innerText || element.value
		navigator.clipboard.writeText(text);
	},

	copyToInput(event) {
		this.input = event.target.innerText
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
		if (!value) {this.input = ''; this.loading = false; return}

		let formData = new FormData()
		formData.append('url', value)

		let response = await fetch('/import/article', {method: "POST", body: formData})
		if (!response.ok) {this.input = 'URL ungültig oder Artikel nicht gefunden'; this.loading = false; return}

		let json = await response.json()
		.catch(error => {this.input = error; this.loading = false; return})
		this.input = json.content || ''
		this.loading = false

	},

	async ask(event) {

		this.loading = true
		this.resetMetaInfo()
		this.startClock()
		let element = event.target

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('payload', this.payload)
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

		//this.payload = ''

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
		let modelpath = encodeURI(this.model) + '/'

		this.eventSource = new EventSource(apiurl + '/stream/' + modelpath + conversionID);

		this.eventSource.addEventListener('message', (event) => {
			this.output += JSON.parse(event.data)
		})

		this.eventSource.addEventListener('stop', (event) => {
			this.stopStream()
			this.fetchConversation()
		})

		this.eventSource.addEventListener("error", (event) => {
			this.errormessages = event.data
			this.stopStream()
		});

		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {
				this.stopStream()
				this.removeLastHistoryEntry()
			}
		});

	},

	async generateImage() {

		this.loading = true
		this.resetMetaInfo()
		this.startClock()

		let apiurl = await this.bestServer()

		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {this.loading = false}
		});

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('resolution', this.resolution)
		formData.append('quality', this.quality)
		formData.append('background', this.background)
		formData.append('image', this.image)

		let response = await fetch(apiurl + '/image/generate', {method: "POST", body: formData})
		if (!response.ok) {this.showError('API Network Connection Error: ' + response.status); return}

		// Own PHP Errors
		response = await response.text()
		let json // no Idea why but it has to be defined first
		try {json = JSON.parse(response);}
		catch (error) {this.showError('PHP Error: ' + response); return}
	
		// PHP Api Handling Errors
		if (json.error) {this.showError(json.error); return}

		this.output = json
		this.image = json
		this.loading = false

	},

	stopStream() {
		this.eventSource.close()

		this.markdown = true		
		marked.use({breaks: true, mangle:false, headerIds: false,});
		this.chars = this.output.length

		this.output = marked.parse(this.output);
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

	async removeHistoryEntry(index) {

		if (!this.conversationID) {return}
		let response = await fetch('/conversation/' + this.conversationID + '/pop/' + index)
		if (!response.ok) {return}

		let json = await response.json()

		this.history = json
	},

	initCopyPaste() {
		let _this = this
		document.addEventListener('DOMContentLoaded', () => {
			document.onpaste = function(event){
				const content = _this.getContentFromPasteEvent(event);
				if (typeof content === 'object') {_this.copyPasteUpload(content);}
			}
		});
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




}, // End of Methods

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

