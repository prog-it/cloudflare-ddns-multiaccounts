<?php

/**
* Script to display remote IP address
*
* @copyright	2017 progit
* @link 		https://github.com/prog-it/cloudflare-ddns-multiaccounts
*
* Examples:
* http://example.com/ip.php 	-> Current IP Address: 123.123.123.123
* http://example.com/ip.php?raw -> 123.123.123.123
*
*/

function getIP() {
	$ip = $_SERVER['REMOTE_ADDR'];
	if (!empty($_SERVER['HTTP_CLIENT_IP']))	{
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	}
	else if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}
	return $ip;
}

if (isset($_GET['raw'])) {
	header('Content-Type: text/plain');
	echo getIP();
	exit;
}

?>
<html><head><title>Current IP Check</title></head><body>Current IP Address: <?php echo getIP(); ?></body></html>