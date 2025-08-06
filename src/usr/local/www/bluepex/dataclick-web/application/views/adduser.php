<div class="container">
  <div class="well title">
		<h3 class="text-center"><?=$this->lang->line('utm_template_menu_users_action_add')?></h3>
	</div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="text-center">
        <div class="col-lg-4 col-lg-offset-4">
          <h5><?=$this->lang->line("utm_user_hello")?> <?php echo $first_name; ?><br>
          <?=$this->lang->line("utm_user_add_new_user_text")?></h5>     
            <?php 
                $fattr = array('class' => 'form-signin');
                echo form_open('/main/adduser', $fattr);
            ?>
            <div class="form-group">
              <?php echo form_input(array('name'=>'firstname', 'id'=> 'firstname', 'placeholder'=>$this->lang->line("utm_user_first_name"), 'class'=>'form-control', 'value' => set_value('firstname'))); ?>
              <?php echo form_error('firstname');?>
            </div>
            <div class="form-group">
              <?php echo form_input(array('name'=>'lastname', 'id'=> 'lastname', 'placeholder'=>$this->lang->line("utm_user_last_name"), 'class'=>'form-control', 'value'=> set_value('lastname'))); ?>
              <?php echo form_error('lastname');?>
            </div>
            <div class="form-group">
              <?php echo form_input(array('name'=>'email', 'id'=> 'email', 'placeholder'=>'Email', 'class'=>'form-control', 'value'=> set_value('email'))); ?>
              <?php echo form_error('email');?>
            </div>
            <div class="form-group">
            <?php
                $dd_list = array(
                          '1'   => 'Admin',
                          '2'   => 'Author',
                          '3'   => 'Editor',
                          '4'   => 'Subscriber',
                        );
                $dd_name = "role";
                echo form_dropdown($dd_name, $dd_list, set_value($dd_name),'class = "form-control" id="role"');
            ?>
            </div>
            <div class="form-group">
              <?php echo form_password(array('name'=>'password', 'id'=> 'password', 'placeholder'=>$this->lang->line("utm_user_pass"), 'class'=>'form-control', 'value' => set_value('password'))); ?>
              <?php echo form_error('password') ?>
            </div>
            <div class="form-group">
              <?php echo form_password(array('name'=>'passconf', 'id'=> 'passconf', 'placeholder'=>$this->lang->line("utm_user_confirm_pass"), 'class'=>'form-control', 'value'=> set_value('passconf'))); ?>
              <?php echo form_error('passconf') ?>
            </div>
            <?php echo form_submit(array('value'=>$this->lang->line("utm_user_add_new_user"), 'class'=>'btn btn-primary btn-block')); ?>
            <?php echo form_close(); ?>
        </div>
        </div>
      </div>
    </div>
  </div>