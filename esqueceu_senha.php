<?php
// esse esqueceu a senha é para quando o usuario ja foi cadastrado mas não lembra da senha,
// ele ira digitar o email para receber um link para o reset


require_once('src/PHPMailer.php');
require_once('src/SMTP.php');
require_once('src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = new mysqli($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        echo "<script>alert('Digite seu e-mail!'); window.history.back();</script>";
        exit;
    }

    // Lista de tabelas com email + senha
    $usuarios = [
        "paciente_maior"  => "pmaior_email",
        "paciente_menor"  => "pmenor_email",
        "medicos"         => "medicos_email",
        "farmacia"        => "farmacia_email",
        "clinica"         => "clinica_email",
        "administradores" => "admin_email"
    ];

    $tabela_encontrada = null;
    $coluna_email = null;

    // Procura o e-mail em todas as tabelas
    foreach ($usuarios as $tabela => $coluna) {
        $sql = "SELECT * FROM $tabela WHERE $coluna = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $tabela_encontrada = $tabela;
            $coluna_email = $coluna;
            $usuario = $result->fetch_assoc();
            break;
        }
    }

    if ($tabela_encontrada) {
        $token = bin2hex(random_bytes(32));

        // Atualiza o token na tabela certa
        $sql = "UPDATE $tabela_encontrada SET token_recuperacao = ? WHERE $coluna_email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $token, $email);
        $stmt->execute();

        // Link para redefinir
        $link = "http://localhost/medsync/reset_senha.php?token=$token";

        // Configura o PHPMailer
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'medsyncc@gmail.com';
            $mail->Password = 'rcdu jzij lotr akgk'; // sua senha de app do Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];

            $mail->setFrom('medsyncc@gmail.com', 'Medsync');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Recuperação de senha - Medsync";
            $mail->Body = "<p>Olá,</p>
                           <p>Recebemos sua solicitação de redefinição de senha.</p>
                           <p><a href='$link'>Clique aqui para redefinir sua senha</a></p>
                           <p>Se você não fez essa solicitação, ignore este e-mail.</p>";
            $mail->AltBody = "Olá,\n\nAcesse o link para redefinir sua senha: $link\n\nSe não foi você, ignore este e-mail.";

            $mail->send();

            echo "<script>alert('Enviamos um link de recuperação para seu e-mail!'); window.location='login.php';</script>";
            exit;
        } catch (Exception $e) {
            echo "<script>alert('Erro ao enviar e-mail: {$mail->ErrorInfo}'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('E-mail não encontrado!'); window.history.back();</script>";
    }
}
?>

<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Esqueceu a Senha</title>
    <link rel="stylesheet" href="./css/form.css">
</head>
<body>
<div class="container">
    <h1>Recuperar Senha</h1>
    <form method="post">
            <label for="email"><font color="#0a4e53">E-mail</font></label>
            <input type="text" id="email" name="email" placeholder="Digite o seu E-mail" required>
        <input type="submit" value="Enviar link">
    </form>
</div>
</body>
</html>
