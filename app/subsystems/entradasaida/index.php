<?php

$identificadorAluno = $_GET['id_aluno'] ?? null;
if (!is_string($identificadorAluno) || trim($identificadorAluno) === '') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'O QR Code não contém a identificação do aluno.';
    exit;
}

header(
    'Location: ../../../index.php?id_aluno=' . rawurlencode($identificadorAluno),
    true,
    302
);
exit;