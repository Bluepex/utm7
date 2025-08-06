<div class="container">
  <div class="well title">
		<h3 class="text-center"><?=$this->lang->line("utm_template_menu_users_action_ban")?></h3>
	</div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="text-center">
        <div class="col-lg-4 col-lg-offset-4">
            <h5><?=$this->lang->line("utm_user_hello")?> <span><?php echo $first_name; ?></span>, <br><?=$this->lang->line("utm_user_ban_text")?> </h5>     
            <?php $fattr = array('class' => 'form-signin');
                echo form_open(site_url().'main/banuser/', $fattr); ?>
            <div class="form-group">
                <select class="form-control" name="email" id="email">
                    <?php
                    foreach($groups as $row)
                    { 
                      echo '<option value="'.$row->email.'">'.$row->email.'</option>';
                    }
                    ?>
                    </select>
            </div>

            <div class="form-group">
            <?php
                $dd_list = array(
                          'unban'   => $this->lang->line("utm_user_unban_action"),
                          'ban'   => $this->lang->line("utm_user_ban_action"),
                        );
                $dd_name = "banuser";
                echo form_dropdown($dd_name, $dd_list, set_value($dd_name),'class = "form-control" id="banuser"');
            ?>
            </div>
            <?php echo form_submit(array('value'=>$this->lang->line("utm_user_submit_action"), 'class'=>'btn btn-primary btn-block')); ?>
            <a href="<?php echo site_url().'main/users/';?>"><button type="button" class="btn btn-default btn-block"><?=$this->lang->line("utm_user_cancel_action")?></button></a>
            <?php echo form_close(); ?>
        </div>
      </div>
    </div>
  </div>
</div>