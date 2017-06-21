<?php
	define("EMULATION", 0);
	define("HOME_DIR", "/usr/share/breadmaker/");
	define("SETTINGS_DIR", HOME_DIR . "settings/");
	define("TOKEN_DIR", "/tmp/");
	define("TOKEN_LIFETIME", 3600);
	define("STATS_DIR", "/tmp/");
	define("PROGRAMS_COUNT", 7);
	define("CRUSTS_COUNT", 3);
	define("LOG_SIZE", 500);
	if (!EMULATION)
	{
		define("UART_OUT", "/dev/ttyS1");
		define("UART_IN", "/dev/ttyS1");
	} else {
		define("UART_OUT", "/tmp/breadmaker_to_device");
		define("UART_IN", "/tmp/breadmaker_from_device");
	}
	define("UART_SETTINGS", "406:0:8bf:b30:3:1c:7f:8:64:2:0:0:11:13:1a:0:12:f:17:16:4:0:0:0:0:0:0:0:0:0:0:0:0:0:0:0");
	define("PIN_RESET", 11);
	define("PIN_SCK", 15);
	define("PIN_MOSI", 17);
	define("PIN_MISO", 16);
	define("DEFAULT_PASSWORD", "breadtime");
?>