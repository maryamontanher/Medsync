<?php
session_start();
require_once '../conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['medico_id'])) {
    header("Location: ../login.php");
    exit();
}

// Buscar todos os pacientes maiores
$query_maiores = "SELECT pmaior_id, pmaior_foto, pmaior_nome, pmaior_email, pmaior_telefone 
                 FROM paciente_maior 
                 ORDER BY pmaior_nome";
$result_maiores = mysqli_query($conn, $query_maiores);
$pacientes_maiores = mysqli_fetch_all($result_maiores, MYSQLI_ASSOC);

// Buscar todos os pacientes menores com seus responsáveis
$query_menores = "SELECT pm.pmenor_id, pm.pmenor_foto, pm.pmenor_nome, pm.pmenor_email, 
                         pm.pmenor_telefone, pma.pmaior_nome as responsavel_nome
                  FROM paciente_menor pm
                  INNER JOIN paciente_maior pma ON pm.pmaior_id = pma.pmaior_id
                  ORDER BY pm.pmenor_nome";
$result_menores = mysqli_query($conn, $query_menores);
$pacientes_menores = mysqli_fetch_all($result_menores, MYSQLI_ASSOC);
// Função para verificar se arquivo existe e gerar URL com timestamp
function getPhotoUrl($foto, $tipo, $nome) {
    if ($foto) {
        // Caminhos corrigidos com a barra entre pasta e arquivo
        $path = $tipo == 'maior' ? '../paciente/uploads/pacientes/' . $foto : '../paciente menor/uploads/pacientes/' . $foto;
        
        // Verificar se o arquivo existe
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/medsync' . str_replace('..', '', $path);
        
        if (file_exists($full_path)) {
            // Adiciona timestamp para evitar cache
            $timestamp = filemtime($full_path);
            return $path . '?t=' . $timestamp;
        } else {
            // Debug: mostra qual arquivo não foi encontrado
            error_log("Arquivo não encontrado: " . $full_path);
        }
    }
    
    // Retorna placeholder SVG local com a primeira letra do nome
    $initial = strtoupper(substr($nome, 0, 1));
    $color = $tipo == 'maior' ? '#0a4e53' : '#4bb9a5';
    $textColor = '#ffffff';
    
    // Cria um SVG como placeholder
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
            <rect width="80" height="80" fill="'.$color.'"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="32" font-weight="bold" fill="'.$textColor.'">'.$initial.'</text>
            </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pacientes | MedSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px 40px;
        }
        .text-success {
            color: #0a4e53 !important;
        }
        .btn-success {
            background-color: #0a4e53;
            border-color: #0a4e53;
            color: #fff;
        }
        .btn-success:hover {
            background-color: #08403a;
            border-color: #08403a;
        }
        .patient-card {
            transition: transform 0.2s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .patient-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .patient-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #4bb9a5;
        }
        .badge.badge-success {
            background-color: #0a4e53;
            color: white;
            padding: 0.25em 0.6em;
            border-radius: 0.25rem;
            font-size: 0.75em;
        }
        .badge.badge-info {
            background-color: #4bb9a5;
            color: white;
            padding: 0.25em 0.6em;
            border-radius: 0.25rem;
            font-size: 0.75em;
        }
        .section-title {
            color: #0a4e53;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #4bb9a5;
        }
        .filter-bar {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar input[type="text"] {
            flex: 1;
            min-width: 300px;
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border: 1px solid #ced4da;
            border-radius: 0.375rem;
            border-color: #4bb9a5;
        }
        .card-title {
            color: #0a4e53;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .card-text {
            color: #555;
            margin-bottom: 0.5rem;
        }
        .btn-primary {
            background-color: #0a4e53;
            border-color: #0a4e53;
        }
        .btn-primary:hover {
            background-color: #08403a;
            border-color: #08403a;
        }
        .btn-outline-secondary {
            color: #0a4e53;
            border-color: #0a4e53;
        }
        .btn-outline-secondary:hover {
            background-color: #0a4e53;
            color: white;
        }
        .no-patients {
            text-align: center;
            color: #777;
            font-size: 1.2rem;
            padding: 40px 0;
        }
        .modal-header {
            background-color: transparent;
            border-bottom: none;
        }
        .modal-title {
            color: #0a4e53;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Navbar igual à página de consultas -->
    <nav class="navbar">
        <img src="../images/logo/logo_branca.png" alt="Logo" />
        <a href="painelmed.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
    </nav>

    <div class="container">
        <h2 class="mb-4 text-center text-success">Lista de Pacientes</h2>

        <!-- Filtros -->
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="Pesquisar por nome do paciente..." />
        </div>

        <!-- Pacientes Maiores de Idade -->
        <div class="row mb-5">
            <div class="col-12">
                <h4 class="section-title">Pacientes Maiores de Idade</h4>
                <div class="row" id="pacientesMaiores">
                    <?php if(empty($pacientes_maiores)): ?>
                        <div class="col-12">
                            <div class="no-patients">
                                <i class="fas fa-users fa-3x mb-3"></i>
                                <p>Nenhum paciente maior de idade cadastrado.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($pacientes_maiores as $paciente): ?>
                            <div class="col-md-6 col-lg-4 mb-4 paciente-item">
                                <div class="card patient-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                           <img src="<?php echo getPhotoUrl($paciente['pmaior_foto'], 'maior', $paciente['pmaior_nome']); ?>" 
     class="patient-photo me-3" 
     alt="Foto de <?php echo htmlspecialchars($paciente['pmaior_nome']); ?>"
     loading="lazy"
     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiB2aWV3Qm94PSIwIDAgODAgODAiPjxyZWN0IHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgZmlsbD0iIzZjNzU3ZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMzIiIGZpbGw9IndoaXRlIj4/PC90ZXh0Pjwvc3ZnPg=='"><div class="flex-grow-1">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($paciente['pmaior_nome']); ?></h5>
                                                <span class="badge badge-success">Maior de idade</span>
                                            </div>
                                        </div>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-phone me-2 text-success"></i><?php echo htmlspecialchars($paciente['pmaior_telefone']); ?>
                                        </p>
                                        <p class="card-text mb-3">
                                            <i class="fas fa-envelope me-2 text-success"></i><?php echo htmlspecialchars($paciente['pmaior_email']); ?>
                                        </p>
                                        <button class="btn btn-primary btn-sm w-100 ver-perfil-btn" 
                                                data-id="<?php echo $paciente['pmaior_id']; ?>" 
                                                data-tipo="maior">
                                            <i class="fas fa-eye me-1"></i>Ver Perfil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Pacientes Menores de Idade -->
        <div class="row">
            <div class="col-12">
                <h4 class="section-title">Pacientes Menores de Idade</h4>
                <div class="row" id="pacientesMenores">
                    <?php if(empty($pacientes_menores)): ?>
                        <div class="col-12">
                            <div class="no-patients">
                                <i class="fas fa-child fa-3x mb-3"></i>
                                <p>Nenhum paciente menor de idade cadastrado.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach($pacientes_menores as $paciente): ?>
                            <div class="col-md-6 col-lg-4 mb-4 paciente-item">
                                <div class="card patient-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                           <img src="<?php echo getPhotoUrl($paciente['pmenor_foto'], 'menor', $paciente['pmenor_nome']); ?>" 
     class="patient-photo me-3" 
     alt="Foto de <?php echo htmlspecialchars($paciente['pmenor_nome']); ?>"
     loading="lazy"
     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjgwIiBoZWlnaHQ9IjgwIiB2aWV3Qm94PSIwIDAgODAgODAiPjxyZWN0IHdpZHRoPSI4MCIgaGVpZ2h0PSI4MCIgZmlsbD0iIzZjNzU3ZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMzIiIGZpbGw9IndoaXRlIj4/PC90ZXh0Pjwvc3ZnPg=='"> <div class="flex-grow-1">
                                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($paciente['pmenor_nome']); ?></h5>
                                                <span class="badge badge-info">Menor de idade</span>
                                            </div>
                                        </div>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-phone me-2 text-success"></i><?php echo htmlspecialchars($paciente['pmenor_telefone']); ?>
                                        </p>
                                        <p class="card-text mb-1">
                                            <i class="fas fa-envelope me-2 text-success"></i><?php echo htmlspecialchars($paciente['pmenor_email']); ?>
                                        </p>
                                        <p class="card-text mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-user me-1 text-success"></i>Responsável: <?php echo htmlspecialchars($paciente['responsavel_nome']); ?>
                                            </small>
                                        </p>
                                        <button class="btn btn-primary btn-sm w-100 ver-perfil-btn" 
                                                data-id="<?php echo $paciente['pmenor_id']; ?>" 
                                                data-tipo="menor">
                                            <i class="fas fa-eye me-1"></i>Ver Perfil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Perfil do Paciente -->
    <div class="modal fade" id="perfilModal" tabindex="-1" aria-labelledby="perfilModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="perfilModalLabel">Perfil do Paciente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Busca em tempo real
            $('#searchInput').on('keyup', function() {
                var searchText = $(this).val().toLowerCase();
                var hasVisiblePatients = false;
                
                $('.paciente-item').each(function() {
                    var patientName = $(this).find('.card-title').text().toLowerCase();
                    if (patientName.indexOf(searchText) === -1) {
                        $(this).hide();
                    } else {
                        $(this).show();
                        hasVisiblePatients = true;
                    }
                });

                // Mostra mensagem se não encontrar pacientes
                $('.no-patients').hide();
                if (!hasVisiblePatients) {
                    $('#pacientesMaiores, #pacientesMenores').append(
                        '<div class="col-12"><div class="no-patients"><p>Nenhum paciente encontrado.</p></div></div>'
                    );
                }
            });

            // Abrir modal de perfil
            $('.ver-perfil-btn').on('click', function() {
                var pacienteId = $(this).data('id');
                var tipoPaciente = $(this).data('tipo');
                
                $('#modalBody').html(`
                    <div class="text-center">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-2">Carregando perfil...</p>
                    </div>
                `);
                
                $('#perfilModal').modal('show');
                
                // Carregar conteúdo via AJAX
                $.ajax({
                    url: 'perfil_paciente.php',
                    type: 'GET',
                    data: {
                        id: pacienteId,
                        tipo: tipoPaciente,
                        t: new Date().getTime()
                    },
                    success: function(response) {
                        $('#modalBody').html(response);
                    },
                    error: function() {
                        $('#modalBody').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Erro ao carregar perfil do paciente.
                            </div>
                        `);
                    }
                });
            });
        });
    // No final do pacientes.php, adicione este código
$(document).on('submit', '#formAnamnese', function(e) {
    e.preventDefault();
    
    var formData = $(this).serialize();
    var urlParams = new URLSearchParams(window.location.search);
    
    // Adicionar id e tipo aos dados do formulário
    formData += '&id=' + urlParams.get('id') + '&tipo=' + urlParams.get('tipo');
    
    $.ajax({
        url: 'perfil_paciente.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            $('#modalBody').html(response);
        },
        error: function() {
            alert('Erro ao salvar anamnese');
        }
    });
});
</script>
</body>
</html>