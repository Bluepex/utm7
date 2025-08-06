<div class="container">
  <div class="well title">
		<h3 class="text-center"><?=$this->lang->line('utm_user_change_profile');?></h3>
	</div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="text-center">
        <div class="col-lg-4 col-lg-offset-4">
            <h5><?=$this->lang->line("utm_user_hello")?> <span><?php echo $first_name; ?></span>.<br><?=$this->lang->line("utm_change_perfil_user")?></h5>     
        <?php 
            $fattr = array('class' => 'form-signin');
            echo form_open(site_url().'main/changeuser/', $fattr); ?>
            
            <div class="form-group">
              <?php echo form_input(array('name'=>'firstname', 'id'=> 'firstname', 'placeholder'=>$this->lang->line("utm_user_first_name"), 'class'=>'form-control', 'value' => set_value('firstname', $groups->first_name))); ?>
              <?php echo form_error('firstname');?>
            </div>
            <div class="form-group">
              <?php echo form_input(array('name'=>'lastname', 'id'=> 'lastname', 'placeholder'=>$this->lang->line("utm_user_last_name"), 'class'=>'form-control', 'value'=> set_value('lastname', $groups->last_name))); ?>
              <?php echo form_error('lastname');?>
            </div>
            <div class="form-group">
              <?php echo form_input(array('name'=>'email', 'id'=> 'email', 'placeholder'=>'Email', 'class'=>'form-control', 'value'=> set_value('email', $groups->email))); ?>
            </div>
            <div class="form-group">
              <?php echo form_password(array('name'=>'password', 'id'=> 'password', 'placeholder'=>$this->lang->line("utm_user_pass"), 'class'=>'form-control', 'value' => set_value('password'))); ?>
              <?php echo form_error('password') ?>
            </div>
            <div class="form-group">
              <?php echo form_password(array('name'=>'passconf', 'id'=> 'passconf', 'placeholder'=>$this->lang->line("utm_user_confirm_pass"), 'class'=>'form-control', 'value'=> set_value('passconf'))); ?>
              <?php echo form_error('passconf') ?>
            </div>
            <?php echo form_submit(array('value'=>$this->lang->line("utm_user_submit_change"), 'class'=>'btn btn-primary btn-block')); ?>
            <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>