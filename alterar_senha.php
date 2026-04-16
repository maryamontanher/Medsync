<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = new mysqli($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// O token é obtido da URL (GET) e é armazenado nesta variável
$token = $_GET['token'] ?? '';
$mensagem = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Na submissão (POST), o token é lido do campo oculto do formulário
    $novaSenha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';
    $token = $_POST['token'] ?? '';

    if ($novaSenha !== $confirmarSenha) {
        $mensagem = "As senhas não coincidem.";
    } elseif (strlen($novaSenha) < 6) {
        $mensagem = "A senha deve ter pelo menos 6 caracteres.";
    } else {
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        
        // CORREÇÃO: Adicionando 'clinica' e 'administradores' para cobrir todos os usuários.
        $tabelas = [
            ['tabela' => 'clinica', 'campo' => 'clinica_senha', 'coluna_token' => 'token_recuperacao'], 
            ['tabela' => 'paciente_maior', 'campo' => 'pmaior_senha', 'coluna_token' => 'token_recuperacao'],
            ['tabela' => 'paciente_menor', 'campo' => 'pmenor_senha', 'coluna_token' => 'token_recuperacao'],
            ['tabela' => 'medicos', 'campo' => 'medico_senha', 'coluna_token' => 'token_recuperacao'],
            ['tabela' => 'farmacia', 'campo' => 'farmacia_senha', 'coluna_token' => 'token_recuperacao'],
            ['tabela' => 'administradores', 'campo' => 'admin_senha', 'coluna_token' => 'token_recuperacao']
        ];

        $senhaAtualizada = false;

        foreach ($tabelas as $dados) {
            // A query atualiza a senha e define o token como NULL, tornando-o inválido para uso futuro.
            $sql = "UPDATE {$dados['tabela']} SET {$dados['campo']} = ?, {$dados['coluna_token']} = NULL WHERE {$dados['coluna_token']} = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $senhaHash, $token);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $senhaAtualizada = true;
                break;
            }
        }

        if ($senhaAtualizada) {
            echo "<script>alert('Senha alterada com sucesso!'); window.location.href='login.php';</script>";
            exit;
        } else {
            // Esta mensagem só é exibida se o token não for encontrado em NENHUMA das tabelas
            $mensagem = "Token inválido ou expirado.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Medsync - Alterar Senha</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="./css/form.css">
    <link rel="shortcut icon" href="images/minilogo_verde.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
    </style>
</head>
<body>
    <div class="container">
        <h1>Altere sua Senha</h1><br><br>
        <form name="f2" method="post">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <font color="#0a4e53">Nova senha: </font>
            <input type="password" id="senha" name="senha" placeholder="Senha">

            <font color="#0a4e53">Confirme sua Senha:</font>
            <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Senha">

            <input type="submit" value="Alterar">
        </form>
    </div>

    <?php if (!empty($mensagem)): ?>
        <script>
            window.addEventListener("DOMContentLoaded", function () {
                alert("<?= $mensagem ?>");
                document.getElementById("senha").value = "";
                document.getElementById("confirmar_senha").value = "";
                document.getElementById("senha").focus();
            });
        </script>
    <?php endif; ?>
</body>
</html>