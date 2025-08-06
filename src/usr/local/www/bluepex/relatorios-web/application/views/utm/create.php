<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="well title">
				<h3 class="text-center"><?=$this->lang->line('utm_create_header');?></h3>
			</div>
			<div class="panel panel-default">
				<div class="panel-body">
					<form action="<?=base_url() . "utm/insert"?>" method="POST">
						<div class="col-md-12">
							<div class="form-group">
								<label for="name"><?=$this->lang->line('utm_create_name');?></label>
								<input type="text" name="name" class="form-control" id="name" placeholder="<?=$this->lang->line('utm_create_name');?>" value="<?php echo set_value('name'); ?>">
								<?php echo form_error('name', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-2">
							<div class="form-group">
								<label for="host"><?=$this->lang->line('utm_create_protocol');?></label>
								<select name="protocol" class="form-control">
									<option value="http://" <?php if (set_value('protocol') == "http://") echo "selected=selected'"; ?>>http://</option>
									<option value="https://" <?php if (set_value('protocol') == "https://") echo "selected=selected'"; ?>>https://</option>
								</select>
							</div>
						</div>
						<div class="col-md-7">
							<div class="form-group">
								<label for="host"><?=$this->lang->line('utm_create_host');?></label>
								<input type="text" name="host" class="form-control" id="host" placeholder="<?=$this->lang->line('utm_create_host');?>" value="<?php echo set_value('host'); ?>">
								<?php echo form_error('host', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-3">
							<div class="form-group">
								<label for="port"><?=$this->lang->line('utm_create_port');?></label>
								<input type="text" name="port" class="form-control" id="port" placeholder="<?=$this->lang->line('utm_create_port');?>" value="<?php echo set_value('port'); ?>">
								<?php echo form_error('port', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="username"><?=$this->lang->line('utm_create_user');?></label>
								<input type="text" name="username" class="form-control" id="username" placeholder="<?=$this->lang->line('utm_create_user');?>" value="<?php echo set_value('username'); ?>">
								<?php echo form_error('username', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="password"><?=$this->lang->line('utm_create_pass');?></label>
								<input type="password" name="password" class="form-control" id="password" placeholder="<?=$this->lang->line('utm_create_pass');?>" value="<?php echo set_value('password'); ?>">
								<?php echo form_error('password', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<button type="submit" class="btn btn-default"><?=$this->lang->line('utm_create_btn_reg');?></button>
								<a class="btn btn-default" href="javascript:window.history.go(-1)"><?=$this->lang->line('utm_create_btn_back');?></a>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
