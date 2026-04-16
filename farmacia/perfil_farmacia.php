<?php
session_start();

$farmacia_id = $_SESSION['farmacia_id'];
$mysqli = new mysqli('localhost', 'root', '', 'medsync');
if ($mysqli->connect_errno) {
    die("Falha na conexão: " . $mysqli->connect_error);
}

$erro = '';
$sucesso = '';

// Se o upload da foto foi feito
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $nome_foto = null;
    $pasta = __DIR__ . '/uploads/farmacia/';
    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }
    $extensao = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $nome_foto = 'farmacia_' . $farmacia_id . '_' . time() . '.' . $extensao;
    $caminho = $pasta . $nome_foto;

    if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
        $erro = "Falha ao salvar a foto de perfil.";
    } else {
        $stmt = $mysqli->prepare("UPDATE farmacia SET farmacia_foto = ? WHERE farmacia_id = ?");
        $stmt->bind_param("si", $nome_foto, $farmacia_id);
        if ($stmt->execute()) {
            $sucesso = "Foto de perfil atualizada com sucesso!";
            $farmacia_foto = $nome_foto;
        } else {
            $erro = "Erro ao atualizar foto: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Se o formulário principal foi enviado (sem upload de foto)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_FILES['foto'])) {
    $farmacia_nome_fant = $_POST['farmacia_nome_fant'] ?? '';
    $farmacia_razao_social = $_POST['farmacia_razao_social'] ?? '';
    $farmacia_cnpj = $_POST['farmacia_cnpj'] ?? '';
    $farmacia_inscricao_estadual = $_POST['farmacia_inscricao_estadual'] ?? '';
    $farmacia_inscricao_municipal = $_POST['farmacia_inscricao_municipal'] ?? '';
    $farmacia_endereco = $_POST['farmacia_endereco'] ?? '';
    $farmacia_uf = $_POST['farmacia_uf'] ?? '';
    $farmacia_cep = $_POST['farmacia_cep'] ?? '';
    $farmacia_telefone = $_POST['farmacia_telefone'] ?? '';

    if (!$farmacia_nome_fant || !$farmacia_razao_social || !$farmacia_cnpj || !$farmacia_inscricao_estadual || !$farmacia_inscricao_municipal || !$farmacia_endereco || !$farmacia_uf || !$farmacia_cep || !$farmacia_telefone) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $stmt = $mysqli->prepare("UPDATE farmacia SET farmacia_nome_fant = ?, farmacia_razao_social = ?, farmacia_cnpj = ?, farmacia_inscricao_estadual = ?, farmacia_inscricao_municipal = ?, farmacia_endereco = ?, farmacia_uf = ?, farmacia_cep = ?, farmacia_telefone = ? WHERE farmacia_id = ?");
        $stmt->bind_param("sssssssssi", $farmacia_nome_fant, $farmacia_razao_social, $farmacia_cnpj, $farmacia_inscricao_estadual, $farmacia_inscricao_municipal, $farmacia_endereco, $farmacia_uf, $farmacia_cep, $farmacia_telefone, $farmacia_id);
        if ($stmt->execute()) {
            $sucesso = "Perfil atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar perfil: " . $stmt->error;
        }
        $stmt->close();
    }
}

$stmt = $mysqli->prepare("SELECT farmacia_nome_fant, farmacia_razao_social, farmacia_cnpj, farmacia_inscricao_estadual, farmacia_inscricao_municipal, farmacia_endereco, farmacia_uf, farmacia_cep, farmacia_telefone, farmacia_email, farmacia_foto FROM farmacia WHERE farmacia_id = ?");
$stmt->bind_param("i", $farmacia_id);
$stmt->execute();
$stmt->bind_result($farmacia_nome_fant, $farmacia_razao_social, $farmacia_cnpj, $farmacia_inscricao_estadual, $farmacia_inscricao_municipal, $farmacia_endereco, $farmacia_uf, $farmacia_cep, $farmacia_telefone, $farmacia_email, $farmacia_foto);
$stmt->fetch();
$stmt->close();
$mysqli->close();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Perfil da Farmácia - Medsync</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
<style>
    :root {
    --verde: #0a4e53;
    --verde-claro: #e3f7f5;
    --cinza: #f0f0f0;
    --cinza-escuro: #6c757d;
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: var(--cinza);
    display: flex; justify-content: center; align-items: center; min-height: 100vh;
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
  .form-foto {
  display: flex;
  flex-direction: column;
  align-items: center;
  width: 100%;
}

  .perfil-container {
    background: white;
    width: 90%;
    max-width: 950px;
    min-height: 500px;
    border-radius: 20px;
    display: flex;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
    overflow: hidden;
  }
  .foto-lado {
    width: 35%;
    background-color: var(--verde-claro);
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    padding: 2rem;
  }
  .foto-lado h2 {
    color: var(--verde);
    margin-bottom: 20px;
    text-align: center;
  }
  .foto-perfil {
    width: 160px; height: 160px;
    border-radius: 50%;
    background-color: #ccc;
    background-size: cover;
    background-position: center;
    border: 4px solid var(--verde);
    cursor: pointer;
  }
  .input-file-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    margin-top: 15px;
  }
  .btn-upload {
    border: none;
    color: white;
    background-color: var(--verde);
    padding: 10px 16px;
    border-radius: 8px;
    font-size: 14px;
    cursor: pointer;
    font-weight: bold;
    transition: 0.3s;
  }
  .btn-upload:hover {
    background-color: #086b70;
  }
  .input-file-wrapper input[type="file"] {
    font-size: 100px;
    position: absolute; left: 0; top: 0;
    opacity: 0;
    cursor: pointer;
  }
  .form-lado {
    width: 65%;
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 15px;
  }
  .form-lado h2 {
    color: var(--verde);
    margin-bottom: 20px;
  }
  .form-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
  }
  .form-col {
    flex: 1;
    min-width: 240px;
    display: flex;
    flex-direction: column;
  }
  .form-col label {
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--verde);
    font-size: 14px;
  }
  .form-col input,
  .form-col select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
    font-size: 14px;
    background-color: #fff;
  }
  .form-col input[disabled] {
    background-color: #f9f9f9;
    color: #888;
  }
  .observacao {
    font-size: 11px;
    color: #999;
    margin-top: 4px;
  }
  .botoes {
    display: flex;
    gap: 15px;
    margin-top: 20px;
  }
  .btn-editar,
  .btn-senha {
    background-color: var(--verde);
    color: white;
    padding: 12px 18px;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: 0.3s;
    font-size: 14px;
  }
  .btn-editar:hover,
  .btn-senha:hover {
    background-color: #086b70;
  }
  /* Modal senha */
  .modal {
    display: none;
    position: fixed; z-index: 9999;
    left: 0; top: 0; width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.5);
    justify-content: center; align-items: center;
  }
  .modal-conteudo {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    width: 350px;
    box-shadow: 0 0 10px #0003;
  }
  .modal-conteudo h3 {
    margin-top: 0;
    margin-bottom: 1rem;
    color: var(--verde);
  }
  .modal-conteudo label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: var(--verde);
  }
  .modal-conteudo input {
    width: 100%;
    margin-bottom: 15px;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ccc;
    font-size: 14px;
  }
  .modal-botoes {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
  }
  .btn-cancelar,
  .btn-confirmar {
    padding: 10px 16px;
    border-radius: 8px;
    border: none;
    font-weight: bold;
    cursor: pointer;
  }
  .btn-cancelar {
    background: #ccc;
    color: #333;
  }
  .btn-confirmar {
    background: var(--verde);
    color: white;
  }
  .btn-cancelar:hover {
    background: #999;
  }
  .btn-confirmar:hover {
    background: #086b70;
  }
  .msg-erro {
    color: #c00;
    font-weight: 600;
    margin-bottom: 10px;
  }
  .msg-sucesso {
    color: green;
    font-weight: 600;
    margin-bottom: 10px;
  }
</style>
</head>
<body>
  <nav class="navbar">
  <img src="../images/logo/logo_branca.png" alt="Logo" />
  <a href="painel-farm.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>
<div class="perfil-container">
  <div class="foto-lado">
    <form method="post" enctype="multipart/form-data" class="form-foto">
      <h2>Foto de Perfil</h2>
      
      <div class="foto-perfil" style="background-image: url('uploads/farmacia/<?= htmlspecialchars($farmacia_foto ?: 'default.jpg') ?>');" onclick="document.getElementById('fotoInput').click()" title="Clique para alterar a foto"></div>
      <div style="height: 20px;"></div>
      <input type="file" accept="image/*" id="fotoInput" name="foto" style="display:none" onchange="this.form.submit()" />
      <button type="button" class="btn-editar" onclick="document.getElementById('fotoInput').click()">Escolher foto</button>
    </form>
  </div>
  <div class="form-lado">
    <h2>Dados da Farmácia</h2>
    <?php if ($erro): ?>
      <div class="msg-erro"><?= htmlspecialchars($erro) ?></div>
    <?php elseif ($sucesso): ?>
      <div class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
      <div class="form-group">
        <div class="form-col">
          <label>Nome Fantasia</label>
          <input type="text" name="farmacia_nome_fant" value="<?= htmlspecialchars($farmacia_nome_fant) ?>" disabled maxlength="80" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>Razão Social</label>
          <input type="text" name="farmacia_razao_social" value="<?= htmlspecialchars($farmacia_razao_social) ?>" disabled maxlength="45" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>
      <div class="form-group">
        <div class="form-col">
          <label>CNPJ</label>
          <input type="text" name="farmacia_cnpj" value="<?= htmlspecialchars($farmacia_cnpj) ?>" disabled maxlength="14" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>Inscrição Estadual</label>
          <input type="text" name="farmacia_inscricao_estadual" value="<?= htmlspecialchars($farmacia_inscricao_estadual) ?>" disabled maxlength="9" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>
      <div class="form-group">
        <div class="form-col">
          <label>Inscrição Municipal</label>
          <input type="text" name="farmacia_inscricao_municipal" value="<?= htmlspecialchars($farmacia_inscricao_municipal) ?>" disabled maxlength="15" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>Endereço</label>
          <input type="text" name="farmacia_endereco" value="<?= htmlspecialchars($farmacia_endereco) ?>" disabled maxlength="255" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>
      <div class="form-group">
        <div class="form-col">
          <label>UF</label>
          <input type="text" name="farmacia_uf" value="<?= htmlspecialchars($farmacia_uf) ?>" disabled maxlength="2" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>CEP</label>
          <input type="text" name="farmacia_cep" value="<?= htmlspecialchars($farmacia_cep) ?>" disabled maxlength="10" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>
      <div class="form-group">
        <div class="form-col">
          <label>Telefone</label>
          <input type="text" name="farmacia_telefone" value="<?= htmlspecialchars($farmacia_telefone) ?>" disabled maxlength="11" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>Email</label>
          <input type="email" name="farmacia_email" value="<?= htmlspecialchars($farmacia_email) ?>" disabled maxlength="100" />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>
      <div class="botoes">
        <button type="submit" class="btn-editar">Salvar Alterações</button>
  <button type="button" class="btn-senha" onclick="abrirModal()">Alterar Senha</button>
      
      <!-- Campos ocultos para senha (preenchidos pelo modal) -->
      <input type="hidden" name="senha_atual" />
      <input type="hidden" name="nova_senha" />
      <input type="hidden" name="confirmar_senha" />
    </form>
  </div>
</div>

<!-- Modal Alterar Senha -->
<div class="modal" id="modalSenha">
  <div class="modal-conteudo">
    <h3>Alterar Senha</h3>
    <form method="post" id="formSenha">
      <label for="senha_atual">Senha Atual</label>
      <input type="password" id="senha_atual" name="senha_atual"  disabled/>

      <label for="nova_senha">Nova Senha</label>
      <input type="password" id="nova_senha" name="nova_senha" />

      <label for="confirmar_senha">Confirmar Nova Senha</label>
      <input type="password" id="confirmar_senha" name="confirmar_senha" />

      <div class="modal-botoes">
        <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
        <button type="button" class="btn-confirmar" onclick="confirmarSenha()">Confirmar</button>
      </div>
    </form>
  </div>
</div>

<script>
  // Função para carregar imagem no círculo de perfil (só visual, não envia)
  function carregarImagem(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('imagemPerfil').style.backgroundImage = 'url(' + e.target.result + ')';
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  function abrirModal() {
    document.getElementById('modalSenha').style.display = 'flex';
  }
  function fecharModal() {
    document.getElementById('modalSenha').style.display = 'none';
    // Limpar campos da senha
    document.getElementById('senha_atual').value = '';
    document.getElementById('nova_senha').value = '';
    document.getElementById('confirmar_senha').value = '';
  }
  function confirmarSenha() {
    // Copia os campos de senha para o formulário principal e submete
    const formPerfil = document.getElementById('formPerfil');
    formPerfil.querySelector('input[name="senha_atual"]').value = document.getElementById('senha_atual').value;
    formPerfil.querySelector('input[name="nova_senha"]').value = document.getElementById('nova_senha').value;
    formPerfil.querySelector('input[name="confirmar_senha"]').value = document.getElementById('confirmar_senha').value;
    fecharModal();
    formPerfil.submit();
  }
</script>

</body>
</html>


