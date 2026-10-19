<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class sessions
{
    function simularLoginLocalParaTeste()
    {
        $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $hostsLocais = ['localhost', '127.0.0.1', '::1'];
        $ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
        $acessoLocal = in_array(strtolower((string) $host), $hostsLocais, true)
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);

        if (!$acessoLocal) {
            return false;
        }

        $idAtual = filter_var($_SESSION['id'] ?? null, FILTER_VALIDATE_INT);
        if ($idAtual !== false && $idAtual > 0) {
            return true;
        }

        require_once __DIR__ . '/select_model.php';
        $emailAtual = $_SESSION['email'] ?? null;
        try {
            $usuario = (new select_model())->usuarioAtivoParaTesteLocal($emailAtual);
        } catch (Throwable $e) {
            error_log('Erro ao preparar sessão local de teste: ' . $e->getMessage());
            return false;
        }

        if (!$usuario) {
            return false;
        }

        $_SESSION['id'] = (int) $usuario['id_usuario'];
        $_SESSION['email'] = (string) ($usuario['email'] ?? 'local-teste@localhost');
        $_SESSION['nome'] = (string) $usuario['nome'];
        $_SESSION['ultimo_acesso'] = time();
        $_SESSION['login_teste_local'] = !is_string($emailAtual) || trim($emailAtual) === '';

        return true;
    }

    function autenticar_session()
    {
        try {
            if (!isset($_SESSION['email']) || !isset($_SESSION['nome']) || !isset($_SESSION['id'])) {

                session_unset();
                session_destroy();
                header('location:../../../main/views/autenticacao/login.php');
                exit();
            }
        } catch (PDOException $e) {
            header('location: ../views/windows/faltal_erro.php');
            exit();
        }
    }

    function tempo_session($tempo = 600)
    {
        try {
            if (isset($_SESSION['ultimo_acesso'])) {

                if (time() - $_SESSION['ultimo_acesso'] > $tempo) {

                    session_unset();
                    session_destroy();
                    header('location:../../../main/views/autenticacao/login.php');
                    exit();
                }
            }
            $_SESSION['ultimo_acesso'] = time();
        } catch (PDOException $e) {
            header('location: ../views/windows/faltal_erro.php');
            exit();
        }
    }

    function deslogar()
    {
        try {
            session_unset();
            session_destroy();
            header('location:../../../main/views/autenticacao/login.php');
            exit();
        } catch (PDOException $e) {
            header('location: ../views/windows/faltal_erro.php');
            exit();
        }
    }
}

if (isset($_GET['sair'])) {

    $session = new sessions();
    $session->deslogar();
}
