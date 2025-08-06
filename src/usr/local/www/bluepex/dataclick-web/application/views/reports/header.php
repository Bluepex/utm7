<header>
	<table width="100%">
	<tbody>
		<tr>
			<td width="25%" align="left" valign="top">
				<?php if (isset($utm_default->logo_name, $utm_default->logo_content)) : ?>
				<img id="customer-logo" src="data:image/<?=pathinfo($utm_default->logo_name, PATHINFO_EXTENSION);?>;base64,<?=$utm_default->logo_content;?>" />
				<?php endif; ?>
			</td>
			<td width="50%" align="center" valign="top"><span class="title"><?=$report_title?></span></td>
			<td width="25%" align="right" valign="top">
				<img id="bluepex-logo" src="data:image/<?=pathinfo($bp_logo_file, PATHINFO_EXTENSION);?>;base64,<?=$bp_logo_content;?>" />
				<br />
				<span id="datetime">{DATE j-m-Y H:i}</span>
			</td>
		</tr>
	</tbody>
	</table>
</header>
