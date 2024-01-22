$(document).ready(function() {
    function send_ajax(button, data, successCallback)
    {
        const dataToSend = {
            nonce: $(button).data('nonce'),
            action: $(button).data('action'),
            data: JSON.stringify(data)
        };

        $.ajax({
            async: true,
            url: 'ajax.php',
            type: 'POST',
            data: dataToSend,
            dataType: 'json',
            headers: {
                'X-CSRF-Token': $('meta[name="HTTP_X_CSRF_TOKEN"]').attr('content')
            },
            success: successCallback,
            error: function(jqXHR, textStatus, errorThrown) {
                // Error handler
                console.error("AJAX Error: " + textStatus, errorThrown); 
                alert(jqXHR.responseJSON.error);
            },
            complete: function() {
                // This block will be executed regardless of success or failure
            }
        });
    }


    $("#btn-step-list-backups").click(function(){
        const selectedValue = $('input[name="remoteStorage"]:checked').val();
        send_ajax(this, {remoteHandler: selectedValue}, function(response) {    
            alert(response);
        });
    });
});