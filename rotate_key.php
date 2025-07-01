<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'admin') {
    die('Accès réservé à l’administrateur.');
}

require_once __DIR__ . '/key.php';
$old_key = FILEUPLOAD_SECRET_KEY;
$old_iv = FILEUPLOAD_SECRET_IV;

function decrypt_old($data) {
    global $old_key, $old_iv;
    return openssl_decrypt($data, 'AES-256-CBC', $old_key, 0, $old_iv);
}

$new_key = 'S3cureK3y-'.bin2hex(random_bytes(16));
$new_iv = substr(hash('sha256', uniqid('', true)), 0, 16);

function encrypt_new($data, $key, $iv) {
    return openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
}

require_once __DIR__ . '/../../main/fbdd.php';
$pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query('SELECT * FROM file_uploads');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    $id = $row['id'];
    $filename = encrypt_new(decrypt_old($row['filename']), $new_key, $new_iv);
    $original_name = encrypt_new(decrypt_old($row['original_name']), $new_key, $new_iv);
    $file_path = encrypt_new(decrypt_old($row['file_path']), $new_key, $new_iv);
    $file_type = encrypt_new(decrypt_old($row['file_type']), $new_key, $new_iv);
    $user_id = encrypt_new(decrypt_old($row['user_id']), $new_key, $new_iv);
    $stmt2 = $pdo->prepare('UPDATE file_uploads SET filename=?, original_name=?, file_path=?, file_type=?, user_id=? WHERE id=?');
    $stmt2->execute([$filename, $original_name, $file_path, $file_type, $user_id, $id]);
}

$stmt = $pdo->prepare("SELECT ID, content FROM logs WHERE path = '/FileUpload'");
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($logs as $log) {
    $id = $log['ID'];
    $content = encrypt_new(decrypt_old($log['content']), $new_key, $new_iv);
    $stmt2 = $pdo->prepare('UPDATE logs SET content=? WHERE ID=?');
    $stmt2->execute([$content, $id]);
}

$keyfile = __DIR__ . '/key.php';
file_put_contents($keyfile, "<?php\ndefine('FILEUPLOAD_SECRET_KEY', '".$new_key."');\ndefine('FILEUPLOAD_SECRET_IV', '".$new_iv."');\n");

echo "Rotation de clé terminée avec succès !"; 