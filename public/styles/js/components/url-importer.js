export default Vue.defineComponent({
data() {return {
	content: '',
	loading: false
}},

emits: ['change'],

template: `
<label><span class="hide-mobile">Importieren:</span>
	<div v-if="loading" class="loadIndicator" style="top:0px; width:14px; height:8px; padding:0; margin-left:0.2em"><div></div><div></div><div></div></div>
	<input type="text" @input="importArticle" :class="{ disabled: loading }" placeholder="Artikel URL eintragen">
</label>
`,

mounted: function() {},

methods: {

	async importArticle(event) {

		let value = event.target.value || null;
		if (!value || value.length < 5) {return}

		this.loading = true

		let formData = new FormData()
		formData.append('url', value)

		let response = await fetch('/import/article', {method: "POST", body: formData})
		if (!response.ok) {
			this.content = 'URL ungültig oder Artikel nicht gefunden'; 
			this.loading = false;
			this.$emit('change', this.content)			
			return}

		let json = await response.json()
		.catch(error => {this.content = error; this.loading = false; return})
		this.content = json.content || ''
		this.loading = false

		this.$emit('change', this.content)
	},

}, // End Methods
})