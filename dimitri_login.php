<?php
require './dimitri_conexao.php'; // conexão com banco

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Recebe os dados do frontend
$data = json_decode(file_get_contents("php://input"), true);

$email = limpaInputDoFront($data['email'] ?? '');
$senha = limpaInputDoFront($data['senha'] ?? '');

// Verifica se os campos estão preenchidos
if (empty($email) || empty($senha)) {
    echo json_encode([
        "success" => false,
        "message" => "Preencha todos os campos."
    ]);
    exit();
}

// Consulta o usuário no banco
$sql = $pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
$sql->execute([$email]);

// Se o e-mail não existir
if ($sql->rowCount() === 0) {
    echo json_encode([
        "success" => false,
        "message" => "E-mail ou senha inválidos."
    ]);
    exit();
}

$usuario = $sql->fetch();

// Verifica a senha com password_verify
if (!password_verify($senha, $usuario['senha'])) {
    echo json_encode([
        "success" => false,
        "message" => "E-mail ou senha inválidos."
    ]);
    exit();
}

// Se tudo estiver certo
echo json_encode([
    "success" => true,
    "message" => "Login realizado com sucesso."
    // Aqui você pode futuramente adicionar um token de autenticação, se quiser
]);
