<?php
// src/database.php

function getDBConnection() {
    // Usamos las variables que ya carga Dotenv en tu bootstrap.php
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $db   = $_ENV['DB_NAME'] ?? 'tourny';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? 'root';
    $charset = 'utf8mb4_unicode_ci';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Activa reporte de errores de SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Trae los datos como arrays asociativos
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Mayor seguridad en las consultas
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}