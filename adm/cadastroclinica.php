<?php
// Inclui as classes do PHPMailer
require_once('../src/PHPMailer.php');
require_once('../src/SMTP.php');
require_once('../src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configurações do Banco de Dados
$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);

if (!$conn) {
    die("Erro na conexão: " . mysqli_connect_error());
}

$mensagem = "";
$campoErro = "";
$formFoiEnviado = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $formFoiEnviado = true;

    // Recebe e sanitiza os dados
    $nomefantasia = $_POST['nomefantasia'];
    $razaosocial = $_POST['razaosocial'];
    $cnpj = preg_replace('/\D/', '', $_POST['cnpj']); // Apenas dígitos
    $inscriestadual = preg_replace('/\D/', '', $_POST['inscriestadual']);
    $inscrimunicipal = preg_replace('/\D/', '', $_POST['inscrimunicipal']);
    $telefone = preg_replace('/\D/', '', $_POST['telefone']); // Apenas dígitos
    $email = $_POST['email'];
    $endereco = $_POST['endereco'];
    $especialidade = $_POST['especialidades'];
    $uf = $_POST['uf'];

    // 1. Validação do CNPJ (tamanho)
    if (strlen($cnpj) != 14) {
        // Esta mensagem será exibida na tela do formulário (se o HTML estiver configurado para isso)
        $mensagem = "CNPJ inválido (deve ter 14 dígitos).";
        $campoErro = "cnpj";
    } else {
        // 2. Verificações de Unicidade
        $verificacoes = [
            // Checagem de CNPJ (somente nas tabelas de CNPJ)
            ["SELECT 1 FROM clinica WHERE clinica_cnpj = ?", $cnpj, "CNPJ de clínica"],
            ["SELECT 1 FROM farmacia WHERE farmacia_cnpj = ?", $cnpj, "CNPJ de farmácia"],

            // Checagem de E-mail (em todas as tabelas de usuário)
            ["SELECT 1 FROM clinica WHERE clinica_email = ?", $email, "e-mail de clínica"],
            ["SELECT 1 FROM medicos WHERE medicos_email = ?", $email, "e-mail de médico"],
            ["SELECT 1 FROM paciente_maior WHERE pmaior_email = ?", $email, "e-mail de paciente"],
            ["SELECT 1 FROM farmacia WHERE farmacia_email = ?", $email, "e-mail de farmácia"],
            ["SELECT 1 FROM administradores WHERE admin_email = ?", $email, "e-mail de administrador"],

            // Checagem de Telefone (em todas as tabelas de usuário)
            ["SELECT 1 FROM clinica WHERE clinica_telefone = ?", $telefone, "telefone de clínica"],
            ["SELECT 1 FROM medicos WHERE medicos_telefone = ?", $telefone, "telefone de médico"],
            ["SELECT 1 FROM paciente_maior WHERE pmaior_telefone = ?", $telefone, "telefone de paciente"],
            ["SELECT 1 FROM farmacia WHERE farmacia_telefone = ?", $telefone, "telefone de farmácia"],
            ["SELECT 1 FROM administradores WHERE admin_telefone = ?", $telefone, "telefone de administrador"]
        ];
        
        foreach ($verificacoes as $verificacao) {
            list($sql, $valor, $campo) = $verificacao;
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                die("Erro ao preparar verificação de $campo: " . $conn->error);
            }

            $stmt->bind_param("s", $valor);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                echo "<script>alert('Já existe um usuário com o $campo informado.'); window.history.back();</script>";
                exit;
            }
        }

        // Gerar senha e token
        $senha_temporaria = bin2hex(random_bytes(4));
        $senha_hash = password_hash($senha_temporaria, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(16));

        // 3. Inserir clínica no banco de dados
        $stmt = $conn->prepare("INSERT INTO clinica (
            clinica_cnpj,
            clinica_nome_fant,
            clinica_razao_social,
            clinica_inscricao_estadual,
            clinica_inscricao_municipal,
            clinica_telefone,
            clinica_email,
            clinica_endereco,
            clinica_uf,
            clinica_especialidade,
            clinica_senha,
            token_recuperacao
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$stmt) {
            die("Erro ao preparar cadastro: " . $conn->error);
        }

        // Note: O tipo da inscrição estadual e municipal no seu SQL é VARCHAR(20), então 's' é o correto.
        $stmt->bind_param("ssssssssssss", $cnpj, $nomefantasia, $razaosocial, $inscriestadual, $inscrimunicipal, $telefone, $email, $endereco, $uf, $especialidade, $senha_hash, $token);

        if ($stmt->execute()) {
            // 4. Envio de e-mail (Mantido o código original)
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'medsyncc@gmail.com';
                $mail->Password = 'rcdu jzij lotr akgk'; // senha de app
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

                echo "<script>alert('Clínica cadastrada com sucesso! A senha foi enviada por e-mail.'); window.location.href='cadastroclinica.php';</script>";
                exit;
            } catch (Exception $e) {
                // Se o cadastro no BD funcionar, mas o e-mail falhar, ainda é um sucesso parcial.
                echo "<script>alert('Clínica cadastrada, mas houve um erro ao enviar o e-mail: {$mail->ErrorInfo}'); window.location.href='cadastroclinica.php';</script>";
                exit;
            }
        } else {
            $mensagem = "Erro ao cadastrar: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medsync - Cadastro de Clinicas</title>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/cadastroclinica.css">
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
        <h1>Cadastro de Clinicas</h1><br><br>
        <?php if (!empty($mensagem) && $campoErro == "cnpj"): ?>
            <p style="color: red; text-align: center;"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        
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
                    <input type="text" id="inscriestadual" name="inscriestadual" maxlength="13"
                        placeholder="Inscrição Estadual">

                    <label class="tamanho" for="inscrimunicipal">Inscrição Municipal:</label>
                    <input type="text" id="inscrimunicipal" name="inscrimunicipal" maxlength="15"
                        placeholder="Inscrição Municipal">
                </div>

                <div class="formdadireita">
                    <label class="tamanho" for="telefone">Telefone:</label>
                    <input type="tel" id="telefone" name="telefone" required placeholder="Telefone">

                    <label class="tamanho" for="email">E-mail:</label>
                    <input type="email" id="email" name="email" required placeholder="E-mail">

                    <label class="tamanho" for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" required placeholder="Endereço">

                    <font color="#0a4e53">Especialidades da Clinica:</font><br>
                    <select name="especialidades" id="especialidades" class="select2" required>
                        <option value="" disabled selected>Escolha uma especialidade</option>
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
                        <option value="Psiquiatria da Infância e Adolescência">Psiquiatria da Infância e Adolescência
                        </option>
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
                        <option value="Medicina Física e Reabilitação (Fisiatria)">Medicina Física e Reabilitação
                            (Fisiatria)</option>
                        <option value="Oftalmologia">Oftalmologia</option>
                        <option value="Otorrinolaringologia">Otorrinolaringologia</option>
                        <option value="Cirurgia Bucomaxilofacial">Cirurgia Bucomaxilofacial</option>
                        <option value="Radiologia">Radiologia</option>
                        <option value="Medicina Nuclear">Medicina Nuclear</option>
                        <option value="Ultrassonografia">Ultrassonografia</option>
                        <option value="Tomografia e Ressonância">Tomografia e Ressonância</option>
                        <option value="Radioterapia">Radioterapia</option>
                        <option value="Patologia Clínica / Medicina Laboratorial">Patologia Clínica / Medicina
                            Laboratorial</option>
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
                    </select>

                    <label class="tamanho" for="uf">UF:</label>
                    <select name="uf" id="uf" class="select2" required>
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.select2').select2({
                placeholder: "Selecione ou digite...",
                allowClear: true,
                width: 'resolve'
            });
        });
    </script>
</body>

</html>