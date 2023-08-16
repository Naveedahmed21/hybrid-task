
$(function(){
    $(document).on('click', '.delete', function () {
        let url = $(this).data('url');
        let tableId = $(this).data('table');
        let refresh = $(this).data('refresh');
        let div_id = $(this).data('div');
        let remove_div_id = $(this).data('remove-div');
        deleteConfirmation(url, tableId, refresh, div_id, remove_div_id);
    });

    initializeLibraries();
});




function deleteConfirmation(url, tableId, refresh = false, div_id = null, remove_div_id = null) {
    window.swal.fire({
        title: 'Are you sure?',
        text: "You want to delete this record",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: "Yes, delete it!"
    }).then((result) => {
        if (result.value) {
            window.swal.fire({
                title: "",
                text: "Please wait...",
                showConfirmButton: false,
                backdrop: true
            });

            window.axios.delete(url).then(response => {
                if (response.status === 200) {
                    window.swal.close();
                    $(tableId).DataTable().ajax.reload();
                    if (refresh) {
                        window.location.reload();
                    }
                    if (div_id != null) {
                        $(div_id).html(response.data.html);
                    }
                    if (remove_div_id != null) {
                        $(remove_div_id).remove();
                    }
                    // Show toast message
                    window.toast.fire({
                        icon: 'success',
                        title: response.data.message
                    });
                }
            }).catch(error => {
                window.swal.close();
                // Show toast message
                window.toast.fire({
                    icon: 'error',
                    title: error.response.data.message
                });
            });
        }
    });
}


$('body').on('submit', '[data-form=ajax-form]', function(e) {
    e.preventDefault();
    const form = this;
    sendAjaxForm(form);
});

function sendAjaxForm(form) {
    const _self = $(form);
    const btn = _self.find('[data-button=submit]');
    const btnHtml = btn.html();
    const dt = _self.data('datatable');
    const redirect = _self.data('redirect');

    btn.attr('disabled', 'disabled');
    btn.html(btnHtml + '&nbsp;&nbsp;<span class="spinner-border spinner-border-sm"></span>');

    axios({
        url: _self.attr('action'),
        method: _self.attr('method'),
        data: new FormData(_self[0]),
    })
    .then(response => {
        if (response.status == 200) {
            if (dt !== '') $(dt).DataTable().ajax.reload();
            toastMessage(response.data.message, 'success');
            if (redirect) {
                window.location.href = redirect;
            }
        }
        else toastMessage();
    })
    .catch(error => {
        toastMessage(error.response.data.message);
    })
    .finally(response => {
        btn.removeAttr('disabled');
        btn.html(btnHtml);
    });
}

function toastMessage(message = '', status = '') {
    status = status == '' ? 'error' : status;

    if (message == '') {
        message = status == 'error' ? 'Something went wrong' : 'Success';
    }

    window.toast.fire({
        title: message,
        icon: status,
    });
}

function initializeLibraries(select = ".select2"){
    $(select).select2({
        tags: $(this).attr('tags') !== 'undefined',
    });
}

