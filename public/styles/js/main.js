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
		shouldAutoScroll: false,
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
		if (this.loading) {return}
		this.loading = true
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
		if (this.loading) {return}
		this.loading = true
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
		if (this.loading) {return}
		this.loading = true

		let element = event?.target || null
		let directPromptID = element.dataset.directId || null
		if (!directPromptID) {
			this.loading = false
			return
		}

		this.directPromptID = directPromptID
		this.removeHistory()

		let backup = this.input
		this.input = null
		this.createStreamRequest()
		this.directPromptID = null
		this.input = backup

	},

	failRequest(message) {
		this.errormessages = message
		this.output = message
		if (this.eventSource) {
			this.eventSource.close()
			this.eventSource = null
		}
		this.loading = false
		this.modelmode = ''
		this.stopClock()
		document.removeEventListener("keydown", this.stopStreamOnEscape)
	},

	attachOutputScrollListener() {
		Vue.nextTick(() => {
			let outputDiv = this.$refs.outputTextarea
			if (!outputDiv || outputDiv.dataset.scrollListenerAttached === 'true') {return}

			let lastScrollTop = outputDiv.scrollTop

			outputDiv.addEventListener('scroll', () => {
				if (this.isProgrammaticScroll) {return}

				const currentScrollTop = outputDiv.scrollTop
				const scrolledUp = currentScrollTop < lastScrollTop

				if (scrolledUp) {
					this.shouldAutoScroll = false
				} else {
					this.shouldAutoScroll = this.isNearBottom(outputDiv)
				}

				lastScrollTop = currentScrollTop
			})

			outputDiv.dataset.scrollListenerAttached = 'true'
		})
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

		try {
			const response = await fetch(requestURL, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify(requestData)
			});

			if (!response.ok) {throw new Error('Kanal-Erstellung fehlgeschlagen')}

			const data = await response.json()
			const streamURL = data.url

			this.stream(streamURL)
		}
		catch (error) {
			this.failRequest(error.message || 'Verbindungsfehler beim Starten des Streams')
		}
	},


	async stream(url) {

		if (!url) {url = '/stream'}

		this.startClock()
		this.errormessages = ''
		this.output = ''
		this.modelmode = ''

		// A stale SSE connection must never keep running when a new explicit start happens.
		if (this.eventSource) {
			this.eventSource.close()
			this.eventSource = null
		}

		// Enable Autoscrolling when user actively scrolls to the bottom
		this.attachOutputScrollListener()

		this.eventSource = new EventSource(url, { withCredentials: true });
		this.eventSource.addEventListener('message', (event) => {
			try {
				this.handleStream(JSON.parse(event.data))
			} catch (error) {
				this.failRequest('Ungültige Stream-Antwort empfangen')
			}
		})
		this.eventSource.addEventListener('done', () => {this.stopStream()})
		this.eventSource.addEventListener('stop', () => {this.stopStream()})
		this.eventSource.addEventListener("error", () => {
			if (!this.errormessages && !this.output) {
				this.errormessages = '404 - Connection Error while Streaming (Browser Console for more)'
				this.output = this.errormessages
			}
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

		this.scrollOutputDown()
	},

	stopStreamOnEscape(event) {
		if (event.key === "Escape") {this.stopStream()}
	},


	isNearBottom(element, threshold = 80) {
		return element.scrollTop + element.clientHeight >= element.scrollHeight - threshold;
	},

	scrollOutputDown(scrollBehavior = 'smooth') {
		if (!this.shouldAutoScroll) {return;}
		const outputDiv = this.$refs.outputTextarea
		if (!outputDiv) {return}
		outputDiv.scrollTo({
			top: outputDiv.scrollHeight,
			behavior: scrollBehavior
		});
	},

	stopStream() {

		let userScrolledDown = false
		if (this.$refs.outputTextarea) {
			userScrolledDown = this.isNearBottom(this.$refs.outputTextarea)
		}

		if (this.output) {
			marked.use({breaks: true, mangle:false, headerIds: false,});
			this.output = marked.parse(this.output)
		}

		Vue.nextTick(() => {
			hljs.highlightAll();
			const outputDiv = document.querySelector(".io-output-div")
			if (outputDiv) {
				outputDiv.contentEditable = 'true'
				if (userScrolledDown) {outputDiv.scrollTop = outputDiv.scrollHeight}
			}
			this.shouldAutoScroll = false
		})

		if (this.eventSource) {
			this.eventSource.close()
			this.eventSource = null
		}
		this.stopClock()
		this.modelmode = ''
		if (!this.isMobileDevice()) {this.autofocus()}
		this.$refs.history.fetchHistory()
		this.loading = false

		document.removeEventListener("keydown", this.stopStreamOnEscape)

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
