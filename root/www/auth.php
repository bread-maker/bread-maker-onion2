<?php
	function check_password()
	{
		global $page;
		if (isset($_REQUEST['password']))
		{
			$_SESSION['password'] = $_REQUEST['password'];
		}
		$t = shell_exec('/usr/bin/check_password root '.escapeshellarg($_SESSION['password']));
		if (strpos($t, 'is VALID') === false) 
			return false;
		return true;
	}

	function header_auth()
	{
		echo('<body class="lang_en" onload="status_init();">');
	}

	function auth()
	{
		global $lng;
?>
<div align="center"><form enctype="multipart/form-data" method="POST">
<?php
    foreach($_REQUEST as $k => $v)
	if ($k != 'password' && $k != 'PHPSESSID' && $k != 'page')
	    echo("<input type='hidden' name='$k' value='" . htmlspecialchars($v, ENT_QUOTES|ENT_HTML401) . "'>");
?>
<br /><br />
<table class="border" style="width: 500px">
<tr><td>
<table class="noborder" style="width: 100%">
<tr><td><?=$lng['enter_password']?>:</td><td rowspan="2" style="width: 1%; text-align: right; vertical-align: bottom"><input type="submit" value="OK" style="width: 30px;"/></td></tr>
<tr><td><input type="password" name="password" size="20" /></td></tr>
</table>
</td></tr>
</table><br />
</form></div>
<?php
	}
?>
