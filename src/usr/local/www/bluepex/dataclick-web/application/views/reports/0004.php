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
<?php if(!empty($data)) :?>
<?php foreach ($data as $zone => $users) : ?>
<p><b><?=$this->lang->line('reports_zone');?>: <?=$zone?></b></p>
<table class="table-default list">
<thead>
	<tr>
		<th align="left"><?=$this->lang->line('reports_p_user');?></th>
		<th align="right"><?=$this->lang->line('reports_c_source');?></th>
		<th align="right"><?=$this->lang->line('reports_p_ip');?></th>
		<th align="right"><?=$this->lang->line('reports_c_mac');?></th>
		<th align="right"><?=$this->lang->line('reports_c_name');?></th>
		<th align="right"><?=$this->lang->line('reports_c_last_name');?></th>
		<th align="right">App id</th>
		<th align="right">Facebook</th>
		<th align="right">CPF</th>
		<th align="right">E-mail</th>
		<th align="right"><?=$this->lang->line('reports_c_birthday');?></th>
		<!--<th align="right"><?=$this->lang->line('reports_c_form1');?></th>-->
		<!--<th align="right"><?=$this->lang->line('reports_c_form2');?></th>-->
		<th align="right"><?=$this->lang->line('reports_c_begin_activities');?></th>
		<th align="right"><?=$this->lang->line('reports_c_last_activities');?></th>
		<th align="right"><?=$this->lang->line('reports_c_disp');?></th>
	</tr>
</thead>
<tbody>
<?php foreach ($users as $user) : ?>
	<tr>
		<?php
			switch($user->provider) {
				case 'facebook':
					$provider = "Facebook";
					break;
				case 'self_regist':
					$provider = "Formulário";
					break;
				case 'baselocal':
					$provider = "Base Local";
					break;
				default:
					$provider = $user->provider;
					break;
			}
		?>
		<td align="left"><?=isset($user->username) ? $user->username : ""?>
		<td align="right"><?=$provider?></td>
		<td align="right"><?=isset($user->ip) ? $user->ip : ""?>
		<td align="right"><?=isset($user->mac) ? $user->mac : ""?>
		<td align="right"><?=isset($user->first_name) ? $user->first_name : ""?>
		<td align="right"><?=isset($user->last_name) ? $user->last_name : ""?>
		<td align="right"><?=isset($user->uid) ? $user->uid : ""?>
		<td align="right"><?=isset($user->profile) ? $user->profile : ""?>
		<td align="right"><?=isset($user->cpf) ? $user->cpf : ""?>
		<td align="right"><?=isset($user->email) ? $user->email : ""?>
		<td align="right"><?=isset($user->birthday) ? $user->birthday : ""?>
		<!--<td align="right"><?=isset($user->frm_custom1) ? $user->frm_custom1 : ""?>-->
		<!--<td align="right"><?=isset($user->frm_custom2) ? $user->frm_custom2 : ""?>-->
		<td align="right"><?=isset($user->connect_start) ? $user->connect_start : ""?>
		<td align="right"><?=isset($user->last_activity) ? $user->last_activity : ""?>
		<td align="right"><?=isset($user->user_agent) ? $user->user_agent : ""?>
	</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endforeach; ?>
<?php endif; ?>
