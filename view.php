<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../../account/login.php');
    exit;
}

require_once __DIR__ . '/../../main/fbdd.php';

$configPath = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($configPath), true);

$maxFileSize = $config['param']['max_file_size'] ?? 10;
$allowedExtensions = explode(',', $config['param']['allowed_extensions'] ?? 'pdf,jpg,jpeg,png,gif,webp');
$maxFilesPerUpload = $config['param']['max_files_per_upload'] ?? 5;
$storagePath = $config['param']['storage_path'] ?? 'uploads';

$uploadDir = __DIR__ . '/' . $storagePath;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Téléversement de fichiers - SICSY</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            flex: 1;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--accent-color);
        }

        .header p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }

        .card {
            background-color: var(--surface-color);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }

        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            margin-bottom: 20px;
            transition: border-color 0.3s;
            cursor: pointer;
        }

        .upload-area:hover {
            border-color: var(--accent-color);
        }

        .upload-area.dragover {
            border-color: var(--accent-color);
            background-color: rgba(0, 123, 255, 0.1);
        }

        .upload-icon {
            font-size: 3rem;
            margin-bottom: 15px;
            color: var(--accent-color);
        }

        .file-input {
            display: none;
        }

        .file-list {
            list-style: none;
            margin-top: 20px;
        }

        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin-bottom: 10px;
            background-color: var(--bg-color);
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }

        .file-info {
            flex: 1;
        }

        .file-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .file-size {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .file-status {
            margin-left: 10px;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .file-status.pending {
            background-color: var(--warning-color);
            color: #000;
        }

        .file-status.success {
            background-color: var(--success-color);
            color: white;
        }

        .file-status.error {
            background-color: var(--danger-color);
            color: white;
        }

        .upload-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s;
            width: 100%;
        }

        .upload-btn:hover {
            background-color: #0056b3;
        }

        .upload-btn:disabled {
            background-color: var(--text-muted);
            cursor: not-allowed;
        }

        .info-box {
            background-color: rgba(0, 123, 255, 0.1);
            border: 1px solid var(--accent-color);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box h3 {
            color: var(--accent-color);
            margin-bottom: 10px;
        }

        .info-list {
            list-style: none;
        }

        .info-list li {
            margin-bottom: 5px;
            color: var(--text-muted);
        }

        .back-btn {
            background-color: var(--surface-color);
            color: var(--font-color);
            border: 1px solid var(--border-color);
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }

        .back-btn:hover {
            background-color: var(--border-color);
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background-color: var(--border-color);
            border-radius: 3px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill {
            height: 100%;
            background-color: var(--accent-color);
            width: 0%;
            transition: width 0.3s;
        }

        .notif {
            position: fixed;
            top: 30px;
            right: 30px;
            min-width: 320px;
            max-width: 400px;
            padding: 18px 28px;
            border-radius: 10px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
            z-index: 9999;
            box-shadow: 0 4px 16px rgba(0,0,0,0.18);
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s, transform 0.3s;
        }
        .notif.success {
            background: linear-gradient(90deg, var(--success-color), #43e97b 99%);
        }
        .notif.error {
            background: linear-gradient(90deg, var(--danger-color), #ff5858 99%);
        }
        .notif.show {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../index.php" class="back-btn">← Retour à l'accueil</a>
        
        <div class="header">
            <h1>📁 Téléversement de fichiers</h1>
            <p>Téléversez vos fichiers PDF et images en toute sécurité</p>
            <div style="display: flex; justify-content: center; margin-top: 24px; margin-bottom: 10px;">
                <a href="files.php" class="btn btn-primary upload-files-link" style="font-size:1.1rem;padding:16px 32px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.10);font-weight:600;letter-spacing:0.5px;display:inline-flex;align-items:center;gap:10px;text-decoration:none;transition:background 0.2s;">
                    <span style="font-size:1.4em;">📂</span> Voir mes fichiers déjà téléversés
                </a>
            </div>
        </div>

        <div class="info-box">
            <h3>📋 Informations</h3>
            <ul class="info-list">
                <li>• Taille maximale par fichier : <?= $maxFileSize ?> MB</li>
                <li>• Extensions autorisées : <?= implode(', ', $allowedExtensions) ?></li>
                <li>• Nombre maximum de fichiers : <?= $maxFilesPerUpload ?></li>
                <li>• Vos fichiers sont stockés de manière sécurisée</li>
            </ul>
        </div>

        <div class="card">
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📤</div>
                <h3>Glissez-déposez vos fichiers ici</h3>
                <p>ou cliquez pour sélectionner des fichiers</p>
                <input type="file" id="fileInput" class="file-input" multiple accept="<?= '.' . implode(',.', $allowedExtensions) ?>">
            </div>

            <ul class="file-list" id="fileList"></ul>

            <button class="upload-btn" id="uploadBtn" disabled>
                Téléverser les fichiers
            </button>
        </div>
    </div>

    <div class="notif">
        <span id="notif-message"></span>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileList = document.getElementById('fileList');
        const uploadBtn = document.getElementById('uploadBtn');
        
        const maxFileSize = <?= $maxFileSize * 1024 * 1024 ?>;
        const allowedExtensions = <?= json_encode($allowedExtensions) ?>;
        const maxFilesPerUpload = <?= $maxFilesPerUpload ?>;
        
        let selectedFiles = [];

        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            handleFiles(e.dataTransfer.files);
        });
        
        fileInput.addEventListener('change', (e) => {
            handleFiles(e.target.files);
        });
        
        function handleFiles(files) {
            selectedFiles = [];
            fileList.innerHTML = '';
            
            for (let file of files) {
                if (selectedFiles.length >= maxFilesPerUpload) break;
                
                const extension = file.name.split('.').pop().toLowerCase();
                const isValidExtension = allowedExtensions.includes(extension);
                const isValidSize = file.size <= maxFileSize;
                
                if (isValidExtension && isValidSize) {
                    selectedFiles.push(file);
                    addFileToList(file);
                }
            }
            
            uploadBtn.disabled = selectedFiles.length === 0;
        }
        
        function addFileToList(file) {
            const li = document.createElement('li');
            li.className = 'file-item';
            
            const size = formatFileSize(file.size);
            
            li.innerHTML = `
                <div class="file-info">
                    <div class="file-name">${file.name}</div>
                    <div class="file-size">${size}</div>
                </div>
                <span class="file-status pending">En attente</span>
            `;
            
            fileList.appendChild(li);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function showNotif(message, type = 'success', duration = 3500) {
            const notif = document.createElement('div');
            notif.className = 'notif ' + type;
            notif.innerHTML = `<span>${type === 'success' ? '✅' : '❌'} ${message}</span>`;
            document.body.appendChild(notif);
            setTimeout(() => notif.classList.add('show'), 10);
            setTimeout(() => {
                notif.classList.remove('show');
                setTimeout(() => notif.remove(), 300);
            }, duration);
        }
        
        uploadBtn.addEventListener('click', async () => {
            if (selectedFiles.length === 0) return;
            uploadBtn.disabled = true;
            uploadBtn.textContent = 'Téléversement en cours...';
            const formData = new FormData();
            selectedFiles.forEach(file => {
                formData.append('files[]', file);
            });
            try {
                const response = await fetch('api.php?action=upload', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    updateFileStatuses('success', 'Téléversé avec succès');
                    setTimeout(() => {
                        showNotif('Tous les fichiers ont été téléversés avec succès !', 'success');
                        location.reload();
                    }, 1000);
                } else {
                    updateFileStatuses('error', 'Erreur lors du téléversement');
                    showNotif('Erreur : ' + result.error, 'error');
                }
            } catch (error) {
                updateFileStatuses('error', 'Erreur de connexion');
                showNotif('Erreur de connexion : ' + error.message, 'error');
            }
            uploadBtn.disabled = false;
            uploadBtn.textContent = 'Téléverser les fichiers';
        });
        
        function updateFileStatuses(status, message) {
            const statusElements = fileList.querySelectorAll('.file-status');
            statusElements.forEach(element => {
                element.className = `file-status ${status}`;
                element.textContent = message;
            });
        }
    </script>
</body>
</html> 