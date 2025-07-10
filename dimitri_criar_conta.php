<?php
//IMPORTA O ARQUIVO ONDE ESTÁ DEFINIDA A CONEXÃO COM O BANCO DE DADOS, FORNECENDO A VARIÁVEL $pdo (utilizado nas consultas SQL) PARA INTERAGIR COM O BANCO. ISSO É IMPORTANTE PARA GARANTIR QUE A CONEXÃO COM O BANCO ESTEJA ESTABELECIDA CORRETAMENTE ANTES DE PROSSEGUIR COM QUALQUER VERIFICAÇÃO.
require './dimitri/dimitri_conexao.php';  

//CARREGAM AS CLASSES DO PHPMailer USADO PARA ENVIAR E-MAIL DE CONFIRMAÇÃO
// A linha abaixo importa a classe PHPMailer, que é responsável por enviar e-mails
require './PHPMailer/src/PHPMailer.php';
// Incluindo a classe SMTP, que permite usar o protocolo SMTP (Simple Mail Transfer Protocol) para enviar os e-mails. O PHPMailer usa essa classe para enviar e-mails através de servidores de e-mail, como o Gmail, por exemplo.
require './PHPMailer/src/SMTP.php';
// Importa a classe Exception, que é usada para tratar erros e exceções que podem ocorrer ao enviar e-mails. Ela ajuda a capturar e mostrar mensagens de erro, caso algo dê errado no processo de envio.
require './PHPMailer/src/Exception.php';

//IMPORTA AS CLASSES PHPMailer E Exception DO NAMESPACE PHPMailer\PHPMailer, PERMITINDO QUE SEJA USADAS DIRETAMENTE COM OS NOMES PHPMailer e Exception.
// Importa a classe PHPMailer, que é a principal classe usada para enviar e-mails. Ela contém métodos e propriedades que facilitam a configuração e envio de e-mails de forma segura e profissional.
use PHPMailer\PHPMailer\PHPMailer;
// Aqui estamos importando a classe Exception, que será usada para capturar e tratar erros que podem ocorrer durante o processo de envio do e-mail, como falhas na autenticação, problemas de conexão, entre outros. A classe Exception ajuda a exibir mensagens de erro amigáveis para o desenvolvedor.
use PHPMailer\PHPMailer\Exception;

//CONFIGURAÇÕES DE CABEÇALHO PARA A RESPOSTA DA API
// A linha abaixo permite que o seu servidor aceite requisições de qualquer origem (domínio) para o seu backend. Permite que qualquer origem (domínio) acesse este endpoint da API (necessário para requisições de outros domínios).
header("Access-Control-Allow-Origin: *");
// Permite que a requisição inclua o cabeçalho 'Content-Type' (necessário para enviar JSON, por exemplo). Define que o servidor aceita o cabeçalho "Content-Type", o que é comum quando estamos recebendo dados em formato JSON de uma requisição (geralmente um POST). Esse cabeçalho permite que a requisição forneça informações sobre o tipo de conteúdo que está sendo enviado (no caso, "application/json").
header("Access-Control-Allow-Headers: Content-Type");
// Define que a resposta do servidor será enviada no formato JSON, e não, por exemplo, HTML ou XML.
header("Content-Type: application/json");

// A função `file_get_contents("php://input")` é usada para ler os dados enviados no corpo da requisição HTTP. Nesse caso, ela está capturando os dados que foram enviados via POST (geralmente em formato JSON) para o servidor. Lê o corpo da requisição (JSON enviado pelo frontend) e converte em array associativo PHP.
$data = json_decode(file_get_contents("php://input"), true); //true = array, sem true = objeto

// VALIDAÇÃO DE CAMPOS VAZIOS
// Verifica se algum dos campos obrigatórios ('email', 'nome', 'telefone', 'senha' e 'repeteSenha') está vazio. A função `empty()` verifica se a variável está vazia, ou seja, se o valor é nulo, uma string vazia ou 0.
if (
    empty($data['email']) || empty($data['nome']) || empty($data['telefone']) ||
    empty($data['senha']) || empty($data['repeteSenha']) 
) {     
    // Se algum campo estiver vazio, a função `json_encode()` é usada para enviar uma resposta JSON de erro. Retorna um objeto com as chaves "success" e "message", informando que todos os campos são obrigatórios
    echo json_encode([
      "success" => false,
      "message" => "Todos os campos são obrigatórios."
    ]);
    // O comando `exit` encerra a execução do script nesse ponto, evitando continuar com o processamento caso os dados estejam incompletos.
    exit();
}

// Tenta obter do array $data (que veio do frontend) o valor do campo 'aceitou_termos'. Caso esse campo não exista (ou seja, o usuário não marcou a checkbox), o valor padrão será false. O operador `??` verifica se o índice existe e não é nulo. Se não existir, retorna o valor à direita (false).
$aceitouTermos = $data['aceitouTermos'] ?? false;
// $aceitouTermos = filter_var($data['aceitou_termos'] ?? false, FILTER_VALIDATE_BOOLEAN);
// Verifica se o usuário NÃO aceitou os termos (ou seja, se $aceitouTermos for false).
if ($aceitouTermos !== true) {
     // Retorna uma resposta JSON indicando erro, com uma mensagem explicando que o aceite é obrigatório.
    echo json_encode([
        "success" => false,
        "message" => "Você precisa aceitar os Termos de Uso."
    ]);
     // O comando `exit` encerra a execução do script nesse ponto, evitando continuar com o processamento caso os dados estejam incompletos.
    exit();
}

// Converte booleano para inteiro antes de salvar no banco
$aceitouTermos = $aceitouTermos ? 1 : 0;

// Se todos os campos obrigatórios forem preenchidos, a data atual de cadastro é armazenada na variável `$data_cadastro`.A função `date('d-m-Y')` retorna a data no formato "dia-mês-ano".
$data_cadastro = date('d-m-Y');

// limpando os dados do frontend para evitar dados maliciosos ou inválidos. A função `limpaInputDoFront()` sanitiza ou valida os valores recebidos antes de usá-los no banco de dados, (como remover espaços extras ou caracteres especiais, prevenindo vulnerabilidades de segurança como XSS).
$email = limpaInputDoFront($data['email']);
$nome = limpaInputDoFront($data['nome']);
$telefone = limpaInputDoFront($data['telefone']);
$senha = limpaInputDoFront($data['senha']);
$repeteSenha = limpaInputDoFront($data['repeteSenha']);
$aceitouTermos = $data['aceitouTermos'];


// VALIDAÇÃO DE NOME
// A função `preg_match()` é usada para verificar se a string `$nome` corresponde ao padrão de expressão regular fornecido.
if (!preg_match("/^[a-zA-Z-' ]*$/", $nome)) {
    // Se o nome não for válido, a função `json_encode()` é usada para enviar uma resposta em formato JSON, indicando que o formato do nome está inválido.
    echo json_encode([
        "success" => false, 
        "message" => "Nome inválido."
    ]);
    // O comando `exit()` interrompe a execução do script para evitar continuar o processamento com dados inválidos.
    exit();
}

// VALIDAÇÃO DE E-MAIL
// A função `filter_var()` é usada para validar dados com base em um filtro específico. Neste caso, estou usando o filtro `FILTER_VALIDATE_EMAIL` para verificar se o formato do e-mail é válido.
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Se o e-mail não for válido, a função `json_encode()` é usada para enviar uma resposta em formato JSON, indicando que o formato do e-mail está inválido.
    echo json_encode([
        "success" => false, 
        "message" => "Formato de e-mail inválido."
    ]);
    // O comando `exit()` interrompe a execução do script para evitar continuar o processamento com dados inválidos.
    exit();
}

// VALIDAÇÃO DE TELEFONE
// A função `preg_match()` é usada para verificar se a string `$telefone` corresponde ao padrão de expressão regular fornecido.
if (!preg_match("/^\(?\d{2}\)?[\s-]?\d{4,5}[-]?\d{4}$/", $telefone)) {
    // Se o telefone não for válido, a função `json_encode()` é usada para enviar uma resposta em formato JSON, indicando que o formato do telefone está inválido.
    echo json_encode([
        "success" => false, 
        "message" => "Formato de telefone inválido."
    ]);
    // O comando `exit()` interrompe a execução do script para evitar continuar o processamento com dados inválidos.
    exit();
}

// VALIDAÇÃO DE SENHAS 
// A condição `if` verifica se as duas senhas fornecidas, `$senha` e `$repeteSenha`, são diferentes. A comparação é feita utilizando o operador de identidade `!==`, que garante que as duas variáveis sejam tanto de tipo quanto de valor diferentes.
if ($senha !== $repeteSenha) {
     // Se as senhas forem diferentes, a função `json_encode()` é usada para enviar uma resposta JSON de erro.
    echo json_encode([
        "success" => false, 
        "message" => "As senhas não conferem."
    ]);
    // O comando `exit()` interrompe a execução do script para evitar continuar o processamento com dados inválidos.
    exit();
}

// CRIPTOGRAFANDO A SENHA
// A função `password_hash()` é usada para gerar um hash seguro da senha fornecida pelo usuário. O primeiro parâmetro é a senha em texto claro (no caso, a variável `$senha`), que será criptografada. O segundo parâmetro é o algoritmo de hash que será utilizado. O valor `PASSWORD_DEFAULT` indica que o PHP vai usar o algoritmo de hash padrão recomendado, que atualmente é o bcrypt, mas pode ser alterado nas versões futuras para um algoritmo mais forte, se necessário. O uso de `PASSWORD_DEFAULT` garante que o hash gerado seja seguro e compatível
// com as versões futuras do PHP.
$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

// Gera um código aleatório seguro com 32 caracteres hexadecimal para gerar tokens de verificação, chaves de API ou IDs únicos. Nesse caso o código será usado para a confirmação de e-mail.
$codigo = bin2hex(random_bytes(16));

try {
    // Verificando se o email já existe
    $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $check->execute([$email]);

    if ($check->rowCount() > 0) {
        echo json_encode(["success" => false, "message" => "Email já cadastrado."]);
        exit();
    }

    // Inserindo os dados na tabela usuarios_pendentes
    $insert = $pdo->prepare("INSERT INTO usuarios_pendentes(email, nome, telefone, senha, codigo_verificacao, data_cadastro, aceitou_termos) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert->execute([
        $email, 
        $nome, 
        $telefone, 
        $senhaHash, 
        $codigo, 
        $data_cadastro,
        $aceitouTermos
    ]);

    // Monta o link de verificação que será enviado no e-mail, contendo o código único do usuário.
    $link = "http://localhost/elaine_charm_backend/dimitri_verificar_email.php?codigo=$codigo";


    // Cria uma nova instância do PHPMailer e ativa o modo de exceções (para capturar erros com try/catch).
    $mail = new PHPMailer(true);
    // Inicia o bloco try, onde tento enviar o e-mail e capturo possíveis erros.
    try {
        // ----------- CONFIGURAÇÕES DO SERVIDOR SMTP DO GMAIL -----------
        // Define que o PHPMailer deve usar SMTP para enviar o e-mail.
        $mail->isSMTP();
        // Define o servidor SMTP do Gmail.
        $mail->Host = 'smtp.gmail.com';
        // Ativa a autenticação SMTP, que é obrigatória para o envio de e-mails através do servidor SMTP do Gmail.
        $mail->SMTPAuth = true;
        // Define o e-mail remetente (precisa ser o mesmo da senha de aplicativo).
        $mail->Username = 'elainetavares.developer@gmail.com'; 
        // Define a senha de aplicativo gerada nas configurações da conta Google (usada para autenticação no servidor SMTP do Gmail).
        $mail->Password = 'tqfn thhz mvjj kdlw';  //'jfyw bnfi zdtb akcy ';  
        // Define o tipo de criptografia a ser usado. No caso do Gmail, usamos 'tls', que é obrigatória para a porta 587.
        $mail->SMTPSecure = 'tls';
        // Define a porta que o servidor SMTP do Gmail usa para envio de e-mails com criptografia TLS (porta 587).
        $mail->Port = 587;
        // Define a codificação de caracteres do e-mail (remetente, assunto) para garantir que caracteres especiais sejam interpretados corretamente.
        $mail->CharSet = 'UTF-8';


        //----------- CONFIGURAÇÃO DE REMETENTE E DESTINATÁRIO -----------
        // Define o remetente do e-mail, ou seja, quem está enviando o e-mail. O primeiro parâmetro é o endereço de e-mail do remetente, e o segundo é o nome que aparecerá como remetente.
        $mail->setFrom('elainetavares.developer@gmail.com', 'Elaine’s Charm');
        // Adiciona o destinatário, ou seja, a pessoa que receberá o e-mail. O primeiro parâmetro é o e-mail do destinatário (que foi recebido do formulário), e o segundo parâmetro é o nome do destinatário, também obtido do formulário. Com isso, o e-mail será enviado para o endereço fornecido no formulário, e o nome aparecerá na saudação do e-mail.
        $mail->addAddress($email, $nome);


        // ----------- CONTEÚDO DO E-MAIL -----------
        // Define que o corpo do e-mail será em formato HTML. Isso permite que eu use tags HTML (como <br>, <a>, etc.)para formatar o conteúdo do e-mail, tornando-o mais interativo e visualmente agradável.
        $mail->isHTML(true);
         // Define o assunto do e-mail (o título que aparece na caixa de entrada do destinatário). 
        $mail->Subject = "Confirmação de Cadastro - Elaine’s Charm";
        // Define o corpo do e-mail, que é a mensagem que será enviada ao destinatário. A mensagem usa HTML, com quebras de linha <br> para organizar o texto e um link <a> para a confirmação do e-mail. A variável `$nome` é utilizada para personalizar a saudação, tornando a mensagem mais pessoal para o destinatário. A variável `$link` contém o link de ativação do e-mail, gerado anteriormente no código.
        $mail->Body = "Olá, $nome!<br><br>Clique no link abaixo para confirmar seu e-mail e ativar sua conta:<br><a href='$link'>Confirmar e-mail</a><br><br>Se você não fez esse cadastro, ignore esta mensagem.";


        // ----------- ENVIO E RESPOSTA -----------
        // Envia o e-mail de fato.A função `$mail->send()` executa o envio do e-mail com todas as configurações definidas anteriormente(remetente, destinatário, assunto e corpo). 
        $mail->send();
        // Se o envio for bem-sucedido, retorna resposta JSON para o Front informando sucesso no envio.
        echo json_encode([
            "success" => true,
            "message" => "Um e-mail foi enviado para $email. Confirme para ativar sua conta.",
            // "codigo" => $codigo // ← Aqui!
        ]);  

    // Se ocorrer algum erro durante o envio do e-mail (por exemplo, problema de conexão, autenticação ou servidor indisponível), o PHPMailer lança uma exceção, que é capturada aqui.
    } catch (Exception $e) {
        // Em seguida, uma resposta JSON é enviada ao frontend indicando falha no envio do e-mail, e a mensagem de erro do PHPMailer (`$mail->ErrorInfo`) é incluída para fins de diagnóstico.
        echo json_encode([
            "success" => false,
            "message" => "Erro ao enviar o e-mail: {$mail->ErrorInfo}"
        ]);
    }
//Captura de exceções do tipo PDOException, que ocorrem quando algo dá errado ao interagir com o banco de dados. Pode englobar erros como: falha na conexão, erro de sintaxe SQL, violação de chave única, entre outros.
} catch (PDOException $e) {
    // Prepara uma resposta em formato JSON para ser enviada ao frontend. O uso de json_encode() transforma o array PHP em uma string JSON.
    echo json_encode([
        "success" => false, // Indica que a operação falhou.
        "message" => "Erro ao salvar os dados: " . $e->getMessage() // Exibe a mensagem de erro gerada pelo PDO.
    ]);
}
?>
