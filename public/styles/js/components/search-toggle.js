export default Vue.defineComponent({
data() {return {
	active: false,
}},

template: `<div class="float-button websearch-button" @click="active = !active" title="Websearch aktivieren">
<span class="visible"><input type="checkbox" v-model="active">Websuche</span></div>
`,

watch: {
	active(value) {
		localStorage.setItem("searchtool", value ? "true" : "false")
	},
},

mounted: function() {
	const storedValue = localStorage.getItem("searchtool")
	if (storedValue !== null) {this.active = storedValue === "true"}
},

methods: {},

})