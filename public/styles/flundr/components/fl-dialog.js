class dialogBox extends HTMLElement {

	constructor() {
		super(); // Custom-Elements requirement
	}

	get selector() {
		const selectorName = this.getAttribute('selector');
		return document.querySelector(selectorName);
	}

	get url() {
		return this.getAttribute('href');
	}

	connectedCallback() {

		const shadowRoot = this.attachShadow({ mode: 'open' });

		if (this.selector) {
			this.selector.addEventListener('click', (e) => {
				shadowRoot.appendChild(this.createContainer());	
			});
		}
		else {
			console.log (`<fl-dialog> has no "selector" attribute` )
		}
	}

	createContainer() {
		let container = document.createElement('div');

		container.innerHTML = `
			<style>
			.wrapper {position:fixed; top:0; left:0; display:grid; width:100vw; height:100vh; background-color:rgba(0,0,0,0.5);}
			.modal {align-self: center; justify-self: center; background-color:#f6f6f6;
				border: 0.2em solid white; padding:2em; max-width:80%; border-radius:0.5em;
				box-shadow: .5em .5em .7em .3em rgba(0,0,0,0.4);}
			@media only screen and (min-height: 600px) { .modal {transform: translateY(-50%);} }
			
			.dialog-buttons {text-align:right; margin-top:0.5em}
			.dialog-buttons button {background:#111; color:white; border:0; display:inline; padding:.4em .6em 0.4em 0.6em; border-radius:0.15em; cursor:pointer; font-size:0.9em}
			.dialog-buttons button:hover {background:#333}
			::slotted(h1) {margin:0; margin-bottom:0.1em;}
			::slotted(p) {margin:0; margin-bottom:0.5em;}
			
			/* animation slide-in-blurred-top by animista */
			.slide-in {animation: slide-in-blurred-top 0.3s cubic-bezier(0.230, 1.000, 0.320, 1.000) both;}
			@keyframes slide-in-blurred-top {
			  0% {transform: translateY(-1000px) scaleY(2.5) scaleX(0.2); transform-origin: 50% 0%; filter: blur(40px); opacity: 0;}
			  100% {transform: translateY(0) scaleY(1) scaleX(1); transform-origin: 50% 50%; filter: blur(0); opacity: 1;}
			}
			</style>
			<div class="wrapper">
				<div class="modal slide-in">
				<slot></slot>
				<div class="dialog-buttons">
				<button class="dialog-true">Ok</button>
				<button class="dialog-false">Cancel</button>
				</div>
				</div>
			</div>
		`;

	

		let trueButton = container.querySelector('.dialog-true');
		trueButton.addEventListener('click', (e) => {
			container.remove();
			if (this.url) window.location = this.url;
		});

		let falseButton = container.querySelector('.dialog-false');
		falseButton.addEventListener('click', (e) => {
			container.remove();
		});

		let wrapper = container.querySelector('.wrapper');
		wrapper.addEventListener('click', (e) => {
			if (wrapper !== e.target) return;
			container.remove();
		});

		return container;
	}

};

document.addEventListener('DOMContentLoaded', function() {
	customElements.define('fl-dialog', dialogBox);
});
