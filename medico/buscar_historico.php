<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    echo json_encode(["erro" => "Erro na conexão: " . $conn->connect_error]);
    exit;
}

$id = $_GET['id'] ?? null;
$tipo = $_GET['tipo'] ?? null;

if (!$id || !$tipo) {
    echo json_encode(["erro" => "Parâmetros id e tipo obrigatórios"]);
    exit;
}

if ($tipo === "maior") {
    $sql = "SELECT c.consulta_id, c.medico_id, c.paciente_maior_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
                   m.medicos_nome AS medicos_nome
            FROM consultas_maior c
            LEFT JOIN medicos m ON c.medico_id = m.medicos_id
            WHERE c.paciente_maior_id = ?
            ORDER BY c.data_consulta DESC, c.hora_consulta DESC";
} else {
    $sql = "SELECT c.consulta_id, c.medico_id, c.paciente_menor_id, c.data_consulta, c.hora_consulta, c.status, c.observacoes,
                   m.medicos_nome AS medicos_nome
            FROM consultas_menor c
            LEFT JOIN medicos m ON c.medico_id = m.medicos_id
            WHERE c.paciente_menor_id = ?
            ORDER BY c.data_consulta DESC, c.hora_consulta DESC";
}


$stmt = $conn->prepare($sql);

if(!$stmt){
    echo json_encode(["erro" => "Erro no prepare: " . $conn->error]);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

$consultas = $result->fetch_all(MYSQLI_ASSOC);
echo json_encode($consultas, JSON_UNESCAPED_UNICODE);
