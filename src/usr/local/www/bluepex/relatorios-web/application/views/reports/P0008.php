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
		<th align="left"><?=$this->lang->line('reports_p_user');?></th>
		<th align="left"><?=$this->lang->line('reports_p_ip');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_p_access');?></th>
		<th width="120" align="right"><?=$this->lang->line('reports_p_consumer');?></th>
	</tr>
</thead>
<tbody>
	<?php
		foreach ($data as $access) :
			if ($access->total == 0) {
				continue;
			}
	?>
	<tr>
		<td align="left"><?=$access->username?></td>
		<td align="left"><?=$access->ipaddress?></td>
		<td align="right"><?=$access->total?></td>
		<td align="right"><?=$access->size_bytes?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
