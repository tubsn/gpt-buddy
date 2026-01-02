export default Vue.defineComponent({
data() {return {}},

template: `
<div class="float-button speech-button" title="Text to Speech" @click="createTTS"><img src="/styles/img/icon-volume.svg"> <span>Text to Speech</span></div>
`,

mounted: function() {},

methods: {

	createTTS(event) {

		const container = event.target.parentElement.parentElement
		let text = ''

		if (container.classList.contains('user-input')) {text = this.$root.input}
		else {text = this.$root.output}

		// HTML has to be striped
		const HTMLcleaner = document.createElement('div');
		HTMLcleaner.innerHTML = text;
		text = HTMLcleaner.textContent || HTMLcleaner.innerText || '';

		const form = document.createElement('form');
		form.method = 'POST';
		form.action = '/tts';

		const data = { input: text};
		for (const key in data) {
			const input = document.createElement('input');
			input.type = 'hidden'; input.name = key; input.value = data[key];
			form.appendChild(input);
		}

		document.body.appendChild(form);
		form.submit();

	},

}, // End Methods
})