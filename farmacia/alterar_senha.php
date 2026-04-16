<?php
session_start();
include('conexao.php');

// Detecta tipo de usuário pela sessão
if (isset($_SESSION['medico_id'])) {
    $tipo = "medico";
    $id = $_SESSION['medico_id'];
    $tabela = "medicos";
    $col_id = "medicos_id";
    $col_senha = "medico_senha";
} elseif (isset($_SESSION['pmaior_id'])) {
    $tipo = "pmaior";
    $id = $_SESSION['pmaior_id'];
    $tabela = "paciente_maior";
    $col_id = "pmaior_id";
    $col_senha = "pmaior_senha";
} elseif (isset($_SESSION['pmenor_id'])) {
    $tipo = "pmenor";
    $id = $_SESSION['pmenor_id'];
    $tabela = "paciente_menor";
    $col_id = "pmenor_id";
    $col_senha = "pmenor_senha";
} elseif (isset($_SESSION['farmacia_id'])) {
    $tipo = "farmacia";
    $id = $_SESSION['farmacia_id'];
    $tabela = "farmacia";
    $col_id = "farmacia_id";
    $col_senha = "farmacia_senha";
} else {
    header("Location: login.php");
    exit();
}

// Carrega dados do usuário
$stmt = $mysqli->prepare("SELECT * FROM $tabela WHERE $col_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    $erro = null;
    $mensagem = null;

    if (!empty($senha_atual) && !empty($nova_senha) && !empty($confirmar_senha)) {
        $stmt = $mysqli->prepare("SELECT $col_senha FROM $tabela WHERE $col_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($senha_hash);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($senha_atual, $senha_hash)) {
            if ($nova_senha === $confirmar_senha) {
                $nova_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

                $stmt = $mysqli->prepare("UPDATE $tabela SET $col_senha = ? WHERE $col_id = ?");
                $stmt->bind_param("si", $nova_hash, $id);

                if ($stmt->execute()) {
                    $mensagem = "Senha alterada com sucesso!";
                } else {
                    $erro = "Erro ao atualizar senha.";
                }
                $stmt->close();
            } else {
                $erro = "A nova senha e a confirmação não coincidem.";
            }
        } else {
            $erro = "Senha atual incorreta.";
        }
    } else {
        $erro = "Preencha todos os campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar Senha</title>
    <link rel="stylesheet" href="perfil.css">
</head>
<body>
    <div class="perfil-container">
        <h2>Alterar Senha (<?= ucfirst($tipo) ?>)</h2>

        <?php if (isset($mensagem)) echo "<p style='color: green;'>$mensagem</p>"; ?>
        <?php if (isset($erro)) echo "<p style='color: red;'>$erro</p>"; ?>

        <form method="POST">
            <label>Senha Atual:</label>
            <input type="password" name="senha_atual" required>

            <label>Nova Senha:</label>
            <input type="password" name="nova_senha" required>

            <label>Confirmar Nova Senha:</label>
            <input type="password" name="confirmar_senha" required>

            <button type="submit">Salvar Alterações</button>
        </form>
    </div>
</body>
</html>
