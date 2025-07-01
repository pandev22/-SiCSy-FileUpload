<?php
require_once __DIR__ . '/key.php';
function encrypt_data($data) {
    return openssl_encrypt($data, 'AES-256-CBC', FILEUPLOAD_SECRET_KEY, 0, FILEUPLOAD_SECRET_IV);
}

session_start();
require_once __DIR__ . '/../../main/fbdd.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../../account/login.php');
    exit;
}

$configPath = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($configPath), true);

$storagePath = $config['param']['storage_path'] ?? 'uploads';
$uploadDir = __DIR__ . '/' . $storagePath;

$installResults = [];

try {
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    $installResults['database'] = ['status' => 'success', 'message' => 'Connexion à la base de données réussie'];
} catch (Exception $e) {
    $installResults['database'] = ['status' => 'error', 'message' => 'Erreur de connexion à la base de données : ' . $e->getMessage()];
}

if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        $installResults['storage'] = ['status' => 'success', 'message' => 'Dossier de stockage créé : ' . $uploadDir];
    } else {
        $installResults['storage'] = ['status' => 'error', 'message' => 'Impossible de créer le dossier de stockage'];
    }
} else {
    $installResults['storage'] = ['status' => 'success', 'message' => 'Dossier de stockage existe déjà : ' . $uploadDir];
}

if (isset($installResults['database']) && $installResults['database']['status'] === 'success') {
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
        
        if ($mysqli->multi_query($sql) === TRUE) {
            $installResults['table'] = ['status' => 'success', 'message' => 'Table file_uploads créée avec succès'];
            $stmt = $mysqli->prepare("INSERT INTO logs (IP, path, content, type, user) VALUES (?, ?, ?, ?, ?)");
            $ip = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'];
            $path = '/FileUpload';
            $content = encrypt_data('Installation du module FileUpload');
            $type = 'install';
            $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
            $stmt->bind_param("sssss", $ip, $path, $content, $type, $user);
            $stmt->execute();
            $stmt->close();
        } else {
            throw new Exception("Erreur lors de la création de la table : " . $mysqli->error);
        }
    } catch (Exception $e) {
        $installResults['table'] = ['status' => 'error', 'message' => 'Erreur lors de la création de la table : ' . $e->getMessage()];
    }
}

if (is_dir($uploadDir)) {
    if (is_writable($uploadDir)) {
        $installResults['permissions'] = ['status' => 'success', 'message' => 'Permissions du dossier correctes'];
    } else {
        $installResults['permissions'] = ['status' => 'warning', 'message' => 'Le dossier n\'est pas accessible en écriture'];
    }
}

$allSuccess = true;
foreach ($installResults as $result) {
    if ($result['status'] === 'error') {
        $allSuccess = false;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation FileUpload - SICSY</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --surface-color: #2d2d2d;
            --border-color: #404040;
            --font-color: #ffffff;
            --accent-color: #007bff;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: var(--font-color);
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background-color: var(--surface-color);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .header h1 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .install-step {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .install-step.success {
            border-color: var(--success-color);
        }

        .install-step.error {
            border-color: var(--danger-color);
        }

        .install-step.warning {
            border-color: var(--warning-color);
        }

        .step-title {
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .step-title.success::before {
            content: '✅';
        }

        .step-title.error::before {
            content: '❌';
        }

        .step-title.warning::before {
            content: '⚠️';
        }

        .step-message {
            color: var(--text-muted);
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--accent-color);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 5px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .btn.success {
            background-color: var(--success-color);
        }

        .btn.success:hover {
            background-color: #218838;
        }

        .btn.danger {
            background-color: var(--danger-color);
        }

        .btn.danger:hover {
            background-color: #c82333;
        }

        .summary {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid var(--border-color);
        }

        .summary.success {
            border-color: var(--success-color);
        }

        .summary.error {
            border-color: var(--danger-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Installation du module FileUpload</h1>
            <p>Configuration et vérification du module de téléversement de fichiers</p>
        </div>

        <?php if ($allSuccess): ?>
            <div class="summary success">
                <h3>✅ Installation réussie !</h3>
                <p>Le module FileUpload a été installé avec succès et est prêt à être utilisé.</p>
            </div>
        <?php else: ?>
            <div class="summary error">
                <h3>❌ Problèmes détectés</h3>
                <p>Certains éléments nécessitent votre attention avant d'utiliser le module.</p>
            </div>
        <?php endif; ?>

        <h2>📋 Résultats de l'installation</h2>

        <?php foreach ($installResults as $step => $result): ?>
            <div class="install-step <?= $result['status'] ?>">
                <div class="step-title <?= $result['status'] ?>">
                    <?= ucfirst($step) ?>
                </div>
                <div class="step-message">
                    <?= $result['message'] ?>
                </div>
            </div>
        <?php endforeach; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="view.php" class="btn success">🚀 Commencer à utiliser le module</a>
            <a href="files.php" class="btn">📁 Voir mes fichiers</a>
            <a href="../../index.php" class="btn">🏠 Retour à l'accueil</a>
            
            <?php if (!$allSuccess): ?>
                <a href="install.php" class="btn danger">🔄 Réessayer l'installation</a>
            <?php endif; ?>
        </div>

        <div class="install-step">
            <h3>📖 Prochaines étapes</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Activez le module dans l'interface d'administration</li>
                <li>Configurez les paramètres selon vos besoins</li>
                <li>Testez le téléversement de fichiers</li>
                <li>Consultez la documentation pour plus d'informations</li>
            </ul>
        </div>
    </div>
</body>
</html> 