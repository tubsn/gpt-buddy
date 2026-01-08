export default Vue.defineComponent({
data() {return {
	model: '',
	userSelectedModel: '',
	description: 'Standart Model',
	reasoning: false
}},

props: ['forcedmodel'],

watch: {
	model(value, oldValue) {
		localStorage.model = value
		this.setMetaInfos()
	},

	forcedmodel(value) {
		this.detectBestValidModel(value)
	},
},

template: `
<label class="picker-ui-wrapper"><span class="hide-mobile">KI-Model:</span>
<select ref="selectBox" v-model="model">
	<slot></slot>
</select>
</label>

<p class="model-description hide-mobile">{{ description }}</p>
`,

mounted: function() {
	this.loadSettings()
	this.detectBestValidModel(this.forcedmodel)
},

methods: {

	loadSettings() {
		if (localStorage.model) {this.model = localStorage.model}

		this.$refs.selectBox.addEventListener("change", (event) => {
			this.userSelectedModel = event.target.value
		})
	},

	setMetaInfos() {
		if (this.model.toLowerCase().includes('reason')) {this.reasoning = true
		} else {this.reasoning = false}
		this.$root.reasoning = this.reasoning

		const matchedOption = Array.from(this.$refs.selectBox.options).find((optionElement) => optionElement.value === this.model)
		this.description = matchedOption ? matchedOption.getAttribute('data-description') || '' : ''
	},

	selectDefault() {
		if (!this.model) {
			const selectElement = this.$refs.selectBox
			if (selectElement && selectElement.options.length > 0) {
				this.model = selectElement.options[0].value
			}
		}
	},

	switchToUserPreference() {
		if (!this.userSelectedModel) {this.selectDefault()}
		else {this.model = this.userSelectedModel}
	},

	detectBestValidModel(modelname) {
		if (!modelname) {
			this.switchToUserPreference()
			return
		}

		let options = [...this.$refs.selectBox].map(el => el.value);
		if (options.includes(modelname)) {
			this.userSelectedModel = this.model
			this.model = modelname
			return
		}

		this.switchToUserPreference()
	},

}, // End Methods
})