(function ($) {

    $.fn.alertModal = function (options) {

        // Default options
        var settings = $.extend(true, {}, {
            id: 'modal-example',
            size: 'sm',
            title: 'Modal Example',
            content: '',
            type: 'default',
            timeout: false,
            footer: {
                show: true,
                btnClose: true,
                btnCloseDescr: 'Close',
                btnSave: true,
                btnSaveDescr: 'Save',
                btnSaveLocation: ''
            }
        }, options);

        // Modal HTML
        var modal_confirm = '<div class="modal modal-alert-' + settings.type + ' fade" tabindex="-1" role="dialog" id="' + settings.id + '">\n';
        modal_confirm += '\t<div class="modal-dialog modal-' + settings.size + '" role="document">\n';
        modal_confirm += '\t\t<div class="modal-content">\n';
        modal_confirm += '\t\t\t<div class="modal-header">\n';
        modal_confirm += '\t\t\t\t<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n';
        modal_confirm += '\t\t\t\t<h4 class="modal-title">' + settings.title + '</h4>\n';
        modal_confirm += '\t\t\t</div>\n';
        modal_confirm += '\t\t\t<div class="modal-body">\n';
        modal_confirm += '\t\t\t\t' + settings.content + '\n';
        modal_confirm += '\t\t\t</div>\n';
        if (settings.footer.show) {
            modal_confirm += '\t\t\t<div class="modal-footer">\n';
            if (settings.footer.btnClose) {
                modal_confirm += '\t\t\t\t<button type="button" class="btn btn-default" data-dismiss="modal">' + settings.footer.btnCloseDescr + '</button>\n';
            }
            if (settings.footer.btnSave) {
                modal_confirm += '\t\t\t\t<button type="button" class="btn btn-primary" id="btn-save">' + settings.footer.btnSaveDescr + '</button>\n';
            }
            modal_confirm += '\t\t\t</div>\n';
        }
        modal_confirm += '\t\t</div>\n';
        modal_confirm += '\t</div>\n';
        modal_confirm += '</div>\n';

        if ($(settings.modalId).length > 0) {
            $(settings.modalId).remove();
        }
        $(this).append(modal_confirm);
        $("#" + settings.id).modal();

        if (settings.footer.btnSaveLocation !== "") {
            $("#" + settings.id).on("click", "#btn-save", function () {
                location.href = settings.footer.btnSaveLocation;
            });
        }

        if (settings.timeout) {
            setTimeout(function () {
                $("#" + settings.id).modal("hide");
            }, settings.timeout);
        }
    };

    $.fn.alertInfo = function (options) {

        // Default options
        var settings = $.extend(true, {}, {
            id: 'alert-example',
            type: 'info',
            message: 'Alert Example',
            timeout: false
        }, options);

        // Alert HTML
        var alert_message = '<div class="alert alert-' + settings.type + ' alert-dismissible" role="alert" id="' + settings.id + '">\n';
        alert_message += '\t<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>\n';
        alert_message += '\t' + settings.message + '\n';
        alert_message += '</div>\n';

        if ($("#" + settings.id).length > 0) {
            $("#" + settings.id).remove();
        }
        $(this).append(alert_message);

        if (settings.timeout) {
            removeElementWithTimeout(settings.id, settings.timeout);
        }
    };

    function removeElementWithTimeout(element_id, timeout)
    {
        setTimeout(function () {
            $("#" + element_id).fadeOut(300, function () {
                $("#" + element_id).remove();
            });
        }, timeout);
    }

}(jQuery));
