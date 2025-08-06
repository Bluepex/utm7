<div id="info">
	<table width="100%">
	<tbody>
		<tr>
			<td width="50%" valign="top">
				<table>
				<tbody>
					<?php if (isset($interval_from, $interval_until)) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_period");?></label></td>
						<td><?=$interval_from?></td>
					</tr>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_period_until");?></label></td>
						<td><?=$interval_until?></td>
					</tr>
					<?php endif; ?>
					<?php if (!empty($filter['username'])) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_user");?></label></td>
						<td><?=$filter['username']?></td>
					</tr>
					<?php endif; ?>
					<?php if (!empty($filter['ipaddress'])) : ?>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_ip");?></label></td>
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
						<td width="80" align="right"><label>UTM:</label></td>
						<td width="150"><?=(!empty($utm->name)) ? $utm->name : shell_exec("hostname");?></td>
					</tr>
					<tr>
						<td width="80" align="right"><label><?=$this->lang->line("reports_c_address");?></label></td>
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
		<th align="left"><?=$this->lang->line("reports_cmd_date");?></th>
		<th align="left">Assinatura</th>
		<th width="120" align="right">Ip</th>
		<th width="120" align="right">Usuário</th>
		<th width="120" align="right">Ação</th>
	</tr>
</thead>
<tbody>
<?php foreach ($data as $_data) : ?>
	<tr>
		<td align="left"><?=$_data->time_date?></td>
		<td align="left"><?=$_data->rule?></td>
		<td align="right"><?=$_data->src_ip_port?></td>
		<td align="right"><?=$_data->dst_ip_port?></td>
		<td align="right"><?=$_data->action?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
