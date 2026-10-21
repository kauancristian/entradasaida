<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class sessions
{
    private function acessoLocal()
    {
        $host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
        $hostsLocais = ['localhost', '127.0.0.1', '::1'];
        $ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];

        return in_array(strtolower((string) $host), $hostsLocais, true)
            && in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);
    }

    function usuarioAtualEhRecepcao()
    {
        $tiposUsuario = $_SESSION['tipos_usuario_permitidos'] ?? [];

        return is_array($tiposUsuario) && in_array('usuario_recepcao', $tiposUsuario, true);
    }

    function simularLoginLocalParaTeste()
    {
        if (!$this->acessoLocal()) {
            return false;
        }

        if (!$this->obterDadosUsuarioAtual()) {
            return false;
        }

        $_SESSION['ultimo_acesso'] = time();
        $_SESSION['login_teste_local'] = true;

        return true;
    }

    function obterDadosUsuarioAtual()
    {
        $id = $_SESSION['id'] ?? null;
        $email = $_SESSION['email'] ?? null;
        $nome = $_SESSION['nome'] ?? null;
        if (
            (!is_string($id) && !is_int($id))
            || !is_string($email)
            || !is_string($nome)
        ) {
            return null;
        }

        $id = trim((string) $id);
        $email = trim($email);
        $nome = trim($nome);
        if ($id === '' || $email === '' || $nome === '' || strlen($id) > 191) {
            return null;
        }

        return ['id' => $id, 'email' => $email, 'nome' => $nome];
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
            header('location:https://salaberga.com/salaberga/portalsalaberga/app/main/views/autenticacao/login.php');
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
