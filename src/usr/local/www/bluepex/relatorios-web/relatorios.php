<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Wesley F. Peres <wesley.peres@bluepex.com>, 2019
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("bp_webservice.inc");
include("head.inc");

?>

<style>
	table { background-color:#fff }
	table tr:hover { background-color:#f9f9f9 }
	.btn-disabled { opacity:0.3; }
	.checked { opacity:1 }
	.btn-group-vertical > .btn.active,
	.btn-group-vertical > .btn:active,
	.btn-group-vertical > .btn:focus,
	.btn-group-vertical > .btn:hover,
	.btn-group > .btn.active,
	.btn-group > .btn:active,
	.btn-group > .btn:focus,
	.btn-group > .btn:hover { outline:none }
	.btn-group .btn { margin:0 }
	.panel .panel-body { padding:10px }

	.btn-primary:focus {
		background-color: #286090 !important;
		border-color: transparent !important;
	}

	.status-running {
		color: #43A047;
		font-weight: bold;
		font-size: 14px;
	}

	.status-stopped {
		color:	#f00;
		font-weight: bold;
		font-size: 14px;
	}
</style>
<?php
if ($savemsg)
	print_info_box($savemsg, 'success');
?>
<br><br><br>
<div class="col-sm-12">
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext("Relatórios BluePex");?>
		</div>
	</div>
</div>
<div class="outer-container">
	<iframe width="854" height="480" src="/relatorios-web/" frameborder="0" style="overflow: scroll; height: 80vh; width: 82vw;" scrolling=yes></iframe>
</div>


<?php include("foot.inc"); ?>

</script>
