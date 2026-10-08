<?php
include_once '../../model/sessions.php';
$session = new sessions();
$session->autenticar_session();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema Escolar - Relatório por Dia</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="icon" href="https://salaberga.com/salaberga/portalsalaberga/app/main/assets/img/Design%20sem%20nome.svg" type="image/x-icon">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'ceara-green': '#008C45',
            'ceara-light-green': '#3CB371',
            'ceara-olive': '#8CA03E',
            'ceara-orange': '#FFA500',
          }
        }
      }
    }
  </script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
    
    * {
      font-family: 'Inter', sans-serif;
    }

    body {
      background: #f8fafc;
      min-height: 100vh;
    }

    .form-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .form-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .gradient-text {
      background: linear-gradient(45deg, #008C45, #3CB371);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .header {
      background: linear-gradient(90deg, #008C45, #3CB371);
    }

    .footer {
      background: white;
      border-top: 1px solid #e5e7eb;
    }

    .footer::before {
      content: '';
      position: absolute;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100px;
      height: 2px;
      background: linear-gradient(70deg, #008C45, #FFA500);
    }

    input[type="date"] {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 8px 12px;
      width: 100%;
      max-width: 200px;
      transition: all 0.3s ease;
    }

    input[type="date"]:focus {
      outline: none;
      border-color: #3CB371;
      box-shadow: 0 0 0 3px rgba(60, 179, 113, 0.2);
    }

    button {
      background: linear-gradient(45deg, #008C45, #3CB371);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>

<body class="min-h-screen flex flex-col">
  <header class="header fixed top-0 left-0 right-0 h-16 flex items-center justify-between px-6 text-white shadow-md z-50">
    <div class="text-xl font-semibold">Salaberga</div>
    <nav>
      <a href="inicio.php?sair" 
         class="flex items-center gap-2 px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
        <i class="fas fa-sign-out-alt"></i>
        <span>Sair</span>
      </a>
    </nav>
  </header>

  <main class="flex-1 container mx-auto px-4 py-8 mt-16">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Relatório por Dia</span>
        </h1>
        <p class="text-gray-600">Selecione a data para gerar o relatório de entradas e saídas</p>
      </div>

      <div class="form-card p-6 flex flex-col items-center gap-4">
        <form action="alunos_geral_dia.php" method="post" class="flex flex-col sm:flex-row gap-4 items-center">
          <div class="flex items-center gap-2">
            <i class="fas fa-calendar-alt text-xl text-gray-600"></i>
            <input type="date" name="data" id="data" required>
          </div>
          <button type="submit">Gerar Relatório</button>
        </form>
      </div>
    </div>
  </main>

  <footer class="footer relative py-4 text-center text-gray-600 text-sm">
    <div class="container mx-auto">
      <p>© 2025 Sistema Escolar Salaberga. Todos os direitos reservados.</p>
    </div>
  </footer>
</body>
</html>