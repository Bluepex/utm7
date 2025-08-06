<div id="info">
	<table width="100%">
	<tbody>
		<tr>
			<td width="50%" valign="top">
				<table>
				<tbody>
					<tr>
						<td width="80"><label>Período (de):</label></td>
						<td><?=convertDate($filter['interval_from'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
					<tr>
						<td width="80"><label>Período (até):</label></td>
						<td><?=convertDate($filter['interval_until'], 'Y-m-d H:i:s', 'd/m/Y H:i:s');?></td>
					</tr>
					<?php if (!empty($filter['username'])) : ?>
					<tr>
						<td width="80"><label>Usuário:</label></td>
						<td><?=$filter['username']?></td>
					</tr>
					<?php endif; ?>
					<?php if (!empty($filter['ipaddress'])) : ?>
					<tr>
						<td width="80"><label>IP Address:</label></td>
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
						<td width="80" align="right"><label>Endereço:</label></td>
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
		<th align="left">Domínio</th>
		<th align="left">Categorias</th>
		<th width="120" align="right">Status</th>
		<th width="120" align="right">Qtde. Acessos</th>
		<th width="120" align="right">Consumo (MB)</th>
		<th width="80" align="right">%</th>
	</tr>
</thead>
<tbody>
<?php
	$total = 0;
	foreach ($data as $_data) :
		$total = $total+$_data->total_consumed;
	endforeach;
	foreach ($data as $_data) :
?>
	<tr>
		<td align="left"><?=$_data->domain?></td>
		<td align="left"><?=$_data->categories?></td>
		<td align="right"><?=(($_data->blocked==0) ? 'Liberado' : 'Bloqueado');?></td>
		<td align="right"><?=$_data->total_accessed?></td>
		<td align="right"><?=$_data->total_consumed?></td>
		<td align="right"><?=($_data->total_consumed > 0 && $total > 0) ? number_format((($_data->total_consumed/$total)*100), 2) : '0'; ?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
	<tr>
		<td colspan="5" align="right"><strong>Total (MB): <?=$total?></strong></td>
		<td></td>
	</tr>
</tfoot>
</table>
