<?php
require_once('../model/select_model.php');
$select = new select_model;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Saída-Estágio - Salaberga</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .header-title {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-nav {
            display: flex;
            gap: 12px;
        }

        .header-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .header-btn:hover {
            background-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .main-content {
            flex: 1;
            margin-top: 64px;
            padding: 32px 16px 80px;
        }

        .container {
            max-width: 768px;
            margin: 0 auto;
        }

        .title-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .icon-container {
            width: 48px;
            height: 48px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .tabs-nav {
            display: flex;
            border-bottom: 1px solid #e5e7eb;
        }

        .tab-btn {
            flex: 1;
            padding: 16px;
            background: none;
            border: none;
            font-weight: 500;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
        }

        .tab-btn.active {
            color: #008C45;
            border-bottom-color: #008C45;
        }

        .tab-content {
            padding: 24px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
            font-size: 0.875rem;
        }

        .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            background: white;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            cursor: pointer;
        }

        .radio-input {
            position: absolute;
            opacity: 0;
        }

        .radio-custom {
            width: 20px;
            height: 20px;
            border: 2px solid #d1d5db;
            border-radius: 50%;
            margin-right: 12px;
            position: relative;
            transition: all 0.3s ease;
        }

        .radio-input:checked + .radio-custom {
            border-color: #008C45;
        }

        .radio-custom::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: #008C45;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .radio-input:checked + .radio-custom::after {
            opacity: 1;
        }

        .radio-label {
            font-size: 0.875rem;
            color: #374151;
        }

        .btn-primary {
            width: 100%;
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .info-card {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
        }

        .info-icon {
            color: #d97706;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-weight: 500;
            color: #92400e;
            margin-bottom: 4px;
            font-size: 0.875rem;
        }

        .info-content p {
            color: #92400e;
            font-size: 0.75rem;
            line-height: 1.4;
        }

        .footer {
            background: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
            text-align: center;
            color: #6b7280;
            font-size: 0.75rem;
            position: relative;
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

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: 44px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: white;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #d1d5db;
            background-color: #f9fafb;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
            color: #374151;
            font-size: 0.875rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 12px;
            transition: transform 0.3s ease;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow {
            transform: rotate(180deg);
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
            background-color: white;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            margin-top: 4px;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .select2-container--default .select2-results__option:hover {
            background-color: #f3f4f6;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #008C45;
            color: white;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6;
            color: #374151;
            font-weight: 500;
        }

        .select2-search--dropdown {
            padding: 8px;
        }

        .select2-search--dropdown .select2-search__field {
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .select2-search--dropdown .select2-search__field:hover {
            border-color: #9ca3af;
        }

        .select2-search--dropdown .select2-search__field:focus {
            border-color: #008C45;
            outline: none;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .select2-container--default .select2-selection--single .select2-selection__clear {
            color: #6b7280;
            margin-right: 32px;
            font-size: 1.25rem;
            transition: color 0.2s ease;
        }

        .select2-container--default .select2-selection--single .select2-selection__clear:hover {
            color: #ef4444;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af;
        }

        /* Loading state for Select2 */
        .select2-container--default.select2-container--loading .select2-selection--single {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23008C45' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 40px center;
            background-size: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 640px) {
            .header-nav {
                gap: 8px;
            }
            
            .header-btn span {
                display: none;
            }
            
            .tabs-nav {
                flex-direction: column;
            }
            
            .tab-btn {
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
                border-right: none;
            }
            
            .tab-btn.active {
                border-bottom-color: #e5e7eb;
                border-left: 3px solid #008C45;
                background: #f9fafb;
            }
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="header">
        <div class="header-title">
            <i class="fas fa-graduation-cap"></i>
            Salaberga
        </div>
        <nav class="header-nav">
            <a href="inicio.php" class="header-btn">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
            <a href="saida_estagio_view.php" class="header-btn">
                <i class="fas fa-eye"></i>
                <span>Visualizar</span>
            </a>
            <a href="relatorio_diario_estagio.php" target="_blank" class="header-btn" style="background: linear-gradient(90deg, #FFA500, #FF8C00);">
                <i class="fas fa-calendar-day"></i>
                <span>Relatório Diário</span>
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h1 class="gradient-text">Relatório de Saída-Estágio</h1>
                <p class="subtitle">Gere relatórios detalhados de saídas para estágio</p>
            </div>

            <!-- Form Card -->
            <div class="form-card">
                <!-- Tabs Navigation -->
                <div class="tabs-nav">
                    <button class="tab-btn active" data-tab="aluno">
                        <i class="fas fa-user"></i> Por Aluno
                    </button>
                    <button class="tab-btn" data-tab="ano">
                        <i class="fas fa-calendar-alt"></i> Por Ano
                    </button>
                    <button class="tab-btn" data-tab="turma">
                        <i class="fas fa-users"></i> Por Turma
                    </button>
                </div>

                <!-- Tab Contents -->
                <!-- Por Aluno -->
                <div class="tab-content active" id="tab-aluno">
                    <form action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Aluno</label>
                            <select class="js-example-basic-single form-select" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunosE();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_aluno">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Ano -->
                <div class="tab-content" id="tab-ano">
                    <form action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <div style="background: #dbeafe; border: 1px solid #93c5fd; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                                <div style="display: flex; gap: 12px;">
                                    <i class="fas fa-info-circle" style="color: #2563eb; margin-top: 2px;"></i>
                                    <div>
                                        <h3 style="font-weight: 500; color: #1e40af; margin-bottom: 4px; font-size: 0.875rem;">Relatório do 3° Ano</h3>
                                        <p style="color: #1e40af; font-size: 0.75rem;">Este relatório mostrará dados de todas as turmas do 3° ano.</p>
                                    </div>
                                </div>
                            </div>

                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="3_ano_geral">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Turma -->
                <div class="tab-content" id="tab-turma">
                    <form action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione a Turma</label>
                            <select name="Turma" class="form-select" required>
                                <option value="" disabled selected>Selecione a turma</option>
                                <option value="9">3° Ano A</option>
                                <option value="10">3° Ano B</option>
                                <option value="11">3° Ano C</option>
                                <option value="12">3° Ano D</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required>
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Dia Atual</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 30 Dias</span>
                                </label>
                                <label class="radio-item">
                                    <input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input">
                                    <span class="radio-custom"></span>
                                    <span class="radio-label">Últimos 12 Meses</span>
                                </label>
                            </div>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turma">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
           
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>© 2025 Sistema Escolar Salaberga. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Initialize Select2 with enhanced options
        $(document).ready(function() {
            $('.js-example-basic-single').select2({
                placeholder: "Buscar aluno...",
                allowClear: true,
                width: '100%',
                language: {
                    noResults: function() {
                        return "Nenhum aluno encontrado";
                    },
                    searching: function() {
                        return "Buscando...";
                    }
                },
                templateResult: formatStudent,
                templateSelection: formatStudentSelection
            });

            // Custom formatting for student options
            function formatStudent(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2 py-1">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            function formatStudentSelection(student) {
                if (!student.id) return student.text;
                
                return $(`
                    <div class="flex items-center gap-2">
                        <i class="fas fa-user-graduate text-ceara-green"></i>
                        <span>${student.text}</span>
                    </div>
                `);
            }

            // Add hover effect to select container
            $('.select2-container').hover(
                function() {
                    $(this).find('.select2-selection--single').addClass('hover');
                },
                function() {
                    $(this).find('.select2-selection--single').removeClass('hover');
                }
            );
        });

        // Tab functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById('tab-' + targetTab).classList.add('active');
                });
            });

            // Form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const radios = form.querySelectorAll('input[type="radio"]');
                    const selects = form.querySelectorAll('select');
                    let isValid = true;

                    // Check radio buttons
                    const radioGroups = {};
                    radios.forEach(radio => {
                        const name = radio.name;
                        if (!radioGroups[name]) radioGroups[name] = [];
                        radioGroups[name].push(radio);
                    });

                    Object.values(radioGroups).forEach(group => {
                        const checked = group.some(radio => radio.checked);
                        if (!checked) isValid = false;
                    });

                    // Check selects
                    selects.forEach(select => {
                        if (!select.value) isValid = false;
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                    }
                });
            });
        });
    </script>
</body>
</html>