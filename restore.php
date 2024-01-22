<?php
declare(strict_types=1);

namespace CMS\PhpBackup;

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

use CMS\PhpBackup\Api\ActionHandler;
use CMS\PhpBackup\Api\AjaxController;
use CMS\PhpBackup\App\RestoreRunner;
use CMS\PhpBackup\Core\AppConfig;

session_start();

$config = AppConfig::loadAppConfig($_GET['app']);
(new RestoreRunner($config))->registerActions();

?>
<head>
    <title>Step-by-Step Backup Restore</title>
    <?php AjaxController::printCsrfToken(); ?>
    <script src="assets/js/lib/jquery/jquery-3.7.1.min.js"></script>
    <script src="assets/js/restore.js"></script>
    <link rel="stylesheet" href="assets/css/step-container.css">
</head>
<main>
    <h1>Step-by-Step Backup Restore</h1>
    <div id="step-list-backups" action="list-backups" class="step-container active-step">
        <h2>Step 1: Select Remote Storage</h2>
        <div>
            <?php
            // Example array of storage labels
            $storageLabels = ['Local', 'pCloud'];

// Loop through the array to create radio buttons
foreach ($storageLabels as $index => $label) {
    $id = 'storage' . $index; // Unique ID for each radio button
    echo '<input type="radio" id="' . $id . '" name="remoteStorage" value="' . $label . '">';
    echo '<label for="' . $id . '">' . $label . '</label><br>';
}
?>
        </div>
        <div class="btn-container">
            <button id="btn-step-list-backups"
                    data-nonce='<?php echo ActionHandler::generateNonce('list-backups'); ?>'
                    data-action='list-backups'>Next</button>
        </div>
    </div>
</main>
<footer>
</footer>

