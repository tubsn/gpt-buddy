const VueDropDown = Vue.defineComponent({
data() {return {
	input: '',
	output: '',
	open: false,
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
				event.preventDefault();
				this.showMenu(event);
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

	copy() {
		if (this.menuElement.includes('output')) {
			this.$parent.copyOutputToClipboard()
			return
		}
		this.$parent.copyInputToClipboard()
	},

	delete() {
		if (this.menuElement.includes('output')) {
			this.$parent.wipeHistory()
			return
		}		
		this.$parent.wipeInput()
	},

	redo(event) {
		this.$parent.redoLastStep()
	},


}, // End Methods
})