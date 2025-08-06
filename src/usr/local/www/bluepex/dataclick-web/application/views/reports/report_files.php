<?php if (!empty($report_files)) : ?>
<div class="panel panel-default">
	<div class="panel-heading"><h4><?=$this->lang->line('reports_files_header');?></h4></div>
	<div class="panel-body">
		<table class="table table-bordered">
		<thead>
			<tr>
				<th><?=$this->lang->line('reports_files_date');?></th>
				<th><?=$this->lang->line('reports_files_report');?></th>
				<th><?=$this->lang->line('reports_files_filesize');?></th>
				<th><?=$this->lang->line('reports_files_download');?></th>
				<th>
					<a href="<?=base_url() . "reports/removeall"?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('reports_all_files_remove');?>" onclick="return confirm('<?=$this->lang->line('reports_confirm_all_files_remove');?>')">
						<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
					</a>
				</th>
			</tr>
		</thead>
		<tbody>
		<?php foreach ($report_files as $rf) : ?>
			<tr>
				<td><?=$rf['datetime']?></td>
				<td><?=$rf['report']?></td>
				<td><?=$rf['filesize']?></td>
				<td><a href="<?=base_url() . "reports/download/{$rf['file']}"?>" id="<?=$rf['file']?>" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('reports_files_download');?>"><?=$rf['file'];?></a></td>
				<td>
					<a href="<?=base_url() . "reports/remove/{$rf['file']}"?>" class="btn btn-xs btn-default" data-toggle="tooltip" data-placement="bottom" title="<?=$this->lang->line('reports_files_remove');?>">
						<span class="glyphicon glyphicon-trash" aria-hidden="true"></span>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
	</div>
</div>
<?php endif; ?>
<?php if (!empty($reports_queue)) : ?>
<div class="panel panel-default">
	<div class="panel-heading"><h4><?=$this->lang->line('reports_files_processing');?></h4></div>
	<div class="panel-body">
		<table class="table table-bordered">
		<tbody>
		<?php foreach ($reports_queue as $rq) : ?>
			<tr>
				<td><?=$rq->report_id?> - <?=getReports($rq->report_id)?></td>
				<td><a href="<?=base_url() . "reports/remove-queue/{$rq->id}"?>"><i class="fa fa-trash"></i></a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
		</table>
	</div>
</div>
<?php endif; ?>
