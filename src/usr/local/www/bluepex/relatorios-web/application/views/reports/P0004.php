<div id="info">
	<table width="100%">
	<tbody>
		<tr>
			<td width="50%" valign="top">
				<table>
				<tbody>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_p_period');?></label></td>
						<td><?=$interval?></td>
					</tr>
					<?php if (isset($interval_from, $interval_until)) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_p_interval_of');?></label></td>
						<td><?=$interval_from;?></td>
					</tr>
					<tr>
						<td width="80"><label><?=$this->lang->line('reports_p_interval_until');?></label></td>
						<td><?=$interval_until;?></td>
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
						<td width="150"><?=(!empty($utm->name)) ? $utm->name : shell_exec("hostname");?></td>
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
		<th align="left"><?=$this->lang->line('reports_p_domain');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_p_consumer');?></th>
		<th width="80" align="right">%</th>
	</tr>
</thead>
<tbody>
<?php
	$total = 0;
	foreach ($data as $obj) :
		$total = $total+$obj->value;
	endforeach;
	foreach ($data as $obj) :
		$total_consumed = (!empty($obj->value) && $total > 0) ? number_format((($obj->value/$total)*100), 2) : '0';
		if ($total_consumed == "0.00" || $total_consumed == "0") {
			continue;
		}
?>
	<tr>
		<td align="left"><?=$obj->item?></td>
		<td align="right"><?=$obj->value?></td>
		<td align="right"><?=$total_consumed?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
	<tr>
		<td colspan="2" align="right"><strong><?=$this->lang->line('reports_p_totalmb');?> <?=$total?></strong></td>
		<td></td>
	</tr>
</tfoot>
</table>
