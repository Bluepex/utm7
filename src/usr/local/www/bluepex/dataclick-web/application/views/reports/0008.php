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
		<th align="left" width="140"><?=$this->lang->line('reports_c_date');?></th>
		<th align="left"><?=$this->lang->line('reports_c_session');?></th>
		<th align="left"><?=$this->lang->line('reports_c_user');?></th>
		<th align="left"><?=$this->lang->line('reports_c_descr');?></th>
	</tr>
</thead>
<tbody>
<?php
	foreach ($data as $obj) :
?>
	<tr>
		<td align="left"><?=$obj->event_date?></td>
		<td align="left"><?=$obj->session?></td>
		<td align="left"><?=$obj->username?></td>
		<td align="left"><?=vsprintf($this->lang->line($obj->descr), explode("|", $obj->complement))?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
</tfoot>
</table>
