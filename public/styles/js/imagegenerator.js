
const {createApp} = Vue

createApp({
data() {
	return {
		input: '',
		output: '',
		payload: '',
		resolution: '1536x1024',
		quality: 'medium',
		background: 'auto',
		image: '',
		loading: false,
		uploading: false,
		responseSeconds: 0,
		stopWatchStartTime: null,		
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

	inputChars() {
		if (!this.input) {return 0}
		return this.input.length
	},
},

watch: {
	input(content) {sessionStorage.input = content},
	resolution(value) {localStorage.resolution = value;},
	quality(value) {localStorage.quality = value;},
	background(value) {localStorage.background = value;},
	style(value) {localStorage.style = value;},
},

mounted() {
	this.autofocus()
	this.getUserSettings()
	this.getHistory()
	this.dragDropSetup()
},

methods: {

	autofocus() {
		if (!this.$refs.autofocusElement) {return}
		Vue.nextTick(() => {this.$refs.autofocusElement.focus()})
	},

	getHistory() {
		if (sessionStorage.input) {this.input = sessionStorage.input}
		if (this.action == sessionStorage.action) {
			this.conversationID = sessionStorage.conversationID
			this.fetchConversation()
		}
	},

	resetMetaInfo() {
		this.responseSeconds = 0
		this.errormessages = ''
	},

	startClock() {this.stopWatchStartTime = Date.now()},
	stopClock() {this.responseSeconds = this.elapsedTime()},
	elapsedTime() {
		if (!this.stopWatchStartTime) {return 0}
		return (Date.now() - this.stopWatchStartTime) / 1000
	},

	showError(message) {
			this.errormessages = message
			this.loading = false
			this.stopClock()
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
		if (localStorage.background && localStorage.background != 0) {this.background = localStorage.background}

	},

	async generateImage() {

		this.loading = true
		this.resetMetaInfo()
		this.startClock()

		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape") {this.loading = false}
		});

		let formData = new FormData()
		formData.append('question', this.input)
		formData.append('resolution', this.resolution)
		formData.append('quality', this.quality)
		formData.append('background', this.background)
		formData.append('image', this.image)

		let response = await fetch('/image/generate', {method: "POST", body: formData})
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


	dragDropSetup() {

		const dropArea = document.getElementById('drop-area');
		const fileElem = document.getElementById('fileElem');
		const imageUrl = document.getElementById('imageUrl');

		dropArea.addEventListener('dragover', e => {
			e.preventDefault();
			dropArea.classList.add('dragging');
		});
		dropArea.addEventListener('dragleave', () => dropArea.classList.remove('dragging'));
		dropArea.addEventListener('drop', async e => {
			e.preventDefault();
			dropArea.classList.remove('dragging');
			const files = e.dataTransfer.files;
			if (files && files.length) {
				const dt = new DataTransfer();
				dt.items.add(files[0]);
				fileElem.files = dt.files;
				this.uploadFile(fileElem.files[0])
				return;
			}

			for (const item of e.dataTransfer.items) {
				if (item.kind === 'string' && item.type === 'text/uri-list') {
					item.getAsString(url => this.image = url);
				}
			}

		});

		dropArea.addEventListener('click', () => fileElem.click());
		fileElem.addEventListener('change', e => {
			imageUrl.value = fileElem.files[0]?.name || '';
			this.image = fileElem.files[0]?.name || '';
			this.uploadFile(fileElem.files[0])
		});

	},
	async uploadFile(file) {

		this.uploading = true

		const formData = new FormData();
		formData.append("imagedata", file);

		const response = await fetch("/image/upload", {
			method: "POST",
			body: formData,
		});

		try {
			const json = await response.json();
			this.image = json.payload
		}
		catch (error) {
			this.showError('Upload Error - Bitte nur gängige Bildformate verwenden (Maximal 25mb)');
			this.uploading = false
			return
		}
		
		this.uploading = false		
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

}).mount('#imageGenerator')


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

