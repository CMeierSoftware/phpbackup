<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (empty($_SESSION['CsrfToken'])) {
    $_SESSION['CsrfToken'] = bin2hex(random_bytes(32));
}

?>
<head>
    <script src="assets/js/lib/jquery/jquery-3.7.1.min.js"></script>
    <meta name="csrf-token" content="<?php echo $_SESSION['CsrfToken']; ?>">
</head>
<main>
    <h1>Restore Backup</h1>
    <button>Send</button>
</main>
<footer>
    <script>
        $(document).ready(function() {
            $("button").click(function(){
                const settings = {
            crossDomain: true,
                    contentType: "application/json; charset=utf-8",
                    async:false,
                    url: "ajax.php",
                    success: function(result){
                       alert(result);
                    },
                    error: function(jqXHR, textStatus, error){
                       alert(error);
                    }
                };
                $.ajaxSetup({
                    headers : {
                        'CsrfToken': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                $.ajax(settings);
            });
        });
    </script>
</footer>

