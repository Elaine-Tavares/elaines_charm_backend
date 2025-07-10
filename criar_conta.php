<?php
//No InfinityFree, os erros não aparecem diretamente na tela, por segurança. Você precisa ativar a exibição de erros ou usar logs.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
//Arquivo responsável por receber os dados enviados do formulário React, processar esses dados (validar, verificar duplicidade, salvar no banco)

//Libera o acesso ao PHP a partir de qualquer origem (origem = domínio/site).
header("Access-Control-Allow-Origin: *");
//Esse header faz parte das regras de CORS (Cross‑Origin Resource Sharing) e serve para informar ao navegador (front) quais cabeçalhos (headers) ele está autorizado a enviar na requisição.
header("Access-Control-Allow-Headers: Content-Type");
//Define o tipo de conteúdo que o servidor está enviando de volta ao cliente.Garante que a resposta do criar_conta.php seja entendida como JSON pelo front 
header("Content-Type: application/json");
 
// Conectar ao banco de dados. Estabelecer a conexão entre o script PHP e o banco de dados MySQL. 
//"localhost": Endereço do servidor de banco de dados
//"root": Usuário do MySQL. No XAMPP, o usuário padrão costuma ser root
//"": Senha do usuário MySQL. Em instalações padrão do XAMPP, a senha de root vem em branco
//"elaine_charm": nome do banco de dados que criei (contendo a tabela usuarios)
$conn = new mysqli("localhost", "root", "", "elaine_charm");

//verificação imediata de falha ao tentar conectar no MySQL e retorna um JSON de erro
if ($conn->connect_error) {
  die(json_encode(["success" => false, "message" => "Erro na conexão com o banco."]));
}

// Receber os dados enviados pelo front em JSON. Lê o corpo da requisição HTTP enviada pelo frontend (React) e transforma em um array associativo PHP
$data = json_decode(file_get_contents("php://input"), true);

// Validar se os campos estão preenchidos. Validação básica no backend, garantindo que todos os campos obrigatórios tenham sido enviados antes de prosseguir
if (
  !isset($data['email']) || !isset($data['nome']) ||
  !isset($data['telefone']) || !isset($data['senha']) || !isset($data['repeteSenha'])
) {
  echo json_encode(["success" => false, "message" => "Todos os campos são obrigatórios."]);
  exit;
}

//Guardar os valores dos campos nas variáveis
$email        = $data['email'];
$nome         = $data['nome'];
$telefone     = $data['telefone'];
$senha        = $data['senha'];
$repeteSenha  = $data['repeteSenha'];


// Verifica se as senhas batem
if ($senha !== $repeteSenha) {
  echo json_encode([
    "success" => false,
    "message" => "As senhas não conferem."
  ]);
  exit;
}

// Criptografa a senha
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Verifica se o e-mail já está cadastrado. A query usa um placeholder ? no lugar do valor do e‑mail, prevenindo SQL Injection.
$check = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
//Liga (bind) o valor da variável $email ao placeholder ? da query. O primeiro argumento "s" informa que o tipo do parâmetro é string.
$check->bind_param("s", $email);
//Executa a consulta no banco com o parâmetro já “colocado” em segurança. O MySQL processa SELECT id FROM usuarios WHERE email = 'valor@exemplo.com'.
$check->execute();
//Recupera o resultado da consulta como um objeto mysqli_result.
$result = $check->get_result();

//Bloquear o cadastro se já existir um usuário com o e‑mail informado.
if ($result->num_rows > 0) {
  echo json_encode(["success" => false, "message" => "Email já cadastrado."]);
  exit;
} else {
  //Inserir um novo registro caso o e‑mail seja único.
  $stmt = $conn->prepare("INSERT INTO usuarios (email, nome, telefone, senha) VALUES (?, ?, ?, ?)");
  //Ligar cada placeholder à variável PHP com:
  $stmt->bind_param("ssss", $email, $nome, $telefone, $senha);
  
  //O PHP tenta gravar o registro no banco e, a seguir, retorna ao frontend um JSON indicando sucesso ou falha:
  if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Conta criada com sucesso!"]);
  } else {
    echo json_encode(["success" => false, "message" => "Erro ao salvar os dados."]);
  }
}
//Encerra a conexão com o banco de dados, liberando recursos alocados pelo PHP
$conn->close();

?>
