<?php
    //CONDIGURAÇÕES GERAIS
    // Define as configurações de conexão com o banco de dados. Esses valores são usados para estabelecer a conexão com o banco MySQL.
    $servidor = "localhost";
    $banco = "elaine_charm";
    $usuario = "root";
    $senha = "";
    
    //CONEXAO
    // Cria uma nova instância da classe PDO para estabelecer a conexão com o banco de dados. O objeto `$pdo` será utilizado em outras partes do código para realizar operações no banco de dados, como consultas e inserções.
    $pdo = new PDO("mysql:host={$servidor}; dbname={$banco}", $usuario, $senha);

    //SANITIZA A ENTRADA DOS DADOS
    // Função para limpar dados de entrada recebidos do frontend (como dados de formulários). Essa função é uma boa prática para evitar problemas como SQL Injection, XSS, entre outros.
    function limpaInputDoFront($dado){
        // Remove espaços extras no início e no final da string.
        $dado = trim($dado);
        // Remove barras invertidas (\) que podem ser adicionadas por formulários com escape de caracteres.
        $dado = stripslashes($dado);
        // Converte caracteres especiais em entidades HTML para evitar ataques XSS (Cross-Site Scripting).
        $dado = htmlspecialchars($dado);
        // Retorna o dado "limpo", pronto para ser utilizado no sistema.
        return $dado;
    };
    
?>