<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = '127.0.0.1'; // Altere para o host do seu banco de dados
$db   = 'restaurante_db'; // Altere para o nome do seu banco de dados
$user = 'root'; // Altere se tiver criado um usuário específico
$pass = '';     // Coloque sua senha do MariaDB, se houver

$dsn = "mysql:host=$host;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    $pdo->exec("USE `$db`");
    
    $check_tables = $pdo->query("SHOW TABLES LIKE 'clientes'");
    
    if ($check_tables->rowCount() === 0) {
        $sql_file = __DIR__ . '/database/model.sql';
        
        if (file_exists($sql_file)) {
            $sql_commands = file_get_contents($sql_file);
            $pdo->exec($sql_commands);
        } else {
            die("Erro crítico: Banco de dados vazio e arquivo model.sql não encontrado para instalação.");
        }
    }

} catch (\PDOException $e) {
    die("Erro na conexão ou configuração do banco de dados: " . $e->getMessage());
}
?>