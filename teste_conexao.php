<?php
// Teste de conexão simples para garantir que o backend se conecta corretamente

$servidor = "sql109.infinityfree.com";
$usuario = "if0_39441375";
$senha = "Front2025";
$banco = "if0_39441375_elaines_charm";

try {
    $pdo = new PDO("mysql:host=$servidor;dbname=$banco;charset=utf8", $usuario, $senha);
    echo "Conexão OK!";
} catch (PDOException $e) {
    echo "Erro de conexão: " . $e->getMessage();
}
