<?php
include_once '../model/sessions.php';
$session = new sessions();
$session->autenticar_session();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema Escolar - Menu</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="icon" href="https://salaberga.com/salaberga/portalsalaberga/app/main/assets/img/Design%20sem%20nome.svg" type="image/xicon">
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

    .menu-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .menu-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .menu-icon {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .menu-card:hover .menu-icon {
      transform: scale(1.1);
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
      background: linear-gradient(70deg, #008C45,rgb(225, 130, 6));
    }
  </style>
</head>

<body class="min-h-screen flex flex-col">
  <header class="header fixed top-0 left-0 right-0 h-16 flex items-center justify-between px-6 text-white shadow-md z-50">
    <div class="text-xl font-semibold">Salaberga</div>
    <nav>
      <a href="../model/sessions.php?sair" 
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
          <span class="gradient-text">Sistema de Entradas e Saídas</span>
        </h1>
        <p class="text-gray-600">Gerencie as entradas e saídas dos alunos de forma eficiente</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <a href="./entradas/registro_entrada.php" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-blue-50 text-blue-600">
            <i class="fas fa-sign-in-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Registrar Entrada</h3>
            <p class="text-sm text-gray-500">Registre a entrada dos alunos</p>
          </div>
        </a>

        <a href="./saidas/registro_saida.php" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-red-50 text-red-600">
            <i class="fas fa-sign-out-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-red-600 transition-colors">Registrar Saída</h3>
            <p class="text-sm text-gray-500">Registre a saída dos alunos</p>
          </div>
        </a>

        <a href="./estagio/saida_Estagio.php" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-purple-50 text-purple-600">
            <i class="fas fa-briefcase text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">Registrar Saída-Estágio</h3>
            <p class="text-sm text-gray-500">Registre saídas para estágio</p>
          </div>
        </a>

        <a href="relatorio.php" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-yellow-50 text-yellow-600">
            <i class="fas fa-chart-bar text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-yellow-600 transition-colors">Relatórios</h3>
            <p class="text-sm text-gray-500">Visualize relatórios do sistema</p>
          </div>
        </a>

        <a href="relatorios/ultimo_registro.php" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-cyan-50 text-cyan-600">
            <i class="fas fa-history text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition-colors">Últimas Saídas</h3>
            <p class="text-sm text-gray-500">Acompanhe as últimas saídas registradas</p>
          </div>
        </a>
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