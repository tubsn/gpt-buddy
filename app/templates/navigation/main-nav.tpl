<header>

<nav class="main-nav">
	<ul>
		<li class="app-logo-li"><a href="/"><?=APP_NAME?></a>
			<!--<ul class="dropdown" aria-label="submenu">
				<li><a target="_blank" href="https://chat.openai.com/">ChatGPT Aufrufen</a></li>
			</ul>-->
		</li>

		<li><a href="/">Assistent</a></li>
		<li><a href="/spelling">Rechtschreibung</a></li>
		<li><a href="/shorten">Textlängen</a></li>
		<li><a href="/translate">Übersetzer</a></li>
		<li><a href="/image">Bildgenerator</a></li>
		<!--<li class="hide-mobile"><a href="/image">Bildertest</a></li>-->

	</ul>

	<ul>
		<!--
		<li>
			<form class="searchbox" method="get" action="/search">
				<input type="text" name="q" placeholder="Suchen..." value="<?=$query ?? ''?>">
				<button type="submit"></button>
			</form>
		</li>
		-->
		<?php if (auth_rights('chatgpt')): ?>
		<li><a href="/settings">Konfiguration</a></li>
		<?php endif; ?>

		<?php if (auth('level') == 'Admin'): ?>
		<li><a href="/admin" title="Einstellungen">Admin</a>
		</li>
		<?php endif; ?>


		<li class="login-icon">
			<a href="/profile" title="Nutzer">
				<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
					 width="15px" height="18px" viewBox="0 0 15 18" enable-background="new 0 0 15 18" xml:space="preserve">
				<path id="loginHeadIcon" fill="#ffffff" d="M7.5,10.017c-2.772,0-5.018-2.242-5.018-5.009S4.728,0,7.5,0c2.772,0,5.017,2.241,5.017,5.008
					S10.272,10.017,7.5,10.017 M10.954,11.163c0,0-1.644,0.923-3.455,0.923c-1.812,0-3.453-0.923-3.454-0.923
					C4.042,11.161,0.043,12.288,0,16.389c0,0.82,0.665,1.485,1.485,1.485h12.029c0.819,0,1.485-0.665,1.485-1.485
					C14.956,12.283,10.958,11.166,10.954,11.163"/>
				</svg>
			</a>
		</li>

	</ul>

</nav>

</header>
