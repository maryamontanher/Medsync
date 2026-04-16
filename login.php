<?php
session_start();
$conn = new mysqli("localhost", "root", "", "medsync");

// Verifica conexão
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $senha = trim($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        echo "<script>alert('Preencha todos os campos!'); window.history.back();</script>";
        exit;
    }

    $sql = "
        SELECT * FROM (
            SELECT 'maior' AS tipo, pmaior_id AS id, pmaior_nome AS nome, pmaior_senha AS senha, NULL AS pmaior_id
            FROM paciente_maior WHERE pmaior_email = ?
            
            UNION ALL
            SELECT 'menor', pmenor_id, pmenor_nome, pmenor_senha, pmaior_id
            FROM paciente_menor WHERE pmenor_email = ?
            
            UNION ALL
            SELECT 'medico', medicos_id, medicos_nome, medico_senha, NULL AS pmaior_id
            FROM medicos WHERE medicos_email = ?
            
            UNION ALL
            SELECT 'farmacia', farmacia_id, farmacia_nome_fant, farmacia_senha, NULL AS pmaior_id
            FROM farmacia WHERE farmacia_email = ?
            
            UNION ALL
            SELECT 'clinica', clinica_id, clinica_nome_fant, clinica_senha, NULL AS pmaior_id
            FROM clinica WHERE clinica_email = ?
            
            UNION ALL
            SELECT 'admin', admin_id, admin_nome, admin_senha, NULL AS pmaior_id
            FROM administradores WHERE admin_email = ?
        ) AS usuarios
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("Erro no prepare: " . $conn->error);
    }

    // Agora são 6 parâmetros (um para cada tabela)
    $stmt->bind_param("ssssss", $email, $email, $email, $email, $email, $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verifica se encontrou algum usuário
    if ($result && $row = $result->fetch_assoc()) {
        // Confere senha com hash
        if (password_verify($senha, $row['senha'])) {
            $_SESSION['tipo'] = $row['tipo'];
            $_SESSION['nome'] = $row['nome'];

            // Redireciona conforme o tipo de usuário
            switch ($row['tipo']) {
                case 'maior':
                    $_SESSION['pmaior_id'] = $row['id'];
                    header("Location: paciente/painelpaciente.php");
                    break;
                case 'menor':
                    $_SESSION['pmenor_id'] = $row['id'];
                    $_SESSION['pmaior_id'] = $row['pmaior_id']; // CORREÇÃO: Adicionado esta linha
                    header("Location: paciente menor/painelpaciente-menor.php");
                    break;
                case 'medico':
                    $_SESSION['medico_id'] = $row['id'];
                    header("Location: medico/painelmed.php");
                    break;
                case 'farmacia':
                    $_SESSION['farmacia_id'] = $row['id'];
                    header("Location: farmacia/painel-farm.php");
                    break;
                case 'clinica':
                    $_SESSION['clinica_id'] = $row['id'];
                    header("Location: adm-clinica/adm-clinica.php");
                    break;
                case 'admin':
                    $_SESSION['admin_id'] = $row['id'];
                    header("Location: adm/adm.php");
                    break;
            }
            exit;
        }
    }

    echo "<script>alert('E-mail ou senha inválidos.'); window.history.back();</script>";
}
?>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="./css/form.css">
    <link rel="shortcut icon" href="images/logo/minilogo_verde.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
   
        .register-link {
            text-align: center;
            margin-top: 10px;
            color:rgb(138, 142, 142);
        }
        .register-link a {
            color: #4bb9a5;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
    <title>Medsync</title>
</head>
<body>
    <div class="container">
        <h1>Entrar</h1>
        <br><br>
        <form action="" method="post">
            <label for="email"><font color="#0a4e53">E-mail</font></label>
            <input type="text" id="email" name="email" placeholder="E-mail" required>

            <label for="senha"><font color="#0a4e53">Senha</font></label>
            <input type="password" id="senha" name="senha" placeholder="Senha" required>

            <input type="submit" value="Entrar">

            <div class="register-link">
                Esqueceu a senha? <a href="esqueceu_senha.php">Recuperar</a>
            </div>
        </form>
    </div>
</body>
</html>