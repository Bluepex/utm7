<?php
require_once('config.inc');
require_once('util.inc');

if (!isset($config['system']['webfilter']['bluepexdataclickagent']['config'][0])) {
	$config['system']['webfilter']['bluepexdataclickagent']['config'][0] = array();
}
$settings = &$config['system']['webfilter']['bluepexdataclickagent']['config'][0];

if (isset($settings['cleanup_db_mb']) && $settings['cleanup_db_mb'] > 0) {
	mwexec("mysql -uwebfilter -pwebfilter webfilter -e \"SELECT table_name AS 'Table_Name', ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_in_(MB)' FROM information_schema.TABLES WHERE table_schema = 'webfilter' ORDER BY (data_length + index_length) DESC;\" > /tmp/tables_show.tmp");
	mwexec("grep -v \"Table_Name\" /tmp/tables_show.tmp | grep -v \"mysql\" | awk -F\" \" '{print $1 \"___\" $2}' > /tmp/tables_show");

	$deleteRegistre = 0;
	if (intval($settings['cleanup_db_mb']) == 100) {
		$deleteRegistre = 33333;
	} elseif (intval($settings['cleanup_db_mb']) == 512) {
		$deleteRegistre = 166665;
	} elseif (intval($settings['cleanup_db_mb']) == 1024) {
		$deleteRegistre = 333330;
	} elseif (intval($settings['cleanup_db_mb']) == 2048) {
		$deleteRegistre = 666660;
	} elseif (intval($settings['cleanup_db_mb']) == 3072) {
		$deleteRegistre = 999990;
	} elseif (intval($settings['cleanup_db_mb']) == 4096) {
		$deleteRegistre = 1333320;
	} elseif (intval($settings['cleanup_db_mb']) == 5120) {
		$deleteRegistre = 1666650;
	}
			
	foreach (array_filter(explode("\n", file_get_contents('/tmp/tables_show'))) as $table_target) {
		$table_target = explode("___", $table_target);
		if (intval($table_target[1]) > intval($settings['cleanup_db_mb'])) {
			mwexec("mysql -uwebfilter -pwebfilter webfilter -e \"delete from {$table_target[0]} order by Id asc limit {$deleteRegistre}\"");
		}
	}

}

//Base
//33333 = 10MB
//100000 = 30MB
//1000000 = 300MB
//10000000 = 3000MB


//Values = 10% = Counter (Qtd delete old)
//100 = 10 = 33333 
//512 = 50 = 166665 
//1024 = 100 = 333330
//2048 = 200 = 666660
//3072 = 300 = 999990
//4096 = 400 = 1333320
//5120 = 500 = 1666650

//Insert massive
//30Mb in test of size table space consumed
//for($i=0;$i<100000;$i++) {
//	mwexec("mysql -uwebfilter -pwebfilter webfilter -e \"insert into accesses values(0,NOW(),'www.google.com','https://www.google.com','','',1003,227,1,'192.168.20.10','teste','teste');\"");
//}