<dropdown menu-element="image-history">
	<!--<div action="exportimage">Bild exportieren</div>-->
	<div action="openimage">Bild öffnen</div>	
	<div action="downloadimage">Bild herunterladen</div>	
	<?php if (auth_rights('deleteimage')): ?>
	<div action="deleteimage" class="danger">Bild löschen</div>
	<?php endif ?>
	<div action="copyimageprompt">Prompt kopieren</div>	
</dropdown>