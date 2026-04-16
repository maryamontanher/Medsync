<?php

// esse reset é a continuação do esqueceu, para o usuario redefinir a nova senha
session_start();
$conn = new mysqli("localhost", "root", "", "medsync");

if (!isset($_GET['token'])) {
    die("Token inválido.");
}

$token = $_GET['token'];

// Tabelas e colunas de senha
$tabelas = [
    "paciente_maior" => ["email" => "pmaior_email", "senha" => "pmaior_senha"],
    "paciente_menor" => ["email" => "pmenor_email", "senha" => "pmenor_senha"],
    "medicos"        => ["email" => "medicos_email", "senha" => "medico_senha"],
    "farmacia"       => ["email" => "farmacia_email", "senha" => "farmacia_senha"],
    "clinica"        => ["email" => "clinica_email", "senha" => "clinica_senha"],
    "administradores"=> ["email" => "admin_email",   "senha" => "admin_senha"]
];

$usuario = null;
$tabela_encontrada = null;
$coluna_senha = null;

// Procura o token em todas as tabelas
foreach ($tabelas as $tabela => $campos) {
    $sql = "SELECT * FROM $tabela WHERE token_recuperacao = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        $tabela_encontrada = $tabela;
        $coluna_senha = $campos['senha'];
        break;
    }
}

if (!$usuario) {
    die("Link inválido ou expirado.");
}

// Quando enviar nova senha
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nova_senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    $sql = "UPDATE $tabela_encontrada SET $coluna_senha = ?, token_recuperacao = NULL WHERE token_recuperacao = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nova_senha, $token);
    $stmt->execute();

    echo "<script>alert('Senha alterada com sucesso!'); window.location='login.php';</script>";
    exit;
}
?>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
    <link rel="stylesheet" href="./css/form.css">
</head>
<body>
<div class="container">
    <h1>Redefinir Senha</h1>
    <form method="post">
    <label for="senha"><font color="#0a4e53">Senha</font></label>
            <input type="password" id="senha" name="senha" placeholder="Senha" required>
        <input type="submit" value="Salvar nova senha">
    </form>
</div>
</body>
</html>
