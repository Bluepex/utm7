<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="well title">
				<h3 class="text-center"><?=$this->lang->line('utm_edit_header');?></h3>
			</div>
			<div class="panel panel-default">
				<div class="panel-body">
					<form action="<?=base_url() . "utm/update/{$utm->id}"?>" method="POST">
						<div class="col-md-12">
							<div class="form-group">
								<label for="name"><?=$this->lang->line('utm_edit_name');?></label>
								<?php $utm->name = (set_value('name') ? set_value('name') : $utm->name); ?>
								<input type="text" name="name" class="form-control" id="name" placeholder="<?=$this->lang->line('utm_edit_name');?>" value="<?=$utm->name?>">
								<?php echo form_error('name', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label for="protocol"><?=$this->lang->line('utm_edit_protocol');?></label>
								<?php $utm->protocol = (set_value('protocol') ? set_value('protocol') : $utm->protocol); ?>
								<select name="protocol" class="form-control">
									<option value="http://" <?php if ($utm->protocol == "http://") echo "selected"; ?>>http://</option>
									<option value="https://" <?php if ($utm->protocol == "https://") echo "selected"; ?>>https://</option>
								</select>
								<?php echo form_error('protocol', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-7">
							<div class="form-group">
								<label for="host"><?=$this->lang->line('utm_edit_host');?></label>
								<?php $utm->host = (set_value('host') ? set_value('host') : $utm->host); ?>
								<input type="text" name="host" class="form-control" id="host" value="<?=$utm->host; ?>" placeholder="<?=$this->lang->line('utm_edit_host');?>">
								<?php echo form_error('host', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="port"><?=$this->lang->line('utm_edit_port');?></label>
								<?php $utm->port = (set_value('port') ? set_value('port') : $utm->port); ?>
								<input type="text" name="port" class="form-control" id="port" value="<?=$utm->port?>" placeholder="<?=$this->lang->line('utm_edit_port');?>">
								<?php echo form_error('port', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="username"><?=$this->lang->line('utm_edit_user');?></label>
								<?php $utm->username = (set_value('username') ? set_value('username') : $utm->username); ?>
								<input type="text" name="username" class="form-control" id="username" value="<?=$utm->username?>" placeholder="<?=$this->lang->line('utm_edit_user');?>">
								<?php echo form_error('username', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="password"><?=$this->lang->line('utm_edit_pass');?></label>
								<?php $utm->password = (set_value('password') ? set_value('password') : ""); ?>
								<input type="password" name="password" class="form-control" id="password" value="<?=$utm->password?>" placeholder="<?=$this->lang->line('utm_edit_pass');?>">
								<?php echo form_error('password', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<button type="submit" class="btn btn-default"><?=$this->lang->line('utm_edit_btn_update');?></button>
								<a class="btn btn-default" href="javascript:window.history.go(-1)"><?=$this->lang->line('utm_edit_btn_back');?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
