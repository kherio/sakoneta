<?php
// Esta carpeta no tiene nada que mostrar directamente: si alguien
// entra a su URL sin pedir un archivo concreto, se le manda a la
// portada del sitio en vez de dejar que el servidor intente listar
// su contenido (por si "Options -Indexes" no llegara a aplicarse en
// este servidor, por ejemplo por la configuración de AllowOverride).
header('Location: ../../index.php');
exit;
