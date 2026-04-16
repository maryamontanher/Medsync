<?php
session_start();
if (!isset($_SESSION['farmacia_id'])) {
    http_response_code(403);
    exit('Acesso negado');
}

if (!isset($_POST['id']) || !isset($_POST['acao'])) {
    http_response_code(400);
    exit('Parâmetros inválidos');
}

$id = intval($_POST['id']);
$acao = $_POST['acao'];

$conn = new mysqli("localhost", "root", "", "medsync");
if ($conn->connect_error) {
    http_response_code(500);
    exit('Erro de conexão');
}

$status = ($acao === 'validar') ? 'vencida' : 'valida';

$stmt = $conn->prepare("UPDATE receita SET status_receita = ? WHERE receita_id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo "OK";
} else {
    http_response_code(500);
    echo "Erro ao atualizar";
}

$stmt->close();
$conn->close();
