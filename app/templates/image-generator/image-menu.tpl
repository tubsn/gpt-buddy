<dropdown menu-element="image-history">
	<div action="exportimage">Bild Exportieren</div>
	<?php if (auth_rights('deleteimage')): ?>
	<div action="deleteimage" class="danger">Bild löschen</div>
	<?php endif ?>
	<div action="copyimageprompt">Prompt Kopieren</div>	
</dropdown>