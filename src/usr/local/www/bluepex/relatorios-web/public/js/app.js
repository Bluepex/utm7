$(function () {
  $('[data-toggle="tooltip"]').tooltip()
})
function showLoadingInfo(element)
{
	$(element).parents('.dataclick-loading').find("#info").slideToggle("slow");
}
function stopLoading(id)
{
	if ($(".dataclick-loading").length == 0) {
		return;
	}
	$(".dataclick-loading").each(function() {
		if ($(this).data("loading-id") == id) {
			$(this).remove();
		}
	});
}
function htmlLoading(id, msg)
{
	var html = "<div class='text-center dataclick-loading' data-loading-id='"+id+"'>";
	    html += "<table>";
	    html += "<tbody>";
	    html += "<tr>";
	    html += "<td><div id='info' class='no-display'>" + msg + "</div></td>";
	    html += "<td><img src='"+base_url+"/public/images/cloud-loading.gif' onclick='showLoadingInfo(this)' /></td>";
	    html += "</tr>";
	    html += "</tbody>";
	    html += "</table>";
	    html += "</div>";

	var exists = false;
	$(".dataclick-loading").each(function() {
		if ($(this).data("loading-id") == id) {
			$(this).find("#info").html(msg);
			exists = true;
		}
	});
	if (!exists) {
		$("#loading").append(html);
	}
}

