<?php
require_once('../src/PHPMailer.php');
require_once('../src/SMTP.php');
require_once('../src/Exception.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

$farmacia_id = isset($_SESSION['farmacia_id']) ? $_SESSION['farmacia_id'] : 1;

$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

function verificaDuplicidadeRemedios($conn, $registro_anvisa) {
    $sql = "SELECT 1 FROM remedios WHERE registro_anvisa = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $registro_anvisa);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return "Já existe um remédio com este registro da ANVISA.";
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome_remedio = $_POST['nome_remedio'] ?? '';
    $principio_ativo = $_POST['principio_ativo'] ?? '';
    $dosagem = $_POST['dosagem'] ?? '';
    $forma_farmaceutica = $_POST['forma_farmaceutica'] ?? '';
    $quantidade = $_POST['quantidade'] ?? 0;
    $registro_anvisa = preg_replace('/\D/', '', $_POST['registro_anvisa'] ?? '');

    if (strlen($registro_anvisa) != 20) {
        echo "<script>alert('Registro da ANVISA inválido.'); window.history.back();</script>";
        exit;
    }

    $erroDuplicado = verificaDuplicidadeRemedios($conn, $registro_anvisa);
    if ($erroDuplicado) {
        echo "<script>alert('$erroDuplicado'); window.history.back();</script>";
        exit;
    }

    // Verifica se o farmacia_id existe na tabela farmacia
    $verificaFarmacia = $conn->prepare("SELECT 1 FROM farmacia WHERE farmacia_id = ?");
    $verificaFarmacia->bind_param("i", $farmacia_id);
    $verificaFarmacia->execute();
    $verificaFarmacia->store_result();
    if ($verificaFarmacia->num_rows === 0) {
        echo "<script>alert('Farmácia não encontrada. Faça login novamente.'); window.history.back();</script>";
        exit;
    }
    $verificaFarmacia->close();

    $stmt = $conn->prepare("INSERT INTO remedios (nome_remedio, principio_ativo, dosagem, forma_farmaceutica, quantidade, registro_anvisa, farmacia_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Erro ao preparar INSERT: " . $conn->error);
    }

    $stmt->bind_param("ssssisi", $nome_remedio, $principio_ativo, $dosagem, $forma_farmaceutica, $quantidade, $registro_anvisa, $farmacia_id);

    if ($stmt->execute()) {
        echo "<script>alert('Remédio cadastrado com sucesso!'); window.location.href='cadastro_remedios.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao cadastrar: " . $stmt->error . "'); window.history.back();</script>";
        exit;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medsync - Cadastro de Remédios</title>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link rel="stylesheet" href="../css/cadastrofarmacia.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
     <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        
.tamanho {
    font-size: 14px;
    color: #0a4e53;
    font-weight: 500;
}
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
        input[type="number"] {
            width: 100%;
    padding: 10px;
    margin-top: 10px;
    margin-bottom: 20px;
    border: 1px solid #7e8786;
    border-radius: 5px;
    font-size: 15px;
        }
        input[type="date"] {
            width: 100%;
    padding: 10px;
    margin-top: 10px;
    margin-bottom: 20px;
    border: 1px solid #7e8786;
    border-radius: 5px;
    font-size: 15px;
        }
    </style>
</head>

<body>
<nav class="navbar">
        <div class="logo">
            <a href="painel-farm.php"><img src="../images/logo/logo_branca.png"  ></a>
        </div>
        <ul class="nav-links">
    <a href="/medsync/farmacia/painel-farm.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
    <div class="container">
        <h1>Cadastro de Remédios</h1><br><br>
        <form name="f2" action="" method="post" id="formCadastro">
            <div class="separando">
                <div class="formdadireita">
                    <label class="tamanho" for="nomefantasia">Nome do Remédio:</label>
                    <input type="text" id="nome_remedio" name="nome_remedio" required placeholder="Nome do remédio">

                    <label class="tamanho" for="principio_ativo">Princípio Ativo:</label>
                    <input type="text" id="principio_ativo" name="principio_ativo" required placeholder="Princípio Ativo do remédio">

                    <label class="tamanho" for="dosagem">Dosagem:</label>
                    <input type="text" id="dosagem" name="dosagem" maxlength="50" placeholder="Dosagem do remédio">
                </div>

                <div class="formdadireita">
                <label class="tamanho" for="medicamento">Forma Farmacêutica:</label>
                <input type="text" id="forma_farmaceutica" name="forma_farmaceutica" maxlength="50" placeholder="Forma farmacêutica do remédio">
                    <label class="tamanho" for="quantidade">Quantidade:</label>
                    <input type="number" id="quantidade" name="quantidade" required placeholder="Quantidade recebida" >
                    <label class="tamanho" for="lote">Registro da ANVISA:</label>
                    <input type="number" id="registro_anvisa" name="registro anvisa" required placeholder="Registro da ANVISA" maxlength="20" >
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
