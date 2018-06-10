<?php
	function script_wifi()
	{
		echo ("
			<script src=\"wifi.js\"></script>
		");
	}

	function header_wifi()
	{
		echo('<body class="lang_en" onload="status_init(); wifi_init();">');
	}

	function preexec_wifi()
	{
		global $alert, $lng;
		if (isset($_REQUEST['change_key']))
		{
			require_once('api/wifi.php');
			if (wifi_ap_key_get() != $_REQUEST['old_key'])
			{
				$alert = $lng['old_key_not_valid'];
				return;
			}
			if ($_REQUEST['key'] != $_REQUEST['key2'])
			{
				$alert = $lng['key_not_match'];
				return;
			}
			if (strlen($_REQUEST['key']) < 8)
			{
				$alert = $lng['key_too_short'];
				return;
			}
			wifi_ap_key_set();
			$alert = $lng['key_changed'];
			return;
		}
	}

	function wifi()
	{
		global $lng;
?>
		<h2><?=$lng['wifi_connection_status']?></h2>
		<div id="wifi_status" align="center">
		<?=$lng['loading']?>
		</div>
		<br/><h2><?=$lng['access_points']?></h2>
		<div id="wifi_scan" align="center">
		<?=$lng['searching']?>
		</div>
		<br/><h2><?=$lng['change_self_key']?></h2>
		<div align="center">
		<form method="POST">
		<table class="settings" style="width: 100%; max-width: 700px;">
		<tr><th colspan="2"><?=$lng['change_key']?></th></tr>
		<tr><td><?=$lng['old_key']?></td><td><input type="password" name="old_key"></td></tr>
		<tr><td><?=$lng['new_key']?></td><td><input type="password" name="key"></td></tr>
		<tr><td><?=$lng['new_key_again']?></td><td><input type="password" name="key2"></td></tr>
		</table>		
		<div align="right" style="width: 100%; max-width: 650px;"><br/><br/><input name="change_key" type="submit" value=" <?=$lng['change']?> " style="width: 100px"></div></form>
		<br/><br/><br/><br/>
		</div>
<?php
	}

	function wifi_status_json()
	{
		require_once('api/wifi.php');
		die(json_encode(wifi_status()));
	}

	function wifi_scan_json()
	{
		require_once('api/wifi.php');
		die(json_encode(wifi_scan()));
	}

	function wifi_connect_ap()
	{
		require_once('api/wifi.php');
		die(json_encode(wifi_ap_connect()));
	}
?>