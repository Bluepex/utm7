<div class="container">
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="text-center">
        <div class="col-lg-4 col-lg-offset-4">
              <div class="class-logo-login-bp">
                <div class="d-flex">
                  <img src='<?=base_url() . "public/images/logo-simple.png"?>' style="width: auto;height: auto;margin: auto;margin-right:0px;"/>
                  <h2 style="margin: auto;margin-left:0px;">BluePex DataClick 2.0</h2>
                </div>
              </div>
              <h5><?=$this->lang->line("main_login_text_input")?></h5>
              <?php $fattr = array('class' => 'form-signin');
                  echo form_open(site_url().'main/login/', $fattr); ?>
              <div class="form-group">
                <?php echo form_input(array(
                    'name'=>'email', 
                    'id'=> 'email', 
                    'placeholder'=>'Email', 
                    'class'=>'form-control', 
                    'value'=> set_value('email'))); ?>
                <?php echo form_error('email') ?>
              </div>
              <div class="form-group">
                <?php echo form_password(array(
                    'name'=>'password', 
                    'id'=> 'password', 
                    'placeholder'=>'Senha', 
                    'class'=>'form-control', 
                    'value'=> set_value('password'))); ?>
                <?php echo form_error('password') ?>
              </div>
              <?php if($recaptcha == 'yes'){ ?>
              <div style="text-align:center;" class="form-group">
                  <div style="display: inline-block;"><?php echo $this->recaptcha->render(); ?></div>
              </div>
              <?php
              }
              echo form_submit(array('value'=> $this->lang->line("main_login_submit"), 'class'=>'btn btn-primary btn-block')); ?>
              <?php echo form_close(); ?>
              <br>
              <!--<p>Not registered? <a href="<?php echo site_url();?>main/register">Register</a></p>-->
              <p><?=$this->lang->line("main_login_pass_text")?> <a href="<?php echo site_url();?>main/forgot"><?=$this->lang->line("main_login_pass_text_recorver_link")?></a></p>
              <?php $returnAddress = explode("/",$_SERVER['HTTP_REFERER']);?>
              <p><a href="<?=$returnAddress[0]."//".$returnAddress[2]?>"><?=$this->lang->line("main_login_pass_text_return_login")?></a></p>
          </div>
        </div>
      </div>
    </div>
  </div>