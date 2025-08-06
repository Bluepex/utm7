<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php echo $page_title; ?></title>
	<link rel="stylesheet" href="<?=base_url() . "public/plugins/bootstrap/dist/css/bootstrap.min.css";?>" />
	<link rel="stylesheet" href="<?=base_url() . "public/plugins/components-font-awesome/css/font-awesome.css";?>" />
	<link rel="stylesheet" href="<?=base_url() . "public/plugins/bootstrap-alert-helper/bootstrap-alert-light-helper.css";?>" />
	<link rel="stylesheet" href="<?=base_url() . "public/css/app.css"?>" />
	<?php echo $styles; ?>
</head>
<body>
	<header>
	</header>

	<section id="main">
		<div id="loading"></div>
		<?php if ($this->session->flashdata('messages')) : ?>
		<div id="messages">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
					<?php showFlashMessages($this->session->flashdata('messages')); ?>
					</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<div id="content">
			<?php echo $content; ?>
		</div>
	</section>

	<footer class="footer">
	</footer>

	<script type="text/javascript">
		var base_url = "<?=base_url()?>";
	</script>
	<script src="<?=base_url() . "public/plugins/jquery/dist/jquery.min.js"; ?>"></script>
	<script src="<?=base_url() . "public/plugins/bootstrap/dist/js/bootstrap.min.js"; ?>"></script>
	<script src="<?=base_url() . 'public/plugins/bootstrap-alert-helper/bootstrap-alert-helper.js'; ?>"></script>
	<script src="<?=base_url() . 'public/js/app.js'; ?>"></script>
	<script type="text/javascript">
		$("#about").click(function() {
			var about_content  = "<center>\n";
			    about_content += "<img class='img-responsive' src='" + base_url + "public/images/logo-blue.png" + "' /><br /><br />\n";
			    about_content += "<?=$this->lang->line('system_about_text');?>";
			    about_content += "<br /><br />";
			    about_content += "<?=$this->lang->line('system_about_text_version');?>: <strong><i><?=$this->config->item('version')?></i></strong>\n";
			    about_content += "</center>";
			$("body").alertModal({
				type: "info",
				title: "<i class='fa fa-exclamation-circle'></i> <?=$this->lang->line('system_about_text_title');?>",
				content: about_content,
				footer: { show: false }
			});
		});
	</script>
	<?php echo $scripts; ?>
</body>
</html>
