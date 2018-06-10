<?php
	//error_reporting(0);
	$api_path = $argv[1];
	$count = $argv[2];
	require_once($api_path . '/config.php');
	require_once($api_path . '/consts.php');

	$stats_file_name = STATS_DIR . '/breadmaker_stats_sec.json';
	$stats_json = trim(@file_get_contents($stats_file_name));
	if ($stats_json)
	{
		if ($stats_json[strlen($stats_json)-1] == ',') $stats_json[strlen($stats_json)-1] = ' ';
	} else {
		$stats_json = '';
	}
	$stats_json = '[' . $stats_json . ']';
	$stats = json_decode($stats_json);
	if ($count < count($stats))
	{
		$stats = array_slice($stats, count($stats) - $count);
	}
	if (!$stats)
	{
		file_put_contents("/tmp/debug_group_$count", $stats_json);
		die();
	}

	$temperature = 0;
	$target_temperature = 0;
	$motor = 'off';
	$heat = false;
	$c = 0;
	
	foreach ($stats as $stat)
	{
		$last = $stat;
		if (!isset($stat->temp))
			continue;
		$temperature += $stat->temp;
		if ($stat->motor == 'on') $motor = 'on';
			else if ($stat->motor == 'onoff' && $motor == 'off') $motor = 'onoff';
		$heat = $heat | $stat->heat;
		$c++;
	}
	$last->temp = $c > 0 ? ($temperature / $c) : 0;
	$last->motor = $motor;
	$last->heat = $heat;
	echo(json_encode($last) . ",\n");
?>
