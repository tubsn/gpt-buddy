export default Vue.defineComponent({
data() {return {
	promptID: '',
	description: '',
	model: ''
}},

props: ['category', 'preselect', 'hideDefault', 'promptless', 'editable'],
emits: ['change', 'forcemodel'],

computed: {},

watch: {
	promptID(value, oldValue) {
		this.$emit('change', value)
	
		if (oldValue != '') {
			this.setWindowState()
			this.$root.removeHistory()
		}
	
		this.setMetaInfos()
		this.$emit('forcemodel', this.model)
	}
},

template: `
<label><span class="hide-mobile">Prompt auswählen:
<a v-if="editable && !isNaN(promptID)" :href="'/settings/'+promptID">(Prompt editieren)</a></span>
<select v-model="promptID" ref="selectBox">
	<option v-if="!hideDefault" value="default">Standard Chat</option>
	<option v-if="promptless" value="unbiased">ChatGPT ohne Prompt</option>
	<slot></slot>
</select>
</label>
`,

mounted: function() {
	this.selectDefault()
	this.preselectByHash()
	this.removeHistoryOnPromptChange()
},

methods: {

	selectDefault() {
		if (!this.promptID) {
			const selectElement = this.$refs.selectBox
			if (selectElement && selectElement.options.length > 0) {
				this.promptID = selectElement.options[0].value
				this.$root.promptID = this.promptID
			}
		}
	},

	
	setMetaInfos() {
		const selectElement = this.$refs.selectBox 
		const matchedOption = Array.from(selectElement.options).find((optionElement) => optionElement.value === this.promptID)
		this.description = matchedOption ? matchedOption.getAttribute('data-description') || '' : ''
		this.$root.infotext = this.description
		this.model = matchedOption ? matchedOption.getAttribute('data-model') || '' : ''
	},
	
	setWindowState() {
		if (this.promptID != 'default') {
			window.location.hash = this.promptID
		} else {
			window.location.hash = ''
			history.replaceState(null, null, window.location.pathname)			
		}
	},

	preselectByHash() {
		if (!location.hash) {return}
		let selectbox = this.$refs.selectBox
		let hash = decodeURI(location.hash.substr(1))
		let options = [...selectbox].map(el => el.value);
		if (options.includes(hash)) {this.promptID = hash}
	},

	removeHistoryOnPromptChange() {
		if (!sessionStorage.lastPrompt) {return}
		if (sessionStorage.lastPrompt != this.promptID) {
			this.$root.removeHistory()
			sessionStorage.removeItem('lastPrompt')
		}
	},

}, // End Methods
})