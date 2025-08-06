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
		<th align="left"><?=$this->lang->line('reports_p_ip');?></th>
		<th align="left"><?=$this->lang->line('reports_c_group');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_p_access');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_p_consumer');?></th>
		<th width="80" align="right">%</th>
	</tr>
</thead>
<tbody>
<?php
	$total = 0;
	foreach ($data as $user) :
		$total = $total+$user->total_consumed;
	endforeach;
	foreach ($data as $user) :
?>
	<tr>
		<td align="left"><?=$user->ipaddress?></td>
		<td align="left"><?=$user->groupname?></td>
		<td align="right"><?=$user->total_accessed?></td>
		<td align="right"><?=$user->total_consumed?></td>
		<td align="right"><?=($user->total_consumed > 0 && $total > 0) ? number_format(round((($user->total_consumed/$total)*100), 2), 2) : '0'; ?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
	<tr>
		<td colspan="4" align="right"><strong><?=$this->lang->line('reports_p_totalmb');?> <?=$total?></strong></td>
		<td></td>
	</tr>
</tfoot>
</table>
