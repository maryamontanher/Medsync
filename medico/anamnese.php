<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['medico_id'])) {
    die('Acesso não autorizado');
}

// Processar POST (salvar anamnese)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    salvarAnamnese($conn);
    exit;
}

// Processar GET (carregar formulário ou detalhes)
if (isset($_GET['acao'])) {
    switch ($_GET['acao']) {
        case 'form':
            carregarFormulario($conn);
            break;
        case 'detalhes':
            carregarDetalhes($conn);
            break;
        default:
            echo '<div class="alert alert-danger">Ação inválida</div>';
    }
    exit;
}

// Função para salvar anamnese
function salvarAnamnese($conn) {
    $medico_id = mysqli_real_escape_string($conn, $_POST['medico_id']);
    $paciente_id = mysqli_real_escape_string($conn, $_POST['paciente_id']);
    $tipo_paciente = mysqli_real_escape_string($conn, $_POST['tipo_paciente']);
    
    // Preparar dados
    $peso = !empty($_POST['peso']) ? mysqli_real_escape_string($conn, $_POST['peso']) : NULL;
    $altura = !empty($_POST['altura']) ? mysqli_real_escape_string($conn, $_POST['altura']) : NULL;
    $pressao_arterial = !empty($_POST['pressao_arterial']) ? mysqli_real_escape_string($conn, $_POST['pressao_arterial']) : NULL;
    $temperatura = !empty($_POST['temperatura']) ? mysqli_real_escape_string($conn, $_POST['temperatura']) : NULL;
    $frequencia_cardiaca = !empty($_POST['frequencia_cardiaca']) ? mysqli_real_escape_string($conn, $_POST['frequencia_cardiaca']) : NULL;
    $queixa_principal = !empty($_POST['queixa_principal']) ? mysqli_real_escape_string($conn, $_POST['queixa_principal']) : NULL;
    $antecedentes_pessoal = !empty($_POST['antecedentes_pessoal']) ? mysqli_real_escape_string($conn, $_POST['antecedentes_pessoal']) : NULL;
    $antecedentes_familiares = !empty($_POST['antecedentes_familiares']) ? mysqli_real_escape_string($conn, $_POST['antecedentes_familiares']) : NULL;
    $uso_medicamentos = !empty($_POST['uso_medicamentos']) ? mysqli_real_escape_string($conn, $_POST['uso_medicamentos']) : NULL;
    $alergias = !empty($_POST['alergias']) ? mysqli_real_escape_string($conn, $_POST['alergias']) : NULL;
    $observacoes_gerais = !empty($_POST['observacoes_gerais']) ? mysqli_real_escape_string($conn, $_POST['observacoes_gerais']) : NULL;
    
    // Construir query base
    if ($tipo_paciente == 'maior') {
        $query = "INSERT INTO anamnese (paciente_maior_id, medico_id, peso, altura, pressao_arterial, temperatura, frequencia_cardiaca, queixa_principal, antecedentes_pessoal, antecedentes_familiares, uso_medicamentos, alergias, observacoes_gerais) 
                  VALUES ('$paciente_id', '$medico_id', '$peso', '$altura', '$pressao_arterial', '$temperatura', '$frequencia_cardiaca', '$queixa_principal', '$antecedentes_pessoal', '$antecedentes_familiares', '$uso_medicamentos', '$alergias', '$observacoes_gerais')";
    } else {
        $query = "INSERT INTO anamnese (paciente_menor_id, medico_id, peso, altura, pressao_arterial, temperatura, frequencia_cardiaca, queixa_principal, antecedentes_pessoal, antecedentes_familiares, uso_medicamentos, alergias, observacoes_gerais) 
                  VALUES ('$paciente_id', '$medico_id', '$peso', '$altura', '$pressao_arterial', '$temperatura', '$frequencia_cardiaca', '$queixa_principal', '$antecedentes_pessoal', '$antecedentes_familiares', '$uso_medicamentos', '$alergias', '$observacoes_gerais')";
    }
    
    if (mysqli_query($conn, $query)) {
        echo '
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i>
            Anamnese registrada com sucesso!
        </div>
        <div class="text-center mt-3">
            <i class="fas fa-spinner fa-spin fa-2x text-success"></i>
            <p class="mt-2">Atualizando a página...</p>
        </div>';
    } else {
        echo '
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Erro ao salvar anamnese: ' . mysqli_error($conn) . '
        </div>';
    }
}

// Função para carregar formulário
function carregarFormulario($conn) {
    $paciente_id = mysqli_real_escape_string($conn, $_GET['id']);
    $tipo_paciente = mysqli_real_escape_string($conn, $_GET['tipo']);
    $medico_id = $_SESSION['medico_id'];
    ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">Nova Anamnese</h5>
        <span class="badge bg-success">Novo registro</span>
    </div>

    <form id="formAnamnese" class="anamnese-form">
        <input type="hidden" name="paciente_id" value="<?php echo $paciente_id; ?>">
        <input type="hidden" name="tipo_paciente" value="<?php echo $tipo_paciente; ?>">
        <input type="hidden" name="medico_id" value="<?php echo $medico_id; ?>">
        
        <div class="vitals-grid">
            <div class="mb-3">
                <label class="form-label">Peso (kg)</label>
                <input type="number" step="0.1" class="form-control" name="peso" placeholder="Ex: 70.5">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Altura (m)</label>
                <input type="number" step="0.01" class="form-control" name="altura" placeholder="Ex: 1.75">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Pressão Arterial</label>
                <input type="text" class="form-control" name="pressao_arterial" placeholder="Ex: 120/80">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Temperatura (°C)</label>
                <input type="number" step="0.1" class="form-control" name="temperatura" placeholder="Ex: 36.5">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Frequência Cardíaca</label>
                <input type="number" class="form-control" name="frequencia_cardiaca" placeholder="Ex: 72">
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Queixa Principal</label>
            <textarea class="form-control" name="queixa_principal" rows="3" placeholder="Descreva a queixa principal do paciente..."></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Antecedentes Pessoais</label>
            <textarea class="form-control" name="antecedentes_pessoal" rows="3" placeholder="Histórico médico pessoal..."></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Antecedentes Familiares</label>
            <textarea class="form-control" name="antecedentes_familiares" rows="3" placeholder="Histórico médico familiar..."></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Uso de Medicamentos</label>
            <textarea class="form-control" name="uso_medicamentos" rows="2" placeholder="Medicamentos em uso..."></textarea>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Alergias</label>
            <textarea class="form-control" name="alergias" rows="2" placeholder="Alergias conhecidas..."></textarea>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Observações Gerais</label>
            <textarea class="form-control" name="observacoes_gerais" rows="3" placeholder="Outras observações..."></textarea>
        </div>
        
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success flex-fill">
                <i class="fas fa-save me-1"></i>Salvar Anamnese
            </button>
        </div>
    </form>
    <?php
}

// Função para carregar detalhes
function carregarDetalhes($conn) {
    $anamnese_id = mysqli_real_escape_string($conn, $_GET['id']);

    $query = "SELECT a.*, m.medicos_nome 
              FROM anamnese a
              INNER JOIN medicos m ON a.medico_id = m.medicos_id
              WHERE a.id = '$anamnese_id'";
    $result = mysqli_query($conn, $query);
    $anamnese = mysqli_fetch_assoc($result);

    if (!$anamnese) {
        echo '<div class="alert alert-danger">Anamnese não encontrada</div>';
        exit;
    }
    ?>
    
    <div class="view-only">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Detalhes da Anamnese</h5>
            <span class="badge bg-info"><?php echo date('d/m/Y H:i', strtotime($anamnese['data_registro'])); ?></span>
        </div>
        
        <div class="mb-3">
            <small class="text-muted">Médico responsável</small>
            <p class="fw-bold">Dr. <?php echo htmlspecialchars($anamnese['medicos_nome']); ?></p>
        </div>
        
        <div class="row mb-4">
            <?php if ($anamnese['peso']): ?>
            <div class="col-md-2 mb-2">
                <small class="text-muted">Peso</small>
                <p class="fw-bold"><?php echo htmlspecialchars($anamnese['peso']); ?> kg</p>
            </div>
            <?php endif; ?>
            
            <?php if ($anamnese['altura']): ?>
            <div class="col-md-2 mb-2">
                <small class="text-muted">Altura</small>
                <p class="fw-bold"><?php echo htmlspecialchars($anamnese['altura']); ?> m</p>
            </div>
            <?php endif; ?>
            
            <?php if ($anamnese['pressao_arterial']): ?>
            <div class="col-md-3 mb-2">
                <small class="text-muted">Pressão Arterial</small>
                <p class="fw-bold"><?php echo htmlspecialchars($anamnese['pressao_arterial']); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($anamnese['temperatura']): ?>
            <div class="col-md-2 mb-2">
                <small class="text-muted">Temp.</small>
                <p class="fw-bold"><?php echo htmlspecialchars($anamnese['temperatura']); ?> °C</p>
            </div>
            <?php endif; ?>
            
            <?php if ($anamnese['frequencia_cardiaca']): ?>
            <div class="col-md-3 mb-2">
                <small class="text-muted">Freq. Cardíaca</small>
                <p class="fw-bold"><?php echo htmlspecialchars($anamnese['frequencia_cardiaca']); ?> bpm</p>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if ($anamnese['queixa_principal']): ?>
        <div class="mb-3">
            <small class="text-muted">Queixa Principal</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['queixa_principal'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($anamnese['antecedentes_pessoal']): ?>
        <div class="mb-3">
            <small class="text-muted">Antecedentes Pessoais</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['antecedentes_pessoal'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($anamnese['antecedentes_familiares']): ?>
        <div class="mb-3">
            <small class="text-muted">Antecedentes Familiares</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['antecedentes_familiares'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($anamnese['uso_medicamentos']): ?>
        <div class="mb-3">
            <small class="text-muted">Uso de Medicamentos</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['uso_medicamentos'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($anamnese['alergias']): ?>
        <div class="mb-3">
            <small class="text-muted">Alergias</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['alergias'])); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($anamnese['observacoes_gerais']): ?>
        <div class="mb-3">
            <small class="text-muted">Observações Gerais</small>
            <p class="fw-normal"><?php echo nl2br(htmlspecialchars($anamnese['observacoes_gerais'])); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
}
?>