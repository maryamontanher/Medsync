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
        // Médicos
        ["SELECT 1 FROM medicos WHERE medicos_crm = ?", $crm, "CRM"],
        ["SELECT 1 FROM medicos WHERE medicos_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM medicos WHERE medicos_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM medicos WHERE medicos_telefone = ?", $telefone, "telefone"],

        // Pacientes maiores
        ["SELECT 1 FROM paciente_maior WHERE pmaior_cpf = ?", $cpf, "CPF"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_email = ?", $email, "e-mail"],
        ["SELECT 1 FROM paciente_maior WHERE pmaior_telefone = ?", $telefone, "telefone"],

        // Pacientes menores
        ["SELECT 1 FROM paciente_menor WHERE pmenor_cpf  = ?", $cpf, "CPF do responsável"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_email = ?", $email, "e-mail do responsável"],
        ["SELECT 1 FROM paciente_menor WHERE pmenor_telefone = ?", $telefone, "telefone do responsável"],

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

    // Coleta e sanitização dos dados
    $nome = trim($_POST['nome']);
    $cpf = preg_replace('/\D/', '', $_POST['cpf']);
    $endereco = trim($_POST['endereco']);
    $telefone = preg_replace('/\D/', '', $_POST['telefone']);
    $email = trim($_POST['email']);
    $especialidade = $_POST['especialidades'];
    $crm = preg_replace('/\D/', '', $_POST['crm']);
    $ufcrm = isset($_POST['estado']) ? substr($_POST['estado'], 0, 2) : '';
    $sexo = isset($_POST['sexo']) ? $_POST['sexo'] : '';
    $data_nascimento = $_POST['data_nascimento'];

    // Validações
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

    if (empty($crm) || strlen($crm) != 6) {
        $mensagem = "CRM inválido. Deve ter 6 dígitos.";
        $campoErro = "crm";
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    if (empty($ufcrm)) {
        $mensagem = "Selecione o UF do CRM.";
        $campoErro = "estado";
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = "E-mail inválido.";
        $campoErro = "email";
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    if (empty($sexo)) {
        $mensagem = "Selecione o sexo.";
        $campoErro = "sexo";
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    if (empty($data_nascimento)) {
        $mensagem = "Data de nascimento é obrigatória.";
        $campoErro = "data_nascimento";
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    // Validação de idade (médico deve ser maior de idade)
    $data_nascimento_obj = new DateTime($data_nascimento);
    $idade = $data_nascimento_obj->diff(new DateTime())->y;

    if ($idade < 18) {
        $mensagem = 'O médico deve ser maior de idade.';
        $campoErro = 'data_nascimento';
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    // Verifica duplicidade
    $erroDuplicado = verificaDuplicidadeUsuarioDetalhado($conn, $cpf, $email, $telefone, $crm);
    if ($erroDuplicado) {
        $mensagem = $erroDuplicado;
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    // Gera senha temporária e token
    $senha_temporaria = bin2hex(random_bytes(4));
    $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16));

    // Prepara e executa o INSERT
    $stmt = $conn->prepare("INSERT INTO medicos (
        medicos_crm,
        medicos_uf_crm,
        medicos_nome,
        medicos_cpf,
        medicos_endereco,
        medicos_telefone,
        medicos_email,
        medicos_especialidade,
        medicos_sexo,
        medicos_datanasc,
        medico_senha,
        token_recuperacao
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        $mensagem = "Erro ao preparar cadastro: " . $conn->error;
        echo "<script>alert('$mensagem'); window.history.back();</script>";
        exit;
    }

    $stmt->bind_param("ssssssssssss", $crm, $ufcrm, $nome, $cpf, $endereco, $telefone, $email, $especialidade, $sexo, $data_nascimento, $senha_hash, $token);

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
            $mail->Body = "
                <p>Olá <strong>$nome</strong>,</p>
                <p>Sua senha temporária de acesso é: <strong>$senha_temporaria</strong></p>
                <p>Altere sua senha acessando:<br>
                <a href='http://localhost/medsync/alterar_senha.php?token=$token'>Clique aqui para alterar sua senha</a></p>
                <p>Atenciosamente,<br><strong>Equipe Medsync</strong></p>
            ";
            $mail->AltBody = "Olá $nome,\n\nSua senha temporária de acesso é: $senha_temporaria\n\nAltere sua senha acessando: http://localhost/medsync/alterar_senha.php?token=$token\n\nEquipe Medsync";

            if ($mail->send()) {
                echo "<script>
                    alert('Médico cadastrado com sucesso! A senha temporária foi enviada para o e-mail: $email');
                    window.location.href = 'cadastromed.php';
                </script>";
            } else {
                echo "<script>
                    alert('Médico cadastrado com sucesso! Senha temporária: $senha_temporaria (Erro no envio do e-mail)');
                    window.location.href = 'cadastromed.php';
                </script>";
            }
            exit;
            
        } catch (Exception $e) {
            echo "<script>
                alert('Médico cadastrado com sucesso! Senha temporária: $senha_temporaria (Erro no envio do e-mail: {$mail->ErrorInfo})');
                window.location.href = 'cadastromed.php';
            </script>";
            exit;
        }
    } else {
        $mensagem = "Erro ao cadastrar médico: " . $stmt->error;
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
    <link rel="stylesheet" href="../css/cadastromedico.css">
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <title>Medsync - Cadastro de Médicos</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        
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

        /* CORREÇÃO PARA O SELECT2 - MANTENDO O LAYOUT ORIGINAL */
        .select2-container {
            width: 100% !important;
            box-sizing: border-box;
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
    </style>
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
    <h1>Cadastro de Médicos</h1><br><br>
    
    <form name="f2" action="" method="post" id="formCadastro">
        <div class="separando">
            <div>
                <font color="#0a4e53" class="tamanho">Nome:</font>
                <input type="text" id="nome" name="nome" placeholder="Nome" required>
                <font color="#0a4e53" class="tamanho">CPF:</font>
                <input type="text" class="inputfont" id="cpf" name="cpf" placeholder="CPF" maxlength="14" required>
                <font color="#0a4e53" class="tamanho">Endereço:</font>
                <input type="text" class="inputfont" id="endereco" name="endereco" placeholder="Endereço" required>
                <font color="#0a4e53" class="tamanho">Telefone:</font>
                <input type="tel" class="inputfont" id="telefone" name="telefone" placeholder="Telefone" required>
                <font color="#0a4e53">Especialidades:</font><br>
                <select name="especialidades" id="especialidades" class="select2" required>
                    <option value="" disabled selected>Selecione ou digite...</option>
                    <option value="Clínica Médica (Medicina Interna)">Clínica Médica (Medicina Interna)</option>
                    <option value="Medicina de Família e Comunidade">Medicina de Família e Comunidade</option>
                    <option value="Medicina Preventiva e Social">Medicina Preventiva e Social</option>
                    <option value="Geriatria">Geriatria</option>
                    <option value="Medicina do Trabalho">Medicina do Trabalho</option>
                    <option value="Medicina do Esporte">Medicina do Esporte</option>
                    <option value="Cardiologia">Cardiologia</option>
                    <option value="Cirurgia Cardiovascular">Cirurgia Cardiovascular</option>
                    <option value="Pneumologia">Pneumologia</option>
                    <option value="Cirurgia Torácica">Cirurgia Torácica</option>
                    <option value="Neurologia">Neurologia</option>
                    <option value="Neurocirurgia">Neurocirurgia</option>
                    <option value="Psiquiatria">Psiquiatria</option>
                    <option value="Psiquiatria da Infância e Adolescência">Psiquiatria da Infância e Adolescência</option>
                    <option value="Medicina do Sono">Medicina do Sono</option>
                    <option value="Medicina Psicossomática">Medicina Psicossomática</option>
                    <option value="Cirurgia Geral">Cirurgia Geral</option>
                    <option value="Cirurgia Plástica">Cirurgia Plástica</option>
                    <option value="Cirurgia Vascular">Cirurgia Vascular</option>
                    <option value="Cirurgia Oncológica">Cirurgia Oncológica</option>
                    <option value="Cirurgia do Aparelho Digestivo">Cirurgia do Aparelho Digestivo</option>
                    <option value="Cirurgia de Cabeça e Pescoço">Cirurgia de Cabeça e Pescoço</option>
                    <option value="Cirurgia Bariátrica">Cirurgia Bariátrica</option>
                    <option value="Ortopedia e Traumatologia">Ortopedia e Traumatologia</option>
                    <option value="Medicina da Dor">Medicina da Dor</option>
                    <option value="Medicina Física e Reabilitação (Fisiatria)">Medicina Física e Reabilitação (Fisiatria)</option>
                    <option value="Oftalmologia">Oftalmologia</option>
                    <option value="Otorrinolaringologia">Otorrinolaringologia</option>
                    <option value="Cirurgia Bucomaxilofacial">Cirurgia Bucomaxilofacial</option>
                    <option value="Radiologia">Radiologia</option>
                    <option value="Medicina Nuclear">Medicina Nuclear</option>
                    <option value="Ultrassonografia">Ultrassonografia</option>
                    <option value="Tomografia e Ressonância">Tomografia e Ressonância</option>
                    <option value="Radioterapia">Radioterapia</option>
                    <option value="Patologia Clínica / Medicina Laboratorial">Patologia Clínica / Medicina Laboratorial</option>
                    <option value="Patologia">Patologia</option>
                    <option value="Genética Médica">Genética Médica</option>
                    <option value="Medicina Legal">Medicina Legal</option>
                    <option value="Medicina Legal e Perícia Médica">Medicina Legal e Perícia Médica</option>
                    <option value="Auditoria Médica">Auditoria Médica</option>
                    <option value="Administração em Saúde">Administração em Saúde</option>
                    <option value="Pediatria">Pediatria</option>
                    <option value="Neonatologia">Neonatologia</option>
                    <option value="Pediatria Intensiva">Pediatria Intensiva</option>
                    <option value="Alergia e Imunologia Pediátrica">Alergia e Imunologia Pediátrica</option>
                    <option value="Neurologia Pediátrica">Neurologia Pediátrica</option>
                    <option value="Cardiologia Pediátrica">Cardiologia Pediátrica</option>
                    <option value="Endocrinologia Pediátrica">Endocrinologia Pediátrica</option>
                    <option value="Gastroenterologia Pediátrica">Gastroenterologia Pediátrica</option>
                    <option value="Nefrologia Pediátrica">Nefrologia Pediátrica</option>
                    <option value="Pneumologia Pediátrica">Pneumologia Pediátrica</option>
                    <option value="Ginecologia">Ginecologia</option>
                    <option value="Obstetrícia">Obstetrícia</option>
                    <option value="Reprodução Assistida">Reprodução Assistida</option>
                    <option value="Mastologia">Mastologia</option>
                    <option value="Endocrinologia Ginecológica">Endocrinologia Ginecológica</option>
                    <option value="Urologia">Urologia</option>
                    <option value="Andrologia">Andrologia</option>
                    <option value="Endocrinologia">Endocrinologia</option>
                    <option value="Alergia e Imunologia">Alergia e Imunologia</option>
                    <option value="Hematologia e Hemoterapia">Hematologia e Hemoterapia</option>
                    <option value="Reumatologia">Reumatologia</option>
                    <option value="Gastroenterologia">Gastroenterologia</option>
                    <option value="Hepatologia">Hepatologia</option>
                    <option value="Proctologia (Coloproctologia)">Proctologia (Coloproctologia)</option>
                    <option value="Nefrologia">Nefrologia</option>
                    <option value="Medicina Intensiva (UTI)">Medicina Intensiva (UTI)</option>
                    <option value="Medicina de Urgência / Emergência">Medicina de Urgência / Emergência</option>
                    <option value="Infectologia">Infectologia</option>
                    <option value="Oncologia Clínica">Oncologia Clínica</option>
                    <option value="Transplantes">Transplantes</option>
                    <option value="Dor e Cuidados Paliativos">Dor e Cuidados Paliativos</option>
                    <option value="Telemedicina">Telemedicina</option>
                </select><br>
            </div>
            
            <div class="formdadireita">
                <font color="#0a4e53" class="">CRM:</font>
                <input type="text" id="crm" name="crm" placeholder="CRM" class="crm" maxlength="6" required>
                <font color="#0a4e53">UF:</font><br>
                <select name="estado" id="estado" class="select2" required>
                    <option value="" disabled selected>Selecione ou digite...</option>
                    <option value="AC">AC - Acre</option>
                    <option value="AL">AL - Alagoas</option>
                    <option value="AP">AP - Amapá</option>
                    <option value="AM">AM - Amazonas</option>
                    <option value="BA">BA - Bahia</option>
                    <option value="CE">CE - Ceará</option>
                    <option value="DF">DF - Distrito Federal</option>
                    <option value="ES">ES - Espírito Santo</option>
                    <option value="GO">GO - Goiás</option>
                    <option value="MA">MA - Maranhão</option>
                    <option value="MT">MT - Mato Grosso</option>
                    <option value="MS">MS - Mato Grosso do Sul</option>
                    <option value="MG">MG - Minas Gerais</option>
                    <option value="PA">PA - Pará</option>
                    <option value="PB">PB - Paraíba</option>
                    <option value="PR">PR - Paraná</option>
                    <option value="PE">PE - Pernambuco</option>
                    <option value="PI">PI - Piauí</option>
                    <option value="RJ">RJ - Rio de Janeiro</option>
                    <option value="RN">RN - Rio Grande do Norte</option>
                    <option value="RS">RS - Rio Grande do Sul</option>
                    <option value="RO">RO - Rondônia</option>
                    <option value="RR">RR - Roraima</option>
                    <option value="SC">SC - Santa Catarina</option>
                    <option value="SP">SP - São Paulo</option>
                    <option value="SE">SE - Sergipe</option>
                    <option value="TO">TO - Tocantins</option>
                </select>

                <font color="#0a4e53" class="tamanho">E-mail:</font>
                <input type="email" class="inputfont" id="email" name="email" placeholder="E-mail" required>

                <font color="#0a4e53" class="tamanho">Sexo:</font>
                <div class="radio-container">
                    <div class="radio-wrapper">
                        <label class="radio-button">
                            <input id="option1" name="sexo" value="Masculino" type="radio" required>
                            <span class="radio-checkmark"></span>
                            <span class="radio-label">Masculino</span>
                        </label>
                    </div>
                    <div class="radio-wrapper">
                        <label class="radio-button">
                            <input id="option2" name="sexo" value="Feminino" type="radio" required>
                            <span class="radio-checkmark"></span>
                            <span class="radio-label">Feminino</span>
                        </label>
                    </div>
                    <div class="radio-wrapper">
                        <label class="radio-button">
                            <input id="option3" name="sexo" value="Outro" type="radio" required>
                            <span class="radio-checkmark"></span>
                            <span class="radio-label">Outro</span>
                        </label>
                    </div>
                </div>
                <br>
                <font color="#0a4e53" class="tamanho">Data de nascimento:</font>
                <input type="date" id="data_nascimento" name="data_nascimento" required><br>
            </div>
        </div>
        <input type="submit" value="Cadastrar" class="entrar">
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/imask"></script>
<script>
    $(document).ready(function () {
        $('.select2').select2({
            placeholder: "Selecione ou digite...",
            allowClear: true,
            width: 'resolve'
        });
    });

    // Adicionar máscaras
    document.addEventListener('DOMContentLoaded', function () {
        var telefone = document.getElementById('telefone');
        if (telefone) {
            IMask(telefone, { mask: '(00) 00000-0000' });
        }
        
        var cpf = document.getElementById('cpf');
        if (cpf) {
            IMask(cpf, { mask: '000.000.000-00' });
        }
        
        var crm = document.getElementById('crm');
        if (crm) {
            IMask(crm, { mask: '000000' });
        }
    });
</script>
</body>
</html>