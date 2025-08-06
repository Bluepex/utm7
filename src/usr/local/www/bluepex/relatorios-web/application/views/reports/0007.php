<div id="info">
	<table width="100%">
	<tbody>
		<tr>
			<td width="50%" valign="top">
				<table>
				<tbody>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_c_period');?></label></td>
						<td><?=convertDate($filter['interval_from'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_c_period_until');?></label></td>
						<td><?=convertDate($filter['interval_until'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
					<?php if (!empty($filter['username'])) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_c_user');?></label></td>
						<td><?=$filter['username']?></td>
					</tr>
					<?php endif; ?>
					<?php if (!empty($filter['ipaddress'])) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_c_ip');?></label></td>
						<td><?=$filter['ipaddress']?></td>
					</tr>
					<?php endif; ?>
				</tbody>
				</table>
			</td>
			<td width="50%" align="right" valign="top">
				<table align="right">
				<tbody>
					<tr>
						<td width="80" align="right"><label><?=$this->lang->line('reports_p_utm');?></label></td>
						<td width="150"><?=$utm->name?></td>
					</tr>
					<tr>
						<td width="80" align="right"><label><?=$this->lang->line('reports_p_address');?></label></td>
						<td width="150">
							<?=$utm->protocol?><?=$utm->host?>
							<?php if (($utm->protocol == "http://" && $utm->port != "80") && ($utm->protocol != "https://" && $utm->port != "443")) : ?>
							:<?=$utm->port?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
				</table>
			</td>
		</tr>
	</tbody>
	</table>

</div>
<table class="table-default list">
<thead>
	<tr>
		<th align="left"><?=$this->lang->line('reports_p_user');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_access_time');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_remote_ip');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_local_ip');?></th>
		<th width="80" align="right"><?=$this->lang->line('reports_c_port');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_bytes_send');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_bytes_rec');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_time_connect');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_c_time_disconnect');?></th>
	</tr>
</thead>
<tbody>
<?php
	$total = 0;
	foreach ($data as $obj) :
		$total = $total+$obj->connected_time;
	endforeach;
	foreach ($data as $obj) :
?>
	<tr>
		<td align="left"><?=$obj->username?></td>
		<td align="right"><?=$obj->connected_time?></td>
		<td align="right"><?=$obj->host_remote?></td>
		<td align="right"><?=$obj->host_local?></td>
		<td align="right"><?=$obj->port?></td>
		<td align="right"><?=$obj->bytes_sent?></td>
		<td align="right"><?=$obj->bytes_received?></td>
		<td align="right"><?=$obj->time_connect?></td>
		<td align="right"><?=$obj->time_disconnect?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
</tfoot>
</table>
