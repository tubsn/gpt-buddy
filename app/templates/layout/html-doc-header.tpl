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
</head>
<body>

