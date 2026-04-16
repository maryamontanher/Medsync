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

// Buscar receitas dos pacientes menores vinculados ao responsável
$sql = "
    SELECT r.receita_id, r.data_emissao, r.validade, r.validade_final, r.observacoes,
           m.medicos_nome AS medico_nome, m.medicos_especialidade AS medico_especialidade,
           p.pmenor_nome AS paciente_nome, p.pmenor_id AS paciente_id,
           r.tipo_paciente
    FROM receita r
    JOIN medicos m ON r.medico_id = m.medicos_id
    JOIN paciente_menor p ON r.paciente_menor_id = p.pmenor_id
    WHERE p.pmaior_id = ?
    ORDER BY r.data_emissao DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $pmaior_id);
$stmt->execute();
$result = $stmt->get_result();
$receitas = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();

// Função para verificar status da receita
function getStatusReceita($validade, $validade_final = null) {
    $hoje = new DateTime();
    $validade_date = new DateTime($validade);
    
    if ($validade_final) {
        $validade_final_date = new DateTime($validade_final);
        if ($hoje > $validade_final_date) {
            return 'expirada';
        } elseif ($hoje >= $validade_date && $hoje <= $validade_final_date) {
            return 'valida';
        } else {
            return 'pendente';
        }
    } else {
        if ($hoje > $validade_date) {
            return 'expirada';
        } else {
            return 'valida';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receitas dos Dependentes | Medsync</title>
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

        .receita-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .receita-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .receita-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .receita-title {
            color: #0a4e53;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .receita-medico {
            color: #4bb9a5;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .receita-info {
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

        .status-valida {
            background-color: #d4edda;
            color: #155724;
        }

        .status-expirada {
            background-color: #f8d7da;
            color: #721c24;
        }

        .status-pendente {
            background-color: #fff3cd;
            color: #856404;
        }

        .observacoes {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
            border-left: 4px solid #4bb9a5;
        }

        .observacoes h6 {
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
            
            .receita-header {
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
    <a href="painelpaciente.php">
        <i class="fa-solid fa-arrow-left me-2"></i>Voltar ao Painel
    </a>
</nav>

<div class="container">
    <h1 class="page-header"><i class="fas fa-file-prescription me-2"></i>Receitas dos Dependentes</h1>
    
    <div class="responsavel-info">
        <i class="fas fa-info-circle"></i>
        <strong>Responsável legal:</strong> Aqui você pode visualizar e gerenciar as receitas médicas de todos os seus dependentes menores de idade.
    </div>
    
    <div class="filter-bar">
        <select id="statusFilter">
            <option value="todas">Todas as receitas</option>
            <option value="valida">Receitas válidas</option>
            <option value="expirada">Receitas expiradas</option>
            <option value="pendente">Receitas pendentes</option>
        </select>
        
        <select id="pacienteFilter">
            <option value="todos">Todos os dependentes</option>
            <?php
            // Buscar lista de pacientes menores únicos
            $pacientes_unicos = [];
            foreach ($receitas as $receita) {
                $paciente_id = $receita['paciente_id'];
                $paciente_nome = $receita['paciente_nome'];
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
            <option value="antigas">Mais antigas</option>
        </select>
    </div>

    <div class="results-section">
        <h3 class="mb-4" style="color: #0a4e53;">
            <i class="fas fa-list me-2"></i>
            <?php echo count($receitas); ?> receita(s) encontrada(s)
        </h3>
        
        <?php if (empty($receitas)): ?>
            <div class="no-results">
                <i class="fas fa-file-medical"></i>
                <h4>Nenhuma receita encontrada</h4>
                <p>Seus dependentes ainda não possuem receitas médicas cadastradas.</p>
            </div>
        <?php else: ?>
            <div id="receitasContainer">
                <?php foreach ($receitas as $receita): 
                    $status = getStatusReceita($receita['validade'], $receita['validade_final']);
                    $data_emissao = date('d/m/Y', strtotime($receita['data_emissao']));
                    $validade = date('d/m/Y', strtotime($receita['validade']));
                    $validade_final = $receita['validade_final'] ? date('d/m/Y', strtotime($receita['validade_final'])) : null;
                ?>
                    <div class="receita-card" data-status="<?php echo $status; ?>" data-paciente="<?php echo $receita['paciente_id']; ?>">
                        <div class="paciente-info">
                            <i class="fas fa-child me-2"></i>
                            <strong>Paciente:</strong> <?php echo htmlspecialchars($receita['paciente_nome']); ?>
                        </div>
                        
                        <div class="receita-header">
                            <div class="receita-info-left">
                                <div class="receita-title">
                                    <i class="fas fa-prescription me-2"></i>
                                    Receita Médica #<?php echo $receita['receita_id']; ?>
                                </div>
                                <div class="receita-medico">
                                    <i class="fas fa-user-md me-2"></i>
                                    Dr(a). <?php echo htmlspecialchars($receita['medico_nome']); ?>
                                </div>
                                <div class="receita-info">
                                    <i class="fas fa-stethoscope me-2"></i>
                                    <?php echo htmlspecialchars($receita['medico_especialidade']); ?>
                                </div>
                            </div>
                            <div class="receita-info-right">
                                <span class="status-badge status-<?php echo $status; ?>">
                                    <?php 
                                    if ($status === 'valida') echo 'Válida';
                                    elseif ($status === 'expirada') echo 'Expirada';
                                    else echo 'Pendente';
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="receita-details">
                            <div class="receita-info">
                                <i class="fas fa-calendar-day me-2"></i>
                                <strong>Data de Emissão:</strong> <?php echo $data_emissao; ?>
                            </div>
                            <div class="receita-info">
                                <i class="fas fa-calendar-check me-2"></i>
                                <strong>Validade:</strong> 
                                <?php if ($validade_final): ?>
                                    de <?php echo $validade; ?> até <?php echo $validade_final; ?>
                                <?php else: ?>
                                    até <?php echo $validade; ?>
                                <?php endif; ?>
                            </div>
                            
                            <?php if (!empty($receita['observacoes'])): ?>
                                <div class="observacoes">
                                    <h6><i class="fas fa-clipboard-list me-2"></i>Observações Médicas:</h6>
                                    <p style="margin: 0; color: #555;"><?php echo nl2br(htmlspecialchars($receita['observacoes'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="receita-actions mt-3">
                            <button class="btn-imprimir" onclick="imprimirReceita(<?php echo $receita['receita_id']; ?>)">
                                <i class="fas fa-print me-1"></i>Imprimir Receita
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
        const container = document.getElementById('receitasContainer');
        const receitas = Array.from(container.querySelectorAll('.receita-card'));
        
        receitas.sort((a, b) => {
            const idA = parseInt(a.querySelector('.receita-title').textContent.match(/#(\d+)/)[1]);
            const idB = parseInt(b.querySelector('.receita-title').textContent.match(/#(\d+)/)[1]);
            
            return ordenar === 'recentes' ? idB - idA : idA - idB;
        });
        
        receitas.forEach(receita => container.appendChild(receita));
    });

    function aplicarFiltros() {
        const status = document.getElementById('statusFilter').value;
        const paciente = document.getElementById('pacienteFilter').value;
        const receitas = document.querySelectorAll('.receita-card');
        let visibleCount = 0;
        
        receitas.forEach(receita => {
            const receitaStatus = receita.getAttribute('data-status');
            const receitaPaciente = receita.getAttribute('data-paciente');
            
            const statusMatch = status === 'todas' || receitaStatus === status;
            const pacienteMatch = paciente === 'todos' || receitaPaciente === paciente;
            
            if (statusMatch && pacienteMatch) {
                receita.style.display = '';
                visibleCount++;
            } else {
                receita.style.display = 'none';
            }
        });
        
        // Atualiza contador
        document.querySelector('.results-section h3').innerHTML = 
            `<i class="fas fa-list me-2"></i>${visibleCount} receita(s) encontrada(s)`;
    }

    // Função para imprimir receita
    function imprimirReceita(receitaId) {
        if (confirm('Deseja imprimir esta receita?')) {
            alert(`Função de impressão para receita #${receitaId} será implementada aqui.`);
        }
    }

    // Mostrar apenas receitas válidas por padrão
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('statusFilter').value = 'valida';
        aplicarFiltros();
    });
</script>

</body>
</html>