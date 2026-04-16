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

// Se salvar atestado
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_atestado'])) {
  $descricao = $_POST['descricao'];
  $dias = $_POST['dias_afastamento'];
  $paciente_maior_id = !empty($_POST['paciente_maior_id']) ? $_POST['paciente_maior_id'] : null;
  $paciente_menor_id = !empty($_POST['paciente_menor_id']) ? $_POST['paciente_menor_id'] : null;
  $responsavel_id = !empty($_POST['responsavel_id']) ? $_POST['responsavel_id'] : null;

  $sql = "INSERT INTO atestado 
          (medico_id, paciente_maior_id, paciente_menor_id, responsavel_id, data_emissao, dias_afastamento, descricao) 
          VALUES (?, ?, ?, ?, NOW(), ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iiiiss", $medico_id, $paciente_maior_id, $paciente_menor_id, $responsavel_id, $dias, $descricao);
  $stmt->execute();
}

// Se salvar receita
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['salvar_receita'])) {
  $observacoes = $_POST['observacoes'];
  $validade = $_POST['validade'];
  $validade_final = $_POST['validade_final'];
  $tipo_paciente = $_POST['tipo_paciente'];

  $paciente_maior_id = $tipo_paciente === "maior" ? $_POST['paciente_maior_id'] : null;
  $paciente_menor_id = $tipo_paciente === "menor" ? $_POST['paciente_menor_id'] : null;

  $sql = "INSERT INTO receita 
          (paciente_maior_id, medico_id, data_emissao, validade, validade_final, observacoes, paciente_menor_id, tipo_paciente) 
          VALUES (?, ?, NOW(), ?, ?, ?, ?, ?)";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iisssis", $paciente_maior_id, $medico_id, $validade, $validade_final, $observacoes, $paciente_menor_id, $tipo_paciente);
  $stmt->execute();
}

// Se finalizar consulta
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['finalizar_consulta'])) {
    $consulta_id = $_POST['consulta_id'];
    $tipo_paciente = $_POST['tipo_paciente'];

    if ($tipo_paciente === "maior") {
        $sql = "UPDATE consultas_maior SET status = 'Finalizada' WHERE consulta_id = ?";
    } else {
        $sql = "UPDATE consultas_menor SET status = 'Finalizada' WHERE consulta_id = ?";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $consulta_id);
    $stmt->execute();
    header("Location: consultas.php");
    exit();
}

function mapStatus($statusBanco) {
    if ($statusBanco === 'Agendada') return 'agendada';
    if ($statusBanco === 'Finalizada') return 'finalizada';
    if ($statusBanco === 'Cancelada') return 'cancelada';
    return strtolower($statusBanco);
}

// CONSULTAS ORDENADAS POR PRIORIDADE:
// 1. Status (Agendadas primeiro)
// 2. Data mais próxima
// 3. Hora mais próxima

$sql_maior = "
    SELECT c.consulta_id, c.medico_id, c.paciente_maior_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
          p.pmaior_nome AS paciente_nome, p.pmaior_cpf AS paciente_cpf, p.pmaior_email AS paciente_email
    FROM consultas_maior c
    JOIN paciente_maior p ON c.paciente_maior_id = p.pmaior_id
    WHERE c.medico_id = ?
    ORDER BY 
        CASE 
            WHEN c.status = 'Agendada' THEN 1
            WHEN c.status = 'Finalizada' THEN 2
            WHEN c.status = 'Cancelada' THEN 3
            ELSE 4
        END,
        c.data_consulta ASC,
        c.hora_consulta ASC
";

$stmt_maior = $conn->prepare($sql_maior);
$stmt_maior->bind_param("i", $medico_id);
$stmt_maior->execute();
$result_maior = $stmt_maior->get_result();
$consultas_maior = $result_maior->fetch_all(MYSQLI_ASSOC);

$sql_menor = "
    SELECT c.consulta_id, c.medico_id, c.paciente_menor_id, c.responsavel_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
          p.pmenor_nome AS paciente_nome, p.pmenor_cpf AS paciente_cpf, p.pmenor_email AS paciente_email
    FROM consultas_menor c
    JOIN paciente_menor p ON c.paciente_menor_id = p.pmenor_id
    WHERE c.medico_id = ?
    ORDER BY 
        CASE 
            WHEN c.status = 'Agendada' THEN 1
            WHEN c.status = 'Finalizada' THEN 2
            WHEN c.status = 'Cancelada' THEN 3
            ELSE 4
        END,
        c.data_consulta ASC,
        c.hora_consulta ASC
";

$stmt_menor = $conn->prepare($sql_menor);
$stmt_menor->bind_param("i", $medico_id);
$stmt_menor->execute();
$result_menor = $stmt_menor->get_result();
$consultas_menor = $result_menor->fetch_all(MYSQLI_ASSOC);
$consultas = [];

// 🔹 Consultas de pacientes MAIORES para o calendário
$sql_maior_calendar = "
    SELECT c.consulta_id, c.medico_id, c.paciente_maior_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
          p.pmaior_nome AS paciente_nome
    FROM consultas_maior c
    JOIN paciente_maior p ON c.paciente_maior_id = p.pmaior_id
    WHERE c.medico_id = ?
    ORDER BY c.data_consulta, c.hora_consulta
";
$stmt = $conn->prepare($sql_maior_calendar);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $consultas[] = [
    'id' => $row['consulta_id'],
    'title' => 'Consulta ' . $row['status'],
    'start' => $row['data_consulta'] . 'T' . $row['hora_consulta'],
    'paciente' => $row['paciente_nome'],
    'paciente_id' => $row['paciente_maior_id'],
    'tipo_paciente' => 'maior',
    'status' => $row['status'],
    'observacoes' => $row['observacoes']
  ];
}

// 🔹 Consultas de pacientes MENORES para o calendário
$sql_menor_calendar = "
    SELECT c.consulta_id, c.medico_id, c.paciente_menor_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
          p.pmenor_nome AS paciente_nome
    FROM consultas_menor c
    JOIN paciente_menor p ON c.paciente_menor_id = p.pmenor_id
    WHERE c.medico_id = ?
    ORDER BY c.data_consulta, c.hora_consulta
";
$stmt = $conn->prepare($sql_menor_calendar);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $consultas[] = [
    'id' => $row['consulta_id'],
    'title' => 'Consulta ' . $row['status'],
    'start' => $row['data_consulta'] . 'T' . $row['hora_consulta'],
    'paciente' => $row['paciente_nome'],
    'paciente_id' => $row['paciente_menor_id'],
    'tipo_paciente' => 'menor',
    'status' => $row['status'],
    'observacoes' => $row['observacoes']
  ];
}

// 🔹 Retorna os eventos como JSON (caso este arquivo seja chamado pelo calendário)
if (isset($_GET['json'])) {
  header('Content-Type: application/json');
  echo json_encode($consultas);
  exit;
}

// Combinar e ordenar todas as consultas para exibição na página
$todas_consultas = [];

// Adicionar consultas de maiores
foreach ($consultas_maior as $consulta) {
    $todas_consultas[] = [
        'consulta' => $consulta,
        'tipo' => 'maior',
        'timestamp' => strtotime($consulta['data_consulta'] . ' ' . $consulta['hora_consulta'])
    ];
}

// Adicionar consultas de menores
foreach ($consultas_menor as $consulta) {
    $todas_consultas[] = [
        'consulta' => $consulta,
        'tipo' => 'menor',
        'timestamp' => strtotime($consulta['data_consulta'] . ' ' . $consulta['hora_consulta'])
    ];
}

// Ordenar por status (Agendadas primeiro) e depois por data/hora
usort($todas_consultas, function($a, $b) {
    // Primeiro ordena por status
    $status_order = ['Agendada' => 1, 'Finalizada' => 2, 'Cancelada' => 3];
    $status_a = $status_order[$a['consulta']['status']] ?? 4;
    $status_b = $status_order[$b['consulta']['status']] ?? 4;
    
    if ($status_a !== $status_b) {
        return $status_a - $status_b;
    }
    
    // Se mesmo status, ordena por data/hora
    return $a['timestamp'] - $b['timestamp'];
});

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Consultas | Medsync</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales-all.global.min.js"></script>


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
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: fixed;
      top: 0;
      width: 100%;
      z-index: 1030;
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
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      border: none;
      padding: 1rem 1.5rem;
      margin: 0 auto 1rem auto;
      transition: transform 0.3s;
      cursor: default;
    }
    .card:hover {
      transform: translateY(-5px);
      cursor: pointer;
    }
    .card h5 {
      color: #157c5d;
      font-weight: 600;
      font-size: 1.25rem;
      margin-bottom: 3px;
      margin-right: 10px;
      display: inline-block;
      text-align: left;
    }
    .card .status {
      float: right;
      font-weight: 500;
      font-size: 0.85rem;
      text-transform: capitalize;
      margin-top: 3px;
      padding: 4px 8px;
      border-radius: 2px;
    }
    .status-agendada {
      color: #155724;
    }
    .status-finalizada {
      color: #721c24;
    }
    .status-cancelada {
      color: #856404;
    }
    .filter-bar {
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
    }
    .filter-bar input[type="text"] {
      flex: 1;
      min-width: 200px;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
    }
    .filter-bar select {
      min-width: 180px;
      padding: 0.375rem 0.75rem;
      font-size: 1rem;
      border: 1px solid #ced4da;
      border-radius: 0.375rem;
      color: #0a4e53;
      font-weight: 600;
      cursor: pointer;
      background: white;
    }
    #noConsultasMessage {
      text-align: center;
      color: #777;
      font-size: 1.2rem;
      padding-top: 30px;
      display: none;
    }
    .text-success {
    --bs-text-opacity: 1;
    color: rgb(10 78 83) !important;
    }
    .btn-success {
      --bs-btn-bg: #0a4e53;
      --bs-btn-color: #fff;
      --bs-btn-bg: #0a4e53;
      --bs-btn-border-color: #0a4e53;
      --bs-btn-hover-color: #fff;
      --bs-btn-hover-bg: #0a4e53;
      --bs-btn-hover-border-color: #0a4e53;
      --bs-btn-focus-shadow-rgb: 60, 153, 110;
      --bs-btn-active-color: #fff;
      --bs-btn-active-bg:#0a4e53;
      --bs-btn-active-border-color: #0a4e53;
      --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
      --bs-btn-disabled-color: #fff;
      --bs-btn-disabled-bg: #0a4e53;
      --bs-btn-disabled-border-color: #0a4e53;
    }
.bg-primary {
    --bs-bg-opacity: 1;
    background-color: #4bb9a5 !important;
}
.bg-success {
    --bs-bg-opacity: 1;
    background-color: #4bb9a5 !important;
}
a {
    color: #0a4e53;
    text-decoration: underline;}

#consultaInfo {
  font-size: 0.95rem;
}
.navbar a i.fa-calendar-days {
  margin-left: 15px;
  cursor: pointer;
}
  #calendar {
    max-width: 100%;
    height: 600px;
    margin: 0 auto;
  }
/* Cores do header do calendário */
.fc .fc-toolbar-title {
    color: #0a4e53 !important;
    font-weight: 600;
}

.fc .fc-toolbar button {
    background-color: #4bb9a5 !important;
    color: #0a4e53 !important;
    border: none;
}

.fc .fc-toolbar button:hover {
    background-color: #3fa390 !important;
    color: #fff !important;
}

/* Eventos com texto ajustado */
.fc-event {
    font-size: 0.75rem !important;
    padding: 2px 4px !important;
    white-space: normal;
    word-wrap: break-word;
    text-align: center;
}
.formulario-card {
    background-color: #fdfdfd;
  }
  .formulario-conteudo {
    transition: all 0.3s ease;
  }
/* Cores dos dias */
.fc-daygrid-day-number {
    color: #0a4e53;
}
.fc .fc-daygrid-event {
    white-space: normal;
    overflow: hidden;
    text-overflow: clip;
    font-size: 0.8rem;
    padding: 2px;
}

/* Cores de fundo dos eventos conforme status */
.fc-event.agendada {
    background-color: #198754 !important; /* verde */
    color: white !important;
}

.fc-event.finalizada {
    background-color: #dc3545 !important; /* vermelho */
    color: white !important;
}

.fc-event.cancelada {
    background-color: #ffc107 !important; /* amarelo */
    color: #0a4e53 !important;
}
/* Botões Salvar e Imprimir - mesma cor do título da consulta */
.btn-consulta {
    background-color: #0a4e53;
    color: #fff;
    border: 1px solid #0a4e53;
    transition: 0.3s;
}

.btn-consulta:hover {
    background-color: #08403a; /* tom mais escuro para hover */
    color: #fff;
    border-color: #08403a;
}

.btn-consulta-outline {
    background-color: transparent;
    color: #0a4e53;
    border: 1px solid #0a4e53;
    transition: 0.3s;
}

.btn-consulta-outline:hover {
    background-color: #0a4e53;
    color: #fff;
    border-color: #0a4e53;
}

/* Botões principais do modal (Salvar, Finalizar) */
.btn-consulta {
    background-color: #0a4e53;
    color: #fff;
    border: 1px solid #0a4e53;
    transition: 0.3s;
}

.btn-consulta:hover {
    background-color: #08403a; /* tom mais escuro para hover */
    color: #fff;
    border-color: #08403a;
}

/* Botões secundários (Imprimir, Enviar, Acesso rápido) */
.btn-consulta-outline {
    background-color: transparent;
    color: #0a4e53;
    border: 1px solid #0a4e53;
    transition: 0.3s;
}

.btn-consulta-outline:hover {
    background-color: #0a4e53;
    color: #fff;
    border-color: #0a4e53;
}

/* Botões de acesso rápido */
.btn-acesso-rapido {
    background-color: transparent;
    color: #0a4e53;
    border: 1px solid #0a4e53;
    transition: 0.3s;
    font-size: 0.85rem;
}
/* Remove o background verde dos modais */
#consultaModal .modal-header,
#historicoConsultasModal .modal-header,
#historicoDocumentosModal .modal-header {
    background-color: transparent; /* Remove qualquer cor de fundo */
    border-bottom: none;           /* Remove a borda inferior se houver */
    color: #0a4e53;                /* Cor do título igual aos botões */
}

/* Opcional: deixar os botões do modal no mesmo estilo */
#consultaModal .btn,
#historicoConsultasModal .btn,
#historicoDocumentosModal .btn {
    background-color: #0a4e53;
    color: #fff;
    border: none;
}

/* Ajusta o título para se alinhar melhor */
#consultaModal .modal-title,
#historicoConsultasModal .modal-title,
#historicoDocumentosModal .modal-title {
    font-weight: 600;
}
modal-header .btn-close {
    padding: calc(var(--bs-modal-header-padding-y) * .5) calc(var(--bs-modal-header-padding-x) * .5);
    margin: calc(-.5 * var(--bs-modal-header-padding-y)) calc(-.5 * var(--bs-modal-header-padding-x)) calc(-.5 * var(--bs-modal-header-padding-y)) auto;
    background-color: #0a4e53;
}
.btn-acesso-rapido:hover {
    background-color: #0a4e53;
    color: #fff;
    border-color: #0a4e53;
}
#consultaModal .modal-header, #historicoConsultasModal .modal-header, #historicoDocumentosModal .modal-header {
    background-color: transparent;
    border-bottom: none;}
    .bg-success {
    --bs-bg-opacity: 1;
    background-color: #ffffffff !important;
}
.modal-title {
    margin-bottom: 0;
    line-height: var(--bs-modal-title-line-height);
    color: #0a4e53; !important;
}

/* ESTILOS PARA IMPRESSÃO DOS DOCUMENTOS */
@media print {
    body * {
        visibility: hidden;
    }
    .documento-impressao,
    .documento-impressao * {
        visibility: visible;
    }
    .documento-impressao {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        font-family: 'Arial', sans-serif;
        line-height: 1.6;
        padding: 20px;
    }
    .no-print {
        display: none !important;
    }
}

.documento-impressao {
    font-family: 'Arial', sans-serif;
    line-height: 1.6;
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.documento-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #0a4e53;
}

.documento-title {
    color: #0a4e53;
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 10px;
}

.documento-subtitle {
    color: #4bb9a5;
    font-size: 1.1rem;
}

.documento-info {
    margin-bottom: 25px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f0;
}

.info-label {
    font-weight: 600;
    color: #0a4e53;
    min-width: 150px;
}

.info-value {
    flex: 1;
    text-align: right;
}

.documento-content {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #0a4e53;
    white-space: pre-line;
}

.documento-observacoes {
    font-style: italic;
    color: #555;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px dashed #ccc;
}

.documento-footer {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid #0a4e53;
}

.assinatura {
    margin-top: 50px;
    text-align: center;
}

.assinatura-line {
    width: 300px;
    height: 1px;
    background: #000;
    margin: 40px auto 10px;
}

.assinatura-text {
    font-weight: bold;
    color: #0a4e53;
}

/* Estilos para os formulários de documentos no modal */
.formulario-conteudo textarea {
    min-height: 120px;
    resize: vertical;
}

.formulario-conteudo .form-control {
    margin-bottom: 10px;
}

  </style>
</head>
<body>

<nav class="navbar">
  <img src="../images/logo/logo_branca.png" alt="Logo" />
  <a href="#" data-bs-toggle="modal" data-bs-target="#modalCalendario" title="Ver calendário">
  <i class="fa-solid fa-calendar-days fa-lg"></i>
</a>


  <a href="painelmed.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>

<div class="container">
  <h2 class="mb-4 text-center text-success">Consultas Agendadas</h2>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="Pesquisar por nome do paciente..." />
    <select id="statusFilter">
      <option value="todas">Todas</option>
      <option value="agendada">Agendada</option>
      <option value="finalizada">Finalizada</option>
      <option value="cancelada">Cancelada</option>
    </select>
  </div>

  <div id="cardsContainer">
    <?php 
    // Usar as consultas ordenadas
    foreach ($todas_consultas as $item) {
        $consulta = $item['consulta'];
        $tipo = $item['tipo'];
        
        $status = mapStatus($consulta['status']);
        $dataFormatada = date('d/m/Y', strtotime($consulta['data_consulta']));
        $horaFormatada = substr($consulta['hora_consulta'], 0, 5);
        $nome = htmlspecialchars($consulta['paciente_nome']);
        $cpf = htmlspecialchars($consulta['paciente_cpf']);
        $email = htmlspecialchars($consulta['paciente_email']);
        $observacoes = htmlspecialchars($consulta['observacoes']);
        
        $status_class = 'status-' . $status;
        
        echo '<div class="card" data-nome="'.strtolower($nome).'" data-status="'.$status.'">';
        echo "<h5>$nome</h5>";
        echo '<span class="status ' . $status_class . '">'.ucfirst($status).'</span>';
        echo "<p>Data: $dataFormatada<br>Hora: $horaFormatada</p>";
        if ($status !== 'finalizada' && $status !== 'cancelada') {
          echo '<button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#consultaModal"
                  data-nome="'.$nome.'" data-tipo="'.$tipo.'" 
                  data-id="'.($tipo === 'maior' ? $consulta['paciente_maior_id'] : $consulta['paciente_menor_id']).'" 
                  data-consulta="'.$consulta['consulta_id'].'"
                  data-cpf="'.$cpf.'" data-email="'.$email.'" data-motivo="'.$observacoes.'" 
                  data-status="'.ucfirst($status).'">Iniciar Consulta</button>';
        }
        echo '</div>';
    }

    if (empty($todas_consultas)): ?>
      <div id="noConsultasMessage">
        <i class="fa-regular fa-calendar-xmark fa-3x mb-3"></i>
        <p>Você não possui consultas no momento.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- --- MODAL CALENDÁRIO --- -->
<div class="modal fade" id="modalCalendario" tabindex="-1" aria-labelledby="modalCalendarioLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalCalendarioLabel">Calendário de Consultas</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="calendar"></div>
      </div>
    </div>
  </div>
</div>
<!-- Modal de detalhes das consultas -->
<div class="modal fade" id="detalhesConsultaModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="detalhesConsultaTitle"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="detalhesConsultaBody"></div>
    </div>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="consultaModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content p-3">
      <div class="modal-header">
        <h5 class="modal-title" style="color:#0a4e53;">Consulta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Triagem organizada -->
        <div class="triagem-info mb-3" id="triagemInfo" style="display:flex; gap:20px; flex-wrap:wrap;">
          <!-- Os dados serão preenchidos via JS -->
        </div>

        
        <div class="mb-3">
  <button type="button" class="btn btn-acesso-rapido btn-sm" id="btnHistoricoConsultas">
    <i class="fa-solid fa-clock-rotate-left"></i> Histórico de Consultas
  </button>
  <button type="button" class="btn btn-acesso-rapido btn-sm" id="btnHistoricoDocumentos">
    <i class="fa-solid fa-file-medical"></i> Atestados e Receitas Anteriores
  </button>
</div>

        <!-- Formulário Atestado -->
<div class="formulario-card mb-3 border rounded p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="m-0">Atestado</h6>
    <i class="fa-solid fa-chevron-down toggle-form" style="cursor:pointer;"></i>
  </div>
  <div class="formulario-conteudo" style="display:none;"> <!-- inicia fechado -->
    <form method="POST" action="consultas.php" id="formAtestado">
      <input type="hidden" name="paciente_maior_id" id="paciente_maior_id_atestado">
      <input type="hidden" name="paciente_menor_id" id="paciente_menor_id_atestado">
      <input type="hidden" name="responsavel_id" id="responsavel_id_atestado">
      
      <textarea class="form-control mb-2" name="descricao" id="descricaoAtestado" rows="4" placeholder="Escreva o atestado..."></textarea>
      <input type="number" name="dias_afastamento" id="diasAtestado" class="form-control mb-2" placeholder="Dias de afastamento">
      <div class="d-flex gap-2">
        <div class="d-flex gap-2">
  <button type="submit" name="salvar_atestado" class="btn btn-consulta">Salvar Atestado</button>
  <button type="button" class="btn btn-consulta-outline" onclick="imprimirAtestado()">Imprimir Atestado</button>
</div>

      </div>
    </form>
  </div>
</div>

<!-- Formulário Receita -->
<div class="formulario-card mb-3 border rounded p-3">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <h6 class="m-0">Receita</h6>
    <i class="fa-solid fa-chevron-down toggle-form" style="cursor:pointer;"></i>
  </div>
  <div class="formulario-conteudo" style="display:none;"> <!-- inicia fechado -->
    <form method="POST" action="consultas.php" id="formReceita">
      <input type="hidden" name="paciente_maior_id" id="paciente_maior_id_receita">
      <input type="hidden" name="paciente_menor_id" id="paciente_menor_id_receita">
      <input type="hidden" name="tipo_paciente" id="tipo_paciente_receita">

      <textarea class="form-control mb-2" name="observacoes" id="observacoesReceita" rows="4" placeholder="Digite a receita médica..."></textarea>
      <input type="date" name="validade" id="validadeReceita" class="form-control mb-2" required>
      <input type="date" name="validade_final" id="validadeFinalReceita" class="form-control mb-2" required>
      <div class="d-flex gap-2">
  <button type="submit" name="salvar_receita" class="btn btn-consulta">Salvar Receita</button>
  <button type="button" class="btn btn-consulta-outline" onclick="imprimirReceita()">Imprimir Receita</button>
 </div>

    </form>
  </div>
</div>

        <!-- Finalizar Consulta -->
        <form method="POST" action="consultas.php">
          <input type="hidden" name="consulta_id" id="consulta_id_finalizar">
          <input type="hidden" name="tipo_paciente" id="tipo_paciente_finalizar">
          <button type="submit" name="finalizar_consulta" class="btn btn-consulta w-100">Finalizar Consulta</button>

        </form>

      </div>
    </div>
  </div>
</div><!-- MODAL HISTÓRICO DE CONSULTAS -->
<div class="modal fade" id="historicoConsultasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- mesmo tamanho do #consultaModal -->
    <div class="modal-content p-3">
      <div class="modal-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Histórico de Consultas</h5>
        <!-- Botão de voltar -->
        <button type="button" class="btn btn-outline-light btn-sm" id="btnVoltarConsulta">
          <i class="fa-solid fa-arrow-left"></i> Voltar
        </button>
      </div>
      <div class="modal-body">
        <div id="historicoConsultasContainer" class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Médico</th>
                <th>Data</th>
                <th>Hora</th>
                <th>Status</th>
                <th>Observações</th>
              </tr>
            </thead>
            <tbody id="historicoConsultasBody">
              <tr><td colspan="4" class="text-center text-muted">Selecione um paciente para visualizar o histórico.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- MODAL HISTÓRICO DE DOCUMENTOS -->
<div class="modal fade" id="historicoDocumentosModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered"> <!-- mesmo tamanho do #consultaModal -->
    <div class="modal-content p-3">
      <div class="modal-header bg-success text-white d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Atestados e Receitas Anteriores</h5>
        <!-- Botão de voltar -->
        <button type="button" class="btn btn-outline-light btn-sm" id="btnVoltarDocumentos">
          <i class="fa-solid fa-arrow-left"></i> Voltar
        </button>
      </div>
      <div class="modal-body">
        <div id="historicoDocumentosContainer" class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th>Médico</th>
                <th>Data</th>
                <th>Tipo</th>
                <th>Descrição / Observações</th>
                <th>Dias / Validade</th>
              </tr>
            </thead>
            <tbody id="historicoDocumentosBody">
              <tr><td colspan="4" class="text-center text-muted">Selecione um paciente para visualizar os documentos.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Variáveis globais para armazenar dados do paciente
let pacienteIdSelecionado = null;
let tipoPacienteSelecionado = null;
let pacienteNomeSelecionado = null;
let pacienteCpfSelecionado = null;

// Referências principais
const consultaModal = document.getElementById("consultaModal");
const triagemDiv = document.getElementById("triagemInfo");
const searchInput = document.getElementById("searchInput");
const statusFilter = document.getElementById("statusFilter");
const cardsContainer = document.getElementById("cardsContainer");
const cards = cardsContainer.getElementsByClassName("card");
const noConsultasMsg = document.getElementById("noConsultasMessage");

// Quando abrir o modal de consulta
consultaModal.addEventListener("show.bs.modal", (event) => {
  const button = event.relatedTarget;
  const nome = button.getAttribute("data-nome");
  const tipo = button.getAttribute("data-tipo");
  const id = button.getAttribute("data-id");
  const consultaId = button.getAttribute("data-consulta");
  const cpf = button.getAttribute("data-cpf");
  const email = button.getAttribute("data-email");
  const motivo = button.getAttribute("data-motivo") || "Nenhum";
  const status = button.getAttribute("data-status");

  // Armazena o paciente selecionado globalmente
  pacienteIdSelecionado = id;
  tipoPacienteSelecionado = tipo;
  pacienteNomeSelecionado = nome;
  pacienteCpfSelecionado = cpf;

  // Preenche inputs ocultos
  document.getElementById("paciente_maior_id_atestado").value = tipo === "maior" ? id : "";
  document.getElementById("paciente_menor_id_atestado").value = tipo === "menor" ? id : "";
  document.getElementById("responsavel_id_atestado").value = "";

  document.getElementById("paciente_maior_id_receita").value = tipo === "maior" ? id : "";
  document.getElementById("paciente_menor_id_receita").value = tipo === "menor" ? id : "";
  document.getElementById("tipo_paciente_receita").value = tipo;

  document.getElementById("consulta_id_finalizar").value = consultaId;
  document.getElementById("tipo_paciente_finalizar").value = tipo;

// Exibe triagem organizada (3 à esquerda + 3 à direita lado a lado)
triagemDiv.innerHTML = `
  <div style="display: flex; justify-content: space-between; gap: 80px; flex-wrap: nowrap;">
    <div style="width: 48%;">
      <p><b>Nome:</b> ${nome}</p>
      <p><b>CPF:</b> ${cpf}</p>
      <p><b>E-mail:</b> ${email}</p>
    </div>
    <div style="width: 48%;">
      <p><b>Status:</b> ${status}</p>
      <p><b>Tipo:</b> ${tipo} de idade</p>
      <p><b>Observações:</b> ${motivo}</p>
    </div>
  </div>
`;

});

// Funções para impressão dos documentos
function imprimirAtestado() {
    const descricao = document.getElementById('descricaoAtestado').value;
    const dias = document.getElementById('diasAtestado').value;
    
    if (!descricao) {
        alert('Por favor, preencha a descrição do atestado antes de imprimir.');
        return;
    }
    
    const dataAtual = new Date().toLocaleDateString('pt-BR');
    
    const conteudo = `
        <div class="documento-impressao">
            <div class="documento-header">
                <div class="documento-title">ATESTADO MÉDICO</div>
                <div class="documento-subtitle">MedSync - Sistema de Saúde</div>
            </div>
            
            <div class="documento-info">
                <div class="info-row">
                    <span class="info-label">Paciente:</span>
                    <span class="info-value">${pacienteNomeSelecionado}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CPF:</span>
                    <span class="info-value">${pacienteCpfSelecionado}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data de Emissão:</span>
                    <span class="info-value">${dataAtual}</span>
                </div>
                ${dias ? `<div class="info-row">
                    <span class="info-label">Dias de Afastamento:</span>
                    <span class="info-value">${dias} dias</span>
                </div>` : ''}
            </div>
            
            <div class="documento-content">
                ${descricao}
            </div>
            
            <div class="documento-footer">
                <div class="assinatura">
                    <div class="assinatura-line"></div>
                    <div class="assinatura-text">Dr. <?php echo $_SESSION['medico_nome'] ?? 'Médico'; ?></div>
                    <div class="assinatura-text">CRM: <?php echo $_SESSION['medico_crm'] ?? ''; ?></div>
                </div>
            </div>
        </div>
    `;
    
    imprimirDocumento(conteudo);
}

function imprimirReceita() {
    const observacoes = document.getElementById('observacoesReceita').value;
    const validade = document.getElementById('validadeReceita').value;
    const validadeFinal = document.getElementById('validadeFinalReceita').value;
    
    if (!observacoes) {
        alert('Por favor, preencha a receita antes de imprimir.');
        return;
    }
    
    const dataAtual = new Date().toLocaleDateString('pt-BR');
    const validadeFormatada = validade ? new Date(validade).toLocaleDateString('pt-BR') : '';
    const validadeFinalFormatada = validadeFinal ? new Date(validadeFinal).toLocaleDateString('pt-BR') : '';
    
    const conteudo = `
        <div class="documento-impressao">
            <div class="documento-header">
                <div class="documento-title">RECEITA MÉDICA</div>
                <div class="documento-subtitle">MedSync - Sistema de Saúde</div>
            </div>
            
            <div class="documento-info">
                <div class="info-row">
                    <span class="info-label">Paciente:</span>
                    <span class="info-value">${pacienteNomeSelecionado}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CPF:</span>
                    <span class="info-value">${pacienteCpfSelecionado}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data de Emissão:</span>
                    <span class="info-value">${dataAtual}</span>
                </div>
                ${validadeFormatada ? `<div class="info-row">
                    <span class="info-label">Validade:</span>
                    <span class="info-value">${validadeFormatada} à ${validadeFinalFormatada}</span>
                </div>` : ''}
            </div>
            
            <div class="documento-content">
                ${observacoes}
            </div>
            
            <div class="documento-footer">
                <div class="assinatura">
                    <div class="assinatura-line"></div>
                    <div class="assinatura-text">Dr. <?php echo $_SESSION['medico_nome'] ?? 'Médico'; ?></div>
                    <div class="assinatura-text">CRM: <?php echo $_SESSION['medico_crm'] ?? ''; ?></div>
                </div>
            </div>
        </div>
    `;
    
    imprimirDocumento(conteudo);
}

function imprimirDocumento(conteudo) {
    const janela = window.open('', '_blank');
    janela.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Documento Médico</title>
            <style>
                body { 
                    font-family: 'Arial', sans-serif; 
                    margin: 0; 
                    padding: 20px; 
                    line-height: 1.6;
                    color: #333;
                }
                .documento-impressao {
                    max-width: 800px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .documento-header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #0a4e53;
                }
                .documento-title {
                    color: #0a4e53;
                    font-size: 1.5rem;
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .documento-subtitle {
                    color: #4bb9a5;
                    font-size: 1.1rem;
                }
                .documento-info {
                    margin-bottom: 25px;
                }
                .info-row {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 8px;
                    padding: 5px 0;
                    border-bottom: 1px solid #f0f0f0;
                }
                .info-label {
                    font-weight: 600;
                    color: #0a4e53;
                    min-width: 150px;
                }
                .info-value {
                    flex: 1;
                    text-align: right;
                }
                .documento-content {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    border-left: 4px solid #0a4e53;
                    white-space: pre-line;
                }
                .documento-footer {
                    text-align: center;
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 2px solid #0a4e53;
                }
                .assinatura {
                    margin-top: 50px;
                    text-align: center;
                }
                .assinatura-line {
                    width: 300px;
                    height: 1px;
                    background: #000;
                    margin: 40px auto 10px;
                }
                .assinatura-text {
                    font-weight: bold;
                    color: #0a4e53;
                }
                @media print {
                    body { margin: 0; }
                    .documento-impressao { padding: 0; }
                }
            </style>
        </head>
        <body>
            ${conteudo}
        </body>
        </html>
    `);
    janela.document.close();
    
    // Aguarda o conteúdo carregar antes de imprimir
    setTimeout(() => {
        janela.print();
        // janela.close(); // Opcional: fechar após imprimir
    }, 250);
}

// Alterna exibição dos formulários (atestado e receita)
document.querySelectorAll(".toggle-form").forEach((icon) => {
  icon.addEventListener("click", () => {
    const conteudo = icon.closest(".formulario-card").querySelector(".formulario-conteudo");
    conteudo.style.display = conteudo.style.display === "none" ? "block" : "none";
    icon.classList.toggle("fa-chevron-down");
    icon.classList.toggle("fa-chevron-up");
  });
});

// Filtro de pesquisa
searchInput.addEventListener("input", filtrarConsultas);
statusFilter.addEventListener("change", filtrarConsultas);

function filtrarConsultas() {
  const termo = searchInput.value.toLowerCase();
  const statusSel = statusFilter.value;
  let visiveis = 0;

  Array.from(cards).forEach((card) => {
    const nome = card.getAttribute("data-nome");
    const status = card.getAttribute("data-status");
    const matchNome = nome.includes(termo);
    const matchStatus = statusSel === "todas" || status === statusSel;

    if (matchNome && matchStatus) {
      card.style.display = "block";
      visiveis++;
    } else {
      card.style.display = "none";
    }
  });

  noConsultasMsg.style.display = visiveis === 0 ? "block" : "none";
}

// -----------------------------
// CALENDÁRIO FULLCALENDAR
// -----------------------------
document.addEventListener("DOMContentLoaded", function() {
    var consultas = <?php echo json_encode($consultas, JSON_UNESCAPED_UNICODE); ?>;
    console.log("Consultas carregadas:", consultas);

    var calendarEl = document.getElementById("calendar");
    var calendar;

    const modalCalendario = document.getElementById('modalCalendario');
    modalCalendario.addEventListener('shown.bs.modal', function () {
        if (!calendar) {
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: "dayGridMonth",
                locale: "pt-br",
                height: 600,
                headerToolbar: {
                    left: "prev,next",
                    center: "title",
                    right: "dayGridMonth,timeGridWeek"
                },
                buttonText: {
                    month: "Mês",
                    week: "Semana",
                    day: "Dia"
                },
                events: consultas.map(c => ({
                    ...c,
                    backgroundColor: c.status === 'Agendada' ? '#d4edda' : c.status === 'Finalizada' ? '#f8d7da' : c.status === 'Cancelada' ? '#fff3cd' : '#e2e3e5',
                    borderColor: c.status === 'Agendada' ? '#4bb9a5' : c.status === 'Finalizada' ? '#dc3545' : c.status === 'Cancelada' ? '#ffc107' : '#6c757d',
                    textColor: '#0a4e53',
                    display: 'block'
                })),
                eventContent: function(arg) {
                    return {
                        html: `<div style="text-align:center; font-size:0.8rem; white-space:normal;">
                                ${arg.event.title}
                              </div>`
                    };
                },
                dateClick: function(info) {
    const consultasDia = consultas.filter(c => c.start.startsWith(info.dateStr));
    if (consultasDia.length > 0) {
        const detalhes = consultasDia.map(c => {
            const dataHora = new Date(c.start);
            const data = dataHora.toLocaleDateString('pt-BR');
            const hora = dataHora.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            return `<b>Data:</b> ${data}<br>
                    <b>Hora:</b> ${hora}<br>
                    <b>Status:</b> ${c.status}<br>
                    <b>Paciente:</b> ${c.paciente}<br>
                    <b>Observações:</b> ${c.observacoes || "Nenhuma"};`;
        }).join("<hr>");
        document.getElementById("detalhesConsultaTitle").innerText = `Consultas em ${info.dateStr.split('-').reverse().join('/')}`;
        document.getElementById("detalhesConsultaBody").innerHTML = detalhes;
        const modal = new bootstrap.Modal(document.getElementById("detalhesConsultaModal"));
        modal.show();
    } else {
        alert("Nenhuma consulta agendada para este dia.");
    }
}

            });
            calendar.render();
        } else {
            calendar.updateSize();
        }
    });
});

// -----------------------------
// HISTÓRICO DE CONSULTAS
// -----------------------------
document.getElementById("btnHistoricoConsultas").addEventListener("click", () => {
  const consultaModalEl = document.getElementById("consultaModal");
  const historicoModalEl = document.getElementById("historicoConsultasModal");

  const tbody = document.getElementById("historicoConsultasBody");
  tbody.innerHTML = "";

  if (!pacienteIdSelecionado || !tipoPacienteSelecionado) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Nenhum paciente selecionado.</td></tr>`;
  } else {
    fetch(`buscar_historico.php?id=${pacienteIdSelecionado}&tipo=${tipoPacienteSelecionado}`)
      .then(res => res.json())
      .then(dados => {
        if (!Array.isArray(dados) || !dados.length) {
          tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">Nenhum histórico encontrado.</td></tr>`;
        } else {
        tbody.innerHTML = dados.map(c => `
  <tr>
  
    <td>${c.medicos_nome} (ID ${c.medico_id})</td>
    <td>${c.data_consulta}</td>
    <td>${c.hora_consulta}</td>
    <td>${c.status}</td>
    <td>${c.observacoes || "Nenhuma"}</td>
  </tr>
`).join('');
        }
      })
      .catch(err => {
        console.error(err);
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Erro ao carregar histórico.</td></tr>`;
      });
  }

  // Fecha o modal da consulta antes de abrir o histórico
  const modalConsulta = bootstrap.Modal.getInstance(consultaModalEl) || new bootstrap.Modal(consultaModalEl);
  modalConsulta.hide();

  // Ajusta tamanho
  const dialog = historicoModalEl.querySelector('.modal-dialog');
  dialog.classList.remove('modal-lg');
  dialog.classList.add('modal-lg');

  // Abre o modal do histórico
  new bootstrap.Modal(historicoModalEl).show();
});

// Botão de voltar do modal de histórico
document.getElementById("btnVoltarConsulta").addEventListener("click", () => {
  const historicoModal = bootstrap.Modal.getInstance(document.getElementById("historicoConsultasModal"));
  if (historicoModal) historicoModal.hide();

  new bootstrap.Modal(document.getElementById("consultaModal")).show();
});
// -----------------------------
// HISTÓRICO DE DOCUMENTOS
// -----------------------------
document.getElementById("btnHistoricoDocumentos").addEventListener("click", () => {
  const consultaModalEl = document.getElementById("consultaModal");
  const historicoDocModalEl = document.getElementById("historicoDocumentosModal");

  if (!pacienteIdSelecionado || !tipoPacienteSelecionado) {
    alert("Nenhum paciente selecionado.");
    return;
  }

  const tbody = document.getElementById("historicoDocumentosBody");
  tbody.innerHTML = "";

  fetch(`buscar_documentos.php?id=${pacienteIdSelecionado}&tipo=${tipoPacienteSelecionado}`)
    .then(res => res.json())
    .then(dados => {
      if (!Array.isArray(dados) || !dados.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Nenhum documento encontrado.</td></tr>`;
      } else {
        tbody.innerHTML = dados.map(doc => {
          // Converte datas para formato pt-BR sem deslocamento
          const dataEmissao = doc.data ? new Date(doc.data + "T00:00:00").toLocaleDateString("pt-BR") : "-";
          let diasOuValidade = "-";

          if (doc.tipo === "Receita") {
            diasOuValidade = doc.dias ? new Date(doc.dias + "T00:00:00").toLocaleDateString("pt-BR") : "-";
          } else if (doc.tipo === "Atestado") {
            diasOuValidade = doc.dias || "-";
          }

          return `
            <tr>
              <td>${doc.medico_nome} (ID ${doc.medico_id})</td>
              <td>${dataEmissao}</td>
              <td>${doc.tipo}</td>
              <td>${doc.descricao || "-"}</td>
              <td>${diasOuValidade}</td>
            </tr>
          `;
        }).join('');
      }
    })
    .catch(err => {
      console.error(err);
      tbody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Erro ao carregar documentos.</td></tr>`;
    });

  // Fecha o modal da consulta antes de abrir o histórico
  const modalConsulta = bootstrap.Modal.getInstance(consultaModalEl) || new bootstrap.Modal(consultaModalEl);
  modalConsulta.hide();

  // Ajusta tamanho
  const dialog = historicoDocModalEl.querySelector('.modal-dialog');
  dialog.classList.remove('modal-lg');
  dialog.classList.add('modal-lg');

  new bootstrap.Modal(historicoDocModalEl).show();
});

// Botão de voltar do modal de documentos
document.getElementById("btnVoltarDocumentos").addEventListener("click", () => {
  const historicoModal = bootstrap.Modal.getInstance(document.getElementById("historicoDocumentosModal"));
  if (historicoModal) historicoModal.hide();

  new bootstrap.Modal(document.getElementById("consultaModal")).show();
});
</script>
</body>
</html>