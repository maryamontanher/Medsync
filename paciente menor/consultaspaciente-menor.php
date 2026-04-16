<?php
session_start();

if (!isset($_SESSION['pmenor_id'])) {
    header("Location: login.php");
    exit();
}

$pmenor_id = $_SESSION['pmenor_id'];

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

function mapStatus($statusBanco) {
    if ($statusBanco === 'Agendada') return 'agendada';
    if ($statusBanco === 'Realizada') return 'finalizada';
    if ($statusBanco === 'Cancelada') return 'cancelada';
    return strtolower($statusBanco);
}

$sql = "
    SELECT c.consulta_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
           m.medicos_nome AS medico_nome, m.medicos_especialidade AS medico_especialidade
    FROM consultas_menor c
    JOIN medicos m ON c.medico_id = m.medicos_id
    WHERE c.paciente_menor_id = ?
    ORDER BY c.data_consulta DESC, c.hora_consulta DESC
";

$stmt = $conn->prepare($sql);

// ADIÇÃO DE DEBUG: Se a preparação falhar (retornar FALSE), mostre o erro SQL.
if ($stmt === false) {
    die('Erro de Preparação da Consulta: ' . $conn->error . "\nSQL: " . $sql);
}

$stmt->bind_param("i", $pmenor_id);
$stmt->execute();
$result = $stmt->get_result();
$minhas_consultas = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Minhas Consultas | Medsync</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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
      margin-bottom: 0.5rem;
      margin-right: 10px;
      display: inline-block;
      text-align: left;
    }
    .card .status {
      float: right;
      font-weight: 500;
      font-size: 0.85rem;
      color: #4bb9a5;
      text-transform: capitalize;
      opacity: 0.7;
      margin-top: 6px;
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
      color: #157c5d;
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

    .btn-outline-info {
    --bs-btn-color: #0a4e53;
    --bs-btn-border-color: #0a4e53;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #0a4e53;
    --bs-btn-hover-border-color: #0a4e53;
    --bs-btn-focus-shadow-rgb: 13, 202, 240;
    --bs-btn-active-color: #000;
    --bs-btn-active-bg: #0a4e53;
    --bs-btn-active-border-color: #0a4e53;
    --bs-btn-active-shadow: inset 0 3px 5px #eee7e7;
    --bs-btn-disabled-color: #0a4e53;
    --bs-btn-disabled-bg: transparent;
    --bs-btn-disabled-border-color: #0a4e53;
    --bs-gradient: none;
    }
    
    /* Estilo para mensagem informativa */
    .info-message {
      background-color: #e3f7f5;
      border: 1px solid #4bb9a5;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
      text-align: center;
      color: #0a4e53;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>

<nav class="navbar">
  <img src="../images/logo/logo_branca.png" alt="Logo" />
  <a href="painelpaciente-menor.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>

<div class="container">
  <h2 class="mb-4 text-center text-success">Minhas Consultas</h2>

  <!-- Mensagem informativa -->
  <div class="info-message">
    <i class="fas fa-info-circle me-2"></i>
    As consultas são agendadas pelo seu responsável. Entre em contato com ele para marcar novas consultas.
  </div>

  <div class="filter-bar">
    <input type="text" id="searchInput" placeholder="Pesquisar por nome do médico..." aria-label="Pesquisar por nome do médico" />
    <select id="statusFilter" aria-label="Filtrar por status da consulta">
      <option value="todas">Todas</option>
      <option value="agendada">Agendada</option>
      <option value="finalizada">Finalizada</option>
      <option value="cancelada">Cancelada</option>
    </select>
    <!-- Botão de agendar removido para paciente menor -->
  </div>

  <div id="cardsContainer">
    <?php 
    if (empty($minhas_consultas)) {
        // mensagem caso nao tenha consultas agendadas
        echo '<div id="noConsultasMessage" style="display: block; text-align: center; color: #777; font-size: 1.2rem; padding-top: 30px;" aria-live="polite" aria-atomic="true">';
        echo '<i class="fa-regular fa-calendar-xmark fa-3x mb-3"></i>';
        echo '<p>Você ainda não possui nenhuma consulta agendada.</p>';
        echo '<p class="small text-muted">Peça ao seu responsável para agendar uma consulta.</p>';
        echo '</div>';
    }

    foreach ($minhas_consultas as $consulta) {
        $status = mapStatus($consulta['status']);
        $dataFormatada = date('d/m/Y', strtotime($consulta['data_consulta']));
        $horaFormatada = substr($consulta['hora_consulta'], 0, 5);
        $nomeMedico = htmlspecialchars($consulta['medico_nome']);
        $especialidade = htmlspecialchars($consulta['medico_especialidade']);
        $observacoes = htmlspecialchars($consulta['observacoes']);
        
        echo '<div class="card" tabindex="0" data-nome="'.strtolower($nomeMedico).'" data-status="'.$status.'">';
        echo "<h5>Dr(a). $nomeMedico</h5>";
        echo '<span class="status">'.ucfirst($status).'</span>';
        echo "<p>Especialidade: $especialidade</p>";
        echo "<p>Data: $dataFormatada<br>Hora: $horaFormatada</p>";
        
        echo '<button class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#detalheModal"
                data-medico="'.$nomeMedico.'" data-especialidade="'.$especialidade.'"
                data-motivo="'.$observacoes.'" data-status="'.ucfirst($status).'">Ver Detalhes</button>';
        echo '</div>';
    }
    ?>
  </div>

  <div id="noConsultasMessageFilter" style="display: none; text-align: center; color: #777; font-size: 1.2rem; padding-top: 30px;" aria-live="polite" aria-atomic="true">
    <i class="fa-regular fa-calendar-xmark fa-3x mb-3"></i>
    <p>Não há consultas que correspondam ao seu filtro.</p>
  </div>
</div>

<div class="modal fade" id="detalheModal" tabindex="-1" aria-labelledby="detalheModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content p-4">
      <div class="modal-header">
        <h5 class="modal-title" id="detalheModalLabel">Detalhes da Consulta</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <div id="detalhesInfo"></div>
      </div>
    </div>
  </div>
</div>

<script>
  const detalheModal = document.getElementById('detalheModal');
  const detalhesDiv = document.getElementById('detalhesInfo');
  const searchInput = document.getElementById('searchInput');
  const statusFilter = document.getElementById('statusFilter');
  const cardsContainer = document.getElementById('cardsContainer');
  const cards = cardsContainer.getElementsByClassName('card');
  const noConsultasMsgFilter = document.getElementById('noConsultasMessageFilter');

  detalheModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;
    const nome = button.getAttribute('data-medico');
    const especialidade = button.getAttribute('data-especialidade');
    const motivo = button.getAttribute('data-motivo');
    const status = button.getAttribute('data-status');

    detalhesDiv.innerHTML = `
      <p><strong>Médico(a):</strong> Dr(a). ${nome}</p>
      <p><strong>Especialidade:</strong> ${especialidade}</p>
      <p><strong>Status:</strong> ${status}</p>
      <hr>
      <p><strong>Observações/Motivo:</strong> ${motivo || 'Nenhuma observação registrada.'}</p>
    `;
  });
  
  function filterCards() {
    const searchTerm = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();
    let visibleCount = 0;

    for (let card of cards) {
      const nome = card.getAttribute('data-nome').toLowerCase();
      const status = card.getAttribute('data-status').toLowerCase();

      const matchesSearch = nome.includes(searchTerm);
      const matchesStatus = (statusValue === 'todas') ? true : (status === statusValue);

      if (matchesSearch && matchesStatus) {
        card.style.display = '';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    }

    if (cards.length > 0) {
      noConsultasMsgFilter.style.display = visibleCount === 0 ? 'block' : 'none';
    } else {
      noConsultasMsgFilter.style.display = 'none';
    }
  }

  searchInput.addEventListener('input', filterCards);
  statusFilter.addEventListener('change', filterCards);

  filterCards();
</script>

</body>
</html>