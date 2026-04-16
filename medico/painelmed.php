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

$sql = "SELECT COUNT(*) as total FROM notificacoes WHERE medicos_id = ? AND lida = 0";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Erro no prepare: " . $conn->error);
}

$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$notificacoes_nao_lidas = $row['total'] ?? 0;

?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
  <link rel="stylesheet" href="../css/index.css">
  <link rel="shortcut icon" href="images/logo/minilogo_verde.png">
  <title>Medsync | Painel do Médico</title>
  <style>
    .dashboard {
      padding: 100px 20px 40px;
      max-width: 1200px;
      margin: auto;
    }
    .dashboard .section-title {
      font-size: 2rem;
      color: #157c5d;
      margin-bottom: 30px;
      text-align: center;
    }
    .dashboard .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s;
    }
    .dashboard .card:hover {
      transform: translateY(-5px);
    }
    .dashboard .card i {
      font-size: 40px;
      color: #4bb9a5;
    }
    .dashboard .card h5 {
      margin-top: 10px;
      color: #2b2b2b;
    }
    .search-bar {
      display: flex;
      justify-content: center;
      margin-bottom: 40px;
    }
    .search-bar input {
      width: 100%;
      max-width: 500px;
      padding: 10px;
      border: 2px solid #4bb9a5;
      border-radius: 8px;
    }
    .badge {
  font-size: 0.75rem;
  padding: 5px 10px;
  border-radius: 12px;
}

    @media (max-width: 768px) {
      .dashboard .col-md-4 {
        margin-bottom: 20px;
      }
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <img src="../images/logo/logo_branca.png" alt="Logo"> 
    </div>
    <ul class="nav-links">
      <li><a href="#" onclick="confirmarSaida(event)"><i class="fa-solid fa-right-from-bracket"></i></a></li>
    </ul>
  </nav>

  <div class="dashboard container">
    <h2 class="section-title">Painel do Médico</h2>

 
<div class="row g-4">
  <div class="col-md-4">
    <a href="pacientes.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
      <i class="fa-regular fa-address-book"></i>
        <h5>pacientes</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="consultas.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-calendar-check"></i>
        <h5>Consultas</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="atestados.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-file-medical"></i>
        <h5>Atestados</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="receita.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-prescription-bottle-medical"></i>
        <h5>Receitas Médicas</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="perfil-medico.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-user-doctor"></i>
        <h5>Perfil do Médico</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="relatorio.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-chart-line"></i>
        <h5>Relatórios</h5>
      </div>
    </a>
  </div>
</div>
  <script>
   function confirmarSaida(event) {
  event.preventDefault();
  if (confirm("Deseja realmente sair da conta?")) {
    window.location.href = "../sair.php";
  }
}
  </script>
</body>
</html>
