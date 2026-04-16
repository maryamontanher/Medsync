<?php
session_start();
require_once '../conexao.php';

if (!isset($_SESSION['medico_id'])) {
    die('Acesso não autorizado');
}

$receita_id = mysqli_real_escape_string($conn, $_GET['id']);

$query = "SELECT r.*, m.medicos_nome, m.medicos_crm, 
          COALESCE(pm.pmaior_nome, pmenor.pmenor_nome) as paciente_nome,
          COALESCE(pm.pmaior_cpf, pmenor.pmenor_cpf) as paciente_cpf
          FROM receita r
          INNER JOIN medicos m ON r.medico_id = m.medicos_id
          LEFT JOIN paciente_maior pm ON r.paciente_maior_id = pm.pmaior_id
          LEFT JOIN paciente_menor pmenor ON r.paciente_menor_id = pmenor.pmenor_id
          WHERE r.receita_id = '$receita_id'";

$result = mysqli_query($conn, $query);
$receita = mysqli_fetch_assoc($result);

if (!$receita) {
    echo '<div class="alert alert-danger">Receita não encontrada</div>';
    exit;
}
?>

<div class="documento-modal">
    <div class="documento-header">
        <div class="documento-title">RECEITA MÉDICA</div>
        <div class="documento-subtitle">MedSync - Sistema de Gestão Médica</div>
    </div>

    <div class="documento-info">
        <div class="info-row">
            <span class="info-label">Paciente:</span>
            <span class="info-value"><?php echo htmlspecialchars($receita['paciente_nome']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">CPF:</span>
            <span class="info-value"><?php echo htmlspecialchars($receita['paciente_cpf']); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Data de Emissão:</span>
            <span class="info-value"><?php echo date('d/m/Y', strtotime($receita['data_emissao'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Validade:</span>
            <span class="info-value"><?php echo date('d/m/Y', strtotime($receita['validade_final'])); ?></span>
        </div>
    </div>

    <?php if (!empty($receita['observacoes'])): ?>
    <div class="documento-content">
        <?php echo nl2br(htmlspecialchars($receita['observacoes'])); ?>
    </div>
    <?php endif; ?>

    <div class="documento-footer">
        <div class="assinatura">
            <div class="assinatura-line"></div>
            <div class="assinatura-text">Dr. <?php echo htmlspecialchars($receita['medicos_nome']); ?></div>
            <div class="assinatura-text">CRM: <?php echo htmlspecialchars($receita['medicos_crm']); ?></div>
        </div>
    </div>
</div>

<!-- Versão para impressão (oculta) -->
<div id="printReceita" style="display: none;">
    <div class="documento-impressao">
        <div class="documento-header">
            <div class="documento-title">RECEITA MÉDICA</div>
            <div class="documento-subtitle">MedSync - Sistema de Gestão Médica</div>
        </div>

        <div class="documento-info">
            <div class="info-row">
                <span class="info-label">Paciente:</span>
                <span class="info-value"><?php echo htmlspecialchars($receita['paciente_nome']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">CPF:</span>
                <span class="info-value"><?php echo htmlspecialchars($receita['paciente_cpf']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Data de Emissão:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($receita['data_emissao'])); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Validade:</span>
                <span class="info-value"><?php echo date('d/m/Y', strtotime($receita['validade_final'])); ?></span>
            </div>
        </div>

        <?php if (!empty($receita['observacoes'])): ?>
        <div class="documento-content">
            <?php echo nl2br(htmlspecialchars($receita['observacoes'])); ?>
        </div>
        <?php endif; ?>

        <div class="documento-footer">
            <div class="assinatura">
                <div class="assinatura-line"></div>
                <div class="assinatura-text">Dr. <?php echo htmlspecialchars($receita['medicos_nome']); ?></div>
                <div class="assinatura-text">CRM: <?php echo htmlspecialchars($receita['medicos_crm']); ?></div>
            </div>
        </div>
    </div>
</div>

<script>
function imprimirReceitaModal() {
    var conteudo = document.getElementById('printReceita').innerHTML;
    var janela = window.open('', '_blank');
    janela.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receita Médica</title>
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
</script>