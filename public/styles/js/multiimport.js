const multiImportApp = Vue.createApp({
	data() {return {
		loading: false,
		ignoredFiles: [],
		results: [],
		imported: [],
		loaders: [],
		prompt: 0,
		ressort: 0,
		maxfilesizemb: 50,
		input: '',
		output: '',
		errorMessage: '',
	}},

	computed: {
		maxsize() {return (this.maxfilesizemb || 50) * 1024 * 1024},
	},

	mounted: function() {
		this.setSelectDefaults();
		this.initCopyPaste();
		this.loadUserSettings();
	},

	methods: {

		setSelectDefaults() {
			defaultOptions = document.querySelectorAll('main select option:first-child');
			defaultOptions.forEach(option => {
				const selectbox = option.parentNode;
				const name = selectbox.dataset.name
				this[name] = option.value;
			})
		},

		resetResults() {this.results = []},

		loadUserSettings() {
			this.ressort = localStorage.ressort
		},

		remember(event, name) {
			const value = event.target.value
			localStorage[name] = value
		},

		dropped(event) {
			let files = [];
			[...event.dataTransfer.items].forEach((item, i) => {
				// If dropped items aren't files, reject them
				if (item.kind === "file") {
					const file = item.getAsFile()
					files.push(file)
				}
			})
			this.gatherFiles(files)
		},

		checkIntegrity(files) {
			this.ignoredFiles = files.filter(file => file.size > this.maxsize)
			files = files.filter(file => file.size < this.maxsize)
			return files
		},

		openFileSelector() {this.$refs.fileSelector.click()},


		async getImports() {
			let response = await fetch('/multiimport/today')
			if (!response.ok) {}
			let json = await response.json();
			this.imported = json
		},

		async gatherFiles(input) {

			this.errorMessage = ''
			this.loading = true
			let files = null

			// Drag and Drop detection
			if (input.target) {files = Array.from(event.target.files)}
			else {files = input}

			files = this.checkIntegrity(files)
			if (files.length < 1) {this.uploadDone(); return}

			for (const [index, file] of files.entries()) {
			    this.results.push({name: 'Datei: ' + file.name, status: null, index: index});
			    this.loaders[index] = true;
			    await this.upload(file, index);
			    this.loaders[index] = false;
			}

			this.uploadDone()
		},



		async upload(file, index = false) {
			
			let formData = new FormData()
			formData.append('file', file)
			formData.append('prompt', this.prompt)
			formData.append('ressort', this.ressort)

			let response = await fetch('', {method: 'POST', body: formData})
			
			if (!response.ok || response.status != 200) {
				console.error (await response.text())
				this.displayError('Errorcode ' , response.status);
				this.loading = false
				return
			}
			
			let text; text = await response.text(); // Recieves any Server output

			try { // Parse Text as JSON
				let json = JSON.parse(text)
				this.results[index].status = json.entries
				this.results[index].ids = json.importIDs
					
			} catch {
				this.displayError(text)
				this.loading = false
			}
			
		},


		async importText() {

			this.errorMessage = ''
			this.loading = true

			let formData = new FormData()
			formData.append('textarea', this.input)
			formData.append('prompt', this.prompt)
			formData.append('ressort', this.ressort)			

			let response = await fetch('', {method: 'POST', body: formData})		

			if (!response.ok || response.status != 200) {
				console.error (await response.text())
				this.displayError('Errorcode ' , response.status);
				this.loading = false
				return
			}

			let text; text = await response.text(); // Recieves any Server output

			try { // Parse Text as JSON
				let json = JSON.parse(text)
 				this.results.push({name: 'Textimport', status: json.entries, index: 0, ids: json.importIDs});
					
			} catch {
				this.displayError(text)
			}

			this.loading = false

		},

		async removeImport(index) {
			let entries = this.results[index].status
			let ids = this.results[index].ids
			ids = Array.from(ids)
			this.deleteEntries(ids)
			this.results.splice(index,1)
		},

		async deleteEntries(ids) {
			let formData = new FormData()
			formData.append('ids', ids)
			let response = await fetch('/multiimport/delete', {method: 'POST', body: formData})
		},

		displayError(message, code = '') {
			if (code) {code = ` ${code}`}
			this.errorMessage = 'Fehler: ' + message + code;
		},

		uploadDone() {
			this.loading = false
			//this.getImports();
			//this.$emit('done')
		},



		initCopyPaste() {
			let _this = this
			document.addEventListener('DOMContentLoaded', () => {
				document.onpaste = function(event){
					const content = _this.getContentFromPasteEvent(event);
					if (typeof content === 'object') {_this.copyPasteUpload(content);}
				}
			});
		},

		getContentFromPasteEvent(event) {

			const items = (event.clipboardData || event.originalEvent.clipboardData).items;

			for (let index in items) {
				const item = items[index];

				if (item.kind === 'file') {
					return item.getAsFile();
				}

			}

			return (event.clipboardData || window.clipboardData).getData("text")
		},

		async copyPasteUpload(file) {
			if (!confirm('Möchten Sie ihren Screenshot hochladen?')) {return}
			this.loading = true			
			this.upload(file,0)
			this.uploadDone()
		},



	}, // End Methods
}).mount('#multiImportApp')








class TabelSort {

	constructor() {

		this.sortableTables = document.querySelectorAll('.js-sortable');
		if (this.sortableTables) {
			this.tableSort(this.sortableTables);
			this.clickAble(this.sortableTables);
		}

		this.uploadIndicator();
	}

	refresh() {

		this.sortableTables = document.querySelectorAll('.js-sortable');
		if (this.sortableTables) {
			this.tableSort(this.sortableTables);
			this.clickAble(this.sortableTables);
		}

	}

	uploadIndicator() {
		let upl = document.querySelectorAll('.fl-upload-form input');
		if (!upl) {return}

		upl.forEach(element => {
			element.onchange = (event) => {emsAPP.isUploading = true;}	
		})
	}

	clickAble(sortableTables) {

		Array.from(sortableTables).forEach(table => {

			let clickableTR = table.querySelectorAll("tr[data-id]");

			clickableTR.forEach(element => {
				element.addEventListener('dblclick', function ( e ) {
					let url = element.dataset.eventUrl;
					window.location.href = url;
				})
			})

		})

	}

	tableSort(sortableTables) {
		let this_ = this;

		Array.from(sortableTables).forEach(table => {

			table.addEventListener( 'mouseup', function ( e ) {

				// proceed Only for Left Click
				if (e.button != 0) {return;}

				/*
				 * sortable 1.0
				 * Copyright 2017 Jonas Earendel
				 * https://github.com/tofsjonas/sortable
				*/

			    var down_class = ' dir-d ';
			    var up_class = ' dir-u ';
			    var regex_dir = / dir-(u|d) /;
			    var regex_table = /\bsortable\b/;
			    var element = e.target;

			    function reclassify( element, dir ) {
			        element.className = element.className.replace( regex_dir, '' ) + (dir?dir:'');
			    }

			    if ( element.nodeName == 'TH' ) {

			        var table = element.offsetParent;

			        // make sure it is a sortable table
			        if ( regex_table.test( table.className ) ) {

			            var column_index;
			            var tr = element.parentNode;
			            var nodes = tr.cells;

			            // reset thead cells and get column index
			            for ( var i = 0; i < nodes.length; i++ ) {
			                if ( nodes[ i ] === element ) {
			                    column_index = i;
			                } else {
			                    reclassify( nodes[ i ] );
			                    // nodes[ i ].className = nodes[ i ].className.replace( regex_dir, '' );
			                }
			            }

			            var dir = up_class;

			            // check if we're sorting up or down, and update the css accordingly
			            if ( element.className.indexOf( up_class ) !== -1 ) {
			                dir = down_class;
			            }

			            reclassify( element, dir );

			            // extract all table rows, so the sorting can start.
			            var org_tbody = table.tBodies[ 0 ];

			            var rows = [].slice.call( org_tbody.cloneNode( true ).rows, 0 );
			            // slightly faster if cloned, noticable for huge tables.

			            var reverse = ( dir == up_class );

			            // sort them using custom built in array sort.
			            rows.sort( function ( a, b ) {

							// sorting with Dates
							if (a.cells[ column_index ].hasAttribute('data-sortdate')){
								a = a.cells[ column_index ].getAttribute('data-sortdate');
							}
							else {
								a = a.cells[ column_index ].innerText;
							}

							if (b.cells[ column_index ].hasAttribute('data-sortdate')){
								b = b.cells[ column_index ].getAttribute('data-sortdate');
							}
							else {
								b = b.cells[ column_index ].innerText;
							}

			                if ( reverse ) {
			                    var c = a;
			                    a = b;
			                    b = c;
			                }

							// Parse to Float when % detected
							if (a.match(/%/g)) {
								a = parseFloat(a);
							} else {a = a.replace('.','');}

							if (b.match(/%/g)) {
								b = parseFloat(b);
							} else {b = b.replace('.','');}

			                return isNaN( a - b ) ? a.localeCompare( b ) : a - b;
			            } );

			            // Make a clone without contents
			            var clone_tbody = org_tbody.cloneNode();

			            // Build a sorted table body and replace the old one.
			            for ( i in rows ) {
			                clone_tbody.appendChild( rows[ i ] );
			            }

			            // And finally insert the end result
			            table.replaceChild( clone_tbody, org_tbody );

			        }

			    }

			}); // End sorttable Plugin

		}); // End For Each Array

	} // End Tablesort Method

}





// Darkmode
function toggleDarkmode() {

	let cssLink = document.querySelector('#dark-mode-css-link')
	
	if (cssLink) {
		cssLink.remove()
		document.cookie = 'darkmode = 0; path=/; expires=Fri, 31 Dec 1970 23:59:59 GMT'
		return
	}

	let link = document.createElement('link')
	link.id = 'dark-mode-css-link'
	link.rel = 'stylesheet'
	link.type = 'text/css'
	link.href = '/styles/css/darkmode.css'
	document.getElementsByTagName("head")[0].appendChild(link)
	document.cookie = 'darkmode = 1;path=/; expires=Fri, 31 Dec 9999 23:59:59 GMT'
}

document.addEventListener("DOMContentLoaded", function(){
	window.flTableSort = new TabelSort();
	let colorModeIcon = document.querySelector('.color-mode')
	if (colorModeIcon) {colorModeIcon.addEventListener('click', event => {toggleDarkmode()})}
});