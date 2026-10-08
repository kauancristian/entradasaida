<?php
require_once('../../model/select_model.php');
$select = new select_model;

include_once '../../model/sessions.php';
$session = new sessions();
$session->autenticar_session();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Saída Estágio - Salaberga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    }
                }
            }
        }
    </script>

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8fafc;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(90deg, #008C45, #3CB371);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .input-focus-ring:focus {
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .btn-primary {
            background: linear-gradient(90deg, #008C45, #3CB371);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
        }

        .loading-spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #008C45;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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

        .icon-container {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
        }

        .slide-in {
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .success-message {
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Custom dropdown styles */
        select:focus + div i {
            transform: rotate(180deg);
        }

        select:hover + div i {
            color: #008C45;
        }

        /* Remove default select styling */
        select {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Custom option styling */
        select option {
            padding: 12px;
            font-size: 14px;
        }

        select option:checked {
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
        }

        /* Select2 Custom Styling */
        .select2-container--default .select2-selection--single {
            height: auto !important;
            padding: 16px !important;
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            background-color: white !important;
            font-size: 16px !important;
        }

        .select2-container--default .select2-selection--single:focus,
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #008C45 !important;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1) !important;
        }

        .select2-container--default .select2-selection--single:hover {
            border-color: #d1d5db !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #374151 !important;
            line-height: normal !important;
            padding: 0 !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__placeholder {
            color: #9ca3af !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100% !important;
            right: 12px !important;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow b {
            border-color: #9ca3af transparent transparent transparent !important;
            border-width: 5px 4px 0 4px !important;
        }

        .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
            border-color: transparent transparent #9ca3af transparent !important;
            border-width: 0 4px 5px 4px !important;
        }

        .select2-dropdown {
            border: 2px solid #e5e7eb !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
            background-color: white !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field {
            border: 2px solid #e5e7eb !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 14px !important;
        }

        .select2-container--default .select2-search--dropdown .select2-search__field:focus {
            border-color: #008C45 !important;
            outline: none !important;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1) !important;
        }

        .select2-container--default .select2-results__option {
            padding: 12px 16px !important;
            font-size: 14px !important;
            color: #374151 !important;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background-color: #008C45 !important;
            color: white !important;
        }

        .select2-container--default .select2-results__option[aria-selected=true] {
            background-color: #f3f4f6 !important;
            color: #374151 !important;
        }

        /* Modal Animations */
        .modal-enter {
            animation: modalEnter 0.3s ease-out forwards;
        }

        .modal-exit {
            animation: modalExit 0.3s ease-in forwards;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        @keyframes modalExit {
            from {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
            to {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
        }

        .modal-backdrop {
            animation: backdropEnter 0.3s ease-out forwards;
        }

        @keyframes backdropEnter {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="header fixed top-0 left-0 right-0 h-16 flex items-center justify-between px-6 text-white shadow-md z-50">
        <div class="text-xl font-semibold">
            <i class="fas fa-graduation-cap mr-2"></i>
            Salaberga
        </div>
        <nav class="flex items-center gap-3">
            <a href="../inicio.php" class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors text-sm">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
            <a href="../relatorios/ultimo_registro.php" class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors text-sm">
                <i class="fas fa-eye"></i>
                <span class="hidden sm:inline">Tempo Real</span>
                <span class="sm:hidden">Ver</span>
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-lg mx-auto slide-in">
            <!-- Title Section -->
            <div class="text-center mb-8">
                <div class="icon-container mx-auto mb-4">
                    <i class="fas fa-briefcase text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Registro de Saída Estágio</span>
                </h1>
                <p class="text-gray-600 text-sm">Registre a saída dos alunos para estágio</p>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mb-6 p-4 bg-green-50 border border-green-200 rounded-lg success-message">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-700 font-medium">Saída registrada com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-8 border-t-4 border-ceara-green">
                <form id="saida-estagio" action="../../control/control_index.php" method="POST" class="space-y-6">
                    
                    <!-- Aluno Selection -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-ceara-green text-sm"></i>
                            </div>
                            <label for="id_aluno" class="text-base font-semibold text-gray-800">
                                Aluno
                            </label>
                        </div>
                        
                        <div class="relative">
                            <select 
                                class="js-example-basic-single w-full p-4 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-base bg-white hover:border-gray-300"
                                name="id_aluno" 
                                required
                                id="id_aluno"
                            >
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunosE();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?=$dado['id_aluno']?>"><?=$dado['nome']?></option>
                                <?php
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div id="aluno-error" class="hidden text-red-500 text-sm flex items-center mt-2">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span>Por favor, selecione um aluno</span>
                        </div>
                    </div>

                    <!-- Data e Hora Section -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-700 flex items-center">
                            <i class="fas fa-calendar-alt mr-2 text-ceara-green"></i>
                            Data e Hora da Saída
                        </h3>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Data -->
                            <div class="space-y-2">
                                <label for="data" class="block text-xs font-medium text-gray-600">
                                    Data
                                </label>
                                <input 
                                    type="date" 
                                    id="data"
                                    name="data" 
                                    class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                                    required
                                >
                                <div id="data-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <span>Data é obrigatória</span>
                                </div>
                            </div>

                            <!-- Hora -->
                            <div class="space-y-2">
                                <label for="hora" class="block text-xs font-medium text-gray-600">
                                    Hora
                                </label>
                                <input 
                                    type="time" 
                                    id="hora"
                                    name="hora" 
                                    class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                                    required
                                >
                                <div id="hora-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    <span>Hora é obrigatória</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="submitBtn"
                        name="Registrar"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Registrar Saída
                        </span>
        
                    </button>
                </form>

                <!-- Back Links -->
                <div class="flex flex-col sm:flex-row gap-3 mt-6 text-center">
                    <a href="../../views/inicio.php" class="flex-1 inline-flex items-center justify-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                    <a href="../relatorios/ultimo_registro.php" class="flex-1 inline-flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-eye text-sm"></i>
                        Ver em Tempo Real
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer relative py-4 text-center text-gray-600 text-xs">
        <div class="container mx-auto">
            <p>© 2025 Sistema Escolar Salaberga. Todos os direitos reservados.</p>
        </div>
    </footer>

    <!-- Modal de Sucesso -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="successModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Sucesso!</h3>
                <p class="text-gray-600 mb-8">A saída do aluno foi registrada com sucesso.</p>
                <button onclick="closeSuccessModal()" class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors">
                    Continuar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Erro -->
    <div id="errorModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="errorModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Erro!</h3>
                <p id="errorMessage" class="text-gray-600 mb-8">Ocorreu um erro ao registrar a saída.</p>
                <button onclick="closeErrorModal()" class="w-full bg-red-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-red-700 transition-colors">
                    Tentar Novamente
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Aviso -->
    <div id="warningModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md w-full mx-4 transform transition-all duration-300 scale-95 opacity-0" id="warningModalContent">
            <div class="text-center">
                <div class="w-20 h-20 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-bold text-gray-800 mb-4">Aviso!</h3>
                <p class="text-gray-600 mb-8">Este aluno já possui um registro de saída para hoje.</p>
                <button onclick="closeWarningModal()" class="w-full bg-yellow-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
                    Entendi
                </button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.js-example-basic-single').select2({
                placeholder: 'Selecione o aluno',
                allowClear: true,
                dropdownParent: $('body'),
                width: '100%',
                language: 'pt-BR',
                minimumResultsForSearch: 0
            });
        });

        class SaidaEstagioForm {
            constructor() {
                this.form = document.getElementById('saida-estagio');
                this.fields = {
                    aluno: document.getElementById('id_aluno'),
                    data: document.getElementById('data'),
                    hora: document.getElementById('hora')
                };
                this.submitBtn = document.getElementById('submitBtn');
                this.init();
            }

            init() {
                // Set current date and time as default
                this.setCurrentDateTime();

                // Add event listeners for validation
                Object.keys(this.fields).forEach(fieldName => {
                    const field = this.fields[fieldName];
                    field.addEventListener('change', () => this.validateField(fieldName));
                    field.addEventListener('blur', () => this.validateField(fieldName));
                });

                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Add visual feedback on field changes
                Object.values(this.fields).forEach(field => {
                    field.addEventListener('change', () => {
                        if (field.value) {
                            field.classList.add('border-green-500');
                            field.classList.remove('border-gray-200');
                        } else {
                            field.classList.remove('border-green-500');
                            field.classList.add('border-gray-200');
                        }
                    });
                });
            }

            setCurrentDateTime() {
                const now = new Date();
                const today = now.toISOString().split('T')[0];
                const currentTime = now.toTimeString().slice(0, 5);
                
                this.fields.data.value = today;
                this.fields.hora.value = currentTime;
                
                // Trigger change events to update visual feedback
                this.fields.data.dispatchEvent(new Event('change'));
                this.fields.hora.dispatchEvent(new Event('change'));
            }

            validateField(fieldName) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                
                if (!field.value) {
                    if (errorElement) {
                        errorElement.classList.remove('hidden');
                    }
                    return false;
                } else {
                    if (errorElement) {
                        errorElement.classList.add('hidden');
                    }
                    return true;
                }
            }

            validateForm() {
                let isValid = true;
                Object.keys(this.fields).forEach(fieldName => {
                    if (!this.validateField(fieldName)) {
                        isValid = false;
                    }
                });
                return isValid;
            }

            handleSubmit(e) {
                if (!this.validateForm()) {
                    e.preventDefault();
                    
                    // Show error modal for validation failures
                    showErrorModal('Por favor, preencha todos os campos obrigatórios.');
                    
                    // Shake effect for invalid form
                    const container = document.querySelector('.form-card');
                    container.classList.add('shake');
                    
                    setTimeout(() => {
                        container.classList.remove('shake');
                    }, 500);
                    
                    return;
                }

                // Show loading state
                this.showLoadingState();
            }

            showLoadingState() {
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                // Reset button state
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show success modal
                showSuccessModal();
                
                // Reset form
                this.form.reset();
                this.setCurrentDateTime();
                
                // Remove visual feedback
                Object.values(this.fields).forEach(field => {
                    field.classList.remove('border-green-500');
                    field.classList.add('border-gray-200');
                });
            }

            showErrorState(errorMessage = 'Ocorreu um erro ao registrar a saída.') {
                // Reset button state
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show error modal
                showErrorModal(errorMessage);
            }
        }

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new SaidaEstagioForm();
        });

        // Handle URL parameters for success messages
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('status') === 'success') {
            // Show modal immediately if already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => showSuccessModal(), 100);
                });
            } else {
                setTimeout(() => showSuccessModal(), 100);
            }
        }
        
        if (urlParams.get('status') === 'ja_registrado') {
            // Show warning modal immediately if already loaded
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', () => {
                    setTimeout(() => showWarningModal(), 100);
                });
            } else {
                setTimeout(() => showWarningModal(), 100);
            }
        }

        // Modal Functions
        function showSuccessModal() {
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModalContent');
            
            if (!modal || !content) {
                console.error('Modal elements not found');
                return;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            const content = document.getElementById('successModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        function showErrorModal(message = 'Ocorreu um erro ao registrar a saída.') {
            const modal = document.getElementById('errorModal');
            const content = document.getElementById('errorModalContent');
            const errorMessage = document.getElementById('errorMessage');
            
            if (!modal || !content || !errorMessage) {
                console.error('Error modal elements not found');
                return;
            }
            
            errorMessage.textContent = message;
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeErrorModal() {
            const modal = document.getElementById('errorModal');
            const content = document.getElementById('errorModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        function showWarningModal() {
            const modal = document.getElementById('warningModal');
            const content = document.getElementById('warningModalContent');
            
            if (!modal || !content) {
                console.error('Warning modal elements not found');
                return;
            }
            
            modal.classList.remove('hidden');
            modal.classList.add('modal-backdrop');
            
            setTimeout(() => {
                content.classList.add('modal-enter');
            }, 10);
        }

        function closeWarningModal() {
            const modal = document.getElementById('warningModal');
            const content = document.getElementById('warningModalContent');
            
            content.classList.remove('modal-enter');
            content.classList.add('modal-exit');
            
            setTimeout(() => {
                modal.classList.add('hidden');
                content.classList.remove('modal-exit');
            }, 300);
        }

        // Close modals when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target.id === 'successModal' || e.target.id === 'errorModal' || e.target.id === 'warningModal') {
                closeSuccessModal();
                closeErrorModal();
                closeWarningModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSuccessModal();
                closeErrorModal();
                closeWarningModal();
            }
        });
    </script>
</body>
</html>