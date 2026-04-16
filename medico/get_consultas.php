<?php
session_start();
if (!isset($_SESSION['medico_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Acesso não autorizado"]);
    exit();
}

$medico_id = $_SESSION['medico_id'];
$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Erro de conexão"]);
    exit();
}

// Consultas de pacientes maiores
$sql_maior = "
    SELECT c.consulta_id, c.data_consulta, c.hora_consulta, p.pmaior_nome AS paciente_nome, c.observacoes
    FROM consultas_maior c
    JOIN paciente_maior p ON c.paciente_maior_id = p.pmaior_id
    WHERE c.medico_id = ?
";
$stmt = $conn->prepare($sql_maior);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();
$consultas = [];

while ($row = $result->fetch_assoc()) {
    $consultas[] = [
        "title" => $row["paciente_nome"],
        "start" => $row["data_consulta"] . "T" . substr($row["hora_consulta"], 0, 5),
        "motivo" => $row["observacoes"]
    ];
}

// Consultas de pacientes menores
$sql_menor = "
    SELECT c.consulta_id, c.data_consulta, c.hora_consulta, p.pmenor_nome AS paciente_nome, c.observacoes
    FROM consultas_menor c
    JOIN paciente_menor p ON c.paciente_menor_id = p.pmenor_id
    WHERE c.medico_id = ?
";
$stmt = $conn->prepare($sql_menor);
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $consultas[] = [
        "title" => $row["paciente_nome"],
        "start" => $row["data_consulta"] . "T" . substr($row["hora_consulta"], 0, 5),
        "motivo" => $row["observacoes"]
    ];
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($consultas);
