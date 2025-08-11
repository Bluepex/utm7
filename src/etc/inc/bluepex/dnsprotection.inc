<?php
/*
 * dnsprotection.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2006 Peter Allgeyer
 * Copyright (c) 2008-2019 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally part of m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("util.inc");

function disabledServiceDNSprotection() {
    [$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
	if ($pidProcess > 0) {
        file_put_contents("/etc/enableDNSProtection", 'false');
        mwexec_bg("kill -9 {$pidProcess}");
        sleep(5);
    }
}

function enableServiceDNSProtection() {
    [$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
	if (($processCounter == 0) && empty($serviceProcess)) {
        file_put_contents("/etc/enableDNSProtection", 'true');
		file_put_contents("/tmp/dnsProtectionExecutation", "/usr/local/pkg/dnsprotect/luna-dns /usr/local/pkg/dnsprotect/config.yml >&1 &");
		shell_exec("chmod 755 /tmp/dnsProtectionExecutation");
		mwexec_bg("/bin/sh /tmp/dnsProtectionExecutation");
		sleep(5);
	}
}

function fileExistsEtcDNSProtection() {
    if (!file_exists('/etc/enableDNSProtection')) {
        file_put_contents("/etc/enableDNSProtection", 'false');
    }
}

function returnStatusProcessDNSProtection() {
    $processCounter = intval(trim(shell_exec("sockstat | grep ':53 ' | grep -v luna-dns | grep -vc grep")));
    $serviceProcess = trim(shell_exec("sockstat | grep ':53 ' | awk -F' ' '{print $2}' | head -n1"));
    $pidProcess = intval(trim(shell_exec("pgrep -f '/usr/local/pkg/dnsprotect/luna-dns'")));
    fileExistsEtcDNSProtection();
    $haveRunning=trim(file_get_contents("/etc/enableDNSProtection"));
    return [$processCounter, $serviceProcess, $pidProcess, $haveRunning];
}