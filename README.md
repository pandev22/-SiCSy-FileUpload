# 📁 Module FileUpload - SICSY

## Description

Le module **FileUpload** permet aux utilisateurs de téléverser, visualiser, télécharger et supprimer des fichiers PDF ou images, de façon sécurisée et chiffrée, sans modifier le cœur de SICSY. Il s'intègre dynamiquement à l'interface principale et respecte le style du thème.

## ✨ Fonctionnalités

- 📤 Téléversement de fichiers PDF et images (jpg, jpeg, png, gif, webp)
- 🖱️ Interface drag & drop moderne, notifications CSS
- 📂 Liste des fichiers déjà téléversés, suppression, téléchargement
- 🔒 Chiffrement des données et des fichiers
- 📝 Logs d'activité chiffrés
- 🧩 Bouton d'accès dynamique dans l'UI principale (affiché seulement si le module est activé)
- ⚙️ Configuration avancée (taille, extensions, nombre, etc.)
- 🌙 Compatible avec le thème DarkModern

## 🚀 Installation

1. **Téléchargez** ou clonez le dossier `FileUpload` dans le dossier `modules/` de SICSY.
2. **Placez** le dossier `FileUpload` dans le dossier principal de votre SICSY.
3. **Sécurisez la clé** : ouvrez le fichier `key.php` et modifiez la valeur `S3cureK3y-ChangeMe-` (remplacez "ChangeMe" par une chaîne de plus de 25 caractères, avec majuscules, minuscules, chiffres et caractères spéciaux).
4. **Lancez l'installation** : rendez-vous sur `votre-domaine/modules/FileUpload/install.php` et suivez les instructions à l'écran.
5. **Supprimez** le fichier `install.php` une fois l'installation terminée (sécurité).
6. **Intégrez le bouton dans l'interface principale** :

   - **Dans la balise `<script>` de votre `index.php` :**

```php
var fileSharingActive = <?php echo json_encode($fileShareActive); ?>;
<?php // Début
$fileUploadActive = false;
$fileUploadConfigPath = 'modules/FileUpload/config.json';
if (file_exists($fileUploadConfigPath)) {
    $fileUploadConfig = json_decode(file_get_contents($fileUploadConfigPath), true);
    $fileUploadActive = ($fileUploadConfig && isset($fileUploadConfig['status']) && $fileUploadConfig['status'] === 'on');
}
?>
var fileUploadActive = <?php echo json_encode($fileUploadActive); ?>;
```

   - **Avant la balise `</html>` :**

```html
<script src="modules/FileUpload/button.js"></script>
```

7. **Activez** le module dans le panneau d'administration SICSY.

> ⚠️ **Attention** : Ne supprimez pas le fichier `.htaccess` dans le dossier `uploads/` pour garantir la sécurité de vos documents !

🎉 Bravo, vous avez maintenant le module FileUpload by Pan_dev !

## 🛡️ Sécurité

- 🔐 Chiffrement AES-256-CBC des données sensibles et des fichiers
- 📝 Logs d'activité chiffrés
- 🚫 Accès aux fichiers uniquement via l'API (pas d'accès direct au dossier uploads)
- 👤 Vérification de session et des droits utilisateur

## 📄 Licence

Ce module suit la même licence que SICSY.

**Pour toute question, consultez la documentation SICSY ou ouvrez une issue sur le dépôt.** 
