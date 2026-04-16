<?php
session_start();

if (!isset($_SESSION['farmacia_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Consulta SQL ajustada para incluir CPF, nome do médico e status da receita
$sql = "
    SELECT r.receita_id, r.data_emissao, r.validade, r.validade_final, r.observacoes, r.tipo_paciente, r.status_receita,
           COALESCE(pm.pmaior_nome, pme.pmenor_nome) AS paciente_nome,
           COALESCE(pm.pmaior_cpf, pme.pmenor_cpf) AS paciente_cpf,
           m.medicos_nome AS nome_medico
    FROM receita r
    LEFT JOIN paciente_maior pm ON r.paciente_maior_id = pm.pmaior_id
    LEFT JOIN paciente_menor pme ON r.paciente_menor_id = pme.pmenor_id
    LEFT JOIN medicos m ON r.medico_id = m.medicos_id
    ORDER BY r.data_emissao DESC
";

$result = $conn->query($sql);
$receitas = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Receitas | Medsync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <style>
        body {
            background-color: #f4f7fa;
            font-family: 'Segoe UI', sans-serif;
            padding-top: 80px;
        }
        .navbar {
            background-color: #4bb9a5;
            padding: 15px 30px;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar img {
            height: 40px;
        }
        .navbar a {
            color: white;
            text-decoration: none;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 15px 40px;
        }
        .card {
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: none;
        }
        .card h5 {
            margin-bottom: 10px;
            color: #157c5d;
        }
        .card p {
            margin: 5px 0;
        }
        .btn-imprimir, .btn-validar, .btn-desfazer {
            margin-top: 10px;
            margin-right: 5px;
        }
        .filters input, .filters select {
            flex: 1;
            min-width: 200px;
            padding: 0.5rem;
            font-size: 1rem;
            border-radius: 0.375rem;
            border: 1px solid #ced4da;
        }
        #cpfInput { 
            padding: 7px;
            width: 85%;
            display: block;
        }
        .btn-outline-secondary {
            color: #4bb9a5 !important;
            border-color: #4bb9a5 !important;
            width: 15%;
        }
        .btn-outline-secondary:hover {
            background-color: #4bb9a5 !important;
            color: white !important;
        }
        .text-success {
            color: #0a4e53 !important;
        }
        .btn-outline-primary {
            --bs-btn-color: #0a4e53;
            --bs-btn-border-color: #0a4e53;
            --bs-btn-hover-color: #fff;
            --bs-btn-hover-bg: #0a4e53;
            --bs-btn-hover-border-color: #0a4e53;
        }
        .receita-card.vencida {
            opacity: 0.6;
            border-left: 5px solid #dc3545;
        }
        .receita-card.vencida h5 {
            text-decoration: line-through;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <img src="../images/logo/logo_branca.png" alt="Logo" />
    <a href="painel-farm.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>

<div class="container">
    <h2 class="mb-4 text-center text-success">Receitas Emitidas</h2>

    <div class="d-flex justify-content-center align-items-center mb-4" style="gap: 10px;">
        <input type="text" id="cpfInput" class="form-control" placeholder="Pesquisar por CPF do paciente (apenas números)...">
        <button class="btn btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosCollapse">
            <i class="fa-solid fa-filter"></i> Filtros
        </button>
    </div>

    <div class="collapse mb-4" id="filtrosCollapse">
        <div class="card card-body shadow-sm">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="tipoSelect" class="form-label">Tipo de Paciente</label>
                    <select id="tipoSelect" class="form-select">
                        <option value="todos">Todos</option>
                        <option value="maior">Maior</option>
                        <option value="menor">Menor</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="vencidaSelect" class="form-label">Status da Receita</label>
                    <select id="vencidaSelect" class="form-select">
                        <option value="todas">Todas</option>
                        <option value="validas">Válidas</option>
                        <option value="vencidas">Vencidas</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="dataInput" class="form-label">Data de Emissão</label>
                    <input type="date" id="dataInput" class="form-control">
                </div>
            </div>
        </div>
    </div>

    <div id="receitasContainer">
        <?php foreach ($receitas as $receita): ?>
            <?php 
                $classe = ($receita['status_receita'] === 'vencida') ? 'vencida' : '';
            ?>
            <div class="card receita-card <?= $classe ?>"
                data-id="<?= $receita['receita_id'] ?>"
                data-nome="<?= strtolower($receita['paciente_nome']) ?>"
                data-tipo="<?= $receita['tipo_paciente'] ?>"
                data-data="<?= $receita['data_emissao'] ?>"
                data-validade="<?= $receita['validade_final'] ?>"
                data-validade-original="<?= $receita['validade_final'] ?>"
                data-cpf="<?= htmlspecialchars($receita['paciente_cpf']) ?>"
                data-status="<?= $receita['status_receita'] ?>"
            >
                <h5><?= htmlspecialchars($receita['paciente_nome']) ?></h5>
                <p><strong>CPF:</strong> <?= htmlspecialchars($receita['paciente_cpf']) ?></p>
                <p><strong>Médico:</strong> <?= htmlspecialchars($receita['nome_medico']) ?></p>
                <p><strong>Data de Emissão:</strong> <?= date('d/m/Y', strtotime($receita['data_emissao'])) ?></p>
                <p><strong>Validade:</strong> <?= date('d/m/Y', strtotime($receita['validade'])) ?></p>
                <p><strong>Prescrição:</strong><br><?= nl2br(htmlspecialchars($receita['observacoes'])) ?></p>
                <button class="btn btn-outline-primary btn-sm btn-imprimir" onclick="imprimirReceita(this)">Imprimir</button>
                <button class="btn btn-outline-danger btn-sm btn-validar" <?= $receita['status_receita']==='vencida'?'style="display:none;"':'' ?> onclick="alterarStatusReceita(this, 'validar')">Validar</button>
                <button class="btn btn-outline-success btn-sm btn-desfazer" <?= $receita['status_receita']==='valida'?'style="display:none;"':'' ?> onclick="alterarStatusReceita(this, 'desfazer')">Desfazer</button>
            </div>
        <?php endforeach; ?>

        <?php if (count($receitas) === 0): ?>
            <div class="text-center text-muted mt-5">
                <i class="fa-solid fa-prescription-bottle-medical fa-4x mb-3" style="color: #ccc;"></i>
                <p class="fs-5">Nenhuma receita encontrada.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function imprimirReceita(button) {
    const card = button.closest('.card');
    const conteudo = card.innerHTML;
    const janela = window.open('', '', 'width=800,height=600');
    janela.document.write(`<html><head><title>Imprimir Receita</title></head><body style="font-family: Arial; padding: 20px;">${conteudo}</body></html>`);
    janela.document.close();
    janela.print();
}

function alterarStatusReceita(button, acao) {
    const card = button.closest('.card');
    const receitaId = card.getAttribute('data-id');
    const validarBtn = card.querySelector('.btn-validar');
    const desfazerBtn = card.querySelector('.btn-desfazer');

    fetch('validar_receita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${receitaId}&acao=${acao}`
    })
    .then(response => response.text())
    .then(data => {
        if (data === "OK") {
            if (acao === 'validar') {
                card.setAttribute('data-validade', '2000-01-01');
                card.classList.add('vencida');
                validarBtn.style.display = 'none';
                desfazerBtn.style.display = 'inline-block';
            } else {
                const validadeOriginal = card.getAttribute('data-validade-original');
                card.setAttribute('data-validade', validadeOriginal);
                card.classList.remove('vencida');
                validarBtn.style.display = 'inline-block';
                desfazerBtn.style.display = 'none';
            }
            filtrar();
        } else {
            alert("Erro ao atualizar status da receita.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Erro de comunicação com o servidor.");
    });
}

const tipoSelect = document.getElementById('tipoSelect');
const vencidaSelect = document.getElementById('vencidaSelect');
const dataInput = document.getElementById('dataInput');
const cpfInput = document.getElementById('cpfInput');
const cards = document.querySelectorAll('.receita-card');

function filtrar() {
    const cpfFiltro = cpfInput.value.replace(/[^0-9]/g, "").toLowerCase();
    const tipoFiltro = tipoSelect.value;
    const vencidaFiltro = vencidaSelect.value;
    const dataFiltro = dataInput.value;
    const hoje = new Date().toISOString().split('T')[0];

    cards.forEach(card => {
        const cpf = card.getAttribute('data-cpf').replace(/[^0-9]/g, "");
        const tipo = card.getAttribute('data-tipo');
        const validade = card.getAttribute('data-validade');
        const data = card.getAttribute('data-data');

        const condCpf = (!cpfFiltro) || (cpf.includes(cpfFiltro)); 
        const condTipo = (tipoFiltro === 'todos') || (tipo === tipoFiltro);
        const condData = (!dataFiltro) || (data === dataFiltro);

        let condVencida = true;
        if (vencidaFiltro === 'validas') condVencida = validade >= hoje;
        if (vencidaFiltro === 'vencidas') condVencida = validade < hoje;

        card.style.display = (condCpf && condTipo && condData && condVencida) ? '' : 'none';
    });
}

[tipoSelect, vencidaSelect, dataInput, cpfInput].forEach(el => {
    el.addEventListener('input', filtrar);
    el.addEventListener('change', filtrar);
});
filtrar();
</script>
</body>
</html>
