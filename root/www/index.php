<?php
	session_start();
	require_once('auth.php');

	$pages = array(
		'auth' => array('file' => 'auth', 'content' => 'auth', 'body_header' => 'header_auth'),
		'status' => array('file' => 'status', 'content' => 'status', 'body_header' => 'header_status'),
		'control' => array('file' => 'control', 'content' => 'control', 'body_header' => 'header_control', 'script' => 'script_control', 'preexec' => 'preexec_control'),
		'wifi' => array('file' => 'wifisettings', 'content' => 'wifi', 'body_header' => 'header_wifi', 'script' => 'script_wifi', 'preexec' => 'preexec_wifi'),
		'wifi.status' => array('file' => 'wifisettings', 'preexec' => 'wifi_status_json'),
		'wifi.scan' => array('file' => 'wifisettings', 'preexec' => 'wifi_scan_json'),
		'wifi.connect_ap' => array('file' => 'wifisettings', 'preexec' => 'wifi_connect_ap'),
		'settings' => array('file' => 'settings', 'content' => 'settings', 'body_header' => 'header_settings', 'preexec' => 'preexec_settings'),
	);

	$alert = '';
	$config = json_decode(file_get_contents('config.json'), true);
	$language = $config['language'];
	if (!file_exists("languages/$language.php"))
		$language = 'english';
	require_once("languages/$language.php");
	$page = isset($_REQUEST['page']) ? strtolower($_REQUEST['page']) : 'status';
	if ($page == 'logout')
	{
		$_SESSION['password'] = '';
		header('Location: /');
		die();
	}
	$auth_ok = check_password();
	if (!$auth_ok && isset($_REQUEST['password']))
		$alert = $lng['invalid_password'];
	if (!$auth_ok && ($page != 'status'))
		$page = 'auth';

	if (!array_key_exists($page, $pages))
		die('Invalid page: '.$page);

	try
	{
		require_once($pages[$page]['file'] . '.php');
	}
	catch (Exception $e)
	{
		die($lng['error'].': '.$e->getMessage());
	}

	if (isset($pages[$page]['preexec']))
		$pages[$page]['preexec']();

	require_once('api/stats.php');
	$last_stats = get_stats();
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Script-Type" content="text/javascript" />
<link rel="stylesheet" type="text/css" media="screen" href="cascade.css" />
<title>Breadmaker</title>
<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">
<link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
<meta name="msapplication-TileColor" content="#fdd549">
<meta name="theme-color" content="#ffffff">
<meta name="viewport" content="width=750">
<?php if ($alert) echo('<script>alert("'.str_replace("\n","\\n",str_replace("\r","\\r",str_replace('"','\"',str_replace("\\","\\\\",$alert)))).'");</script>');?>
<script>var last_stats = <?php echo(json_encode($last_stats)) ?>;</script>
<script src="strings.php"></script>
<script src="status.js"></script>
<?php
	try
	{
		if (isset($pages[$page]['script']))
			$pages[$page]['script']();
	}
	catch (Exception $e)
	{
		die('Error: '.$e->getMessage());
	}
?>
</head>
<?php
	try
	{
		$pages[$page]['body_header']();
	}
	catch (Exception $e)
	{
		die('Error: '.$e->getMessage());
	}
?>
<div id="menubar">
<div class="hostinfo" style="width: 95%">
	Breadmaker
	<span style="display:none">
		| <?=$lng['baking_program']?>:
		<span id="lb_header_program"></span>
	</span>
	<span style="display:none">
		| <?=$lng['state']?>:
		<span id="lb_header_state"></span>
	</span>
	<span style="display:none">
		| IP:
		<span id="lb_header_ip"></span>
	</span>
<?php
	if ($auth_ok)
	{
?>
	<span style="float:right; cursor:pointer;" onclick="window.location.href = '/?page=logout'"><?=$lng['logout']?></span>
<?php
	}
?>
</div>
<div class="clear"></div>
</div>
<div id="maincontainer">
	<div class="tabmenu1">
	<ul class="tabmenu l1">
		
			<li<?php if ($page == 'status') echo(' class="active"');?>>
				<a id="tab_status" href="?page=status"><?=$lng['status']?></a>
			</li>
			<li<?php if ($page == 'control') echo(' class="active"');?>>
				<a id="tab_control" href="/?page=control"><?=$lng['control']?></a>
			</li>
			<li<?php if ($page == 'wifi') echo(' class="active"');?>>
				<a id="tab_wifi" href="/?page=wifi"><?=$lng['wifi']?></a>
			</li>		
			<li<?php if ($page == 'settings') echo(' class="active"');?>>
				<a id="tab_misc" href="/?page=settings"><?=$lng['settings']?></a>
			</li>
	</ul>
	<br style="clear:both" />
	</div>	

	<div id="maincontent">
		<noscript>
			<div class="errorbox">
				<strong>Java Script required!</strong><br />
				You must enable Java Script in your browser.
			</div>
		</noscript>
		<div id="content">
<?php
	try
	{
		$pages[$page]['content']();
	}
	catch (Exception $e)
	{
		die('Error: '.$e->getMessage());
	}
?>
		</div>
	</div>
	<div id="footer">Breadmaker &copy; Cluster, 2018</div>
</div>
</body>
</html>

