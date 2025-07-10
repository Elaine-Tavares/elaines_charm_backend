<?php
//IMPORTA O ARQUIVO ONDE ESTÁ DEFINIDA A CONEXÃO COM O BANCO DE DADOS, FORNECENDO A VARIÁVEL $pdo (utilizado nas consultas SQL) PARA INTERAGIR COM O BANCO. ISSO É IMPORTANTE PARA GARANTIR QUE A CONEXÃO COM O BANCO ESTEJA ESTABELECIDA CORRETAMENTE ANTES DE PROSSEGUIR COM QUALQUER VERIFICAÇÃO.
require './dimitri/dimitri_conexao.php';  

// Captura o valor do parâmetro 'codigo' enviado pela URL (método GET). Quando o usuário clicar no link enviado por e-mail, o código de verificação será passado na URL. O operador de coalescência nula `??` verifica se o parâmetro 'codigo' foi fornecido. Se sim, o valor será atribuído à variável `$codigo`. Se não (ou seja, se 'codigo' não existir na URL), será atribuído um valor padrão vazio (''). Isso evita erros de "índice indefinido" e garante que a variável sempre tenha um valor definido.
$codigo = $_GET['codigo'] ?? '';

// Verifica se a variável $codigo está vazia. Isso pode acontecer se o parâmetro 'codigo' não foi enviado na URL ou se veio como uma string vazia, o que indica que o link de verificação está incorreto ou incompleto.
if (empty($codigo)) {
    // Exibe uma mensagem simples em HTML informando que o código de verificação é inválido.
    echo "<h1>Código de verificação inválido.</h1>";
     // Mensagem complementar orientando o usuário a verificar o link enviado por e-mail.
    echo "<p>Por favor, verifique o link enviado no e-mail e tente novamente.</p>";
    // O comando `exit` encerra a execução do script nesse ponto, evitando continuar com o processamento caso os dados estejam inválidos ou inexistentes.
    exit(); 
}


// Prepara a consulta SQL para verificar se o código de verificação informado existe na tabela usuarios_pendentes. O sinal de interrogação (?) é um marcador de posição (placeholder) utilizado para proteger contra SQL Injection. O valor real será passado mais adiante usando o método `execute()`.O método `prepare()` retorna um objeto de declaração (statement) que será executado em seguida.
$sql = $pdo->prepare("SELECT * FROM usuarios_pendentes WHERE codigo_verificacao = ?");
// Executa a consulta, passando o código de verificação como parâmetro para substituir o marcador '?'.

// Executa a consulta SQL preparada, passando o valor do código de verificação para substituir o marcador de posição (?). O valor de `$codigo` é colocado no array que será passado para o método `execute()`. Isso garante que a consulta seja segura, substituindo o marcador de posição por um valor que vem diretamente da variável, sem risco de SQL Injection.
$sql->execute([$codigo]);

// Verifica o número de linhas retornadas pela consulta SQL. O método `rowCount()` retorna a quantidade de registros que correspondem à consulta realizada. Se o número de linhas for 0, significa que não encontramos nenhum usuário com o código de verificação fornecido, ou seja, o código é inválido ou não existe na tabela 'usuarios_pendentes'.
if ($sql->rowCount() === 0) {
    echo "<h1>Código de verificação não encontrado.</h1>";
    echo "<p>Por favor, verifique o link enviado no e-mail e tente novamente.</p>";
     // O comando `exit` encerra a execução do script nesse ponto, evitando continuar com o processamento caso o dado seja inválido ou não exista.
    exit();  
}

// Caso o código exista, pega os dados do usuário. O método `fetch()` recupera a primeira linha dos resultados da consulta SQL. Ele retorna os dados da linha como um array associativo na variável $usuario, onde as chaves são os nomes das colunas da tabela. Nesse caso, ele armazena as informações do usuário correspondente ao código de verificação encontrado, o que significa que agora tenho acesso aos dados do usuário, como nome, e-mail, e outros dados cadastrados.
$usuario = $sql->fetch();


// Prepara a instrução SQL para inserir os dados do usuário na tabela 'usuarios'. O comando SQL de inserção usa placeholders (?) para valores que serão fornecidos posteriormente a fim de prevenir SQL Injection e garantir a segurança da consulta.
$insert = $pdo->prepare("INSERT INTO usuarios (data_cadastro, email, nome, telefone, senha, aceitou_termos) VALUES (?, ?, ?, ?, ?, ?)");
// Executa a consulta SQL de inserção, passando os valores que serão inseridos na tabela. A função `execute()` substitui os placeholders (?) pelos valores no array fornecido. Esses valores são:
$insert->execute([
    date('d-m-Y'),   // Data atual no formato 'dia-mês-ano'. Usado para preencher o campo 'data_cadastro' com a data de cadastro do usuário.
    $usuario['email'], // O e-mail do usuário, retirado do array `$usuario` obtido da consulta anterior.
    $usuario['nome'], // O nome do usuário, retirado do array `$usuario` obtido da consulta anterior. 
    $usuario['telefone'], // O telefone do usuário, retirado do array `$usuario` obtido da consulta anterior.
    $usuario['senha'], // A senha do usuário, que será inserida diretamente na tabela 'usuarios' (já criptografada no arquivo dimitri_criar_conta.php).
    $usuario['aceitou_termos'] // A senha do usuário, que será inserida diretamente na tabela 'usuarios' (já criptografada no arquivo dimitri_criar_conta.php).
]);

// REMOVE O USUÁRIO DA TABELA 'usuarios_pendentes'
// Prepara a instrução SQL para excluir o registro do usuário da tabela 'usuarios_pendentes'. O comando SQL de exclusão usa um placeholder (?) para o valor que será fornecido na execução. O valor fornecido será o `id` do usuário, que foi recuperado anteriormente na consulta.
$delete = $pdo->prepare("DELETE FROM usuarios_pendentes WHERE id = ?");
$delete->execute([$usuario['id']]);
// Envia uma resposta em formato JSON para o frontend informando que a conta foi criada com sucesso.
 echo json_encode([
            "success" => true,
            "message" => 'Conta criada com sucesso'
        ]);


// Exibe uma mensagem diretamente na tela, informando ao usuário que a conta foi ativada com sucesso. Essa mensagem é útil para feedback imediato ao usuário, antes de redirecioná-lo para outra página.
echo "Conta ativada com sucesso! Você já pode fazer login.";
// Redireciona o usuário para a página de login, passando um parâmetro de sucesso na URL.
// O parâmetro 'sucesso=1' indica que a ativação foi bem-sucedida.


// O comando `header()` envia um cabeçalho HTTP para o navegador, redirecionando o usuário para uma nova URL. Nesse caso, o usuário será redirecionado para a página 'criarConta' do frontend, passando o parâmetro `sucesso=1` na URL. Isso pode ser usado para mostrar uma mensagem de sucesso na página de criação de conta ou fazer outras ações no frontend
// header("Location: http://localhost:5173/entrar?sucesso=1");
header("Location: http://localhost:5173/criarConta?sucesso=1");
// O comando `exit()` interrompe a execução do script após o redirecionamento para garantir que nada mais seja executado.
exit();

?>
