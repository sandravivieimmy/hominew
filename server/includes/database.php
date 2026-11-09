<?php

// Lee primero $_ENV (cargado desde includes/.env) y luego las variables reales del entorno
// (Render las inyecta como variables de entorno y tienen prioridad sobre el .env).
function env_value(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === null || $value === '') ? $default : $value;
}

$dbHost = env_value('DB_HOST', 'localhost');
$dbUser = env_value('DB_USER');
$dbPass = env_value('DB_PASS', '');
$dbName = env_value('DB_NAME');
$dbPort = (int) env_value('DB_PORT', 3306);
$dbSsl  = filter_var(env_value('DB_SSL', false), FILTER_VALIDATE_BOOLEAN);

$db = mysqli_init();
$flags = 0;

if ($dbSsl) {
    // Proveedores gestionados exigen TLS. Se verifica el certificado del servidor:
    // - DB_SSL_CA_PEM: contenido del certificado CA del proveedor (p. ej. Aiven), o
    // - si no se define, se usan las CAs del sistema (p. ej. TiDB Cloud).
    $caFile = null;
    $caPem = env_value('DB_SSL_CA_PEM');
    if ($caPem) {
        $caFile = sys_get_temp_dir() . '/homi-db-ca.pem';
        file_put_contents($caFile, str_replace('\n', "\n", $caPem));
    } elseif (is_readable('/etc/ssl/certs/ca-certificates.crt')) {
        $caFile = '/etc/ssl/certs/ca-certificates.crt';
    }
    mysqli_ssl_set($db, null, null, $caFile, null, null);
    $flags = MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_VERIFY_SERVER_CERT;
}

try {
    mysqli_real_connect($db, $dbHost, $dbUser, $dbPass, $dbName, $dbPort, null, $flags);
} catch (mysqli_sql_exception $e) {
    error_log('HOMI: no se pudo conectar a MySQL: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'No se pudo conectar a la base de datos.']);
    exit;
}

mysqli_set_charset($db, 'utf8mb4');
