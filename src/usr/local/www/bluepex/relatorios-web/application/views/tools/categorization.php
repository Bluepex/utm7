<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="well title">
				<h3 class="text-center"><?=$this->lang->line('tools_categorization_header');?></h3>
			</div>
			<div class="panel panel-default">
				<div class="panel-body">
					<form action="<?=base_url() . "tools/categorization/send"?>" method="POST">
						<div class="col-md-12">
							<div class="form-group">
								<label for="url"><?=$this->lang->line('tools_cat_input_url');?></label>
								<input type="text" name="url" class="form-control" id="url" placeholder="<?=$this->lang->line('tools_cat_input_url_placeholder');?>" value="<?php echo set_value('url'); ?>">
								<?php echo form_error('url', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="category"><?=$this->lang->line('tools_cat_input_category');?></label>
								<select name="category" class="form-control">
								<?php foreach (getWfCategories() as $id => $cat) : ?>
									<option value="<?=$cat?>"><?=$cat?></option>
								<?php endforeach; ?>
								</select>
								<?php echo form_error('category', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="category_suggested"><?=$this->lang->line('tools_cat_input_category_suggest');?></label>
								<select name="category_suggested" class="form-control">
								<?php foreach (getWfCategories() as $id => $cat) : ?>
									<option value="<?=$cat?>"><?=$cat?></option>
								<?php endforeach; ?>
								</select>
								<?php echo form_error('category_suggested', '<span class="form-field-error">', '</span>'); ?>
							</div>
						</div>
						<div class="col-md-12">
							<div class="form-group">
								<button type="submit" class="btn btn-default"><?=$this->lang->line('btn_send');?></button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
