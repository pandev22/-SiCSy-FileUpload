<?php
$fileUploadActive = false;
$fileUploadConfigPath = __DIR__ . "/config.json";
if (file_exists($fileUploadConfigPath)) {
    $fileUploadConfig = json_decode(file_get_contents($fileUploadConfigPath), true);
    $fileUploadActive = ($fileUploadConfig && isset($fileUploadConfig['status']) && $fileUploadConfig['status'] === 'on');
}
require_once __DIR__ . '/../../main/fbdd.php';
if ($fileUploadActive): ?>
<button onclick="window.location.href='modules/FileUpload/view.php'" class="upload-file-button button">📁 Téléverser un document</button>
<?php endif; ?> 