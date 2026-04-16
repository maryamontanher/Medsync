<?php
session_start();

if (!isset($_SESSION['farmacia_id'])) {
    die("Erro: Farmácia não autenticada.");
}

$farmacia_id = intval($_SESSION['farmacia_id']);

$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";

$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}


$sql_remedios = "SELECT id_remedio, nome_remedio, principio_ativo, dosagem, forma_farmaceutica, quantidade, registro_anvisa
                 FROM remedios
                 WHERE farmacia_id = ?
                 ORDER BY nome_remedio";

$stmt = $conn->prepare($sql_remedios);
$stmt->bind_param("i", $farmacia_id);
$stmt->execute();
$result_remedios = $stmt->get_result();


if (isset($_POST['acao_remedio']) && $_POST['acao_remedio'] === 'deletar' && isset($_POST['id_remedio'])) {

    $id_remedio = intval($_POST['id_remedio']);

    // Delete apenas se o remédio pertence à farmácia
    $stmt = $conn->prepare("DELETE FROM remedios WHERE id_remedio = ? AND farmacia_id = ?");
    $stmt->bind_param("ii", $id_remedio, $farmacia_id);

    if ($stmt->execute()) {
        echo "<script>alert('Remédio deletado com sucesso!'); window.location.href='tabela_remedios.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao deletar remédio.'); window.location.href='tabela_remedios.php';</script>";
        exit;
    }
}


if (isset($_POST['acao_remedio']) && $_POST['acao_remedio'] === 'editar' && isset($_POST['id_remedio'])) {

    $id_remedio = intval($_POST['id_remedio']);
    $nome_remedio = $_POST['nome_remedio'] ?? '';
    $principio_ativo = $_POST['principio_ativo'] ?? '';
    $dosagem = $_POST['dosagem'] ?? '';
    $forma_farmaceutica = $_POST['forma_farmaceutica'] ?? '';
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $registro_anvisa = $_POST['registro_anvisa'] ?? '';

    
    $stmt = $conn->prepare("UPDATE remedios 
                            SET nome_remedio=?, principio_ativo=?, dosagem=?, forma_farmaceutica=?, quantidade=?, registro_anvisa=?
                            WHERE id_remedio=? AND farmacia_id=?");

    $stmt->bind_param("ssssisis", 
        $nome_remedio, 
        $principio_ativo, 
        $dosagem, 
        $forma_farmaceutica, 
        $quantidade, 
        $registro_anvisa, 
        $id_remedio,
        $farmacia_id
    );

    if ($stmt->execute()) {
        echo "<script>alert('Remédio editado com sucesso!'); window.location.href='tabela_remedios.php';</script>";
        exit;
    } else {
        echo "<script>alert('Erro ao editar remédio.'); window.location.href='tabela_remedios.php';</script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabela de Usuarios Pacientes</title>
     <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        html {
            scroll-behavior: smooth;
        }

        * {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            background-image: url('../images/bglogin.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            margin: 0 !important;
            padding: 0 !important;
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

        .table-container {
            margin: 100px auto 0 auto;
            width: 90%;
            max-width: 100vw;
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px 8px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.18), 0 1.5px 6px rgba(0,0,0,0.10);
        }

        table {
            width: 100%;
            min-width: 900px;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #4bb9a5;
            padding: 8px 10px;
            text-align: left;
        }

        th {
            background: #4bb9a5;
            color: #fff;
        }

        tr:nth-child(even) {
            background: #f2f2f2;
        }

        h2 {
            color: rgb(50, 97, 81);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 18px;
            text-align: center;
        }

        @media (max-width: 1100px) {
            .table-container {
                width: 100vw;
                padding: 10px 2px;
            }

            th,
            td {
                font-size: 0.97rem;
                padding: 7px 5px;
            }
        }

        @media (max-width: 900px) {
            .navbar {
                height: 60px;
                padding: 0 5px;
            }

            .logo img {
                width: 90px;
            }

            .table-container {
                margin-top: 70px;
                padding: 2px 0;
            }

            h2 {
                font-size: 1.1rem;
                color: rgb(50, 97, 81);
            }
        }

        @media (max-width: 700px) {
            .table-container {
                margin-top: 60px;
            }

            th,
            td {
                font-size: 0.93rem;
            }
        }

        @media (max-width: 600px) {
            .navbar {
                height: 56px;
                padding: 0 2px;
            }

            .logo img {
                width: 70px;
            }

            .table-container {
                margin-top: 60px;
                padding: 1px 0;
            }

            h2 {
                font-size: 1rem;
                color: rgb(50, 97, 81);
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                border-radius: 8px;
                box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
            }

            td {
                border: none;
                border-bottom: 1px solid #4bb9a5;
                position: relative;
                padding-left: 50%;
                min-height: 40px;
                font-size: 0.95rem;
            }

            td:before {
                position: absolute;
                top: 10px;
                left: 10px;
                width: 45%;
                white-space: nowrap;
                font-weight: bold;
                color: #4bb9a5;
                content: attr(data-label);
            }

            td:last-child {
                padding-bottom: 20px;
            }

            input,
            select,
            button {
                font-size: 0.95rem;
                width: 95%;
                min-width: 0;
            }
        }

        .input-table {
            width: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            font-size: 1rem;
            border-radius: 3px;
            border: 1px solid #b2dfdb;
            background: #f8f8f8;
        }

        .input-readonly {
            background: #eee;
            color: #888;
        }

        .input-uf {
            width: 50px;
            min-width: 0;
        }

        .td-actions {
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            gap: 5px;
            align-items: stretch;
            justify-content: center;
            height: 100%;
            min-width: 120px;
        }

        .acoes-titulo {
            display: block;
            font-size: 0.95rem;
            font-weight: bold;
            color: #4bb9a5;
            margin-bottom: 2px;
            text-align: center;
            letter-spacing: 0.5px;
            width: 100%;
        }

        .btn-editar,
        .btn-deletar {
            width: 100%;
            box-sizing: border-box;
        }

        .btn-editar {
            background: #4bb9a5;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-editar:hover {
            background: #388e7c;
        }

        .btn-deletar {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-deletar:hover {
            background: #c0392b;
        }

        @media (max-width: 600px) {
            .input-table {
                font-size: 0.95rem;
                width: 95%;
            }

            .td-actions {
                flex-direction: column;
                gap: 2px;
                min-width: 0;
            }

            .acoes-titulo {
                font-size: 0.93rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <a href="painel-farm.php"><img src="../images/logo/logo_branca.png" alt="Logo da plataforma" ></a>
        </div>
        <ul class="nav-links">
          <a href="painel-farm.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
    <div class="table-container">
        <h2>Estoque de remédios</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Princípio ativo</th>
                    <th>Dosagem</th>
                    <th>Forma farmacêutica</th>
                    <th>Quantidade</th>
                    <th>Registro da Anvisa</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result_remedios && $result_remedios->num_rows > 0): ?>
                    <?php while ($row = $result_remedios->fetch_assoc()): ?>
                        <tr>
                            <form method="post" action="">
                                <td><input type="text" name="id_remedio" value="<?= htmlspecialchars($row['id_remedio']) ?>" readonly class="input-table input-readonly" style="width:60px"></td>
                                <td><input type="text" name="nome_remedio" value="<?= htmlspecialchars($row['nome_remedio']) ?>" required class="input-table"></td>
                                <td><input type="text" name="principio_ativo" value="<?= htmlspecialchars($row['principio_ativo']) ?>" required class="input-table"></td>
                                <td><input type="text" name="dosagem" value="<?= htmlspecialchars($row['dosagem']) ?>" class="input-table"></td>
                                <td><input type="text" name="forma_farmaceutica" value="<?= htmlspecialchars($row['forma_farmaceutica']) ?>" class="input-table"></td>
                                <td><input type="number" name="quantidade" value="<?= htmlspecialchars($row['quantidade']) ?>" required class="input-table" min="0"></td>
                                <td><input type="text" name="registro_anvisa" value="<?= htmlspecialchars($row['registro_anvisa']) ?>" class="input-table"></td>
                                <td class="td-actions">
                                    <span class="acoes-titulo">Ações</span>
                                    <button type="submit" name="acao_remedio" value="editar" class="btn-editar">Editar</button>
                                    <button type="submit" name="acao_remedio" value="deletar" onclick="return confirm('Tem certeza que deseja deletar este remédio?')" class="btn-deletar">Deletar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Nenhum remédio cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
        function confirmarSaida(event) {
            event.preventDefault();
            if (confirm("Deseja realmente sair da conta?")) {
                window.location.href = "sair.php";
            }
        }
    </script>
</body>

</html>
<?php $conn->close(); ?>