<?php
//check user level
//$dataLevel = $this->userlevel->checkLevel($role);
//$result = $this->user_model->getAllSettings();
//$site_title = $result->site_title;

defined('BASEPATH') OR exit('No direct script access allowed');

$data = $this->session->userdata;
$result = $this->user_model->getAllSettings();
$dataLevel = $this->userlevel->checkLevel($data['role']);
$first_name = $data['first_name'];
$theme = $result->theme;
//print_r($data);die;
?>
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
  <?php if (count($data) != 1): ?>
  <header>
  <!--<div class="container">-->
  <div>
      <nav class="navbar navbar-default">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">
            <img src="<?=base_url() . "public/images/logo-simple.png"?>"  style="height:20px"/> <?php echo $this->config->item("app_name"); ?> <?=$this->config->item('version')?>
          </a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
          <ul class="nav navbar-nav">
            <!--<li><a href="<?=$_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST']?>">BluePexUTM</a></li>-->
						<li><a href="<?=base_url() . "dashboard"?>"><i class='fa fa-tachometer'></i> Dashboard</a></li>
						<li><a href="<?=base_url() . "utm"?>"><i class='fa fa-fire'></i> <?=$this->lang->line('utm_template_menu_utm');?></a></li>
						<li><a href="<?=base_url() . "reports"?>"><i class="fa fa-file"></i> <?=$this->lang->line('utm_template_menu_rel');?></a></li>
            <?php if ($dataLevel == 'is_admin'): ?>
              <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-users" aria-hidden="true"></i> <?=$this->lang->line('utm_template_menu_users_action');?> <span class="caret"></span></a>
                <ul class="dropdown-menu">
                  <li><a href="<?=site_url()?>main/users"><?=$this->lang->line('utm_template_menu_users_action_list')?></a></li>
                  <li><a href="<?=site_url()?>main/adduser"><?=$this->lang->line('utm_template_menu_users_action_add')?></a></li>
                  <li><a href="<?=site_url()?>main/banuser"><?=$this->lang->line('utm_template_menu_users_action_ban')?></a></li>
                  <li><a href="<?=site_url()?>main/changelevel"><?=$this->lang->line('utm_template_menu_users_action_role')?></a></li>
                </ul>
              </li>
              <!--<li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-wrench" aria-hidden="true"></i> <?=$this->lang->line('utm_template_menu_tools');?> <span class="caret"></span></a>
                <ul class="dropdown-menu">
                  <li><a href="<?=base_url() . "tools/categorization";?>"><?=$this->lang->line('utm_template_menu_tools_cat');?></a></li>
                </ul>
              </li>-->
            <?php endif; ?>
  					</ul>
						<ul class="nav navbar-nav navbar-right">
							<li class="dropdown">

								<?php if (!isset($_SESSION['dataclick']['lang'])): ?>
									<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="<?=base_url() . "public/images/flags/16/Brazil.png"?>" /> <span class="caret"></span></a>
								<?php else: ?>
									<?php if (isset($_SESSION['dataclick']['lang']) && $_SESSION['dataclick']['lang'] == "pt-br"): ?>
										<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="<?=base_url() . "public/images/flags/16/Brazil.png"?>" /> <span class="caret"></span></a>
									<?php else: ?>
										<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><img src="<?=base_url() . "public/images/flags/16/UnitedStates.png"?>" /> <span class="caret"></span></a>
									<?php endif; ?>
								<?php endif; ?>
								<ul class="dropdown-menu">
									<li><a href="<?=base_url();?>lang/pt-br" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('language_pt_br');?>"><img src="<?=base_url() . "public/images/flags/16/Brazil.png"?>" /> <?=$this->lang->line('utm_navbar_portuguese')?></a></li>
									<li><a href="<?=base_url();?>lang/english" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('language_english');?>"><img src="<?=base_url() . "public/images/flags/16/UnitedStates.png"?>" /> <?=$this->lang->line('utm_navbar_english')?></a></li>
								</ul>
							</li>
							<li class="dropdown">
								<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><i class="fa fa-user-circle" aria-hidden="true"></i> <?=$_SESSION['first_name']?><span class="caret"></span></a>
								<ul class="dropdown-menu">
									<li><a href="<?php echo site_url();?>main/profile"><i class="fa fa-user-circle" aria-hidden="true"></i> <?=$_SESSION['first_name']?></a></li>
									<li><a href="<?php echo site_url();?>main/changeuser"><i class="glyphicon glyphicon-cog" aria-hidden="true"></i> <?=$this->lang->line('utm_template_menu_users_edit_profile');?></a></li>
									<li><a href="<?=site_url()?>main/settings"><i class="glyphicon glyphicon-cog" aria-hidden="true"></i> <?=$this->lang->line('utm_template_menu_conf');?></a></li>
									<li><a href="#" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('system_about');?>" id="about"><i class="fa fa-exclamation-circle"></i> <?=$this->lang->line('utm_navbar_about')?></a></li>
									<li><hr class="separetor-hr"></li>
									<li><a href="<?php echo base_url().'main/logout' ?>"><i class="fa fa-sign-out"></i> <?=$this->lang->line('utm_template_menu_users_logout');?></a></li>
								</ul>
							</li>
						</ul>
        </div><!-- /.navbar-collapse -->
      </nav>
    </div>
  </header>
  <?php endif; ?>

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
    <div class="content col-sm-12 text-center">
      <div class="copyright">
        <a target="_blank" href="<?php echo $this->config->item("product_website_footer"); ?>">
          <i>&copy; <?php echo $this->lang->line("utm_template_footer_bluepex") . " | " . $this->lang->line("utm_template_footer_all_right") . " | " . $this->config->item("product_copyright_years")?></i>
        </a>
      </div>
    </div>
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
