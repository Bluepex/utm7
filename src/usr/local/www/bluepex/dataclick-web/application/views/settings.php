<div class="container">
  <div class="well title">
		<h3 class="text-center"><?=$this->lang->line('utm_template_menu_conf');?></h3>
	</div>
  <div class="panel panel-default">
    <div class="panel-body">
      <div class="text-center">
        <div class="col-lg-8 col-lg-offset-2">
            <h5><?=$this->lang->line("utm_user_hello")?> <span><?php echo $first_name; ?></span>.<br><?=$this->lang->line("utm_change_perfil_user")?></h5>     
            <?php
            $fattr = array('class' => 'form-signin');
            echo form_open(site_url().'main/settings/', $fattr); 
            
            function tz_list() {
                $zones_array = array();
                $timestamp = time();
                foreach(timezone_identifiers_list() as $key => $zone) {
                  date_default_timezone_set($zone);
                  $zones_array[$key]['zone'] = $zone;
                }
                return $zones_array;
            }
            ?>
            
            <?php echo '<input type="hidden" name="id" value="'.$id.'">'; ?>
            <div class="form-group">
            <span><?=$this->lang->line("utm_user_title_set")?></span>
              <?php echo form_input(array('name'=>'site_title', 'id'=> 'site_title', 'placeholder'=>$this->lang->line("utm_user_title_set"), 'class'=>'form-control', 'value' => set_value('site_title', $site_title))); ?>
              <?php echo form_error('site_title');?>
            </div>
            
            <div class="form-group">
            <span><?=$this->lang->line("utm_user_timezone")?></span>
            <select name="timezone" id="timezone" class="form-control">
                <option value="<?php echo $timezonevalue; ?>"><?php echo $timezone; ?></option>
              <?php foreach(tz_list() as $t) { ?>
                <option value="<?php echo $t['zone']; ?>"> <?php echo $t['zone']; ?></option>
              <?php } ?>
            </select>
            </div>
            
            <div class="form-group">
            <span>Recaptcha</span>
            <select name="recaptcha" id="recaptcha" class="form-control">
                <option value="no"><?=$this->lang->line("utm_user_option_no")?></option>
                <option value="yes"><?=$this->lang->line("utm_user_option_yes")?></option>
            </select>
            </div>

            <div class="form-group">
            <span><?=$this->lang->line("utm_user_theme")?></span>
            <select name="theme" id="theme" class="form-control">
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/cosmo/bootstrap.min.css">Cosmo</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/darkly/bootstrap.min.css">Darkly</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/flatly/bootstrap.min.css">Flatly</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/journal/bootstrap.min.css">Journal</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/lumen/bootstrap.min.css">Lumen</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/slate/bootstrap.min.css">Slate</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/superhero/bootstrap.min.css">Superhero</option>
                <option value="https://maxcdn.bootstrapcdn.com/bootswatch/3.3.7/yeti/bootstrap.min.css">Yeti</option>
            </select>
            </div>
            <?php echo form_submit(array('value'=>$this->lang->line("utm_user_submit_action"), 'name'=>'submit', 'class'=>'btn btn-primary btn-block')); ?>
            <?php echo form_close(); ?>
        </div>
    </div>
    </div>
  </div>
  </div>
  </div>
</div>