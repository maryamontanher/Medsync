<?php
session_start();

if (!isset($_SESSION['pmaior_id'])) {
    header("Location: login.php");
    exit();
}

$pmaior_id = $_SESSION['pmaior_id'];

$mensagem = '';
$tipo_mensagem = '';

// Buscar pacientes menores vinculados a este responsável
$conn = new mysqli("localhost", "root", "", "medsync");
$pacientes_menores = [];

if (!$conn->connect_error) {
    $sql_pacientes = "SELECT pmenor_id, pmenor_nome, pmenor_datanasc 
                      FROM paciente_menor 
                      WHERE pmaior_id = ? 
                      ORDER BY pmenor_nome";
    $stmt_pacientes = $conn->prepare($sql_pacientes);
    $stmt_pacientes->bind_param("i", $pmaior_id);
    $stmt_pacientes->execute();
    $result_pacientes = $stmt_pacientes->get_result();
    
    if ($result_pacientes && $result_pacientes->num_rows > 0) {
        $pacientes_menores = $result_pacientes->fetch_all(MYSQLI_ASSOC);
    }
    $stmt_pacientes->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo_paciente = $_POST['tipo_paciente'] ?? '';
    $paciente_menor_id = $_POST['paciente_menor_id'] ?? '';
    $medico_id = $_POST['medico_id'] ?? '';
    $data_consulta = $_POST['data_consulta'] ?? '';
    $hora_consulta = $_POST['hora_consulta'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    // Validações
    if (empty($tipo_paciente) || empty($medico_id) || empty($data_consulta) || empty($hora_consulta)) {
        $mensagem = 'Todos os campos obrigatórios devem ser preenchidos';
        $tipo_mensagem = 'error';
    } elseif ($tipo_paciente === 'menor' && empty($paciente_menor_id)) {
        $mensagem = 'Selecione o paciente menor para agendar a consulta';
        $tipo_mensagem = 'error';
    } else {
        // Verificar se o paciente menor pertence ao responsável
        if ($tipo_paciente === 'menor') {
            $paciente_valido = false;
            foreach ($pacientes_menores as $paciente) {
                if ($paciente['pmenor_id'] == $paciente_menor_id) {
                    $paciente_valido = true;
                    break;
                }
            }
            
            if (!$paciente_valido) {
                $mensagem = 'Paciente menor não encontrado ou não autorizado';
                $tipo_mensagem = 'error';
            }
        }

        if ($tipo_mensagem !== 'error') {
            $data_selecionada = new DateTime($data_consulta);
            $hoje = new DateTime();
            $hoje->setTime(0, 0, 0);

            if ($data_selecionada < $hoje) {
                $mensagem = 'A data da consulta deve ser futura';
                $tipo_mensagem = 'error';
            } else {
                if ($conn->connect_error) {
                    $mensagem = 'Erro na conexão com o banco de dados';
                    $tipo_mensagem = 'error';
                } else {
                    if ($tipo_paciente === 'maior') {
                        // Consulta para paciente maior
                        $sql_verifica = "SELECT consulta_id FROM consultas_maior 
                                         WHERE medico_id = ? AND data_consulta = ? AND hora_consulta = ? 
                                         AND status = 'Agendada'";
                        $stmt_verifica = $conn->prepare($sql_verifica);
                        $stmt_verifica->bind_param("iss", $medico_id, $data_consulta, $hora_consulta);
                    } else {
                        // Consulta para paciente menor
                        $sql_verifica = "SELECT consulta_id FROM consultas_menor 
                                         WHERE medico_id = ? AND data_consulta = ? AND hora_consulta = ? 
                                         AND status = 'Agendada'";
                        $stmt_verifica = $conn->prepare($sql_verifica);
                        $stmt_verifica->bind_param("iss", $medico_id, $data_consulta, $hora_consulta);
                    }
                    
                    $stmt_verifica->execute();
                    $result_verifica = $stmt_verifica->get_result();

                    if ($result_verifica->num_rows > 0) {
                        $mensagem = 'Já existe uma consulta agendada para este médico no horário selecionado';
                        $tipo_mensagem = 'error';
                    } else {
                        if ($tipo_paciente === 'maior') {
                            $sql_inserir = "INSERT INTO consultas_maior (medico_id, paciente_maior_id, data_consulta, hora_consulta, observacoes, status) 
                                            VALUES (?, ?, ?, ?, ?, 'Agendada')";
                            $stmt_inserir = $conn->prepare($sql_inserir);
                            $stmt_inserir->bind_param("iisss", $medico_id, $pmaior_id, $data_consulta, $hora_consulta, $observacoes);
                        } else {
                            $sql_inserir = "INSERT INTO consultas_menor (medico_id, paciente_menor_id, responsavel_id, data_consulta, hora_consulta, observacoes, status) 
                                            VALUES (?, ?, ?, ?, ?, ?, 'Agendada')";
                            $stmt_inserir = $conn->prepare($sql_inserir);
                            $stmt_inserir->bind_param("iiisss", $medico_id, $paciente_menor_id, $pmaior_id, $data_consulta, $hora_consulta, $observacoes);
                        }

                        if ($stmt_inserir->execute()) {
                            $mensagem = 'Consulta agendada com sucesso!';
                            $tipo_mensagem = 'success';
                            
                            $_POST = array();
                        } else {
                            $mensagem = 'Erro ao agendar consulta: ' . $conn->error;
                            $tipo_mensagem = 'error';
                        }
                        $stmt_inserir->close();
                    }
                    $stmt_verifica->close();
                }
            }
        }
    }
}

// Buscar médicos
$medicos = [];
if (!$conn->connect_error) {
    $sql_medicos = "SELECT medicos_id, medicos_nome, medicos_especialidade FROM medicos ORDER BY medicos_nome";
    $result_medicos = $conn->query($sql_medicos);
    if ($result_medicos && $result_medicos->num_rows > 0) {
        $medicos = $result_medicos->fetch_all(MYSQLI_ASSOC);
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Nova Consulta | Medsync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-image: url('../images/bglogin.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            margin: 0;
            padding: 70px;
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
            max-width: 900px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 25px #7e8786;
        }

        h1 {
            text-align: center;
            color: #0a4e53;
            margin-bottom: 30px;
            font-weight: 400;
            font-size: 28px;
        }

        .form-label {
            font-weight: 600;
            color: #0a4e53;
            margin-bottom: 8px;
            display: block;
        }

        .required::after {
            content: " *";
            color: #dc3545;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 20px;
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

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .button-group .btn,
        .button-group a.btn {
            flex: 1;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .btn-success, 
        .btn-outline-secondary {
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
            height: auto;
        }

        .btn-success {
            background-color: #4bb9a5;
            border: none;
            color: #fff;
        }

        .btn-success:hover {
            background-color: #3c9181;
        }

        .btn-outline-secondary {
            border: 2px solid #4bb9a5;
            background-color: transparent;
            color: #4bb9a5;
            text-decoration: none;
        }

        .btn-outline-secondary:hover {
            background-color: #4bb9a5;
            color: white;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 5px;
            font-weight: 600;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }

        .form-text {
            color: #7e8786;
            font-size: 14px;
            margin-top: -15px;
            margin-bottom: 15px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .button-group .btn {
            flex: 1;
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%237e8786' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.75rem center;
            background-repeat: no-repeat;
            background-size: 16px 12px;
        }

        .paciente-menor-group {
            display: none;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            body {
                padding: 70px 20px;
            }
            
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            
            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="logo">
        <img src="../images/logo/logo_branca.png" alt="Logo">
    </div>
    <a href="consultas-paciente.php">
        <i class="fa-solid fa-arrow-left me-2"></i>Voltar para Minhas Consultas
    </a>
</nav>

<div class="container">
    <h1><i class="fas fa-calendar-plus me-2"></i>Agendar Nova Consulta</h1>
    
    <?php if ($mensagem): ?>
        <div class="alert <?php echo $tipo_mensagem === 'success' ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
            <i class="fas <?php echo $tipo_mensagem === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
            <?php echo htmlspecialchars($mensagem); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
        
        <?php if ($tipo_mensagem === 'success'): ?>
            <div class="button-group">
                <a href="consultas-paciente.php" class="btn btn-success">
                    <i class="fas fa-list me-2"></i>Ver Minhas Consultas
                </a>
                <a href="agendar-consulta.php" class="btn btn-outline-secondary">
                    <i class="fas fa-plus me-2"></i>Nova Consulta
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <form method="POST" action="agendar-consulta.php" id="formAgendarConsulta">
        <!-- Seleção do tipo de paciente -->
        <div class="mb-4">
            <label for="tipo_paciente" class="form-label required">Para quem é a consulta?</label>
            <select class="form-select" id="tipo_paciente" name="tipo_paciente" required>
                <option value="">Selecione...</option>
                <option value="maior" <?php echo (isset($_POST['tipo_paciente']) && $_POST['tipo_paciente'] == 'maior') ? 'selected' : ''; ?>>Para mim (paciente maior)</option>
                <?php if (!empty($pacientes_menores)): ?>
                    <option value="menor" <?php echo (isset($_POST['tipo_paciente']) && $_POST['tipo_paciente'] == 'menor') ? 'selected' : ''; ?>>Para paciente menor</option>
                <?php endif; ?>
            </select>
            <div class="form-text">Escolha para quem deseja agendar a consulta</div>
        </div>

        <!-- Seleção do paciente menor (aparece apenas quando selecionar "menor") -->
        <div class="mb-4 paciente-menor-group" id="paciente-menor-group">
            <label for="paciente_menor_id" class="form-label required">Selecionar Paciente Menor</label>
            <select class="form-select" id="paciente_menor_id" name="paciente_menor_id">
                <option value="">Selecione um paciente...</option>
                <?php foreach ($pacientes_menores as $paciente): 
                    $idade = date_diff(date_create($paciente['pmenor_datanasc']), date_create('today'))->y;
                ?>
                    <option value="<?php echo $paciente['pmenor_id']; ?>" 
                        <?php echo (isset($_POST['paciente_menor_id']) && $_POST['paciente_menor_id'] == $paciente['pmenor_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($paciente['pmenor_nome']); ?> (<?php echo $idade; ?> anos)
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Selecione o paciente menor do qual você é responsável</div>
        </div>

        <?php if (empty($pacientes_menores)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Você não possui pacientes menores vinculados à sua conta.
            </div>
        <?php endif; ?>

        <div class="mb-4">
            <label for="medico_id" class="form-label required">Selecionar Médico</label>
            <select class="form-select" id="medico_id" name="medico_id" required>
                <option value="">Selecione um médico...</option>
                <?php foreach ($medicos as $medico): ?>
                    <option value="<?php echo $medico['medicos_id']; ?>" 
                        <?php echo (isset($_POST['medico_id']) && $_POST['medico_id'] == $medico['medicos_id']) ? 'selected' : ''; ?>>
                        Dr(a). <?php echo htmlspecialchars($medico['medicos_nome']); ?> - <?php echo htmlspecialchars($medico['medicos_especialidade']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Escolha o profissional que deseja consultar</div>
        </div>

        <div class="mb-4">
            <label for="data_consulta" class="form-label required">Data da Consulta</label>
            <input type="date" class="form-control" id="data_consulta" name="data_consulta" 
                   value="<?php echo $_POST['data_consulta'] ?? ''; ?>" 
                   min="<?php echo date('Y-m-d'); ?>" required>
            <div class="form-text">Selecione uma data futura para sua consulta</div>
        </div>

        <div class="mb-4">
            <label for="hora_consulta" class="form-label required">Horário da Consulta</label>
            <select class="form-select" id="hora_consulta" name="hora_consulta" required>
                <option value="">Selecione um horário...</option>
                <option value="08:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '08:00:00') ? 'selected' : ''; ?>>08:00</option>
                <option value="09:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '09:00:00') ? 'selected' : ''; ?>>09:00</option>
                <option value="10:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '10:00:00') ? 'selected' : ''; ?>>10:00</option>
                <option value="11:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '11:00:00') ? 'selected' : ''; ?>>11:00</option>
                <option value="14:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '14:00:00') ? 'selected' : ''; ?>>14:00</option>
                <option value="15:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '15:00:00') ? 'selected' : ''; ?>>15:00</option>
                <option value="16:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '16:00:00') ? 'selected' : ''; ?>>16:00</option>
                <option value="17:00:00" <?php echo (isset($_POST['hora_consulta']) && $_POST['hora_consulta'] == '17:00:00') ? 'selected' : ''; ?>>17:00</option>
            </select>
            <div class="form-text">Horários disponíveis: Manhã (08h-12h) e Tarde (14h-18h)</div>
        </div>

        <div class="mb-4">
            <label for="observacoes" class="form-label">Observações/Motivo da Consulta</label>
            <textarea class="form-control" id="observacoes" name="observacoes" rows="4" 
                      placeholder="Descreva brevemente o motivo da consulta, sintomas ou informações relevantes..."><?php echo $_POST['observacoes'] ?? ''; ?></textarea>
            <div class="form-text">Opcional - Estas informações ajudarão o médico a se preparar para sua consulta</div>
        </div>

        <div class="button-group">
            <a href="consultas-paciente.php" class="btn btn-outline-secondary">
                <i class="fas fa-times me-2"></i>Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-calendar-check me-2"></i>Agendar Consulta
            </button>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Mostrar/ocultar seleção de paciente menor
    document.getElementById('tipo_paciente').addEventListener('change', function() {
        const pacienteMenorGroup = document.getElementById('paciente-menor-group');
        const pacienteMenorSelect = document.getElementById('paciente_menor_id');
        
        if (this.value === 'menor') {
            pacienteMenorGroup.style.display = 'block';
            pacienteMenorSelect.required = true;
        } else {
            pacienteMenorGroup.style.display = 'none';
            pacienteMenorSelect.required = false;
            pacienteMenorSelect.value = '';
        }
    });

    // Inicializar estado do campo paciente menor
    document.addEventListener('DOMContentLoaded', function() {
        const tipoPaciente = document.getElementById('tipo_paciente');
        if (tipoPaciente.value === 'menor') {
            document.getElementById('paciente-menor-group').style.display = 'block';
        }
    });

    document.getElementById('data_consulta').addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        if (selectedDate < today) {
            alert('Por favor, selecione uma data futura.');
            this.value = '';
        }
    });

    // Validação do formulário
    document.getElementById('formAgendarConsulta').addEventListener('submit', function(e) {
        const tipoPaciente = document.getElementById('tipo_paciente').value;
        const pacienteMenor = document.getElementById('paciente_menor_id').value;
        const medico = document.getElementById('medico_id').value;
        const data = document.getElementById('data_consulta').value;
        const hora = document.getElementById('hora_consulta').value;
        
        if (!tipoPaciente || !medico || !data || !hora) {
            e.preventDefault();
            alert('Por favor, preencha todos os campos obrigatórios.');
            return false;
        }
        
        if (tipoPaciente === 'menor' && !pacienteMenor) {
            e.preventDefault();
            alert('Por favor, selecione o paciente menor.');
            return false;
        }
        
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Agendando...';
        submitBtn.disabled = true;
    });
</script>

</body>
</html>