<?php
// !!! NE PAS PARTAGER OU VERSIONNER CE FICHIER CHANGER LE "S3cureK3y-ChangeMe-" PAR UNE AUTRE CHAINE DE CARACTERES !!!
define('FILEUPLOAD_SECRET_KEY', 'S3cureK3y-ChangeMe-'.substr(hash('sha256', __DIR__), 0, 32));
define('FILEUPLOAD_SECRET_IV', substr(hash('sha256', __FILE__), 0, 16)); 