<?php
include_once 'db_connect.php';

// Leer el archivo init.sql
$sqlFile = file_get_contents("init.sql");

// Dividir las consultas por punto y coma
$sqlQueries = explode(";", $sqlFile);

// Ejecutar cada consulta
foreach ($sqlQueries as $query) {
    if (!empty(trim($query))) {
        $result = $conn->query($query);
        if (!$result) {
            echo "Error en la consulta: " . $conn->error;
        }
    }
}

echo "Tablas creadas con éxito.";