<?php
// Arquivo central de conexão com o MySQL via PDO.
// Reutilize este arquivo em todas as páginas com: require_once __DIR__ . '/config/db.php';

$host = 'localhost';
$port = 3306;
$dbname = 'tcc';
$user = 'root';      // padrão XAMPP
$pass = '';          // padrão XAMPP sem senha

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // lança exceções em erros
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // retorna arrays associativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // prepared statements nativos
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Mensagem amigável para desenvolvimento local
    die('Erro ao conectar com o banco de dados: ' . $e->getMessage());
}
