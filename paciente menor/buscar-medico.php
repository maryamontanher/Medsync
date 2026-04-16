<?php
session_start();

if (!isset($_SESSION['pmaior_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Buscar médicos com filtros
$especialidade = $_GET['especialidade'] ?? '';
$nome = $_GET['nome'] ?? '';

$sql = "SELECT medicos_id, medicos_nome, medicos_especialidade, medicos_crm, medicos_uf_crm 
        FROM medicos 
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($especialidade)) {
    $sql .= " AND medicos_especialidade = ?";
    $params[] = $especialidade;
    $types .= "s";
}

if (!empty($nome)) {
    $sql .= " AND medicos_nome LIKE ?";
    $params[] = "%$nome%";
    $types .= "s";
}

$sql .= " ORDER BY medicos_nome";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$medicos = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

// Buscar especialidades que existem no banco (apenas para referência)
$sql_especialidades_existentes = "SELECT DISTINCT medicos_especialidade FROM medicos ORDER BY medicos_especialidade";
$result_especialidades_existentes = $conn->query($sql_especialidades_existentes);
$especialidades_existentes = [];
while ($row = $result_especialidades_existentes->fetch_assoc()) {
    $especialidades_existentes[] = $row['medicos_especialidade'];
}

$conn->close();

$especialidades_lista = [
    "Administração em Saúde",
    "Alergia e Imunologia",
    "Alergia e Imunologia Pediátrica",
    "Andrologia",
    "Auditoria Médica",
    "Cardiologia",
    "Cardiologia Pediátrica",
    "Cirurgia Bariátrica",
    "Cirurgia Bucomaxilofacial",
    "Cirurgia Cardiovascular",
    "Cirurgia de Cabeça e Pescoço",
    "Cirurgia do Aparelho Digestivo",
    "Cirurgia Geral",
    "Cirurgia Oncológica",
    "Cirurgia Plástica",
    "Cirurgia Torácica",
    "Cirurgia Vascular",
    "Clínica Médica (Medicina Interna)",
    "Dor e Cuidados Paliativos",
    "Endocrinologia",
    "Endocrinologia Ginecológica",
    "Endocrinologia Pediátrica",
    "Gastroenterologia",
    "Gastroenterologia Pediátrica",
    "Genética Médica",
    "Geriatria",
    "Ginecologia",
    "Hematologia e Hemoterapia",
    "Hepatologia",
    "Infectologia",
    "Mastologia",
    "Medicina da Dor",
    "Medicina de Família e Comunidade",
    "Medicina de Urgência / Emergência",
    "Medicina do Esporte",
    "Medicina do Sono",
    "Medicina do Trabalho",
    "Medicina Física e Reabilitação (Fisiatria)",
    "Medicina Intensiva (UTI)",
    "Medicina Legal",
    "Medicina Legal e Perícia Médica",
    "Medicina Nuclear",
    "Medicina Preventiva e Social",
    "Medicina Psicossomática",
    "Nefrologia",
    "Nefrologia Pediátrica",
    "Neonatologia",
    "Neurocirurgia",
    "Neurologia",
    "Neurologia Pediátrica",
    "Obstetrícia",
    "Oftalmologia",
    "Oncologia Clínica",
    "Ortopedia e Traumatologia",
    "Otorrinolaringologia",
    "Patologia",
    "Patologia Clínica / Medicina Laboratorial",
    "Pediatria",
    "Pediatria Intensiva",
    "Pneumologia",
    "Pneumologia Pediátrica",
    "Proctologia (Coloproctologia)",
    "Psiquiatria",
    "Psiquiatria da Infância e Adolescência",
    "Radiologia",
    "Radioterapia",
    "Reprodução Assistida",
    "Reumatologia",
    "Telemedicina",
    "Tomografia e Ressonância",
    "Transplantes",
    "Ultrassonografia",
    "Urologia"
];
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Médicos | Medsync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 70px 0 0 0;
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

        .navbar img {
            width: 120px;
            background: no-repeat left center / contain;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: color 0.3s;
        }

        .navbar a:hover {
            color: rgb(50, 97, 81);
        }

        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 15px;
        }

        .page-header {
            text-align: center;
            color: #0a4e53;
            margin-bottom: 30px;
            font-weight: 400;
            font-size: 28px;
        }

        .search-section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-label {
            font-weight: 600;
            color: #0a4e53;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #7e8786;
            border-radius: 5px;
            font-size: 15px;
            font-family: 'Roboto', sans-serif;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4bb9a5;
            box-shadow: 0 0 0 0.2rem rgba(75, 185, 165, 0.25);
            outline: none;
        }

        .btn-success {
            background-color: #4bb9a5;
            border: none;
            color: #fff;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            height: 45px;
        }

        .btn-success:hover {
            background-color: #3c9181;
        }

        .btn-outline-secondary {
            border: 2px solid #4bb9a5;
            background-color: transparent;
            color: #4bb9a5;
            padding: 12px 25px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            height: 45px;
            line-height: 20px;
        }

        .btn-outline-secondary:hover {
            background-color: #4bb9a5;
            color: white;
        }

        .results-section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .doctor-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .doctor-name {
            color: #0a4e53;
            font-weight: 600;
            font-size: 1.25rem;
            margin-bottom: 5px;
        }

        .doctor-specialty {
            color: #4bb9a5;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .doctor-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .no-results {
            text-align: center;
            color: #777;
            padding: 40px 0;
        }

        .no-results i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ccc;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .filter-col {
            flex: 1;
        }

        .minor-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
            text-align: center;
        }

        .minor-warning i {
            color: #f39c12;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            
            .container {
                margin: 20px auto;
                padding: 0 10px;
            }
            
            .search-section, .results-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <img src="../images/logo/logo_branca.png" alt="Logo">
    </div>
    <a href="painelpaciente-menor.php">
        <i class="fa-solid fa-arrow-left me-2"></i>Voltar ao Painel
    </a>
</nav>

<div class="container">
    <h1 class="page-header"><i class="fas fa-search me-2"></i>Buscar Médicos</h1>
    
    <div class="search-section">
        <form method="GET" action="buscar-medico.php">
            <div class="filter-row">
                <div class="filter-col">
                    <label for="nome" class="form-label">Nome do Médico</label>
                    <input type="text" class="form-control" id="nome" name="nome" 
                           value="<?php echo htmlspecialchars($nome); ?>" 
                           placeholder="Digite o nome do médico...">
                </div>
                
                <div class="filter-col">
                    <label for="especialidade" class="form-label">Especialidade</label>
                    <select class="form-select" id="especialidade" name="especialidade">
                        <option value="">Todas as especialidades</option>
                        <?php foreach ($especialidades_lista as $esp): ?>
                            <option value="<?php echo htmlspecialchars($esp); ?>" 
                                <?php echo ($especialidade == $esp) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($esp); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-flex gap-3">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-search me-2"></i>Buscar Médicos
                </button>
                <a href="buscar-medico.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times me-2"></i>Limpar Filtros
                </a>
            </div>
        </form>
    </div>

    <div class="results-section">
        <div class="minor-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Paciente menor de idade:</strong> Para agendar consultas, é necessário que seu responsável legal realize o agendamento através da conta dele.
        </div>
        
        <h3 class="mb-4" style="color: #0a4e53;">
            <i class="fas fa-list me-2"></i>
            <?php echo count($medicos); ?> médico(s) encontrado(s)
        </h3>
        
        <?php if (empty($medicos)): ?>
            <div class="no-results">
                <i class="fas fa-user-md"></i>
                <h4>Nenhum médico encontrado</h4>
                <p>
                    <?php if (!empty($especialidade) && !in_array($especialidade, $especialidades_existentes)): ?>
                        A especialidade "<?php echo htmlspecialchars($especialidade); ?>" não possui médicos cadastrados no momento.
                    <?php else: ?>
                        Tente ajustar os filtros de busca para encontrar o profissional desejado.
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($medicos as $medico): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="doctor-card">
                            <div class="doctor-name">
                                <i class="fas fa-user-md me-2"></i>
                                Dr(a). <?php echo htmlspecialchars($medico['medicos_nome']); ?>
                            </div>
                            <div class="doctor-specialty">
                                <i class="fas fa-stethoscope me-2"></i>
                                <?php echo htmlspecialchars($medico['medicos_especialidade']); ?>
                            </div>
                            <div class="doctor-info">
                                <i class="fas fa-id-card me-2"></i>
                                CRM: <?php echo htmlspecialchars($medico['medicos_crm']); ?>/<?php echo htmlspecialchars($medico['medicos_uf_crm']); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>