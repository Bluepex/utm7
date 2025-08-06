<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2017
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");

if (!isset($_GET['serial_status']) || empty($_GET['serial_status'])) {
	header("Location: /index.php");
	exit;
}

$pgtitle = array(gettext("Licenses Control"), gettext("No Service Access"));
include('head.inc');
?>
<style type="text/css">

	#header-licenses-information { min-height: 165px; margin-bottom: 65px; background:url(./images/bg-header.png) no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;}
	#description-information h4 {color: #007dc5;}
	#description-information h6 {color: #333; background-color: #efefef; padding: 12px 55px; font-size: 1.4em;}
	#information-support {margin: 0 auto;}
	#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
	/* Footer Licenses Control */
	.footer-licenses-control {position: absolute; bottom: 0; right: 0; width: 100%; min-height: 66px; z-index: 0; color:#fff; background-color: #007dc5; padding-top: 30px; margin-top: 20px;}
	@media only screen and (max-width : 768px) {
		body { background: #fff; }
		#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
		#img-cloud { height:240px; }
	}
	@media only screen and (max-width : 480px) {
		#img-cloud { height:150px; }
	}
	@media only screen and (max-width : 320px) {
		#img-cloud { height:100px; }   
	}
</style>
<div id="wrapper-licenses-control">
	<div class="container-fluid">
		<div class="row" id="header-licenses-information"></div>
			<div class="col-md-12" id="content">
				<div class="row" id="warning-licenses">
					<div class="col-12 col-md-12 mt-5 text-center">
						<div id="description-information">
							<div class="icon-ilustration">
								<img src="./images/icon-license.png" class="img-fluid text-center">
							</div>
							<div class="mt-4 text-center">
								<h4><?=gettext("EXPIRED OR UNLICENSED LICENSES")?></h4>
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
<?php include("foot.inc"); ?>
