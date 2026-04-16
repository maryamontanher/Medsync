<?php
$senha = "";  // Coloque aqui a senha que você quer criptografar
$hash = password_hash($senha, PASSWORD_DEFAULT);
echo "Senha original: " . $senha . "\n";
echo "Senha criptografada (hash): " . $hash . "\n";
?>
