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

/*####################################### Update Webfilter #######################################
Step 1: Recreate database webfilter, set parameter innodb_file_per_table in my.cnf file.
Step 2: Rebuild database webfilter with new structure.
################################################################################################*/


if (file_exists('modules_backup_restore.inc')) {
	require_once("modules_backup_restore.inc");
} 
require_once("services.inc");
require_once("system.inc");
require_once("webfilter.inc");
require_once("nf_db.inc");

define("PID", getmypid());
define("PIDFILE", "/var/run/update_webfilter_db.pid");

define("DIRMYSQL", "/var/db/mysql/");
define("UPDATEDB", "Web filter update database: ");

$status_update = &$config['status_update'];
$enable_log = &$config['system']['webfilter']['nf_reports_settings']['element0']['remote_reports'];
$conf = get_element_config('nf_reports_settings');
$db = new NetfilterDatabase();
$tables_old = array(
		"urls_nokeys", 
		"access_categories_nokeys", 
		"accesses_nokeys", 
		"access_log", 
		"referer_log", 
		"netfilter_log", 
		"hosts", 
		"queries", 
		"schemes", 
		"topsites", 
		"topsites_nokeys", 
		"paths", 
		"urls");

check_script_conflict(); 

if(check_tables_old_exists()) {

	before_update();
	file_put_contents(PIDFILE,PID);

	if(!isset($status_update['step1']) && ($g['platform'] != "nanobsd") && ($g['platform'] != "embedded"))
		step1();

       	step2();

} else {
	after_update();
}

function step1() {
	global $conf, $status_update;

	log_error(UPDATEDB."Executando Passo 1 de 2...");
	notify_via_smtp(UPDATEDB."Executando Passo 1 de 2...");

	$namebkp = date("YmdHis")."-webfilter-database";
	$namefile = $namebkp.".tar.gz";

	log_error(UPDATEDB."Gerando backup da database webfilter...");
	backup_webfilter($namebkp, "database");

	if(file_exists(DIR.$namefile)) {
		$cmd = "/usr/local/bin/mysql -p{$conf['reports_password']} -u{$conf['reports_user']} -h{$conf['reports_ip']} -P{$conf['reports_port']} -e 'DROP DATABASE {$conf['reports_db']}'";
		exec($cmd, $out, $err);
		$output = implode('\n',$out);
		if($err == 0) {
			log_error(UPDATEDB."Parando o servi�o banco de dados mysql...");
			stop_service("mysqld");

			$regex = "/^ib(data|_logfile)[0-9]/";
			foreach(scandir(DIRMYSQL) as $file)
				if(preg_match($regex, $file)) {
					log_error(UPDATEDB."Removendo arquivo {$file}...");
					unlink(DIRMYSQL.$file);
				}

			log_error(UPDATEDB."Sincronizando arquivo de configura��o do mysql com parametro innodb_file_per_table habilitado...");
			webfilter_resync_mysql();

			log_error(UPDATEDB."Restaurando backup da database webfilter...");
			restore_webfilter($namefile);

			$status_update = array("step1" => true);
			write_config('Bank restore completed successfully');

			log_error(UPDATEDB."Passo 1 de 2 finalizado com sucesso!");
			notify_via_smtp(UPDATEDB."Passo 1 de 2 finalizado com sucesso!");

		} else {
			log_error(UPDATEDB."Ocorreu um erro ao remover a database webfilter: \n{$output}");
			notify_via_smtp(UPDATEDB."Ocorreu um erro ao remover a database webfilter: \n\n{$output}");
			close_script();
		}
	}
}

function step2() {
	global $conf;

	log_error(UPDATEDB."Executando Passo 2 de 2...");
	notify_via_smtp(UPDATEDB."Executando Passo 2 de 2...");

	$cmd = "/usr/local/bin/mysql -p{$conf['reports_password']} -u{$conf['reports_user']} -h{$conf['reports_ip']} -P{$conf['reports_port']} -B {$conf['reports_db']} -e 'CALL rebuild_webfilter_db()'";
        mwexec_bg($cmd, $out, $err);
	$output = !empty($out) ? implode('\n',$out): "";

	if($err == 0) {
		log_error(UPDATEDB."Atualizando banco de dados webfilter. Este processo pode demorar dependendo do tamanho do banco de dados, por favor aguarde...");
		notify_via_smtp(UPDATEDB."Atualizando banco de dados webfilter. Este processo pode demorar dependendo do tamanho do banco de dados, por favor aguarde...");
	} else {
		log_error(UPDATEDB."Erro ao chamar fun��o para atualiza��o do banco de dados: \n{$output}");
		notify_via_smtp(UPDATEDB."Erro ao chamar fun��o para atualiza��o do banco de dados: \n\n{$output}");
		close_script();
	}
}

function check_tables_old_exists() {
	global $tables_old, $db;

	$res = $db->Query("SHOW TABLES");
	if($res)
                while($result = $db->FetchArray($res)) {
			if(in_array($result[0], $tables_old))
				return true;
		}
}

function check_script_conflict() {
	if(file_exists(PIDFILE)) {
		exec("cat ".PIDFILE." | xargs ps", $out, $err);
		if (!$err)
			die("Script update_webfilter_db is already running!");
		else
			unlink(PIDFILE);
	}
}

function before_update() {
	global $config, $g;

	// Check mysql is running
	if($g['platform'] != "nanobsd" && $g['platform'] != "embedded") {
		if(!is_process_running("mysqld")) {
			start_service("mysqld");

			if(!is_process_running("mysqld")) {
				log_error(UPDATEDB."Servi�o de banco de dados mysql n�o est� rodando!");
				notify_via_smtp(UPDATEDB."Servi�o de banco de dados mysql n�o est� rodando!");
				close_script();
			}
		}
		// Check disk space free, if < 15G close script 
		if(disk_free_space("/") < 16106127360) {
			log_error(UPDATEDB."Para prosseguir com a atualiza��o � necess�rio que a parti��o tenha pelo menos 15G de espa�o livre!");
			notify_via_smtp(UPDATEDB."Para prosseguir com a atualiza��o � necess�rio que a parti��o tenha pelo menos 15G de espa�o livre!");
			close_script();
		}
	}

	// Disabled Remote Logs
	log_error(UPDATEDB."Desabilitando logs de relat�rio...");
	on_off_logs("off");
}

function after_update() {
	global $config;

	// Deinstall update script cron
	$cron_item = &$config['cron']['item'];
	for($x=0; $x < count($cron_item); $x++) {
		if($cron_item[$x]['task_name'] == "update_webfilter_db") {
			unset($cron_item[$x]);
			write_config('Removed cron update');
			configure_cron();
			break;
		}
	}

	// Remove update tag xml
	unset($status_update);

	// Enable Remote Logs
	on_off_logs("on");

	write_config('Database update successful');
	log_error(UPDATEDB."Atualiza��o de banco de dados finalizada com sucesso!");
	notify_via_smtp(UPDATEDB."Atualiza��o de banco de dados finalizada com sucesso!");
}

function on_off_logs($act) {
	global $enable_log;

	if($enable_log != $act) {
		$enable_log = $act;
		write_config('Restarting log system');
		stop_service("syslogd");
		system_syslogd_start();
	}
}

function close_script() {
	if(file_exists(PIDFILE)) unlink(PIDFILE);
	exit();
}






?>

