<?php
	function header_settings()
	{
		echo('<body class="lang_en" onload="status_init();">');
	}

	function preexec_settings()
	{
		global $alert, $lng, $language;
		if (!isset($_REQUEST['save_settings'])) return;
		require_once('api/programs.php');
		require_once('api/misc.php');
		global_config_set();
		timezone_set();
		$config = json_decode(file_get_contents('config.json'), true);
		$language = $config['language'] = $_REQUEST['language'];
		file_put_contents('config.json', json_encode($config));

		if ((strlen($_REQUEST['old_password'])>0) && (strlen($_REQUEST['new_password'])>0))
		{
			$t = shell_exec('/usr/bin/check_password root '.escapeshellarg($_REQUEST['old_password']));
			if (strpos($t, 'is VALID') === false) 
			{
				$alert = $lng['old_password_not_valid'];
				return;
			}
			if ($_REQUEST['new_password'] != $_REQUEST['new_password2'])
			{
				$alert = $lng['password_not_match'];
				return;
			}
			$r = shell_exec('sh -c "printf '.escapeshellarg($_REQUEST['new_password'].'\n'.$_REQUEST['new_password']).' | passwd" 2>&1');
			if (strpos($r, 'password for root changed') > 0)
			{
				$alert = $lng['password_changed'];
				$_SESSION['password'] = $_REQUEST['new_password'];
			} else
				$alert = $lng['password_change_error'].": ".$r;
			//return;
		}		
		header('Location: ' . $_SERVER['REQUEST_URI']);
		die();
	}

	function settings()
	{
		global $lng, $language;
		require_once('bm_consts.php');
		$global_config = global_config_get();
		$timezone = timezone_get();
		$zonetab = file('/usr/share/zoneinfo/zone1970.tab');
		$timezones = array();
		foreach($zonetab as $zone)
		{
			if ((strlen(trim($zone)) > 0) && ($zone[0] != "#"))
			{
				$timezones[] = trim(explode("\t", $zone)[2]);
			}
		}
		sort($timezones);
		$language_files = scandir('languages');
		$languages = array();
		foreach($language_files as $l)
			if (strstr($l,'.php'))
				$languages[] = str_replace('.php','',$l);
		
?>
		<h2>Settings</h2>
		<div align="center">
		<form method="POST">
		<table class="settings" style="width: 100%; max-width: 700px;">
		<tr><td><?=$lng['language']?></td><td style="width: 1%"><select name="language" style="width: 200px"><?php
		foreach($languages as $l)
			echo("<option value='$l'".(($l == $language) ? " selected" : "").">$l</option>");
		?></select></td></tr>
		<tr><td><?=$lng['temp_a']?></td><td style="width: 1%"><input name="max_temp_a" type="number" min="0" max="<?= MAX_TEMP ?>" style="width: 50px" value="<?= $global_config['max_temp_a'] ?>"/> &#176;C</td></tr>
		<tr><td><?=$lng['temp_b']?></td><td style="width: 1%"><input name="max_temp_b" type="number" min="0" max="<?= MAX_TEMP ?>" style="width: 50px" value="<?= $global_config['max_temp_b'] ?>"/> &#176;C</td></tr>
		<tr><td><?=$lng['warm_temp']?></td><td style="width: 1%"><input name="warm_temp" type="number" min="0" max="<?= MAX_TEMP ?>" style="width: 50px" value="<?= $global_config['warm_temp'] ?>"/> &#176;C</td></tr>
		<tr><td><?=$lng['warm_time']?></td><td style="width: 1%"><input name="max_warm_time" type="number" min="0" max="999" style="width: 50px" value="<?= $global_config['max_warm_time'] ?>"/> <?=$lng['minutes']?></td></tr>
		<tr><td><?=$lng['timezone']?></td><td style="width: 1%"><select name="timezone" style="width: 200px"><?php
		foreach($timezones as $zone)
			echo("<option value='$zone'".(($zone == $timezone) ? " selected" : "").">$zone</option>");
		?></select></td></tr>
		</table>
		<br/><br/>		
		<table class="settings" style="width: 100%; max-width: 700px;">
		<tr><th colspan="2"><?=$lng['change_password']?></th></tr>
		<tr><td><?=$lng['old_password']?></td><td><input type="password" name="old_password"></td></tr>
		<tr><td><?=$lng['new_password']?></td><td><input type="password" name="new_password"></td></tr>
		<tr><td><?=$lng['new_password_again']?></td><td><input type="password" name="new_password2"></td></tr>
		</table>
		<div align="right" style="width: 100%; max-width: 650px;"><br/><br/><input name="save_settings" type="submit" value=" <?=$lng['save']?> " style="width: 100px"></div></form>
		</div>
		<br/><br/>
		<br/><br/>
<?php
	}
?>