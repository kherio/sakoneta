<?php
// Herramienta de diagnóstico temporal. Ábrelo en el navegador para ver
// qué límites de subida está aplicando REALMENTE tu servidor (no solo
// lo que dicen los ficheros .htaccess / .user.ini, sino el valor final
// que usa PHP en este directorio). Bórralo del servidor en cuanto lo
// hayas comprobado: no debe quedar publicado de forma permanente.
header('Content-Type: text/plain; charset=utf-8');
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "\nSi upload_max_filesize NO pone 20M aquí, Apache no está leyendo\n";
echo "el .htaccess de este directorio (revisa AllowOverride en el\n";
echo "VirtualHost) o estás en PHP-FPM y hace falta recargar el servicio.\n";
