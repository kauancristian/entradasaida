<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Salaberga</title>
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

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .report-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .report-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .report-card:hover .report-icon {
            transform: scale(1.1);
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
            background: linear-gradient(70deg, #008C45, rgb(225, 130, 6));
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <header class="header fixed top-0 left-0 right-0 h-16 flex items-center justify-between px-6 text-white shadow-md z-50">
        <div class="text-xl font-semibold">Salaberga</div>
        <nav>
            <a href="inicio.php" class="flex items-center gap-2 px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
        </nav>
    </header>

    <main class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatórios do Sistema</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-4">
                <a href="#" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-blue-50 text-blue-600">
    <i class="fas fa-right-to-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Entradas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de entradas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="#" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-red-50 text-red-600">
    <i class="fas fa-right-from-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="relatorios/relatorio_diario_estagio.php" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-violet-50 text-violet-600">
    <i class="fas fa-user-tie text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas para estágio</p>
                        </div>
                    </div>
                </a>

                <a href="relatorios/escolher_dia.php" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-amber-50 text-amber-600">
    <i class="fas fa-calendar-day text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio no dia especifico</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
                        </div>
                    </div>
                </a>
                
                <a href="relatorios/pre_estagio.php" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-emerald-50 text-emerald-600">
    <i class="fas fa-graduation-cap text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Preparação para estágio - Frequência</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
                        </div>
                    </div>
                </a>
                
                <a href="./QRCode/decisao.php" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-cyan-50 text-cyan-600">
    <i class="fas fa-qrcode text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório Geral de QRCodes</h3>
                            <p class="text-sm text-gray-500">Gere QRCodes para todos os alunos</p>
                        </div>
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