<?php
	header("Content-type: application/x-javascript");
	$config = json_decode(file_get_contents('config.json'), true);
	$language = $config['language'];
	require_once("languages/$language.php");
	echo("	var lng = " . json_encode($lng));
?>