<div id="info">
	<table width="100%">
	<tbody>
		<tr>
			<td width="50%" valign="top">
				<table>
				<tbody>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_period");?></label></td>
						<td><?=convertDate($filter['interval_from'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
					<tr>
						<td width="80"><label><?=$this->lang->line("reports_c_period_until");?></label></td>
						<td><?=convertDate($filter['interval_until'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
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
						<td width="150"><?=$utm->name?></td>
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
		<th align="left"><?=$this->lang->line("reports_cmd_username");?></th>
		<th width="120" align="right"><?=$this->lang->line("reports_cmd_ip");?></th>
		<th width="120" align="right">URL</th>
		<th width="120" align="right"><?=$this->lang->line("reports_c_justification");?></th>
		<th width="80" align="right"><?=$this->lang->line("reports_c_rejected");?></th>
	</tr>
</thead>
<tbody>
<?php foreach ($data as $_data) : ?>
	<tr>
		<td align="left"><?=$_data->time_date?></td>
		<td align="left"><?=$_data->username?></td>
		<td align="right"><?=$_data->ip?></td>
		<td align="right"><?=$_data->url_blocked?></td>
		<td align="right"><?=$_data->reason?></td>
		<td align="right"><?=($_data->rejected == "1") ? $this->lang->line("reports_cmd_yes") : $this->lang->line("reports_cmd_no"); ?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
