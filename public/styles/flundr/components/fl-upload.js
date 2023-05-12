class fl_fileUploader extends HTMLElement {

	constructor() {super();} // Super Constructor required

	// Attributes
	get destination() {return this.getAttribute('destination');}
	get label() {return this.innerText;}
	get anchor() {return this.hasAttribute('anchor');}
	get multiple() {if (this.hasAttribute('multiple')) {return 'multiple';}	return;}

	// Init when in DOM
	connectedCallback() {
		this.styleElement();
	}

	styleElement() {

		let randomID = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);

		this.innerHTML =
		`<style>
		.fl-upload-form {display:none;}
		</style>
		<a class="button fl-upload-button">${this.label}</a>
		<form id="${randomID}" class="fl-upload-form" action="${this.destination}" method="post" enctype="multipart/form-data" accept-charset="utf-8">
			<input name="uploads[]" type="file" ${this.multiple}>
		</form>`;

		const flUpload = new fl_uploads(randomID,'.fl-upload-button');

	}

};


class fl_loadIndicator {

	constructor(holder, color=null, prepend=true) {
		this.holder = holder;
		this.color = color;
		this.loader = this.createLoaderElement();
		this.holder.appendChild(this.loader);
	}

	createLoaderElement(){
		const indicatorDiv = document.createElement('div');
		indicatorDiv.className = 'loadIndicator';
		indicatorDiv.classList.add(this.color);
		indicatorDiv.innerHTML = '<div></div><div></div><div></div>';
		return indicatorDiv;
	}

	hide() {
		this.loader.remove();
	}

}


class fl_uploads {

	constructor(form,buttons) {
		this.form = document.getElementById(form);

		// Do nothing if no Form is found
		if (this.form == null) {return;}

		this.buttons = document.querySelectorAll(buttons);
		this.clickedButton = null; // set after one of buttons is clicked
		this.fileSelectInput = this.form.querySelector('input[type="file"]');

		this.listenToButtons(this.buttons);
		this.listenToFileSelect(this.fileSelectInput);
	}

	listenToButtons(buttons) {
		if (!buttons) {return;}
		const _this = this;
		Array.from(buttons).forEach(button => {

		    button.addEventListener('click', () => {
				_this.clickedButton = button;
		        _this.fileSelectInput.click();
		    });
		});
	}

	listenToFileSelect(element) {
		if (!element) {return;}
		const _this = this;
		element.addEventListener('change', (e) => {
			this.uploadFiles(e.target.files);
		});

	}

	uploadFiles(files) {

		// Add a Loadindicator
		const loadi = new fl_loadIndicator(this.clickedButton,'white');

		const path = this.form.getAttribute('action');
		const arrayNameNoBrackets = this.fileSelectInput.getAttribute('name').slice(0, - 2);

		let data = new FormData();
		let counter=0;
		let skippedFileErrors = [];

		for (const file of files) {

			if (file.size >= (20 * 1024 * 1024)) {
				skippedFileErrors.push('<li>'+file.name+'</li>');
				continue;
			}
			data.append(arrayNameNoBrackets+'['+counter+']', file);
			counter++;
		}

		if (skippedFileErrors.length > 0) {
			let errorMessage = '<h3>Fehler beim Hochladen: </h3> Folgende Dateien sind zu groß: <ul>';
			skippedFileErrors.forEach(filename => {
				errorMessage = errorMessage + filename + ' ';
			});
			errorMessage = errorMessage + '</ul>';

			let alertBox = document.querySelector('.js-alert');
			if (alertBox) {
				alertBox.classList.add('danger');
				alertBox.innerHTML = errorMessage;
				alertBox.style.display = 'block';
			}
			else {console.warn(errorMessage)}
		}

		if (counter == 0) {
			loadi.hide();
			console.warn('0 files uploaded');
			return null;
		}

		fetch(path, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		})
			.then(response => {

				if(response.status === 404) {return response;}

				if (response.headers.get("content-type") == 'application/json; charset=utf-8' || response.headers.get("content-type") == 'application/json') {
					return response.json();
				}
				else {
					return response.text();
				}
			})
			.then(phpresponse => {
				loadi.hide(); // Hide the Load Indicator
				this.processUploads(phpresponse);
			})
			.catch(error => {
				console.error(`Fatal Error: ${error}`);
			});

	}

	processUploads(response) {

		if (typeof response == 'object') {

			console.log(response);

			response.forEach(fileInfo => {

				fileInfo.size = (fileInfo.size / 1024 / 1024).toFixed(2) + 'MB';

				let errorMessage = `
Datei: ${fileInfo.name} konnte nicht hochgeladen werden.
Fehler: ${fileInfo.error} - Dateityp: .${fileInfo.ext} - Dateigröße: ${fileInfo.size}`;
				alert (errorMessage);
				return;
			});

			window.location.href += "#anhaenge";
			location.reload(); // Note for later: should not reload after Error?
		}

		// Return what else is left
		console.log(response);

	}

}

document.addEventListener('DOMContentLoaded', function() {
	customElements.define('fl-upload', fl_fileUploader);
});
