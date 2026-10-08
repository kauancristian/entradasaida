<?php
include_once 'app/main/model/sessions.php';
$session = new sessions();
$session->autenticar_session();
?>
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="Sistema SEEPS - Confirmação de Inscrição">
  <meta name="author" content="SEEPS">

  <title>Saida - Confirmação</title>

  <link rel="shortcut icon" href="../assets/images/icone_salaberga.png" type="image">
  <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --ceara-green: #008C45;
      --ceara-green-light: #00b357;
      --ceara-orange: #FFA500;
      --ceara-white: #FFFFFF;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Raleway', sans-serif;
    }

    body {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background: linear-gradient(135deg, var(--ceara-green) 0%, var(--ceara-green-light) 100%);
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg,
          rgba(255, 255, 255, 0.1) 25%,
          transparent 25%,
          transparent 50%,
          rgba(255, 255, 255, 0.1) 50%,
          rgba(255, 255, 255, 0.1) 75%,
          transparent 75%,
          transparent);
      background-size: 100px 100px;
      animation: backgroundMove 30s linear infinite;
      opacity: 0.3;
    }

    @keyframes backgroundMove {
      0% {
        background-position: 0 0;
      }

      100% {
        background-position: 100px 100px;
      }
    }

    .container {
      width: 100%;
      max-width: 600px;
      position: relative;
      z-index: 1;
    }

    .success-card {
      background-color: var(--ceara-white);
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
      padding: 3rem 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .success-icon {
      width: 90px;
      height: 90px;
      background: var(--ceara-green);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 2rem;
      position: relative;
      animation: pulseAnimation 2s infinite;
    }

    .success-icon i {
      color: var(--ceara-white);
      font-size: 40px;
    }

    h1 {
      color: #333;
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      letter-spacing: 0.5px;
    }


    .message {
      color: #666;
      font-size: 1.1rem;
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    .congratulations {
      display: block;
      color: var(--ceara-green);
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: -15px;

    }

    .loading-indicator {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
      color: var(--ceara-orange);
      font-size: 1rem;
      font-weight: 500;
    }

    .loading-dots {
      display: flex;
      gap: 5px;
    }

    .dot {
      width: 8px;
      height: 8px;
      background-color: var(--ceara-orange);
      border-radius: 50%;
      animation: dotAnimation 1.4s infinite;
    }

    .dot:nth-child(2) {
      animation-delay: 0.2s;
    }

    .dot:nth-child(3) {
      animation-delay: 0.4s;
    }

    @keyframes pulseAnimation {
      0% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(0, 140, 69, 0.4);
      }

      70% {
        transform: scale(1.05);
        box-shadow: 0 0 0 15px rgba(0, 140, 69, 0);
      }

      100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(0, 140, 69, 0);
      }
    }

    @keyframes dotAnimation {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-5px);
      }
    }

    @media (max-width: 480px) {
      .success-card {
        padding: 2rem 1.5rem;
      }

      h1 {
        font-size: 1.8rem;
      }

      .success-icon {
        width: 70px;
        height: 70px;
      }

      .success-icon i {
        font-size: 30px;
      }
    }
  </style>
</head>

<body>
  <div class="container text-center">
    <div class="success-card">
      <div class="success-icon">
        <i class="fas fa-check"></i>
      </div>
      <h1>Aluno(a) <?php echo $_GET['id_aluno']; ?> saiu com sucesso!</h1>
      <p class="message">
        
        <br>
        Sua saida foi processada e confirmada em nosso sistema.
      </p>
      <div class="loading-indicator" style="margin-left: 30px">
        Redirecionando
        <div class="loading-dots">
          <div class="dot"></div>
          <div class="dot"></div>
          <div class="dot"></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Redireciona a página após 2 segundos
    setTimeout(function() {
      window.location.href = "app/main/views/relatorios/ultimo_registro.php";
    }, 550);
</script>
</body>

</html>