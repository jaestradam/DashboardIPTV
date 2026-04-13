<?php
// --- VITAL PARA HOSTING: Evitamos que el servidor corte el script por tiempo ---
set_time_limit(0);       // Tiempo de ejecución ilimitado
ignore_user_abort(true); // Que siga corriendo aunque se cierre la conexión HTTP

// --- CONFIGURACIÓN ---
$url_base = "AQUI_LA_URL_DE_TU_SERVICIO"; // Cambia por tu URL
$usuario = "AQUI_TU_USUARIO";
$password = "AQUI_TU_CONTRASEÑA";
$archivo_csv = __DIR__ . "/registro_conexiones.csv"; 

$url_api = "{$url_base}/player_api.php?username={$usuario}&password={$password}";

// --- BUCLE PARA 1 HORA (6 ejecuciones cada 10 minutos) ---
for ($vuelta = 0; $vuelta < 6; $vuelta++) {
    
    // Inicializamos cURL DENTRO del bucle para asegurar una conexión fresca cada vez
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url_api);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
    curl_setopt($ch, CURLOPT_USERAGENT, "IPTVSmartersPro"); 

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error_curl = curl_error($ch);
    curl_close($ch);

    $timestamp = date("Y-m-d H:i:s");
    $active = 0;
    $max_conn = 0;
    $status = "Unknown";

    // --- EVALUAMOS LA RESPUESTA ---
    if ($response !== false && $http_code == 200) {
        $data = json_decode($response, true);
        
        if (isset($data['user_info'])) {
            $active = $data['user_info']['active_connections'] ?? '0';
            $max_conn = $data['user_info']['max_connections'] ?? '0';
            $status = $data['user_info']['status'] ?? 'Active';
        } else {
            $status = "ERROR_JSON"; 
        }
    } else {
        if ($http_code == 0) {
            $status = "OFFLINE_TIMEOUT"; 
        } else {
            $status = "HTTP_ERROR_" . $http_code; 
        }
    }

    // --- GUARDAMOS EN EL CSV ---
    $es_nuevo = !file_exists($archivo_csv) || filesize($archivo_csv) === 0;
    $fp = fopen($archivo_csv, 'a');

    if ($fp) {
        if ($es_nuevo) {
            // Seguimos manteniendo el punto y coma (;) como en tu código original
            fputcsv($fp, ['timestamp', 'activas', 'maximas', 'estado'], ";");
        }
        
        fputcsv($fp, [$timestamp, $active, $max_conn, $status], ";");
        fclose($fp);
        
        echo "[$timestamp] Vuelta " . ($vuelta + 1) . "/6 - Registro guardado. Estado: $status.\n<br>";
    } else {
        echo "Error: No se tienen permisos para escribir el archivo CSV.\n<br>";
    }

    // --- ESPERAR 10 MINUTOS ---
    // Solo esperamos si NO es la última vuelta. Así el script termina limpiamente
    // justo antes de que el Cron de OVH vuelva a lanzarlo en la siguiente hora.
    if ($vuelta < 5) {
        sleep(600); // 600 segundos = 10 minutos
    }
}
?>