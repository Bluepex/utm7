<?php
/* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Guilherme R. Brechot <guilherme.brechot@bluepex.com>, 2023
* ====================================================================
*
*/

require_once("config.inc");
require_once("functions.inc");
require_once("util.inc");

define('TABLE_AUDITING_BP_AUDI', 'bp_audit_logs');
define('HOST_MYSQL_BP_AUDI', 'localhost');
define('USER_MYSQL_BP_AUDI', 'webfilter');
define('PASS_MYSQL_BP_AUDI', 'webfilter');
define('DB_MYSQL_BP_AUDI', 'webfilter');

function bp_connection_db_reports() {
	try {
	    $pdo = new PDO("mysql:host=localhost;dbname=webfilter", "webfilter", "webfilter");
	    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    return $pdo;
	} catch (PDOException $e) {
	    log_error("Erro PDO: " . $e->getMessage());
	    return false;
	}

	if ($mysql_connection->connect_error) {
		log_error("Bluepex: Reports Auditing: Unable to connect to database");
		return false;
	}

	return $mysql_connection;
}

function bp_confirm_exists_auditing_log_reports() {
	$table_auditing = TABLE_AUDITING_BP_AUDI;
	$return_status = false;

	$mysql_connection = bp_connection_db_reports();

	if (is_bool($mysql_connection) &&
	    $mysql_connection == false) {
		log_error("Bluepex: Reports Auditing: Confirm: It was not possible to communicate with the bank to check the existence of the tables");
		return $return_status;
	}

	$sql_command = "SELECT table_name FROM information_schema.tables WHERE table_name = '{$table_auditing}';";
	$return_sql_command = $mysql_connection->query($sql_command);
	$return_status = (mysqli_num_rows($return_sql_command) > 0);

	$mysql_connection->close();

	return $return_status;
}

function bp_create_auditing_log_reports() {
	$mysql_connection = bp_connection_db_reports();

	if (is_bool($mysql_connection) &&
	    $mysql_connection == false) {
		log_error("Bluepex: Reports Auditing: Create: Database creation canceled due to lack of connection to the bank");
		return;
	}

	if (bp_confirm_exists_auditing_log_reports()) {
		$mysql_connection->close();
		return;
	}

	$table_auditing = TABLE_AUDITING_BP_AUDI;
	$sql_command = <<<EOD
CREATE TABLE IF NOT EXISTS {$table_auditing} (
	id INT NOT NULL AUTO_INCREMENT PRIMARY KEY NOT NULL,
	event_date DATETIME NOT NULL,
	session VARCHAR(10) NOT NULL,
	username VARCHAR(50) NOT NULL,
	descr TEXT NOT NULL,
	complement TEXT
);
EOD;

	$return_msg = ($mysql_connection->query($sql_command) === TRUE) ? "Create database" : "Error creating database: " . $mysql_connection->error;

	log_error("Bluepex: Reports Auditing: {$return_msg}");

	$mysql_connection->close();
}

function bp_write_report_db($descr, $complement = '') {
	global $_SESSION;

	$mysql_connection = bp_connection_db_reports();

	if (is_bool($mysql_connection) &&
	    $mysql_connection == false) {
		log_error("Bluepex: Reports Auditing: Insert: Database creation canceled due to lack of connection to the bank");
		return;
	}

	$stop_action = bp_confirm_exists_auditing_log_reports();

	if (!$stop_action) {
		for ($counter = 1; $counter <= 3; $counter++) {
			bp_create_auditing_log_reports();
			$stop_action = bp_confirm_exists_auditing_log_reports();

			if ($stop_action) {
				break;
			}
		}
	}

	if (!$stop_action) {
		log_error("Bluepex: Reports Auditing: Database no exists");
		$mysql_connection->close();
		return;
	}

	if (empty($descr)) {
		log_error("Bluepex: Reports Auditing: Not enough information to generate the record");
		$mysql_connection->close();
		return;
	}

	if (!isset($_SESSION['report_session_00061']) ||
	    empty($_SESSION['report_session_00061'])) {
		log_error("Bluepex: Reports Auditing: User without session registration");
		$mysql_connection->close();
		return;
	}

	$table_auditing = TABLE_AUDITING_BP_AUDI;
	$date_now = date("Y-m-d H:i:s");
	$username = empty($_SESSION['Username']) ? "admin" : $_SESSION['Username'];

	$sql_command = "INSERT INTO {$table_auditing} VALUES (0, '{$date_now}', '{$_SESSION['report_session_00061']}', '{$username}', '{$descr}', '{$complement}');";

	$text_log = ($mysql_connection->query($sql_command) === TRUE) ? "Registration carried out" : "Registry Issues";

	log_error("Bluepex: Reports Auditing: {$text_log}");

	$mysql_connection->close();
}
