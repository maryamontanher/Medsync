
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

function verificaDuplicidadeFarmacia($conn, $cnpj, $email, $telefone, $inscricaoMunicipal) {
    $verificacoes = [
    ["SELECT 1 FROM farmacia WHERE farmacia_cnpj = ?", $cnpj, "CNPJ"],
    ["SELECT 1 FROM farmacia WHERE farmacia_email = ?", $email, "e-mail"],
    ["SELECT 1 FROM farmacia WHERE farmacia_telefone = ?", $telefone, "telefone"],
    ["SELECT 1 FROM farmacia WHERE farmacia_inscricao_municipal = ?", $inscricaoMunicipal, "inscrição municipal"],

    // Verificações cruzadas
    ["SELECT 1 FROM medicos WHERE medicos_email = ?", $email, "e-mail"],
    ["SELECT 1 FROM medicos WHERE medicos_telefone = ?", $telefone, "telefone"],
    ["SELECT 1 FROM paciente_maior WHERE pmaior_email = ?", $email, "e-mail"],
    ["SELECT 1 FROM paciente_maior WHERE pmaior_telefone = ?", $telefone, "telefone"],
    ["SELECT 1 FROM paciente_menor WHERE pmenor_email = ?", $email, "e-mail"],
    ["SELECT 1 FROM paciente_menor WHERE pmenor_telefone = ?", $telefone, "telefone"],

    // NOVO: Verificações com administradores
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
    $nomefantasia = $_POST['nomefantasia'];
    $razaosocial = $_POST['razaosocial'];
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj']);
    $inscriestadual = preg_replace('/\D/', '', $_POST['inscriestadual']);
    $inscrimunicipal = preg_replace('/\D/', '', $_POST['inscrimunicipal']);
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $cep = preg_replace('/\D/', '', $_POST['cep']);
    $uf = $_POST['uf'];

    if (strlen($cnpj) != 14) {
        echo "<script>alert('CNPJ inválido.'); window.history.back();</script>";
        exit;
    }

    $erroDuplicado = verificaDuplicidadeFarmacia($conn, $cnpj, $email, $telefone, $inscrimunicipal);
    if ($erroDuplicado) {
        echo "<script>alert('$erroDuplicado'); window.history.back();</script>";
        exit;
    }

    $senha_temporaria = bin2hex(random_bytes(4));
    $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("INSERT INTO farmacia (
        farmacia_cnpj,
        farmacia_nome_fant,
        farmacia_razao_social,
        farmacia_inscricao_estadual,
        farmacia_inscricao_municipal,
        farmacia_telefone,
        farmacia_email,
        farmacia_endereco,
        farmacia_uf,
        farmacia_cep,
        token_recuperacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Erro ao preparar INSERT: " . $conn->error);
    }

    $stmt->bind_param("sssssssssss", $cnpj, $nomefantasia, $razaosocial, $inscriestadual, $inscrimunicipal, $telefone, $email, $endereco, $uf, $cep, $token);

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
            $mail->addAddress($email, $nomefantasia);
            $mail->addReplyTo('medsyncc@gmail.com', 'Medsync');

            $mail->isHTML(true);
            $mail->Subject = 'Acesso ao portal Medsync';
            $mail->Body = "
                <p>Olá <strong>$nomefantasia</strong>,</p>
                <p>Sua senha temporária de acesso é: <strong>$senha_temporaria</strong></p>
                <p>Altere sua senha acessando:<br>
                <a href='http://localhost/medsync/alterar_senha.php?token=$token'>Clique aqui para alterar sua senha</a></p>
                <p>Atenciosamente,<br><strong>Equipe Medsync</strong></p>
            ";
            $mail->AltBody = "Olá $nomefantasia,\n\nSua senha temporária de acesso é: $senha_temporaria\n\nAltere sua senha acessando: http://localhost/medsync/alterar_senha.php?token=$token\n\nEquipe Medsync";

            $mail->send();

            echo "<script>alert('Farmácia cadastrada com sucesso! A senha foi enviada por e-mail.'); window.location.href='cadastrofarmacia.php';</script>";
            exit;

        } catch (Exception $e) {
            echo "<script>alert('Farmácia cadastrada, mas houve um erro ao enviar o e-mail: {$mail->ErrorInfo}'); window.location.href='cadastrofarmacia.php';</script>";
            exit;
        }
    } else {
        echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medsync - Cadastro de Farmácias</title>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/cadastrofarmacia.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
     <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        

        .select2-container {
            width: 100% !important;
            box-sizing: border-box;
            margin-top: 10px;
            margin-bottom: 20px;
        }

        .select2-container--default .select2-selection--single {
            width: 100%;
            padding: 10px;
            height: auto;
            border: 1px solid #7e8786;
            border-radius: 5px;
            font-size: 15px;
            font-family: 'Roboto', sans-serif;
            color: #000;
            background-color: #fff;
            box-sizing: border-box;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: normal;
            padding: 0;
            margin-top: 2px;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 10px;
        }

        .select2-container--default .select2-selection--single:focus {
            outline: none;
            border-color: #7e8786;
            box-shadow: none;
        }
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #4bb9a5;
            padding: 0px 10px;
            position: fixed;
            top: 0;
            left: 0;
            height: 70px;
            width: 100%;
            z-index: 1000;
        }

        .logo img {
            width: 120px;
            background: no-repeat left center / contain;
        }

        .nav-links {
            list-style: none;
            display: flex;
        }

        .nav-links li {
            margin: 0 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: rgb(50, 97, 81);
        }
    </style>
</head>

<body>
<nav class="navbar">
        <div class="logo">
            <a href="../medsync/adm.php"><img src="../images/logo/logo_branca.png"  ></a>
        </div>
        <ul class="nav-links">
        <a href="adm.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
    <div class="container">
        <h1>Cadastro de Farmácias</h1><br><br>
        <form name="f2" action="" method="post" id="formCadastro">
            <div class="separando">
                <div class="formdadireita">
                    <label class="tamanho" for="nomefantasia">Nome Fantasia:</label>
                    <input type="text" id="nomefantasia" name="nomefantasia" required placeholder="Nome Fantasia">

                    <label class="tamanho" for="razaosocial">Razão Social:</label>
                    <input type="text" id="razaosocial" name="razaosocial" required placeholder="Razão Social">

                    <label class="tamanho" for="cnpj">CNPJ:</label>
                    <input type="text" id="cnpj" name="cnpj" maxlength="14" required placeholder="CNPJ">

                    <label class="tamanho" for="inscriestadual">Inscrição Estadual:</label>
                    <input type="text" id="inscriestadual" name="inscriestadual" maxlength="13" placeholder="Inscrição Estadual">

                    <label class="tamanho" for="inscrimunicipal">Inscrição Municipal:</label>
                    <input type="text" id="inscrimunicipal" name="inscrimunicipal" maxlength="15" placeholder="Inscrição Municipal">
                </div>

                <div class="formdadireita">
                    <label class="tamanho" for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" required placeholder="Telefone" maxlength="11">

                    <label class="tamanho" for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="E-mail" maxlength="255">

                    <label class="tamanho" for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" required placeholder="Endereço">

                    <label class="tamanho" for="cep">CEP:</label>
                    <input type="text" id="cep" name="cep" required placeholder="CEP" maxlength="8">

                    <label class="tamanho" for="uf">UF:</label>
                    <select name="uf" id="uf" class="uf" required >
                        <option value="">Selecione o estado</option>
                        <option value="AC">Acre (AC)</option>
                        <option value="AL">Alagoas (AL)</option>
                        <option value="AP">Amapá (AP)</option>
                        <option value="AM">Amazonas (AM)</option>
                        <option value="BA">Bahia (BA)</option>
                        <option value="CE">Ceará (CE)</option>
                        <option value="DF">Distrito Federal (DF)</option>
                        <option value="ES">Espírito Santo (ES)</option>
                        <option value="GO">Goiás (GO)</option>
                        <option value="MA">Maranhão (MA)</option>
                        <option value="MT">Mato Grosso (MT)</option>
                        <option value="MS">Mato Grosso do Sul (MS)</option>
                        <option value="MG">Minas Gerais (MG)</option>
                        <option value="PA">Pará (PA)</option>
                        <option value="PB">Paraíba (PB)</option>
                        <option value="PR">Paraná (PR)</option>
                        <option value="PE">Pernambuco (PE)</option>
                        <option value="PI">Piauí (PI)</option>
                        <option value="RJ">Rio de Janeiro (RJ)</option>
                        <option value="RN">Rio Grande do Norte (RN)</option>
                        <option value="RS">Rio Grande do Sul (RS)</option>
                        <option value="RO">Rondônia (RO)</option>
                        <option value="RR">Roraima (RR)</option>
                        <option value="SC">Santa Catarina (SC)</option>
                        <option value="SP">São Paulo (SP)</option>
                        <option value="SE">Sergipe (SE)</option>
                        <option value="TO">Tocantins (TO)</option>
                    </select>
                </div>
            </div>
            <input type="submit" value="Cadastrar">
        </form>
    </div>

    <!-- jQuery e Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#uf').select2({
                placeholder: "Selecione ou digite o estado",
                allowClear: true
            });
        });
    </script>
</body>

</html>
