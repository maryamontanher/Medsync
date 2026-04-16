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

// Buscar atestados emitidos pelo médico
$sql = "
    SELECT a.atestado_id, a.data_emissao, a.dias_afastamento, a.descricao,
           COALESCE(pm.pmaior_nome, pme.pmenor_nome) AS paciente_nome,
           COALESCE(pm.pmaior_cpf, pme.pmenor_cpf) AS paciente_cpf,
           m.medicos_nome, m.medicos_crm
    FROM atestado a
    LEFT JOIN paciente_maior pm ON a.paciente_maior_id = pm.pmaior_id
    LEFT JOIN paciente_menor pme ON a.paciente_menor_id = pme.pmenor_id
    INNER JOIN medicos m ON a.medico_id = m.medicos_id
    WHERE a.medico_id = ?
    ORDER BY a.data_emissao DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();
$atestados = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Atestados | Medsync</title>
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
    .btn-imprimir {
      margin-top: 10px;
    }
    #searchInput {
      margin-bottom: 20px;
    }
    .text-success {
    --bs-text-opacity: 1;
    color: #0a4e53 !important;
}
.btn-outline-success {
    --bs-btn-color: #0a4e53;
    --bs-btn-border-color: #0a4e53;
    --bs-btn-hover-color: #fff;
    --bs-btn-hover-bg: #0a4e53;
    --bs-btn-hover-border-color: #0a4e53;
    --bs-btn-focus-shadow-rgb: 25, 135, 84;
    --bs-btn-active-color: #fff;
    --bs-btn-active-bg:#0a4e53;
    --bs-btn-active-border-color:#0a4e53;
    --bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
    --bs-btn-disabled-color: #0a4e53;
    --bs-btn-disabled-bg: transparent;
    --bs-btn-disabled-border-color: #0a4e53;
    --bs-gradient: none;
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

  </style>
</head>
<body>
<nav class="navbar">
  <img src="../images/logo/logo_branca.png" alt="Logo" />
  <a href="painelmed.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>

<div class="container">
  <h2 class="mb-4 text-center text-success">Atestados Emitidos</h2>

<div class="container mt-4">
  <input type="text" id="searchInput" class="form-control" placeholder="Pesquisar por nome do paciente...">
  
  <div id="atestadosContainer">
    <?php foreach ($atestados as $atestado): ?>
      <div class="card atestado-card" data-nome="<?= strtolower($atestado['paciente_nome']) ?>">
        <h5><?= htmlspecialchars($atestado['paciente_nome']) ?></h5>
        <p><strong>Data de Emissão:</strong> <?= date('d/m/Y', strtotime($atestado['data_emissao'])) ?></p>
        <p><strong>Dias de Afastamento:</strong> <?= htmlspecialchars($atestado['dias_afastamento']) ?> dias</p>
        <p><strong>Descrição:</strong><br><?= nl2br(htmlspecialchars($atestado['descricao'])) ?></p>
        <button class="btn btn-outline-success btn-sm btn-imprimir" 
                onclick="imprimirAtestado(<?= htmlspecialchars(json_encode($atestado)) ?>)">
          Imprimir
        </button>
      </div>
    <?php endforeach; ?>

    <?php if (count($atestados) === 0): ?>
      <div class="text-center text-muted mt-5">
  <i class="fa-solid fa-file-medical fa-4x mb-3"></i>
  <p class="fs-5">Nenhum atestado encontrado.</p>
</div>

</div>

    <?php endif; ?>
  </div>
</div>

<script>
function imprimirAtestado(atestado) {
    const dataEmissao = new Date(atestado.data_emissao).toLocaleDateString('pt-BR');
    
    const conteudo = `
        <div class="documento-impressao">
            <div class="documento-header">
                <div class="documento-title">ATESTADO MÉDICO</div>
                <div class="documento-subtitle">MedSync - Sistema de Gestão Médica</div>
            </div>
            
            <div class="documento-info">
                <div class="info-row">
                    <span class="info-label">Paciente:</span>
                    <span class="info-value">${atestado.paciente_nome}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">CPF:</span>
                    <span class="info-value">${atestado.paciente_cpf || 'Não informado'}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Data de Emissão:</span>
                    <span class="info-value">${dataEmissao}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Dias de Afastamento:</span>
                    <span class="info-value">${atestado.dias_afastamento} dias</span>
                </div>
            </div>
            
            ${atestado.descricao ? `
            <div class="documento-content">
                ${atestado.descricao}
            </div>
            ` : ''}
            
            <div class="documento-footer">
                <div class="assinatura">
                    <div class="assinatura-line"></div>
                    <div class="assinatura-text">Dr. ${atestado.medicos_nome}</div>
                    <div class="assinatura-text">CRM: ${atestado.medicos_crm}</div>
                </div>
            </div>
        </div>
    `;
    
    const janela = window.open('', '_blank');
    janela.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Atestado Médico - ${atestado.paciente_nome}</title>
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
    
    setTimeout(() => {
        janela.print();
    }, 250);
}

// Filtro por nome
const input = document.getElementById('searchInput');
input.addEventListener('input', () => {
  const termo = input.value.toLowerCase();
  const cards = document.querySelectorAll('.atestado-card');

  cards.forEach(card => {
    const nome = card.getAttribute('data-nome');
    card.style.display = nome.includes(termo) ? '' : 'none';
  });
});
</script>

</body>
</html>