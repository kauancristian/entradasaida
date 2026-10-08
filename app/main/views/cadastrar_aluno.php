<?php 
require_once('../model/select_model.php');
$select = new select_model();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Aluno - Salaberga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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

        .floating-label {
            transition: all 0.3s ease;
            pointer-events: none;
            background: white;
            padding: 0 4px;
            z-index: 10;
        }

        .input-group:focus-within .floating-label,
        .input-group.has-value .floating-label {
            transform: translateY(-1.8rem) scale(0.85);
            color: #008C45;
            font-weight: 500;
        }

        .input-field {
            position: relative;
            z-index: 5;
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .slide-in {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pulse-success {
            animation: pulseSuccess 0.6s ease-in-out;
        }

        @keyframes pulseSuccess {
            0% { transform: scale(1); }
            50% { transform: scale(1.02); }
            100% { transform: scale(1); }
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

        .btn-primary {
            background: linear-gradient(90deg, #008C45, #3CB371);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #006d35, #2e8b57);
            transform: translateY(-1px);
            box-shadow: 0 6px 12px -2px rgba(0, 140, 69, 0.2);
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
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            background: rgba(0, 140, 69, 0.1);
            color: #008C45;
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
        <nav>
            <a href="inicio.php" class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors text-sm">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-md mx-auto">
            <!-- Title Section -->
            <div class="text-center mb-6">
                <div class="icon-container mx-auto mb-3">
                    <i class="fas fa-user-plus text-lg"></i>
                </div>
                <h1 class="text-2xl font-bold mb-2">
                    <span class="gradient-text">Cadastro de Aluno</span>
                </h1>
                <p class="text-gray-600 text-sm">Adicione um novo aluno ao sistema</p>
            </div>

            <!-- Success Message -->
            <div id="successMessage" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg slide-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2 text-sm"></i>
                    <span class="text-green-700 font-medium text-sm">Aluno cadastrado com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-6 border-t-4 border-ceara-green">
                <form id="cadastroForm" action="../control/control_index.php" method="post" class="space-y-5">
                    <input type="hidden" name="cadastrar" value="1">

                    <!-- Nome Field -->
                    <div class="input-group relative pt-2">
                        <label class="floating-label absolute left-3 top-5 text-gray-500 text-sm">
                            Nome Completo
                        </label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            class="input-field w-full pt-4 pb-2 px-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                            required
                        >
                        <div class="absolute right-3 top-5 text-gray-400">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div id="nome-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, insira um nome válido (apenas letras)</span>
                        </div>
                        <div id="nome-success" class="success-message hidden mt-1 text-green-600 text-xs flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span>Nome válido</span>
                        </div>
                    </div>

                    <!-- Matrícula Field -->
                    <div class="input-group relative pt-2">
                        <label class="floating-label absolute left-3 top-5 text-gray-500 text-sm">
                            Matrícula (7 dígitos)
                        </label>
                        <input 
                            type="text" 
                            id="matricula" 
                            name="matricula" 
                            maxlength="7"
                            class="input-field w-full pt-4 pb-2 px-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-sm"
                            required
                        >
                        <div class="absolute right-3 top-5 text-gray-400">
                            <i class="fas fa-id-card text-sm"></i>
                        </div>
                        <div id="matricula-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>A matrícula deve conter exatamente 7 dígitos</span>
                        </div>
                        <div id="matricula-success" class="success-message hidden mt-1 text-green-600 text-xs flex items-center">
                            <i class="fas fa-check-circle mr-1"></i>
                            <span>Matrícula válida</span>
                        </div>
                    </div>

                    <!-- Turma Field -->
                    <div class="input-group relative">
                        <label for="id_turma" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-users mr-1 text-ceara-green text-sm"></i>
                            Turma
                        </label>
                        <select 
                            id="id_turma" 
                            name="id_turma" 
                            class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione uma turma</option>
                            <?php 
                            $dados = $select->select_turmas();

                            foreach($dados as $dado){
                            ?>
                            <option value="<?=$dado['id_turma']?>"><?=$dado['ano']?> <?=$dado['turma']?></option>
                            <?php }?>
                        </select>
                        <div id="turma-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Curso Field -->
                    <div class="input-group relative">
                        <label for="id_curso" class="block text-sm font-medium text-gray-700 mb-1">
                            <i class="fas fa-book mr-1 text-ceara-green text-sm"></i>
                            Curso
                        </label>
                        <select 
                            id="id_curso" 
                            name="id_curso" 
                            class="w-full p-3 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione um curso</option>
                            <?php 
                            $dados = $select->select_curso();
                            foreach($dados as $dado){
                            ?>
                            <option value="<?=$dado['id_curso']?>"><?=$dado['curso']?></option>
                            <?php }?>
                        </select>
                        <div id="curso-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione um curso</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full btn-primary text-white font-medium py-3 px-4 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm mt-6"
                    >
                        <span id="submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-user-plus text-sm"></i>
                            Cadastrar Aluno
                        </span>
                        <span id="submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Cadastrando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-4">
                    <a href="inicio.php" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer relative py-3 text-center text-gray-600 text-xs">
        <div class="container mx-auto">
            <p>© 2024 Sistema Escolar Salaberga. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        // Form validation and UX improvements
        class FormValidator {
            constructor() {
                this.form = document.getElementById('cadastroForm');
                this.fields = {
                    nome: document.getElementById('nome'),
                    matricula: document.getElementById('matricula'),
                    turma: document.getElementById('id_turma'),
                    curso: document.getElementById('id_curso')
                };
                this.submitBtn = document.getElementById('submitBtn');
                this.init();
            }

            init() {
                // Add event listeners for real-time validation
                Object.keys(this.fields).forEach(fieldName => {
                    const field = this.fields[fieldName];
                    field.addEventListener('input', () => this.validateField(fieldName));
                    field.addEventListener('blur', () => this.validateField(fieldName));
                    field.addEventListener('focus', () => this.clearFieldError(fieldName));
                });

                // Handle floating labels
                this.handleFloatingLabels();

                // Form submission
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Format matricula input
                this.fields.matricula.addEventListener('input', this.formatMatricula);
            }

            handleFloatingLabels() {
                const inputs = document.querySelectorAll('.input-group input');
                inputs.forEach(input => {
                    const group = input.closest('.input-group');
                    
                    input.addEventListener('input', () => {
                        if (input.value.trim() !== '') {
                            group.classList.add('has-value');
                        } else {
                            group.classList.remove('has-value');
                        }
                    });

                    input.addEventListener('focus', () => {
                        group.classList.add('has-value');
                    });

                    input.addEventListener('blur', () => {
                        if (input.value.trim() === '') {
                            group.classList.remove('has-value');
                        }
                    });

                    // Check initial value
                    if (input.value.trim() !== '') {
                        group.classList.add('has-value');
                    }
                });
            }

            formatMatricula(e) {
                // Only allow numbers
                e.target.value = e.target.value.replace(/\D/g, '');
            }

            validateField(fieldName) {
                const field = this.fields[fieldName];
                const value = field.value.trim();
                let isValid = true;
                let errorMessage = '';

                switch (fieldName) {
                    case 'nome':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Nome é obrigatório';
                        } else if (!/^[A-Za-zÀ-ÖØ-öø-ÿ\s]+$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Nome deve conter apenas letras';
                        } else if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Nome deve ter pelo menos 2 caracteres';
                        }
                        break;

                    case 'matricula':
                        if (!value) {
                            isValid = false;
                            errorMessage = 'Matrícula é obrigatória';
                        } else if (!/^\d{7}$/.test(value)) {
                            isValid = false;
                            errorMessage = 'Matrícula deve conter exatamente 7 dígitos';
                        }
                        break;

                    case 'turma':
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Selecione uma turma';
                        }
                        break;

                    case 'curso':
                        if (!value || value === '') {
                            isValid = false;
                            errorMessage = 'Selecione um curso';
                        }
                        break;
                }

                this.showFieldValidation(fieldName, isValid, errorMessage);
                return isValid;
            }

            showFieldValidation(fieldName, isValid, errorMessage) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                const successElement = document.getElementById(`${fieldName}-success`);

                // Remove previous states
                field.classList.remove('border-red-500', 'border-green-500', 'shake');
                
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
                if (successElement) {
                    successElement.classList.add('hidden');
                }

                if (!isValid && field.value.trim() !== '') {
                    // Show error
                    field.classList.add('border-red-500', 'shake');
                    if (errorElement) {
                        errorElement.querySelector('span').textContent = errorMessage;
                        errorElement.classList.remove('hidden');
                        errorElement.classList.add('slide-in');
                    }
                } else if (isValid && field.value.trim() !== '') {
                    // Show success
                    field.classList.add('border-green-500');
                    if (successElement) {
                        successElement.classList.remove('hidden');
                        successElement.classList.add('slide-in');
                    }
                }
            }

            clearFieldError(fieldName) {
                const field = this.fields[fieldName];
                const errorElement = document.getElementById(`${fieldName}-error`);
                
                field.classList.remove('border-red-500', 'shake');
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
            }

            validateForm() {
                let isFormValid = true;
                Object.keys(this.fields).forEach(fieldName => {
                    if (!this.validateField(fieldName)) {
                        isFormValid = false;
                    }
                });
                return isFormValid;
            }

            handleSubmit(e) {
                e.preventDefault();
                
                if (!this.validateForm()) {
                    // Shake the form container
                    const container = document.querySelector('.form-card');
                    container.classList.add('shake');
                    setTimeout(() => container.classList.remove('shake'), 500);
                    return;
                }

                // Show loading state
                this.showLoadingState();

                // Simulate form submission (replace with actual submission)
                setTimeout(() => {
                    this.showSuccessState();
                }, 2000);

                // Uncomment the line below for actual form submission
                // this.form.submit();
            }

            showLoadingState() {
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                const successMessage = document.getElementById('successMessage');
                const container = document.querySelector('.form-card');
                
                // Reset button state
                const submitText = document.getElementById('submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show success message
                successMessage.classList.remove('hidden');
                container.classList.add('pulse-success');
                
                // Reset form
                this.form.reset();
                document.querySelectorAll('.input-group').forEach(group => {
                    group.classList.remove('has-value');
                });
                document.querySelectorAll('.border-green-500').forEach(field => {
                    field.classList.remove('border-green-500');
                });
                document.querySelectorAll('.success-message').forEach(msg => {
                    msg.classList.add('hidden');
                });

                // Hide success message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                    container.classList.remove('pulse-success');
                }, 5000);
            }
        }
    </script>
</body>
</html>