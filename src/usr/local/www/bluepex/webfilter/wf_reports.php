<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Desenvolvimento <desenvolvimento@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
 
require_once('globals.inc');
require_once('config.inc');
require_once('cg2_util.inc');
require_once('nf_defines.inc');
require_once('nf_db.inc');
require_once('util.inc');
echo '<body bgcolor="#F0F0F0">';
?>
<style>
a {
	color: #000000;
	text-decoration:none;
}
a:hover {
	text-decoration: underline;
}
</style>
<?php
if(!isset($_GET['domain'])) {
	echo dgettext("BluePexWebFilter","Domain not found!");
	exit;
}
$db = new NetfilterDatabase();
$sql = "select url_str
	from hosts
	inner join urls on hosts.id = host_id
	inner join accesses on urls.id = accesses.url_id
	where hosts.description = '{$_GET['domain']}'
	group by url_str
	order by url_str
	limit 1000";
$res = $db->Query($sql);
if(!$res) {
	echo dgettext("BluePexWebFilter","Error querying the database!");
} else {
	?> <table style="background: #FFFFFF" width="100%" cellspacing="0" cellpadding="2">
	<?php
	while($row = $db->FetchRow($res)) {
	?>
		<tr>
			<td><font "helvetica"><a href="<?php echo $row[0];?>" target="_blank"><?php echo $row[0];?></a></font></td>
		</tr>
	<?php	
	}
	?> </table> <?php
}
echo "</body>";
