<?php
session_start();
if(!isset($_SESSION['pmenor_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../css/index.css">
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <title>Medsync | Painel do Paciente Menor de Idade</title>

    <style>
    * {
        box-sizing: border-box;
    }
    
    body {
        margin: 0;
        padding: 0;
        overflow-x: hidden;
        font-family: Arial, sans-serif;
    }

    .navbar {
        background-color: #3c9181;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        width: 100%;
        position: fixed;
        top: 0;
        z-index: 1000;
    }

    .logo img {
        height: 40px;
    }

    .nav-links {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 20px;
    }

    .nav-links a {
        color: white;
        text-decoration: none;
        font-weight: 500;
    }

    .nav-links a:hover {
        color: #e0e0e0;
    }

    .main-container {
        margin-top: 70px;
        width: 100%;
        padding: 0;
    }
   /* ---------- CARROSSEL RESPONSIVO ---------- */
        .carrossel-container {
            width: 100%;
            position: relative;
            overflow: hidden;
            height: 200px; /* Altura fixa reduzida */
        }

        .carrossel-painel {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .carrossel-painel .slides {
            width: 300%;
            height: 100%;
            display: flex;
            transition: transform 0.5s ease;
        }

        .carrossel-painel .slide {
            flex: 0 0 33.333333%;
            width: 33.333333%;
            position: relative;
        }

        .carrossel-painel img {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Mudei de 'cover' para 'contain' para evitar cortes */
            display: block;
            background-color: #f8f9fa; /* Cor de fundo caso a imagem não preencha totalmente */
        }

        .carrossel-painel .arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            font-size: 1.2rem;
            cursor: pointer;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .carrossel-painel .arrow:hover {
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-50%) scale(1.1);
        }

        .carrossel-painel .arrow.left {
            left: 15px;
        }

        .carrossel-painel .arrow.right {
            right: 15px;
        }

        /* Indicadores do carrossel */
        .carrossel-indicators {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 10;
        }

        .carrossel-indicators .indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .carrossel-indicators .indicator.active {
            background: rgba(255, 255, 255, 0.9);
            transform: scale(1.2);
        }
        /* ---------- FIM DO CARROSSEL ---------- */
    .atalhos {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        gap: 15px;
        margin: 30px auto;
        max-width: 1200px;
        padding: 0 20px;
    }

    .card-botao {
        background-color: #3c9181;
        color: #fff;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        transition: all 0.3s ease;
        width: 150px;
        min-height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }

    .card-botao a {
        text-decoration: none;
        color: white;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        width: 100%;
    }

    .card-botao:hover {
        background-color: #4ab19e;
        transform: translateY(-3px);
        box-shadow: 0 5px 10px rgba(0,0,0,0.2);
    }

    .icone i {
        font-size: 1.8rem;
    }

    .card-botao p {
        margin: 0;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* ---------- CARD AGENDAMENTO MAIS PRÓXIMO - ESTILO MELHORADO ---------- */
    .card-agendamento {
        max-width: 500px;
        width: 90%;
        margin: 30px auto;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border: none;
        position: relative;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .card-agendamento:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
    }

    .card-titulo {
        background: linear-gradient(135deg, #3c9181 0%, #4bb9a5 100%);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .card-titulo::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transform: translateX(-100%);
        transition: transform 0.6s;
    }

    .card-titulo:hover::before {
        transform: translateX(100%);
    }

    .info-icon {
        cursor: pointer;
        font-size: 1.1rem;
        background: rgba(255,255,255,0.2);
        width: 25px;
        height: 25px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
    }

    .info-icon:hover {
        background: rgba(255,255,255,0.3);
    }

    .card-conteudo {
        padding: 25px 20px;
        text-align: center;
        position: relative;
    }

    .agendamento-info {
        position: relative;
        z-index: 1;
    }

    .agendamento-info p {
        margin: 10px 0;
        font-size: 1rem;
        color: #333;
    }

    .agendamento-info .data-hora {
        font-size: 1.2rem;
        font-weight: 600;
        color: #3c9181;
        margin: 15px 0;
    }

    .agendamento-info .medico {
        font-size: 1.1rem;
        font-weight: 500;
        color: #2c3e50;
        margin: 10px 0;
        padding: 8px 0;
        border-top: 1px solid #eaeaea;
        border-bottom: 1px solid #eaeaea;
    }

    .agendamento-info .status {
        display: inline-block;
        background: #e8f5e8;
        color: #2e7d32;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        margin: 10px 0;
    }

    .btn-agendar {
        background: linear-gradient(135deg, #3c9181 0%, #4bb9a5 100%);
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-top: 15px;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(60, 145, 129, 0.3);
        font-size: 0.95rem;
    }

    .btn-agendar:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(60, 145, 129, 0.4);
        color: white;
    }

    .sem-agendamento {
        text-align: center;
        padding: 20px 0;
    }

    .sem-agendamento p {
        color: #666;
        margin-bottom: 15px;
        font-size: 1rem;
    }

    .sem-agendamento .icone-vazio {
        font-size: 2.5rem;
        color: #3c9181;
        opacity: 0.7;
        margin-bottom: 15px;
        display: block;
    }

    @media (max-width: 768px) {
        .navbar {
            padding: 10px 15px;
        }
        
        .logo img {
            height: 35px;
        }
        
        .nav-links {
            gap: 15px;
        }
        
        .nav-links a {
            font-size: 0.9rem;
        }
        
        .main-container {
            margin-top: 65px;
        }
        
        .carrossel-painel {
            height: 200px;
        }
        
        .card-botao {
            width: 140px;
            min-height: 90px;
        }
        
        .icone i {
            font-size: 1.5rem;
        }
        
        .card-botao p {
            font-size: 0.85rem;
        }
        
        .card-agendamento {
            margin: 25px auto;
            max-width: 400px;
        }
        
        .card-titulo {
            padding: 12px 15px;
            font-size: 1rem;
        }
        
        .card-conteudo {
            padding: 20px 15px;
        }
        
        .agendamento-info .data-hora {
            font-size: 1.1rem;
        }
        
        .agendamento-info .medico {
            font-size: 1rem;
        }
    }

    @media (max-width: 480px) {
        .main-container {
            margin-top: 60px;
        }
        
        .atalhos {
            gap: 10px;
        }
        
        .card-botao {
            width: 120px;
            min-height: 80px;
            padding: 12px;
        }
        
        .carrossel-painel {
            height: 150px;
        }
        
        .card-agendamento {
            margin: 20px auto;
            width: 95%;
        }
        
        .card-titulo {
            padding: 10px 15px;
        }
        
        .card-conteudo {
            padding: 15px 12px;
        }
        
        .agendamento-info p {
            font-size: 0.9rem;
        }
        
        .agendamento-info .data-hora {
            font-size: 1rem;
        }
        
        .agendamento-info .medico {
            font-size: 0.95rem;
        }
    }
</style>

</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../images/logo/logo_branca.png" alt="Medsync Logo"> 
        </div>
        <ul class="nav-links">
            <li><a href="inicio.html">Início</a></li>
            <li><a href="atestados-menor.php">Seus Atestados</a></li>
            <li><a href="#" onclick="confirmarSaida(event)"><i class="fa-solid fa-right-from-bracket"></i></a></li>
        </ul>
    </nav>

    <div class="main-container">
        <div class="carrossel-painel">
            <div class="slides">
                <div class="slide">
                    <img src="../images/painel_paciente/carrossel1.png" alt="Cuide da sua saúde">
                </div>
                <div class="slide">
                    <img src="../images/painel_paciente/carrossel2.png" alt="Agende suas consultas">
                </div>
                <div class="slide">
                    <img src="../images/painel_paciente/carrossel3.png" alt="Acompanhe seus exames">
                </div>
            </div>
            <button class="arrow left">&#10094;</button>
            <button class="arrow right">&#10095;</button>
        </div>

        <div class="atalhos">
            <div class="card-botao">
                <a href="consultaspaciente-menor.php">
                    <div class="icone"><i class="bi bi-calendar"></i></div>
                    <p>Agendamentos</p>
                </a>
            </div>

            <div class="card-botao">
                <a href="buscar-medico.php">
                    <div class="icone"><i class="bi bi-search"></i></div>
                    <p>Buscar médicos</p>
                </a>
            </div>

            <div class="card-botao">
                <a href="receitas-menor.php">
                    <div class="icone"><i class="bi bi-clipboard2"></i></div>
                    <p>Suas receitas</p>
                </a>
            </div>

            <div class="card-botao">
                <a href="atestados-menor.php">
                    <div class="icone"><i class="bi bi-file-earmark-text"></i></div>
                    <p>Atestados</p>
                </a>
            </div>

            <div class="card-botao">
                <a href="perfilpaciente-menor.php">
                    <div class="icone"><i class="bi bi-person-fill"></i></div>
                    <p>Perfil</p>
                </a>
            </div>
        </div>

        <div class="card-agendamento">
            <div class="card-titulo">
                <span>Próximo agendamento</span>
                <span class="info-icon" title="Seu próximo compromisso agendado">&#9432;</span>
            </div>
            <div class="card-conteudo">
                <?php
                    $pmaior_id = (int)$_SESSION['pmaior_id'];
                    
                    $conn = new mysqli("localhost", "root", "", "medsync");
                    if ($conn->connect_error) {
                        die("Erro na conexão: " . $conn->connect_error);
                    }

                    // Consulta para buscar agendamentos dos pacientes menores vinculados ao responsável
                    $sql = "SELECT cm.data_consulta, cm.hora_consulta, cm.status, 
                                   m.medicos_nome, m.medicos_sexo,
                                   pm.pmenor_nome
                            FROM consultas_menor cm 
                            INNER JOIN medicos m ON cm.medico_id = m.medicos_id 
                            INNER JOIN paciente_menor pm ON cm.paciente_menor_id = pm.pmenor_id
                            WHERE cm.responsavel_id = $pmaior_id 
                            AND cm.status = 'Agendada'
                            AND CONCAT(cm.data_consulta, ' ', cm.hora_consulta) >= NOW()
                            ORDER BY cm.data_consulta ASC, cm.hora_consulta ASC 
                            LIMIT 1";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        $row = $result->fetch_assoc();
                        $data = date('d/m/Y', strtotime($row['data_consulta']));
                        
                        // Definir o título de acordo com o sexo do médico
                        $titulo = "Dr.";
                        if ($row['medicos_sexo'] == 'Feminino') {
                            $titulo = "Dra.";
                        } elseif ($row['medicos_sexo'] == 'Outro') {
                            $titulo = "Dr(a).";
                        }
                        
                        echo '<div class="agendamento-info">';
                        echo '<p>Próximo agendamento de:</p>';
                        echo '<p><strong>' . htmlspecialchars($row['pmenor_nome']) . '</strong></p>';
                        echo '<div class="data-hora">';
                        echo '<p><strong>' . htmlspecialchars($data) . '</strong></p>';
                        echo '<p><strong>' . htmlspecialchars($row['hora_consulta']) . '</strong></p>';
                        echo '</div>';
                        echo '<div class="medico">';
                        echo '<p><strong>' . htmlspecialchars($titulo) . ' ' . htmlspecialchars($row['medicos_nome']) . '</strong></p>';
                        echo '</div>';
                        echo '<div class="status">' . htmlspecialchars($row['status']) . '</div>';
                        echo '</div>';
                    } else {
                        echo '<div class="sem-agendamento">';
                        echo '<i class="bi bi-calendar-x icone-vazio"></i>';
                        echo '<p>Nenhum agendamento futuro encontrado.</p>';
                        echo '<a href="agendar-consulta.php" class="btn-agendar">';
                        echo '<i class="bi bi-plus-circle"></i> Agendar consulta';
                        echo '</a>';
                        echo '</div>';
                    }
                    $conn->close();
                ?>
            </div>
        </div>
    </div>

    <script>
        const carrossel = document.querySelector(".carrossel-painel .slides");
        const prevBtn = document.querySelector(".carrossel-painel .arrow.left");
        const nextBtn = document.querySelector(".carrossel-painel .arrow.right");
        let index = 0;
        const totalSlides = 3;

        function showSlide(i) {
            index = i;
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;
            
            carrossel.style.transform = `translateX(-${index * 33.333}%)`;
        }

        prevBtn.addEventListener("click", () => showSlide(index - 1));
        nextBtn.addEventListener("click", () => showSlide(index + 1));

        setInterval(() => {
            showSlide(index + 1);
        }, 6000);

        function confirmarSaida(event) {
            event.preventDefault();
            if (confirm("Deseja realmente sair da conta?")) {
                window.location.href = "../sair.php";
            }
        }

        // Inicializar carrossel
        showSlide(0);
    </script>
</body>
</html>