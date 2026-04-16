<?php
session_start();

if (!isset($_SESSION['pmaior_id'])) {
    header("Location: login.php");
    exit();
}

$pmaior_id = $_SESSION['pmaior_id'];

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

// Buscar atestados dos pacientes menores vinculados ao responsável
$sql = "
    SELECT a.atestado_id, a.data_emissao, a.dias_afastamento, a.descricao,
           m.medicos_nome AS medico_nome, m.medicos_especialidade AS medico_especialidade,
           p.pmenor_nome AS paciente_nome, p.pmenor_id AS paciente_id
    FROM atestado a
    JOIN medicos m ON a.medico_id = m.medicos_id
    JOIN paciente_menor p ON a.paciente_menor_id = p.pmenor_id
    WHERE p.pmaior_id = ?
    ORDER BY a.data_emissao DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pmaior_id);
$stmt->execute();
$result = $stmt->get_result();
$atestados = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Função para verificar status do atestado
function getStatusAtestado($data_emissao, $dias_afastamento) {
    $hoje = new DateTime();
    $emissao = new DateTime($data_emissao);
    $validade = clone $emissao;
    $validade->modify("+{$dias_afastamento} days");
    
    if ($hoje > $validade) {
        return 'expirado';
    } elseif ($hoje >= $emissao && $hoje <= $validade) {
        return 'valido';
    } else {
        return 'pendente';
    }
}

// Função para calcular data de retorno
function getDataRetorno($data_emissao, $dias_afastamento) {
    $emissao = new DateTime($data_emissao);
    $retorno = clone $emissao;
    $retorno->modify("+{$dias_afastamento} days");
    return $retorno->format('d/m/Y');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atestados dos Dependentes | Medsync</title>
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

        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links li {
            margin: 0;
        }

        .container {
            max-width: 1000px;
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

        .filter-bar {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .filter-bar select {
            padding: 10px;
            border: 1px solid #7e8786;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Roboto', sans-serif;
            min-width: 180px;
        }

        .filter-bar select:focus {
            border-color: #4bb9a5;
            outline: none;
        }

        .results-section {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }

        .atestado-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .atestado-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .atestado-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .atestado-title {
            color: #0a4e53;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .atestado-medico {
            color: #4bb9a5;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .atestado-info {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .paciente-info {
            background-color: #e8f5f2;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 4px solid #4bb9a5;
        }

        .paciente-info strong {
            color: #0a4e53;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-valido {
            background-color: #d4edda;
            color: #155724;
        }

        .status-expirado {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }

        .afastamento-info {
            background-color: #e3f7f5;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #4bb9a5;
        }

        .afastamento-info h6 {
            color: #0a4e53;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .descricao {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #6c757d;
        }

        .descricao h6 {
            color: #0a4e53;
            margin-bottom: 8px;
            font-weight: 600;
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

        .btn-imprimir {
            background-color: #4bb9a5;
            border: none;
            color: #fff;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
        }

        .btn-imprimir:hover {
            background-color: #3c9181;
        }

        .btn-download {
            background-color: #6c757d;
            border: none;
            color: #fff;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .btn-download:hover {
            background-color: #5a6268;
        }

        .responsavel-info {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }

        .responsavel-info i {
            color: #f39c12;
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .container {
                margin: 20px auto;
                padding: 0 10px;
            }
            
            .results-section {
                padding: 20px;
            }
            
            .atestado-header {
                flex-direction: column;
                gap: 10px;
            }
            
            .atestado-actions {
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .btn-download {
                margin-left: 0;
            }

            .nav-links {
                flex-direction: column;
                gap: 10px;
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
        <li><a href="atestados.php"><i class="fas fa-file-medical me-1"></i>Meus Atestados</a></li>
        <li>
            <a href="painelpaciente.php">
                <i class="fa-solid fa-arrow-left me-2"></i>Voltar ao Painel
            </a>
        </li>
    </ul>
</nav>

<div class="container">
    <h1 class="page-header"><i class="fas fa-file-medical me-2"></i>Atestados dos Dependentes</h1>
    
    <div class="responsavel-info">
        <i class="fas fa-info-circle"></i>
        <strong>Responsável legal:</strong> Aqui você pode visualizar e gerenciar os atestados médicos de todos os seus dependentes menores de idade.
    </div>
    
    <div class="filter-bar">
        <select id="statusFilter">
            <option value="todos">Todos os atestados</option>
            <option value="valido">Atestados válidos</option>
            <option value="expirado">Atestados expirados</option>
            <option value="pendente">Atestados pendentes</option>
        </select>
        
        <select id="pacienteFilter">
            <option value="todos">Todos os dependentes</option>
            <?php
            // Buscar lista de pacientes menores únicos
            $pacientes_unicos = [];
            foreach ($atestados as $atestado) {
                $paciente_id = $atestado['paciente_id'];
                $paciente_nome = $atestado['paciente_nome'];
                if (!isset($pacientes_unicos[$paciente_id])) {
                    $pacientes_unicos[$paciente_id] = $paciente_nome;
                }
            }
            
            foreach ($pacientes_unicos as $id => $nome): ?>
                <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($nome); ?></option>
            <?php endforeach; ?>
        </select>
        
        <select id="ordenarFilter">
            <option value="recentes">Mais recentes</option>
            <option value="antigos">Mais antigos</option>
        </select>
    </div>

    <div class="results-section">
        <h3 class="mb-4" style="color: #0a4e53;">
            <i class="fas fa-list me-2"></i>
            <?php echo count($atestados); ?> atestado(s) encontrado(s)
        </h3>
        
        <?php if (empty($atestados)): ?>
            <div class="no-results">
                <i class="fas fa-file-medical-alt"></i>
                <h4>Nenhum atestado encontrado</h4>
                <p>Seus dependentes ainda não possuem atestados médicos cadastrados.</p>
            </div>
        <?php else: ?>
            <div id="atestadosContainer">
                <?php foreach ($atestados as $atestado): 
                    $status = getStatusAtestado($atestado['data_emissao'], $atestado['dias_afastamento']);
                    $data_emissao = date('d/m/Y', strtotime($atestado['data_emissao']));
                    $data_retorno = getDataRetorno($atestado['data_emissao'], $atestado['dias_afastamento']);
                ?>
                    <div class="atestado-card" data-status="<?php echo $status; ?>" data-paciente="<?php echo $atestado['paciente_id']; ?>">
                        <div class="paciente-info">
                            <i class="fas fa-child me-2"></i>
                            <strong>Paciente:</strong> <?php echo htmlspecialchars($atestado['paciente_nome']); ?>
                        </div>
                        
                        <div class="atestado-header">
                            <div class="atestado-info-left">
                                <div class="atestado-title">
                                    <i class="fas fa-file-medical me-2"></i>
                                    Atestado Médico #<?php echo $atestado['atestado_id']; ?>
                                </div>
                                <div class="atestado-medico">
                                    <i class="fas fa-user-md me-2"></i>
                                    Dr(a). <?php echo htmlspecialchars($atestado['medico_nome']); ?>
                                </div>
                                <div class="atestado-info">
                                    <i class="fas fa-stethoscope me-2"></i>
                                    <?php echo htmlspecialchars($atestado['medico_especialidade']); ?>
                                </div>
                            </div>
                            <div class="atestado-info-right">
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php 
                                    if ($status === 'valido') echo 'Válido';
                                    elseif ($status === 'expirado') echo 'Expirado';
                                    else echo 'Pendente';
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="atestado-details">
                            <div class="atestado-info">
                                <i class="fas fa-calendar-day me-2"></i>
                                <strong>Data de Emissão:</strong> <?php echo $data_emissao; ?>
                            </div>
                            
                            <div class="afastamento-info">
                                <h6><i class="fas fa-calendar-times me-2"></i>Período de Afastamento</h6>
                                <div class="atestado-info">
                                    <strong>Dias de afastamento:</strong> <?php echo $atestado['dias_afastamento']; ?> dias
                                </div>
                                <div class="atestado-info">
                                    <strong>Data de retorno:</strong> <?php echo $data_retorno; ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($atestado['descricao'])): ?>
                                <div class="descricao">
                                    <h6><i class="fas fa-clipboard-list me-2"></i>Descrição do Atestado:</h6>
                                    <p style="margin: 0; color: #555;"><?php echo nl2br(htmlspecialchars($atestado['descricao'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="atestado-actions mt-3">
                            <button class="btn-imprimir" onclick="imprimirAtestado(<?php echo $atestado['atestado_id']; ?>)">
                                <i class="fas fa-print me-1"></i>Imprimir Atestado
                            </button>
                            <button class="btn-download" onclick="downloadAtestado(<?php echo $atestado['atestado_id']; ?>)">
                                <i class="fas fa-download me-1"></i>Download PDF
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Filtro por status
    document.getElementById('statusFilter').addEventListener('change', function() {
        aplicarFiltros();
    });

    // Filtro por paciente
    document.getElementById('pacienteFilter').addEventListener('change', function() {
        aplicarFiltros();
    });

    // Ordenação
    document.getElementById('ordenarFilter').addEventListener('change', function() {
        const ordenar = this.value;
        const container = document.getElementById('atestadosContainer');
        const atestados = Array.from(container.querySelectorAll('.atestado-card'));
        
        atestados.sort((a, b) => {
            const idA = parseInt(a.querySelector('.atestado-title').textContent.match(/#(\d+)/)[1]);
            const idB = parseInt(b.querySelector('.atestado-title').textContent.match(/#(\d+)/)[1]);
            
            return ordenar === 'recentes' ? idB - idA : idA - idB;
        });
        
        // Reorganiza no container
        atestados.forEach(atestado => container.appendChild(atestado));
    });

    function aplicarFiltros() {
        const status = document.getElementById('statusFilter').value;
        const paciente = document.getElementById('pacienteFilter').value;
        const atestados = document.querySelectorAll('.atestado-card');
        let visibleCount = 0;
        
        atestados.forEach(atestado => {
            const atestadoStatus = atestado.getAttribute('data-status');
            const atestadoPaciente = atestado.getAttribute('data-paciente');
            
            const statusMatch = status === 'todos' || atestadoStatus === status;
            const pacienteMatch = paciente === 'todos' || atestadoPaciente === paciente;
            
            if (statusMatch && pacienteMatch) {
                atestado.style.display = '';
                visibleCount++;
            } else {
                atestado.style.display = 'none';
            }
        });
        
        // Atualiza contador
        document.querySelector('.results-section h3').innerHTML = 
            `<i class="fas fa-list me-2"></i>${visibleCount} atestado(s) encontrado(s)`;
    }

    // Função para imprimir atestado
    function imprimirAtestado(atestadoId) {
        if (confirm('Deseja imprimir este atestado?')) {
            // Simulação da função de impressão
            alert(`Função de impressão para atestado #${atestadoId} será implementada aqui.`);
            
        }
    }

    // Função para download do atestado
    function downloadAtestado(atestadoId) {
        if (confirm('Deseja baixar este atestado em PDF?')) {
            // Simulação da função de download
            alert(`Download do atestado #${atestadoId} em PDF será implementado aqui.`);
            
        }
    }

    // Filtro inicial - mostrar apenas atestados válidos por padrão
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('statusFilter').value = 'valido';
        aplicarFiltros();
    });
</script>

</body>
</html>