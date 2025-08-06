<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Desenvolvimento <desenvolvimento@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
 
require("guiconfig.inc");
require("wf_quarantine.inc");
if(isset($_POST['act'])) {
	if($_POST['act']=="apply") {
		$id = $_POST['id'];
		$ipaddress = $_POST['ip'];
		$url = $_POST['url'];
		$url = preg_match("#^\b(http:\/\/|https:\/\/)#", $url) ? parse_url($url, PHP_URL_HOST) : $url;
		$customlist_checked = $_POST['custom'];
		if(!empty($customlist_checked)) {
			foreach($customlist_checked as $custom)
				if($custom != "undefined")
					setUrlCustomList($custom, $url);
			apply_resync();
			if(!empty($id))
				updateStatusJustification($id);
			echo dgettext("BluePexWebFilter","URL allowed successfully!");
		} else {
			echo dgettext("BluePexWebFilter","Select custom list to allow.");
		}
	}
	if($_POST['act']=="allow") {
		$id = $_POST['id'];
		$url = base64_decode($_POST['url']);
		$ipaddress = $_POST['ip'];
		$username = $_POST['user'];
		$html  = '<a href="#" class="close"><img src="/themes/'.$g["theme"].'/images/icons/icon_block.gif" width="11" height="11" border="0" title="Close"></a>';
		$html .= '<h3>'.dgettext("BluePexWebFilter","Choose custom list to allow this solicitation").'</h3>';
		$html .= '<div id="msg"></div>';
		$html .= '<fieldset style="background-color: #f7f7f7; border:1px dotted #999">';
		$html .= '<legend><p><b>'.dgettext("BluePexWebFilter","Allow Justification").'</b></p></legend>';
		$html .= '<table style="background: #FFFFFF" width="100%" border="0" cellpadding="0" cellspacing="0">';
		$html .= '<tr><td colspan="2"><input type="checkbox" id="remove" value="'.$id.'">'.htmlentities(dgettext("BluePexWebFilter","Check this option to remove justification after allow access.")).'<br><br></td></tr>';
		$html .= '<tr><td width="100">URL:</td><td>'.$url.'</td></tr>';
		if(!empty($username))
			$html .= '<tr><td width="100">'.htmlentities(dgettext("BluePexWebFilter","Username:")).'</td><td>'.$username.'</td>';
		$html .= '<tr><td width="100">'.dgettext("BluePexWebFilter","Ipaddress:").'</td><td>'.$ipaddress.'</td></tr>';
		$html .= '</table>';
		$html .= '<br>';
		$custom = get_element_config('nf_content_custom');
		if(!empty($custom)) {
			$html .= '<table style="background: #FFFFFF" width="100%" border="0" cellpadding="0" cellspacing="0">';
			$html .= '<tr><td width="200" valign="top"><b>'.dgettext("BluePexWebFilter","Select Custom List:").'</b><br><br>';
			$html .= '<table style="background: #FFFFFF" width="100%" border="0" cellpadding="0" cellspacing="0">';
			$html .= '<tr><td>';
			$i=0;
			foreach($custom['item'] as $customlist) {
				$html .= '<input class="customlist" id="custom'.$i.'" type="checkbox" value="'.$customlist['name'].'">'.$customlist['name'].'<br>';
				$i++;
			}
		} else {
			$html .= '<b>'.dgettext("BluePexWebFilter","No custom list found!").' <a href="/webfilter/wf_custom_list.php">'.dgettext("BluePexWebFilter","Click here to create custom list.").'</a></b>';
		}
		$html .= '</td></tr></table></td></tr>';
		$html .= '</table>';
		$html .= '</fieldset><br>';
		$html .= "<input type='button' onClick='allow_custom(\"{$url}\", \"{$ipaddress}\")' value='apply'>";
		$html .= '<img src="/themes/'.$g['theme'].'/images/icons/icon_fw-update.gif" style="display:none; float:right" id="loading">';
		echo $html;
	}
}
?>
<script type="text/javascript">
function allow_custom(url, ip) {
	var custom = new Array();
	var total = jQuery('.customlist').length;
	var id = "";
	if(jQuery('#remove').is(':checked'))
		id = jQuery('#remove').val();
	if(total > 0) {
		for(var i=0; i<total;i++)
			if(jQuery('#custom'+i).is(':checked'))
				custom[i] = jQuery('#custom'+i).val();
		jQuery('#loading').fadeIn('fast');
		jQuery.post("wf_quarantine_data.php", { act: "apply", id: id, url: url, ip: ip, custom: custom }, function(data) {
			jQuery('#res'+id.trim()).fadeOut('slow');
			jQuery('#loading').fadeOut('fast');
			jQuery('#msg').html(data);
		});
	} else {
		jQuery('#msg').html('<?=dgettext("BluePexWebFilter","No custom list found!")?>');
	}
}
jQuery('.close').click(function (e) {
	e.preventDefault();
	jQuery('#mask').hide();
	jQuery('.window').hide();
});
</script>
