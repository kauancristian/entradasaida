<?php 
require_once(__DIR__ . '/../../model/sessions.php');
$session = new sessions();
$session->autenticar_session();
if(isset($_GET['sair'])){
  $session->quebra_session();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Salaberga - Seleção de Turma</title>
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

        .pulse-effect {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
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
            <a href="../relatorio.php" class="flex items-center gap-2 px-3 py-2 bg-white/10 rounded-lg hover:bg-white/20 transition-colors text-sm">
                <i class="fas fa-home"></i>
                <span>Menu</span>
            </a>
        </nav>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 pt-16">
        <div class="max-w-md w-full slide-in">
            <!-- Title Section -->
            <div class="text-center mb-8">
                <div class="icon-container mx-auto mb-4">
                    <i class="fas fa-qrcode text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Seleção de Turma</span>
                </h1>
                <p class="text-gray-600 text-sm">Selecione a turma para gerar o QR Code</p>
            </div>

            <!-- Form Container -->
            <div class="form-card p-8 border-t-4 border-ceara-green">
                <form id="turmaForm" action="qrcode.php" method="post" class="space-y-6">
                    <!-- Turma Selection -->
                    <div class="space-y-2">
                        <label for="turma" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-users mr-2 text-ceara-green"></i>
                            Turma
                        </label>
                        <select 
                            name="turma" 
                            id="turmajs" 
                            class="w-full p-4 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 bg-white text-sm"
                            required
                        >
                            <option value="">Selecione uma turma</option>
                            <option value="9">3° ano A</option>
                            <option value="10">3° ano B</option>
                            <option value="11">3° ano C</option>
                            <option value="12">3° ano D</option>
                            <option value="13">Area Dev</option>
                            <option value="14">Suporte TI</option>
                            <option value="15">Reimpressão Chachás</option>
                        </select>
                        <div id="turma-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="submitBtn"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode"></i>
                            Gerar QR Code
                        </span>
                        <span id="submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Gerando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-6">
                    <a href="../relatorio.php" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer relative py-4 text-center text-gray-600 text-xs">
        <div class="container mx-auto">
            <p>© 2024 Sistema Escolar Salaberga. Todos os direitos reservados.</p>
        </div>
    </footer>

    <script>
        class TurmaSelector {
            constructor() {
                this.form = document.getElementById('turmaForm');
                this.select = document.getElementById('turmajs');
                this.submitBtn = document.getElementById('submitBtn');
                this.init();
            }

            init() {
                // Add event listeners
                this.select.addEventListener('change', () => this.validateSelection());
                this.form.addEventListener('submit', (e) => this.handleSubmit(e));

                // Add visual feedback on select change
                this.select.addEventListener('change', () => {
                    if (this.select.value) {
                        this.select.classList.add('border-green-500');
                        this.select.classList.remove('border-gray-200');
                    } else {
                        this.select.classList.remove('border-green-500');
                        this.select.classList.add('border-gray-200');
                    }
                });
            }

            validateSelection() {
                const errorElement = document.getElementById('turma-error');
                
                if (!this.select.value) {
                    errorElement.classList.remove('hidden');
                    return false;
                } else {
                    errorElement.classList.add('hidden');
                    return true;
                }
            }

            handleSubmit(e) {
                if (!this.validateSelection()) {
                    e.preventDefault();
                    
                    // Shake effect for invalid selection
                    this.select.classList.add('border-red-500');
                    this.select.style.animation = 'shake 0.5s ease-in-out';
                    
                    setTimeout(() => {
                        this.select.style.animation = '';
                        this.select.classList.remove('border-red-500');
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

                // Add pulse effect to the form
                document.querySelector('.form-card').classList.add('pulse-effect');
            }
        }

        // Add shake animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);

        // Initialize when DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            new TurmaSelector();
        });

        // Add smooth scroll behavior
        document.documentElement.style.scrollBehavior = 'smooth';
    </script>
</body>
</html>