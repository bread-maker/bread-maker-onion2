<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function wifi_status()
	{
		global $result;
		if (!EMULATION)
		{
			$status = json_decode(shell_exec('ubus call network.interface.wwan status'));
			$result['wifi_status'] = $status;
		} else {
			$up = false;
			$config_file_name = SETTINGS_DIR . "/client_ap.json";
			$config_json = @file_get_contents($config_file_name);
			$ap = json_decode($config_json);
			if ($ap->ssid == 'bread_router' && $ap->encryption == 'WPA2PSK' && $ap->key == 'breadkey') $up = true;
			if ($up)
				$result['wifi_status'] = json_decode('{"up":true,"pending":false,"available":true,"autostart":true,"dynamic":false,"uptime":62821,"l3_device":"apcli0","proto":"dhcp","device":"apcli0","metric":0,"dns_metric":0,"delegation":true,"ipv4-address":[{"address":"10.13.1.10","mask":24}],"ipv6-address":[],"ipv6-prefix":[],"ipv6-prefix-assignment":[],"route":[{"target":"10.13.1.254","mask":32,"nexthop":"0.0.0.0","source":"10.13.1.10\/32"},{"target":"0.0.0.0","mask":0,"nexthop":"10.13.1.254","source":"10.13.1.10\/32"}],"dns-server":["10.13.1.254"],"dns-search":["lan"],"inactive":{"ipv4-address":[],"ipv6-address":[],"route":[],"dns-server":[],"dns-search":[]},"data":{"hostname":"Breadmaker","leasetime":43200}}');
			else
				$result['wifi_status'] = json_decode('{"up":false}');
		}
	}

	function wifi_scan()
	{
		global $result;
		if (!EMULATION)
		{
			$scan = shell_exec("ubus call onion wifi-scan '{\"device\":\"ra0\"}'");
			$result['wifi_scan_result'] = json_decode($scan)->results;
		} else {
			sleep(2);
			$result['wifi_scan_result'] = json_decode('{"results":[{"channel":"1","ssid":"SHOGA","bssid":"c8:6c:87:43:31:b1","authentication":"AES","encryption":"WPA2PSK","signalStrength":"39","wirelessMode":"11b\/g\/n","ext-ch":"ABOVE"},{"channel":"1","ssid":"bread_router","bssid":"11:22:33:44:55:66","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"63","wirelessMode":"11b\/g\/n","ext-ch":"NONE"},{"channel":"4","ssid":"MGTS_GPON_2421","bssid":"00:0e:8f:65:f5:8b","authentication":"AES","encryption":"WPA2PSK","signalStrength":"70","wirelessMode":"11b\/g\/n","ext-ch":"NONE"},{"channel":"4","ssid":"igrushka-wifi","bssid":"c4:6e:1f:b9:42:c0","authentication":"AES","encryption":"WPA2PSK","signalStrength":"57","wirelessMode":"11b\/g\/n","ext-ch":"ABOVE"},{"channel":"5","ssid":"WirelessNet","bssid":"fc:48:ef:95:0f:e8","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"31","wirelessMode":"11b\/g\/n","ext-ch":"NONE"},{"channel":"5","ssid":"Hedgemade-mgts","bssid":"fc:48:ef:95:13:1c","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"100","wirelessMode":"11b\/g\/n","ext-ch":"NONE"},{"channel":"5","ssid":"mgts-36","bssid":"70:54:f5:c5:4d:cc","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"26","wirelessMode":"11b\/g\/n","ext-ch":"BELOW"},{"channel":"6","ssid":"Amaritta","bssid":"00:90:4c:c1:00:00","authentication":"TKIP","encryption":"WPA1PSKWPA2PSK","signalStrength":"18","wirelessMode":"11b\/g","ext-ch":"NONE"},{"channel":"6","ssid":"kv29","bssid":"fc:48:ef:95:13:58","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"24","wirelessMode":"11b\/g\/n","ext-ch":"BELOW"},{"channel":"7","ssid":"mgts-27","bssid":"fc:48:ef:95:0d:18","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"60","wirelessMode":"11b\/g","ext-ch":"NONE"},{"channel":"8","ssid":"WirelessNet","bssid":"fc:48:ef:95:0c:e6","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"76","wirelessMode":"11b\/g\/n","ext-ch":"NONE"},{"channel":"10","ssid":"mgts-21","bssid":"70:54:f5:c4:ed:82","authentication":"TKIPAES","encryption":"WPA1PSKWPA2PSK","signalStrength":"0","wirelessMode":"11b\/g\/n","ext-ch":"NONE"}]}')->results;
		}
	}

	function wifi_ap_key_get()
	{
		global $result;
		if (!EMULATION)
		{
			$key = trim(shell_exec("uci get wireless.@wifi-iface[0].key"));
			$result['wifi_ap_key'] = $key;
		} else {
			$config_file_name = SETTINGS_DIR . "/wifi_key";
			$result['wifi_ap_key'] = @file_get_contents($config_file_name);
		}
	}

	function wifi_ap_key_set()
	{
		global $result;
		if (!isset($_REQUEST['key']))
			error(ERROR_INVALID_ARGUMENT);		
		$key = $_REQUEST['key'];
		if (!EMULATION)
		{
			$key = str_replace("'", "\\'", $key);
			$r = trim(shell_exec("uci set wireless.@wifi-iface[0].key='$key' 2>&1"));
			$r2 = trim(shell_exec("uci commit wireless.@wifi-iface[0].key 2>&1"));
			$result['result'] = $r == '' && $r2 == '';
		} else {
			$config_file_name = SETTINGS_DIR . "/wifi_key";
			$result['wifi_ap_key'] = @file_put_contents($config_file_name, $key);
			$result['result'] = true;
		}
	}

/*
	function wifi_client_aps_get()
	{
		global $result;
		if (!EMULATION)
		{
			$id = 0;
			$aps = array();
			do
			{
				$n = trim(shell_exec("uci -q get wireless.@wifi-config[$id]"));
				$ssid = trim(shell_exec("uci -q get wireless.@wifi-config[$id].ssid"));
				$key = trim(shell_exec("uci -q get wireless.@wifi-config[$id].key"));
				$encryption = trim(shell_exec("uci -q get wireless.@wifi-config[$id].encryption"));
				if (strlen($n) > 0)
				{
					$aps[$id] = array("id" => $id, "ssid" => $ssid, "key" => $key, "encryption" => $encryption);
				}
				$id++;
			} while (strlen($n) > 0);
		} else {
			$config_file_name = SETTINGS_DIR . "/client_aps.json";
			$config_json = @file_get_contents($config_file_name);
			$aps = json_decode($config_json);
			$id = 0;
			foreach($aps as $k => $ap)
			{
				$aps[$k]->id = $id;
				$id++;
			}
		}
		$result['APs'] = $aps;
	}

	function wifi_client_aps_add()
	{
		$id = 0;
		if (!EMULATION)
		{
			do
			{
				$n = trim(shell_exec("uci -q get wireless.@wifi-config[$id]"));
				$id++;
			} while (strlen($n) > 0);
			$id--;
		} else {
			$config_file_name = SETTINGS_DIR . "/client_aps.json";
			$config_json = @file_get_contents($config_file_name);
			$aps = json_decode($config_json);
			$id = count($aps);
		}

		wifi_client_aps_edit($id, true);
	}

	function wifi_client_aps_edit($id = -1, $add = false)
	{
		global $result;
		if ($id < 0)
		{
			if (!isset($_REQUEST['id']))
				error(ERROR_MISSED_ARGUMENT, 'id');
			$id = $_REQUEST['id'];
		}
		if (!isset($_REQUEST['ssid']))
			error(ERROR_MISSED_ARGUMENT, 'ssid');
		$ssid = $_REQUEST['ssid'];
		if (isset($_REQUEST['key']))
			$key = $_REQUEST['key'];
		else
			$key = '';
		if (!isset($_REQUEST['encryption']))
			error(ERROR_MISSED_ARGUMENT, 'encryption');
		$encryption = $_REQUEST['encryption'];
		switch (strtoupper($encryption))
		{
			case "WPA1PSKWPA2PSK":
			case "WPA2PSK":
			case "WPA2":
			case "PSK2":
				$encryption = "WPA2PSK";
				break;
			case "WPA1PSK":
			case "WPA":
			case "WPA1":
			case "PSK":
				$encryption = "WPA1PSK";
				break;
			case "WEP":
				$encryption = "WEP";
				break;
			case "NONE":
				$encryption = "NONE";
				break;
			default:
				error(ERROR_INVALID_ARGUMENT, 'encryption');
		}

		if (($encryption != "NONE") && (strlen($key) == 0))
			error(ERROR_MISSED_ARGUMENT, 'key');
	
		if (!EMULATION)
		{
			$ssid = str_replace("'", "\\'", $ssid);
			$key = str_replace("'", "\\'", $key);
			if ($add) shell_exec("uci -q add wireless wifi-config");
			$r = trim(shell_exec("uci set wireless.@wifi-config[$id].ssid='$ssid' 2>&1"));
			$r2 = trim(shell_exec("uci set wireless.@wifi-config[$id].key='$key' 2>&1"));
			$r3 = trim(shell_exec("uci set wireless.@wifi-config[$id].encryption='$encryption' 2>&1"));
			if ($r == '' && $r2 == '' && $r3 == '')
				$r4 = trim(shell_exec("uci commit wireless.@wifi-config[$id] 2>&1"));
			else
				shell_exec("uci delete wireless.@wifi-config[$id]");

			$ok = $r == '' && $r2 == '' && $r3 == '' && $r4 == '';
			$result['result'] = $ok;
		} else {
			$config_file_name = SETTINGS_DIR . "/client_aps.json";
			$config_json = @file_get_contents($config_file_name);
			$aps = json_decode($config_json);
			$aps[$id] = array("ssid" => $ssid, "key" => $key, "encryption" => $encryption);
			file_put_contents($config_file_name, json_encode($aps));
			$result['result'] = true;
		}
		if ($ok) $result['id'] = $id;
		wifi_client_aps_get();
		//shell_exec('wifimanager --check');
	}

	function wifi_client_aps_delete()
	{
		global $result;
		if (!isset($_REQUEST['id']))
			error(ERROR_INVALID_ARGUMENT);
		$id = (int)$_REQUEST['id'];

		if (!EMULATION)
		{
			$r = trim(shell_exec("uci delete wireless.@wifi-config[$id] 2>&1"));
			$r2 = trim(shell_exec("uci commit wireless 2>&1"));
			$ok = $r == '' && $r2 == '';
			$result['result'] = $ok;
		} else {
			$config_file_name = SETTINGS_DIR . "/client_aps.json";
			$config_json = @file_get_contents($config_file_name);
			$aps = json_decode($config_json);
			if (isset($aps[$id]))
			{
				$result['result'] = true;
				unset($aps[$id]);
				$newaps = array();
				foreach($aps as $ap) $newaps[] = $ap;
				file_put_contents($config_file_name, json_encode($newaps));
			} else $result['result'] = false;
		}
		wifi_client_aps_get();
	}
*/

	function wifi_ap_connect()
	{
		global $result;
		if (!isset($_REQUEST['ssid']))
			error(ERROR_MISSED_ARGUMENT, 'ssid');
		$ssid = $_REQUEST['ssid'];
		if (isset($_REQUEST['key']))
			$key = $_REQUEST['key'];
		else
			$key = '';
		if (!isset($_REQUEST['encryption']))
			error(ERROR_MISSED_ARGUMENT, 'encryption');
		$encryption = $_REQUEST['encryption'];
		switch (strtoupper($encryption))
		{
			case "WPA1PSKWPA2PSK":
			case "WPA2PSK":
			case "WPA2":
			case "PSK2":
				$encryption = "WPA2PSK";
				break;
			case "WPA1PSK":
			case "WPA":
			case "WPA1":
			case "PSK":
				$encryption = "WPA1PSK";
				break;
			case "WEP":
				$encryption = "WEP";
				break;
			case "NONE":
				$encryption = "NONE";
				break;
			default:
				error(ERROR_INVALID_ARGUMENT, 'encryption');
		}

		if (($encryption != "NONE") && (strlen($key) == 0))
			error(ERROR_MISSED_ARGUMENT, 'key');
	
		if (!EMULATION)
		{
			$ssid = str_replace("'", "\\'", $ssid);
			$key = str_replace("'", "\\'", $key);
			$r .= trim(shell_exec("uci set wireless.@wifi-iface[0].ApCliSsid='$ssid' 2>&1"));
			$r .= trim(shell_exec("uci set wireless.@wifi-iface[0].ApCliPassWord='$key' 2>&1"));
			$r .= trim(shell_exec("uci set wireless.@wifi-iface[0].ApCliAuthMode='$encryption' 2>&1"));
			$r .= trim(shell_exec("uci set wireless.@wifi-iface[0].ApCliEnable=1 2>&1"));
			$r .= trim(shell_exec("uci commit wireless.@wifi-config[0] 2>&1"));
			header("Connection: close");
			shell_exec("( sleep 2 ; wifimanager ) > /dev/null 2>/dev/null &");
			$result['result'] = $r == '';
		} else {
			$config_file_name = SETTINGS_DIR . "/client_ap.json";
			$config_json = @file_get_contents($config_file_name);
			$ap = array("ssid" => $ssid, "key" => $key, "encryption" => $encryption);
			file_put_contents($config_file_name, json_encode($ap));
			$result['result'] = true;
		}
	}

	function wifi_current_ap()
	{
		global $result;
		if (!EMULATION)
		{
			$result["ssid"] = trim(shell_exec("uci -q get wireless.@wifi-iface[0].ApCliSsid"));
			$result["encryption"] = trim(shell_exec("uci -q get wireless.@wifi-iface[0].ApCliAuthMode"));
			$result["key"] = trim(shell_exec("uci -q get wireless.@wifi-iface[0].ApCliPassWord"));
		} else {
			$config_file_name = SETTINGS_DIR . "/client_ap.json";
			$config_json = @file_get_contents($config_file_name);
			$ap = json_decode($config_json);
			$result["ssid"] = $ap->ssid;
			$result["encryption"] = $ap->encryption;
			$result["key"] = $ap->key;
		}
	}

	function wifi_restart()
	{
		global $result;
		if (!EMULATION)
		{
			shell_exec("ubus call network.interface.wwan down");
			sleep(1);
	            	shell_exec("wifi");
			sleep(1);
			shell_exec("ubus call network.interface.wwan up");
			sleep(1);
			shell_exec("wifimanager");
		} else sleep(3);
		$result['result'] = true;
	}
?>
