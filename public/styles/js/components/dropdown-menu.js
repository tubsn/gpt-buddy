export default Vue.defineComponent({
data() {return {
	input: '',
	output: '',
	anchor: true,
	open: false,
	clickTarget: null,
	userMadeSelection: '',
}},

template: `<nav class="dropdown-menu" :class="{ open: open }" @click="doStuff">
	<slot></slot>
</nav>`,

props: ['menuElement'],

mounted: function() {
	this.bindEventListeners()
},

methods: {

	bindEventListeners() {
		const textarea = document.querySelector(`.${this.menuElement}`);
		if (textarea) {
				textarea.addEventListener('contextmenu', (event) => {
				this.userMadeSelection = window.getSelection().toString();
				event.preventDefault();
				this.showMenu(event);
				this.clickTarget = event.target
			});
		}

		document.addEventListener('mousedown', this.removeMenu.bind(this));
		document.addEventListener('keydown', (event) => {if (event.key === "Escape") this.removeMenu(event)})
	},

	doStuff(event) {
		let action = event.target.getAttribute('action')
		if (action && typeof this[action] === 'function') {
			this[action](event);
		}
		this.open = false
	},

	showMenu(event) {
		let x = event.clientX
		let y = event.clientY
		this.$el.style.left = x + 'px'
		this.$el.style.top = y + 'px'
		this.open = true
	},

	removeMenu(event) {
		if (event.target.contains(this.$el)) {return}
		if (event.target.parentElement.contains(this.$el)) {return}
		this.open = false
	},

	link() {
		let element = this.clickTarget
		if (element.tagName === 'A') {
			window.open(element.href);
		}
	},

	copy() {
		if (this.menuElement.includes('output')) {
			this.$root.copyOutputToClipboard(this.userMadeSelection)
			return
		}
		this.$root.copyInputToClipboard(this.userMadeSelection)
	},

	async paste() {
		try {
			const text = await navigator.clipboard.readText();
			const textarea = document.querySelector('.io-textarea')
			const value = textarea.value;

			// Pastes text inbetween current values
			if (value) {
				const start = textarea.selectionStart;
				const end = textarea.selectionEnd;
				this.$root.input = value.slice(0, start) + text + value.slice(end);
			}
			else {
				this.$root.input = text;
			}

		} catch (err) {
			alert('Kein Zugriff auf Zwischenablage möglich. Bitte nutzen sie die STRG+V funktion zum einfügen');
		}
	},

	delete() {
		if (this.menuElement.includes('output')) {
			this.$root.removeHistory()
			return
		}		
		this.$root.removeInput()
	},

	redo() {this.$root.redoLastStep()},

	// This is Ai Coded handle with care :S
	async replaceBNNUrls() {

		let content = this.$root.input
		
		const findBnnUrls = (text) => {
			const regex = /\b(?:https?:\/\/)?(?:www\.)?bnn\.de(?:\/[^\s<>"'()]+)?/gi;
			const ranges = [];
			text.replace(regex, (m, ...args) => {
				const start = args[args.length - 2];
				ranges.push({ url: m, start, end: start + m.length });
				return m;
			});
			return ranges;
		};

		const urlPositions = findBnnUrls(content)

		const applyReplacements = (text, replacements) => {
			replacements.sort((a, b) => b.start - a.start).forEach(r => {
				text = text.slice(0, r.start) + r.value + text.slice(r.end);
			});
			return text;
		};

		const values = await Promise.all(
			urlPositions.map(async r => {
				const urlContent = await this.importBNNArticle(r.url);
				const article = `-----\n${urlContent}\n-----`
				return article;
			})
		);

		const replacements = urlPositions.map((r, i) => ({
			start: r.start,
			end: r.end,
			value: values[i]
		}));

		this.$root.input = applyReplacements(content, replacements);
		this.$root.loading = false

	},

	async importBNNArticle(url) {

		this.$root.loading = true

		let formData = new FormData()
		formData.append('url', url)

		let response = await fetch('/import/article', {method: "POST", body: formData})
		if (!response.ok) {this.input = 'URL ungültig oder Artikel nicht gefunden'; this.$root.loading = false; return}

		let json = await response.json()
		.catch(error => {
			this.$root.errormessages = error
			this.$root.loading = false
		})

		return json.content || ''
	},


	async toPipedreamApi(event) {

		let accessToken = event.target.getAttribute('data-acesstoken') || null

		let history = this.$root.getHistory()
		history = JSON.stringify(history)

		const output = document.querySelector('.io-output-div')
		let rawOutput = null

		if (output) {rawOutput = output.innerHTML}
		else {rawOutput = this.$root.output;}

		const doc = new DOMParser().parseFromString(rawOutput, 'text/html');
		const plainText = doc.body.textContent || '';

		let promptID = this.$root.promptID
		if (promptID == 'default') {promptID = null}

		const formData = new FormData();
		formData.append('promptid', promptID);
		formData.append('content', rawOutput);
		formData.append('plaintext', plainText);
		formData.append('history', history);

		const headers = {};
		if (accessToken) {headers.Authorization = `Bearer ${accessToken}`;}

		const response = await fetch('https://eoszb5zit59t7z1.m.pipedream.net', {method: 'POST', headers, body: formData})
		console.log(response.text())
	},


}, // End Methods
})