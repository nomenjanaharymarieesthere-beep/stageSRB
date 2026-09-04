<?php

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = 'localhost';
        $dbname = 'drbf';
        $username = 'root';
        $password = '';

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password
        );

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    return $pdo;
}