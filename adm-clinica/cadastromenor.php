<?php
require_once('../src/PHPMailer.php');
require_once('../src/SMTP.php');
require_once('../src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Inicializar variáveis para evitar erros
$formFoiEnviado = false;
$mensagem = '';
$campoErro = '';

$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

function verificaDuplicidadeUsuarioDetalhado($conn, $cpf, $email, $telefone) {
    $verificacoes = [
        // Médicos
        ["SELECT 1 FROM medicos WHERE medicos_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM medicos WHERE medicos_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM medicos WHERE medicos_telefone = ?", $telefone, "telefone"],

        // Pacientes maiores
        ["SELECT 1 FROM paciente_maior WHERE pmaior_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_telefone = ?", $telefone, "telefone"],

        // Pacientes menores
        ["SELECT 1 FROM paciente_menor WHERE pmenor_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_telefone = ?", $telefone, "telefone"],

        // Farmácias
        ["SELECT 1 FROM farmacia WHERE farmacia_cnpj = ?", $cpf, "CNPJ"],
        ["SELECT 1 FROM farmacia WHERE farmacia_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM farmacia WHERE farmacia_telefone = ?", $telefone, "telefone"],

        // Administradores
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
    $formFoiEnviado = true;
    $nomeResponsavel = $_POST['nomeresponsavel'] ?? '';
    $cpfResponsavel = preg_replace('/\D/', '', $_POST['cpfresponsavel'] ?? '');
    $nome = $_POST['nome'] ?? '';
    $cpf = preg_replace('/\D/', '', $_POST['cpf'] ?? '');
    $endereco = $_POST['endereco'] ?? '';
    $telefone = preg_replace('/\D/', '', $_POST['telefone'] ?? '');
    $email = $_POST['email'] ?? '';
    $sexo = $_POST['sexo'] ?? '';
    $estado_civil = $_POST['estado_civil'] ?? '';
    $data_nascimento = $_POST['data_nascimento'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($cpf) || empty($email) || empty($telefone)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $campoErro = empty($nome) ? "nome" : (empty($cpf) ? "cpf" : (empty($email) ? "email" : "telefone"));
    } elseif (empty($cpfResponsavel) || strlen($cpfResponsavel) != 11) {
        $mensagem = "CPF do responsável inválido.";
        $campoErro = "cpfresponsavel";
    } elseif (strlen($cpf) != 11) {
        $mensagem = "CPF do paciente inválido.";
        $campoErro = "cpf";
    } else {
        // Verificação detalhada
        $erroDuplicado = verificaDuplicidadeUsuarioDetalhado($conn, $cpf, $email, $telefone);
        if ($erroDuplicado) {
            $mensagem = $erroDuplicado;
            $campoErro = strpos($erroDuplicado, 'CPF') !== false ? "cpf" : 
                        (strpos($erroDuplicado, 'e-mail') !== false ? "email" : "telefone");
        } else {
            // Verificar se o responsável existe
            $stmt = $conn->prepare("SELECT pmaior_id FROM paciente_maior WHERE pmaior_cpf = ?");
            $stmt->bind_param("s", $cpfResponsavel);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                $mensagem = "Responsável não encontrado. O CPF do responsável deve estar cadastrado como paciente maior.";
                $campoErro = "cpfresponsavel";
            } else {
                $responsavel = $result->fetch_assoc();
                $responsavel_id = $responsavel['pmaior_id'];

                $senha_temporaria = bin2hex(random_bytes(4));
                $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(16));

                $stmt = $conn->prepare("INSERT INTO paciente_menor (
                    paciente_maior_pmaior_id,
                    pmenor_cpf,
                    pmenor_nome,
                    pmenor_endereco,
                    pmenor_telefone,
                    pmenor_email,
                    pmenor_sexo,
                    pmenor_estadocivil,
                    pmenor_datanasc,
                    pmenor_senha,
                    token_recuperacao
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->bind_param("issssssssss", $responsavel_id, $cpf, $nome, $endereco, $telefone, $email, $sexo, $estado_civil, $data_nascimento, $senha_hash, $token);

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
                        $mail->Body = "<p>Olá <strong>$nome</strong>,</p><p>Sua senha temporária de acesso é: <strong>$senha_temporaria</strong></p><p>Altere sua senha acessando:<br><a href='http://localhost/medsync/alterar_senha.php?token=$token'>Clique aqui para alterar sua senha</a></p><p>Atenciosamente,<br><strong>Equipe Medsync</strong></p>";
                        $mail->AltBody = "Olá $nome,\n\nSua senha temporária de acesso é: $senha_temporaria\n\nAltere sua senha acessando: http://localhost/medsync/alterar_senha.php?token=$token\n\nEquipe Medsync";

                        $mail->send();

                        $mensagem = "Paciente cadastrado com sucesso! A senha foi enviada por e-mail.";
                    } catch (Exception $e) {
                        $mensagem = "Paciente cadastrado, mas houve um erro ao enviar o e-mail: {$mail->ErrorInfo}";
                    }
                } else {
                    $mensagem = "Erro ao cadastrar: " . $stmt->error;
                    $campoErro = "nome";
                }
            }
        }
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
  <link rel="stylesheet" href="../css/cadastromenor.css">
  <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
  </style>
  <title>Medsync</title>
</head>

<body>
<nav class="navbar">
        <div class="logo">
            <a href="../medsync/adm-clinica"><img src="../images/logo/logo_branca.png"></a>
        </div>
        <ul class="nav-links">
        <a href="adm-clinica.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
  <div class="container">
    <h1>Cadastro do Paciente</h1>
    <h2>para menores de idade</h2>
    <br><br>
    <form name="f2" action="" method="post">
      <div class="separando">
        <div>
          <font color="#0a4e53" class="tamanho">Nome do responsável:</font>
          <input type="text" class="inputfont" id="nomeresponsavel" name="nomeresponsavel" placeholder="Nome do responsável" value="<?php echo isset($_POST['nomeresponsavel']) ? htmlspecialchars($_POST['nomeresponsavel']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">CPF do responsável:</font>
          <input type="text" class="inputfont" id="cpfresponsavel" name="cpfresponsavel" placeholder="CPF do responsável" maxlength="14" value="<?php echo isset($_POST['cpfresponsavel']) ? htmlspecialchars($_POST['cpfresponsavel']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">Nome do paciente:</font>
          <input type="text" class="inputfont" id="nome" name="nome" placeholder="Nome do paciente" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">Endereço:</font>
          <input type="text" class="inputfont" id="endereco" name="endereco" placeholder="Endereço" value="<?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">Telefone:</font>
          <input type="text" class="inputfont" id="telefone" name="telefone" placeholder="Telefone" maxlength="15" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>">
        </div>

        <div class="formdadireita">
          <font color="#0a4e53" class="tamanho">E-mail:</font>
          <input type="email" class="inputfont" id="email" name="email" placeholder="E-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">CPF:</font>
          <input type="text" class="inputfont" id="cpf" name="cpf" placeholder="CPF do paciente" maxlength="14" value="<?php echo isset($_POST['cpf']) ? htmlspecialchars($_POST['cpf']) : ''; ?>">

          <font color="#0a4e53" class="tamanho">Sexo:</font>
          <div class="radio-container">
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option1" name="sexo" value="Masculino" type="radio" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Masculino') ? 'checked' : ''; ?>>
                <span class="radio-checkmark"></span>
                <span class="radio-label">Masculino</span>
              </label>
            </div>
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option2" name="sexo" value="Feminino" type="radio" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Feminino') ? 'checked' : ''; ?>>
                <span class="radio-checkmark"></span>
                <span class="radio-label">Feminino</span>
              </label>
            </div>
            <div class="radio-wrapper">
              <label class="radio-button">
                <input id="option3" name="sexo" value="Outro" type="radio" <?php echo (isset($_POST['sexo']) && $_POST['sexo'] == 'Outro') ? 'checked' : ''; ?>>
                <span class="radio-checkmark"></span>
                <span class="radio-label">Outro</span>
              </label>
            </div>
          </div>

          <font color="#0a4e53" class="tamanho">Estado civil:</font><br>
          <select class="select" name="estado_civil">
            <option value="Casado" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'Casado') ? 'selected' : ''; ?>>Casado</option>
            <option value="Solteiro" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'Solteiro') ? 'selected' : ''; ?>>Solteiro</option>
            <option value="Divorciado" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'Divorciado') ? 'selected' : ''; ?>>Divorciado</option>
            <option value="União Estável" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'União Estável') ? 'selected' : ''; ?>>União Estável</option>
            <option value="Outro" <?php echo (isset($_POST['estado_civil']) && $_POST['estado_civil'] == 'Outro') ? 'selected' : ''; ?>>Outro</option>
          </select><br>

          <font color="#0a4e53" class="tamanho">Data de nascimento:</font><br>
          <input type="date" id="data_nascimento" name="data_nascimento" value="<?php echo isset($_POST['data_nascimento']) ? htmlspecialchars($_POST['data_nascimento']) : ''; ?>"><br>
        </div>
      </div>

      <input type="submit" value="Cadastrar" class="entrar">
    </form>
  </div>

  <?php if ($formFoiEnviado && !empty($mensagem)): ?>
    <script>
      window.addEventListener("DOMContentLoaded", function() {
        alert("<?= $mensagem ?>");
        <?php if (!empty($campoErro)): ?>
          var campo = document.getElementById("<?= $campoErro ?>");
          if (campo) {
            campo.focus();
          }
        <?php endif; ?>
      });
    </script>
  <?php endif; ?>

  <script>
    // Função para aplicar máscara de CPF
    function mascaraCPF(campo) {
        campo.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            }
            e.target.value = value;
        });
    }

    // Função para aplicar máscara de telefone
    function mascaraTelefone(campo) {
        campo.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length <= 11) {
                if (value.length <= 10) {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{4})(\d)/, '$1-$2');
                } else {
                    value = value.replace(/(\d{2})(\d)/, '($1) $2');
                    value = value.replace(/(\d{5})(\d)/, '$1-$2');
                }
            }
            e.target.value = value;
        });
    }

    // Aplicar máscaras quando a página carregar
    document.addEventListener('DOMContentLoaded', function() {
        // Máscara para CPF do responsável
        const cpfResponsavel = document.getElementById('cpfresponsavel');
        if (cpfResponsavel) {
            mascaraCPF(cpfResponsavel);
        }

        // Máscara para CPF do paciente
        const cpfPaciente = document.getElementById('cpf');
        if (cpfPaciente) {
            mascaraCPF(cpfPaciente);
        }

        // Máscara para telefone
        const telefone = document.getElementById('telefone');
        if (telefone) {
            mascaraTelefone(telefone);
        }
    });
  </script>
</body>
</html>