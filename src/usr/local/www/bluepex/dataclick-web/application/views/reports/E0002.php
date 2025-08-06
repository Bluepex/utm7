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
<?php foreach ($data as $user) : ?>
<table class="table-default list" width="100%">
<thead>
	<tr>
		<th width="130" align="left"><?=$this->lang->line('reports_p_ip');?></th>
		<th align="left"><?=$this->lang->line('reports_c_group');?></th>
		<th width="100" align="right"><?=$this->lang->line('reports_p_access');?></th>
	</tr>
</thead>
<tbody>
	<tr>
		<td width="130" align="left"><?=$user->ipaddress?></td>
		<td align="left"><?=$user->groupname?></td>
		<td width="100" align="right"><?=count($user->sites)?></td>
	</tr>
</tbody>
</table>
<?php if (!empty($user->sites)) : ?>
<table class="table-default list-light" width="100%">
<thead>
	<tr>
		<th width="130" align="left">Data / Hora</th>
		<th width="600" align="left">Site</th>
		<th align="left"><?=$this->lang->line('reports_c_categ');?></th>
		<th width="100" align="right"><?=$this->lang->line('reports_p_consumer');?></th>
	</tr>
</thead>
<tbody>
<?php
	$total = 0;
	foreach ($user->sites as $site) :
		$total = $total+$site->total_consumed;
?>
	<tr>
		<td width="130"><?=$site->time_date?></td>
		<td width="600">
		<?php if (strlen($site->url) > 90) : ?>
			<?=substr($site->url, 0, 90);?>...
		<?php else : ?>
			<?=$site->url;?>
		<?php endif; ?>
		</td>
		<td><?=$site->categories?></td>
		<td width="100" align="right"><?=$site->total_consumed?></td>
	</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
	<tr>
		<td colspan="4" align="right"><strong><?=$this->lang->line('reports_p_totalmb');?> <?=$total?></strong></td>
	</tr>
</tfoot>
</table>
<?php endif; ?>
<br />
<?php endforeach; ?>
