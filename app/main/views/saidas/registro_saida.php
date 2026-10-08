<?php
require_once('../../model/select_model.php');
$select = new select_model;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Registrar Saída - Salaberga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'ceara-green': '#008C45',
                        'ceara-light-green': '#3CB371',
                        'ceara-olive': '#8CA03E',
                        'ceara-orange': '#FFA500',
                        primary: '#4CAF50',
                        secondary: '#FFB74D',
                        danger: '#dc3545',
                        admin: '#0dcaf0',
                        grey: '#6c757d',
                        info: '#4169E1'
                    },
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif']
                    }
                },
            },
        };
    </script>
    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 1rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            padding-right: 3rem;
        }

        .form-select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23008C45' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }

        .required-field::after {
            content: ' *';
            color: #dc3545;
            font-weight: bold;
        }

        /* Custom Select Styles */
        .custom-select-container {
            position: relative;
            width: 100%;
        }

        .select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            min-height: 3.5rem;
        }

        .select-trigger:hover {
            border-color: #008C45;
        }

        .select-trigger.active {
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .select-placeholder {
            color: #9ca3af;
            font-weight: 500;
        }

        .select-arrow {
            color: #6b7280;
            transition: transform 0.3s ease;
        }

        .select-trigger.active .select-arrow {
            transform: rotate(180deg);
        }

        .select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e5e7eb;
            border-top: none;
            border-radius: 0 0 0.5rem 0.5rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 300px;
            overflow: hidden;
            display: none;
        }

        .select-dropdown.active {
            display: block;
        }

        .search-container {
            position: relative;
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 2rem 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            outline: none;
        }

        .search-input:focus {
            border-color: #008C45;
            box-shadow: 0 0 0 2px rgba(0, 140, 69, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 0.875rem;
        }

        .options-container {
            max-height: 200px;
            overflow-y: auto;
        }

        .select-option {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .select-option:hover {
            background-color: #f9fafb;
        }

        .select-option.selected {
            background-color: #008C45;
            color: white;
        }

        .select-option.hidden {
            display: none;
        }

        .hidden-select {
            display: none;
        }

        /* Scrollbar personalizada */
        .options-container::-webkit-scrollbar {
            width: 6px;
        }

        .options-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .options-container::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .options-container::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-gradient-to-r from-ceara-green to-ceara-light-green text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-graduation-cap text-2xl"></i>
                    <h1 class="text-xl font-bold">Salaberga</h1>
                </div>
                <nav>
                    <a href="../inicio.php" class="inline-flex items-center px-4 py-2 bg-white bg-opacity-20 rounded-lg text-white font-medium hover:bg-opacity-30 transition-colors">
                        <i class="fas fa-home mr-2"></i>
                        Menu Principal
                    </a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-ceara-green to-ceara-light-green text-white px-6 py-8 text-center">
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Registrar Saída de Aluno
                </h2>
                <p class="text-lg opacity-90">
                    Preencha os dados para registrar a saída do aluno
                </p>
            </div>

            <!-- Form Content -->
            <div class="p-6 lg:p-8">
                <form id="registro-s" action="../../control/control_index.php" method="POST" class="space-y-6">
                    
                    <!-- Seção: Dados do Aluno -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-graduate text-ceara-green mr-3"></i>
                            Dados do Aluno
                        </h3>
                        <div class="form-group">
                            <label for="id_aluno" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                Nome do Aluno
                            </label>
                            <div class="relative">
                                <div class="custom-select-container">
                                    <div class="select-trigger" id="select-trigger">
                                        <span class="select-placeholder">Selecione o Nome do Aluno</span>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <div class="select-dropdown" id="select-dropdown">
                                        <div class="search-container">
                                            <input type="text" id="search_aluno" placeholder="Digite para pesquisar..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="options-container" id="options-container">
                                            <?php
                                            $dados = $select->select_alunos();
                                            foreach ($dados as $dado) {
                                            ?>
                                            <div class="select-option" data-value="<?=$dado['id_aluno']?>" data-nome="<?=strtolower($dado['nome'])?>">
                                                <?=$dado['nome']?>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <select id="id_aluno" name="id_aluno" class="hidden-select" required>
                                        <option value="" disabled selected>Selecione o Nome do Aluno</option>
                                        <?php
                                        foreach ($dados as $dado) {
                                        ?>
                                        <option value="<?=$dado['id_aluno']?>"><?=$dado['nome']?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Responsável -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-shield text-info mr-3"></i>
                            Dados do Responsável
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="nome_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Nome do Responsável
                                </label>
                                <input type="text" id="nome_responsavel" name="nome_responsavel" 
                                       placeholder="Digite o nome completo" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="id_tipo_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Responsável
                                </label>
                                <select id="id_tipo_responsavel" name="id_tipo_responsavel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_responsavel();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_responsavel']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Conducente -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-car text-secondary mr-3"></i>
                            Dados do Conducente
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="nome_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Conducente
                                </label>
                                <input type="text" id="nome_conducente" name="nome_conducente" 
                                       placeholder="Digite o nome do conducente" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green">
                            </div>
                            <div class="form-group">
                                <label for="id_tipo_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Conducente
                                </label>
                                <select id="id_tipo_conducente" name="id_tipo_conducente" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select">
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_conducente();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_conducente']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados da Saída -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-danger mr-3"></i>
                            Dados da Saída
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo da Saída
                                </label>
                                <select id="id_motivo" name="id_motivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o motivo</option>
                                    <?php
                                    $dados = $select->select_motivo();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_motivo']?>"><?=$dado['motivo']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="id_usuario" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Administrador
                                </label>
                                <select id="id_usuario" name="id_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o administrador</option>
                                    <?php
                                    $dados = $select->select_funcionario();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_funcionario']?>"><?=$dado['nome']?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="data" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Data
                                </label>
                                <input type="date" name="data" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="hora" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Horário
                                </label>
                                <input type="time" name="hora" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                        </div>
                    </div>

                    <!-- Botão de Envio -->
                    <button type="submit" name="saida" class="w-full bg-gradient-to-r from-ceara-green to-ceara-light-green text-white font-semibold py-4 px-6 rounded-lg hover:from-ceara-light-green hover:to-ceara-green transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-ceara-green focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Registrar Saída
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-ceara-green text-white text-center py-6 mt-8">
        <div class="flex items-center justify-center space-x-2">
            <span>© 2025 Salaberga - Todos os direitos reservados</span>
        </div>
    </footer>

    <script>
        // Custom Select Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectTrigger = document.getElementById('select-trigger');
            const selectDropdown = document.getElementById('select-dropdown');
            const searchInput = document.getElementById('search_aluno');
            const optionsContainer = document.getElementById('options-container');
            const selectOptions = optionsContainer.querySelectorAll('.select-option');
            const hiddenSelect = document.getElementById('id_aluno');
            const placeholder = selectTrigger.querySelector('.select-placeholder');

            // Toggle dropdown
            selectTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                selectDropdown.classList.toggle('active');
                selectTrigger.classList.toggle('active');
                
                if (selectDropdown.classList.contains('active')) {
                    searchInput.focus();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!selectTrigger.contains(e.target) && !selectDropdown.contains(e.target)) {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Search functionality
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                selectOptions.forEach(option => {
                    const nome = option.getAttribute('data-nome');
                    if (nome.includes(searchTerm)) {
                        option.classList.remove('hidden');
                    } else {
                        option.classList.add('hidden');
                    }
                });
            });

            // Option selection
            selectOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const value = this.getAttribute('data-value');
                    const text = this.textContent;
                    
                    // Update hidden select
                    hiddenSelect.value = value;
                    
                    // Update trigger display
                    placeholder.textContent = text;
                    placeholder.style.color = '#374151';
                    
                    // Update visual state
                    selectOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    // Close dropdown
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                    
                    // Clear search
                    searchInput.value = '';
                    selectOptions.forEach(opt => opt.classList.remove('hidden'));
                });
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    selectDropdown.classList.remove('active');
                    selectTrigger.classList.remove('active');
                }
            });

            // Auto-focus on search when dropdown opens
            selectTrigger.addEventListener('click', function() {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            });
        });

        // Validação em tempo real
        const form = document.getElementById('registro-s');
        const inputs = form.querySelectorAll('input, select');

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });

            input.addEventListener('input', function() {
                if (this.value) {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                }
            });
        });

        // Prevenção de envio duplo
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Registrando...';
        });
    </script>
</body>
</html>