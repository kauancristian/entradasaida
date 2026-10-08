<?php
require_once('../model/select_model.php');
$select = new select_model;
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Saída - Salaberga</title>
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
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .report-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #008C45, #3CB371);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 140, 69, 0.2), 0 4px 6px -2px rgba(0, 140, 69, 0.1);
        }

        .report-card:hover::before {
            opacity: 1;
        }

        .select-field {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: white;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
        }

        .select-field:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .radio-group {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            justify-content: center;
            flex-wrap: wrap;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            min-width: 140px;
            justify-content: center;
        }

        .radio-option:hover {
            border-color: #008C45;
            background-color: #f0fdf4;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0, 140, 69, 0.15);
        }

        .radio-option input[type="radio"] {
            accent-color: #008C45;
            width: 16px;
            height: 16px;
        }

        .radio-option span {
            font-weight: 500;
            color: #374151;
        }

        .btn-submit {
            background: linear-gradient(45deg, #008C45, #3CB371);
            color: white;
            padding: 0.875rem 1.75rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 140, 69, 0.2);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .btn-submit i {
            font-size: 1.1rem;
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

        .card-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .card-icon.aluno {
            background: #E8F5E9;
            color: #008C45;
        }

        .card-icon.ano {
            background: #E3F2FD;
            color: #1976D2;
        }

        .card-icon.turma {
            background: #FFF3E0;
            color: #FF9800;
        }

        @media (max-width: 640px) {
            .radio-group {
                flex-direction: column;
                align-items: stretch;
            }

            .radio-option {
                width: 100%;
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <header class="header fixed top-0 left-0 right-0 h-16 flex items-center justify-between px-6 text-white shadow-md z-50">
        <div class="text-xl font-semibold">
            <i class="fas fa-graduation-cap mr-2"></i>
            Salaberga
        </div>
        <nav>
            <a href="index.php" class="flex items-center gap-2 px-4 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
        </nav>
    </header>

    <main class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatório de Saída</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-8">
                <!-- Relatório por Aluno -->
                <div class="report-card p-6">
                    <div class="card-icon aluno">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Aluno</h2>
                    <form id="saida" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="id_aluno" name="id_aluno" class="select-field" required>
                            <option value="" disabled selected>Selecione o Nome do Aluno</option>
                            <?php
                            $dados = $select->select_alunos();
                            foreach ($dados as $dado) {
                            ?>
                                <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                            <?php
                            }
                            ?>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_alunoSaida">
                        <input type="hidden" name="form_id" value="entrada">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Ano -->
                <div class="report-card p-6">
                    <div class="card-icon ano">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Ano</h2>
                    <form id="saidaA" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="ano" name="Ano" class="select-field" required>
                            <option value="" disabled selected>Selecione o ano</option>
                            <option value="1">1° Anos</option>
                            <option value="2">2° Anos</option>
                            <option value="3">3° Anos</option>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="ano_geralSaida">
                        <input type="hidden" name="form_id" value="entradaA">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Turma -->
                <div class="report-card p-6">
                    <div class="card-icon turma">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Turma</h2>
                    <form id="saidaT" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="Turma" name="Turma" class="select-field" required>
                            <option value="" disabled selected>Selecione a turma</option>
                            <option value="1">1° Ano A</option>
                            <option value="2">1° Ano B</option>
                            <option value="3">1° Ano C</option>
                            <option value="4">1° Ano D</option>
                            <option value="5">2° Ano A</option>
                            <option value="6">2° Ano B</option>
                            <option value="7">2° Ano C</option>
                            <option value="8">2° Ano D</option>
                            <option value="9">3° Ano A</option>
                            <option value="10">3° Ano B</option>
                            <option value="11">3° Ano C</option>
                            <option value="12">3° Ano D</option>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turmaSaida">
                        <input type="hidden" name="form_id" value="saidaT">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>
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