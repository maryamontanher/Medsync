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
        body {}

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

            border-radius: 12px;
            box-shadow: 0 0 0 5px #ffffff80;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
        }



        .card {
            background-color: rgba(75, 185, 165, 0.2);

            border-radius: 12px;
            box-shadow: 0 0 0 5px #ffffff80;
            transition: all 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
        }

        /* Card pai (envolve os dois menores) */
        .card-pai {
            padding: 30px;
            display: flex;
            flex-direction: row;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            align-items: flex-start;
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
        }

        .card.interno {
            background-color: white;
            max-width: 280px;
            width: 280px;
            min-width: 180px;
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

        .admin-area {
            background-color: #d9f2ee;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            max-width: 600px;
            margin: auto;
        }

        .cards-row {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .card.interno {
                max-width: 90vw;
                width: 90vw;
            }
        }

        @media (max-width: 600px) {
            .card.interno {
                max-width: 100%;
                width: 100%;
            }

            .cards-row {
                flex-direction: column;
                align-items: center;
            }
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
            <div class="card card-pai">
                <h1 class="titulo">Área do Administrador</h1>
                <div class="cards-row">
                    <div class="card interno">
                        <img src="../images/farmacia-adm.jpg" alt="Formulário de cadastro de farmácia">
                        <h3> Farmácia</h3>
                        <a href="cadastrofarmacia.php" class="btn">Cadastrar Farmácia </a>
                        <a href="tabela_farmacia.php" class="btn">Tabela de Usuarios </a>
                    </div>
                    <div class="card interno">
                        <img src="../images/clinica-adm.jpg" alt="Formulário de cadastro de clínicas">
                        <h3>Clínicas</h3>
                        <a href="cadastroclinica.php" class="btn">Cadastrar Clínicas</a>
                        <a href="tabela_clinica.php" class="btn">Tabela de Usuarios</a>
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