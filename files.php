<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: ../../account/login.php');
    exit;
}

$configPath = __DIR__ . '/config.json';
$config = json_decode(file_get_contents($configPath), true);

require_once __DIR__ . '/../../main/fbdd.php';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes fichiers - SICSY</title>
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
            max-width: 1200px;
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

        .file-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .file-card {
            background-color: var(--bg-color);
            border-radius: 8px;
            padding: 20px;
            border: 1px solid var(--border-color);
            transition: transform 0.2s, border-color 0.2s;
        }

        .file-card:hover {
            transform: translateY(-2px);
            border-color: var(--accent-color);
        }

        .file-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .file-name {
            font-weight: bold;
            margin-bottom: 8px;
            word-break: break-word;
        }

        .file-info {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .file-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
            transition: background-color 0.3s;
            flex: 1;
        }

        .btn-primary {
            background-color: var(--accent-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-danger {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 20px;
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

        .upload-btn {
            background-color: var(--success-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .upload-btn:hover {
            background-color: #218838;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: var(--text-muted);
        }

        .loading::after {
            content: '';
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 50%;
            border-top-color: var(--accent-color);
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .file-type-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            text-align: center;
        }

        .file-type-icon.pdf { color: #dc3545; }
        .file-type-icon.image { color: #28a745; }

        #notif-container {
            position: fixed;
            top: 30px;
            right: 30px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 12px;
            pointer-events: none;
        }
        .notif {
            min-width: 260px;
            max-width: 350px;
            background: var(--surface-color);
            color: var(--font-color);
            border-left: 6px solid var(--accent-color);
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.18);
            padding: 18px 22px 18px 18px;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0.98;
            animation: notifIn 0.3s;
            pointer-events: auto;
        }
        .notif.success { border-left-color: var(--success-color); }
        .notif.error { border-left-color: var(--danger-color); }
        .notif.info { border-left-color: var(--accent-color); }
        .notif .notif-close {
            margin-left: auto;
            background: none;
            border: none;
            color: var(--font-color);
            font-size: 1.2rem;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
            pointer-events: auto;
        }
        .notif .notif-close:hover { opacity: 1; }
        @keyframes notifIn {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 0.98; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../../index.php" class="back-btn">← Retour à l'accueil</a>
        
        <div class="header">
            <h1>📁 Mes fichiers</h1>
            <p>Gérez vos fichiers PDF et images téléversés</p>
            <a href="view.php" class="upload-btn">📤 Téléverser des fichiers</a>
        </div>

        <div class="card">
            <div id="fileContainer">
                <div class="loading">Chargement de vos fichiers...</div>
            </div>
        </div>
        <div id="notif-container"></div>
    </div>

    <script>
        function getFileIcon(filename) {
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (extension === 'pdf') {
                return '📄';
            } else if (imageExtensions.includes(extension)) {
                return '🖼️';
            } else {
                return '📁';
            }
        }

        function getFileTypeClass(filename) {
            const extension = filename.split('.').pop().toLowerCase();
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (extension === 'pdf') {
                return 'pdf';
            } else if (imageExtensions.includes(extension)) {
                return 'image';
            } else {
                return '';
            }
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showNotif(message, type = 'info', duration = 3500) {
            const notif = document.createElement('div');
            notif.className = 'notif ' + type;
            notif.innerHTML = `<span>${message}</span><button class="notif-close" onclick="this.parentNode.remove()">×</button>`;
            document.getElementById('notif-container').appendChild(notif);
            setTimeout(() => {
                if (notif.parentNode) notif.parentNode.removeChild(notif);
            }, duration);
        }

        async function loadFiles() {
            try {
                const response = await fetch('api.php?action=list_files');
                const result = await response.json();
                
                const container = document.getElementById('fileContainer');
                
                if (result.success) {
                    if (result.files.length === 0) {
                        container.innerHTML = `
                            <div class="empty-state">
                                <div class="icon">📁</div>
                                <h3>Aucun fichier trouvé</h3>
                                <p>Vous n'avez pas encore téléversé de fichiers.</p>
                                <a href="view.php" class="btn btn-primary">Téléverser votre premier fichier</a>
                            </div>
                        `;
                    } else {
                        const fileGrid = document.createElement('div');
                        fileGrid.className = 'file-grid';
                        
                        result.files.forEach(file => {
                            const fileCard = document.createElement('div');
                            fileCard.className = 'file-card';
                            
                            const icon = getFileIcon(file.original_name);
                            const typeClass = getFileTypeClass(file.original_name);
                            
                            fileCard.innerHTML = `
                                <div class="file-type-icon ${typeClass}">${icon}</div>
                                <div class="file-name">${file.original_name}</div>
                                <div class="file-info">
                                    <div>Taille: ${file.formatted_size}</div>
                                    <div>Type: ${file.file_type}</div>
                                    <div>Téléversé le: ${file.upload_date_formatted}</div>
                                </div>
                                <div class="file-actions">
                                    <a href="${file.download_url}" class="btn btn-primary">📥 Télécharger</a>
                                    <button onclick="deleteFile(${file.id}, '${file.original_name}')" class="btn btn-danger">🗑️ Supprimer</button>
                                </div>
                            `;
                            
                            fileGrid.appendChild(fileCard);
                        });
                        
                        container.innerHTML = '';
                        container.appendChild(fileGrid);
                    }
                } else {
                    container.innerHTML = `
                        <div class="empty-state">
                            <div class="icon">❌</div>
                            <h3>Erreur</h3>
                            <p>${result.error}</p>
                            <button onclick="loadFiles()" class="btn btn-primary">Réessayer</button>
                        </div>
                    `;
                }
            } catch (error) {
                const container = document.getElementById('fileContainer');
                container.innerHTML = `
                    <div class="empty-state">
                        <div class="icon">❌</div>
                        <h3>Erreur de connexion</h3>
                        <p>Impossible de charger vos fichiers.</p>
                        <button onclick="loadFiles()" class="btn btn-primary">Réessayer</button>
                    </div>
                `;
            }
        }

        async function deleteFile(fileId, fileName) {
            if (!confirm(`Êtes-vous sûr de vouloir supprimer "${fileName}" ?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('id', fileId);
                
                const response = await fetch('api.php?action=delete', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showNotif('Fichier supprimé avec succès !', 'success');
                    loadFiles();
                } else {
                    showNotif('Erreur lors de la suppression : ' + result.error, 'error');
                }
            } catch (error) {
                showNotif('Erreur de connexion lors de la suppression', 'error');
            }
        }

        loadFiles();
    </script>
</body>
</html>