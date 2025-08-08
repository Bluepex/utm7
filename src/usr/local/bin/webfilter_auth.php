#!/usr/local/bin/php -f
<?php
/* $Id$ */
/*
	webfilter_auth

	Copyright (C) 2012 BluePex Security Solutions
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.

	DISABLE_PHP_LINT_CHECKING
*/
/*
	pfSense_BUILDER_BINARIES:
	pfSense_MODULE:	webfilter
*/
/*
 * WebFilter calls this script to authenticate a user
 * based on a username and password. We lookup these
 * in our config.xml file and check the credentials.
 */

require_once("config.inc");
require_once("auth.inc");

if (! defined("STDIN")) {
	define("STDIN", fopen("php://stdin", "r"));
}
if (! defined("STDOUT")) {
	define("STDOUT", fopen("php://stdout", "w"));
}

$start = microtime(true);

while (!feof(STDIN)) {

	$line = trim(fgets(STDIN));
	$fields = explode(' ', $line);
	$username = rawurldecode($fields[0]); //1738
	$password = rawurldecode($fields[1]); //1738

	# if username or password are empty ignore them
	if(!$username || !$password) {
		continue;
	}

	$authserver = $config['system']['webgui']['authmode'];
	$authcfg = auth_get_authserver($authserver);

	if(!authenticate_user($username, $password, $authcfg)) {
		fwrite(STDOUT, "ERR\n");
		syslog(LOG_WARNING, "WEBFILTER: user {$username} could not authenticate.\n");
	} else {
		fwrite(STDOUT, "OK\n");
		syslog(LOG_WARNING, "WEBFILTER: user {$username} authenticated\n");
	}
}
?>
