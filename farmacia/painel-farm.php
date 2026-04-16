
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
  <title>Medsync | Painel do Farmácia</title>
  <style>
    .dashboard {
      padding: 100px 20px 40px;
      max-width: 1200px;
      margin: auto;
    }
    .dashboard .section-title {
      font-size: 2rem;
      color: #157c5d;
      margin-bottom: 60px;
      text-align: center;
    }
    .dashboard .card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s;
      margin-top: 0px;
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
      <li><a href="perfil_farmacia.php">Perfil</a></li>
      <li><a href="#" onclick="confirmarSaida(event)"><i class="fa-solid fa-right-from-bracket"></i></a></li>
    </ul>
  </nav>

  <div class="dashboard container">
    <h2 class="section-title">Painel da Farmácia</h2>

 
<div class="row g-4">
  <div class="col-md-4">
    <a href="cadastro_remedios.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-circle-plus"></i>
        <h5>Cadastro de Remédios</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="receitas-farm.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-file-medical"></i>
        <h5>Receitas Médicas</h5>
      </div>
    </a>
  </div>
  <div class="col-md-4">
    <a href="tabela_remedios.php" style="text-decoration: none;">
      <div class="card p-4 text-center">
        <i class="fa-solid fa-prescription-bottle-medical"></i>
        <h5>Estoque</h5>
      </div>
    </a>
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
