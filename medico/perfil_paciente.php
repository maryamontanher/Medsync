<?php
session_start();
require_once '../conexao.php';

function calcularIdade($data_nasc) {
    if (empty($data_nasc)) return 0;
    $data_nasc = new DateTime($data_nasc);
    $hoje = new DateTime();
    $idade = $hoje->diff($data_nasc);
    return $idade->y;
}

function getPhotoUrlModal($foto, $tipo, $nome) {
    if ($foto) {
        $path = $tipo == 'maior' ? '../paciente/uploads/pacientes/' . $foto : '../paciente menor/uploads/pacientes/' . $foto;
        $full_path = $_SERVER['DOCUMENT_ROOT'] . '/medsync' . str_replace('..', '', $path);
        
        if (file_exists($full_path)) {
            $timestamp = filemtime($full_path);
            return $path . '?t=' . $timestamp;
        }
    }
    
    $initial = strtoupper(substr($nome, 0, 1));
    $color = $tipo == 'maior' ? '#0a4e53' : '#4bb9a5';
    $textColor = '#ffffff';
    
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="150" viewBox="0 0 150 150">
            <rect width="150" height="150" fill="'.$color.'"/>
            <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" 
            font-family="Arial, sans-serif" font-size="48" font-weight="bold" 
            fill="'.$textColor.'">'.$initial.'</text>
            </svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

if (!isset($_SESSION['medico_id'])) {
    die('Acesso não autorizado');
}

if (!isset($_GET['id']) || !isset($_GET['tipo'])) {
    die('sucesso');
}

$paciente_id = mysqli_real_escape_string($conn, $_GET['id']);
$tipo_paciente = mysqli_real_escape_string($conn, $_GET['tipo']);

// Buscar dados do paciente
if ($tipo_paciente == 'maior') {
    $query = "SELECT * FROM paciente_maior WHERE pmaior_id = '$paciente_id'";
    $result = mysqli_query($conn, $query);
    $paciente = mysqli_fetch_assoc($result);
    
    if (!$paciente) die('Paciente não encontrado');
    
    $nome = $paciente['pmaior_nome'];
    $cpf = $paciente['pmaior_cpf'];
    $email = $paciente['pmaior_email'];
    $telefone = $paciente['pmaior_telefone'];
    $endereco = $paciente['pmaior_endereco'];
    $sexo = $paciente['pmaior_sexo'];
    $estado_civil = $paciente['pmaior_estadocivil'];
    $data_nasc = $paciente['pmaior_datanasc'];
    $foto = $paciente['pmaior_foto'];

} else {

    $query = "SELECT pm.*, 
              pma.pmaior_nome AS responsavel_nome,
              pma.pmaior_cpf AS responsavel_cpf,
              pma.pmaior_telefone AS responsavel_telefone,
              pma.pmaior_email AS responsavel_email,
              pma.pmaior_endereco AS responsavel_endereco
              FROM paciente_menor pm
              INNER JOIN paciente_maior pma ON pm.pmaior_id = pma.pmaior_id
              WHERE pm.pmenor_id = '$paciente_id'";

    $result = mysqli_query($conn, $query);
    $paciente = mysqli_fetch_assoc($result);

    if (!$paciente) die('Paciente não encontrado');

    $nome = $paciente['pmenor_nome'];
    $cpf = $paciente['pmenor_cpf'];
    $email = $paciente['pmenor_email'];
    $telefone = $paciente['pmenor_telefone'];
    $endereco = $paciente['pmenor_endereco'];
    $sexo = $paciente['pmenor_sexo'];
    $estado_civil = $paciente['pmenor_estadocivil'];
    $data_nasc = $paciente['pmenor_datanasc'];
    $foto = $paciente['pmenor_foto'];

    $responsavel_nome = $paciente['responsavel_nome'];
    $responsavel_cpf = $paciente['responsavel_cpf'];
    $responsavel_telefone = $paciente['responsavel_telefone'];
    $responsavel_email = $paciente['responsavel_email'];
    $responsavel_endereco = $paciente['responsavel_endereco'];
}

$idade = calcularIdade($data_nasc);

// Consultas - com verificação de erro
if ($tipo_paciente == 'maior') {
    $query_consultas = "SELECT cm.*, m.medicos_nome 
                        FROM consultas_maior cm 
                        INNER JOIN medicos m ON cm.medico_id = m.medicos_id 
                        WHERE cm.paciente_maior_id = '$paciente_id'
                        ORDER BY cm.data_consulta DESC";
} else {
    $query_consultas = "SELECT cm.*, m.medicos_nome 
                        FROM consultas_menor cm 
                        INNER JOIN medicos m ON cm.medico_id = m.medicos_id 
                        WHERE cm.paciente_menor_id = '$paciente_id'
                        ORDER BY cm.data_consulta DESC";
}

$result_consultas = mysqli_query($conn, $query_consultas);
if ($result_consultas) {
    $consultas = mysqli_fetch_all($result_consultas, MYSQLI_ASSOC);
} else {
    $consultas = [];
}

// Receitas - com verificação de erro
if ($tipo_paciente == 'maior') {
    $query_receitas = "SELECT r.*, m.medicos_nome 
                       FROM receita r
                       INNER JOIN medicos m ON r.medico_id = m.medicos_id
                       WHERE r.paciente_maior_id = '$paciente_id'
                       ORDER BY r.data_emissao DESC";
} else {
    $query_receitas = "SELECT r.*, m.medicos_nome 
                       FROM receita r
                       INNER JOIN medicos m ON r.medico_id = m.medicos_id
                       WHERE r.paciente_menor_id = '$paciente_id'
                       ORDER BY r.data_emissao DESC";
}

$result_receitas = mysqli_query($conn, $query_receitas);
if ($result_receitas) {
    $receitas = mysqli_fetch_all($result_receitas, MYSQLI_ASSOC);
} else {
    $receitas = [];
}

// Atestados 
if ($tipo_paciente == 'maior') {
    $query_atestados = "SELECT a.*, m.medicos_nome 
                        FROM atestado a
                        INNER JOIN medicos m ON a.medico_id = m.medicos_id
                        WHERE a.paciente_maior_id = '$paciente_id'
                        ORDER BY a.data_emissao DESC";
} else {
    // CORREÇÃO: Query corrigida para pacientes menores
    $query_atestados = "SELECT a.*, m.medicos_nome 
                        FROM atestado a
                        INNER JOIN medicos m ON a.medico_id = m.medicos_id
                        WHERE a.paciente_menor_id = '$paciente_id'
                        ORDER BY a.data_emissao DESC";
}

$result_atestados = mysqli_query($conn, $query_atestados);

// Verificação segura do resultado
if ($result_atestados) {
    $atestados = mysqli_fetch_all($result_atestados, MYSQLI_ASSOC);
} else {
    $atestados = [];
    // Opcional: descomente a linha abaixo para debug
    // error_log("Erro SQL atestados: " . mysqli_error($conn));
}
// Buscar anamneses do paciente - COM VERIFICAÇÃO
if ($tipo_paciente == 'maior') {
    $query_anamneses = "SELECT a.*, m.medicos_nome 
                       FROM anamnese a
                       INNER JOIN medicos m ON a.medico_id = m.medicos_id
                       WHERE a.paciente_maior_id = '$paciente_id'
                       ORDER BY a.data_registro DESC";
} else {
    $query_anamneses = "SELECT a.*, m.medicos_nome 
                       FROM anamnese a
                       INNER JOIN medicos m ON a.medico_id = m.medicos_id
                       WHERE a.paciente_menor_id = '$paciente_id'
                       ORDER BY a.data_registro DESC";
}

$result_anamneses = mysqli_query($conn, $query_anamneses);
if ($result_anamneses) {
    $anamneses = mysqli_fetch_all($result_anamneses, MYSQLI_ASSOC);
} else {
    $anamneses = [];
}
?>

<!-- O RESTANTE DO SEU HTML/CSS PERMANECE IGUAL -->
<style>
.patient-profile {
    font-family: 'Segoe UI', sans-serif;
}
.nav-tabs .nav-link {
    color: #0a4e53;
    font-weight: 500;
    border: none;
    padding: 12px 20px;
}
.nav-tabs .nav-link.active {
    color: #0a4e53;
    background-color: transparent;
    border-bottom: 3px solid #0a4e53;
    font-weight: 600;
}
.nav-tabs .nav-link:hover {
    color: #4bb9a5;
    border: none;
}
.tab-content {
    padding: 0;
}
.profile-section {
    background: white;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.profile-section h5 {
    color: #0a4e53;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #4bb9a5;
}
.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}
.info-item {
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}
.info-item:last-child {
    border-bottom: none;
}
.info-label {
    font-weight: 600;
    color: #0a4e53;
    min-width: 180px;
    display: inline-block;
}
.badge-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
}
.badge-agendada { background-color: #d4edda; color: #155724; }
.badge-realizada { background-color: #cce7ff; color: #004085; }
.badge-cancelada { background-color: #f8d7da; color: #721c24; }
.table th {
    background-color: #0a4e53;
    color: white;
    border: none;
}
.table td {
    vertical-align: middle;
}
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}
.empty-state i {
    font-size: 3rem;
    margin-bottom: 15px;
    color: #dee2e6;
}
.btn-consulta {
    background-color: #0a4e53;
    color: #fff;
    border: 1px solid #0a4e53;
}
.btn-consulta:hover {
    background-color: #08403a;
    color: #fff;
    border-color: #08403a;
}
.anamnese-form .form-label {
    font-weight: 600;
    color: #0a4e53;
    margin-bottom: 5px;
}
.anamnese-form .form-control, .anamnese-form .form-select {
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
}
.anamnese-form .form-control:focus, .anamnese-form .form-select:focus {
    border-color: #4bb9a5;
    box-shadow: 0 0 0 0.2rem rgba(75, 185, 165, 0.25);
}
.vitals-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.profile-header {
    margin-bottom: 20px;
}
.profile-content {
    display: flex;
    gap: 20px;
}
.profile-sidebar {
    flex: 0 0 300px;
}
.profile-main {
    flex: 1;
    min-width: 0;
}
.anamnese-layout {
    display: flex;
    gap: 20px;
    min-height: 600px;
}
.anamnese-list {
    flex: 0 0 350px;
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-height: 600px;
    overflow-y: auto;
}
.anamnese-detail {
    flex: 1;
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.anamnese-item {
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 10px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.anamnese-item:hover {
    border-color: #4bb9a5;
    background-color: #f8f9fa;
}
.anamnese-item.active {
    border-color: #0a4e53;
    background-color: #e8f4f1;
}
.anamnese-date {
    font-weight: 600;
    color: #0a4e53;
    margin-bottom: 5px;
}
.anamnese-doctor {
    color: #6c757d;
    font-size: 0.9em;
}
.anamnese-preview {
    font-size: 0.85em;
    color: #6c757d;
    margin-top: 8px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.view-only .form-control {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    color: #495057;
    cursor: not-allowed;
}
.view-only textarea.form-control {
    resize: none;
}.bg-success {
    --bs-bg-opacity: 1;
    background-color: #08403a !important;
}
.bg-info {
    --bs-bg-opacity: 1;
    background-color: #0a4e53 !important;}
    .anamnese-layout {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 20px;
    min-height: 600px;
}

.anamnese-list {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-height: 600px;
    overflow-y: auto;
}

.anamnese-detail {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-height: 600px;
    overflow-y: auto;
}

/* Novo design compacto para o formulário */
.anamnese-form-compact {
    max-height: 560px;
    overflow-y: auto;
    padding-right: 10px;
}

.anamnese-form-compact::-webkit-scrollbar {
    width: 6px;
}

.anamnese-form-compact::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.anamnese-form-compact::-webkit-scrollbar-thumb {
    background: #4bb9a5;
    border-radius: 3px;
}

.compact-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 20px;
}

.compact-grid .form-group {
    margin-bottom: 0;
}

.compact-grid .form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #0a4e53;
    margin-bottom: 5px;
}

.compact-grid .form-control {
    font-size: 0.9rem;
    padding: 8px 12px;
    height: auto;
}

.textarea-group {
    grid-column: 1 / -1;
}

.textarea-group .form-control {
    min-height: 80px;
    resize: vertical;
}

.quick-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #0a4e53;
    margin: 20px 0 10px 0;
    padding-bottom: 5px;
    border-bottom: 2px solid #4bb9a5;
}

/* Design mais compacto para a lista */
.anamnese-item-compact {
    padding: 12px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: white;
}

.anamnese-item-compact:hover {
    border-color: #4bb9a5;
    background-color: #f8f9fa;
    transform: translateX(2px);
}

.anamnese-item-compact.active {
    border-color: #0a4e53;
    background-color: #e8f4f1;
    box-shadow: 0 2px 8px rgba(10, 78, 83, 0.1);
}

.anamnese-header {
    display: flex;
    justify-content: between;
    align-items: flex-start;
    margin-bottom: 5px;
}

.anamnese-date {
    font-weight: 600;
    color: #0a4e53;
    font-size: 0.85rem;
}

.anamnese-doctor {
    color: #6c757d;
    font-size: 0.8rem;
    text-align: right;
    flex: 1;
}

.anamnese-preview {
    font-size: 0.8rem;
    color: #6c757d;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.3;
}

/* Botões de ação */
.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #e9ecef;
}

.btn-save {
    flex: 2;
    background: #0a4e53;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 6px;
    font-weight: 600;
}

/* Estilos para os modais de documentos */
.documento-modal {
    font-family: 'Arial', sans-serif;
    line-height: 1.6;
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

.btn-view {
    background: transparent;
    border: 1px solid #0a4e53;
    color: #0a4e53;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.btn-view:hover {
    background: #0a4e53;
    color: white;
}
/* Responsivo */
@media (max-width: 1200px) {
    .anamnese-layout {
        grid-template-columns: 1fr;
    }
    
    .anamnese-list {
        max-height: 300px;
    }
}

@media (max-width: 768px) {
    .compact-grid {
        grid-template-columns: 1fr;
    }
    
    .quick-actions {
        flex-direction: column;
    }
}
</style>

    <div class="profile-content">
        <!-- Sidebar com foto do paciente -->
        <div class="profile-sidebar">
            <div class="profile-section text-center">
                <?php
                $foto_url = getPhotoUrlModal($foto, $tipo_paciente, $nome);
                ?>
                <img src="<?php echo $foto_url; ?>" 
                     class="img-fluid rounded-circle mb-3 border" 
                     style="width: 150px; height: 150px; object-fit: cover; border-color: #4bb9a5 !important;" 
                     alt="Foto de <?php echo htmlspecialchars($nome); ?>"
                     onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz48c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgd2lkdGg9IjE1MCIgaGVpZ2h0PSIxNTAiIHZpZXdCb3g9IjAgMCAxNTAgMTUwIj48cmVjdCB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgZmlsbD0iIzZjNzU3ZCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iNDgiIGZpbGw9IndoaXRlIj4/PC90ZXh0Pjwvc3ZnPg=='">
                <h4 class="text-success"><?php echo htmlspecialchars($nome); ?></h4>
                <span class="badge bg-<?php echo $tipo_paciente == 'maior' ? 'success' : 'info'; ?>">
                    <?php echo $tipo_paciente == 'maior' ? 'Maior de idade' : 'Menor de idade'; ?>
                </span>
                <p class="text-muted mt-2"><?php echo $idade; ?> anos</p>
                
                <!-- Informações rápidas -->
                <div class="mt-3 text-start">
                    <div class="info-item">
                        <span class="info-label">CPF:</span>
                        <?php echo htmlspecialchars($cpf); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Telefone:</span>
                        <?php echo htmlspecialchars($telefone); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">E-mail:</span>
                        <?php echo htmlspecialchars($email); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conteúdo principal com abas -->
        <div class="profile-main">
            <ul class="nav nav-tabs" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                        <i class="fas fa-user me-1"></i>Dados Pessoais
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="consultas-tab" data-bs-toggle="tab" data-bs-target="#consultas" type="button" role="tab">
                        <i class="fas fa-calendar me-1"></i>Consultas
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab">
                        <i class="fas fa-file-medical me-1"></i>Documentos
                    </button>
                </li>
                 <!-- NOVA ABA AQUI -->
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="anamnese-tab" data-bs-toggle="tab" data-bs-target="#anamnese" type="button" role="tab">
            <i class="fas fa-notes-medical me-1"></i>Anamnese
        </button>
    </li>
</ul>

            <!-- Conteúdo das abas -->
            <div class="tab-content mt-3" id="profileTabsContent">
                
                <!-- Aba Dados Pessoais -->
                <div class="tab-pane fade show active" id="dados" role="tabpanel">
                    <div class="profile-section">
                        <h5><i class="fas fa-id-card me-2"></i>Informações Pessoais</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nome:</span>
                                <?php echo htmlspecialchars($nome); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Estado Civil:</span>
                                <?php echo htmlspecialchars($estado_civil); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Data de Nascimento:</span>
                                <?php echo date('d/m/Y', strtotime($data_nasc)); ?> (<?php echo $idade; ?> anos)
                            </div>
                            <div class="info-item">
                                <span class="info-label">Sexo:</span>
                                <?php echo htmlspecialchars($sexo); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Endereço:</span>
                                <?php echo htmlspecialchars($endereco); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($tipo_paciente == 'menor'): ?>
                    <div class="profile-section">
                        <h5><i class="fas fa-user-shield me-2"></i>Responsável</h5>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Nome:</span>
                                <?php echo htmlspecialchars($responsavel_nome); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Telefone:</span>
                                <?php echo htmlspecialchars($responsavel_telefone); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">CPF:</span>
                                <?php echo htmlspecialchars($responsavel_cpf); ?>
                            </div>
                            <div class="info-item">
                                <span class="info-label">E-mail:</span>
                                <?php echo htmlspecialchars($responsavel_email); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Aba Consultas -->
                <div class="tab-pane fade" id="consultas" role="tabpanel">
                    <div class="profile-section">
                        <h5><i class="fas fa-history me-2"></i>Histórico de Consultas</h5>
                        <?php if (empty($consultas)): ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <p>Nenhuma consulta encontrada</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Data</th>
                                            <th>Hora</th>
                                            <th>Médico</th>
                                            <th>Status</th>
                                            <th>Observações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($consultas as $consulta): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($consulta['data_consulta'])); ?></td>
                                                <td><?php echo substr($consulta['hora_consulta'], 0, 5); ?></td>
                                                <td><?php echo htmlspecialchars($consulta['medicos_nome']); ?></td>
                                                <td>
                                                    <span class="badge-status badge-<?php echo strtolower($consulta['status']); ?>">
                                                        <?php echo $consulta['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($consulta['observacoes'] ?? 'Nenhuma'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Aba Documentos -->
                <div class="tab-pane fade" id="documentos" role="tabpanel">
                    <!-- Receitas -->
                    <div class="profile-section mb-4">
                        <h5></i>Receitas Médicas</h5>
                        <?php if (empty($receitas)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-prescription"></i>
                                <p>Nenhuma receita encontrada</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Data Emissão</th>
                <th>Médico</th>
                <th>Validade</th>
                <th>Observações</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($receitas as $receita): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($receita['data_emissao'])); ?></td>
                    <td><?php echo htmlspecialchars($receita['medicos_nome']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($receita['validade_final'])); ?></td>
                    <td>
                        <?php 
                        $obs = $receita['observacoes'];
                        echo strlen($obs) > 50 ? substr($obs, 0, 50) . '...' : $obs;
                        ?>
                    </td>
                    <td>
                        <span class="badge bg-<?php echo $receita['status_receita'] == 'valida' ? 'success' : 'secondary'; ?>">
                            <?php echo ucfirst($receita['status_receita']); ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-view btn-sm" onclick="visualizarReceita(<?php echo $receita['receita_id']; ?>)">
                            Visualizar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                        <?php endif; ?>
                    </div>

                    <!-- Atestados -->
                    <div class="profile-section">
                        <h5></i>Atestados Médicos</h5>
                        <?php if (empty($atestados)): ?>
                            <div class="empty-state">
                                <i class="fas fa-file-medical-alt"></i>
                                <p>Nenhum atestado encontrado</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Data Emissão</th>
                <th>Médico</th>
                <th>Dias de Afastamento</th>
                <th>Descrição</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($atestados as $atestado): ?>
                <tr>
                    <td><?php echo date('d/m/Y', strtotime($atestado['data_emissao'])); ?></td>
                    <td><?php echo htmlspecialchars($atestado['medicos_nome']); ?></td>
                    <td><?php echo $atestado['dias_afastamento']; ?> dias</td>
                    <td>
                        <?php 
                        $desc = $atestado['descricao'];
                        echo strlen($desc) > 50 ? substr($desc, 0, 50) . '...' : $desc;
                        ?>
                    </td>
                    <td>
                        <button class="btn btn-view btn-sm" onclick="visualizarAtestado(<?php echo $atestado['atestado_id']; ?>)">
                            Visualizar
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
                        <?php endif; ?>
                    </div>
                </div>
                <!-- Adicione esta seção no tab-content, após a aba Documentos -->
<div class="tab-pane fade" id="anamnese" role="tabpanel">
    <div class="anamnese-layout">
        <!-- Lista de anamneses existentes -->
        <div class="anamnese-list">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Histórico de Anamneses</h5>
                <button class="btn btn-success btn-sm" id="novaAnamneseBtn">
                    <i class="fas fa-plus me-1"></i>
                </button>
            </div>
            
            <?php
            // Buscar anamneses do paciente
            if ($tipo_paciente == 'maior') {
                $query_anamneses = "SELECT a.*, m.medicos_nome 
                                   FROM anamnese a
                                   INNER JOIN medicos m ON a.medico_id = m.medicos_id
                                   WHERE a.paciente_maior_id = '$paciente_id'
                                   ORDER BY a.data_registro DESC";
            } else {
                $query_anamneses = "SELECT a.*, m.medicos_nome 
                                   FROM anamnese a
                                   INNER JOIN medicos m ON a.medico_id = m.medicos_id
                                   WHERE a.paciente_menor_id = '$paciente_id'
                                   ORDER BY a.data_registro DESC";
            }
            
            $result_anamneses = mysqli_query($conn, $query_anamneses);
            $anamneses = mysqli_fetch_all($result_anamneses, MYSQLI_ASSOC);
            ?>
            
            <div id="listaAnamneses">
                <?php if (empty($anamneses)): ?>
                    <div class="empty-state">
                        <i class="fas fa-notes-medical"></i>
                        <p>Nenhuma anamnese registrada</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($anamneses as $anamnese): ?>
                        <div class="anamnese-item" data-id="<?php echo $anamnese['id']; ?>">
                            <div class="anamnese-date">
                                <?php echo date('d/m/Y H:i', strtotime($anamnese['data_registro'])); ?>
                            </div>
                            <div class="anamnese-doctor">
                                Dr. <?php echo htmlspecialchars($anamnese['medicos_nome']); ?>
                            </div>
                            <?php if (!empty($anamnese['queixa_principal'])): ?>
                                <div class="anamnese-preview">
                                    <?php echo htmlspecialchars(substr($anamnese['queixa_principal'], 0, 100)); ?>...
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Área de detalhes/nova anamnese -->
        <div class="anamnese-detail">
            <div id="anamneseDetailContent">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-notes-medical fa-3x mb-3"></i>
                    <p>Selecione uma anamnese da lista ou crie uma nova</p>
                </div>
            </div>
        </div>
    </div>
</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal para Visualizar Receita -->
<div class="modal fade" id="modalReceita" tabindex="-1" aria-labelledby="modalReceitaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalReceitaBody">
                <!-- Conteúdo da receita será carregado aqui -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" onclick="imprimirReceita()">
                    <i class="fas fa-print me-1"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Visualizar Atestado -->
<div class="modal fade" id="modalAtestado" tabindex="-1" aria-labelledby="modalAtestadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="modalAtestadoBody">
                <!-- Conteúdo do atestado será carregado aqui -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-success" onclick="imprimirAtestado()">
                    <i class="fas fa-print me-1"></i>Imprimir
                </button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Nova anamnese
    $('#novaAnamneseBtn').on('click', function() {
        carregarFormularioAnamnese();
    });
    
    // Carregar detalhes da anamnese
    $(document).on('click', '.anamnese-item', function() {
        var anamneseId = $(this).data('id');
        carregarDetalhesAnamnese(anamneseId);
    });
    
    // Submeter formulário de anamnese
    $(document).on('submit', '#formAnamnese', function(e) {
        e.preventDefault();
        submeterAnamnese();
    });
    
    function carregarFormularioAnamnese() {
        $.ajax({
            url: 'anamnese.php',
            type: 'GET',
            data: {
                acao: 'form',
                id: '<?php echo $paciente_id; ?>',
                tipo: '<?php echo $tipo_paciente; ?>'
            },
            success: function(response) {
                $('#anamneseDetailContent').html(response);
                $('.anamnese-item').removeClass('active');
            },
            error: function() {
                alert('Erro ao carregar formulário');
            }
        });
    }
    
    function carregarDetalhesAnamnese(anamneseId) {
        $.ajax({
            url: 'anamnese.php',
            type: 'GET',
            data: {
                acao: 'detalhes',
                id: anamneseId
            },
            success: function(response) {
                $('#anamneseDetailContent').html(response);
                $('.anamnese-item').removeClass('active');
                $('.anamnese-item[data-id="' + anamneseId + '"]').addClass('active');
            },
            error: function() {
                alert('Erro ao carregar detalhes');
            }
        });
    }
    
    function submeterAnamnese() {
        var formData = $('#formAnamnese').serialize();
        
        $.ajax({
            url: 'anamnese.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                // Recarregar a página imediatamente após salvar com sucesso
                window.location.reload();
            },
            error: function() {
                alert('Erro ao salvar anamnese');
            }
        });
    }
});
</script>
<script>
// Funções para os modais de documentos
function visualizarReceita(receitaId) {
    $.ajax({
        url: 'carregar_receita.php',
        type: 'GET',
        data: { id: receitaId },
        success: function(response) {
            $('#modalReceitaBody').html(response);
            $('#modalReceita').modal('show');
        },
        error: function() {
            alert('Erro ao carregar receita');
        }
    });
}

function visualizarAtestado(atestadoId) {
    $.ajax({
        url: 'carregar_atestado.php',
        type: 'GET',
        data: { id: atestadoId },
        success: function(response) {
            $('#modalAtestadoBody').html(response);
            $('#modalAtestado').modal('show');
        },
        error: function() {
            alert('Erro ao carregar atestado');
        }
    });
}

function imprimirReceita() {
    var conteudo = document.getElementById('modalReceitaBody').innerHTML;
    var janela = window.open('', '_blank');
    janela.document.write(`
        <html>
            <head>
                <title>Receita Médica</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .documento-header { text-align: center; margin-bottom: 30px; }
                    .documento-title { color: #0a4e53; font-size: 1.5rem; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                    .documento-content { background: #f8f9fa; padding: 15px; margin: 20px 0; }
                    .assinatura { margin-top: 50px; text-align: center; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                ${conteudo}
            </body>
        </html>
    `);
    janela.document.close();
    janela.print();
}

function imprimirAtestado() {
    var conteudo = document.getElementById('modalAtestadoBody').innerHTML;
    var janela = window.open('', '_blank');
    janela.document.write(`
        <html>
            <head>
                <title>Atestado Médico</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .documento-header { text-align: center; margin-bottom: 30px; }
                    .documento-title { color: #0a4e53; font-size: 1.5rem; }
                    .info-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
                    .documento-content { background: #f8f9fa; padding: 15px; margin: 20px 0; }
                    .assinatura { margin-top: 50px; text-align: center; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                ${conteudo}
            </body>
        </html>
    `);
    janela.document.close();
    janela.print();
}
</script>