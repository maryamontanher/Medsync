<?php
$servidor = "localhost";
$usuario = "root";
$senha = "";
$dbname = "medsync";
$conn = mysqli_connect($servidor, $usuario, $senha, $dbname);
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}


if (isset($_POST['acao']) && isset($_POST['edit_crm'])) {
    $crm = preg_replace('/\D/', '', $_POST['edit_crm']);
    if ($_POST['acao'] === 'editar') {
        $nome = $_POST['nome'];
        $cpf = preg_replace('/\D/', '', $_POST['cpf']);
        $endereco = $_POST['endereco'];
        $telefone = preg_replace('/\D/', '', $_POST['telefone']);
        $email = $_POST['email'];
        $especialidade = $_POST['especialidade'];
        $ufcrm = $_POST['ufcrm'];
        $sexo = $_POST['sexo'];
        $data_nascimento = $_POST['data_nascimento'];
        $stmt = $conn->prepare("UPDATE medicos SET medicos_nome=?, medicos_cpf=?, medicos_endereco=?, medicos_telefone=?, medicos_email=?, medicos_especialidade=?, medicos_uf_crm=?, medicos_sexo=?, medicos_datanasc=? WHERE medicos_crm=?");
        $stmt->bind_param("ssssssssss", $nome, $cpf, $endereco, $telefone, $email, $especialidade, $ufcrm, $sexo, $data_nascimento, $crm);
        if ($stmt->execute()) {
            echo "<script>alert('Médico editado com sucesso!'); window.location.href='tabela_med.php';</script>";
            exit;
        } else {
            echo "<script>alert('Erro ao editar médico.'); window.location.href='tabela_med.php';</script>";
            exit;
        }
    } elseif ($_POST['acao'] === 'deletar') {
        $stmt = $conn->prepare("DELETE FROM medicos WHERE medicos_crm = ?");
        $stmt->bind_param("s", $crm);
        if ($stmt->execute()) {
            echo "<script>alert('Médico deletado com sucesso!'); window.location.href='tabela_med.php';</script>";
            exit;
        } else {
            echo "<script>alert('Erro ao deletar médico.'); window.location.href='tabela_med.php';</script>";
            exit;
        }
    }
}

$sql = "SELECT medicos_nome, medicos_cpf, medicos_endereco, medicos_telefone, medicos_email, medicos_especialidade, medicos_crm, medicos_uf_crm, medicos_sexo, medicos_datanasc FROM medicos ORDER BY medicos_nome";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabela de Usuarios Medicos</title>
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
            width: 100%;
            max-width: 100vw;
            overflow-x: auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
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
            <img src="../images/logo/logo_branca.png" alt="Logo da plataforma">
        </div>
        <ul class="nav-links">
        <a href="adm-clinica.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
        </ul>
    </nav>
    <div class="table-container">
        <h2>Lista de Médicos Cadastrados</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>CPF</th>
                    <th>Endereço</th>
                    <th>Telefone</th>
                    <th>E-mail</th>
                    <th>Especialidade</th>
                    <th>CRM</th>
                    <th>UF CRM</th>
                    <th>Sexo</th>
                    <th>Data de Nascimento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <form method="post" action="tabela_med.php">
                                <td><input type="text" name="nome" value="<?= htmlspecialchars($row['medicos_nome']) ?>"
                                        required class="input-table"></td>
                                <td><input type="text" name="cpf" value="<?= htmlspecialchars($row['medicos_cpf']) ?>" required
                                        maxlength="11" class="input-table"></td>
                                <td><input type="text" name="endereco" value="<?= htmlspecialchars($row['medicos_endereco']) ?>"
                                        required class="input-table"></td>
                                <td><input type="text" name="telefone" value="<?= htmlspecialchars($row['medicos_telefone']) ?>"
                                        required maxlength="11" class="input-table"></td>
                                <td><input type="email" name="email" value="<?= htmlspecialchars($row['medicos_email']) ?>"
                                        required class="input-table"></td>
                                <td><input type="text" name="especialidade"
                                        value="<?= htmlspecialchars($row['medicos_especialidade']) ?>" required
                                        class="input-table"></td>
                                <td><input type="text" name="edit_crm" value="<?= htmlspecialchars($row['medicos_crm']) ?>"
                                        required maxlength="15" class="input-table"></td>

                                <td>
                                    <select name="uf" required class="input-table input-uf">
                                        <?php
                                        $ufs = [
                                            'AC',
                                            'AL',
                                            'AP',
                                            'AM',
                                            'BA',
                                            'CE',
                                            'DF',
                                            'ES',
                                            'GO',
                                            'MA',
                                            'MT',
                                            'MS',
                                            'MG',
                                            'PA',
                                            'PB',
                                            'PR',
                                            'PE',
                                            'PI',
                                            'RJ',
                                            'RN',
                                            'RS',
                                            'RO',
                                            'RR',
                                            'SC',
                                            'SP',
                                            'SE',
                                            'TO'
                                        ];
                                        foreach ($ufs as $uf) {
                                            $selected = ($row['farmacia_uf'] === $uf) ? 'selected' : '';
                                            echo "<option value='$uf' $selected>$uf</option>";
                                        }
                                        ?>
                                    </select>

                                </td>
                                <td>
                                    <select name="sexo" required class="input-table">
                                        <option value="Masculino" <?= $row['medicos_sexo'] === 'Masculino' ? 'selected' : '' ?>>
                                            Masculino</option>
                                        <option value="Feminino" <?= $row['medicos_sexo'] === 'Feminino' ? 'selected' : '' ?>>
                                            Feminino
                                        </option>
                                        <option value="Outro" <?= $row['medicos_sexo'] === 'Outro' ? 'selected' : '' ?>>Outro
                                        </option>
                                    </select>
                                </td>
                                <td><input type="date" name="data_nascimento"
                                        value="<?= htmlspecialchars($row['medicos_datanasc']) ?>" required class="input-table">
                                </td>
                                <td class="td-actions">
                                    <span class="acoes-titulo">Ações</span>
                                    <button type="submit" name="acao" value="editar" class="btn-editar">Editar</button>
                                    <button type="submit" name="acao" value="deletar"
                                        onclick="return confirm('Tem certeza que deseja deletar este médico?')"
                                        class="btn-deletar">Deletar</button>
                                </td>
                            </form>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="11">Nenhum médico cadastrado.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
   
</body>

</html>
<?php $conn->close(); ?>