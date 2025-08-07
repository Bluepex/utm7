#!/usr/local/bin/php -f
<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once("globals.inc");
require_once("util.inc");

define("PIDFILE", $g['varrun_path']."/webfilter_rotate_uncategorized.pid");
define("DEBUG", 0);

// Log files
define("LOG", $g['varlog_path']."/uncategorized.log");
define("TMPLOG", '/var/tmp/uncategorized.log');
define("ERRORLOG", $g['varlog_path']."/uncategorized-error.log");

// Netfilter constants 
define("LINES", 2000);
define("URL", 'http://feedback.netfilter.com.br/bluepex');
define("PORT", 80);
define("USER", "bluepex");
define("PASS", "FdRQ9vDYEuvGG9CA8MEFK5sy");
define("SOCKET", $g['varrun_path']."/netfilter/interface.socket");

if($g["platform"] != "BluePexUTM")
	exit;
elseif(file_exists(LOG) && file_exists(SOCKET)) {

	pid_checkClean();

	if(connNetfilter()) {

		rename(LOG, TMPLOG);
		touch(LOG);
		chmod(LOG, 0666);

		$total_lines = count(file(TMPLOG));
		$suffix = 1;

		if($total_lines > 0) {
			while(true) {

				$endline = ($suffix*LINES);
				$startline = ($endline-LINES+1);

				$cmd = "/usr/bin/sed '".$startline.",".$endline." !d' ".TMPLOG." | /usr/bin/egrep '^HTTP(S)?://' | ".
						"/usr/local/bin/check_category -s '".SOCKET."' -c ALL";

				$gb = exec($cmd, $out, $error);
				if($error == 0 && !empty($out)) {
					$urls = implode("\n", $out);
					if(!submit_urls($urls))
						writeErrorLog(gettext("An error ocurred while sending URLs to Netfilter!"));
				}


				if( $endline >= $total_lines ) {
					pid_checkClean("clean");
					if(is_process_running("interface"))
						exec('/usr/local/etc/rc.d/interface reload_local');
					break;
				}

				$suffix++;
			}
		}
	}
} else
	writeErrorLog(gettext("Logging and socket files not exists: ").LOG.", ".SOCKET);

function writeErrorLog($msg) {
	global $ERRORLOG;

	$msg = "[ ".date("Y-m-d H:i:s")." ]: {$msg}\n";

	if(!file_exists(ERRORLOG))
		touch(ERRORLOG);

	$openfile = @fopen(ERRORLOG, "a+");

	if(is_resource($openfile)) {
		fwrite($openfile, $msg);
		fclose($openfile);
	}
}

function connNetfilter() {
	global $URL, $PORT;

	$url = parse_url(URL);
	$open = @fsockopen($url['host'],PORT, $errno, $errstr, 20);

	if (is_resource($open)) {
		fclose($open);
		return true;
	} else
		writeErrorLog(gettext("Not was possible to connect server: ").$errno.": ".$errstr);
}

function pid_checkClean( $opt="" ) {
        global $PIDFILE, $TMPLOG;

	if($opt == "clean") {
		unlink_if_exists(PIDFILE);
		unlink_if_exists(TMPLOG);
	} else {

		if(file_exists(PIDFILE)) {
			exec("/bin/cat ".PIDFILE." | /usr/bin/xargs ps", $out, $error);
			if (!$error) {
				writeErrorLog(gettext("Uncategorized Logs Rotate is already running"));
				exit;
			} else
				unlink(PIDFILE);
		}

		$pid = getmypid();
		file_put_contents(PIDFILE, $pid);
	}
}

function submit_urls($urls) {
	global $URL, $USER, $PASS, $DEBUG;

	$post = "urls=".urlencode($urls);
	$header = array(
			'Content-Length: ' . strlen($urls),
			'Content-Type: application/x-www-form-urlencoded'
			);
 
	$ch = curl_init();
	if(is_resource($ch)) {
		curl_setopt($ch, CURLOPT_URL, URL);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_setopt($ch, CURLOPT_USERPWD, USER.":".PASS);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header); 

		if(DEBUG == 1)
			curl_setopt($ch, CURLOPT_VERBOSE, TRUE);

		curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if($http_code == 200)
			return true;
	}
}

?>

