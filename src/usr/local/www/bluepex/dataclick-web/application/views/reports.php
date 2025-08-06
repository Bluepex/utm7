<?php
require_once("decode_reports.inc");
?>
<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="well title">
				<h3 class="text-center"><?=$this->lang->line('reports_header');?></h3>
			</div>
			<div id="alert-messages"></div>
			<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
				<div class="panel panel-default">
					<div class="panel-heading" role="tab" id="headingOne">
						<h4 class="panel-title">
							<a role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
								<?=$this->lang->line('reports_pre_title');?>
							</a>
						</h4>
					</div>
					<div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">

						<div class="panel-group" id="reports" role="tablist" aria-multiselectable="true">
							<div class="col-md-12">
								<div class="panel panel-default">
									<div class="panel-heading" role="tab" id="headingPre">
										<h4 class="panel-title">
											<a role="button" data-toggle="collapse" data-parent="#reports" href="#collapsePre" aria-expanded="false" aria-controls="collapsePre" id="bcollapsePre">
												<?=$this->lang->line('reports_title_sub1');?>
											</a>
										</h4>
									</div>

									<div id="collapsePre" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="collapsePre">
										<div class="panel-body">
											<form id="FormPre" class="form-horizontal" action="<?=base_url() . "reports/generate"?>" method="POST">
												<div class="col-md-4">
													<?php foreach ($reports as $id => $name) : if (!preg_match("/^P/", $id)) continue; ?>
													<div class="text-center btn-reports" data-report-id="<?=$id?>">
														<div class="col-md-2"><?=$id?></div>
														<div class="col-md-10">
														<?php if ($id == "P0007") : ?>
														<div class="btn-reports-sn">
															<img class="img-responsive" src='<?=base_url() . "public/images/logo_social_networks/facebook.png";?>' />
														</div>
														<?php elseif ($id == "P0008") : ?>
														<div class="btn-reports-sn">
															<img class="img-responsive" src='<?=base_url() . "public/images/logo_social_networks/youtube.png";?>' />
														</div>
														<?php elseif ($id == "P0009") : ?>
														<div class="btn-reports-sn">
															<img class="img-responsive" src='<?=base_url() . "public/images/logo_social_networks/instagram.png";?>' />
														</div>
														<?php elseif ($id == "P0010") : ?>
														<div class="btn-reports-sn">
															<img class="img-responsive" src='<?=base_url() . "public/images/logo_social_networks/linkedin.png";?>' />
														</div>
														<?php else : ?>
														<?=$name?>
														<?php endif; ?>
														</div>
														<div class="clearfix"></div>
													</div>
													<?php endforeach; ?>
												</div>
												<div class="col-md-8" id="preprocessed_inputs">
													<h4 class="text-center"><?=$this->lang->line('reports_input_filters');?></h4>
													<hr />
													<input type="hidden" name="form" value="form_pre"/>
													<div class="form-group">
														<label for="period" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_period');?></label>
														<div class="col-sm-8">
															<select class="form-control" name="period">
																<?php foreach ($periods as $id => $period) : ?>
																<option value="<?=$id?>"><?=$period?></option>
																<?php endforeach; ?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label for="limit" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_registers');?></label>
														<div class="col-md-8">
															<select class="form-control" name="limit">
															  <option value="10">10</option>
															  <option value="20" selected>20</option>
															  <option value="50">50</option>
															  <option value="100">100</option>
															  <option value="300">300</option>
															  <option value="500">500</option>
															  <option value="1000">1000</option>
															  <option value="5000">5000</option>
															  <option value="10000">10000</option>
															  <option value="10000">50000</option>
															  <option value="10000">100000</option>
															  <!--<option value="60 dias">60 dias</option>-->
															</select>
															<!--<input type="number" name="limit" class="form-control" min="1" max="100000000" />-->
														</div>
													</div>
													<div class="form-group">
														<label for="format" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_ext');?></label>
														<div class="col-md-8">
															<select name="format" class="form-control">
																<option value="pdf">PDF</option>
																<option value="csv">CSV</option>
															</select>
														</div>
													</div>
													<!--
													<div class="form-group">
														<label for="type" class="col-sm-4 control-label">Agendar</label>
														<div class="col-md-6">
															<div class="input-group">
																<span class="input-group-addon" id="basic-addon1"><span class="glyphicon glyphicon-calendar" aria-hidden="true"></span></span>
																<input type="text" name="schedule_to" class="form-control datetimepicker" placeholder="Data/Hora" />
															</div>
														</div>
														<div class="col-md-2">
															<input type="checkbox" name="repeat" value="1" autocomplete="off"> Repetir
														</div>
													</div>
													-->
													<div class="form-group">
														<div class="col-sm-offset-4 col-sm-8">
															<button type="submit" name="generate" class="btn btn-default" data-loading-text="<?=$this->lang->line('reports_btn_loading');?>"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> <?=$this->lang->line('reports_btn_process');?></button>
														</div>
													</div>
												</div>
											</form>
											<div class="clearfix"></div>
										</div>

									</div>
								</div> <!-- #END Pre-->

								<div class="panel panel-default"> <!-- #Begin Rel -->
									<div class="panel-heading" role="tab" id="headingRel">
										<h4 class="panel-title">
											<a class="collapsed" role="button" data-toggle="collapse" data-parent="#reports" href="#collapseRel" aria-expanded="false" aria-controls="collapseRel" id="bcollapseRel">
												<?=$this->lang->line('reports_title_sub2');?>
											</a>
										</h4>
									</div>

									<div id="collapseRel" class="panel-collapse collapse" role="tabpanel" aria-labelledby="collapseRel">
										<div class="panel-body">
											<form id="FormCustom" class="form-horizontal" action="<?=base_url() . "reports/generate"?>" method="POST">
												<div class="col-md-4">
													<?php foreach ($reports as $id => $name) : if (preg_match("/^P/", $id) or preg_match("/^E/", $id)) continue; ?>
													<div class="text-center btn-reports" data-report-id="<?=$id?>">
														<div class="col-md-2"><?=$id?></div>
														<div class="col-md-10"><?=$name?></div>
														<div class="clearfix"></div>
													</div>
													<?php endforeach; ?>
												</div>
												<div class="col-md-8" id="export_inputs">
													<h4 class="text-center"><?=$this->lang->line('reports_input_filters');?></h4>
													<hr />
													<input type="hidden" name="form" value="form_custom"/>
													<div class="form-group" id="interval">
														<label for="interval" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_interval');?></label>
														<div class="col-md-8">
															<div class="input-group">
																<span class="input-group-addon" id="basic-addon1"><?=$this->lang->line('reports_input_from');?></span>
																<input type="text" name="interval_from" class="form-control datetimepicker" value="<?php echo set_value('interval_from'); ?>" />
															</div>
															<br />
															<div class="input-group">
																<span class="input-group-addon" id="basic-addon1"><?=$this->lang->line('reports_input_until');?></span>
																<input type="text" name="interval_until" class="form-control datetimepicker" value="<?php echo set_value('interval_until'); ?>" />
															</div>
														</div>
													</div>
													<div class="form-group">
														<label for="username" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_user');?></label>
														<div class="col-md-8">
															<select name="username" class="form-control">
															<option value=""></option>
																<?php foreach ($users as $user) : ?>
																<option value="<?=$user?>"><?=$user?></option>
																<?php endforeach; ?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label for="ipaddress" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_ip');?></label>
														<div class="col-md-8">
															<input type="text" name="ipaddress" class="form-control" />
														</div>
													</div>
													<div class="form-group" id="category_id">
														<label for="category_id" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_categories');?></label>
														<div class="col-md-8">
															<select name="category_id" class="form-control">
																<option value=""></option>
																<?php foreach (getWfCategories() as $id => $cat) : ?>
																<option value="<?=$id?>"><?=$cat?></option>
																<?php endforeach; ?>
															</select>
														</div>
													</div>
													<div class="form-group">
														<label for="limit" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_total_records');?></label>
														<div class="col-md-8">
															<select class="form-control" name="limit">
															  <option value="10">10</option>
															  <option value="20" selected>20</option>
															  <option value="50">50</option>
															  <option value="100">100</option>
															  <option value="300">300</option>
															  <option value="500">500</option>
															  <option value="1000">1000</option>
															  <option value="5000">5000</option>
															  <option value="10000">10000</option>
															  <option value="10000">50000</option>
															  <option value="10000">100000</option>
															  <!--option value="60 dias">60 dias</option>-->
															</select>
															<!--<input type="number" name="limit" class="form-control" min="1" value="100" />-->
														</div>
													</div>
													<div class="form-group">
														<label for="format" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_ext');?></label>
														<div class="col-md-8">
															<select name="format" class="form-control">
																<option value="pdf">PDF</option>
																<option value="csv">CSV</option>
															</select>
														</div>
													</div>
													<div class="form-group">
														<div class="col-sm-offset-4 col-sm-8">
															<button type="submit" name="generate" class="btn btn-default" data-loading-text="<?=$this->lang->line('reports_btn_loading');?>"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> <?=$this->lang->line('reports_btn_process');?></button>
														</div>
													</div>
												</div>
											</form>
										</div>
									</div>
								</div> <!-- #END Generate-->
							</div>
						<div class="clearfix"></div>
					</div>
				</div>
				<div class="panel panel-default">
					<div class="panel-heading" role="tab" id="headingTwo">
						<h4 class="panel-title">
							<a class="collapsed" role="button" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
								<?=$this->lang->line('reports_custom_title');?>
							</a>
						</h4>
					</div>
					<div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
						<div class="panel-body">
							<form id="FormCustom" class="form-horizontal" action="<?=base_url() . "reports/generate"?>" method="POST">
								<div class="col-md-4">
									<?php foreach ($reports as $id => $name) : if (!preg_match("/^E/", $id)) continue; ?>
									<div class="text-center btn-reports" data-report-id="<?=$id?>">
										<div class="col-md-2"><?=$id?></div>
										<div class="col-md-10"><?=$name?></div>
										<div class="clearfix"></div>
									</div>
									<?php endforeach; ?>
								</div>
								<div class="col-md-8" id="export_inputs">
									<h4 class="text-center"><?=$this->lang->line('reports_input_filters');?></h4>
									<hr />
									<input type="hidden" name="form" value="form_custom"/>
									<div class="form-group" id="interval">
										<label for="interval" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_interval');?></label>
										<div class="col-md-8">
											<div class="input-group">
												<span class="input-group-addon" id="basic-addon1"><?=$this->lang->line('reports_input_from');?></span>
												<input type="text" name="interval_from" class="form-control datetimepicker" value="<?php echo set_value('interval_from'); ?>" />
											</div>
											<br />
											<div class="input-group">
												<span class="input-group-addon" id="basic-addon1"><?=$this->lang->line('reports_input_until');?></span>
												<input type="text" name="interval_until" class="form-control datetimepicker" value="<?php echo set_value('interval_until'); ?>" />
											</div>
										</div>
									</div>
									<div class="form-group">
										<label for="username" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_user');?></label>
										<div class="col-md-8">
											<select name="username" class="form-control">
												<option value=""></option>
												<?php foreach ($users as $user) : ?>
												<option value="<?=$user?>"><?=$user?></option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label for="ipaddress" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_ip');?></label>
										<div class="col-md-8">
											<input type="text" name="ipaddress" class="form-control" />
										</div>
									</div>
									<div class="form-group">
										<label for="groupname" class="col-sm-4 control-label">Grupos</label>
										<div class="col-md-8">
											<select name="groupname" class="form-control">
												<option value=""></option>
												<?php foreach ($groups as $group) : ?>
												<option value="<?=$group?>"><?=$group?></option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label for="category_id" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_categories');?></label>
										<div class="col-md-8">
											<select name="category_id" class="form-control">
												<option value=""></option>
												<?php foreach (getWfCategories() as $id => $cat) : ?>
												<option value="<?=$id?>"><?=$cat?></option>
												<?php endforeach; ?>
											</select>
										</div>
									</div>
									<div class="form-group">
										<label for="limit" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_total_records');?></label>
										<div class="col-md-8">
											<input type="number" name="limit" class="form-control" min="1" value="100" />
										</div>
									</div>
									<div class="form-group">
										<label for="format" class="col-sm-4 control-label"><?=$this->lang->line('reports_input_ext');?></label>
										<div class="col-md-8">
											<select name="format" class="form-control">
												<option value="csv">CSV</option>
												<option value="pdf">PDF</option>
											</select>
										</div>
									</div>
									<div class="form-group">
										<div class="col-sm-offset-4 col-sm-8">
											<button type="submit" name="generate" class="btn btn-default" data-loading-text="<?=$this->lang->line('reports_btn_loading');?>"><span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> <?=$this->lang->line('reports_btn_process');?></button>
										</div>
									</div>
								</div>
							</form>
							<div class="clearfix"></div>
						</div>
					</div>
				</div>
			</div>
			<br />
			<div id="report-files-table"></div>
		</div>
	</div>
</div>
<?php
$all_users = array_unique(array_filter(array_merge($users, filter_query_select($users_config))));
sort($all_users);
?>
<link rel="stylesheet" href="<?=base_url() . "/public/plugins/eonasdan-bootstrap-datetimepicker/build/css/bootstrap-datetimepicker.min.css";?>" />
<script type="text/javascript" src="<?=base_url() . "/public/plugins/moment/min/moment-with-locales.min.js";?>"></script>
<script src="<?=base_url() . "/public/plugins/eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min.js";?>"></script>
<script type="text/javascript">
$(function () {
	$('.datetimepicker').datetimepicker({
		format: 'YYYY-MM-DD HH:mm:ss',
		locale: "pt-BR",
	});

	getReportFilesTable();
	// Onload of page, check if reports are generating...
	checkReportsInProcessing();
	checkReportsTimer = setInterval(function() {
		checkReportsInProcessing();
	}, 3000);

	$("#headingPre").click(function() {
		$("#collapseRel").removeClass("in");
	});

	$("#headingRel").click(function() {;
		$("#collapsePre").removeClass("in");
	});

	$("#headingOne").click(function() {
		$("#collapseTwo").removeClass("in");
	});

	$("#headingTwo").click(function() {;
		$("#collapseOne").removeClass("in");
	});

	$(".btn-reports").click(function() {
		var form = $(this).parents("form");
		form.find(".btn-reports").removeClass("active");
		$(this).addClass("active");
		var report_id = $(this).data("report-id");
		// Disable PDF format for 0002 and 0004 reports
		reset_all_fields(report_id);
		disabled_fields(report_id);

		let all_users = "<?=join("||", $all_users)?>";
		let return_html = "<option value=''></option>";
		let use_array = all_users.split("||");

		for(var counter=0; counter <= use_array.length-1; counter++) {
			return_html += "<option value='" + use_array[counter] + "'>" + use_array[counter] + "</option>";
		}
		$("select[name=username]").html(return_html);

		if (report_id == "E0001" || report_id == "E0002" || report_id == "E0003") {
			form.find("select[name=format]").val("csv");
			form.find("select[name=format] option[value=pdf]").css("display", "none");
		// Hidden Categories
		} else if (report_id == "0003" || report_id == "0004" || report_id == "0007") {
			$("#category_id").hide();
		} else {
			form.find("select[name=format] option[value=pdf]").css("display", "inherit");
			$("#category_id").show();
		}
	});

	$("a[data-toggle=collapse]").click(function() {
		reset_all_fields("");
	});


	function reset_all_fields(report_id) {

		if (report_id == "") {

			$(".text-center.btn-reports.active").removeClass("active");

			$("select[name=period]").attr("disabled", true);
			$("input[name=interval_from]").attr("disabled", true);
			$("input[name=interval_until]").attr("disabled", true);
			$("select[name=username]").attr("disabled", true);
			$("input[name=ipaddress]").attr("disabled", true);
			$("select[name=groupname]").attr("disabled", true);
			$("select[name=limit]").attr("disabled", true);
			$("input[name=limit]").attr("disabled", true);
			$("select[name=format]").attr("disabled", true);
			$("button[name=generate]").attr("disabled", true);
			$("select[name=category_id]").attr("disabled", true);

			$("select[name=period]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("input[name=interval_from]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("input[name=interval_until]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("select[name=username]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("input[name=ipaddress]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("select[name=groupname]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("select[name=limit]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("input[name=limit]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("select[name=format]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("button[name=generate]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
			$("select[name=category_id]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block');?>");
		} else {
			$("select[name=period]").removeAttr("disabled");
			$("input[name=interval_from]").removeAttr("disabled");
			$("input[name=interval_until]").removeAttr("disabled");
			$("select[name=username]").removeAttr("disabled");
			$("input[name=ipaddress]").removeAttr("disabled");
			$("select[name=groupname]").removeAttr("disabled");
			$("select[name=limit]").removeAttr("disabled");
			$("input[name=limit]").removeAttr("disabled");
			$("select[name=format]").removeAttr("disabled");
			$("button[name=generate]").removeAttr("disabled");
			$("select[name=category_id]").removeAttr("disabled");

			$("select[name=period]").removeAttr("title");
			$("input[name=interval_from]").removeAttr("title");
			$("input[name=interval_until]").removeAttr("title");
			$("select[name=username]").removeAttr("title");
			$("input[name=ipaddress]").removeAttr("title");
			$("select[name=groupname]").removeAttr("title");
			$("select[name=limit]").removeAttr("title");
			$("input[name=limit]").removeAttr("title");
			$("select[name=format]").removeAttr("title");
			$("button[name=generate]").removeAttr("title");
			$("select[name=category_id]").removeAttr("title");
		}
	}

	reset_all_fields("");

	function disabled_fields(report_id) {
		if (report_id == "E0001" || 
		report_id == "E0002" || 
		report_id == "E0003" ||
		report_id == "0001" ||
		report_id == "0002" ||
		report_id == "0003" ||
		report_id == "0004" ||
		report_id == "0005" ||
		report_id == "0006" ||
		report_id == "0007" ||
		report_id == "0008"
		) {
			$("select[name=username]").val("");
			$("input[name=ipaddress]").val("");
			$("select[name=groupname]").val("");
			$("select[name=category_id]").val("");
			if (report_id == "E0001" || report_id == "E0002" || report_id == "E0003") {
				if (report_id == "E0001") {
					$("select[name=username]").removeAttr("disabled");
					$("select[name=username]").removeAttr("title");

					$("input[name=ipaddress]").attr("disabled", true);
					$("input[name=ipaddress]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_user');?>");
					$("input[name=ipaddress]").val("");

					$("select[name=groupname]").attr("disabled", true);
					$("select[name=groupname]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_user');?>");
					$("select[name=groupname]").val("");

					$("select[name=category_id]").removeAttr("disabled");
					$("select[name=category_id]").removeAttr("title");

				} else if (report_id == "E0002") {
					$("select[name=username]").attr("disabled", true);
					$("select[name=username]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_ip');?>");
					$("select[name=username]").val("");

					$("input[name=ipaddress]").removeAttr("disabled");
					$("input[name=ipaddress]").removeAttr("title");

					$("select[name=groupname]").attr("disabled", true);
					$("select[name=groupname]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_ip');?>");
					$("select[name=groupname]").val("");

					$("select[name=category_id]").attr("disabled", true);
					$("select[name=category_id]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_ip');?>");
					$("select[name=category_id]").val("");
				} else if (report_id == "E0003") {
					$("select[name=username]").attr("disabled", true);
					$("select[name=username]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_group');?>");
					$("select[name=username]").val("");

					$("input[name=ipaddress]").attr("disabled", true);
					$("input[name=ipaddress]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_group');?>");
					$("input[name=ipaddress]").val("");

					$("select[name=groupname]").removeAttr("disabled");
					$("select[name=groupname]").removeAttr("title");

					$("select[name=category_id]").removeAttr("disabled");
					$("select[name=category_id]").removeAttr("title");
				}
			} else if (report_id == "0001" || report_id == "0002") {
				if (report_id == "0001") {
					$("select[name=username]").removeAttr("disabled");
					$("select[name=username]").removeAttr("title");

					$("input[name=ipaddress]").attr("disabled", true);
					$("input[name=ipaddress]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_user');?>");
					$("input[name=ipaddress]").val("");
				} else if (report_id == "0002") {
					$("select[name=username]").attr("disabled", true);
					$("select[name=username]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_ip');?>");
					$("select[name=username]").val("");

					$("input[name=ipaddress]").removeAttr("disabled");
					$("input[name=ipaddress]").removeAttr("title");
				}
			} else if (report_id == "0008") {
				$("input[name=ipaddress]").attr("disabled", true);
				$("input[name=ipaddress]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_group');?>");
				$("input[name=ipaddress]").val("");

				$("select[name=category_id]").attr("disabled", true);
				$("select[name=category_id]").attr("title", "<?=$this->lang->line('reports_cmd_text_title_inputs_block_ip');?>");
				$("select[name=category_id]").val("");
			} else {
				$("select[name=username]").removeAttr("disabled");
				$("input[name=ipaddress]").removeAttr("disabled");
				$("select[name=groupname]").removeAttr("disabled");
				$("select[name=username]").removeAttr("title");
				$("input[name=ipaddress]").removeAttr("title");
				$("select[name=groupname]").removeAttr("title");
			}
		}
	}

	// Process the report
	$("#FormPre, #FormCustom").submit(function(e) {
		e.preventDefault();

		// Limit search interval, 90 days
		if ($(this).attr("id") == "FormCustom") {
			var start = moment($(this).find("input[name=interval_from]").val());
			var end = moment($(this).find("input[name=interval_until]").val());
			var interval_diff = end.diff(start, "days");
			if (interval_diff == undefined || interval_diff > 90) {
				$("#alert-messages").alertInfo({ id: "alert1", type: "warning", message: "<?=$this->lang->line('reports_msg_limit');?>" });
				return;
			}
		}

		var report_id = $(this).find(".btn-reports.active").data("report-id");
		if (report_id == "" || report_id == undefined) {
			$("#alert-messages").alertInfo({ id: "alert2", type: "warning", message: "<?=$this->lang->line('reports_msg_select_rel');?>" });
			return;
		}
		var btn = $(this).find("button[type=submit]").button('loading')
		var params = $(this).serialize() + "&report_id=" + report_id;
		$.ajax({
			method: "POST",
			url: base_url + "reports/generate",
			data: params,
			success: function( response ) {
				res = JSON.parse(response);
				if (res.status == "ok") {
					clearInterval(checkReportsTimer);
					checkReportsTimer = setInterval(function() {
						checkReportsInProcessing();
					}, 3000);
					setTimeout(function(){
						$("#alert-messages").alertInfo({ id: "alert3", type: "success", message: res.message, timeout: 5000 });
						btn.button('reset');
					}, 3000);
				} else if (res.status == "error") {
					$("#alert-messages").alertInfo({ id: "alert4", type: "danger", message: res.message });
					btn.button('reset');
				}
			},
			error: function(error) {
				console.log(error);
			}
		});
	});
});

function stopReport(id)
{
	if (id != "") {
		$.get(base_url + "reports/stopReport/" + id);
	}
}

function getReportFilesTable()
{
	$.get(base_url + "reports/get-files-table", function(table) {
		$("#report-files-table").html(table);
	});
}

function getStateExports()
{
	$.get(base_url + "reports/get-state-exports", function(data) {
		if (data.length > 0) {
			data = "<h4><?=$this->lang->line('report_title_exports')?></h4>" + "<br>" + data;
			$("#alert-messages").alertInfo({ id: "alert5", type: "warning", message: data });
		}
	});
}

getStateExports();
setInterval(function() { getStateExports(); }, 3000);

function checkReportsInProcessing()
{
	$.ajax({
		method: "GET",
		url: base_url + "reports/checkReport",
		success: function( response ) {
			var res = JSON.parse(response);
			if (res.running) {
				var msg = "<p>Gerando Relatórios</p>";
				for (var i = 0; i < res.reports.length; i++) {
					msg += res.reports[i]['name']+" <a href='#' title='Stop' onclick='stopReport("+res.reports[i]['id']+")'><span class='glyphicon glyphicon-stop' aria-hidden='true'></span></a><br/>";
				}
				htmlLoading("reports", msg);
				return;
			}
			if (checkReportsTimer) {
				clearInterval(checkReportsTimer);
				getReportFilesTable();
			}
			stopLoading("reports");
		},
		error: function(error) {
			console.log(error);
		}
	});
}
</script>
