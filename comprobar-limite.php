<?php
// Herramienta de diagnóstico temporal. Ábrelo en el navegador para ver
// qué límites de subida está aplicando REALMENTE tu servidor (no solo
// lo que dicen los ficheros .htaccess / .user.ini, sino el valor final
// que usa PHP en este directorio). Bórralo del servidor en cuanto lo
// hayas comprobado: no debe quedar publicado de forma permanente.
header('Content-Type: text/plain; charset=utf-8');
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "max_input_time: " . ini_get('max_input_time') . "\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "\nEsta web admite fotos (JPG/PNG/WEBP) hasta 20 MB y vídeos\n";
echo "(MP4/WEBM/MOV) hasta 80 MB, así que upload_max_filesize debería\n";
echo "poner 90M aquí para tener margen. Si NO lo pone, Apache no está\n";
echo "leyendo el .htaccess de este directorio (revisa AllowOverride en\n";
echo "el VirtualHost) o estás en PHP-FPM y hace falta recargar el servicio.\n";
