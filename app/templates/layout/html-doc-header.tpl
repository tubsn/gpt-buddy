<!DOCTYPE html>
<html lang="de">
<head>
	<title> <?=$page['title']?> </title>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
	<meta name="author" content="<?=$page['meta']['author'] ?? 'flundr'?>" />
	<meta name="description" content="<?=$page['description']?>" />
<?php if (isset($page['fonts'])):?>
	<link href="<?=$page['fonts']?>" rel="stylesheet">
<?php endif ?>
<?php if (!empty($page['css'])):?>
<?php foreach ($page['css'] as $css): ?>
	<link rel="stylesheet" type="text/css" media="all" href="<?=$css?>" />
<?php endforeach ?>
<?php endif ?>
<?php if (isset($page['meta']['favicon'])):?>
	<link rel="shortcut icon" href="<?=$page['meta']['favicon']?>" /><?php endif ?>	
<?php if (!empty($page['framework'])):?>
<?php foreach ($page['framework'] as $framework): ?>
	<script type="text/javascript" src="<?=$framework?>"></script>
<?php endforeach ?>
<?php endif ?>
<?php if (!empty($page['modules'])):?>
<?php foreach ($page['modules'] as $module): ?>
	<script type="module" src="<?=$module?>"></script>
<?php endforeach ?>
<?php endif ?>
<?php if (isset($_COOKIE['darkmode']) && $_COOKIE['darkmode']): ?>
	<link id="dark-mode-css-link" rel="stylesheet" type="text/css" media="all" href="/styles/css/darkmode.css" />
<?php endif ?>

</head>
<body>

