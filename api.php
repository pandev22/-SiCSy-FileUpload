<?php
require_once __DIR__ . '/key.php';
session_start();
require_once __DIR__ . '/../../main/fbdd.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

error_log("📁 FileUpload API - Action: " . ($_GET['action'] ?? 'none') . " - Method: " . $_SERVER['REQUEST_METHOD']);

$public_actions = ['list_files', 'download'];

$action = $_GET['action'] ?? '';
$is_public = in_array($action, $public_actions);

if (!isset($_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Session non valide']);
    exit;
}

$configPath = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($configPath), true);

$maxFileSize = ($config['param']['max_file_size'] ?? 10) * 1024 * 1024;
$allowedExtensions = explode(',', $config['param']['allowed_extensions'] ?? 'pdf,jpg,jpeg,png,gif,webp');
$maxFilesPerUpload = $config['param']['max_files_per_upload'] ?? 5;
$storagePath = $config['param']['storage_path'] ?? 'uploads';

$uploadDir = __DIR__ . '/' . $storagePath;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

function createUploadTable($mysqli) {
    try {
        $sql = "CREATE TABLE IF NOT EXISTS file_uploads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            user_id VARCHAR(200) NOT NULL,
            user_hash VARCHAR(64) NOT NULL,
            upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            is_active TINYINT(1) DEFAULT 1
        )";
        
        $mysqli->multi_query($sql);
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("📁 FileUpload API - Erreur création table: " . $e->getMessage());
        return false;
    }
}

createUploadTable($mysqli);

function validateFile($file) {
    global $maxFileSize, $allowedExtensions;
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($extension, $allowedExtensions)) {
        return ['valid' => false, 'error' => 'Extension de fichier non autorisée'];
    }
    
    if ($file['size'] > $maxFileSize) {
        return ['valid' => false, 'error' => 'Fichier trop volumineux'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Erreur lors du téléversement'];
    }
    
    return ['valid' => true];
}

function encrypt_data($data) {
    return openssl_encrypt($data, 'AES-256-CBC', FILEUPLOAD_SECRET_KEY, 0, FILEUPLOAD_SECRET_IV);
}

function decrypt_data($data) {
    return openssl_decrypt($data, 'AES-256-CBC', FILEUPLOAD_SECRET_KEY, 0, FILEUPLOAD_SECRET_IV);
}

function encrypt_file_content($data) {
    return openssl_encrypt($data, 'AES-256-CBC', FILEUPLOAD_SECRET_KEY, 0, FILEUPLOAD_SECRET_IV);
}

function decrypt_file_content($data) {
    return openssl_decrypt($data, 'AES-256-CBC', FILEUPLOAD_SECRET_KEY, 0, FILEUPLOAD_SECRET_IV);
}

function saveFileToDatabase($mysqli, $filename, $originalName, $filePath, $fileSize, $fileType, $userId) {
    try {
        $stmt = $mysqli->prepare("
            INSERT INTO file_uploads (filename, original_name, file_path, file_size, file_type, user_id, user_hash) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $encryptedFilename = encrypt_data($filename);
        $encryptedOriginalName = encrypt_data($originalName);
        $encryptedFilePath = encrypt_data($filePath);
        $encryptedFileType = encrypt_data($fileType);
        $encryptedUserId = encrypt_data($userId);
        $userHash = hash('sha256', $userId);
        
        $stmt->bind_param('sssssss', $encryptedFilename, $encryptedOriginalName, $encryptedFilePath, $fileSize, $encryptedFileType, $encryptedUserId, $userHash);
        $stmt->execute();
        
        try {
            $logStmt = $mysqli->prepare("INSERT INTO logs (IP, path, content, type, user) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $path = '/FileUpload';
            $content = encrypt_data('Téléversement fichier: ' . $originalName . ' (' . formatFileSize($fileSize) . ')');
            $type = 'upload';
            
            $logStmt->bind_param('sssss', $ip, $path, $content, $type, $userId);
            $logStmt->execute();
        } catch (mysqli_sql_exception $e) {
            error_log("📁 FileUpload API - Erreur création log: " . $e->getMessage());
        }
        
        return true;
    } catch (mysqli_sql_exception $e) {
        error_log("📁 FileUpload API - Erreur sauvegarde DB: " . $e->getMessage());
        return false;
    }
}

function formatFileSize($bytes) {
    if ($bytes === 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

switch ($action) {
    case 'upload':
        error_log("📁 FileUpload API - Action upload");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
        }
        
        if (!isset($_FILES['files'])) {
            echo json_encode(['error' => 'Aucun fichier reçu']);
            break;
        }
        
        $files = $_FILES['files'];
        $uploadedFiles = [];
        $errors = [];
        
        $fileCount = is_array($files['name']) ? count($files['name']) : 1;
        
        if ($fileCount > $maxFilesPerUpload) {
            echo json_encode(['error' => 'Trop de fichiers sélectionnés']);
            break;
        }
        
        for ($i = 0; $i < $fileCount; $i++) {
            $file = [
                'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size' => is_array($files['size']) ? $files['size'][$i] : $files['size']
            ];
            
            $validation = validateFile($file);
            
            if (!$validation['valid']) {
                $errors[] = $file['name'] . ': ' . $validation['error'];
                continue;
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . '/' . $filename;
            
            $fileContent = file_get_contents($file['tmp_name']);
            $encryptedContent = encrypt_file_content($fileContent);
            if (file_put_contents($filePath, $encryptedContent) !== false) {
                if (saveFileToDatabase($mysqli, $filename, $file['name'], $filePath, $file['size'], $file['type'], $_SESSION['username'])) {
                    $uploadedFiles[] = [
                        'original_name' => $file['name'],
                        'filename' => $filename,
                        'size' => $file['size'],
                        'type' => $file['type']
                    ];
                } else {
                    unlink($filePath);
                    $errors[] = $file['name'] . ': Erreur lors de la sauvegarde';
                }
            } else {
                $errors[] = $file['name'] . ': Erreur lors de l\'écriture du fichier';
            }
        }
        
        if (empty($uploadedFiles) && !empty($errors)) {
            echo json_encode(['error' => implode(', ', $errors)]);
        } else {
            echo json_encode([
                'success' => true,
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors
            ]);
        }
        break;
        
    case 'list_files':
        error_log("📁 FileUpload API - Action list_files");
        
        try {
            $userHash = hash('sha256', $_SESSION['username']);
            $stmt = $mysqli->prepare("
                SELECT * FROM file_uploads 
                WHERE is_active = 1 AND user_hash = ?
                ORDER BY upload_date DESC
            ");
            $stmt->bind_param('s', $userHash);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $userFiles = [];
            while ($file = $result->fetch_assoc()) {
                $file['filename'] = decrypt_data($file['filename']);
                $file['original_name'] = decrypt_data($file['original_name']);
                $file['file_path'] = decrypt_data($file['file_path']);
                $file['file_type'] = decrypt_data($file['file_type']);
                $file['user_id'] = decrypt_data($file['user_id']);
                $file['download_url'] = 'api.php?action=download&id=' . $file['id'];
                $file['formatted_size'] = formatFileSize($file['file_size']);
                $file['upload_date_formatted'] = date('d/m/Y H:i', strtotime($file['upload_date']));
                $userFiles[] = $file;
            }
            
            echo json_encode(['success' => true, 'files' => $userFiles]);
            
        } catch (mysqli_sql_exception $e) {
            error_log("📁 FileUpload API - Erreur list_files: " . $e->getMessage());
            echo json_encode(['error' => 'Erreur lors de la récupération des fichiers']);
        }
        break;
        
    case 'download':
        error_log("📁 FileUpload API - Action download");
        
        $fileId = $_GET['id'] ?? '';
        
        if (empty($fileId)) {
            http_response_code(404);
            echo json_encode(['error' => 'Fichier non trouvé']);
            break;
        }
        
        try {
            $stmt = $mysqli->prepare("
                SELECT * FROM file_uploads 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            $file = $result->fetch_assoc();
            
            if (!$file) {
                http_response_code(404);
                echo json_encode(['error' => 'Fichier non trouvé']);
                break;
            }
            
            if (!$is_public && decrypt_data($file['user_id']) !== $_SESSION['username']) {
                http_response_code(403);
                echo json_encode(['error' => 'Accès non autorisé']);
                break;
            }
            
            $filePath = decrypt_data($file['file_path']);
            $originalName = decrypt_data($file['original_name']);
            
            if (!file_exists($filePath)) {
                http_response_code(404);
                echo json_encode(['error' => 'Fichier introuvable sur le serveur']);
                break;
            }
            
            $encryptedContent = file_get_contents($filePath);
            $decryptedContent = decrypt_file_content($encryptedContent);
            
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $originalName . '"');
            header('Content-Length: ' . strlen($decryptedContent));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            
            echo $decryptedContent;
            exit;
            
        } catch (mysqli_sql_exception $e) {
            error_log("📁 FileUpload API - Erreur download: " . $e->getMessage());
            echo json_encode(['error' => 'Erreur lors du téléchargement']);
        }
        break;
        
    case 'delete':
        error_log("📁 FileUpload API - Action delete");
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Méthode non autorisée']);
            break;
        }
        
        $fileId = $_POST['id'] ?? '';
        
        if (empty($fileId)) {
            echo json_encode(['error' => 'ID de fichier manquant']);
            break;
        }
        
        try {
            $stmt = $mysqli->prepare("
                SELECT * FROM file_uploads 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            $file = $result->fetch_assoc();
            
            if (!$file || decrypt_data($file['user_id']) !== $_SESSION['username']) {
                echo json_encode(['error' => 'Fichier non trouvé ou accès non autorisé']);
                break;
            }
            
            $filePath = decrypt_data($file['file_path']);
            $originalName = decrypt_data($file['original_name']);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            $stmt = $mysqli->prepare("DELETE FROM file_uploads WHERE id = ?");
            $stmt->bind_param('i', $fileId);
            $stmt->execute();
            
            try {
                $logStmt = $mysqli->prepare("INSERT INTO logs (IP, path, content, type, user) VALUES (?, ?, ?, ?, ?)");
                $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
                $path = '/FileUpload';
                $content = encrypt_data('Suppression fichier: ' . $originalName);
                $type = 'delete';
                
                $logStmt->bind_param('sssss', $ip, $path, $content, $type, $_SESSION['username']);
                $logStmt->execute();
            } catch (mysqli_sql_exception $e) {
                error_log("📁 FileUpload API - Erreur création log: " . $e->getMessage());
            }
            
            echo json_encode(['success' => true]);
            
        } catch (mysqli_sql_exception $e) {
            error_log("📁 FileUpload API - Erreur delete: " . $e->getMessage());
            echo json_encode(['error' => 'Erreur lors de la suppression']);
        }
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Action non reconnue']);
        break;
}
?> 