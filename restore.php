<?php
declare(strict_types=1);

if (!isset($_GET['app']) || empty($_GET['app'])) {
    $msg = 'Forbidden';
    header('HTTP/1.1 403 ' . $msg, true, 403);

    exit($msg);
}

require_once __DIR__ . DIRECTORY_SEPARATOR . 'defines.php';

if (!defined('ABS_PATH')) {
    $msg = 'ABS_PATH not defined';
    header('HTTP/1.1 500 ' . $msg, true, 500);

    exit($msg);
}

use CMS\PhpBackup\Api\AjaxController;
use CMS\PhpBackup\Api\ActionHandler;
session_start();
?>
<head>
    <?php AjaxController::printCsrfToken(); ?>
    <script src="assets/js/lib/jquery/jquery-3.7.1.min.js"></script>
</head>
<main>
    <h1>Restore Backup</h1>
    <button>Send</button>
</main>
<footer>
    <script>
        $(document).ready(function() {
            $("button").click(function(){
                const data = {
                    nonce: '<?php echo ActionHandler::generateNonce('test'); ?>a',
                    action: 'test',
                    data: ['...', '...']
                };
                $.ajax({
                    async: true,
                    url: 'ajax.php',
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    headers: {
                        'X-CSRF-Token': $('meta[name="HTTP_X_CSRF_TOKEN"]').attr('content')
                    },
                    success: function(response) {    
                        alert(response);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        // Error handler
                        console.error("AJAX Error: " + textStatus, errorThrown); 
                        alert(jqXHR.responseJSON.error);
                    },
                    complete: function() {
                        // This block will be executed regardless of success or failure
                    }
                });
            });
        });
    </script>
</footer>

