<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Administrador</title>
    <link rel="shortcut icon" href="../images/logo/minilogo_verde.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');

        html {
            scroll-behavior: smooth;
        }

        * {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;

        }

        html,
        body {
            background-image: url('../images/adm-bg.jpg');
            background-repeat: no-repeat;
            background-size: cover;
            margin: 0 !important;
            padding: 0 !important;
            /* background: rgb(255, 255, 255); */
            /* Remova ou comente esta linha */
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

        .logo img {
            width: 120px;
            background: no-repeat left center / contain;
        }

        .nav-links {
            list-style: none;
            display: flex;
        }

        .nav-links li {
            margin: 0 15px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 18px;
            transition: color 0.3s;
        }

        .nav-links a:hover {
            color: rgb(50, 97, 81);
        }

        .beneficios {

            width: 100%;
            padding: 80px 20px;
            text-align: center;
            margin-top: 70px;
        }

        .beneficios h2 {
            font-size: 2.5rem;
            color: #2b2b2b;
            margin-bottom: 50px;
        }

        .beneficios-cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            padding: 30px 20px;
            max-width: 300px;
            transition: transform 0.3s;
            border-top: 5px solid #4bb9a5;
            text-decoration: none;
            color: inherit;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card h3 {
            font-size: 1.5rem;
            color: #157c5d;
            margin-bottom: 15px;
        }

        .card p {
            font-size: 1rem;
            color: #555;
            line-height: 1.5;
        }

        .card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }

        @media (max-width: 600px) {
            .beneficios {
                padding: 40px 10px;
            }

            .card {
                max-width: 100%;
            }
        }

        .card {
            background-color: rgba(75, 185, 165, 0.2);
            /* verde translúcido */
            border-radius: 12px;
            box-shadow: 0 0 0 5px #ffffff80;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
        }



        .card {
            background-color: rgba(75, 185, 165, 0.2);
            /* verde translúcido */
            border-radius: 12px;
            box-shadow: 0 0 0 5px #ffffff80;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
        }

        
        .card-pai {
            padding: 30px;
            display: flex;
            flex-direction: row;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
        }

        .card.interno {
            background-color: white;
            max-width: 300px;
            height: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-top: 5px solid #4bb9a5;
            transition: transform 0.3s;
        }

        .card.interno:hover {
            transform: translateY(-5px);
        }

        .card.interno img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
            margin-bottom: 20px;
        }

        .card.interno h3 {
            font-size: 1.5rem;
            color: #157c5d;
            margin-bottom: 15px;
        }

        .btn {
            margin-top: 15px;
            background-color: #1b7f6d;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-decoration: none;
        }

        .acesso-btn:hover {
            background-color: #166556;
        }

        .titulo {
            text-align: center;
            color: #157c5d;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="logo">
            <img src="../images/logo/logo_branca.png" alt="Logo da plataforma">
        </div>
        <ul class="nav-links">
        <li><a href="#" onclick="confirmarSaida(event)"><i class="fa-solid fa-right-from-bracket"></i></a></li>
        </ul>
    </nav>

    <section class="beneficios">
        <div class="beneficios-cards">
            <div class="card card-pai" style="flex-direction: column; align-items: center;">
                <h1 class="titulo" style="width: 100%;">Área do Administrador</h1>
                <div style="display: flex; gap: 20px; justify-content: center; flex-direction: row; width: 100%;">
                    <div class="card interno">
                        <img src="../images/medico-adm.jpg" alt="Formulário de cadastro de médicos">
                        <h3>Cadastro de Médicos</h3>
                        <a href="cadastromed.php" class="btn">Cadastrar Médicos</a>
                        <a href="tabela_med.php" class="btn">Tabela de Usuários</a>
                    </div>
                    <div class="card interno">
                        <img src="../images/paciente-adm.jpg" alt="Formulário de cadastro de pacientes">
                        <h3>Cadastro de Pacientes</h3>
                        <a href="cadastropaciente.php" class="btn">Cadastrar Pacientes</a>
                        <a href="tabela_paciente.php" class="btn">Tabela de Usuários</a>
                    </div>
                    <div class="card interno">
                        <img src="../images/menores-adm.jpg" alt="Formulário de cadastro de pacientes menores de idade">
                        <h3>Cadastro de Pacientes Menores de Idade</h3>
                        <a href="cadastromenor.php" class="btn">Cadastrar Pacientes</a>
                        <a href="tabela_menor.php" class="btn">Tabela de Usuários</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function confirmarSaida(event) {
            event.preventDefault();
            if (confirm("Deseja realmente sair da conta?")) {
                window.location.href = "../sair.php";
            }
        }
    </script>
</body>
</html>