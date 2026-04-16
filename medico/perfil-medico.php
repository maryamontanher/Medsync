<?php
session_start();

if (!isset($_SESSION['medico_id'])) {
    header('Location: login.php');
    exit;
}

$medico_id = $_SESSION['medico_id'];

$mysqli = new mysqli('localhost', 'root', '', 'medsync');
if ($mysqli->connect_errno) {
    die("Falha na conexão: " . $mysqli->connect_error);
}

$erro = '';
$sucesso = '';
$foto = null;

$stmt = $mysqli->prepare("SELECT medicos_nome, medicos_telefone, medicos_especialidade, medicos_sexo, medicos_datanasc, medicos_email, medicos_cpf, medicos_crm, medicos_uf_crm, medicos_foto, medico_senha FROM medicos WHERE medicos_id = ?");
$stmt->bind_param("i", $medico_id);
$stmt->execute();
$stmt->bind_result($nome, $telefone, $especialidade, $sexo, $data_nascimento, $email, $cpf, $crm, $ufcrm, $foto_banco, $senha_hash);
$stmt->fetch();
$stmt->close();

if (!empty($foto_banco)) {
    $foto = $foto_banco;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome_post = $_POST['nome'] ?? '';
    $telefone_post = $_POST['telefone'] ?? '';
    $especialidade_post = $_POST['especialidade'] ?? '';
    $sexo_post = $_POST['sexo'] ?? '';
    $data_nascimento_post = $_POST['data_nascimento'] ?? '';

    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    if (!$nome_post || !$telefone_post || !$especialidade_post || !$sexo_post || !$data_nascimento_post) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $alterar_senha = false;

        if (!empty($senha_atual) || !empty($nova_senha) || !empty($confirmar_senha)) {
            if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
                $erro = "Preencha todos os campos de senha para alterar.";
            } elseif (!password_verify($senha_atual, $senha_hash)) {
                $erro = "Senha atual incorreta.";
            } elseif ($nova_senha !== $confirmar_senha) {
                $erro = "Nova senha e confirmação não coincidem.";
            } else {
                $alterar_senha = true;
            }
        }

        $nome_foto = $foto;
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $pasta = __DIR__ . '/uploads/medicos/';
            if (!is_dir($pasta)) mkdir($pasta, 0755, true);

            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $nome_foto = 'medico_' . $medico_id . '_' . time() . '.' . $ext;
            $caminho = $pasta . $nome_foto;

            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $caminho)) {
                $erro = "Falha ao salvar a foto de perfil.";
            }
        }

        if (!$erro) {
            if ($alterar_senha) {
                $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE medicos SET medicos_nome=?, medicos_telefone=?, medicos_especialidade=?, medicos_sexo=?, medicos_datanasc=?, medico_senha=?, medicos_foto=? WHERE medicos_id=?");
                $stmt->bind_param("sssssssi", $nome_post, $telefone_post, $especialidade_post, $sexo_post, $data_nascimento_post, $nova_senha_hash, $nome_foto, $medico_id);
            } else {
                $stmt = $mysqli->prepare("UPDATE medicos SET medicos_nome=?, medicos_telefone=?, medicos_especialidade=?, medicos_sexo=?, medicos_datanasc=?, medicos_foto=? WHERE medicos_id=?");
                $stmt->bind_param("ssssssi", $nome_post, $telefone_post, $especialidade_post, $sexo_post, $data_nascimento_post, $nome_foto, $medico_id);
            }

            if ($stmt->execute()) {
                $sucesso = "Perfil atualizado com sucesso!";
                $nome = $nome_post;
                $telefone = $telefone_post;
                $especialidade = $especialidade_post;
                $sexo = $sexo_post;
                $data_nascimento = $data_nascimento_post;
                $foto = $nome_foto;
            } else {
                $erro = "Erro ao atualizar perfil: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Perfil do Médico - Medsync</title>
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
  <a href="painelmed.php"><i class="fa-solid fa-arrow-left"></i> Voltar ao painel</a>
</nav>

<div class="perfil-container">
  <div class="foto-lado">
    <form method="post" action="" id="formPerfil" enctype="multipart/form-data" class="form-foto">
      <h2>Foto de Perfil</h2>

      <div
          class="foto-perfil"
          id="imagemPerfil"
          style="background-image: url('uploads/medicos/<?= htmlspecialchars($foto ?: 'default.jpg') ?>?t=<?= time() ?>');"
          onclick="document.getElementById('fotoInput').click()"
          title="Clique para alterar a foto"
      ></div>

      <div class="input-file-wrapper">
        <button type="button" class="btn-upload" onclick="document.getElementById('fotoInput').click()">Escolher foto</button>
        <input type="file" accept="image/*" id="fotoInput" name="foto" onchange="carregarImagem(this)" />
      </div>

      <input type="hidden" name="senha_atual" />
      <input type="hidden" name="nova_senha" />
      <input type="hidden" name="confirmar_senha" />
  </div>

  <div class="form-lado">
    <h2>Dados do Médico</h2>
    <?php if ($erro): ?><div class="msg-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="msg-sucesso"><?= htmlspecialchars($sucesso) ?></div><?php endif; ?>

    <div class="form-group">
        <div class="form-col">
          <label>Nome</label>
          <input type="text" name="nome" value="<?= htmlspecialchars($nome) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>CPF</label>
          <input type="text" value="<?= htmlspecialchars($cpf) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label>CRM</label>
          <input type="text" value="<?= htmlspecialchars($crm) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>UF do CRM</label>
          <input type="text" value="<?= htmlspecialchars($ufcrm) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label>Telefone</label>
          <input type="text" name="telefone" value="<?= htmlspecialchars($telefone) ?>" required />
        </div>
        <div class="form-col">
          <label>Email</label>
          <input type="email" value="<?= htmlspecialchars($email) ?>" required />
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label>Especialidade</label>
          <input type="text" name="especialidade" value="<?= htmlspecialchars($especialidade) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
        <div class="form-col">
          <label>Sexo</label>
          <select name="sexo" disabled >
            <option value="Masculino" <?= $sexo === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
            <option value="Feminino" <?= $sexo === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
            <option value="Outro" <?= $sexo === 'Outro' ? 'selected' : '' ?>>Outro</option>
          </select>
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>

      <div class="form-group">
        <div class="form-col">
          <label>Data de Nascimento</label>
          <input type="date" name="data_nascimento" value="<?= htmlspecialchars($data_nascimento) ?>" disabled />
          <span class="observacao">Solicite alteração à administração</span>
        </div>
      </div>

      <div class="botoes">
        <button type="submit" class="btn-editar">Salvar Alterações</button>
        <button type="button" class="btn-senha" onclick="abrirModal()">Alterar Senha</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Senha -->
<div class="modal" id="modalSenha">
  <div class="modal-conteudo">
    <h3>Alterar Senha</h3>
    <label>Senha Atual</label>
    <input type="password" id="senha_atual_modal">
    <label>Nova Senha</label>
    <input type="password" id="nova_senha_modal">
    <label>Confirmar Nova Senha</label>
    <input type="password" id="confirmar_senha_modal">
    <div class="modal-botoes">
      <button class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
      <button class="btn-confirmar" onclick="salvarSenha()">Confirmar</button>
    </div>
  </div>
</div>

<script>
function carregarImagem(input){
    if(input.files && input.files[0]){
        const reader = new FileReader();
        reader.onload = function(e){
            document.getElementById('imagemPerfil').style.backgroundImage = 'url(' + e.target.result + ')';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function abrirModal(){ document.getElementById('modalSenha').style.display='flex'; }
function fecharModal(){ document.getElementById('modalSenha').style.display='none'; }

function salvarSenha(){
    document.querySelector('input[name="senha_atual"]').value = document.getElementById('senha_atual_modal').value;
    document.querySelector('input[name="nova_senha"]').value = document.getElementById('nova_senha_modal').value;
    document.querySelector('input[name="confirmar_senha"]').value = document.getElementById('confirmar_senha_modal').value;
    fecharModal();
    document.getElementById('formPerfil').submit();
}
</script>
</body>
</html>