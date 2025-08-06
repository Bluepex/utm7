<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="well title">
				<h3 class="text-center"><?=$this->lang->line('utm_list_header');?></h3>
			</div>
			<div class="panel panel-default">
				<div class="panel-body">
					<p><a href="<?=base_url() . 'utm/create';?>" class="btn btn-default"><span class="glyphicon glyphicon-pencil" aria-hidden="true"></span> <?=$this->lang->line('utm_list_btn_register');?></a></p>
					<?php if (!empty($utms)) : ?>
					<div class="table-responsive">
						<table class="table table-bordered table-striped">
						<thead>
							<tr>
								<th><?=$this->lang->line('utm_list_name');?></th>
								<th><?=$this->lang->line('utm_list_host');?></th>
								<th class="text-center"><?=$this->lang->line('utm_list_port');?></th>
								<th class="text-center"><?=$this->lang->line('utm_list_user');?></th>
								<th class="text-center"><?=$this->lang->line('utm_list_serial');?></th>
								<th class="text-center"><?=$this->lang->line('utm_list_default');?></th>
								<th width="100"></th>
							</tr>
						</thead>
						<tbody>
						<?php 
							foreach ($utms as $utm) : 
								$class = ($utm->is_default == 1) ? "btn-success" : "btn-default";
						?>
							<tr>
								<td><?=$utm->name?></td>
								<td><?=$utm->protocol?><?=$utm->host?></td>
								<td class="text-center"><?=$utm->port?></td>
								<td class="text-center"><?=$utm->username?></td>
								<td class="text-center"><?=$utm->serial?></td>
								<td class="text-center">
									<a href="<?=base_url() . "utm/set-default/{$utm->id}";?>" class="btn <?=$class?> btn-xs" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_list_btn_default_title');?>"><?=$this->lang->line('utm_list_btn_default');?></a></td>
								<td>
									<a href="<?=base_url() . "utm/edit/{$utm->id}"?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_list_btn_edit');?>">
										<span class="glyphicon glyphicon-pencil" aria-hidden="true"></span>
									</a>
									<?php if ($utm->is_default != 1) : ?>
									<a href="<?=base_url() . "utm/remove/{$utm->id}"?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_list_btn_remove');?>">
										<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
									</a>
									<?php endif; ?>
									<a href="<?=base_url() . "utm/test-connection-sync/{$utm->id}"?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('utm_list_btn_test');?>">
										<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
						</table>
					</div>
					<?php else : ?>
					<p class="text-center"><?=$this->lang->line('utm_list_info');?></p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
