import ModelSelector from "./components/model-selector.js";
import PromptSelector from "./components/prompt-selector.js";
import UrlImporter from "./components/url-importer.js";
import RagSettings from "./components/rag-settings.js";
import FileUpload from "./components/file-upload.js";
import TtsButton from "./components/tts-button.js";
import SearchToggleButton from "./components/search-toggle.js";
import ChatHistory from "./components/chat-history.js";
import Dropdown from "./components/dropdown-menu.js";
import DebugModal from "./components/debug-modal.js";

const { createApp } = Vue

const chatApp = createApp({
data() {
	return {
		input : null,
		output : null,
		promptID: null,
		responseID: null,
		directPromptID: null,
		infotext: null,
		eventSource: null,		
		loading : false,
		reasoning: false,
		model : null,
		modelmode : null,
		modelarguments : null,
		stopWatchStartTime: null,
		responsetime: 0,
		usage: [],
		errormessages: null,
		sseFinalOutput: [],
		sseProgress: [],
	}
},

components: {
	"model-selector": ModelSelector,
	"prompt-selector": PromptSelector,
	"url-importer": UrlImporter,
	"rag-settings": RagSettings,
	"file-upload": FileUpload,
	"tts-button": TtsButton,
	"search-toggle": SearchToggleButton,
	"chat-history": ChatHistory,
	"dropdown": Dropdown,
	"debug-modal": DebugModal,
},

computed: {
	chars() {
		if (!this.output) {return 0}
		return this.output.length
	},
},

watch: {
	input(content) {sessionStorage.input = content},
	output(content) {sessionStorage.output = content},
	model() {this.reasoning = this.$refs.model?.reasoning || false},
},

mounted() {
	this.loadInput()
},

methods: {

	send() {
		this.clearLogs()
		this.createStreamRequest()
	},

	clearLogs() {
		this.errormessages = null
		if (this.$refs.debug) {
			this.$refs.debug.sseFinalOutput = []
			this.$refs.debug.sseProgress = []
		}
	},

	loadInput() {
		if (sessionStorage.input) {this.input = sessionStorage.input}
		if (sessionStorage.output) {this.output = sessionStorage.output}
	},

	removeInput() {
		this.input = ''
		this.$refs.payload.payload = ''
		this.errormessages = ''
	},
	
	async removeHistory() {
		this.$refs.history.kill()
		this.clearLogs()
		this.responsetime = 0
		this.output = ''
		sessionStorage.output = ''
	},

	getHistory() {return this.$refs.history.history},

	async redoLastStep() {
		const regenerate = true
		this.createStreamRequest(regenerate)
	},

	async copyOutputToClipboard(userSelection = null) {
		if (typeof userSelection === 'string' && userSelection) {
			await navigator.clipboard.writeText(userSelection);
			return
		}
		let element = document.querySelector('.gpt-output .io-textarea')
		let text = element.innerText || element.value || ''
		navigator.clipboard.writeText(text);
	},

	async copyInputToClipboard(userSelection = null) {
		if (typeof userSelection === 'string' && userSelection) {
			await navigator.clipboard.writeText(userSelection);
			return
		}		
		let element = document.querySelector('.user-input .io-textarea')
		let text = element.innerText || element.value || ''
		navigator.clipboard.writeText(text);
	},

	createUserEmail(event) {
		const email = event.currentTarget.dataset.usermail || '';
		const output = document.querySelector('.gpt-output .io-textarea')
		const input = document.querySelector('.user-input .io-textarea')

		let text = output.innerText || output.value || ''
		if (text == '') {text = input.innerText || input.value || ''}

		const subject = 'AiBuddy'

		const mailtoUrl =
		"mailto:" +	encodeURIComponent(email) +
		"?subject=" + encodeURIComponent(subject) +
		"&body=" + encodeURIComponent(text);
		window.location.href = mailtoUrl;
	},

	directPrompt(event) {

		let element = event?.target || null
		let directPromptID = element.dataset.directId || null
		if (!directPromptID) {return}

		this.directPromptID = directPromptID
		this.removeHistory()

		let backup = this.input
		this.input = null
		this.createStreamRequest()
		this.directPromptID = null
		this.input = backup

	},

	async createStreamRequest(regenerate = false) {

		const requestURL = '/stream'
		let requestData = {
			input : this.input,
			responseID : this.responseID,
			promptID : this.promptID,
			model : this.$refs.model.model,
			search : this.$refs.searchtoggle.active,
			category : this.$refs.prompts.category,
			payload : this.$refs.payload?.payload || null,
			parameters: this.$refs.parameters?.accessData() || null,
			regenerate : regenerate,
		}

		if (this.directPromptID) {
			requestData.promptID = this.directPromptID
		}

		const response = await fetch(requestURL, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(requestData)
		});

		if (!response.ok) throw new Error('Kanal-Erstellung fehlgeschlagen');
		
		const data = await response.json()
		const streamURL = data.url

		this.stream(streamURL)
	},


	async stream(url) {

		this.startClock()
		this.errormessages = ''
		this.output = ''
		this.loading = true

		if (!url) {url = '/stream'}

		this.eventSource = new EventSource(url, { withCredentials: true });
		this.eventSource.addEventListener('message', (event) => {this.handleStream(JSON.parse(event.data))})
		this.eventSource.addEventListener('done', (event) => {this.stopStream()})
		this.eventSource.addEventListener('stop', (event) => {this.stopStream()})
		this.eventSource.addEventListener("error", (event) => {

			//if (event.data.type == 'done') {this.stopStream();}
			if (event.data) {
				this.errormessages = event.data
				this.output += event.data
			}
			else {this.errormessages = '404 - Connection Error while Streaming (Browser Console for more)'}
			this.stopStream()
		});

		document.removeEventListener("keydown", this.stopStreamOnEscape);
		document.addEventListener("keydown", this.stopStreamOnEscape);

	},

	handleStream(chunk) {
		switch (chunk.type) {
			case 'delta': {
				this.modelmode = ''
				this.output += chunk.content
				break
			}

			case 'error': {
				this.errormessages = chunk.text || chunk.message
				console.error(chunk)
				this.output = chunk.text
				break
			}

			case 'progress': {
				if (this.$refs.debug) {this.$refs.debug.sseProgress.push(chunk.content)}
				break
			}

			case 'tool_call': {
				if (chunk.content == 'start') {this.modelmode = `verwende Tool - ${chunk.tool_name}`}
				if (chunk.arguments && this.$refs.debug) {this.$refs.debug.modelarguments += chunk.arguments}
				break
			}

			case 'reasoning': {
				if (chunk.content == 'start') {this.modelmode = 'Reasoning'}
				if (chunk.content == 'done') {this.modelmode = ''}
				break
			}

			case 'completed': {
				if (this.$refs.debug) {this.$refs.debug.sseFinalOutput.push(chunk.content)}
				this.responseID = chunk.content.id 
				this.usage = chunk.content.usage
				break
			}

			case 'final': {
				this.stopStream()
				break
			}

		}
	},

	stopStreamOnEscape(event) {
		if (event.key === "Escape") {this.stopStream()}
	},

	stopStream() {

		marked.use({breaks: true, mangle:false, headerIds: false,});
		this.output = marked.parse(this.output)

		Vue.nextTick(() => {
			hljs.highlightAll();
			const outputDiv = document.querySelector(".output")
			if (outputDiv) {
				outputDiv.contentEditable = 'true'
			}
		})

		this.eventSource.close()
		this.stopClock()
		this.modelmode = ''
		if (!this.isMobileDevice()) {this.autofocus()}
		this.$refs.history.fetchHistory()
		this.loading = false

		sessionStorage.lastPrompt = this.promptID

	},

	startClock() {this.stopWatchStartTime = Date.now(); this.responsetime = 0},
	stopClock() {this.responsetime = this.elapsedTime()},
	
	elapsedTime() {
		if (!this.stopWatchStartTime) {return 0}
		return (Date.now() - this.stopWatchStartTime) / 1000
	},

	autofocus() {
		if (!this.$refs.autofocusElement) {return}
		Vue.nextTick(() => {this.$refs.autofocusElement.focus()})
	},

	isMobileDevice() {
		const isTouchDevice = window.matchMedia("(pointer: coarse)").matches || navigator.maxTouchPoints > 0;
		const isSmallScreen = window.matchMedia("(max-width: 768px)").matches;
		const isMobileLike = isTouchDevice && isSmallScreen;

		return isMobileLike;
	}

}, // End of Methods


}).mount('#chatapp')


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
