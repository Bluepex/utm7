<?php
/*
 * diag_backup.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally based on m0n0wall (http://m0n0.ch/wall)
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

##|+PRIV
##|*IDENT=page-diagnostics-backup-restore
##|*NAME=Diagnostics: Backup & Restore
##|*DESCR=Allow access to the 'Diagnostics: Backup & Restore' page.
##|*WARN=standard-warning-root
##|*MATCH=diag_backup.php*
##|-PRIV

/* Allow additional execution time 0 = no limit. */
ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');

/* omit no-cache headers because it confuses IE with file downloads */
$omit_nocacheheaders = true;
require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("pkg-utils.inc");
require_once("bp_webservice.inc");

$rrddbpath = "/var/db/rrd";
$rrdtool = "/usr/bin/nice -n20 /usr/local/bin/rrdtool";

/* Backup in Cloud */
if (isset($config['backup_cloud_enabled'])) {
	$backup_cloud_enabled = $config['backup_cloud_enabled'];
} else {
	$config['backup_cloud_enabled'] = false;
}

$cloud_enabled = false;
$bkp_date = "";

if (isset($backup_cloud_enabled)) {
	$isEnabled = do_webservice_request('backup', 'check-backup-enabled');

	if ($isEnabled->status == 'enabled') {
		$cloud_enabled = true;
		$resp = do_webservice_request('backup', 'list-backups-date');

		if ($resp->status == 'ok') {
			if (!empty($resp->data->backups)) {
				if (is_array($resp->data->backups))
					$bkp_date = $resp->data->backups;
			}
		}
	}
}

function backup_config_section_fapp($section_name) {
	global $config;

	if ($section_name == "firewallapp") {
		$new_section = &$config['system'][$section_name];
		/* generate configuration XML */
		$xmlconfig = dump_xml_config($new_section, $section_name);
		$xmlconfig = str_replace("<?xml version=\"1.0\"?>", "", $xmlconfig);
		return $xmlconfig;	
	}
	if ($section_name == "suricata") {
		$new_section = &$config['installedpackages'][$section_name];
		/* generate configuration XML */
		$xmlconfig = dump_xml_config($new_section, $section_name);
		$xmlconfig = str_replace("<?xml version=\"1.0\"?>", "", $xmlconfig);
		return $xmlconfig;	
	}
	
}

function restore_config_section_fapp($section_name, $new_contents) {
	global $config, $g;
	$fout = fopen("{$g['tmp_path']}/tmpxml", "w");
	fwrite($fout, $new_contents);
	fclose($fout);

	$xml = parse_xml_config($g['tmp_path'] . "/tmpxml", null);

	if ($xml[$section_name]) {
		$section_xml = $xml[$section_name];
	} else {
		$section_xml = -1;
	}

	@unlink($g['tmp_path'] . "/tmpxml");
	if ($section_xml === -1) {
		return false;
	}
	if ($section_name == "firewallapp") {
		$config['system'][$section_name] = &$section_xml;
		if (file_exists("{$g['tmp_path']}/config.cache")) {
			unlink("{$g['tmp_path']}/config.cache");
		}
	}
	if ($section_name == "suricata") {
		$config['installedpackages'][$section_name] = &$section_xml;
		if (file_exists("{$g['tmp_path']}/config.cache")) {
			unlink("{$g['tmp_path']}/config.cache");
		}
	}

	write_config(sprintf(gettext("Restored %s of config file (maybe from CARP partner)"), $section_name));
	disable_security_checks();
	return true;
}

function remove_bad_chars($string) {
	return preg_replace('/[^a-z_0-9]/i', '', $string);
}

function check_and_returnif_section_exists($section) {
	global $config;
	if (is_array($config[$section])) {
		return true;
	}
	return false;
}

if ($_POST['apply']) {
	ob_flush();
	flush();
	clear_subsystem_dirty("restore");
	exit;
}

if ($_POST) {
	unset($input_errors);
	if ($_POST['restore']) {
		$mode = "restore";
	} else if ($_POST['reinstallpackages']) {
		$mode = "reinstallpackages";
	} else if ($_POST['clearpackagelock']) {
		$mode = "clearpackagelock";
	} else if ($_POST['download']) {
		$mode = "download";
	}
	if ($_POST["nopackages"] <> "") {
		$options = "nopackages";
	}
	if ($mode) {
		if ($mode == "download") {
			if ($_POST['encrypt']) {
				if (!$_POST['encrypt_password']) {
					$input_errors[] = gettext("A password for encryption must be supplied and confirmed.");
				}
			}

			if (!$input_errors) {

				//$lockbckp = lock('config');

				$host = "{$config['system']['hostname']}.{$config['system']['domain']}";
				$name = "config-{$host}-".date("YmdHis").".xml";
				$data = "";

				if (1 == 1) {
					if (!$_POST['backuparea']) {
						/* backup entire configuration */
						$data = file_get_contents("{$g['conf_path']}/config.xml");
					} else {
						/* backup specific area of configuration */
						$data = backup_config_section_fapp($_POST['backuparea']);
						if ($_POST['backuparea'] == "suricata") {
							$name = "firewallapp_rules"."-"."{$name}";
						} else {
							$name = "{$_POST['backuparea']}-{$name}";
						}
						
					}
					$data = preg_replace('/\t*<installedpackages>.*<\/installedpackages>\n/sm', '', $data);
				} else {
					if (!$_POST['backuparea']) {
						/* backup entire configuration */
						$data = file_get_contents("{$g['conf_path']}/config.xml");
					} else if ($_POST['backuparea'] === "rrddata") {
						$data = rrd_data_xml();
						$name = "{$_POST['backuparea']}-{$name}";
					} else {
						/* backup specific area of configuration */
						$data = backup_config_section_fapp($_POST['backuparea']);
						$name = "{$_POST['backuparea']}-{$name}";
					}
				}

				//unlock($lockbckp);

				/*
				 *	Backup RRD Data
				 */
				if ($_POST['backuparea'] !== "rrddata" && !$_POST['donotbackuprrd']) {
					$rrd_data_xml = rrd_data_xml();
					$closing_tag = "</" . $g['xml_rootobj'] . ">";

					/* If the config on disk had rrddata tags already, remove that section first.
					 * See https://redmine.pfsense.org/issues/8994 */
					$data = preg_replace("/<rrddata>.*<\\/rrddata>/", "", $data);
					$data = preg_replace("/<rrddata\\/>/", "", $data);

					$data = str_replace($closing_tag, $rrd_data_xml . $closing_tag, $data);
				}

				if ($_POST['encrypt']) {
					$data = encrypt_data($data, $_POST['encrypt_password']);
					tagfile_reformat($data, $data, "config.xml");
				}

				$size = strlen($data);
				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename={$name}");
				header("Content-Length: $size");
				if (isset($_SERVER['HTTPS'])) {
					header('Pragma: ');
					header('Cache-Control: ');
				} else {
					header("Pragma: private");
					header("Cache-Control: private, must-revalidate");
				}

				while (ob_get_level()) {
					@ob_end_clean();
				}
				echo $data;
				@ob_end_flush();
				exit;
			}
		}

		if ($mode == "restore") {
			if ($_POST['decrypt']) {
				if (!$_POST['decrypt_password']) {
					$input_errors[] = gettext("A password for decryption must be supplied and confirmed.");
				}
			}

			/* Backuo in Cloud */
			$local_cloud = $_POST['backup_file'];
			if ($local_cloud == "local") {
				if (is_uploaded_file($_FILES['conffile']['tmp_name'])) {
					$data = file_get_contents($_FILES['conffile']['tmp_name']);
				} else {
					$input_errors[] = gettext("The configuration could not be restored (file upload error).");
				}
			} else {
				$bkp_id = $_POST['backup_date'];
				if (!empty($bkp_id)) {
					$resp = do_webservice_request('backup', 'get-backup', array('backup_id' => $bkp_id));

					if ($resp->status == 'ok') {
						if (!empty($resp->data->backup->backup)) {
							$data = base64_decode($resp->data->backup->backup);

							file_put_contents(TMPFILECLOUD, $data);

							exec("/usr/local/bin/xmllint --noout ".TMPFILECLOUD, $out, $err);

							if ($err != 0) {
								$input_errors[] = gettext("The configuration could not be restored. Backup file is corrupted.");
							}
						} else {
							$input_errors[] = gettext("The configuration could not be restored. Backup file is empty!.");
						}
					} else {
						$input_errors[] = gettext("Error to recover configurarion backup file.");
					}
				} else {
					$input_errors[] = gettext("Select backup date to restore!");
				}
			}

			if (!$input_errors) {
				
				if (empty($data)) {
					if ($local_cloud == "local") {
						log_error(sprintf(gettext("Warning, could not read file %s"), $_FILES['conffile']['tmp_name']));
					} else {
						log_error(sprintf(gettext("Warning, could not read file in cloud date %s"), $_POST['backup_date']));
						unlink_if_exists(TMPFILECLOUD);
					}
						return 1;
				}

				if ($_POST['decrypt']) {
					if (!tagfile_deformat($data, $data, "config.xml")) {
						$input_errors[] = gettext("The uploaded file does not appear to contain an encrypted BluePexUTM configuration.");
						return 1;
					}
					$data = decrypt_data($data, $_POST['decrypt_password']);
				}

				if (stristr($data, "<m0n0wall>")) {
					log_error(gettext("Upgrading m0n0wall configuration to BluePexUTM."));
					/* m0n0wall was found in config.  convert it. */
					$data = str_replace("m0n0wall", "BluePexUTM", $data);
					$m0n0wall_upgrade = true;
				}

				/* If the config on disk had empty rrddata tags, remove them to
				 * avoid an XML parsing error.
				 * See https://redmine.pfsense.org/issues/8994 */
				$data = preg_replace("/<rrddata><\\/rrddata>/", "", $data);
				$data = preg_replace("/<rrddata\\/>/", "", $data);

				if ($_POST['restorearea']) {
					/* restore a specific area of the configuration */
					if (!stristr($data, "<" . $_POST['restorearea'] . ">")) {
						$input_errors[] = gettext("You have selected to restore an area but we could not locate the correct xml tag.");
					} else {
						if (!restore_config_section_fapp($_POST['restorearea'], $data)) {
							$input_errors[] = gettext("An area to restore was selected but the correct xml tag could not be located.");
						} else {
							if ($config['rrddata']) {
								restore_rrddata();
								unset($config['rrddata']);
								unlink_if_exists("{$g['tmp_path']}/config.cache");
								write_config(sprintf(gettext("Unset RRD data from configuration after restoring %s configuration area"), $_POST['restorearea']));
								add_base_packages_menu_items();
								convert_config();
							}
							filter_configure();
							$savemsg = gettext("The configuration area has been restored.  You may need to reboot the firewall.");
						}
					}
				} else {
					if (!stristr($data, "<" . $g['xml_rootobj'] . ">")) {
						$input_errors[] = sprintf(gettext("You have selected to restore the full configuration but we could not locate a %s tag."), $g['xml_rootobj']);
					} else {
						/* restore the entire configuration */
						if ($local_cloud == "local") {
							file_put_contents($_FILES['conffile']['tmp_name'], $data);
							$config_install = config_install($_FILES['conffile']['tmp_name']);
						} else {
							$config_install = config_install(TMPFILECLOUD);
						}
						
						if ($config_install == 0) {
							/* Save current pkg repo to re-add on new config */
							unset($pkg_repo_conf_path);
							if (isset($config['system']['pkg_repo_conf_path'])) {
								$pkg_repo_conf_path = $config['system']['pkg_repo_conf_path'];
							}

							/* this will be picked up by /index.php */
							mark_subsystem_dirty("restore");
							touch("/conf/needs_package_sync");
							/* remove cache, we will force a config reboot */
							if (file_exists("{$g['tmp_path']}/config.cache")) {
								unlink("{$g['tmp_path']}/config.cache");
							}
							$config = parse_config(true);

							/* Restore previously pkg repo configured */
							$pkg_repo_restored = false;
							if (isset($pkg_repo_conf_path)) {
								$config['system']['pkg_repo_conf_path'] =
								    $pkg_repo_conf_path;
								$pkg_repo_restored = true;
							} elseif (isset($config['system']['pkg_repo_conf_path'])) {
								unset($config['system']['pkg_repo_conf_path']);
								$pkg_repo_restored = true;
							}

							if ($pkg_repo_restored) {
								write_config(gettext("Removing pkg repository set after restoring full configuration"));
								pkg_update(true);
							}

							if (file_exists("/boot/loader.conf")) {
								$loaderconf = file_get_contents("/boot/loader.conf");
								if (strpos($loaderconf, "console=\"comconsole") ||
								    strpos($loaderconf, "boot_serial=\"YES")) {
									$config['system']['enableserial'] = true;
									write_config(gettext("Restore serial console enabling in configuration."));
								}
								unset($loaderconf);
							} else if (file_exists("/boot/loader.conf.local")) {
								$loaderconf = file_get_contents("/boot/loader.conf.local");
								if (strpos($loaderconf, "console=\"comconsole") ||
								    strpos($loaderconf, "boot_serial=\"YES")) {
									$config['system']['enableserial'] = true;
									write_config(gettext("Restore serial console enabling in configuration."));
								}
								unset($loaderconf);
							}
							/* extract out rrd items, unset from $config when done */
							if ($config['rrddata']) {
								restore_rrddata();
								unset($config['rrddata']);
								unlink_if_exists("{$g['tmp_path']}/config.cache");
								write_config(gettext("Unset RRD data from configuration after restoring full configuration"));
								add_base_packages_menu_items();
								convert_config();
							}
							if ($m0n0wall_upgrade == true) {
								if ($config['system']['gateway'] <> "") {
									$config['interfaces']['wan']['gateway'] = $config['system']['gateway'];
								}
								unset($config['shaper']);
								/* optional if list */
								$ifdescrs = get_configured_interface_list(true, true);
								/* remove special characters from interface descriptions */
								if (is_array($ifdescrs)) {
									foreach ($ifdescrs as $iface) {
										$config['interfaces'][$iface]['descr'] = remove_bad_chars($config['interfaces'][$iface]['descr']);
									}
								}
								/* check for interface names with an alias */
								if (is_array($ifdescrs)) {
									foreach ($ifdescrs as $iface) {
										if (is_alias($config['interfaces'][$iface]['descr'])) {
											$origname = $config['interfaces'][$iface]['descr'];
											update_alias_name($origname . "Alias", $origname);
										}
									}
								}
								unlink_if_exists("{$g['tmp_path']}/config.cache");
								// Reset configuration version to something low
								// in order to force the config upgrade code to
								// run through with all steps that are required.
								$config['system']['version'] = "1.0";
								// Deal with descriptions longer than 63 characters
								for ($i = 0; isset($config["filter"]["rule"][$i]); $i++) {
									if (count($config['filter']['rule'][$i]['descr']) > 63) {
										$config['filter']['rule'][$i]['descr'] = substr($config['filter']['rule'][$i]['descr'], 0, 63);
									}
								}
								// Move interface from ipsec to enc0
								for ($i = 0; isset($config["filter"]["rule"][$i]); $i++) {
									if ($config['filter']['rule'][$i]['interface'] == "ipsec") {
										$config['filter']['rule'][$i]['interface'] = "enc0";
									}
								}
								// Convert icmp types
								// http://www.openbsd.org/cgi-bin/man.cgi?query=icmp&sektion=4&arch=i386&apropos=0&manpath=OpenBSD+Current
								$convert = array('echo' => 'echoreq', 'timest' => 'timereq', 'timestrep' => 'timerep');
								foreach ($config["filter"]["rule"] as $ruleid => &$ruledata) {
									if ($convert[$ruledata['icmptype']]) {
										$ruledata['icmptype'] = $convert[$ruledata['icmptype']];
									}
								}
								$config['diag']['ipv6nat'] = true;
								write_config("Changes applied to interfaces related to m0n0wall");
								add_base_packages_menu_items();
								convert_config();
								$savemsg = gettext("The m0n0wall configuration has been restored and upgraded to BluePexUTM.");
								mark_subsystem_dirty("restore");
							}
							if (is_array($config['captiveportal'])) {
								foreach ($config['captiveportal'] as $cp) {
									if (isset($cp['enable'])) {
										/* for some reason ipfw doesn't init correctly except on bootup sequence */
										mark_subsystem_dirty("restore");
										break;
									}
								}
							}
							setup_serial_port();
							if (is_interface_mismatch() == true) {
								touch("/var/run/interface_mismatch_reboot_needed");
								clear_subsystem_dirty("restore");
								convert_config();
								header("Location: interfaces_assign.php");
								exit;
							}
							if (is_interface_vlan_mismatch() == true) {
								touch("/var/run/interface_mismatch_reboot_needed");
								clear_subsystem_dirty("restore");
								convert_config();
								header("Location: interfaces_assign.php");
								exit;
							}
						} else {
							$input_errors[] = gettext("The configuration could not be restored.");
						}
					}
				}
			}
		}

		if ($mode == "reinstallpackages") {
			header("Location: pkg_mgr_install.php?mode=reinstallall");
			exit;
		} else if ($mode == "clearpackagelock") {
			clear_subsystem_dirty('packagelock');
			$savemsg = "Package lock cleared.";
		}
	}
}

$id = rand() . '.' . time();

$mth = ini_get('upload_progress_meter.store_method');
$dir = ini_get('upload_progress_meter.file.filename_template');

function build_area_list($showall) {
	global $config;

	$areas = array(
		"firewallapp" => gettext("FirewallApp Profiles")
		);

	$list = array("suricata" => gettext("FirewallApp Rules"));

	if ($showall) {
		return($list + $areas);
	} else {
		foreach ($areas as $area => $areaname) {
			if ($area === "rrddata" || check_and_returnif_section_exists($area) == true) {
				$list[$area] = $areaname;
			}
		}

		return($list);
	}
}

$pgtitle = array(gettext("FirewallApp"), htmlspecialchars(gettext("Backup & Restore")));
$pglinks = array("./firewallapp/services.php", "@self");
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}

if (is_subsystem_dirty('restore')):
?>
	<br/>
	<form action="diag_reboot.php" method="post">
		<input name="Submit" type="hidden" value="Yes" />
		<?php print_info_box(gettext("The firewall configuration has been changed.") . "<br />" . gettext("The firewall is now rebooting.")); ?>
		<br />
	</form>
<?php
endif;

function build_backup_list() {
	global $bkp_date;
	$list = array();

	if ($bkp_date) {
		foreach ($bkp_date as $bkp) {
			$list[$bkp->id] = $bkp->created_at;
		}
	} else {
		$list = array("" => gettext("Backup not found!"));
	}
	return($list);
}

$tab_array = array();
$tab_array[] = array(htmlspecialchars(gettext("Backup & Restore")), true, "diag_backup.php");
display_top_tabs($tab_array);

$form = new Form(false);
$form->setMultipartEncoding();	// Allow file uploads

$section = new Form_Section('Backup Configuration');

$section->addInput(new Form_Select(
	'backuparea',
	'Backup area',
	'',
	build_area_list(true)
));

$section->addInput(new Form_Checkbox(
	'nopackages',
	'Skip packages',
	'Do not backup package information.',
	false
), true);

$section->addInput(new Form_Checkbox(
	'donotbackuprrd',
	'Skip RRD data',
	'Do not backup RRD data (NOTE: RRD Data can consume 4+ megabytes of config.xml space!)',
	true
), true);

$section->addInput(new Form_Checkbox(
	'encrypt',
	'Encryption',
	'Encrypt this configuration file.',
	false
));

$section->addInput(new Form_Input(
	'encrypt_password',
	'Password',
	'password',
	null
));

$group = new Form_Group('');
// Note: ID attribute of each element created is to be unique.  Not being used, suppressing it.
$group->add(new Form_Button(
	'download',
	'Download configuration as XML',
	null,
	'fa-download'
))->setAttribute('id')->addClass('btn-primary');

$section->add($group);
$form->add($section);

$section = new Form_Section('Restore Backup');

$section->addInput(new Form_StaticText(
	null,
	sprintf(gettext("Open a %s configuration XML file and click the button below to restore the configuration."), $g['product_name'])
));

$section->addInput(new Form_Select(
	'restorearea',
	'Restore area',
	'',
	build_area_list(true)
));

$group = new Form_Group(gettext('Backup File'));

$group->add(new Form_Checkbox(
	'backup_file',
	null,
	gettext('Local File'),
	true,
	'local'
))->displayAsRadio();

if ($cloud_enabled) {
	$group->add(new Form_Checkbox(
		'backup_file',
		null,
		gettext('Cloud File'),
		false,
		'cloud'
	))->displayAsRadio();
}

$section->add($group);

$section->addInput(new Form_Input(
	'conffile',
	'Configuration file',
	'file',
	null
));

$section->addInput(new Form_Checkbox(
	'decrypt',
	'Encryption',
	'Configuration file is encrypted.',
	false
));

$section->addInput(new Form_Input(
	'decrypt_password',
	'Password',
	'password',
	null,
	['placeholder' => 'Password']
));

if (!empty($bkp_date)) {
	$section->addInput(new Form_Select(
		'backup_date',
		gettext('Select Backup date to restore:'),
		'',
		build_backup_list()
	));
}

$group = new Form_Group('');
// Note: ID attribute of each element created is to be unique.  Not being used, suppressing it.
$group->add(new Form_Button(
	'restore',
	'Restore Configuration',
	null,
	'fa-undo'
))->setHelp('The firewall will reboot after restoring the configuration.')->addClass('btn-danger restore')->setAttribute('id');

$section->add($group);

$form->add($section);

print($form);
?>
<script type="text/javascript">
//<![CDATA[
events.push(function() {
	
	function hidden_backup_inputs(isCloud) {
		hideInput('conffile', isCloud);
		hideCheckbox('decrypt', isCloud);
		if ($('input[name="decrypt"]').is(':checked')) {
			hideInput('decrypt_passconf', isCloud);
			hideInput('decrypt_password', isCloud);
		}
		hideSelect('backup_date', !isCloud);
	}
	
	// Hide/show input elements depending on the 'backup_file' radio button setting
	function backup_file_change() {
		var mode = $("input[name=backup_file]:checked").val();
		
		if (mode == 'local') {
			hidden_backup_inputs(false);
		} else if (mode == 'cloud') {
			hidden_backup_inputs(true);
		}
	}

	// ------- Show/hide sections based on checkbox settings --------------------------------------

	function hideSections(hide) {
		hidePasswords();
	}

	function hidePasswords() {

		encryptHide = !($('input[name="encrypt"]').is(':checked'));
		decryptHide = !($('input[name="decrypt"]').is(':checked'));

		hideInput('encrypt_password', encryptHide);
		hideInput('encrypt_password_confirm', encryptHide);
		hideInput('decrypt_password', decryptHide);
		hideInput('decrypt_password_confirm', decryptHide);
	}

	// ---------- Click handlers ------------------------------------------------------------------

	// When radio backup_file are clicked . .
	$('input:radio[name=backup_file]').click(function() {
		backup_file_change();
	});

	$('input[name="encrypt"]').on('change', function() {
		hidePasswords();
	});

	$('input[name="decrypt"]').on('change', function() {
		hidePasswords();
	});

	$('#conffile').change(function () {
		if (document.getElementById("conffile").value) {
			$('.restore').prop('disabled', false);
		} else {
			$('.restore').prop('disabled', true);
		}
    });
	// ---------- On initial page load ------------------------------------------------------------

	hideSections();
	backup_file_change();
	$('.restore').prop('disabled', true);
});
//]]>
</script>

<?php
include("foot.inc");

if (is_subsystem_dirty('restore')) {
	system_reboot();
}
