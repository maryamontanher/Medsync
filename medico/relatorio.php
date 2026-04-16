<?php
session_start();

if (!isset($_SESSION['medico_id'])) {
    header("Location: login.php");
    exit();
}

$medico_id = $_SESSION['medico_id'];
$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Buscar dados do médico - CORREÇÃO AQUI
$medico_query = $conn->query("SELECT medicos_nome, medicos_especialidade FROM medicos WHERE medicos_id = $medico_id");
$medico = $medico_query->fetch_assoc(); // CORREÇÃO: era $medino_query

// Período padrão: últimos 30 dias
$inicio = $_GET['inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$fim = $_GET['fim'] ?? date('Y-m-d');

// Função auxiliar para contar
function contar($conn, $query) {
    $res = $conn->query($query);
    return $res ? ($res->fetch_assoc()['total'] ?? 0) : 0;
}

// CONSULTAS
$qtd_consultas_maior = contar($conn, "SELECT COUNT(*) AS total FROM consultas_maior WHERE medico_id = $medico_id AND status IN ('Realizada','Finalizada') AND data_consulta BETWEEN '$inicio' AND '$fim'");
$qtd_consultas_menor = contar($conn, "SELECT COUNT(*) AS total FROM consultas_menor WHERE medico_id = $medico_id AND status IN ('Realizada','Finalizada') AND data_consulta BETWEEN '$inicio' AND '$fim'");
$total_consultas = $qtd_consultas_maior + $qtd_consultas_menor;

// DOCUMENTOS
$qtd_atestados = contar($conn, "SELECT COUNT(*) AS total FROM atestado WHERE medico_id = $medico_id AND data_emissao BETWEEN '$inicio' AND '$fim'");
$qtd_receitas = contar($conn, "SELECT COUNT(*) AS total FROM receita WHERE medico_id = $medico_id AND data_emissao BETWEEN '$inicio' AND '$fim'");

// PACIENTES
$qtd_pacientes_maior = contar($conn, "SELECT COUNT(DISTINCT paciente_maior_id) AS total FROM consultas_maior WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'");
$qtd_pacientes_menor = contar($conn, "SELECT COUNT(DISTINCT paciente_menor_id) AS total FROM consultas_menor WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'");
$total_pacientes = $qtd_pacientes_maior + $qtd_pacientes_menor;

// ANAMNESES
$qtd_anamneses = contar($conn, "SELECT COUNT(*) AS total FROM anamnese WHERE medico_id = $medico_id AND data_registro BETWEEN '$inicio' AND '$fim'");

// EVOLUÇÃO MENSAL
$evolucao_mensal = [];
$meses_query = $conn->query("
    SELECT DATE_FORMAT(data_consulta, '%Y-%m') AS mes, COUNT(*) AS total
    FROM (
        SELECT data_consulta FROM consultas_maior WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
        UNION ALL
        SELECT data_consulta FROM consultas_menor WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
    ) AS consultas
    GROUP BY mes
    ORDER BY mes
");
if ($meses_query) {
    while($row = $meses_query->fetch_assoc()) {
        $evolucao_mensal[] = $row;
    }
}

// PACIENTES MAIS FREQUENTES
$pacientes_frequentes = $conn->query("
    SELECT p.pmaior_nome AS nome, COUNT(*) AS consultas
    FROM consultas_maior c
    JOIN paciente_maior p ON c.paciente_maior_id = p.pmaior_id
    WHERE c.medico_id = $medico_id AND c.data_consulta BETWEEN '$inicio' AND '$fim'
    GROUP BY c.paciente_maior_id
    UNION ALL
    SELECT p.pmenor_nome AS nome, COUNT(*) AS consultas
    FROM consultas_menor c
    JOIN paciente_menor p ON c.paciente_menor_id = p.pmenor_id
    WHERE c.medico_id = $medico_id AND c.data_consulta BETWEEN '$inicio' AND '$fim'
    GROUP BY c.paciente_menor_id
    ORDER BY consultas DESC
    LIMIT 10
");

// HORÁRIOS MAIS UTILIZADOS
$horarios_query = $conn->query("
    SELECT HOUR(hora_consulta) AS hora, COUNT(*) AS total
    FROM (
        SELECT hora_consulta FROM consultas_maior WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
        UNION ALL
        SELECT hora_consulta FROM consultas_menor WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
    ) AS consultas
    GROUP BY hora
    ORDER BY hora
");
$horarios = [];
if ($horarios_query) {
    while($row = $horarios_query->fetch_assoc()) {
        $horarios[] = $row;
    }
}
// STATUS DAS CONSULTAS - CORRIGIDO
$status_consultas_query = $conn->query("
    SELECT status, COUNT(*) AS total
    FROM (
        SELECT status FROM consultas_maior WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
        UNION ALL
        SELECT status FROM consultas_menor WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
    ) AS consultas
    GROUP BY status
");
$status_consultas = [];
if ($status_consultas_query) {
    while($row = $status_consultas_query->fetch_assoc()) {
        $status_consultas[] = $row;
    }
}

// Preparar dados para o gráfico de status
$status_labels = [];
$status_data = [];
$status_cores = [
    'Agendada' => '#0a4e53',
    'Finalizada' => '#4bb9a5',
    'Cancelada' => '#dc3545'
];

foreach ($status_consultas as $status) {
    $status_labels[] = $status['status'];
    $status_data[] = $status['total'];
}
// DOCUMENTOS RECENTES
$documentos_recentes = $conn->query("
    SELECT 'Atestado' AS tipo, data_emissao AS data, descricao AS texto, dias_afastamento AS info
    FROM atestado
    WHERE medico_id = $medico_id AND data_emissao BETWEEN '$inicio' AND '$fim'
    UNION ALL
    SELECT 'Receita' AS tipo, data_emissao AS data, observacoes AS texto, validade AS info
    FROM receita
    WHERE medico_id = $medico_id AND data_emissao BETWEEN '$inicio' AND '$fim'
    ORDER BY data DESC
    LIMIT 10
");
// DISTRIBUIÇÃO POR FAIXA ETÁRIA - CORRIGIDO
$faixa_etaria_query = $conn->query("
    SELECT 
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, data_nasc, CURDATE()) BETWEEN 0 AND 12 THEN '0-12 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nasc, CURDATE()) BETWEEN 13 AND 17 THEN '13-17 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nasc, CURDATE()) BETWEEN 18 AND 30 THEN '18-30 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nasc, CURDATE()) BETWEEN 31 AND 45 THEN '31-45 anos'
            WHEN TIMESTAMPDIFF(YEAR, data_nasc, CURDATE()) BETWEEN 46 AND 60 THEN '46-60 anos'
            ELSE '60+ anos'
        END AS faixa_etaria,
        COUNT(*) AS total
    FROM (
        SELECT pmaior_datanasc AS data_nasc 
        FROM paciente_maior pm
        INNER JOIN consultas_maior cm ON pm.pmaior_id = cm.paciente_maior_id
        WHERE cm.medico_id = $medico_id AND cm.data_consulta BETWEEN '$inicio' AND '$fim'
        
        UNION ALL
        
        SELECT pmenor_datanasc AS data_nasc 
        FROM paciente_menor pmen
        INNER JOIN consultas_menor cmen ON pmen.pmenor_id = cmen.paciente_menor_id
        WHERE cmen.medico_id = $medico_id AND cmen.data_consulta BETWEEN '$inicio' AND '$fim'
    ) AS pacientes
    GROUP BY faixa_etaria
    ORDER BY 
        CASE faixa_etaria
            WHEN '0-12 anos' THEN 1
            WHEN '13-17 anos' THEN 2
            WHEN '18-30 anos' THEN 3
            WHEN '31-45 anos' THEN 4
            WHEN '46-60 anos' THEN 5
            ELSE 6
        END
");

$faixa_etaria = [];
if ($faixa_etaria_query) {
    while($row = $faixa_etaria_query->fetch_assoc()) {
        $faixa_etaria[] = $row;
    }
}

// Se não houver dados, criar estrutura vazia para o gráfico
$faixa_labels = ['0-12 anos', '13-17 anos', '18-30 anos', '31-45 anos', '46-60 anos', '60+ anos'];
$faixa_data = [0, 0, 0, 0, 0, 0];

if (!empty($faixa_etaria)) {
    foreach ($faixa_etaria as $faixa) {
        $index = array_search($faixa['faixa_etaria'], $faixa_labels);
        if ($index !== false) {
            $faixa_data[$index] = $faixa['total'];
        }
    }
}
// PRODUTIVIDADE POR DIA DA SEMANA - CORRIGIDO
$dias_semana_query = $conn->query("
    SELECT 
        DAYNAME(data_consulta) AS dia_semana,
        DAYOFWEEK(data_consulta) AS dia_numero,
        COUNT(*) AS total
    FROM (
        SELECT data_consulta FROM consultas_maior 
        WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
        UNION ALL
        SELECT data_consulta FROM consultas_menor 
        WHERE medico_id = $medico_id AND data_consulta BETWEEN '$inicio' AND '$fim'
    ) AS consultas
    GROUP BY dia_semana, dia_numero
    ORDER BY dia_numero
");

$dias_semana = [];
$dias_semana_labels = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];
$dias_semana_data = [0, 0, 0, 0, 0, 0, 0];

if ($dias_semana_query) {
    while($row = $dias_semana_query->fetch_assoc()) {
        $dias_semana[] = $row;
        
        // Mapear para o array correto (MySQL retorna em inglês)
        $dia_index = $row['dia_numero'] - 1; // DAYOFWEEK retorna 1=Domingo, 2=Segunda, etc.
        if ($dia_index >= 0 && $dia_index < 7) {
            $dias_semana_data[$dia_index] = $row['total'];
        }
    }
}

// TENDÊNCIA MENSAL - CORRIGIDO (últimos 6 meses)
$tendencia_mensal_query = $conn->query("
    SELECT 
        DATE_FORMAT(data_consulta, '%Y-%m') AS mes,
        DATE_FORMAT(data_consulta, '%b/%Y') AS mes_formatado,
        COUNT(*) AS total
    FROM (
        SELECT data_consulta FROM consultas_maior 
        WHERE medico_id = $medico_id 
        AND data_consulta BETWEEN DATE_SUB('$fim', INTERVAL 6 MONTH) AND '$fim'
        UNION ALL
        SELECT data_consulta FROM consultas_menor 
        WHERE medico_id = $medico_id 
        AND data_consulta BETWEEN DATE_SUB('$fim', INTERVAL 6 MONTH) AND '$fim'
    ) AS consultas
    GROUP BY mes, mes_formatado
    ORDER BY mes
");

$tendencia_mensal = [];
$tendencia_labels = [];
$tendencia_data = [];

if ($tendencia_mensal_query) {
    while($row = $tendencia_mensal_query->fetch_assoc()) {
        $tendencia_mensal[] = $row;
        $tendencia_labels[] = $row['mes_formatado'];
        $tendencia_data[] = $row['total'];
    }
}

// Se não houver dados de tendência, criar últimos 6 meses vazios
if (empty($tendencia_mensal)) {
    for ($i = 5; $i >= 0; $i--) {
        $mes = date('M/Y', strtotime("-$i months"));
        $tendencia_labels[] = $mes;
        $tendencia_data[] = 0;
    }
}
// Dados para gráficos (com fallback)
$evolucao_labels = $evolucao_mensal ? json_encode(array_column($evolucao_mensal, 'mes')) : '[]';
$evolucao_data = $evolucao_mensal ? json_encode(array_column($evolucao_mensal, 'total')) : '[]';
$horarios_labels = $horarios ? json_encode(array_column($horarios, 'hora')) : '[]';
$horarios_data = $horarios ? json_encode(array_column($horarios, 'total')) : '[]';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatórios | Medsync</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
<link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
<style>
:root {
    --primary-color: #0a4e53;
    --secondary-color: #4bb9a5;
    --accent-color: #157c5d;
    --light-bg: #f8f9fa;
    --card-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

body { 
    background: linear-gradient(135deg, #f4f7fa 0%, #e8f4f1 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    padding-top: 80px;
    min-height: 100vh;
}

.navbar { 
    background: #4bb9a5;
    padding: 15px 30px;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 1030;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.navbar img { height: 40px; }
.navbar a { color: white; text-decoration: none; transition: opacity 0.3s; }
.navbar a:hover { opacity: 0.8; }

.container { max-width: 1400px; margin: 0 auto; padding: 20px; }

/* Cards modernos */
.dashboard-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: var(--card-shadow);
    border: none;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.card-title {
    color: var(--primary-color);
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.card-value {
    color: var(--primary-color);
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.card-change {
    font-size: 0.85rem;
    font-weight: 500;
}

.card-change.positive { color: #28a745; }
.card-change.negative { color: #dc3545; }

.icon-circle {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Filtros */
.filter-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
}

.btn-primary {
    background: #4bb9a5;
    border: none;
    padding: 0.5rem 1.5rem;
    font-weight: 600;
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
    transform: translateY(-1px);
}

.btn-outline-secondary {
    border-color: var(--secondary-color);
    color: var(--secondary-color);
}

.btn-outline-secondary:hover {
    background: var(--secondary-color);
    border-color: var(--secondary-color);
    color: white;
}
.bg-warning {
    --bs-bg-opacity: 1;
    background-color: #0a4e53!important;
}
.bg-info {
    --bs-bg-opacity: 1;
    background-color: #4bb9a5!important;
}
/* Tabs */
.nav-tabs {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.nav-tabs .nav-link {
    color: #6c757d;
    font-weight: 500;
    border: none;
    padding: 0.75rem 1.5rem;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    color: var(--primary-color);
    background: none;
    border-bottom: 3px solid var(--primary-color);
    font-weight: 600;
}

.nav-tabs .nav-link:hover {
    color: var(--primary-color);
    border: none;
}

/* Charts */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.mini-chart {
    height: 200px;
}


/* Table */
.table-modern {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.table-modern th {
    background: var(--light-bg);
    color: var(--primary-color);
    font-weight: 600;
    border: none;
    padding: 1rem;
}

.table-modern td {
    padding: 1rem;
    border-color: #f1f3f4;
    vertical-align: middle;
}

/* Stats grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

/* Progress bars */
.progress {
    background-color: #e9ecef;
    border-radius: 4px;
}

.progress-bar {
    background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%);
}

/* Responsive */
@media (max-width: 768px) {
    .container { padding: 10px; }
    .stats-grid { grid-template-columns: 1fr; }
    .card-value { font-size: 1.5rem; }
    .nav-tabs .nav-link { padding: 0.5rem 1rem; font-size: 0.9rem; }
}
</style>
</head>
<body>

<nav class="navbar">
    <img src="../images/logo/logo_branca.png" alt="Logo" />
    <a href="painelmed.php"><i class="fa-solid fa-arrow-left me-2"></i>Voltar ao painel</a>
</nav>

<div class="container">
    <!-- Cabeçalho -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1" style="color: var(--primary-color);">Relatórios de Desempenho</h1>
                    <p class="text-muted mb-0">
                        <?php if($medico): ?>
                            Dr. <?php echo htmlspecialchars($medico['medicos_nome']); ?> • <?php echo htmlspecialchars($medico['medicos_especialidade']); ?>
                        <?php else: ?>
                            Médico • Especialidade
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-section">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label fw-semibold">Data Inicial</label>
                <input type="date" name="inicio" value="<?= $inicio ?>" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold">Data Final</label>
                <input type="date" name="fim" value="<?= $fim ?>" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-filter me-2"></i>Filtrar
                </button>
            </div>
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="setPeriod('today')">Hoje</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setPeriod('week')">7 Dias</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="setPeriod('month')">30 Dias</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Métricas Principais -->
    <div class="stats-grid">
        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-title">Consultas Realizadas</div>
                    <div class="card-value"><?= $total_consultas ?></div>
                    <div class="card-change positive">
                        <i class="fa-solid fa-arrow-up me-1"></i>Análise do período
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-title">Pacientes Atendidos</div>
                    <div class="card-value"><?= $total_pacientes ?></div>
                    <div class="card-change positive">
                        <i class="fa-solid fa-arrow-up me-1"></i>Análise do período
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-title">Documentos Emitidos</div>
                    <div class="card-value"><?= $qtd_receitas + $qtd_atestados ?></div>
                    <div class="card-change positive">
                        <i class="fa-solid fa-arrow-up me-1"></i>Análise do período
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="card-title">Anamneses Realizadas</div>
                    <div class="card-value"><?= $qtd_anamneses ?></div>
                    <div class="card-change positive">
                        <i class="fa-solid fa-arrow-up me-1"></i>Análise do período
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs de Conteúdo -->
    <ul class="nav nav-tabs" id="reportsTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button">
                <i class="fa-solid fa-chart-pie me-2"></i>Visão Geral
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="patients-tab" data-bs-toggle="tab" data-bs-target="#patients" type="button">
                <i class="fa-solid fa-user-group me-2"></i>Pacientes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button">
                <i class="fa-solid fa-file-lines me-2"></i>Documentos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button">
                <i class="fa-solid fa-chart-line me-2"></i>Análises
            </button>
        </li>
    </ul>

    <div class="tab-content" id="reportsTabContent">
        <!-- ABA 1: VISÃO GERAL -->
        <div class="tab-pane fade show active" id="overview" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Evolução de Consultas</h5>
                        <div class="chart-container">
                            <canvas id="evolutionChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Distribuição por Tipo</h5>
                        <div class="chart-container">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Status das Consultas</h5>
                        <div class="chart-container mini-chart">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Horários Mais Utilizados</h5>
                        <div class="chart-container mini-chart">
                            <canvas id="hoursChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 2: PACIENTES -->
        <div class="tab-pane fade" id="patients" role="tabpanel">
            <div class="row">
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Pacientes Mais Frequentes</h5>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Paciente</th>
                                        <th>Consultas</th>
                                        <th>Frequência</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($pacientes_frequentes && $pacientes_frequentes->num_rows > 0): ?>
                                        <?php while($paciente = $pacientes_frequentes->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($paciente['nome']) ?></td>
                                            <td><?= $paciente['consultas'] ?></td>
                                            <td>
                                                <div class="progress" style="height: 8px;">
                                                    <div class="progress-bar" style="width: <?= ($paciente['consultas'] / max(1, $total_consultas)) * 100 ?>%"></div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="3" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-users fa-2x mb-2 d-block"></i>
                                                Nenhum paciente encontrado no período
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Distribuição por Faixa Etária</h5>
                        <div class="chart-container">
                            <canvas id="ageChart"></canvas>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 3: DOCUMENTOS -->
        <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="row">
                <div class="col-lg-8">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Documentos Recentes</h5>
                        <div class="table-responsive">
                            <table class="table table-modern">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Data</th>
                                        <th>Descrição</th>
                                        <th>Informações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($documentos_recentes && $documentos_recentes->num_rows > 0): ?>
                                        <?php while($doc = $documentos_recentes->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?= $doc['tipo'] == 'Atestado' ? 'bg-warning' : 'bg-info' ?>">
                                                    <?= $doc['tipo'] ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($doc['data'])) ?></td>
                                            <td class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($doc['texto']) ?>">
                                                <?= htmlspecialchars(substr($doc['texto'], 0, 50)) ?>...
                                            </td>
                                            <td>
                                                <?php if($doc['tipo'] == 'Atestado'): ?>
                                                    <?= $doc['info'] ?> dias
                                                <?php else: ?>
                                                    Válido até <?= date('d/m/Y', strtotime($doc['info'])) ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="fa-solid fa-file-lines fa-2x mb-2 d-block"></i>
                                                Nenhum documento encontrado no período
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Distribuição de Documentos</h5>
                        <div class="chart-container">
                            <canvas id="documentsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ABA 4: ANÁLISES -->
        <div class="tab-pane fade" id="analytics" role="tabpanel">
            <div class="row">
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Produtividade por Dia da Semana</h5>
                        <div class="chart-container">
                            <canvas id="weekdayChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Tendência Mensal</h5>
                        <div class="chart-container">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="dashboard-card">
                        <h5 class="mb-3">Métricas de Desempenho</h5>
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-primary mb-1"><?= $total_consultas ?></div>
                                    <small class="text-muted">Consultas/Mês</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-success mb-1"><?= round($total_consultas / max(1, $total_pacientes), 1) ?></div>
                                    <small class="text-muted">Consultas por Paciente</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-warning mb-1"><?= $qtd_receitas + $qtd_atestados ?></div>
                                    <small class="text-muted">Documentos/Mês</small>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3">
                                    <div class="h4 text-info mb-1"><?= $qtd_anamneses ?></div>
                                    <small class="text-muted">Anamneses/Mês</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Função para definir períodos rapidamente
function setPeriod(period) {
    const today = new Date();
    let startDate = new Date();
    
    switch(period) {
        case 'today':
            startDate = today;
            break;
        case 'week':
            startDate.setDate(today.getDate() - 7);
            break;
        case 'month':
            startDate.setDate(today.getDate() - 30);
            break;
    }
    
    document.querySelector('input[name="inicio"]').value = startDate.toISOString().split('T')[0];
    document.querySelector('input[name="fim"]').value = today.toISOString().split('T')[0];
    document.querySelector('form').submit();
}

// Gráficos
document.addEventListener('DOMContentLoaded', function() {
    // Evolução de Consultas
    const evolutionCtx = document.getElementById('evolutionChart');
    if (evolutionCtx) {
        new Chart(evolutionCtx, {
            type: 'line',
            data: {
                labels: <?= $evolucao_labels ?>,
                datasets: [{
                    label: 'Consultas',
                    data: <?= $evolucao_data ?>,
                    borderColor: '#0a4e53',
                    backgroundColor: 'rgba(10, 78, 83, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
    // Gráfico de Status das Consultas
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    data: <?= json_encode($status_data) ?>,
                    backgroundColor: [
                        '#0a4e53', // Agendada
                        '#4bb9a5', // Finalizada/Realizada
                        '#dc3545'  // Cancelada
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom',
                        labels: {
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
    // Distribuição por Tipo
    const distributionCtx = document.getElementById('distributionChart');
    if (distributionCtx) {
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Maiores de Idade', 'Menores de Idade'],
                datasets: [{
                    data: [<?= $qtd_pacientes_maior ?>, <?= $qtd_pacientes_menor ?>],
                    backgroundColor: ['#0a4e53', '#4bb9a5']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Distribuição de Documentos
    const documentsCtx = document.getElementById('documentsChart');
    if (documentsCtx) {
        new Chart(documentsCtx, {
            type: 'pie',
            data: {
                labels: ['Receitas', 'Atestados'],
                datasets: [{
                    data: [<?= $qtd_receitas ?>, <?= $qtd_atestados ?>],
                    backgroundColor: ['#4bb9a5', '#0a4e53']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
    // Gráfico de Faixa Etária - CORRIGIDO
    const ageCtx = document.getElementById('ageChart');
    if (ageCtx) {
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ['0-12 anos', '13-17 anos', '18-30 anos', '31-45 anos', '46-60 anos', '60+ anos'],
                datasets: [{
                    label: 'Pacientes',
                    data: <?= json_encode($faixa_data) ?>,
                    backgroundColor: [
                        '#4bb9a5', // 0-12 anos
                        '#2fa2aaff', // 13-17 anos  
                        '#447066ff', // 18-30 anos
                        '#0a4e53', // 31-45 anos
                        '#19633aff', // 46-60 anos
                        '#18462cff'  // 60+ anos
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Pacientes'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Faixa Etária'
                        }
                    }
                }
            }
        });
    }
        // Gráfico de Produtividade por Dia da Semana - CORRIGIDO
    const weekdayCtx = document.getElementById('weekdayChart');
    if (weekdayCtx) {
        new Chart(weekdayCtx, {
            type: 'bar',
            data: {
                labels: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                datasets: [{
                    label: 'Consultas',
                    data: <?= json_encode($dias_semana_data) ?>,
                    backgroundColor: '#0a4e53',
                    borderColor: '#0a4e53',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Número de Consultas'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Dia da Semana'
                        }
                    }
                }
            }
        });
    }

    // Gráfico de Tendência Mensal - CORRIGIDO
    const trendCtx = document.getElementById('trendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($tendencia_labels) ?>,
                datasets: [{
                    label: 'Consultas',
                    data: <?= json_encode($tendencia_data) ?>,
                    borderColor: '#4bb9a5',
                    backgroundColor: 'rgba(75, 185, 165, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#0a4e53',
                    pointBorderColor: '#0a4e53',
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Consultas Realizadas'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Mês'
                        }
                    }
                }
            }
        });
    }
    // Horários Mais Utilizados
    const hoursCtx = document.getElementById('hoursChart');
    if (hoursCtx) {
        new Chart(hoursCtx, {
            type: 'bar',
            data: {
                labels: <?= $horarios_labels ?>,
                datasets: [{
                    label: 'Consultas',
                    data: <?= $horarios_data ?>,
                    backgroundColor: '#4bb9a5'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Horário do Dia'
                        }
                    }
                }
            }
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>