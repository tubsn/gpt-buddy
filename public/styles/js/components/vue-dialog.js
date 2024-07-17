const flundrVueDialog = Vue.defineComponent({
data() {return {
	input: '',
	placeholder: 'Hier Text einfügen',
}},

props: ['addonclasses', 'headline'],
emits: ['open','cancel','ok'],

computed: {
	maxsize() {},
},

template: `
<div>
<dialog ref="dialog" >

	<div class="dialog-header">
		{{headline}}
	</div>

	<div class="dialog-content">
		<form method="dialog">
		<textarea tabindex="1" v-model="input" :placeholder="placeholder"></textarea>
		<div class="dialog-button-area">
			<button tabindex="3" @click="cancel" class="light">zurück</button>
			<button tabindex="2" @click="accept">absenden</button>
		</div>
		</form>
		<div @click="cancel" class="dialog-close-btn">&#10006;</div>
	</div>
</dialog>
<button class="button" :class="addonclasses" type="button" @click.prevent="openModal"><slot></slot></button>
</div>
`,

mounted: function() {

},

methods: {

	accept() {
		this.loading = true;
		this.$emit('ok', this.input);
	},

	cancel() {
		this.$emit('cancel');
		this.$refs.dialog.close()
	},

	openModal() {
		let element = this.$refs.dialog
		element.addEventListener('click', (event) => {
			if (event.target.nodeName != 'DIALOG') {return}
			element.close()
		});	
		element.showModal()
	},

}, // End Methods
})