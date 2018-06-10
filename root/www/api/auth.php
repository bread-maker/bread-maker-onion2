<?php
	require_once('config.php');
	require_once('consts.php');
	require_once('misc.php');

	function auth_check($password_only = false)
	{
		if (EMULATION && isset($_REQUEST['forceauth']))
		{
			// force auth for emulator
		}
		else if (isset($_REQUEST['password']))
		{
			$password = $_REQUEST['password'];
			if (!EMULATION)
			{
				$presult = trim(shell_exec('check_password root "' . str_replace('"', '\"', $password) . '"'));
				if ($presult != 'Password is VALID')
					error(ERROR_INVALID_PASSWORD);
			} else {
				$pw_file_name = SETTINGS_DIR . "/password";
				if ($password != @file_get_contents($pw_file_name))
					error(ERROR_INVALID_PASSWORD);
			}
		}
		else if (!$password_only && isset($_REQUEST['token']))
		{
			$token = $_REQUEST['token'];
			$token_file = TOKEN_DIR . '/token_' . $token;
			if (!file_exists($token_file)) error(ERROR_INVALID_TOKEN);
			$age = time() - filemtime($token_file);
			if ($age > TOKEN_LIFETIME) error(ERROR_TOKEN_EXPIRED);
			touch($token_file);
		} else error(ERROR_AUTH_REQURED);
	}

	function auth()
	{
		global $result;
		$token = md5(rand());
		touch(TOKEN_DIR . '/token_' . $token);
		$result['token'] = $token;		
	}

	function logout()
	{
		global $result;
		if (isset($_REQUEST['token']))
			$token = $_REQUEST['token'];
		else
			error(ERROR_MISSED_ARGUMENT, 'token');
		$token_file = TOKEN_DIR . '/token_' . $token;
		if (!file_exists($token_file)) error(ERROR_INVALID_TOKEN);
		unlink($token_file);
		$result['result'] = true;
	}

	function passwd()
	{
		global $result;
		auth_check(true);
		$password = $_REQUEST['new_password'];
		if (!EMULATION)
		{
			$password = str_replace('"', '\"', $password);
			$r = shell_exec('sh -c "printf '.escapeshellarg($password.'\n'.$password).' | passwd" 2>&1');
			if (strpos($r, 'password for root changed') > 0)
				$result['result'] = true;
			else
				$result['result'] = false;
		} else {
			$pw_file_name = SETTINGS_DIR . "/password";
			@file_put_contents($pw_file_name, $password);
			$result['result'] = true;
		}
	}

?>
