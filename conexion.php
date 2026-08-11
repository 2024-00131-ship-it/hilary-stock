<?php
$host = 'localhost';
$db   = 'inventario_db';
$user = 'root';
$pass = ''; // Por defecto en XAMPP viene vacío

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>