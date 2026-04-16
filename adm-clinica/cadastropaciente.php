<?php
require_once('../src/PHPMailer.php');
require_once('../src/SMTP.php');
require_once('../src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

$formFoiEnviado = false;
$mensagem = "";
$campoErro = "";

function verificaDuplicidadeUsuarioDetalhado($conn, $cpf, $email, $telefone, $crm = null) {
    $verificacoes = [
        ["SELECT 1 FROM medicos WHERE medicos_crm = ?", $crm, "CRM"],
        ["SELECT 1 FROM medicos WHERE medicos_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM medicos WHERE medicos_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM medicos WHERE medicos_telefone = ?", $telefone, "telefone"],

        ["SELECT 1 FROM paciente_maior WHERE pmaior_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_telefone = ?", $telefone, "telefone"],

        ["SELECT 1 FROM paciente_menor WHERE pmenor_cpf  = ?", $cpf, "CPF do responsável"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_email = ?", $email, "e-mail do responsável"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_telefone = ?", $telefone, "telefone do responsável"],

        ["SELECT 1 FROM farmacia WHERE farmacia_cnpj = ?", $cpf, "CNPJ"],
        ["SELECT 1 FROM farmacia WHERE farmacia_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM farmacia WHERE farmacia_telefone = ?", $telefone, "telefone"],

        ["SELECT 1 FROM administradores WHERE admin_email = ?", $email, "e-mail de administrador"],
        ["SELECT 1 FROM administradores WHERE admin_telefone = ?", $telefone, "telefone de administrador"]
    ];

    foreach ($verificacoes as $verificacao) {
        list($sql, $valor, $campo) = $verificacao;

        if (empty($valor)) continue;

        $stmt = $conn->prepare($sql);
        if (!$stmt) continue;

        $stmt->bind_param("s", $valor);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            return "Já existe um usuário com o $campo informado.";
        }
    }

    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $formFoiEnviado = true;  // marca que o formulário foi enviado

    $nome = $_POST['nome'];
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    $endereco = $_POST['endereco'];
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $email = $_POST['email'];
    $sexo = isset($_POST['sexo']) ? $_POST['sexo'] : '';
    $estado_civil = $_POST['estado_civil'];
    $data_nascimento = $_POST['data_nascimento'];

    if (empty($nome)) {
        $mensagem = 'O campo Nome é obrigatório.';
        $campoErro = 'nome';
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    if (empty($cpf) || strlen($cpf) !== 11) {
        $mensagem = 'O CPF deve ter exatamente 11 dígitos.';
        $campoErro = 'cpf';
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    $data_nascimento_obj = new DateTime($data_nascimento);
    $idade = $data_nascimento_obj->diff(new DateTime())->y;

    if ($idade < 18) {
        $mensagem = 'O paciente deve ser maior de idade.';
        $campoErro = 'data_nascimento';
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    $erroDuplicado = verificaDuplicidadeUsuarioDetalhado($conn, $cpf, $email, $telefone, null);
    if ($erroDuplicado) {
        $mensagem = $erroDuplicado;
        $campoErro = '';
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    $senha_temporaria = bin2hex(random_bytes(4));
    $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));

    $sql = "INSERT INTO paciente_maior (
        pmaior_nome, 
        pmaior_endereco, 
        pmaior_telefone, 
        pmaior_email, 
        pmaior_cpf, 
        pmaior_sexo, 
        pmaior_estadocivil, 
        pmaior_datanasc, 
        pmaior_senha,
        token_recuperacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $mensagem = "Erro ao preparar INSERT: " . $conn->error;
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("ssssssssss", $nome, $endereco, $telefone, $email, $cpf, $sexo, $estado_civil, $data_nascimento, $senha_hash, $token);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'medsyncc@gmail.com';
            $mail->Password = 'rcdu jzij lotr akgk';
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
            $mail->addAddress($email, $nome);
            $mail->addReplyTo('medsyncc@gmail.com', 'Medsync');

            $mail->isHTML(true);
            $mail->Subject = 'Acesso ao portal Medsync';
            $mail->Body = "<p>Olá <strong>$nome</strong>,</p><p>Sua senha temporária de acesso é: <strong>$senha_temporaria</strong></p><p><a href='http://localhost/medsync/alterar_senha.php?token=$token'>Clique aqui para alterar sua senha</a></p><p>Equipe Medsync</p>";
            $mail->AltBody = "Olá $nome,\n\nSua senha temporária é: $senha_temporaria\n\nAcesse: http://localhost/medsync/alterar_senha.php?token=$token\n\nEquipe Medsync";

            $mail->send();

            $mensagem = 'Paciente cadastrado com sucesso! A senha foi enviada por e-mail.';
            $campoErro = '';

            echo "<script>alert('$mensagem'); window.location.href='cadastropaciente.php';</script>";
            exit;
        } catch (Exception $e) {
            $mensagem = "Paciente cadastrado, mas houve erro no envio do e-mail: {$mail->ErrorInfo}";
            echo "<script>alert('$mensagem'); window.location.href='cadastropaciente.php';</script>";
            exit;
        }
    } else {
        $mensagem = "Erro ao cadastrar: " . $stmt->error;
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link rel="stylesheet" href="../css/cadastropaciente.css">
  <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
  <title>Medsync</title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
  </style>
</head>
<body>
<nav class="navbar">
        <div class="logo">
            <a href="../medsync/adm-clinica"><img src="../images/logo/logo_branca.png"  ></a>
        </div>
        <ul class="nav-links">
        <a href="adm-clinica.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
  <div class="container">
    <h1>Cadastro do Paciente</h1><br><br>

    <form name="f2" action="" method="post">
      <div class="separando">
        <div>
          <font color="#0a4e53" class="tamanho">Nome:</font>
          <input type="text" id="nome" name="nome" placeholder="Nome">
          <font color="#0a4e53" class="tamanho">Endereço:</font>
          <input type="text" class="inputfont" id="endereco" name="endereco" placeholder="Endereço">
          <font color="#0a4e53" class="tamanho">Telefone:</font>
          <input type="tel" class="inputfont" id="telefone" name="telefone" placeholder="Telefone">
          <font color="#0a4e53" class="tamanho">E-mail:</font>
          <input type="email" class="inputfont" id="email" name="email" placeholder="E-mail">
        </div>

        <div class="formdadireita">
          <font color="#0a4e53" class="tamanho">CPF:</font>
          <input type="text" class="inputfont" id="cpf" name="cpf" placeholder="CPF" maxlength="14">

          <font color="#0a4e53" class="tamanho">Sexo:</font>
          <div class="radio-container">
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option1" name="sexo" value="Masculino" type="radio">
                <span class="radio-checkmark"></span>
                <span class="radio-label">Masculino</span>
              </label>
            </div>
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option2" name="sexo" value="Feminino" type="radio">
                <span class="radio-checkmark"></span>
                <span class="radio-label">Feminino</span>
              </label>
            </div>
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option3" name="sexo" value="Outro" type="radio">
                <span class="radio-checkmark"></span>
                <span class="radio-label">Outro</span>
              </label>
            </div>
          </div>

          <font color="#0a4e53" class="tamanho">Estado civil:</font><br>
          <select class="select" name="estado_civil">
            <option>Casado</option>
            <option>Solteiro</option>
            <option>Divorciado</option>
            <option>União Estável</option>
            <option>Outro</option>
          </select><br>

          <font color="#0a4e53" class="tamanho">Data de Nascimento:</font><br>
          <input type="date" name="data_nascimento" id="data_nascimento"><br>
        </div>
      </div>

      <input type="submit" value="Cadastrar" class="entrar">
    </form>
  </div>

  <?php if ($formFoiEnviado && !empty($mensagem) && !empty($campoErro)): ?>
    <script>
      window.addEventListener("DOMContentLoaded", function() {
        alert("<?= $mensagem ?>");
        var campo = document.getElementById("<?= $campoErro ?>");
        if (campo) {
          campo.value = "";
          campo.focus();
        }
      });
    </script>
  <?php endif; ?>

    <script src="https://unpkg.com/imask"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
      var element = document.getElementById('telefone');
      if (element) {
        IMask(element, { mask: '(00) 00000-0000' });
      }
      var elementCpf = document.getElementById('cpf');
      if (elementCpf) {
        IMask(elementCpf, { mask: '000.000.000-00' });
      }
    });
    </script>
</body>
</html>