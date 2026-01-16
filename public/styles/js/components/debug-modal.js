export default Vue.defineComponent({
data() {return {
	sseFinalOutput: [],
	sseProgress: [],
	modelarguments: '',
}},

template: `
<section class="hide-mobile debug-wrapper">
<button class="button light debug-button" type="button"
	onclick="document.getElementById('debugDialog').showModal()">Debug infos</button>

<dialog id="debugDialog">
	<button class="fright button" type="button"
		onclick="document.getElementById('debugDialog').close()">
		Schließen
	</button>

	<figure>
		Model Tooling Parameter:
		<pre>{{modelarguments || 'empty'}}</pre>
	</figure>

	<div class="col-2 debug">
		<label>MCP/Tooling Events:
			<textarea v-if="sseProgress.length>0" tabindex="-1">{{ sseProgress }}</textarea>
			<textarea v-else tabindex="-1"></textarea>
		</label>

		<label>Output Events:
			<textarea v-if="sseFinalOutput.length>0" tabindex="-1">{{ sseFinalOutput }}</textarea>
			<textarea v-else tabindex="-1"></textarea>
		</label>
	</div>
</dialog>
</section>
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