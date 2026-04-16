<?php
// 1. Evitamos que PHP imprima Warnings o Errores en formato HTML que rompan el JSON
error_reporting(0);
ini_set('display_errors', 0);

// 2. Iniciamos sesión
session_start();

// 3. Forzamos la cabecera JSON
header('Content-Type: application/json; charset=utf-8');

$password_correcta = "AQUI_TU_CONTRASEÑA"; // VUELVE A CAMBIAR ESTO

// Verificación de Login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password_correcta) {
        $_SESSION['autenticado'] = true;
        echo json_encode(['status' => 'success']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Contraseña incorrecta']);
    }
    exit;
}

// Verificación de Autenticación
if (!isset($_SESSION['autenticado']) || $_SESSION['autenticado'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

// --- NUEVO: Procesar orden de borrado (Archivado) ---
if (isset($_POST['action']) && $_POST['action'] === 'reset') {
    $archivo_csv = __DIR__ . '/registro_conexiones.csv';
    
    // Si el archivo existe, lo renombramos con la fecha exacta
    if (file_exists($archivo_csv)) {
        // Formato: Año-Mes-Dia_Hora-Minuto-Segundo
        $fecha_actual = date('Y-m-d_H-i-s'); 
        $nuevo_nombre = __DIR__ . '/registro_conexiones.csv.' . $fecha_actual;
        
        rename($archivo_csv, $nuevo_nombre); // Movemos y renombramos el archivo
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Historial archivado']);
    exit;
}

// Lectura de Datos
$archivo_csv = __DIR__ . '/registro_conexiones.csv';
$datos = [];

// Comprobamos que el archivo existe y tiene algo de contenido
if (file_exists($archivo_csv) && ($gestor = fopen($archivo_csv, "r")) !== FALSE) {
    fgetcsv($gestor, 1000, ";"); // Saltar cabecera
    while (($fila = fgetcsv($gestor, 1000, ";")) !== FALSE) {
        $datos[] = [
            'fecha' => $fila[0],
            'activas' => (int)$fila[1],
            'maximas' => (int)$fila[2],
            'estado' => $fila[3]
        ];
    }
    fclose($gestor);
}

// --- NUEVO: Enviamos el tamaño del archivo en una cabecera oculta ---
if (file_exists($archivo_csv)) {
    header('X-CSV-Size: ' . filesize($archivo_csv));
} else {
    header('X-CSV-Size: 0');
}

// Imprimimos el JSON final
echo json_encode($datos);
