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
		<th align="left">ID da Regra</th>
		<th align="left">Ação</th>
		<th align="left">Regra</th>
		<th align="left">Classificação</th>
		<th align="left">IP Origem</th>
		<th align="left">Direção</th>
		<th align="left">IP Destino</th>
	</tr>
</thead>
<tbody>
	<?php
		$total = 0;
        	foreach ($data as $obj) :
                	$total = $total+$obj->value;
        	endforeach;

		foreach ($data as $access) :
	?>
	<tr>
		<td align="left"><?=$access->id_rule?></td>
		<td align="left"><?=$access->action?></td>
		<td align="left"><?=$access->rule?></td>
		<td align="left"><?=$access->classification?></td>
		<td align="left"><?=$access->src_ip_port?></td>
		<td align="center"><?=$access->dir?></td>
		<td align="left"><?=$access->dst_ip_port?></td>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
