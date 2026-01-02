export default Vue.defineComponent({
data() {return {
	history: '',
	historyExpanded: false,
	responseID: null,
}},

props: ['currentResponse'],

watch: {
	historyExpanded(value) {localStorage.historyExpanded = value;},
	currentResponse(value) {
		this.responseID = value;
		sessionStorage.responseID = value
	},	
},

template: `
<details v-if="history" :open="historyExpanded">
	<summary @click.self.prevent="historyExpanded = !historyExpanded">Chatverlauf einblenden</summary>
	<table class="fancy history wide">
		<tr :class="entry.role.toLowerCase()" v-for="entry,index in history"> 
			<td class="ucfirst">{{entry.role}}</td>
			<td><pre @click.prevent="copyToInput" @contextmenu="editHistoryEntry" @blur="updateHistoryEntry(index,$event)">{{filterInstructions(entry.content)}}</pre></td>
			<td class="text-right nowrap">
				<span @click="copyHistoryToClipboard(index)" title="Eintrag kopieren">
				<img class="icon-copy" src="/styles/img/copy-icon.svg">
				</span>&nbsp;<span @click="removeHistoryEntry(index)" title="Eintrag löschen">
				<img class="icon-delete" src="/styles/flundr/img/icon-delete-black.svg">
				</span>
			</td>
		</tr>
	</table>

	<div class="small">
	<button class="button light" type="button" @click="copyHistoryToClipboard">Chatverlauf kopieren</button>
	<button class="button light" type="button" @click="copyHistoryResultToClipboard">nur Antworten kopieren</button>
	<button class="button light" type="button" @click="addHistoryEntry">Inhalt einfügen</button>	
	</div>
</details>
`,

mounted: function() {
	this.getSettings()
	this.fetchHistory()
},

methods: {

	getSettings() {
		if (localStorage.historyExpanded == 'true') {this.historyExpanded = true}
		if (sessionStorage.responseID) {this.responseID = sessionStorage.responseID}
	},

	empty() {
		this.history = null
	},

	async kill() {

		if (!this.responseID) {this.responseID = sessionStorage.responseID}
		if (!this.responseID) {return}

		let url = '/stream/deleteconversion/' + this.responseID
		const response = await fetch(url)
		this.history = null
		this.responseID = null		
		sessionStorage.removeItem('responseID')
	},

	async fetchHistory() {
		if (sessionStorage.length === 0) {return}

		if (!this.responseID) {return} 
		let url = '/stream/session/' + this.responseID

		let response = await fetch(url)
		if (!response.ok) {return}

		let json = await response.json()
		this.history = json
	},

	filterInstructions(node) {
		// Removes OpenAI instrucational Arrays e.g. for Vision Uploads
		if (node && node[0].text) {return node[0].text}
		else {return node}
	},

	copyToInput(event) {
		let element = event.target
		if (element == document.activeElement) {return}
		this.$root.input = element.innerText
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

	editHistoryEntry(event) {
		let element = event.target
		if (!element.isContentEditable) {
			event.preventDefault()
			element.setAttribute('contenteditable', 'true');
			element.focus();
		}		
	},

	async updateHistoryEntry(index, event) {

		let element = event.target
		element.setAttribute('contenteditable', 'false')
		
		let content = element.innerText
		let formData = new FormData()
		formData.append('responseID', this.responseID)
		formData.append('entryID', index)
		formData.append('content', content)
		let response = await fetch('/conversation', {method: "POST", body: formData})
		if (!response.ok) {return}
	},

	async removeHistoryEntry(index) {
		let formData = new FormData()
		formData.append('responseID', this.responseID)
		let response = await fetch('/conversation/pop/' + index, {method: "POST", body: formData})
		if (!response.ok) {return}
		let json = await response.json()
		this.history = json
	},

	async addHistoryEntry() {
		let formData = new FormData()
		formData.append('responseID', this.responseID)
		let response = await fetch('/conversation/new', {method: "POST", body: formData})
		if (!response.ok) {return}
		let json = await response.json()
		this.history = json
	},


}, // End Methods
})


