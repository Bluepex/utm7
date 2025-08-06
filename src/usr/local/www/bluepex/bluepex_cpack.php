<?php
/*
* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Marcos Claudiano <desenvolvimento@bluepex.com>, 2022
* Written by Guilherme Brechot <guilherme.brechot@bluepex.com>, 2022
* Rewritten by Marcos Claudiano <desenvolvimento@bluepex.com>, 2023
* Rewritten by Guilherme Brechot <guilherme.brechot@bluepex.com>, 2023
* ====================================================================
*/

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("util.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("bp_pack_version.inc");

if ($_POST['reinstall_all_cpacks']) {
	file_put_contents("/tmp/reinstall_all_cpacks", "cd /root && " .
	    "/usr/bin/fetch http://wsutm.bluepex.com/packs/6.0.0/cpacks_full.tar.gz && " .
	    "/usr/bin/tar -zxvf cpacks_full.tar.gz && " .
	    "cd cpacks_full && " .
	    "/bin/sh install_all_cpacks_offline.sh");
	mwexec_bg("/usr/bin/nohup /bin/sh /tmp/reinstall_all_cpacks");
	unset($_POST);
}

if (isset($_POST['passmodal_local']) &&
    !empty($_POST['passmodal_local'])) {
	$pass_modal = "!@#Bluepex!@#";

	if (file_exists("/var/db/.passModal")) {
		$tmp_pass = trim(file_get_contents("/var/db/.passModal"));
		$pass_modal = (empty($tmp_pass)) ? $pass_modal : $tmp_pass;
	}

	$password_verificy_passmodal = ($_POST['passmodal_local'] == $pass_modal);
	unset($_POST['passmodal_local']);
}

exec("/bin/ps aux | /usr/bin/grep -E 'cpacks_full|install_all_cpacks_offline' | /usr/bin/grep -vc 'grep'", $out, $err);

$bp_show_btn_cp_full = (intval(join("", $out)) == 0);

if ($bp_show_btn_cp_full &&
    file_exists('/etc/utm_custom') &&
    file_exists('/etc/utm_custom.txt')) {
	$bp_show_btn_cp_full = ((strlen(trim(file_get_contents('/etc/utm_custom'))) == 44 ||
	    strlen(trim(file_get_contents('/etc/utm_custom'))) == 0) &&
	    strlen(trim(file_get_contents('/etc/utm_custom.txt'))) == 0);
}

$pgtitle = array(gettext("System"), gettext("Update"), gettext('Pack Fix'));
$pglinks = array("", "pkg_mgr_install.php?id=firmware", "@self");
$tab_array[] = array(gettext("System Update"), false, "pkg_mgr_install.php?id=firmware");
$tab_array[] = array(gettext("Update Settings"), false, "system_update_settings.php");
$tab_array[] = array(gettext("Pack Fix"), true, "bluepex_cpack.php");
include("head.inc");

display_top_tabs($tab_array);

if (!$password_verificy_passmodal ||
    !isset($password_verificy_passmodal)):
?>

<style type="text/css">

#header-licenses-information { min-height: 165px; margin-bottom: 65px; background:url(./images/bg-header.png) no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;}
#description-information h4 {color: #007dc5;}
#description-information h6 {color: #333; background-color: #efefef; padding: 12px 55px; font-size: 1.4em;}
#information-support {margin: 0 auto;}
#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
.footer-licenses-control {position: absolute; bottom: 0; right: 0; width: 100%; min-height: 66px; z-index: 0; color:#fff; background-color: #007dc5; padding-top: 30px; margin-top: 20px;}
@media only screen and (max-width : 768px) {
	body { background: #fff; }
	#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
	#img-cloud { height:240px; }
}
@media only screen and (max-width : 480px) { #img-cloud { height:150px; } }
@media only screen and (max-width : 320px) { #img-cloud { height:100px; } }
</style>

<!-- Modal -->
<div class="modal fade" id="ExemploModalCentralizado" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado"><?=gettext("Provide Password to Access")?></h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" action="" style="margin-top: 10px !important; border: 0px solid transparent;">
					<div class="form-row">
						<div class="col">
							<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
							<input type="password" class="form-control" name="passmodal_local" maxlength="50" required>
						</div>
					</div>
					<hr>
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
					<button type="submit" name="confirmar_senha" class="btn btn-primary"><?=gettext("Continuar")?></button>
				</form>
			</div>
		</div>
	</div>
</div>

<div id="wrapper-licenses-control">
	<div class="container-fluid">
		<div class="row" id="header-licenses-information"></div>
			<div class="col-md-12" id="content">
				<div class="row" id="warning-licenses">
					<div class="col-12 col-md-12 mt-5 text-center">
						<div id="description-information">
							<div class="icon-ilustration">
								<img src="./images/cadeado.jpg" class="img-fluid text-center">
							</div>
							<div class="mt-4 text-center">
								<h4><?=gettext("RESTRICTED ACCESS")?></h4>
							</div>
							<div class="col-12 mt-4 text-center">
								<div class="row">
									<div id="information-support">
										<h6><?=gettext(" Please contact us for more information.")?></h6>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- jquery -->
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript">
	<?php if (isset($password_verificy_passmodal) &&
	    !$password_verificy_passmodal): ?>
		alert("<?=gettext("Incorrect password!")?>");
	<?php endif; ?>
	<?php if (!$password_verificy_passmodal ||
	    !isset($password_verificy_passmodal)):?>
		$(document).ready(function() {
			$('#ExemploModalCentralizado').modal('show');
		});
	<?php endif; ?>
</script>
<?php else:?>

<?php if ($bp_show_btn_cp_full): ?>
<div id="div_buttons" class="text-right mt-5">
	<form action="./bluepex_cpack.php" method="POST" class="border-0">
		<input type="hidden" id="reinstall_all_cpacks" name="reinstall_all_cpacks" value="reinstall_all_cpacks"/>
		<button type="submit" class="btn btn-warning"><?=gettext("Apply all pack packages")?></button>
	</form>
</div>
<?php endif; ?>
<style>
.panel-heading a:link, .panel-heading a:visited { color: #007dc5 !important; }
</style>

<div class="panel-heading">
	<h5 class=""><?=gettext('Pack Options')?> <?=bp_link_changelog("( " . gettext("Changelog") . " )")?>
</div>

<div class="panel-body">
	<div class="panel-body panel-default">
		<div class="table-responsive">
			<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap" data-sortable>
				<thead>
					<tr>
						<th><?=gettext("Version")?></th>
						<th><?=gettext("Status")?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach (bp_list_files_pack_fix('inverse', true) as $line_cpack): ?>
					<tr>
						<td><?=$line_cpack?></td>
						<td><i class='text-success fa fa-check-circle fa-1x'></i></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_ativa" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_ativa" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<?php
endif;
include("foot.inc");
?>
