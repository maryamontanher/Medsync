<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    echo json_encode(["erro" => "Erro na conexão: " . $conn->connect_error]);
    exit;
}

$paciente_id = intval($_GET['id'] ?? 0);
$tipo = $_GET['tipo'] ?? '';

if ($paciente_id === 0 || ($tipo !== 'maior' && $tipo !== 'menor')) {
    echo json_encode(["erro" => "Parâmetros inválidos."]);
    exit;
}

$documentos = [];

// Função auxiliar para buscar documentos
function buscar_documentos($conn, $paciente_id, $tipo, $tabela, $tipo_doc) {
    if ($tabela === "atestado") {
        $sql = ($tipo === "maior")
            ? "SELECT d.atestado_id AS id, d.data_emissao, d.dias_afastamento AS dias, d.descricao,
                       '$tipo_doc' AS tipo, d.medico_id, m.medicos_nome AS medico_nome
               FROM atestado d
               JOIN medicos m ON d.medico_id = m.medicos_id
               WHERE d.paciente_maior_id = ?
               ORDER BY d.data_emissao DESC"
            : "SELECT d.atestado_id AS id, d.data_emissao, d.dias_afastamento AS dias, d.descricao,
                       '$tipo_doc' AS tipo, d.medico_id, m.medicos_nome AS medico_nome
               FROM atestado d
               JOIN medicos m ON d.medico_id = m.medicos_id
               WHERE d.paciente_menor_id = ?
               ORDER BY d.data_emissao DESC";
    } elseif ($tabela === "receita") {
        $sql = ($tipo === "maior")
            ? "SELECT d.receita_id AS id, d.data_emissao, d.validade_final AS dias, d.observacoes AS descricao,
                       '$tipo_doc' AS tipo, d.medico_id, m.medicos_nome AS medico_nome
               FROM receita d
               JOIN medicos m ON d.medico_id = m.medicos_id
               WHERE d.paciente_maior_id = ?
               ORDER BY d.data_emissao DESC"
            : "SELECT d.receita_id AS id, d.data_emissao, d.validade_final AS dias, d.observacoes AS descricao,
                       '$tipo_doc' AS tipo, d.medico_id, m.medicos_nome AS medico_nome
               FROM receita d
               JOIN medicos m ON d.medico_id = m.medicos_id
               WHERE d.paciente_menor_id = ?
               ORDER BY d.data_emissao DESC";
    } else {
        return [];
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [["erro" => "Erro no prepare ($tipo_doc): " . $conn->error]];
    }

    $stmt->bind_param("i", $paciente_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $docs = [];
    while ($row = $result->fetch_assoc()) {
        // padroniza os nomes dos campos para JS
        $docs[] = [
            "id" => $row["id"],
            "data" => $row["data_emissao"],
            "dias" => $row["dias"],
            "descricao" => $row["descricao"],
            "tipo" => $row["tipo"],
            "medico_id" => $row["medico_id"],
            "medico_nome" => $row["medico_nome"]
        ];
    }
    return $docs;
}

// 🔹 Atestados
$documentos = array_merge($documentos, buscar_documentos($conn, $paciente_id, $tipo, 'atestado', 'Atestado'));

// 🔹 Receitas
$documentos = array_merge($documentos, buscar_documentos($conn, $paciente_id, $tipo, 'receita', 'Receita'));

if (empty($documentos)) {
    echo json_encode(["mensagem" => "Nenhum documento encontrado."]);
} else {
    echo json_encode($documentos, JSON_UNESCAPED_UNICODE);
}

$conn->close();
?>
