export default Vue.defineComponent({
data() {return {
	timeframe: 'nicht einschränken',
	from: '',
	to: '',
	length: '',
	userneed: '',
	tags: '',
	section: '',
	disabled: false,
}},

props: [],
emits: ['change'],

computed: {},

watch: {
	timeframe(range) {this.setDateRange(range)},
},

template: `
<fieldset :disabled="disabled" class="rag-grid">

	<label title="Zeitraum in dem das Archiv durchsucht wird">Zeitraum wählen <span class="small">[?]</span>:
	<select v-model="timeframe" name="timeframe">
		<option>nicht einschränken</option>
		<option>diese Woche</option>
		<option>dieser Monat</option>
		<option>3 Monate</option>
		<option>1 Jahr</option>
	</select>
	</label>

	<label>Zeitraum von: <input v-model="from" type="date" value=""></label>
	<label>Zeitraum bis: <input v-model="to" type="date" value=""></label>

	<label>Userneed wählen:
	<select v-model="userneed">
		<option value="">nicht vorgeben</option>
		<option>Update Me</option>
		<option>Divert Me</option>
		<option>Give me Perspective</option>
		<option>Educate Me</option>
		<option>Help Me</option>
		<option>Inspire Me</option>
	</select>
	</label>

	<label>Textlänge wählen:
	<select v-model="length">
		<option value="">nicht vorgeben</option>
		<option>kurz</option>
		<option>medium</option>
		<option>lang</option>
	</select>
	</label>

	<label>Rubrik Filtern:
		<input v-model="section" list="bnn-ressorts" type="text" placeholder="z.B. Karlsruhe oder Ettlingen">
	</label>

	<label>Tag Filtern:
		<input v-model="tags" list="bnn-tags" type="text" placeholder="z.B. Restaurants, Wandern" >
	</label>

</fieldset>
`,

mounted: function() {
	this.selectDefault()
	this.setDateRange(this.timeframe)
},

methods: {

	accessData() {
		return {
			from: this.from,
			to:this.to,
			userneed: this.userneed,
			length: this.length,
			tags: this.tags,
			section: this.section,
		}
	},

	selectDefault() {
		if (!this.promptID) {
			const selectElement = this.$refs.selectBox
			if (selectElement && selectElement.options.length > 0) {
				this.promptID = selectElement.options[0].value
			}
		}
	},

	setDateRange(range) {

		moment.updateLocale('de', { week: { dow: 1 } });
		const toDateString = date => date.format('YYYY-MM-DD');

		const setRange = (start, end) => {
			this.from = toDateString(start);
			this.to = toDateString(end);
		};


		switch (range) {
			case 'diese Woche': {
				setRange(moment().subtract(6, 'days').startOf('day'), moment().endOf('day'))
				break
			}

			case 'dieser Monat': {
				setRange(moment().startOf('month'), moment().endOf('month'))
				break
			}

			case '3 Monate': {
				setRange(moment().subtract(3, 'months').startOf('day'), moment().endOf('day'))
				break
			}

			case '1 Jahr': {
				setRange(moment().subtract(1, 'year').startOf('day'), moment().endOf('day'))
				break
			}

			case 'alles': case '0': case 'nicht einschränken': {
				this.from = ''
				this.to = ''
				break
			}
		}



		//setRange(moment().subtract(6, 'days').startOf('day'), moment().endOf('day'))


		const actions = {
			'diese woche': () => setRange(moment().subtract(6, 'days').startOf('day'), moment().endOf('day')),
			'dieser monat': () => setRange(moment().startOf('month'), moment().endOf('month')),
			'3 monate': () => setRange(moment().subtract(3, 'months').startOf('day'), moment().endOf('day')),
			'alles': () => { elementFrom.value = ''; elementTo.value = ''; }
		};

	},


}, // End Methods
})