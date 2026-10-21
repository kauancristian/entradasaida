<?php
require_once __DIR__ . '/../model/select_model.php';
require_once __DIR__ . '/../model/sessions.php';
require_once __DIR__ . '/../model/RestrictionModel.php';
$session = new sessions();

function exitRecordDataAttributes(array $record): string
{
    $attributes = [
        'data-exit-id' => (string) ($record['id_saida'] ?? ''),
        'data-exit-student' => (string) ($record['nome'] ?? ''),
        'data-exit-matricula' => (string) ($record['matricula'] ?? ''),
        'data-exit-turma' => (string) ($record['id_turma'] ?? ''),
        'data-exit-datetime' => (string) ($record['dae'] ?? '')
    ];

    $html = '';
    foreach ($attributes as $name => $value) {
        $html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }

    return $html;
}

$host = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
$hostsLocais = ['localhost', '127.0.0.1', '::1'];
$ipsLocais = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
$acessoLocalTemporario = in_array(strtolower((string) $host), $hostsLocais, true)
    && in_array($_SERVER['REMOTE_ADDR'] ?? '', $ipsLocais, true);
if (!$acessoLocalTemporario) {
    $session->autenticar_session();
} else {
    $session->simularLoginLocalParaTeste();
}
$ehRecepcao = $session->usuarioAtualEhRecepcao();
$select = new select_model();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$restrictionModel = new RestrictionModel();
$restrictionModelError = false;
$restrictionList = [];
$restrictionClasses = [];
$restrictionAssignments = [];
try {
    $restrictionList = $restrictionModel->listarRestricoes();
    $restrictionClasses = $restrictionModel->listarTurmas();
    $restrictionAssignments = $restrictionModel->restricoesPorTurma();
} catch (PDOException $e) {
    $restrictionModelError = true;
    error_log('Não foi possível carregar restrições: ' . $e->getMessage());
}
$validacaoAtrasosDisponivel = $select->suportaValidacaoAtrasos();
$sectionInicial = $_GET['section'] ?? ($ehRecepcao ? 'inicio' : 'entrada');
$sectionsPermitidas = ['inicio', 'entrada', 'saida', 'estagio', 'restricoes', 'relatorios', 'relatorio-entrada', 'relatorio-saida', 'consulta-saida', 'consulta-estagio', 'consulta-ausencias', 'relatorio-estagio', 'relatorio-dia', 'qrcode', 'ultimas-saidas', 'atrasos', 'cadastro'];
if ($ehRecepcao) {
    $sectionsPermitidas = ['inicio', 'entrada', 'saida', 'estagio', 'consulta-ausencias'];
}
if (!in_array($sectionInicial, $sectionsPermitidas, true)) {
    $sectionInicial = 'entrada';
}
$statusRegistro = $_GET['status'] ?? '';
$mensagensRegistro = [
    'success' => 'Registro salvo com sucesso no banco de dados.',
    'ja_registrado' => 'Já existe um registro para este aluno nesta data.',
    'aluno_nao_encontrado' => 'O aluno selecionado não foi encontrado.',
    'campos_obrigatorios' => 'Preencha todos os campos obrigatórios.',
    'data_hora_futura' => 'Não é permitido registrar uma data ou horário futuro. Escolha um horário até o momento atual.',
    'restricao_bloqueada' => 'Uma restrição configurada para a turma impede este registro neste horário.',
    'qr_atraso_estrutura' => 'A estrutura do banco está incompleta. Consulte o README para configurar o banco de dados.',
    'qr_atraso_opcoes' => 'Faltam opções obrigatórias no banco para registrar atrasos pelo QR Code.',
    'usuario_inativo' => 'Não há um usuário autenticado na sessão. Entre novamente no sistema.',
    'acesso_negado' => 'Seu perfil não tem acesso a esta função.',
    'erro_interno' => 'Não foi possível salvar no banco. Confira os dados e tente novamente.',
    'erro_desconhecido' => 'O registro não foi concluído. Atualize a página e tente novamente.'
];
$mensagemRegistro = $mensagensRegistro[$statusRegistro] ?? '';
$classeMensagemRegistro = $statusRegistro === 'success'
    ? 'border-green-200 bg-green-50 text-green-800'
    : ($statusRegistro === 'ja_registrado'
        ? 'border-amber-200 bg-amber-50 text-amber-900'
        : 'border-red-200 bg-red-50 text-red-800');
$statusCadastro = $_GET['status'] ?? '';
$mensagensCadastro = [
    'success' => 'Aluno cadastrado com sucesso.',
    'matricula_duplicada' => 'Já existe um aluno com esta matrícula.',
    'turma_curso_invalidos' => 'Selecione uma turma e um curso válidos.',
    'campos_invalidos' => 'Confira o nome, a matrícula, a turma e o curso.',
    'erro_interno' => 'Não foi possível cadastrar o aluno. Tente novamente.'
];
$mensagemCadastro = $mensagensCadastro[$statusCadastro] ?? '';
$statusEdicao = is_string($_GET['edit_status'] ?? null) ? $_GET['edit_status'] : '';
$mensagensEdicao = [
    'success' => 'As alterações foram salvas com sucesso.',
    'duplicate' => 'Este aluno já possui um registro desse tipo nessa data.',
    'not_found' => 'O registro não foi encontrado. Atualize a consulta e tente novamente.',
    'restricted' => 'A edição foi bloqueada por uma restrição de horário atribuída à turma.',
    'invalid' => 'Confira os dados informados e tente novamente.',
    'error' => 'Não foi possível salvar as alterações. Tente novamente.'
];
$mensagemSucessoToast = '';
$mensagemErroToast = '';
if (isset($mensagensEdicao[$statusEdicao])) {
    if ($statusEdicao === 'success') {
        $mensagemSucessoToast = $mensagensEdicao[$statusEdicao];
    } else {
        $mensagemErroToast = $mensagensEdicao[$statusEdicao];
    }
} elseif ($statusCadastro === 'success' && $sectionInicial === 'cadastro') {
    $mensagemSucessoToast = $mensagemCadastro;
} elseif ($statusRegistro === 'success') {
    $mensagensSucessoRegistro = [
        'entrada' => 'O atraso do aluno foi registrado com sucesso.',
        'saida' => 'A saída do aluno foi registrada com sucesso.',
        'estagio' => 'A saída do aluno para estágio foi registrada com sucesso.',
        'atrasos' => 'A entrada atrasada foi registrada com sucesso.',
        'ultimas-saidas' => 'A saída do aluno para estágio foi registrada com sucesso.'
    ];
    $mensagemSucessoToast = $mensagensSucessoRegistro[$sectionInicial] ?? $mensagensRegistro['success'];
} elseif (isset($mensagensRegistro[$statusRegistro]) && $statusRegistro !== '') {
    $mensagemErroToast = $mensagensRegistro[$statusRegistro];
}
$atrasosPorTurma = [9 => [], 10 => [], 11 => [], 12 => []];
foreach ($select->atrasosHoje() as $registroAtraso) {
    $idTurmaAtraso = (int) $registroAtraso['id_turma'];
    if (isset($atrasosPorTurma[$idTurmaAtraso])) {
        $atrasosPorTurma[$idTurmaAtraso][] = $registroAtraso;
    }
}
$historicoAtrasos = $select->historicoAtrasos();
$saidasAntecipadas = $select->saidasAntecipadas();
$saidasEstagio = $select->saidasEstagio();
$turmasConsultaSaida = $select->select_turmas();
$turmasPorId = [];
foreach ($turmasConsultaSaida as $turmaConsulta) {
    $turmasPorId[(int) $turmaConsulta['id_turma']] = ucfirst((string) $turmaConsulta['ano']) . ' ' . strtoupper((string) $turmaConsulta['turma']);
}
$cursosPorId = [];
foreach ($select->select_curso() as $cursoConsulta) {
    $cursosPorId[(int) $cursoConsulta['id_curso']] = ucfirst((string) $cursoConsulta['curso']);
}
$alunosConsultaEdicao = $select->select_alunos();
$tiposResponsaveisEdicao = $select->select_responsavel();
$tiposConducentesEdicao = $select->select_conducente();
$motivosEdicao = $select->select_motivo();
$historicoPorTurma = [9 => [], 10 => [], 11 => [], 12 => []];
foreach ($historicoAtrasos as $registroHistorico) {
    $idTurmaHistorico = (int) $registroHistorico['id_turma'];
    if (isset($historicoPorTurma[$idTurmaHistorico])) {
        $historicoPorTurma[$idTurmaHistorico][] = $registroHistorico;
    }
}
$detalhesAtraso = static function ($registro) {
    $detalhes = [
        'Aluno' => $registro['nome'],
        'Matrícula' => $registro['matricula'] ?? 'Não informada',
        'Turma' => '3º Ano ' . strtoupper($registro['letra_turma']),
        'Data e hora' => date('d/m/Y H:i', strtotime($registro['date_time'])),
        'Registrado por' => ($registro['usuario_nome'] ?? '') ?: 'Não informado',
        'Responsável' => $registro['nome_responsavel'] ?: 'Não informado',
        'Tipo de responsável' => $registro['tipo_responsavel'] ?: 'Não informado',
        'Acompanhante' => $registro['nome_conducente'] ?: 'Não informado',
        'Tipo de acompanhante' => $registro['tipo_conducente'] ?: 'Não informado',
        'Motivo' => $registro['motivo'] ?: 'Não informado',
        'Justificativa do atraso' => ($registro['justificativa_atraso'] ?? '') ?: 'Não informada',
        'Status da justificativa' => [
            'pendente' => 'Pendente',
            'aprovada' => 'Aprovada',
            'recusada' => 'Recusada'
        ][$registro['status_justificativa'] ?? 'pendente'] ?? 'Pendente',
        'Validado por' => ($registro['validado_por'] ?? '') ?: 'Ainda não validada',
        'Validado em' => !empty($registro['validado_em']) ? date('d/m/Y H:i', strtotime($registro['validado_em'])) : 'Ainda não validada',
        'Justificativa da validação' => ($registro['observacao_validacao'] ?? '') ?: 'Nenhuma justificativa'
    ];
    return $detalhes;
};
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<title>Sistema Escolar Salaberga</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
(() => {
    const endpoint = 'relatorios/comprovante_termico.php';
    let reservedWindow = null;

    function reserveWindow() {
        const printWindow = window.open('', '_blank');
        if (!printWindow) {
            window.alert('Permita a abertura de janelas para visualizar o comprovante em PDF.');
            return null;
        }
        printWindow.document.write('<title>Preparando comprovante</title><p>Preparando comprovante para impressão...</p>');
        return printWindow;
    }

    window.prepareThermalReceiptWindow = () => {
        reservedWindow = reserveWindow();
        return reservedWindow;
    };

    async function imprimirComprovante(tipo, dados, printWindow = null, prepared = false) {
        const destino = prepared
            ? printWindow
            : (printWindow || reservedWindow || reserveWindow());
        reservedWindow = null;
        if (!destino) return;

        try {
            const formData = new FormData();
            formData.set('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
            formData.set('tipo', tipo);
            Object.entries(dados).forEach(([campo, valor]) => {
                formData.set(campo, valor == null ? '' : String(valor));
            });

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
                headers: {'Accept': 'application/pdf'}
            });
            if (!response.ok) {
                let message = 'Não foi possível gerar o comprovante em PDF.';
                try {
                    const error = await response.json();
                    message = error.message || message;
                } catch {}
                throw new Error(message);
            }

            const pdf = await response.blob();
            if (!pdf.type.startsWith('application/pdf')) {
                throw new Error('O servidor não retornou um arquivo PDF válido.');
            }
            const pdfUrl = URL.createObjectURL(pdf);
            destino.location.replace(pdfUrl);
            window.setTimeout(() => URL.revokeObjectURL(pdfUrl), 60000);
        } catch (error) {
            destino.close();
            console.error('Não foi possível gerar o comprovante térmico.', error);
            window.alert(error.message || 'Não foi possível gerar o comprovante em PDF.');
        }
    }

    window.cancelThermalReceiptWindow = printWindow => {
        (printWindow || reservedWindow)?.close();
        reservedWindow = null;
    };
    window.printEntryReceipt = (data, printWindow) => imprimirComprovante('entrada', data, printWindow, true);
    window.printExitReceipt = (data, printWindow) => imprimirComprovante('saida', data, printWindow, true);
    window.printDelayReceipt = data => imprimirComprovante('atraso', {
        aluno: data.Aluno,
        matricula: data['Matrícula'],
        turma: data.Turma,
        motivo: data.Motivo,
        justificativa: data['Justificativa do atraso']
    });
})();
</script>
<script>tailwind={config:{theme:{extend:{colors:{'ceara-green':'#008C45','ceara-light-green':'#3CB371','ceara-olive':'#8CA03E','ceara-orange':'#FFA500',primary:'#4CAF50',secondary:'#FFB74D',danger:'#dc3545',admin:'#0dcaf0',grey:'#6c757d',info:'#4169E1'}},fontFamily:{inter:['Inter','sans-serif']}}}};</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
:root{--salaberga-dark:#064c2b;--salaberga-green:#008c45;--salaberga-active:#276448;--salaberga-amber:#f3a11a;--salaberga-workspace:#eaf3e8;--salaberga-ink:#14251c;--salaberga-muted:#64736a;--sidebar-width:256px;--modal-overlay:rgba(10,20,17,.42);--modal-border:rgba(15,23,42,.08);--modal-shadow:0 24px 68px rgba(7,24,16,.16);--modal-surface:#fff;--modal-radius:22px;--modal-padding:1.5rem;--modal-max-width:32rem;--modal-max-height:calc(100dvh - 2rem)}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;font-family:'Inter',sans-serif;color:var(--salaberga-ink);background:var(--salaberga-workspace)}
.sidebar{position:fixed;inset:0 auto 0 0;z-index:1000;width:256px;display:flex;flex-direction:column;padding:25px 16px 18px;background:var(--salaberga-dark);color:#f5fff8}
.brand{display:flex;align-items:center;gap:11px;margin:2px 7px 35px;color:#fff;text-decoration:none;font-size:15px;font-weight:700}.brand-mark{display:grid;width:40px;height:40px;place-items:center;border:1px solid #ffffff55;border-radius:11px;color:#ffc04d;font-size:20px}.brand small{display:block;margin-top:4px;color:#b6d4c1;font-size:10px;font-weight:400}
.sidebar>nav{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#3c7254 transparent}.nav-group{margin-bottom:20px}.nav-label{margin:0 10px 8px;color:#8eb9a0;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.nav-group>summary.nav-label{display:flex;align-items:center;justify-content:space-between;cursor:pointer;list-style:none}.nav-group>summary.nav-label::-webkit-details-marker{display:none}.nav-group>summary.nav-label::after{width:6px;height:6px;margin-right:3px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;content:'';transform:rotate(45deg);transition:transform .18s ease}.nav-group[open]>summary.nav-label::after{transform:rotate(225deg)}.nav-group>summary.nav-label:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:3px}.nav-item{position:relative;width:100%;min-height:40px;display:flex;align-items:center;gap:12px;margin:3px 0;padding:0 11px;border:0;border-radius:7px;background:transparent;color:#e1f0e6;text-align:left;font:600 12px 'Inter',sans-serif;cursor:pointer}.nav-item i{width:16px;color:#b8d7c3;text-align:center}.nav-item:hover{background:#ffffff1a;color:#fff}.nav-item.active{background:var(--salaberga-active);color:#ffc04d}.nav-item.active:before{position:absolute;inset:8px auto 8px 0;width:3px;border-radius:0 3px 3px 0;background:var(--salaberga-amber);content:''}.nav-item.active i{color:#ffc04d}.sidebar-bottom{margin-top:auto}.nav-item.logout{color:#f0d5ce}.nav-item.logout i{color:#f1aa91}
.nav-group>summary.nav-label{min-height:34px;margin:0 2px 8px;padding:0 10px;border:1px solid #ffffff24;border-radius:6px;background:#ffffff0a;color:#c5dfce;font-size:10px;transition:background .18s ease,color .18s ease}.nav-group>summary.nav-label:hover{background:#ffffff16;color:#fff}.nav-item{font-size:13px}.nav-item i{color:#c5dfce}.nav-item:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:2px}.nav-group[open]>.nav-item{animation:sidebar-submenu-in .2s ease both}.nav-group[open]>.nav-item:nth-child(3){animation-delay:35ms}.nav-group[open]>.nav-item:nth-child(4){animation-delay:70ms}.nav-group[open]>.nav-item:nth-child(5){animation-delay:105ms}.nav-group[open]>.nav-item:nth-child(6){animation-delay:140ms}@keyframes sidebar-submenu-in{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
.main-content{min-height:100vh;margin-left:256px;padding:34px clamp(20px,4vw,60px);}.page-section{display:none;max-width:1200px;margin:0 auto}.page-section.active{display:block}.section-title{margin:0 0 22px;color:#08723a;font-size:25px}.section-lead{margin:-14px 0 22px;color:var(--salaberga-muted);font-size:13px}.page-section [class*="max-w-"]{max-width:100%}.page-section .container{width:100%;max-width:100%;margin-left:auto;margin-right:auto}.page-section .fixed{z-index:900}
#atrasos{max-width:none;width:100%;margin:0}.delays-shell{position:relative;min-height:calc(100dvh - 68px);padding:16px;border-radius:12px;background:#fff;box-shadow:0 2px 8px #143b2114}.delays-heading{text-align:center;margin:14px 0 30px}.delays-topline{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}.delays-back{position:absolute;top:16px;left:16px;display:inline-flex;align-items:center;gap:9px;padding:9px 14px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#53615a;box-shadow:0 2px 6px #00000012;font:500 13px 'Inter',sans-serif;cursor:pointer}.delays-back i{color:#008c45}.delays-title{margin:0;font-size:30px;font-weight:700}.delays-clock{padding:8px 16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#344254;box-shadow:0 1px 3px #0000000c;font-size:19px;font-weight:700;font-variant-numeric:tabular-nums}.delays-note{margin:14px 0 0;color:#64736a;font-size:13px}.delays-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.delay-card{min-height:350px;overflow:hidden;border:1px solid #e5e7eb;border-radius:12px;background:#fff;box-shadow:0 2px 6px #143b2118}.delay-card-header{min-height:99px;padding:16px;color:white}.delay-card-header.turma-3a{background:#cf2035}.delay-card-header.turma-3b{background:#3854d9}.delay-card-header.turma-3c{background:#0db5d0}.delay-card-header.turma-3d{background:#59636b}.delay-card-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.delay-card-heading h2{display:flex;align-items:center;gap:8px;margin:0;font-size:17px;font-weight:700}.delay-count{width:34px;height:28px;border-radius:18px;background:#fff}.delay-search{width:min(100%,200px);height:32px;padding:0 12px;border:1px solid #ffffff70;border-radius:6px;background:#ffffff30;color:#fff;font:400 13px 'Inter',sans-serif}.delay-search::placeholder{color:#ffffffc9}.delay-search:focus{outline:2px solid #fff;outline-offset:1px}.delay-empty{padding:44px 14px;color:#64748b;text-align:center;font-size:15px;font-style:italic}
#relatorio-entrada > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
.delay-list{min-height:230px;max-height:420px;overflow-y:auto;padding:8px 12px}.delay-entry{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 2px;border-bottom:1px solid #edf0ee}.delay-entry:last-child{border-bottom:0}.delay-entry-name-group,.delay-history-entry-name-group{display:flex;min-width:0;align-items:center;gap:7px}.delay-entry-name-group{flex:1}.delay-entry-name{flex:1;overflow:hidden;color:#26352c;font-size:13px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.delay-entry-meta{display:flex;align-items:center;gap:10px;color:#64736a;font-size:12px;white-space:nowrap}.delay-detail-button{border:0;background:transparent;color:#08723a;font:600 12px 'Inter',sans-serif;cursor:pointer}.delay-detail-button:hover{text-decoration:underline}.delay-print-button{display:inline-grid;flex:none;width:28px;height:28px;place-items:center;border:1px solid #d5e3d9;border-radius:6px;background:#f2f8f3;color:#08723a;cursor:pointer}.delay-print-button:hover{background:#e4f1e7}.delay-print-button:focus-visible{outline:2px solid #008c45;outline-offset:2px}.delay-count{display:grid;place-items:center;color:#23352a;font-size:12px;font-weight:700}.delay-search:disabled{opacity:1;cursor:not-allowed}.delay-history{margin-top:30px;padding-top:20px;border-top:1px solid #e7ece8}.delay-history h2{margin:0;color:#08723a;font-size:21px;font-weight:700}.delay-history-note{margin:6px 0 14px;color:#64736a;font-size:13px}.delay-history-table{width:100%;border-collapse:collapse;background:#fff}.delay-history-table th,.delay-history-table td{padding:11px 12px;border-bottom:1px solid #e7ece8;text-align:left;font-size:13px}.delay-history-table th{background:#f4f8f3;color:#53615a;font-weight:700}.delay-history-empty{padding:24px 12px;border:1px solid #e7ece8;border-radius:8px;color:#64736a;text-align:center;font-size:14px}.delay-dialog{width:min(520px,calc(100vw - 32px));max-height:calc(100dvh - 32px);padding:0;border:0;border-radius:10px;box-shadow:0 16px 48px #102b1c40}.delay-dialog::backdrop{background:#10251bb0}.delay-dialog-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e7ece8}.delay-dialog-header h2{margin:0;color:#08723a;font-size:19px}.delay-dialog-close{width:34px;height:34px;border:0;border-radius:6px;background:#f1f5f2;color:#43534a;cursor:pointer}.delay-dialog-content{padding:18px 20px}.delay-dialog-row{display:grid;grid-template-columns: minmax(130px, 1fr) 2fr;gap:12px;padding:9px 0;border-bottom:1px solid #edf0ee;font-size:14px}.delay-dialog-row:last-child{border-bottom:0}.delay-dialog-label{color:#64736a}.delay-dialog-value{color:#203128;font-weight:600;overflow-wrap:anywhere}
#relatorio-estagio > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
#ultimas-saidas{width:calc(100% + min(16px,1.5vw) - 40px);max-width:none;margin-left:12px;margin-right:calc(-1 * min(16px,1.5vw))}
#ultimas-saidas .main-container{width:100%;max-width:none}
#ultimas-saidas .main-container > .text-center{margin-top:14px}
.app-modal-overlay{position:fixed;inset:0 auto 0 var(--sidebar-width,0px);width:calc(100vw - var(--sidebar-width,0px));display:grid;place-items:center;padding:clamp(1rem,2vw,2rem);background:var(--modal-overlay);z-index:1300;overflow-y:auto;overscroll-behavior:contain}.app-modal-card{position:relative;width:min(100%,var(--modal-max-width));max-width:100%;max-height:var(--modal-max-height);display:flex;flex-direction:column;align-items:stretch;background:var(--modal-surface);border:1px solid var(--modal-border);border-radius:var(--modal-radius);box-shadow:var(--modal-shadow);overflow:hidden}.app-modal-card:focus-visible{outline:2px solid rgba(0,140,69,.28);outline-offset:2px}.app-modal-card > *{min-width:0}.app-modal-body{text-align:center;padding:clamp(1.5rem,2vw,2rem)}.app-modal-icon{width:4rem;height:4rem;display:grid;place-items:center;margin:0 auto 1.25rem;border-radius:50%;background:rgba(0,140,69,.1);color:var(--salaberga-green)}.app-modal-card.error .app-modal-icon{background:rgba(220,53,69,.1);color:#dc3545}.app-modal-card.warning .app-modal-icon{background:rgba(243,161,26,.12);color:#d97706}.app-modal-card h3{margin:0 0 .75rem;color:#1d2c24;font-size:clamp(1.45rem,2vw,2rem);font-weight:700;line-height:1.2}.app-modal-card p{margin:0;color:#53615a;font-size:.97rem;line-height:1.6}.app-modal-actions{display:flex;justify-content:center;padding:0 clamp(1.5rem,2vw,2rem) clamp(1.5rem,2vw,2rem);margin-top:1.25rem}.app-modal-actions button,.app-modal-actions .button{width:min(100%,17rem);min-height:2.9rem;padding:.8rem 1.15rem;border:0;border-radius:12px;background:linear-gradient(90deg,#008c45,#3cb371);color:#fff;font:600 14px 'Inter',sans-serif;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,filter .2s ease}.app-modal-actions button:hover,.app-modal-actions .button:hover{transform:translateY(-1px);box-shadow:0 10px 16px rgba(0,140,69,.18);filter:brightness(1.02)}.app-modal-card.error .app-modal-actions button,.app-modal-card.error .app-modal-actions .button{background:linear-gradient(90deg,#dc3545,#ef4444)}.app-modal-card.warning .app-modal-actions button,.app-modal-card.warning .app-modal-actions .button{background:linear-gradient(90deg,#d97706,#f59e0b)}.app-modal-close{position:absolute;top:.9rem;right:.9rem;display:grid;place-items:center;width:2.25rem;height:2.25rem;border:1px solid rgba(15,23,42,.08);border-radius:10px;background:#f4f7f5;color:#495b55;font-size:1rem;cursor:pointer;transition:background .2s ease,border-color .2s ease}.app-modal-close:hover{background:#eef6f0;border-color:rgba(8,114,58,.2)}.app-modal-card .app-modal-body .app-modal-icon svg{width:2.2rem;height:2.2rem}.delay-dialog{position:fixed;top:50%;left:calc(var(--sidebar-width,0px) / 2 + 50vw);transform:translate(-50%,-50%);width:min(560px,calc(100vw - var(--sidebar-width,0px) - 2rem));max-width:calc(100vw - var(--sidebar-width,0px) - 2rem);max-height:var(--modal-max-height);padding:0;border:0;border-radius:18px;background:#fff;box-shadow:var(--modal-shadow);overflow:hidden}.delay-dialog::backdrop{background:var(--modal-overlay)}.delay-dialog-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.2rem 1.25rem;border-bottom:1px solid rgba(15,23,42,.08)}.delay-dialog-header h2{margin:0;color:#1d2c24;font-size:1.2rem;font-weight:700}.delay-dialog-heading{display:flex;align-items:flex-start;gap:10px;min-width:0}.delay-dialog-heading p{margin:5px 0 0;color:#64736a;font-size:.82rem;line-height:1.5}.delay-dialog-close{display:grid;place-items:center;width:2.2rem;height:2.2rem;border:1px solid rgba(15,23,42,.08);border-radius:10px;background:#f4f7f5;color:#43534a;cursor:pointer}.delay-dialog-content{max-height:min(52dvh,420px);overflow-y:auto;padding:1.2rem 1.25rem;scrollbar-gutter:stable}.delay-dialog-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem}.delay-dialog-label,.delay-form-label,.delay-justification-label{display:block;margin:0 0 .45rem;color:#475c54;font-size:.8rem;font-weight:700}.delay-dialog-value{margin:0;color:#1d2c24;font-size:.92rem;line-height:1.5}.delay-validation-summary{margin-bottom:1rem;padding:1rem 1.1rem;border:1px solid rgba(243,161,26,.28);border-radius:12px;background:#fffaf0}.delay-validation-summary-label{margin:0 0 .3rem;color:#9a4b08;font-size:.68rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.delay-validation-summary-name{margin:0;color:#17251c;font-size:1.02rem;font-weight:700}.delay-validation-summary-meta{margin:.35rem 0 0;color:#586d66;font-size:.82rem}.delay-justification{width:100%;min-height:88px;resize:vertical;padding:.75rem .9rem;border:1px solid rgba(148,163,184,.6);border-radius:10px;background:#fff;color:#17251c;font:400 .92rem/1.5 'Inter',sans-serif}.delay-form-field{display:grid;gap:.4rem}.delay-form-control{width:100%;min-height:42px;padding:.65rem .8rem;border:1px solid rgba(148,163,184,.65);border-radius:10px;background:#fff;color:#17251c;font:400 .9rem 'Inter',sans-serif;transition:border-color .2s ease,box-shadow .2s ease}.delay-form-control:focus,.delay-justification:focus{outline:none;border-color:rgba(0,140,69,.7);box-shadow:0 0 0 3px rgba(0,140,69,.1)}.delay-decision-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem}.delay-decision-option{display:flex;align-items:center;justify-content:center;min-height:2.8rem;padding:.7rem .8rem;border:1px solid rgba(148,163,184,.7);border-radius:10px;background:#fff;color:#43534a;font:600 .82rem 'Inter',sans-serif;cursor:pointer}.delay-decision-option-approve{border-color:rgba(34,197,94,.38);color:#176b38}.delay-decision-option-reject{border-color:rgba(239,68,68,.3);color:#9b2c25}.delay-decision-option[aria-pressed="true"]{box-shadow:0 0 0 2px rgba(0,140,69,.12)}.delay-dialog-foot{display:flex;justify-content:flex-end;gap:.8rem;padding:0 1.25rem 1.2rem}.delay-dialog-foot button{min-height:2.75rem;padding:.7rem 1rem;border:1px solid rgba(148,163,184,.7);border-radius:10px;background:#fff;color:#2d3d38;font:600 .82rem 'Inter',sans-serif;cursor:pointer}.delay-dialog-foot .delay-detail-button{border-color:rgba(0,140,69,.2);background:linear-gradient(90deg,#008c45,#3cb371);color:#fff}.delay-dialog-foot .delay-detail-button-secondary{background:#f2f8f3;color:#08723a}.delay-form-field[hidden],.delay-validation-form[hidden]{display:none}@media(max-width:760px){:root{--sidebar-width:0px}.app-modal-overlay{left:0;width:100vw}.delay-dialog{left:50vw;width:min(560px,calc(100vw - 1.25rem));max-width:calc(100vw - 1.25rem)}.delay-dialog-row{grid-template-columns:1fr}}
@media(max-width:1100px){#ultimas-saidas{width:100%;margin-left:0;margin-right:0}}
@media(max-width:1100px){.delays-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.delays-back{position:static;margin-bottom:18px}.delays-shell{min-height:calc(100dvh - 100px)}}
@media(max-width:600px){.delays-grid{grid-template-columns:1fr}.delay-card{min-height:300px}.delays-title{font-size:25px}.delays-clock{font-size:16px}.delays-heading{margin:8px 0 22px}}
.page-section .bg-white{background:#fff}.page-section .rounded-xl,.page-section .rounded-2xl{border-radius:8px}.page-section .shadow-lg,.page-section .shadow-md{box-shadow:0 2px 7px #143b2114}
.page-section .bg-gradient-to-r.from-ceara-green.to-ceara-light-green{background:linear-gradient(100deg,var(--salaberga-dark),var(--salaberga-green))!important}
.page-section .bg-gray-50,.page-section .bg-slate-50,.page-section .bg-gray-100{background:#f4f8f3}.page-section .text-ceara-green,.page-section .text-green-600,.page-section .text-green-700{color:var(--salaberga-green)}.page-section .bg-ceara-green,.page-section .bg-green-600,.page-section .bg-green-700{background-color:var(--salaberga-green)}
.mobile-menu,.sidebar-close,.sidebar-scrim{display:none}
@media(max-width:760px){:root{--sidebar-width:0px}.sidebar{width:min(290px,84vw);transform:translateX(-102%);transition:transform .22s ease}.sidebar-open .sidebar{transform:translateX(0)}.sidebar-close{position:absolute;top:20px;right:14px;display:grid;width:34px;height:34px;place-items:center;border:1px solid #ffffff44;border-radius:7px;background:transparent;color:white}.brand{margin-right:38px}.mobile-menu{position:fixed;top:10px;left:12px;z-index:999;display:grid;width:38px;height:38px;place-items:center;border:0;border-radius:7px;background:var(--salaberga-dark);color:#fff}.sidebar-scrim{position:fixed;inset:0;z-index:998;display:none;border:0;background:#081f126b}.sidebar-open .sidebar-scrim{display:block}.main-content{margin-left:0;padding:62px 15px 24px}#ultimas-saidas{width:100%;margin-left:0;margin-right:0}.page-section [id$="Modal"]:not([id$="ModalContent"]){padding:12px}.page-section [id$="ModalContent"]{height:min(520px,calc(100dvh - 24px));max-height:calc(100dvh - 24px);padding:24px}.app-modal-overlay{left:0;width:100vw}.delay-dialog{left:50vw;width:min(560px,calc(100vw - 1.25rem));max-width:calc(100vw - 1.25rem)}.delay-dialog-row{grid-template-columns:1fr}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important}.nav-group[open]>.nav-item{animation:none!important}}
.entry-confirm-overlay{position:fixed;inset:0;z-index:1500;display:grid;place-items:center;padding:20px;background:rgba(8,24,15,.58)}.entry-confirm-overlay[hidden]{display:none}.entry-confirm-card{width:min(100%,440px);padding:25px;border:1px solid #e5ebe7;border-radius:12px;background:#fff;box-shadow:0 20px 60px #07181040}.entry-confirm-card h2{margin:0 0 8px;color:#14251c;font-size:21px}.entry-confirm-card p{margin:0;color:#64736a;font-size:14px;line-height:1.5}.entry-confirm-error{margin-top:12px!important;color:#b42318!important}.entry-confirm-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:24px}.entry-confirm-actions button{min-height:42px;padding:0 16px;border:1px solid #dce5df;border-radius:8px;background:#fff;color:#24352b;font:600 14px 'Inter',sans-serif;cursor:pointer}.entry-confirm-actions button.primary{border-color:#008c45;background:#008c45;color:#fff}.entry-confirm-actions button:disabled{cursor:wait;opacity:.6}.entry-field-error{display:block;margin-top:5px;color:#b42318;font-size:12px}.entry-invalid{border-color:#dc2626!important;box-shadow:0 0 0 1px #dc2626!important}.entry-select-invalid{border-color:#dc2626!important}.entry-feedback{margin:0 0 14px;padding:11px 14px;border:1px solid #a6d7b7;border-radius:8px;background:#f0fbf3;color:#146c36;font-size:14px}.thermal-receipt{display:none}
@media print{@page{size:80mm auto;margin:0}html,body{width:80mm!important;min-width:80mm!important;margin:0!important;padding:0!important;background:#fff!important}body.printing-thermal-receipt>*:not(.thermal-receipt-active){display:none!important}body *{visibility:hidden!important}.thermal-receipt-active,.thermal-receipt-active *{visibility:visible!important}.thermal-receipt-active{position:absolute!important;top:0!important;left:0!important;display:block!important;width:72mm!important;margin:0!important;padding:3mm!important;background:#fff!important;color:#000!important;font:10pt/1.35 monospace!important;box-shadow:none!important}.thermal-receipt-active,.thermal-receipt-active *{font-family:monospace!important}.thermal-receipt-active h1,.thermal-receipt-active h2,.thermal-receipt-active p{margin:0!important;color:#000!important;font:inherit!important}.thermal-receipt-active h1{text-align:center!important;font-weight:bold!important}.thermal-receipt-active h2{margin-top:3mm!important;border-bottom:1px dashed #000!important;font-weight:bold!important}.thermal-receipt-active .thermal-center{text-align:center!important}.thermal-receipt-active .thermal-row{overflow-wrap:anywhere!important;margin-top:1mm!important}.thermal-receipt-active .thermal-rule{margin:3mm 0!important;border:0!important;border-top:1px dashed #000!important}.thermal-receipt-active .thermal-signature{margin-top:12mm!important;padding-top:2mm!important;border-top:1px solid #000!important;text-align:center!important}}

    * {
      font-family: 'Inter', sans-serif;
    }

    .menu-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .menu-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .menu-icon {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
      transition: all 0.3s ease;
    }

    .menu-card:hover .menu-icon {
      transform: scale(1.1);
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
      background: linear-gradient(70deg, #008C45,rgb(225, 130, 6));
    }
  

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
    

        * {
            font-family: 'Inter', sans-serif;
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

        .modal-form-shell {
            position: fixed;
            z-index: 50;
            top: 50%;
            left: calc(var(--sidebar-width, 0px) + (100vw - var(--sidebar-width, 0px)) / 2);
            transform: translate(-50%, -50%);
            width: min(calc(100vw - var(--sidebar-width, 0px) - 2rem), 1120px);
            min-height: 0;
            height: min(64dvh, 520px);
            max-height: calc(100dvh - 20px);
            margin: 0;
            display: flex;
            flex-direction: column;
            background: #fff;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-left: 5px solid var(--salaberga-green);
            border-radius: 22px;
            box-shadow: 0 24px 68px rgba(7, 24, 16, 0.16);
            overflow: hidden;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        body.sidebar-collapsed {
            --sidebar-width: 0px;
        }

        body .main-content {
            margin-left: var(--sidebar-width, 256px);
        }

        body.sidebar-collapsed .main-content {
            margin-left: 0;
        }

        .sidebar {
            transition: transform .22s cubic-bezier(.2, .75, .3, 1);
        }

        #consulta-saida, #consulta-estagio { width: 100%; max-width: none; margin: 0; }
        .saida-consulta-shell {
            display: flex;
            height: min(calc(100dvh - 112px), 920px);
            max-height: calc(100dvh - 112px);
            min-height: 0;
            flex-direction: column;
            padding: clamp(1rem, 2.5vw, 2rem);
            border: 1px solid #e1e9e3;
            border-left: 5px solid var(--salaberga-green);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 2px 8px #143b2114;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .page-section.active#consulta-saida .saida-consulta-shell,
        .page-section.active#consulta-estagio .saida-consulta-shell {
            animation: modal-shell-appear .42s cubic-bezier(.16, 1, .3, 1) both;
        }

        .saida-consulta-shell:hover {
            border-left-color: #3cb371;
            box-shadow: 0 12px 32px rgba(7, 24, 16, .12);
        }

        .saida-consulta-header {
            display: flex;
            align-items: center;
            gap: .9rem;
            margin-bottom: .5rem;
        }

        .saida-consulta-title-mark { display: grid; flex: 0 0 auto; width: 44px; height: 44px; place-items: center; border-radius: 12px; background: #e8f5ec; color: #08723a; font-size: 1.1rem; }
        .saida-consulta-title { display: flex; min-width: 0; flex: 1; align-items: center; gap: .8rem; }
        .saida-consulta-title h1 { margin: 0; color: #08723a; font-size: clamp(1.35rem, 2vw, 1.7rem); font-weight: 700; line-height: 1.2; }
        .saida-consulta-title p { margin: .3rem 0 0; color: #64736a; font-size: .86rem; line-height: 1.45; }

        .saida-consulta-count {
            display: inline-flex;
            flex: 0 0 auto;
            align-items: center;
            gap: .5rem;
            padding: .6rem .8rem;
            border: 1px solid #dcebe0;
            border-radius: 8px;
            background: #f3f8f4;
            color: #176b38;
            font-size: .8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .saida-consulta-filters {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) minmax(160px, 1fr) minmax(160px, 1fr) auto;
            align-items: end;
            gap: .85rem;
            margin-bottom: .25rem;
            padding: 1rem;
            border: 1px solid #e5ece7;
            border-radius: 10px;
            background: #f7faf7;
        }

        .saida-consulta-filter { display: grid; min-width: 0; gap: .4rem; color: #475c54; font-size: .76rem; font-weight: 700; }
        .saida-consulta-filter input,.saida-consulta-filter select { width: 100%; min-width: 0; height: 42px; padding: 0 .75rem; border: 1px solid #cbd5ce; border-radius: 7px; background: #fff; color: #26352c; font: 400 .84rem 'Inter',sans-serif; transition: border-color .18s ease, box-shadow .18s ease; }
        .saida-consulta-filter input::placeholder { color: #87948b; }
        .saida-consulta-filter input:focus,.saida-consulta-filter select:focus { outline: 0; border-color: #008c45; box-shadow: 0 0 0 3px #008c4518; }

        .saida-consulta-reset { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; min-height: 42px; padding: 0 .9rem; border: 1px solid #d5ded8; border-radius: 7px; background: #fff; color: #43534a; font: 600 .8rem 'Inter',sans-serif; cursor: pointer; transition: background .18s ease, border-color .18s ease, color .18s ease; }
        .saida-consulta-reset:hover { border-color: #b9d7c3; background: #edf6f0; color: #176b38; }
        .saida-consulta-reset:focus-visible { outline: 2px solid #008c45; outline-offset: 2px; }

        .saida-consulta-results { display: flex; min-height: 0; flex: 1 1 auto; flex-direction: column; }
        .saida-consulta-table-wrap { min-height: 0; flex: 1 1 auto; overflow: auto; border: 1px solid #e2e9e4; border-radius: 9px; }
        .saida-consulta-table-wrap[hidden],.saida-consulta-empty[hidden],.saida-consulta-pagination[hidden] { display: none; }
        .saida-consulta-table { width: 100%; min-width: 980px; border-collapse: separate; border-spacing: 0; }
        .saida-consulta-table th,.saida-consulta-table td { padding: 2px 12px; border-bottom: 1px solid #edf1ee; text-align: left; vertical-align: top; font-size: .8rem; }
        .saida-consulta-table th { background: #f4f8f5; color: #53615a; font-size: .7rem; font-weight: 700; letter-spacing: .02em; white-space: nowrap; }
        .saida-consulta-table tbody tr:last-child td { border-bottom: 0; }
        .saida-consulta-table tbody tr:nth-child(even) { background: #fbfcfb; }
        .saida-consulta-table tbody tr:hover { background: #f1f7f2; }
        .saida-consulta-table tbody tr[hidden] { display: none; }
        .saida-consulta-primary { display: block; color: #26352c; font-weight: 600; }
        .saida-consulta-secondary { display: block; margin-top: .2rem; color: #758078; font-size: .72rem; }
        .saida-consulta-empty { display: flex; min-height: 120px; flex: 1 1 auto; flex-direction: column; align-items: center; justify-content: center; gap: .65rem; margin: 0; padding: 1rem; border: 1px dashed #dce6df; border-radius: 9px; background: #fbfdfb; color: #64736a; text-align: center; font-size: .9rem; }
        .saida-consulta-empty i { color: #54a874; font-size: 1.5rem; }
        .saida-consulta-pagination { display: flex; flex: 0 0 auto; align-items: center; justify-content: center; gap: .75rem; padding-top: .25rem; color: #64736a; font-size: .78rem; }
        .saida-consulta-page-button { display: inline-grid; width: 34px; height: 34px; place-items: center; border: 1px solid #d5ded8; border-radius: 7px; background: #fff; color: #176b38; cursor: pointer; transition: background .18s ease, border-color .18s ease; }
        .saida-consulta-page-button:hover:not(:disabled) { border-color: #b9d7c3; background: #edf6f0; }
        .saida-consulta-page-button:focus-visible { outline: 2px solid #008c45; outline-offset: 2px; }
        .saida-consulta-page-button:disabled { color: #aab5ad; cursor: not-allowed; }

        @media (max-width: 1050px) {
            .saida-consulta-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 760px) {
            .saida-consulta-shell { height: min(calc(100dvh - 112px), 920px); max-height: calc(100dvh - 112px); padding: 1rem; }
            .saida-consulta-header { display: grid; grid-template-columns: minmax(0, 1fr); gap: .75rem; margin-bottom: 1rem; }
            .saida-consulta-title { gap: .6rem; }
            .saida-consulta-title > div { min-width: 0; }
            .saida-consulta-title-mark { width: 38px; height: 38px; border-radius: 10px; font-size: 1rem; }
            .saida-consulta-title h1 { font-size: 1.25rem; }
            .saida-consulta-title p { font-size: .78rem; }
            .saida-consulta-count { grid-column: 1; grid-row: 2; justify-self: start; margin-left: 0; padding: .5rem .65rem; }
            .saida-consulta-filters { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .6rem; padding: .8rem; }
            .saida-consulta-filter:first-child,.saida-consulta-reset { grid-column: 1 / -1; }
            .saida-consulta-reset { width: 100%; }
            .saida-consulta-table-wrap { overflow: auto; border: 0; }
            .saida-consulta-table { display: block; min-width: 0; }
            .saida-consulta-table thead { display: none; }
            .saida-consulta-table tbody { display: grid; gap: .75rem; }
            .saida-consulta-table tbody tr { display: block; padding: .5rem .75rem; border: 1px solid #e2e9e4; border-radius: 8px; background: #fff; box-shadow: 0 2px 6px #143b210a; }
            .saida-consulta-table tbody tr:hover { background: #fff; }
            .saida-consulta-table tbody td { display: grid; grid-template-columns: minmax(105px, .7fr) minmax(0, 1.3fr); gap: .65rem; padding: .6rem 0; border-bottom: 1px solid #edf1ee; overflow-wrap: anywhere; }
            .saida-consulta-table tbody td:last-child { border-bottom: 0; }
            .saida-consulta-table tbody td::before { color: #64736a; content: attr(data-label); font-size: .72rem; font-weight: 700; }
        }

        .brand-mark img {
            display: block;
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        @media (min-width: 761px) {
            body.sidebar-collapsed .sidebar {
                transform: translateX(-100%);
            }
        }

        .sidebar-collapse-toggle {
            position: fixed;
            top: 50%;
            left: calc(var(--sidebar-width, 256px) - 1px);
            z-index: 1001;
            display: grid;
            width: 30px;
            height: 72px;
            place-items: center;
            padding: 0;
            border: 1px solid #ffffff55;
            border-left: 0;
            border-radius: 0 10px 10px 0;
            background: linear-gradient(145deg, #0b7042, #064c2b);
            color: #ffd06a;
            box-shadow: 4px 0 12px #052d1b40, inset -1px 0 #ffffff55;
            transform: translateY(-50%);
            cursor: pointer;
            transition: left .22s ease, width .18s ease, background .18s ease, box-shadow .18s ease;
        }

        .sidebar-collapse-toggle::before {
            position: absolute;
            top: 8px;
            bottom: 8px;
            left: 3px;
            width: 1px;
            background: #ffffff4a;
            content: '';
        }

        .sidebar-collapse-toggle:hover {
            width: 35px;
            background: linear-gradient(145deg, #0d7a49, #075b34);
            box-shadow: 5px 0 15px #052d1b55, inset -1px 0 #ffffff70;
            transform: translateY(-50%);
        }

        .sidebar-collapse-toggle:focus-visible {
            outline: 2px solid var(--salaberga-amber);
            outline-offset: 2px;
        }

        .sidebar-collapse-toggle i {
            transition: transform .22s ease;
        }

        .sidebar-collapse-toggle:hover i {
            transform: rotate(180deg);
        }

        body.sidebar-collapsed .sidebar-collapse-toggle {
            left: 0;
        }

        body.sidebar-collapsed .sidebar-collapse-toggle i {
            transform: rotate(180deg);
        }

        body.sidebar-collapsed .sidebar-collapse-toggle:hover i {
            transform: rotate(0deg);
        }

        select.form-select:not([multiple]) {
            -webkit-appearance: none;
            appearance: none;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='m6 8 4 4 4-4' stroke='%23008c45' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.8'/%3E%3C/svg%3E");
            background-position: right .75rem center;
            background-repeat: no-repeat;
            background-size: 14px 14px;
            padding-right: 2.4rem;
        }

        .modal-form-shell:hover {
            border-left-color: #3cb371;
            box-shadow: 0 28px 76px rgba(0, 140, 69, 0.22);
        }

        .app-modal-card {
            border-left: 5px solid var(--salaberga-green);
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .app-modal-card:hover {
            border-left-color: #3cb371;
            box-shadow: 0 28px 76px rgba(0, 140, 69, 0.22);
        }

        .success-toast {
            position: fixed;
            z-index: 1400;
            top: 1rem;
            left: calc(var(--sidebar-width, 0px) + (100vw - var(--sidebar-width, 0px)) / 2);
            width: min(28rem, calc(100vw - var(--sidebar-width, 0px) - 2rem));
            overflow: hidden;
            border: 1px solid rgba(0, 140, 69, .18);
            border-radius: 12px;
            background: #fff;
            color: #203629;
            box-shadow: 0 12px 32px rgba(7, 24, 16, .18);
            opacity: 0;
            transform: translate(-50%, -12px);
            transition: opacity .22s ease, transform .22s ease;
            pointer-events: none;
        }

        .success-toast[hidden] {
            display: none;
        }

        .success-toast.visible {
            opacity: 1;
            transform: translate(-50%, 0);
        }

        .success-toast-content {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .9rem 1rem;
            font-size: .9rem;
            font-weight: 600;
        }

        .success-toast-icon {
            display: grid;
            width: 1.75rem;
            height: 1.75rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 50%;
            background: rgba(0, 140, 69, .1);
            color: var(--salaberga-green);
        }

        .success-toast-progress {
            height: 3px;
            width: 100%;
            background: linear-gradient(90deg, #008c45, #3cb371);
            transform-origin: left;
        }

        .success-toast.error {
            border-color: rgba(220, 53, 69, .2);
        }

        .success-toast.error .success-toast-icon {
            background: rgba(220, 53, 69, .1);
            color: #dc3545;
        }

        .success-toast.error .success-toast-progress {
            background: linear-gradient(90deg, #dc3545, #ef777f);
        }

        .success-toast.visible .success-toast-progress {
            animation: success-toast-countdown 4s linear forwards;
        }

        @keyframes success-toast-countdown {
            to { transform: scaleX(0); }
        }

        .stage-exit-confirmation {
            position: fixed;
            inset: 0;
            z-index: 1500;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            background: linear-gradient(135deg, #008c45 0%, #00b357 100%);
            opacity: 0;
            pointer-events: none;
        }

        .stage-exit-confirmation[hidden] {
            display: none;
        }

        .stage-exit-confirmation::before {
            position: absolute;
            inset: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, .1) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, .1) 50%, rgba(255, 255, 255, .1) 75%, transparent 75%, transparent);
            background-size: 100px 100px;
            content: '';
            opacity: .3;
            animation: stage-success-background 30s linear infinite;
        }

        .stage-exit-confirmation.visible {
            opacity: 1;
        }

        @keyframes stage-success-background {
            to { background-position: 100px 100px; }
        }

        .stage-exit-confirmation-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 600px;
        }

        .stage-exit-confirmation-card {
            position: relative;
            padding: 3rem 2rem;
            overflow: hidden;
            border-radius: 20px;
            background: #fff;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .2);
        }

        .stage-exit-confirmation-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 90px;
            height: 90px;
            margin: 0 auto 2rem;
            border-radius: 50%;
            background: #008c45;
            animation: stage-success-pulse 2s infinite;
        }

        .stage-exit-confirmation-icon i {
            color: #fff;
            font-size: 40px;
        }

        .stage-exit-confirmation-title {
            margin: 0;
            color: #333;
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: .5px;
        }

        .stage-exit-confirmation-name {
            display: block;
            margin: 0 0 1rem;
            color: #008c45;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .stage-exit-confirmation-details {
            margin: 0 0 2rem;
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
        }

        .stage-exit-confirmation.is-error .stage-exit-confirmation-icon {
            background: #c62828;
            animation: none;
        }

        .stage-exit-confirmation.is-error {
            background: linear-gradient(135deg, #a31515 0%, #e53935 100%);
        }

        .stage-exit-confirmation.is-error .stage-exit-confirmation-title,
        .stage-exit-confirmation.is-error .stage-exit-confirmation-name,
        .stage-exit-confirmation.is-error .stage-exit-confirmation-details {
            color: #b42318;
        }

        .stage-exit-confirmation.is-error .stage-exit-confirmation-loading {
            display: none;
        }

        .stage-exit-confirmation.is-pending {
            background: linear-gradient(135deg, #f9a825 0%, #ffd54f 100%);
        }

        .stage-exit-confirmation.is-pending .stage-exit-confirmation-icon {
            background: #f9a825;
            animation: none;
        }

        .stage-exit-confirmation.is-pending .stage-exit-confirmation-title,
        .stage-exit-confirmation.is-pending .stage-exit-confirmation-name,
        .stage-exit-confirmation.is-pending .stage-exit-confirmation-details {
            color: #805600;
        }

        .stage-exit-confirmation-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            color: #ffa500;
            font-size: 1rem;
            font-weight: 500;
        }

        .stage-exit-confirmation-dots {
            display: flex;
            gap: 5px;
        }

        .stage-exit-confirmation-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #ffa500;
            animation: stage-success-dot 1.4s infinite;
        }

        .stage-exit-confirmation-dots span:nth-child(2) {
            animation-delay: .2s;
        }

        .stage-exit-confirmation-dots span:nth-child(3) {
            animation-delay: .4s;
        }

        @keyframes stage-success-pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 140, 69, .4); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(0, 140, 69, 0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(0, 140, 69, 0); }
        }

        @keyframes stage-success-dot {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        @media (max-width: 480px) {
            .stage-exit-confirmation-card {
                padding: 2rem 1.5rem;
            }

            .stage-exit-confirmation-title {
                font-size: 1.8rem;
            }

            .stage-exit-confirmation-icon {
                width: 70px;
                height: 70px;
            }

            .stage-exit-confirmation-icon i {
                font-size: 30px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .stage-exit-confirmation::before,
            .stage-exit-confirmation-icon,
            .stage-exit-confirmation-dots span {
                animation: none;
            }
        }

        .edit-record-dialog {
            position: fixed;
            top: 50%;
            right: auto;
            bottom: auto;
            left: calc(var(--sidebar-width, 0px) + (100vw - var(--sidebar-width, 0px)) / 2);
            width: min(560px, calc(100vw - 2rem));
            max-width: calc(100vw - 2rem);
            max-height: calc(100dvh - 2rem);
            margin: 0;
            padding: 0;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .1);
            border-left: 5px solid var(--salaberga-green);
            border-radius: 18px;
            background: #fff;
            color: var(--salaberga-ink);
            box-shadow: 0 24px 68px rgba(7, 24, 16, .22);
            transform: translate(-50%, -50%);
        }

        .edit-record-dialog[open] {
            animation: modal-shell-appear .25s ease-out both;
        }

        .edit-record-dialog::backdrop {
            background: rgba(10, 20, 17, .5);
            animation: modal-overlay-appear .2s ease-out both;
        }

        .early-exit-heading {
            display: flex;
            align-items: center;
            gap: .8rem;
            margin-bottom: 1rem;
        }

        .early-exit-heading-icon {
            display: grid;
            width: 2.7rem;
            height: 2.7rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 50%;
            background: #fff4df;
            color: #a35b00;
        }

        .early-exit-heading h2 {
            margin: 0;
            color: #203128;
            font-size: 1.12rem;
            font-weight: 750;
        }

        .early-exit-heading p {
            margin: .2rem 0 0;
            color: #647067;
            font-size: .85rem;
        }

        .early-exit-info {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .65rem;
            margin-bottom: 1rem;
        }

        .early-exit-info div {
            min-width: 0;
            padding: .65rem .75rem;
            border: 1px solid #e5ece7;
            border-radius: 10px;
            background: #f8fbf9;
        }

        .early-exit-info dt {
            color: #68766d;
            font-size: .72rem;
            font-weight: 600;
        }

        .early-exit-info dd {
            margin: .15rem 0 0;
            overflow-wrap: anywhere;
            color: #203128;
            font-size: .9rem;
            font-weight: 700;
        }

        .early-exit-error {
            min-height: 1.2rem;
            margin: .35rem 0 0;
            color: #b42318;
            font-size: .8rem;
        }

        .early-exit-actions {
            display: flex;
            justify-content: flex-end;
            gap: .65rem;
            margin-top: 1rem;
        }

        .early-exit-actions button {
            min-height: 42px;
            padding: .6rem 1rem;
            border: 0;
            border-radius: 9px;
            font-weight: 700;
        }

        .early-exit-cancel {
            background: #eef2ef;
            color: #35483b;
        }

        .early-exit-confirm {
            background: var(--salaberga-green);
            color: #fff;
        }

        .exit-record-clickable {
            cursor: pointer;
        }

        .exit-record-clickable:hover {
            background-color: rgba(0, 140, 69, .06);
        }

        .exit-record-clickable:focus-visible {
            outline: 3px solid rgba(0, 140, 69, .55);
            outline-offset: 2px;
        }

        .delete-exit-dialog {
            position: fixed;
            top: 50%;
            left: calc(var(--sidebar-width, 0px) + (100vw - var(--sidebar-width, 0px)) / 2);
            width: min(540px, calc(100vw - 2rem));
            max-width: calc(100vw - 2rem);
            max-height: calc(100dvh - 2rem);
            margin: 0;
            padding: 1.4rem;
            overflow: auto;
            border: 0;
            border-top: 5px solid #b42318;
            border-radius: 18px;
            background: #fff;
            color: var(--salaberga-ink);
            box-shadow: 0 24px 68px rgba(7, 24, 16, .22);
            transform: translate(-50%, -50%);
        }

        .delete-exit-dialog::backdrop {
            background: rgba(10, 20, 17, .58);
        }

        .delete-exit-confirm {
            background: #b42318;
            color: #fff;
        }

        .edit-record-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-bottom: 1px solid rgba(15, 23, 42, .08);
        }

        .edit-record-title {
            display: flex;
            align-items: center;
            gap: .75rem;
        }

        .edit-record-title-icon {
            display: grid;
            width: 2.5rem;
            height: 2.5rem;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 10px;
            background: rgba(0, 140, 69, .1);
            color: var(--salaberga-green);
        }

        .edit-record-title h2 {
            margin: 0;
            color: #1d2c24;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .edit-record-title p {
            margin: .2rem 0 0;
            color: #64736a;
            font-size: .76rem;
        }

        .edit-record-close {
            display: grid;
            width: 2rem;
            height: 2rem;
            place-items: center;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 8px;
            background: #f4f7f5;
            color: #495b55;
            font-size: 1.2rem;
            cursor: pointer;
        }

        .edit-record-form {
            display: flex;
            max-height: calc(100dvh - 7rem);
            flex-direction: column;
        }

        .edit-record-fields {
            display: grid;
            gap: .85rem;
            overflow-y: auto;
            padding: 1rem 1.2rem;
        }

        .edit-record-fields[hidden] {
            display: none;
        }

        .edit-record-field {
            display: grid;
            min-width: 0;
            gap: .35rem;
            color: #43534a;
            font-size: .78rem;
            font-weight: 650;
        }

        .edit-record-field input,
        .edit-record-field select {
            width: 100%;
            min-height: 2.65rem;
            padding: .55rem .75rem;
            border: 1px solid #cbd5ce;
            border-radius: 9px;
            background-color: #fff;
            color: #25372e;
            font: 400 .84rem 'Inter', sans-serif;
        }

        .edit-record-field input:focus,
        .edit-record-field select:focus {
            border-color: var(--salaberga-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, .1);
        }

        .edit-record-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .85rem;
        }

        .edit-record-footer {
            display: flex;
            justify-content: flex-end;
            gap: .65rem;
            padding: .85rem 1.2rem;
            border-top: 1px solid rgba(15, 23, 42, .08);
            background: #fff;
        }

        .edit-record-button {
            display: inline-flex;
            min-height: 2.55rem;
            align-items: center;
            justify-content: center;
            gap: .45rem;
            padding: .55rem .9rem;
            border: 1px solid #d5ded8;
            border-radius: 9px;
            background: #f5f8f6;
            color: #43534a;
            font: 650 .8rem 'Inter', sans-serif;
            cursor: pointer;
        }

        .edit-record-button.primary {
            border-color: transparent;
            background: linear-gradient(90deg, #008c45, #3cb371);
            color: #fff;
            box-shadow: 0 8px 16px rgba(0, 140, 69, .18);
        }

        .edit-record-dialog .select2-container {
            width: 100% !important;
        }

        .edit-record-dialog .select2-container--default .select2-selection--single {
            min-height: 2.65rem;
            padding: .35rem .65rem !important;
            border: 1px solid #cbd5ce !important;
            border-radius: 9px !important;
            font-size: .84rem !important;
        }

        .edit-record-dialog .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.7rem !important;
        }

        .edit-record-dialog .select2-dropdown {
            z-index: 1600;
        }

        @media (max-width: 600px) {
            .edit-record-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .success-toast {
                top: 3.75rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .success-toast,
            .success-toast.visible {
                transition: none;
            }
        }

        .delay-dialog[open] {
            border-left: 5px solid var(--salaberga-green);
            animation: modal-shell-appear .42s cubic-bezier(.16,1,.3,1) both;
            transition: border-color .2s ease, box-shadow .2s ease;
        }

        .page-section#atrasos .delay-dialog[open] {
            border-left: 5px solid var(--salaberga-green);
        }

        .delay-dialog[open]:hover {
            border-left-color: #3cb371;
            box-shadow: 0 28px 76px rgba(0, 140, 69, 0.22);
        }

        .delay-dialog::backdrop {
            animation: modal-overlay-appear .3s ease-out both;
        }

        .app-modal-overlay:not(.hidden) {
            animation: modal-overlay-appear .3s ease-out both;
        }

        @keyframes modal-shell-appear {
            from { opacity: 0; filter: blur(2px); translate: 0 10px; }
            to { opacity: 1; filter: blur(0); translate: 0 0; }
        }

        @keyframes modal-overlay-appear {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .main-content:has(> .page-section.active .modal-form-shell) {
            height: 100dvh;
            min-height: 100dvh;
            margin-top: 0;
            padding-top: 0;
            padding-bottom: 0;
            overflow: hidden;
        }

        .page-section#atrasos .delays-shell {
            min-height: 0;
            max-width: 1800px;
            margin: 0 auto;
            padding: 1rem;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
        }

        @media (min-width: 1101px) {
            #atrasos .delays-shell {
                width: calc(100% + min(16px, 1.5vw) - 40px);
                max-width: none;
                margin-right: calc(-1 * min(16px, 1.5vw));
                margin-left: 12px;
            }
        }

        .modal-form-shell.modal-form-shell-sm {
            width: min(calc(100vw - var(--sidebar-width, 0px) - 2rem), 560px);
            height: min(52dvh, 460px);
        }

        .modal-form-shell-sm .modal-form-scroll {
            padding-top: .45rem;
        }

        .modal-form-shell-sm .modal-form-body {
            gap: .3rem;
        }

        #cadastro .modal-form-body {
            gap: .45rem;
        }

        #cadastro .modal-form-shell-sm {
            height: auto;
            max-height: calc(100dvh - 2rem);
        }

        #cadastro .modal-form-form,
        #cadastro .modal-form-scroll {
            flex: 0 1 auto;
        }

        #cadastro .modal-form-scroll {
            overflow-y: auto;
        }

        #cadastro .modal-form-body {
            min-height: 0;
        }

        #estagio .modal-form-shell-sm {
            height: auto;
            max-height: calc(100dvh - 2rem);
        }

        #estagio .modal-form-form,
        #estagio .modal-form-scroll {
            flex: 0 1 auto;
        }

        #estagio .modal-form-scroll {
            overflow-y: auto;
        }

        #estagio .modal-form-body {
            min-height: 0;
        }

        .page-section.active#entrada,
        .page-section.active#saida,
        .page-section.active#estagio,
        .page-section.active#cadastro {
            min-height: 0;
            display: block;
        }

        .modal-form-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex: 0 0 auto;
            min-height: 64px;
            padding: .7rem 1.15rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
        }

        .modal-form-title {
            display: flex;
            align-items: center;
            gap: .7rem;
            min-width: 0;
        }

        .modal-form-icon {
            display: grid;
            place-items: center;
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 9px;
            background: rgba(0, 140, 69, 0.1);
            color: var(--salaberga-green);
            font-size: 1rem;
            flex-shrink: 0;
        }

        .modal-form-header h2 {
            margin: 0;
            color: #1d2c24;
            font-size: 1.2rem;
            line-height: 1.2;
            font-weight: 700;
        }

        .modal-form-header p {
            margin: .2rem 0 0;
            color: #64736a;
            font-size: .76rem;
        }

        .modal-form-close {
            display: grid;
            place-items: center;
            width: 2rem;
            height: 2rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 10px;
            background: #f4f7f5;
            color: #495b55;
            font-size: 1.3rem;
            cursor: pointer;
            transition: background .2s ease;
        }

        .modal-form-close:hover {
            background: #edf6f0;
        }

        .modal-form-scroll {
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            padding: .7rem 1.15rem 0;
        }

        .modal-form-body {
            display: flex;
            flex-direction: column;
            gap: .55rem;
            min-height: 100%;
        }

        .modal-form-form {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            flex-direction: column;
        }

        .modal-form-body-registration {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            grid-template-areas:
                "student entry"
                "observation observation";
            grid-auto-rows: max-content;
            align-content: start;
            align-items: stretch;
            gap: .55rem;
        }

        .modal-form-body-registration > [role="status"] {
            grid-column: 1 / -1;
            margin: 0;
            padding: .35rem .6rem;
            font-size: .7rem;
            line-height: 1.3;
        }

        .modal-form-section {
            min-width: 0;
            height: auto;
            min-height: 0;
            padding: .5rem .65rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            background: #f7faf7;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.4);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }

        .modal-form-section h3 {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin: 0 0 .35rem;
            color: #1d2c24;
            font-size: .86rem;
            font-weight: 700;
        }

        .modal-form-section h3 i {
            color: var(--salaberga-green);
        }

        .modal-form-section .grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .35rem;
        }

        .modal-form-section .grid.mb-6 {
            margin-bottom: .35rem;
        }

        .modal-form-section label,
        .modal-form-shell .form-group > label {
            margin-bottom: .25rem;
            font-size: .7rem;
            line-height: 1.25;
        }

        .modal-form-shell .form-group {
            margin: 0;
        }

        .modal-form-shell .search-input {
            width: 100%;
            max-width: none;
            color: #26352d;
            background: #fff;
            border-color: #d1d5db;
        }

        .modal-form-shell .search-input::placeholder {
            color: #9ca3af;
            opacity: 1;
        }

        .modal-form-shell .search-input:focus {
            background: #fff;
        }

        .modal-form-shell textarea {
            min-height: 64px;
            resize: vertical;
        }

        .modal-form-body-registration input:not([type="hidden"]),
        .modal-form-body-registration select,
        .modal-form-body-registration .select-trigger,
        .modal-form-body-registration .entry-fixed-display {
            min-height: 2rem;
            padding-top: .3rem;
            padding-bottom: .3rem;
        }

        .registration-form-scroll {
            overflow-y: auto;
        }

        .exit-registration-body {
            grid-template-areas:
                "student responsible"
                "observation exit"
                ". companion";
        }

        .entry-registration-shell,
        .exit-registration-shell {
            height: auto;
            max-height: calc(100dvh - 2rem);
        }

        .entry-registration-shell .modal-form-form,
        .entry-registration-shell .modal-form-scroll,
        .exit-registration-shell .modal-form-form,
        .exit-registration-shell .registration-form-scroll {
            flex: 0 1 auto;
            min-height: 0;
        }

        .modal-form-shell input:not([type="hidden"]),
        .modal-form-shell select,
        .modal-form-shell .select-trigger,
        .modal-form-shell .entry-fixed-display {
            min-height: 2.35rem;
            padding-top: .4rem;
            padding-bottom: .4rem;
            font-size: .76rem;
        }

        .modal-form-shell .custom-select-container {
            min-height: 2.25rem;
        }

        .modal-form-shell .space-y-6 > :not([hidden]) ~ :not([hidden]),
        .modal-form-shell .space-y-5 > :not([hidden]) ~ :not([hidden]),
        .modal-form-shell .space-y-4 > :not([hidden]) ~ :not([hidden]),
        .modal-form-shell .space-y-3 > :not([hidden]) ~ :not([hidden]) {
            margin-top: 0;
        }

        .modal-form-footer {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: .75rem;
            flex: 0 0 auto;
            padding: .75rem 1.15rem .8rem;
            margin-top: .25rem;
            border-top: 1px solid rgba(15, 23, 42, 0.08);
            background: #fff;
        }

        .modal-form-btn {
            min-height: 2.35rem;
            padding: .55rem .9rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            background: #fff;
            color: #1d2c24;
            font: 600 .78rem 'Inter', sans-serif;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .modal-form-btn:hover {
            transform: translateY(-1px);
            border-color: rgba(0, 140, 69, 0.28);
        }

        .modal-form-btn.primary {
            background: linear-gradient(90deg, #008c45, #3cb371);
            border-color: transparent;
            color: #fff;
            box-shadow: 0 10px 16px rgba(0, 140, 69, 0.18);
        }

        .modal-form-btn.primary:hover {
            box-shadow: 0 12px 20px rgba(0, 140, 69, 0.22);
        }

        .modal-form-btn.ghost {
            background: #f5f8f6;
        }

        .modal-form-shell .form-group,
        .modal-form-shell .input-group,
        .modal-form-shell .space-y-3,
        .modal-form-shell .space-y-4,
        .modal-form-shell .space-y-6 {
            margin: 0;
        }

        .modal-form-shell .w-full,
        .modal-form-shell select,
        .modal-form-shell input,
        .modal-form-shell textarea {
            max-width: 100%;
        }

        .cadastro-field {
            display: flex;
            flex-direction: column;
            gap: .25rem;
            min-width: 0;
        }

        .cadastro-field label {
            display: flex;
            align-items: center;
            gap: .45rem;
            margin: 0;
            color: #34463d;
            font-size: .76rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .cadastro-field label i {
            width: 1rem;
            color: var(--salaberga-green);
            text-align: center;
        }

        .cadastro-field input,
        .cadastro-field select {
            width: 100%;
            height: 2.3rem;
            min-height: 2.3rem;
            padding: .4rem .7rem;
            border: 1px solid #cbd5ce;
            border-radius: 9px;
            background-color: #fff;
            color: #25372e;
            font: 400 .78rem 'Inter', sans-serif;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .cadastro-field input:focus,
        .cadastro-field select:focus {
            border-color: var(--salaberga-green);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, .1);
        }

        .page-section.active .modal-form-shell {
            animation: modal-shell-appear .42s cubic-bezier(.16,1,.3,1) both;
        }

        .page-section.active .modal-form-shell.pulse-success {
            animation: modal-shell-success-pulse .6s ease-in-out;
        }

        .page-section.active .modal-form-shell.shake {
            animation: modal-shell-validation-shake .5s ease-in-out;
        }

        @keyframes modal-shell-success-pulse {
            0%, 100% { box-shadow: 0 24px 68px rgba(7, 24, 16, .16); }
            50% { box-shadow: 0 28px 76px rgba(0, 140, 69, .26); }
        }

        @keyframes modal-shell-validation-shake {
            0%, 100% { translate: 0 0; }
            25% { translate: -5px 0; }
            75% { translate: 5px 0; }
        }

        @media (max-width: 760px) {
            .sidebar-collapse-toggle {
                display: none;
            }

            .page-section.active#entrada,
            .page-section.active#saida,
            .page-section.active#estagio,
            .page-section.active#cadastro {
                min-height: 0;
            }

            .modal-form-shell {
                width: calc(100vw - 24px);
                height: min(72dvh, 560px);
                left: 50vw;
                border-radius: 14px;
            }

            .modal-form-shell.modal-form-shell-sm {
                height: min(56dvh, 460px);
            }

            #cadastro .modal-form-shell-sm {
                height: auto;
                max-height: calc(100dvh - 1.5rem);
            }

            .modal-form-header,
            .modal-form-footer {
                padding-right: .75rem;
                padding-left: .75rem;
            }

            .modal-form-scroll {
                padding: .55rem .75rem 0;
            }

            .modal-form-body-registration {
                gap: .4rem;
            }

            .modal-form-section {
                padding: .55rem;
            }
        }

        @media (max-width: 600px) {
            .modal-form-body-registration {
                grid-template-columns: 1fr;
                grid-template-areas:
                    "student"
                    "entry"
                    "observation";
                grid-auto-rows: auto;
            }

            .exit-registration-body {
                grid-template-areas:
                    "student"
                    "responsible"
                    "observation"
                    "exit"
                    "companion";
            }

            .modal-form-section {
                height: auto;
            }
        }

        @media (max-height: 650px) {
            .modal-form-shell {
                height: min(64dvh, 520px);
            }

            .modal-form-header {
                min-height: 54px;
                padding-top: .4rem;
                padding-bottom: .4rem;
            }

            .modal-form-footer {
                padding-top: .4rem;
                padding-bottom: .45rem;
            }

            .modal-form-section {
                padding-top: .4rem;
                padding-bottom: .4rem;
            }

            .modal-form-body-registration {
                gap: .2rem;
            }

            .modal-form-section {
                padding-top: .25rem;
                padding-bottom: .25rem;
            }

            .modal-form-section h3 {
                margin-bottom: .2rem;
            }

            .modal-form-shell input:not([type="hidden"]),
            .modal-form-shell select,
            .modal-form-shell .select-trigger,
            .modal-form-shell .entry-fixed-display {
                min-height: 1.95rem;
                padding-top: .25rem;
                padding-bottom: .25rem;
            }

            .modal-form-section label,
            .modal-form-shell .form-group > label {
                margin-bottom: .1rem;
            }

            .modal-form-section .grid {
                gap: .3rem;
            }
        }

        .slide-in {
            animation: slideIn 0.5s ease-out;
        }

        #estagio .space-y-2 > :not([hidden]) ~ :not([hidden]) {
            margin-top: .75rem;
        }

        #estagio .modal-form-body > .space-y-3 > .relative,
        #estagio .modal-form-body > .space-y-3 + .space-y-4 {
            margin-top: .75rem;
        }

        @media (prefers-reduced-motion: reduce) {
            .page-section.active .modal-form-shell,
            .page-section.active#consulta-saida .saida-consulta-shell,
            .app-modal-overlay:not(.hidden),
            .app-modal-overlay:not(.hidden) .app-modal-card,
            .delay-dialog[open],
            .delay-dialog::backdrop {
                animation-duration: .01ms !important;
            }
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
            animation: modalEnter .42s cubic-bezier(.16, 1, .3, 1) forwards;
        }

        .modal-exit {
            animation: modalExit .18s ease-in forwards;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(.97) translateY(10px);
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
                transform: scale(.98) translateY(6px);
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
    

        * {
            font-family: 'Inter', sans-serif;
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
            cursor: pointer;
            text-decoration: none;
        }

        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .report-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .report-card:hover .report-icon {
            transform: scale(1.1);
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
    

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
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
            appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23374151' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 16px;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #008C45;
            box-shadow: 0 0 0 3px rgba(0, 140, 69, 0.1);
        }

        .form-select:hover {
            border-color: #d1d5db;
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
            padding: 8px 0;
            transition: all 0.3s ease;
        }

        .radio-item:hover {
            background: #f9fafb;
            border-radius: 6px;
            padding: 8px 12px;
            margin: 0 -12px;
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
            flex-shrink: 0;
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
            font-weight: 400;
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

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .info-card {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px;
            display: flex;
            gap: 12px;
        }

        .info-icon {
            color: #2563eb;
            flex-shrink: 0;
        }

        .info-content h3 {
            font-weight: 500;
            color: #1e40af;
            margin-bottom: 4px;
            font-size: 0.875rem;
        }

        .info-content p {
            color: #1e40af;
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
    

        * {
            font-family: 'Inter', sans-serif;
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
    

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
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
    

    * {
      font-family: 'Inter', sans-serif;
    }

    .form-card {
      background: white;
      border-radius: 16px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }

    .form-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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

    input[type="date"] {
      border: 1px solid #d1d5db;
      border-radius: 8px;
      padding: 8px 12px;
      width: 100%;
      max-width: 200px;
      transition: all 0.3s ease;
    }

    input[type="date"]:focus {
      outline: none;
      border-color: #3CB371;
      box-shadow: 0 0 0 3px rgba(60, 179, 113, 0.2);
    }

    button {
      background: linear-gradient(45deg, #008C45, #3CB371);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 10px 20px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    button:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

        * {
            font-family: 'Inter', sans-serif;
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
    

        * {
            font-family: 'Inter', sans-serif;
        }

        .main-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            max-width: 1800px !important;
            padding: 1rem !important;
        }

        /* Estilos para Cards (Mobile) */
        .class-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 350px;
            max-height: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin: 0;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .student-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }

        .student-card:hover {
            background: #f3f4f6;
        }

        /* Estilos para Tabelas (Desktop) */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e5e7eb;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .table-header {
            background: linear-gradient(90deg, #008C45, #3CB371);
            color: white;
            padding: 12px 16px;
        }

        .table-content {
            flex: 1;
            overflow-y: auto;
            max-height: 600px;
        }

        .table-row {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.2s ease;
        }

        .table-row:hover {
            background-color: #f9fafb;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        /* Classes específicas para cada turma */
        .turma-3a .table-header {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        .turma-3b .table-header {
            background: linear-gradient(90deg, #4169E1, #3651d1);
        }

        .turma-3c .table-header {
            background: linear-gradient(90deg, #0dcaf0, #0bb5d6);
        }

        .turma-3d .table-header {
            background: linear-gradient(90deg, #6c757d, #5a6268);
        }

        .card-header-3a {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }

        .card-header-3b {
            background: linear-gradient(90deg, #4169E1, #3651d1);
        }

        .card-header-3c {
            background: linear-gradient(90deg, #0dcaf0, #0bb5d6);
        }

        .card-header-3d {
            background: linear-gradient(90deg, #6c757d, #5a6268);
        }

        .gradient-text {
            background: linear-gradient(45deg, #008C45, #3CB371);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Responsividade */
        .desktop-view {
            display: none !important;
        }

        .mobile-view {
            display: block !important;
        }

        /* Layout Grid */
        .mobile-view .space-y-6 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1rem;
            max-width: 1800px;
            margin: 0 auto;
        }

        @media (max-width: 1400px) {
            .mobile-view .space-y-6 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .mobile-view .space-y-6 {
                grid-template-columns: 1fr;
                padding: 0.5rem;
            }

            .student-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 1rem;
                padding: 1rem;
                border: 1px solid #e5e7eb;
            }

            .student-card .flex {
                flex-direction: column;
                gap: 0.5rem;
            }

            .student-card .flex-shrink-0 {
                margin-bottom: 0.5rem;
            }

            .student-card .text-sm {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                border-top: 1px solid #e5e7eb;
            }

            .class-card {
                min-height: auto;
                max-height: none;
                box-shadow: none;
                border: none;
                background: transparent;
            }

            .class-card .card-header-3a,
            .class-card .card-header-3b,
            .class-card .card-header-3c,
            .class-card .card-header-3d {
                position: sticky;
                top: 0;
                z-index: 10;
                border-radius: 12px;
                margin-bottom: 1rem;
            }

            .class-card .compact-cards {
                padding: 0;
            }

            .search-input {
                max-width: 100%;
                margin-top: 0.5rem;
            }
        }

        .class-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 350px;
            max-height: 500px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin: 0;
        }

        .class-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .class-card .compact-cards {
            flex: 1;
            overflow-y: auto;
            padding: 0.75rem;
        }

        .student-card {
            margin-bottom: 0.5rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            background: #f8fafc;
            transition: background-color 0.2s ease;
        }

        .student-card:hover {
            background: #f1f5f9;
        }

        /* Ajustes para o cabeçalho dos cards */
        .card-header-3a,
        .card-header-3b,
        .card-header-3c,
        .card-header-3d {
            padding: 1rem;
        }

        .card-header-3a h2,
        .card-header-3b h2,
        .card-header-3c h2,
        .card-header-3d h2 {
            font-size: 1.1rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Scrollbar personalizada */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Otimizações para muitos alunos */
        .compact-table td {
            padding-top: 6px;
            padding-bottom: 6px;
            font-size: 0.875rem;
        }

        .compact-table th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f9fafb;
        }

        .compact-cards {
            max-height: 400px;
            overflow-y: auto;
        }

        .compact-card {
            padding: 8px 12px;
            margin-bottom: 4px;
        }

        /* Filtro de busca */
        .search-input {
            background-color: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 6px;
            padding: 4px 12px;
            width: 100%;
            max-width: 200px;
            font-size: 0.875rem;
        }

        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .search-input:focus {
            outline: none;
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Paginação */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 4px;
            margin-top: 8px;
            padding: 8px;
            border-top: 1px solid #f3f4f6;
        }

        .pagination-btn {
            padding: 4px 8px;
            border-radius: 4px;
            background: #f3f4f6;
            color: #374151;
            font-size: 0.75rem;
            cursor: pointer;
        }

        .pagination-btn.active {
            background: #008C45;
            color: white;
        }

        /* Footer geométrico */
        .geometric-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            z-index: -1;
            overflow: hidden;
        }

        .geometric-shape {
            position: absolute;
            bottom: 0;
        }

        .shape-1 {
            left: 0;
            width: 0;
            height: 0;
            border-left: 200px solid #FFA500;
            border-top: 100px solid transparent;
        }

        .shape-2 {
            left: 150px;
            width: 0;
            height: 0;
            border-left: 250px solid #8CA03E;
            border-top: 80px solid transparent;
            opacity: 0.9;
        }

        .shape-3 {
            left: 350px;
            width: 0;
            height: 0;
            border-left: 300px solid #008C45;
            border-top: 60px solid transparent;
            opacity: 0.8;
        }

        .shape-4 {
            right: 0;
            width: 0;
            height: 0;
            border-right: 200px solid #FFA500;
            border-top: 100px solid transparent;
            opacity: 0.7;
        }

        .shape-5 {
            right: 150px;
            width: 0;
            height: 0;
            border-right: 250px solid #8CA03E;
            border-top: 80px solid transparent;
            opacity: 0.6;
        }

        .shape-6 {
            right: 350px;
            width: 0;
            height: 0;
            border-right: 300px solid #008C45;
            border-top: 60px solid transparent;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .shape-1 {
                border-left-width: 120px;
            }

            .shape-2 {
                border-left-width: 150px;
                left: 100px;
            }

            .shape-3 {
                border-left-width: 180px;
                left: 200px;
            }

            .shape-4 {
                border-right-width: 120px;
            }

            .shape-5 {
                border-right-width: 150px;
                right: 100px;
            }

            .shape-6 {
                border-right-width: 180px;
                right: 200px;
            }
        }
    

        * {
            font-family: 'Inter', sans-serif;
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
    
 .delay-history-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}.delay-history-table{border:1px solid #e7ece8;border-radius:8px;overflow:hidden}.delay-history-table th{position:sticky;top:0;z-index:1}.delay-history-table tbody tr:nth-child(even){background:#fafcf9}.delay-history-table tbody tr:hover{background:#f1f7f1}.delay-status{display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}.delay-status-pendente{background:#fff4d6;color:#8b5a00}.delay-status-aprovada{background:#e4f5e9;color:#176b38}.delay-status-recusada{background:#fde8e7;color:#9b2c25}.delay-dialog{position:fixed;top:50%;left:calc(var(--sidebar-width,0px) / 2 + 50vw);transform:translate(-50%,-50%);width:min(560px,calc(100vw - var(--sidebar-width,0px) - 2rem));max-width:calc(100vw - var(--sidebar-width,0px) - 2rem);max-height:var(--modal-max-height);padding:0;border:0;border-radius:18px;background:#fff;box-shadow:var(--modal-shadow);overflow:hidden}.delay-dialog-header{align-items:center}.delay-dialog-heading{min-width:0}.delay-dialog-heading p{margin:5px 0 0;color:#64736a;font-size:13px;line-height:1.45}.delay-dialog-content{max-height:min(52dvh,420px);overflow-y:auto;scrollbar-gutter:stable}.delay-validation-summary{margin-bottom:16px;padding:14px 16px;border:1px solid #ffdf83;border-radius:8px;background:#fffbed}.delay-validation-summary-label{margin:0 0 5px;color:#9a4b08;font-size:11px;font-weight:700;text-transform:uppercase}.delay-validation-summary-name{margin:0;color:#17251c;font-size:16px;font-weight:700}.delay-validation-summary-meta{margin:4px 0 0;color:#47574c;font-size:13px}.delay-justification-label,.delay-form-label{display:block;margin:0 0 7px;color:#26372c;font-size:13px;font-weight:700}.delay-justification{width:100%;min-height:74px;resize:vertical;padding:10px 12px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#17251c;font:400 14px/1.5 'Inter',sans-serif}.delay-extra-details{margin-top:14px;border-top:1px solid #e7ece8}.delay-extra-details summary{padding:12px 0 4px;color:#08723a;font-size:12px;font-weight:700;cursor:pointer}.delay-extra-details .delay-dialog-row{padding:8px 0}.delay-validation-form{display:grid;gap:14px}.delay-form-field{display:grid;gap:6px}.delay-form-control{width:100%;min-height:42px;padding:9px 12px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#17251c;font:400 14px 'Inter',sans-serif}.delay-form-control:focus,.delay-justification:focus{outline:2px solid #008c4530;border-color:#008c45}.delay-form-control:disabled{background:#f2f5f2;color:#43534a;opacity:1}.delay-validation-submit{display:flex;min-height:46px;align-items:center;justify-content:center;gap:9px;border:0;border-radius:7px;background:#008c45;color:#fff;font:700 14px 'Inter',sans-serif;cursor:pointer}.delay-validation-submit:hover{background:#06753e}
.delay-dialog[open]{position:fixed;top:50%;left:calc(var(--sidebar-width,0px) / 2 + 50vw);transform:translate(-50%,-50%);width:min(560px,calc(100vw - var(--sidebar-width,0px) - 2rem));max-width:calc(100vw - var(--sidebar-width,0px) - 2rem);max-height:var(--modal-max-height);overflow-y:auto;border:1px solid rgba(15,23,42,.08);border-radius:18px;background:#fff;box-shadow:var(--modal-shadow)}.delay-dialog::backdrop{background:var(--modal-overlay)}.delay-dialog-header{padding:1.2rem 1.25rem;background:#fff;border-bottom:1px solid rgba(15,23,42,.08)}.delay-dialog-heading{display:flex;align-items:flex-start;gap:10px}.delay-dialog-heading::before{content:'\f058';flex:none;margin-top:2px;color:#008c45;font-family:'Font Awesome 6 Free';font-size:17px;font-weight:900}.delay-dialog-heading h2{font-size:19px}.delay-dialog-heading p{line-height:1.45}.delay-dialog-content{max-height:none;overflow:visible;padding:1.2rem 1.25rem}.delay-validation-summary{padding:12px 15px}.delay-justification{min-height:88px}.delay-demo-note{margin:0 0 17px;color:#64736a;font-size:12px;line-height:1.5}.delay-validation-form{gap:12px;padding:14px 20px 18px}.delay-form-control{min-height:40px}.delay-validation-form[hidden],.delay-form-field[hidden]{display:none}.delay-detail-button{display:inline-flex;min-height:32px;align-items:center;justify-content:center;gap:7px;padding:0 11px;border:1px solid #008c45;border-radius:6px;background:#008c45;color:#fff;font:600 12px 'Inter',sans-serif;text-decoration:none;cursor:pointer}.delay-detail-button:hover{background:#06753e;text-decoration:none}.delay-detail-button-secondary{border-color:#d5e3d9;background:#f2f8f3;color:#08723a}.delay-detail-button-secondary:hover{background:#e4f1e7}.delay-decision-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.delay-decision-option{display:flex;min-height:48px;align-items:center;justify-content:center;gap:8px;padding:9px 10px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#43534a;font:600 13px 'Inter',sans-serif;cursor:pointer}.delay-decision-option-approve{border-color:#b5ddc2;color:#176b38}.delay-decision-option-approve:hover,.delay-decision-option-approve[aria-pressed="true"]{border-color:#008c45;background:#e7f5eb;color:#08723a;box-shadow:inset 0 0 0 1px #008c45}.delay-decision-option-reject{border-color:#f0c4c1;color:#9b2c25}.delay-decision-option-reject:hover,.delay-decision-option-reject[aria-pressed="true"]{border-color:#c9362b;background:#fff0ef;color:#a62f26;box-shadow:inset 0 0 0 1px #c9362b}.delay-validation-submit.is-rejection{background:#c9362b}.delay-validation-submit.is-rejection:hover{background:#a62f26}.delay-validation-submit:disabled{opacity:.55;cursor:not-allowed}
.delay-history-filters{display:grid;grid-template-columns:minmax(180px,1.5fr) repeat(4,minmax(130px,1fr)) auto;align-items:end;gap:10px;margin:18px 0 20px;padding:14px;border:1px solid #e3eae5;border-radius:8px;background:#f8faf8}.delay-history-filter{display:grid;gap:6px;color:#53615a;font-size:11px;font-weight:700}.delay-history-filter input,.delay-history-filter select{width:100%;height:39px;padding:0 10px;border:1px solid #d5ded8;border-radius:6px;background:#fff;color:#26352c;font:400 13px 'Inter',sans-serif}.delay-history-filter input:focus,.delay-history-filter select:focus{outline:2px solid #008c4555;border-color:#008c45}.delay-history-reset{height:39px;padding:0 13px;border:1px solid #d5ded8;border-radius:6px;background:#fff;color:#43534a;font:600 12px 'Inter',sans-serif;cursor:pointer}.delay-history-reset:hover{background:#edf5ef}.delay-history-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.delay-history-card{overflow:hidden;border:1px solid #e1e8e3;border-radius:8px;background:#fff}.delay-history-card-header{display:flex;align-items:center;justify-content:space-between;padding:13px 15px;border-bottom:1px solid #e7ece8;background:#f7faf7}.delay-history-card-header h3{margin:0;color:#183c27;font-size:15px;font-weight:700}.delay-history-count{display:grid;min-width:27px;height:25px;place-items:center;padding:0 7px;border-radius:14px;background:#e5f3e9;color:#176b38;font-size:11px;font-weight:700}.delay-history-list{max-height:330px;overflow-y:auto;padding:0 14px}.delay-history-entry{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:7px 12px;padding:12px 1px;border-bottom:1px solid #edf0ee}.delay-history-entry:last-child{border-bottom:0}.delay-history-entry[hidden],.delay-history-card[hidden]{display:none}.delay-history-entry-name{overflow:hidden;color:#26352c;font-size:13px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.delay-history-entry-meta{display:flex;align-items:center;gap:8px;color:#64736a;font-size:11px;white-space:nowrap}.delay-history-entry-action{grid-column:1/-1;justify-self:start}.delay-history-empty{padding:22px 14px;color:#64736a;text-align:center;font-size:13px}.delay-history-no-results{margin:14px 0 0;padding:20px;border:1px dashed #cbd8cf;border-radius:8px;color:#64736a;text-align:center;font-size:13px}.delay-history-no-results[hidden]{display:none}
@media(max-width:1100px){.delay-history-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:600px){.delay-history-filters{grid-template-columns:repeat(2,minmax(0,1fr));padding:11px}.delay-history-filter:first-child{grid-column:1/-1}.delay-history-grid{grid-template-columns:1fr}.delay-history-entry{grid-template-columns:minmax(0,1fr)}.delay-history-entry-meta{flex-wrap:wrap}}
.delay-history-filters{grid-template-columns:minmax(170px,1.4fr) minmax(145px,.9fr) minmax(145px,.9fr) minmax(260px,1.5fr) auto;gap:12px}.delay-history-date-range{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;min-width:0}.delay-history-filter input,.delay-history-filter select{min-width:0}.delay-history-grid{align-items:start;gap:16px}.delay-history-card{border-color:#dce5df;box-shadow:0 2px 7px #143b2110}.delay-history-card-header{min-height:54px;border-bottom:0;background:#59636b;color:#fff}.delay-history-card-header h3{color:#fff}.delay-history-card-header h3 i{margin-right:4px}.delay-history-count{background:#ffffff2e;color:#fff}.delay-history-card.turma-3a .delay-history-card-header{background:#cf2035}.delay-history-card.turma-3b .delay-history-card-header{background:#3854d9}.delay-history-card.turma-3c .delay-history-card-header{background:#0b9bb2}.delay-history-card.turma-3d .delay-history-card-header{background:#59636b}.delay-history-list{max-height:360px;padding:0 16px}.delay-history-entry{grid-template-columns:minmax(0,1fr) auto auto;gap:10px;padding:13px 1px}.delay-history-entry-meta{gap:9px}.delay-history-entry-action{grid-column:auto}.delay-history-empty{margin:0;padding:20px 8px;border:0;border-radius:0;background:transparent;color:#758078;font-size:12px}
@media(max-width:1100px){.delay-history-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.delay-history-date-range{grid-column:span 2}}
@media(max-width:600px){.delay-history-filters{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.delay-history-filter:first-child,.delay-history-date-range{grid-column:1/-1}.delay-history-date-range{gap:8px}.delay-history-grid{grid-template-columns:1fr}.delay-history-entry{grid-template-columns:minmax(0,1fr) auto;gap:8px}.delay-history-entry-meta{grid-column:1/-1;grid-row:2;flex-wrap:wrap}.delay-history-entry-action{grid-column:2;grid-row:1}}
.delay-history-card{display:flex;min-height:290px;flex-direction:column}
.delay-history-list{flex:1;min-height:220px;max-height:420px;overflow-y:auto;overscroll-behavior:contain;scrollbar-gutter:stable}
.delay-history-card-filters{display:grid;grid-template-columns:minmax(130px,1.4fr) minmax(110px,.9fr) minmax(145px,1fr) auto;align-items:end;gap:7px;padding:8px 14px;border-bottom:1px solid #e7ece8;background:#f8faf8}.delay-history-card-filters .delay-history-filter{gap:4px}.delay-history-card-filters .delay-history-filter input,.delay-history-card-filters .delay-history-filter select{height:31px}.delay-history-card-filters .delay-history-reset{height:31px;padding:0 9px}
@media(max-width:1100px){.delay-history-card-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.delay-history-card-filters .delay-history-filter:first-child{grid-column:1/-1}}

/* Relatorios: identidade visual compartilhada sem alterar os fluxos de geracao. */
#relatorios > .flex-1 {
    width: 100%;
    max-width: none;
    margin: 0;
    padding: clamp(1rem, 3vw, 2rem) 0;
}

#relatorios > .flex-1 > .max-w-2xl { max-width: 1040px; }

#relatorios .text-center.mb-8 {
    margin: 0 0 1.35rem;
    text-align: left;
}

#relatorios .text-center.mb-8 h1 {
    margin: 0;
    color: #08723a;
    font-size: clamp(1.45rem, 2.2vw, 1.9rem);
    font-weight: 750;
    line-height: 1.2;
}

#relatorios .text-center.mb-8 p {
    margin: .4rem 0 0;
    color: #64736a;
    font-size: .88rem;
}

#relatorios .space-y-4 {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

#relatorios .space-y-4 > * + * { margin-top: 0; }

#relatorios .report-card {
    display: block;
    min-height: 112px;
    padding: 1.15rem 1.25rem;
    border: 1px solid #e0e9e2;
    border-left: 5px solid #008c45;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 3px 12px #143b2110;
    text-decoration: none;
    transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
}

#relatorios .report-card:hover {
    transform: translateY(-2px);
    border-color: #b9d7c3;
    border-left-color: #3cb371;
    box-shadow: 0 12px 24px #143b2117;
}

#relatorios .report-card > div { width: 100%; }

#relatorios .report-icon {
    display: grid;
    width: 46px;
    height: 46px;
    flex: 0 0 46px;
    place-items: center;
    border-radius: 12px;
    background: #e8f5ec;
    color: #08723a;
    font-size: 1rem;
}

#relatorios .report-card h3 {
    margin: 0;
    color: #203629;
    font-size: .98rem;
    font-weight: 700;
    line-height: 1.4;
}

#relatorios .report-card p {
    margin: .3rem 0 0;
    color: #68776e;
    font-size: .8rem;
    line-height: 1.45;
}

#relatorio-entrada > .main-content,
#relatorio-dia > .main-content,
#relatorio-saida > .main-content,
#relatorio-estagio > .main-content {
    width: 100%;
    min-height: 0;
    margin: 0;
    padding: clamp(1rem, 3vw, 2rem) 0;
}

.page-section.active#relatorio-entrada,
.page-section.active#relatorio-dia,
.page-section.active#relatorio-saida,
.page-section.active#relatorio-estagio {
    display: flex;
    min-height: calc(100dvh - 68px);
    align-items: center;
}

@media (min-width: 761px) {
    main.main-content {
        height: 100dvh;
        min-height: 0;
        margin-top: 0;
        padding: 34px clamp(20px, 4vw, 60px);
        overflow-y: auto;
        overscroll-behavior-y: contain;
    }
}

#relatorio-entrada > .main-content > .container,
#relatorio-dia > .main-content > .container,
#relatorio-saida > .main-content > .container,
#relatorio-estagio > .main-content > .container {
    max-width: 900px;
    padding: 0;
}

#relatorio-entrada .title-section,
#relatorio-dia .title-section,
#relatorio-saida .title-section,
#relatorio-estagio .title-section {
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr);
    align-items: center;
    gap: .2rem 1rem;
    margin: 0 0 1.25rem;
    text-align: left;
}

#relatorio-saida .title-section .icon-container,
#relatorio-estagio .title-section .icon-container {
    grid-column: 1;
    grid-row: 1 / span 2;
    width: 48px;
    height: 48px;
    margin: 0;
    border-radius: 10px;
    background: rgba(0, 140, 69, 0.1);
    color: #008c45;
}

#relatorio-dia .title-section .icon-container {
    grid-column: 1;
    grid-row: 1 / span 2;
    width: 48px;
    height: 48px;
    margin: 0;
    border-radius: 10px;
    background: rgba(0, 140, 69, 0.1);
    color: #008c45;
}

#relatorio-dia .title-section .icon-container i { font-size: 1.5rem; }

#relatorio-saida .title-section .icon-container i,
#relatorio-estagio .title-section .icon-container i {
    font-size: 1.5rem;
}

#relatorio-entrada .title-section h1,
#relatorio-dia .title-section h1,
#relatorio-saida .title-section h1,
#relatorio-estagio .title-section h1 {
    grid-column: 2;
    grid-row: 1;
    margin: 0;
    color: #08723a;
    font-size: clamp(1.45rem, 2vw, 1.9rem);
    font-weight: 750;
    line-height: 1.2;
}

#relatorio-entrada .title-section .subtitle,
#relatorio-dia .title-section .subtitle,
#relatorio-saida .title-section .subtitle,
#relatorio-estagio .title-section .subtitle {
    grid-column: 2;
    grid-row: 2;
    margin: 0;
    color: #64736a;
    font-size: .88rem;
}

#relatorio-entrada .form-card,
#relatorio-dia .form-card,
#relatorio-saida .form-card,
#relatorio-estagio .form-card,
#relatorio-dia .form-card,
#qrcode .form-card {
    border: 1px solid #e1e9e3;
    border-left: 5px solid #008c45;
    border-radius: 14px;
    background: #fff;
    box-shadow: 0 4px 18px #143b2110;
}

#relatorio-entrada .form-card,
#relatorio-dia .form-card,
#relatorio-saida .form-card,
#relatorio-estagio .form-card { overflow: visible; }

#relatorio-entrada .tabs-nav,
#relatorio-dia .tabs-nav,
#relatorio-saida .tabs-nav,
#relatorio-estagio .tabs-nav {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .45rem;
    margin: 0;
    padding: .55rem;
    border: 0;
    border-bottom: 1px solid #e7eee8;
    border-radius: 0 13px 0 0;
    background: #f6faf7;
}

#relatorio-entrada .tab-btn,
#relatorio-dia .tab-btn,
#relatorio-saida .tab-btn,
#relatorio-estagio .tab-btn {
    display: inline-flex;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .65rem .8rem;
    border: 1px solid transparent;
    border-radius: 8px;
    background: transparent;
    color: #5b6c61;
    font: 650 .84rem 'Inter', sans-serif;
    transition: background .18s ease, color .18s ease, border-color .18s ease;
}

#relatorio-entrada .tab-btn:hover,
#relatorio-dia .tab-btn:hover,
#relatorio-saida .tab-btn:hover,
#relatorio-estagio .tab-btn:hover {
    transform: none;
    background: #edf6ef;
    color: #176b38;
    box-shadow: none;
}

#relatorio-entrada .tab-btn.active,
#relatorio-dia .tab-btn.active,
#relatorio-saida .tab-btn.active,
#relatorio-estagio .tab-btn.active {
    border-color: #d6e9db;
    background: #fff;
    color: #08723a;
    box-shadow: 0 2px 6px #143b210d;
}

#relatorio-entrada .tab-content,
#relatorio-dia .tab-content,
#relatorio-saida .tab-content,
#relatorio-estagio .tab-content { padding: clamp(1.1rem, 3vw, 1.8rem); }

#relatorio-entrada .form-group,
#relatorio-dia .form-group,
#relatorio-saida .form-group,
#relatorio-estagio .form-group { margin-bottom: 1.25rem; }

#relatorio-entrada .form-label,
#relatorio-dia .form-label,
#relatorio-saida .form-label,
#relatorio-estagio .form-label {
    display: block;
    margin: 0 0 .5rem;
    color: #35483b;
    font-size: .82rem;
    font-weight: 700;
}

#relatorio-entrada select,
#relatorio-dia select,
#relatorio-saida select,
#relatorio-estagio select,
#qrcode .form-card select {
    width: 100%;
    min-height: 46px;
    padding: .65rem .8rem;
    border: 1px solid #d2ded5;
    border-radius: 8px;
    background-color: #fff;
    color: #26352c;
    font: 400 .88rem 'Inter', sans-serif;
    box-shadow: none;
}

#relatorio-entrada select:focus,
#relatorio-dia select:focus,
#relatorio-saida select:focus,
#relatorio-estagio select:focus,
#qrcode .form-card select:focus {
    outline: 0;
    border-color: #008c45;
    box-shadow: 0 0 0 3px #008c4518;
}

#relatorio-entrada .select2-container,
#relatorio-saida .select2-container,
#relatorio-estagio .select2-container { width: 100% !important; }

#relatorio-entrada .select2-container--default .select2-selection--single,
#relatorio-saida .select2-container--default .select2-selection--single,
#relatorio-estagio .select2-container--default .select2-selection--single {
    height: auto;
    min-height: 46px;
    padding: .55rem .8rem;
    border: 1px solid #d2ded5;
    border-radius: 8px;
}

#relatorio-entrada .select2-container--default .select2-selection--single .select2-selection__rendered,
#relatorio-saida .select2-container--default .select2-selection--single .select2-selection__rendered,
#relatorio-estagio .select2-container--default .select2-selection--single .select2-selection__rendered {
    padding: 0;
    color: #26352c;
    line-height: 1.5;
}

#relatorio-entrada .radio-group,
#relatorio-dia .radio-group,
#relatorio-saida .radio-group,
#relatorio-estagio .radio-group {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: .65rem;
    margin: .65rem 0 0;
}

#relatorio-entrada .radio-item,
#relatorio-dia .radio-item,
#relatorio-saida .radio-item,
#relatorio-estagio .radio-item {
    min-width: 0;
    min-height: 48px;
    padding: .7rem .8rem;
    border: 1px solid #dce6de;
    border-radius: 8px;
    background: #fff;
}

#relatorio-entrada .radio-item:hover,
#relatorio-dia .radio-item:hover,
#relatorio-saida .radio-item:hover,
#relatorio-estagio .radio-item:hover {
    margin: 0;
    padding: .7rem .8rem;
    border-color: #b9d7c3;
    background: #f5faf6;
    box-shadow: none;
}

#relatorio-entrada .radio-label,
#relatorio-dia .radio-label,
#relatorio-saida .radio-label,
#relatorio-estagio .radio-label {
    color: #43534a;
    font-size: .8rem;
    font-weight: 600;
}

#relatorio-entrada .btn-primary,
#relatorio-dia .btn-primary,
#relatorio-saida .btn-primary,
#relatorio-estagio .btn-primary,
#qrcode .form-card .btn-primary {
    width: 100%;
    min-height: 46px;
    border-radius: 8px;
    background: linear-gradient(110deg, #08723a, #20a45a);
    color: #fff;
    font: 700 .88rem 'Inter', sans-serif;
    box-shadow: 0 4px 10px #008c4522;
}

#relatorio-entrada .btn-primary:hover,
#relatorio-dia .btn-primary:hover,
#relatorio-saida .btn-primary:hover,
#relatorio-estagio .btn-primary:hover,
#qrcode .form-card .btn-primary:hover {
    transform: translateY(-1px);
    background: linear-gradient(110deg, #065c32, #168849);
}

#relatorio-entrada .btn-primary,
#relatorio-dia .btn-primary,
#relatorio-saida .btn-primary,
#relatorio-estagio .btn-primary,
#qrcode .form-card .btn-primary {
    display: flex;
    width: fit-content;
    min-height: 2.4rem;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    margin-left: auto;
    padding: .6rem 1rem;
    border: 0;
    border-radius: 12px;
    background: linear-gradient(90deg, #008c45, #3cb371);
    color: #fff;
    font: 700 .8rem 'Inter', sans-serif;
    box-shadow: 0 10px 16px rgba(0, 140, 69, 0.18);
}

#relatorio-entrada .btn-primary:hover,
#relatorio-dia .btn-primary:hover,
#relatorio-saida .btn-primary:hover,
#relatorio-estagio .btn-primary:hover,
#qrcode .form-card .btn-primary:hover {
    background: linear-gradient(90deg, #006d35, #2e8b57);
    box-shadow: 0 12px 20px rgba(0, 140, 69, 0.22);
}

#relatorio-entrada .info-card {
    margin-top: 1rem;
    padding: 1rem 1.15rem;
    border: 1px solid #d7e9dc;
    border-radius: 10px;
    background: #f3f9f4;
}

#relatorio-dia .report-error {
    margin: 1rem 1.5rem 0;
    padding: .8rem 1rem;
    border: 1px solid #f2c5c2;
    border-radius: 8px;
    background: #fff2f1;
    color: #a62f26;
    font-size: .84rem;
}

#relatorio-dia .report-hint {
    margin: .5rem 0 0;
    color: #64736a;
    font-size: .78rem;
    font-weight: 400;
}

#qrcode .form-card label i { color: #08723a; }

#qrcode > .flex-1 {
    min-height: calc(100dvh - 100px);
    align-items: center;
    padding: clamp(1rem, 3vw, 2rem) 1rem;
}

#qrcode > .flex-1 > .max-w-md { max-width: 760px; }

#qrcode .text-center.mb-8 {
    display: grid;
    grid-template-columns: 52px minmax(0, 1fr);
    align-items: center;
    gap: .2rem 1rem;
    margin: 0 0 1.25rem;
    text-align: left;
}

#qrcode .text-center.mb-8 .icon-container {
    grid-column: 1;
    grid-row: 1 / span 2;
    width: 48px;
    height: 48px;
    margin: 0;
}

#qrcode .text-center.mb-8 h1 {
    grid-column: 2;
    grid-row: 1;
    margin: 0;
    color: #08723a;
    font-size: clamp(1.45rem, 2vw, 1.85rem);
    font-weight: 750;
    line-height: 1.2;
}

#qrcode .text-center.mb-8 p {
    grid-column: 2;
    grid-row: 2;
    margin: 0;
    color: #64736a;
    font-size: .88rem;
}

#qrcode .form-card { padding: clamp(1.2rem, 3vw, 2rem); }
#qrcode .form-card form { display: grid; gap: 1rem; }
#qrcode .form-card form > div:first-child { display: grid; gap: .5rem; }
#qrcode .form-card label { color: #35483b; font-size: .82rem; font-weight: 700; }
#qrcode .form-card .text-center.mt-6 { margin-top: 1rem; padding-top: .9rem; border-top: 1px solid #edf1ee; text-align: left; }
#qrcode .form-card .text-center.mt-6 a { color: #08723a; font-size: .82rem; font-weight: 650; text-decoration: none; }

.page-section.active#relatorios .report-card,
.page-section.active#relatorio-entrada .form-card,
.page-section.active#relatorio-dia .form-card,
.page-section.active#relatorio-saida .form-card,
.page-section.active#relatorio-estagio .form-card,
.page-section.active#relatorio-dia .form-card,
.page-section.active#qrcode .form-card {
    animation: modal-shell-appear .42s cubic-bezier(.16, 1, .3, 1) both;
}

@media (max-width: 640px) {
    #relatorios .space-y-4,
    #relatorio-entrada .tabs-nav,
    #relatorio-dia .tabs-nav,
    #relatorio-saida .tabs-nav,
    #relatorio-estagio .tabs-nav,
    #relatorio-entrada .radio-group,
    #relatorio-dia .radio-group,
    #relatorio-saida .radio-group,
    #relatorio-estagio .radio-group {
        grid-template-columns: minmax(0, 1fr);
    }

    #relatorio-entrada .tab-btn,
    #relatorio-dia .tab-btn,
    #relatorio-saida .tab-btn,
    #relatorio-estagio .tab-btn { justify-content: flex-start; }

}

@media (prefers-reduced-motion: reduce) {
    #relatorios .report-card,
    #relatorio-entrada .tab-btn,
    #relatorio-saida .tab-btn,
    #relatorio-estagio .tab-btn {
        animation: none;
        transition: none;
    }
}

#atrasos .delays-title,
#ultimas-saidas .main-container > .text-center h1 {
    font-size: 1.875rem;
    font-weight: 700;
    line-height: 1.2;
}

#atrasos .delays-heading {
    margin-bottom: 39px;
}

#ultimas-saidas .main-container > .text-center {
    margin-bottom: 39px;
}

#ultimas-saidas .main-container > .text-center .flex.flex-col.lg\:flex-row {
    gap: 14px;
}

#ultimas-saidas .main-container > .text-center > .mt-4 {
    margin-top: 14px;
    color: #64736a;
    font-size: 13px;
    line-height: 1.5;
}

#ultimas-saidas .main-container > .text-center .inline-flex.items-center.bg-white {
    min-width: 0;
    min-height: 47px;
    padding: 8px 16px;
    border-color: #e5e7eb;
    border-radius: 8px;
}

#ultimas-saidas #relogio {
    font-family: 'Inter', sans-serif !important;
    font-size: 19px !important;
    font-weight: 700;
    letter-spacing: 0 !important;
    line-height: 1.25;
}

#ultimas-saidas .card-header-3a h2,
#ultimas-saidas .card-header-3b h2,
#ultimas-saidas .card-header-3c h2,
#ultimas-saidas .card-header-3d h2 {
    font-size: 17px;
    font-weight: 700;
    line-height: 1.5;
}

#ultimas-saidas .card-header-3a .flex.items-center.justify-between,
#ultimas-saidas .card-header-3b .flex.items-center.justify-between,
#ultimas-saidas .card-header-3c .flex.items-center.justify-between,
#ultimas-saidas .card-header-3d .flex.items-center.justify-between {
    margin-bottom: 10px;
}

#ultimas-saidas .class-card .bg-white.bg-opacity-20 {
    display: inline-grid;
    width: 34px;
    height: 28px;
    place-items: center;
    padding: 0;
    border-radius: 18px;
    background: #fff;
    color: #23352a;
    font-size: 12px;
    font-weight: 700;
    line-height: 1;
}

#ultimas-saidas .search-input {
    width: min(100%, 200px);
    height: 32px;
    margin-top: 0;
    padding: 0 12px;
    border: 1px solid #ffffff70;
    border-radius: 6px;
    background: #ffffff30;
    color: #fff;
    font: 400 13px 'Inter', sans-serif;
}

#ultimas-saidas .search-input::placeholder {
    color: #ffffffc9;
}

#ultimas-saidas .search-input:focus {
    outline: 2px solid #fff;
    outline-offset: 1px;
    background: #ffffff30;
}

#ultimas-saidas .card-header-3a {
    background: #cf2035;
    color: #fff;
}

#ultimas-saidas .card-header-3b {
    background: #3854d9;
    color: #fff;
}

#ultimas-saidas .card-header-3c {
    background: #0db5d0;
    color: #fff;
}

#ultimas-saidas .card-header-3d {
    background: #59636b;
    color: #fff;
}

#ultimas-saidas .class-card .card-header-3a,
#ultimas-saidas .class-card .card-header-3b,
#ultimas-saidas .class-card .card-header-3c,
#ultimas-saidas .class-card .card-header-3d {
    position: static;
    z-index: auto;
    margin-bottom: 0;
    border-radius: 0;
}

#ultimas-saidas .class-card .compact-cards {
    padding: 8px 12px;
}

#ultimas-saidas .compact-cards > .text-center.py-8 {
    padding-top: 44px;
    padding-bottom: 44px;
    color: #64748b;
    font-size: 15px;
    font-style: italic;
    line-height: 1.5;
}

@media (max-width: 600px) {
    #atrasos .delays-heading,
    #ultimas-saidas .main-container > .text-center {
        min-height: 152px;
        margin-bottom: 39px;
    }

    #atrasos .delays-heading {
        margin-top: 20px;
        margin-bottom: 52px;
        transform: translateY(7px);
    }

    #ultimas-saidas .main-container > .text-center {
        margin-top: 14px;
    }

    #atrasos .delays-clock {
        height: 47px;
        min-height: 47px;
        font-size: 19px;
        line-height: 1.5;
    }

    #atrasos .delay-card {
        min-height: 350px;
    }
}

@media (max-width: 760px) {
    html,
    body {
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }

    .main-content {
        width: 100%;
        min-width: 0;
        margin-left: 0;
        padding: 62px 12px 20px;
    }

    .page-section,
    .page-section.active {
        width: 100%;
        min-width: 0;
        max-width: 100%;
        margin-right: 0;
        margin-left: 0;
    }

    .page-section :where(.container, .main-container, .form-card, form, table) {
        min-width: 0;
        max-width: 100%;
    }

    .page-section :where(input, select, textarea, button, img) {
        min-width: 0;
        max-width: 100%;
    }

    .page-section form .grid,
    .page-section .modal-form-section .grid {
        grid-template-columns: minmax(0, 1fr) !important;
    }

    #entrada .modal-form-scroll,
    #saida .modal-form-scroll,
    #estagio .modal-form-scroll,
    #cadastro .modal-form-scroll {
        min-height: 0;
        overflow-x: hidden;
        overflow-y: auto;
        overscroll-behavior: contain;
        -webkit-overflow-scrolling: touch;
    }

    .modal-form-footer {
        flex-wrap: wrap;
    }

    .modal-form-footer > * {
        min-width: 0;
    }

    .edit-record-dialog,
    .delay-dialog {
        width: calc(100vw - 24px);
        max-width: calc(100vw - 24px);
        max-height: calc(100dvh - 24px);
    }

    .early-exit-actions {
        flex-direction: column-reverse;
    }

    .early-exit-actions button {
        width: 100%;
    }

    .delay-dialog-header,
    .delay-dialog-content {
        padding-right: 1rem;
        padding-left: 1rem;
    }

    #qrcode > .flex-1 {
        min-height: calc(100dvh - 82px);
        align-items: center;
        padding: 1.25rem .5rem;
    }
}

input:focus,
textarea:focus,
select:focus,
.select2-container--focus .select2-selection,
.select2-container--open .select2-selection,
.select2-search__field:focus {
    outline: 2px solid rgba(0, 140, 69, .45) !important;
    outline-offset: 1px;
    box-shadow: 0 0 0 3px rgba(0, 140, 69, .12) !important;
}

input[type="checkbox"],
input[type="radio"] {
    accent-color: var(--salaberga-green);
}
.qr-input-capture{position:fixed!important;top:0!important;left:-10000px!important;width:1px!important;height:1px!important;min-width:1px!important;max-width:1px!important;margin:0!important;padding:0!important;border:0!important;opacity:0!important;pointer-events:none!important}
.delay-history-entry-actions{display:flex;align-items:center;gap:7px}@media(max-width:600px){.delay-history-entry-actions{grid-column:1/-1;grid-row:auto}}
#delay-validation-motive-field{margin-bottom:20px}
</style>
<style>
    body.reception-interface {
        --sidebar-width: 0px;
        display: flex;
        min-height: 100vh;
        flex-direction: column;
        background: var(--salaberga-workspace);
    }

    .reception-header {
        position: relative;
        z-index: 1001;
        display: flex;
        min-height: 56px;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        padding: 0 20px;
        background: var(--salaberga-dark);
        color: #fff;
        box-shadow: 0 2px 5px #143b211a;
    }

    .reception-brand {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: #fff;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: .1px;
        text-decoration: none;
    }

    .reception-brand-mark {
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        overflow: hidden;
        border: 0;
        border-radius: 8px;
        background: transparent;
    }

    .reception-brand-mark img {
        display: block;
        width: 29px;
        height: 29px;
        object-fit: contain;
    }

    .reception-brand-system {
        color: #fff;
    }

    .reception-brand-name {
        color: var(--salaberga-amber);
    }

    .reception-header-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .reception-header-link {
        display: inline-flex;
        min-height: 36px;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 0 13px;
        border: 1px solid #ffffff30;
        border-radius: 7px;
        background: #ffffff1c;
        color: #fff;
        font: 600 13px 'Inter', sans-serif;
        text-decoration: none;
        cursor: pointer;
        transition: background .18s ease;
    }

    .reception-header-link[hidden] {
        display: none;
    }

    .reception-header-link:hover {
        background: #ffffff30;
    }

    .reception-header-link:focus-visible {
        outline: 2px solid #fff;
        outline-offset: 2px;
    }

    .reception-interface .sidebar,
    .reception-interface .sidebar-collapse-toggle,
    .reception-interface .sidebar-scrim,
    .reception-interface .mobile-menu {
        display: none !important;
    }

    .reception-interface .main-content {
        width: 100%;
        min-height: 0;
        flex: 1;
        margin: 0;
        padding: 24px 20px 48px;
    }

    .reception-interface #inicio {
        width: 100%;
        max-width: none;
    }

    .reception-dashboard-content {
        width: min(100%, 760px);
        margin: 0 auto !important;
        padding: 0 !important;
    }

    .reception-dashboard-content > div {
        max-width: none !important;
    }

    .reception-dashboard-heading {
        margin: 0 0 25px !important;
    }

    .reception-dashboard-heading h1 {
        margin-bottom: 4px !important;
        color: var(--salaberga-green);
        font-size: clamp(24px, 3vw, 30px);
        line-height: 1.25;
    }

    .reception-dashboard-heading p {
        margin: 0;
        color: #59665f;
        font-size: 13px;
    }

    .reception-dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .reception-interface .menu-card {
        min-height: 112px;
        padding: 24px 26px;
        border: 1px solid var(--reception-card-border, #e8eee9);
        border-left: 4px solid var(--reception-card-color, var(--salaberga-green));
        border-radius: 14px;
        box-shadow: 0 2px 5px #143b2118;
        text-decoration: none;
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease, background .2s ease;
    }

    .reception-interface .menu-card:hover {
        border-color: var(--reception-card-color);
        background: var(--reception-card-tint);
        box-shadow: 0 6px 14px #143b2120;
        transform: translateY(-3px);
    }

    .reception-interface .menu-icon {
        width: 52px;
        height: 52px;
        flex: 0 0 52px;
        border-radius: 10px;
        background: var(--reception-card-tint) !important;
        color: var(--reception-card-color) !important;
        font-size: 18px;
        transition: color .2s ease, background .2s ease, transform .2s ease;
    }

    .reception-interface .menu-card:hover .menu-icon {
        background: var(--reception-card-color) !important;
        color: #fff !important;
        transform: scale(1.06);
    }

    .reception-card-delay {
        --reception-card-color: #dc3545;
        --reception-card-border: #f0c7cc;
        --reception-card-tint: #fff0f1;
    }

    .reception-card-exit {
        --reception-card-color: #2878c7;
        --reception-card-border: #c7dbee;
        --reception-card-tint: #eef6ff;
    }

    .reception-card-internship {
        --reception-card-color: #8246bd;
        --reception-card-border: #ddcbed;
        --reception-card-tint: #f6effc;
    }

    .reception-card-absence {
        --reception-card-color: #d9850b;
        --reception-card-border: #f0ddbd;
        --reception-card-tint: #fff6e8;
    }

    .reception-interface .menu-card h3 {
        margin: 0 0 3px;
        color: #1c2d23;
        font-size: 16px;
    }

    .reception-interface .menu-card p {
        margin: 0;
        color: #69766e;
        font-size: 12px;
        line-height: 1.4;
    }

    .reception-interface .menu-card:hover h3 {
        color: var(--reception-card-color);
    }

    .reception-footer {
        position: relative;
        padding: 14px 20px;
        border-top: 1px solid #e6ebe7;
        background: #fff;
        color: #59665f;
        text-align: center;
        font-size: 11px;
    }

    .reception-footer::before {
        position: absolute;
        top: 0;
        left: 50%;
        width: 80px;
        height: 2px;
        background: linear-gradient(90deg, var(--salaberga-green), var(--salaberga-amber));
        content: '';
        transform: translateX(-50%);
    }

    @media (max-width: 600px) {
        .reception-header {
            min-height: 54px;
            padding: 0 14px;
        }

        .reception-interface .main-content {
            padding: 26px 16px 36px;
        }

        .reception-dashboard-heading {
            margin-bottom: 22px !important;
        }

        .reception-dashboard-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }
    }

    .delay-entry,
    .delay-history-entry {
        cursor: pointer;
    }

    .delay-entry:focus-visible,
    .delay-history-entry:focus-visible {
        outline: 3px solid rgba(0, 140, 69, .45);
        outline-offset: 2px;
    }
</style>
</head>
<body class="<?= $ehRecepcao ? 'reception-interface' : '' ?>">
<?php if ($ehRecepcao) { ?>
<header class="reception-header">
    <a class="reception-brand" href="#" data-section-target="inicio">
        <span class="reception-brand-mark"><img src="../assets/img/logo.png" alt=""></span>
        <span><span class="reception-brand-system">Sistema </span><span class="reception-brand-name">Salaberga</span></span>
    </a>
    <div class="reception-header-actions">
        <a class="reception-header-link" href="#" data-section-target="inicio" data-reception-home<?= $sectionInicial === 'inicio' ? ' hidden' : '' ?>><i class="fas fa-arrow-left" aria-hidden="true"></i>Início</a>
        <a class="reception-header-link" href="../model/sessions.php?sair"><i class="fas fa-right-from-bracket" aria-hidden="true"></i>Sair</a>
    </div>
</header>
<?php } ?>
<aside class="sidebar" aria-label="Navegação principal">
    <div class="brand"><span class="brand-mark"><img src="../assets/img/logo.png" alt=""></span><span>Sistema <span class="text-[#FFA500]">Salaberga</span><small>Entradas e saídas escolares</small></span></div>
    <nav>
        <?php if ($ehRecepcao) { ?>
        <div class="nav-group" aria-label="Menu da recepção">
            <button class="nav-item" type="button" data-target="entrada"><i class="fas fa-arrow-right-to-bracket"></i>Registrar atraso</button>
            <button class="nav-item" type="button" data-target="saida"><i class="fas fa-arrow-right-from-bracket"></i>Saída antecipada</button>
            <button class="nav-item" type="button" data-target="estagio"><i class="fas fa-briefcase"></i>Saída para estágio</button>
            <button class="nav-item" type="button" data-target="consulta-ausencias"><i class="fas fa-user-xmark"></i>Ausências registradas</button>
        </div>
        <?php } else { ?>
        <details class="nav-group" data-sidebar-group="registros"><summary class="nav-label">Registros</summary><button class="nav-item" type="button" data-target="entrada"><i class="fas fa-arrow-right-to-bracket"></i>Registro de Atraso</button><button class="nav-item" type="button" data-target="saida"><i class="fas fa-arrow-right-from-bracket"></i>Saída Antecipada</button><button class="nav-item" type="button" data-target="estagio"><i class="fas fa-briefcase"></i>Saída para estágio</button><button class="nav-item" type="button" data-target="cadastro"><i class="fas fa-user-plus"></i>Cadastrar aluno</button><button class="nav-item" type="button" data-target="restricoes"><i class="fas fa-ban"></i>Restrições</button></details>
        <details class="nav-group" data-sidebar-group="consultas"><summary class="nav-label">Consultas</summary><button class="nav-item" type="button" data-target="consulta-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saída antecipada</button><button class="nav-item" type="button" data-target="consulta-estagio"><i class="fas fa-user-tie"></i>Saídas para estágio</button><button class="nav-item" type="button" data-target="consulta-ausencias"><i class="fas fa-user-xmark"></i>Ausências Registradas</button><button class="nav-item" type="button" data-target="atrasos"><i class="fas fa-clock-rotate-left"></i>Atrasos registrados</button><button class="nav-item" type="button" data-target="ultimas-saidas"><i class="fas fa-clock"></i>Últimas Saídas</button></details>
        <details class="nav-group" data-sidebar-group="relatorios"><summary class="nav-label">Relatórios</summary><button class="nav-item report-nav" type="button" data-target="relatorio-dia"><i class="fas fa-clock-rotate-left"></i>Atrasos registrados</button><button class="nav-item report-nav" type="button" data-target="relatorio-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saídas antecipadas</button><button class="nav-item report-nav" type="button" data-target="relatorio-estagio"><i class="fas fa-user-tie"></i>Saídas para estágio</button><a class="nav-item report-nav" href="relatorios/pre_estagio.php" target="_blank" rel="noopener"><i class="fas fa-graduation-cap"></i>Preparação para estágio</a><button class="nav-item report-nav" type="button" data-target="qrcode"><i class="fas fa-qrcode"></i>QR Code</button></details>
        <?php } ?>
  </nav>
  <div class="sidebar-bottom"><a class="nav-item logout" href="../model/sessions.php?sair"><i class="fas fa-right-from-bracket"></i>Sair</a></div>
</aside>
<button class="sidebar-collapse-toggle" type="button" aria-label="Ocultar menu lateral" aria-expanded="true" title="Ocultar menu lateral">
    <i class="fas fa-chevron-left" aria-hidden="true"></i>
</button>
<button class="sidebar-scrim" type="button" aria-label="Fechar menu"></button>
<button class="mobile-menu" type="button" aria-label="Abrir menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
<div class="success-toast<?= $mensagemErroToast !== '' ? ' error' : '' ?>" id="successToast" role="status" aria-live="polite" hidden>
    <div class="success-toast-content">
        <span class="success-toast-icon" aria-hidden="true"><i class="fas <?= $mensagemErroToast !== '' ? 'fa-exclamation' : 'fa-check' ?>"></i></span>
        <span class="success-toast-message"><?= htmlspecialchars($mensagemErroToast !== '' ? $mensagemErroToast : $mensagemSucessoToast, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
    <div class="success-toast-progress" aria-hidden="true"></div>
</div>
<div class="stage-exit-confirmation" id="stage-exit-confirmation" role="status" aria-live="assertive" aria-atomic="true" hidden>
    <div class="stage-exit-confirmation-container">
        <div class="stage-exit-confirmation-card">
            <div class="stage-exit-confirmation-icon" aria-hidden="true"><i class="fas fa-check"></i></div>
            <h2 class="stage-exit-confirmation-title" id="stage-exit-confirmation-title"></h2>
            <p class="stage-exit-confirmation-name">Sua saída foi processada e confirmada em nosso sistema.</p>
            <p class="stage-exit-confirmation-details" id="stage-exit-confirmation-details"></p>
            <div class="stage-exit-confirmation-loading">
                Registro confirmado
                <span class="stage-exit-confirmation-dots" aria-hidden="true"><span></span><span></span><span></span></span>
            </div>
        </div>
    </div>
</div>
<main class="main-content"><section class="page-section<?= $sectionInicial === 'inicio' ? ' active' : '' ?>" id="inicio" aria-label="Visão geral">

  

  <div class="<?= $ehRecepcao ? 'reception-dashboard-content' : 'flex-1 container mx-auto px-4 py-8 mt-16' ?>">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8<?= $ehRecepcao ? ' reception-dashboard-heading' : '' ?>">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Sistema de Entradas e Saídas</span>
        </h1>
        <p class="text-gray-600">Gerencie as entradas e saídas dos alunos de forma eficiente</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6<?= $ehRecepcao ? ' reception-dashboard-grid' : '' ?>">

        <a href="#" data-section-target="entrada" class="menu-card reception-card-delay p-6 flex items-center gap-4 group">
          <div class="menu-icon">
            <i class="fas fa-sign-in-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors"><?= $ehRecepcao ? 'Registrar atraso' : 'Registrar Atrasos' ?></h3>
            <p class="text-sm text-gray-500">Registre os atrasos dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="saida" class="menu-card<?= $ehRecepcao ? ' reception-card-exit' : '' ?> p-6 flex items-center gap-4 group">
          <div class="menu-icon<?= $ehRecepcao ? '' : ' bg-red-50 text-red-600' ?>">
            <i class="fas fa-sign-out-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-red-600 transition-colors"><?= $ehRecepcao ? 'Saída antecipada' : 'Registrar Saída' ?></h3>
            <p class="text-sm text-gray-500">Registre a saída dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="estagio" class="menu-card<?= $ehRecepcao ? ' reception-card-internship' : '' ?> p-6 flex items-center gap-4 group">
          <div class="menu-icon<?= $ehRecepcao ? '' : ' bg-purple-50 text-purple-600' ?>">
            <i class="fas fa-briefcase text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors"><?= $ehRecepcao ? 'Saída para estágio' : 'Registrar Saída-Estágio' ?></h3>
            <p class="text-sm text-gray-500">Registre saídas para estágio</p>
          </div>
        </a>

        <?php if (!$ehRecepcao) { ?>
        <a href="#" data-section-target="relatorios" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-yellow-50 text-yellow-600">
            <i class="fas fa-chart-bar text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-yellow-600 transition-colors">Relatórios</h3>
            <p class="text-sm text-gray-500">Visualize relatórios do sistema</p>
          </div>
        </a>

        <a href="#" data-section-target="ultimas-saidas" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-cyan-50 text-cyan-600">
            <i class="fas fa-history text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-cyan-600 transition-colors">Últimas Saídas</h3>
            <p class="text-sm text-gray-500">Acompanhe as últimas saídas registradas</p>
          </div>
        </a>
        <?php } else { ?>
        <a href="#" data-section-target="consulta-ausencias" class="menu-card reception-card-absence p-6 flex items-center gap-4 group">
          <div class="menu-icon">
            <i class="fas fa-user-xmark text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-amber-600 transition-colors">Ausências registradas</h3>
            <p class="text-sm text-gray-500">Consulte os alunos ausentes para estágio</p>
          </div>
        </a>
        <?php } ?>
      </div>
    </div>
  </div>

  

</section>

<?php require __DIR__ . '/restricoes.php'; ?>

<section class="page-section<?= $sectionInicial === 'entrada' ? ' active' : '' ?>" id="entrada" aria-label="Registrar Atrasos">

    
    

    <!-- Main Container -->
    <div class="modal-form-shell modal-form-shell-lg entry-registration-shell">
        <header class="modal-form-header">
            <div class="modal-form-title">
                <div class="modal-form-icon"><i class="fas fa-arrow-right-to-bracket"></i></div>
                <div>
                    <h2>Registrar Atraso do Aluno</h2>
                    <p>Preencha os dados para registrar a entrada do aluno</p>
                </div>
            </div>
        </header>

                <form id="registro-e" action="../control/control_index.php" method="POST" class="modal-form-form" novalidate>
                        <div class="modal-form-scroll">
                        <div class="modal-form-body modal-form-body-registration">
                    <input type="hidden" name="entrada" value="1">
                    <?php if ($mensagemRegistro !== '' && $statusRegistro !== 'success' && $sectionInicial === 'entrada') { ?>
                        <p class="rounded-lg border px-4 py-3 text-sm <?= $classeMensagemRegistro ?>" role="status"><?= htmlspecialchars($mensagemRegistro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } ?>

                    <!-- Seção: Dados do Aluno -->
                    <div class="modal-form-section" style="grid-area: student;">
                        <h3>
                            <i class="fas fa-user-graduate"></i>
                            Dados do Aluno
                        </h3>
                        <div class="form-group">
                            <label for="entrada-id_aluno" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                Nome do Aluno
                            </label>
                            <div class="relative">
                                <div class="custom-select-container">
                                    <div class="select-trigger" id="entrada-select-trigger">
                                        <span class="select-placeholder">Selecione o Nome do Aluno</span>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <div class="select-dropdown" id="entrada-select-dropdown">
                                        <div class="search-container">
                                            <input type="text" id="entrada-search_aluno" placeholder="Digite para pesquisar..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="options-container" id="entrada-options-container">
                                            <?php
                                            $dados = $select->select_alunos();
                                            foreach ($dados as $dado) {
                                                $matriculaAluno = htmlspecialchars((string) ($dado['matricula'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                $turmaAluno = htmlspecialchars($turmasPorId[(int) ($dado['id_turma'] ?? 0)] ?? 'Não informada', ENT_QUOTES, 'UTF-8');
                                                $cursoAluno = htmlspecialchars($cursosPorId[(int) ($dado['id_curso'] ?? 0)] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
                                            ?>
                                                <div class="select-option" data-value="<?= (int) $dado['id_aluno'] ?>" data-nome="<?= strtolower($dado['nome']) ?>" data-matricula="<?= $matriculaAluno ?>" data-turma="<?= $turmaAluno ?>" data-curso="<?= $cursoAluno ?>">
                                                    <?= $dado['nome'] ?>
                                                </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <select id="entrada-id_aluno" name="id_aluno" class="hidden-select" required>
                                        <option value="" disabled selected>Selecione o Nome do Aluno</option>
                                        <?php
                                        foreach ($dados as $dado) {
                                            $matriculaAluno = htmlspecialchars((string) ($dado['matricula'] ?? ''), ENT_QUOTES, 'UTF-8');
                                            $turmaAluno = htmlspecialchars($turmasPorId[(int) ($dado['id_turma'] ?? 0)] ?? 'Não informada', ENT_QUOTES, 'UTF-8');
                                            $cursoAluno = htmlspecialchars($cursosPorId[(int) ($dado['id_curso'] ?? 0)] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
                                        ?>
                                            <option value="<?= (int) $dado['id_aluno'] ?>" data-matricula="<?= $matriculaAluno ?>" data-turma="<?= $turmaAluno ?>" data-curso="<?= $cursoAluno ?>"><?= $dado['nome'] ?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados da Entrada -->
                    <div class="modal-form-section" style="grid-area: entry;">
                        <h3>
                            <i class="fas fa-calendar-alt"></i>
                            Dados do Registro
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="entrada-id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo do Atraso
                                </label>
                                <select id="entrada-id_motivo" name="id_motivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
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
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Usuário
                                </label>
                                <div id="entrada-usuario-fixo" class="entry-fixed-display w-full px-4 py-3 border border-gray-300 rounded-lg bg-white text-gray-900" aria-readonly="true"><?= htmlspecialchars((string) ($_SESSION['nome'] ?? 'Usuário não autenticado') . (!empty($_SESSION['login_teste_local']) ? ' (teste local)' : ''), ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="entrada-data-fixa" class="block text-sm font-medium text-gray-700 mb-2">
                                    Data
                                </label>
                                <input type="date" id="entrada-data-fixa" value="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg" readonly aria-readonly="true" tabindex="-1">
                            </div>
                            <div class="form-group">
                                <label for="entrada-hora-fixa" class="block text-sm font-medium text-gray-700 mb-2">
                                    Horário
                                </label>
                                <input type="time" id="entrada-hora-fixa" value="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('H:i:s') ?>" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg" readonly aria-readonly="true" tabindex="-1">
                            </div>
                        </div>

                    </div>

                    <div class="modal-form-section" style="grid-area: observation;">
                        <h3>
                            <i class="fas fa-comment-dots"></i>
                            Justificativa
                        </h3>
                        <div class="form-group">
                            <label for="entrada-observacao" class="block text-sm font-medium text-gray-700 mb-2">
                                Justificativa
                            </label>
                            <textarea id="entrada-observacao" name="observacao" rows="3" placeholder="Digite a justificativa" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green resize-y"></textarea>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-form-footer">
                <button type="button" class="modal-form-btn ghost" data-section-target="inicio">Cancelar</button>
                <button type="submit" class="modal-form-btn primary">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Registrar Atraso
                </button>
            </div>
        </form>
    </div>

    <div id="entrada-confirmacao" class="entry-confirm-overlay" hidden>
        <section class="entry-confirm-card" role="dialog" aria-modal="true" aria-labelledby="entrada-confirmacao-titulo" aria-describedby="entrada-confirmacao-descricao" tabindex="-1">
            <h2 id="entrada-confirmacao-titulo">Salvar registro de atraso?</h2>
            <p id="entrada-confirmacao-descricao">Imprimir comprovante salva o atraso e abre a impressão. Cancelar fecha sem salvar.</p>
            <p id="entrada-confirmacao-erro" class="entry-confirm-error" role="alert" hidden></p>
            <div class="entry-confirm-actions">
                <button type="button" data-entry-action="cancel">Cancelar</button>
                <button type="button" class="primary" data-entry-action="print"><i class="fas fa-print" aria-hidden="true"></i> Imprimir comprovante</button>
            </div>
        </section>
    </div>
    <!-- Footer -->
    

    

</section>

<script>
(() => {
$(document).ready(function() {
            $('#entrada .js-example-basic-single').select2({
                placeholder: 'Selecione o aluno',
                allowClear: true,
                dropdownParent: $('#entrada'),
                width: '100%',
                language: 'pt-BR',
                minimumResultsForSearch: 0
            });
        });
        // Custom Select Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectTrigger = document.getElementById('entrada-select-trigger');
            const selectDropdown = document.getElementById('entrada-select-dropdown');
            const searchInput = document.getElementById('entrada-search_aluno');
            const optionsContainer = document.getElementById('entrada-options-container');
            const selectOptions = optionsContainer.querySelectorAll('.select-option');
            const hiddenSelect = document.getElementById('entrada-id_aluno');
            const placeholder = selectTrigger.querySelector('.select-placeholder');

            hiddenSelect.addEventListener('invalid', function(event) {
                event.preventDefault();
                selectDropdown.classList.add('active');
                selectTrigger.classList.add('active');
                selectTrigger.style.borderColor = '#dc2626';
                searchInput.focus();
            });

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
                    hiddenSelect.dispatchEvent(new Event('change', {bubbles: true}));

                    // Update trigger display
                    placeholder.textContent = text;
                    placeholder.style.color = '#374151';
                    selectTrigger.style.borderColor = '';

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

        // Validação em tempo real e confirmação antes de persistir a entrada.
        const form = document.getElementById('registro-e');
        const requiredFields = ['id_aluno', 'id_motivo'];
        const inputs = form.querySelectorAll('input, select');
        const fixedDateField = document.getElementById('entrada-data-fixa');
        const fixedTimeField = document.getElementById('entrada-hora-fixa');
        let savingEntry = false;

        function updateFixedEntryDateTime() {
            const now = new Date();
            const timeZone = 'America/Sao_Paulo';
            const dateParts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
                timeZone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(now).map(part => [part.type, part.value]));
            const timeParts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hourCycle: 'h23'
            }).formatToParts(now).map(part => [part.type, part.value]));
            fixedDateField.value = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
            fixedTimeField.value = `${timeParts.hour}:${timeParts.minute}:${timeParts.second}`;
        }

        updateFixedEntryDateTime();
        window.setInterval(updateFixedEntryDateTime, 1000);

        class ModalConfirmacaoEntrada {
            constructor(root) {
                this.root = root;
                this.buttons = [...root.querySelectorAll('[data-entry-action]')];
                this.error = root.querySelector('#entrada-confirmacao-erro');
                this.previousFocus = null;
                root.addEventListener('keydown', event => this.handleKeydown(event));
                root.addEventListener('click', event => {
                    if (event.target === root && !savingEntry) this.close();
                });
            }

            open() {
                this.previousFocus = document.activeElement;
                this.error.hidden = true;
                this.root.hidden = false;
                this.buttons[0].focus();
            }

            close() {
                this.root.hidden = true;
                this.previousFocus?.focus();
            }

            setSaving(saving) {
                this.buttons.forEach(button => button.disabled = saving);
            }

            showError(message) {
                this.error.textContent = message;
                this.error.hidden = false;
            }

            handleKeydown(event) {
                if (event.key === 'Escape' && !savingEntry) {
                    event.preventDefault();
                    this.close();
                    return;
                }
                if (event.key !== 'Tab') return;
                const first = this.buttons[0];
                const last = this.buttons[this.buttons.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }

        const confirmationModal = new ModalConfirmacaoEntrada(document.getElementById('entrada-confirmacao'));

        function validateEntryForm() {
            let firstInvalid = null;
            requiredFields.forEach(name => {
                const field = form.elements.namedItem(name);
                const valid = Boolean(field && String(field.value).trim());
                const group = field.closest('.form-group');
                let error = group.querySelector('.entry-field-error');
                field.setAttribute('aria-invalid', valid ? 'false' : 'true');
                if (name === 'id_aluno') {
                    const trigger = document.getElementById('entrada-select-trigger');
                    trigger.classList.toggle('entry-select-invalid', !valid);
                    trigger.setAttribute('aria-invalid', valid ? 'false' : 'true');
                } else {
                    field.classList.toggle('entry-invalid', !valid);
                }
                if (!valid) {
                    if (!error) {
                        error = document.createElement('span');
                        error.className = 'entry-field-error';
                        error.textContent = 'Este campo é obrigatório.';
                        error.setAttribute('role', 'alert');
                        group.append(error);
                    }
                    firstInvalid ||= name === 'id_aluno' ? document.getElementById('entrada-select-trigger') : field;
                } else if (error) {
                    error.remove();
                }
            });
            firstInvalid?.focus();
            return !firstInvalid;
        }

        function selectedText(name) {
            const field = form.elements.namedItem(name);
            return field?.selectedOptions?.[0]?.textContent.trim() || '';
        }

        function captureReceiptData() {
            const alunoSelecionado = form.elements.namedItem('id_aluno').selectedOptions[0];
            const opcaoAlunoVisivel = document.querySelector('#entrada-options-container .select-option.selected');
            const turma = opcaoAlunoVisivel?.dataset.turma || alunoSelecionado?.dataset.turma || '';
            const curso = opcaoAlunoVisivel?.dataset.curso || alunoSelecionado?.dataset.curso || 'Não informado';
            const ano = turma.match(/\d+/)?.[0];
            return {
                aluno: opcaoAlunoVisivel?.textContent.trim() || selectedText('id_aluno'),
                matricula: opcaoAlunoVisivel?.dataset.matricula || alunoSelecionado?.dataset.matricula || '',
                turma: ano ? `${ano}º ${curso}` : curso,
                motivo: selectedText('id_motivo'),
                observacao: form.elements.namedItem('observacao').value.trim()
            };
        }

        function resetEntryForm() {
            form.reset();
            form.querySelectorAll('.entry-field-error').forEach(error => error.remove());
            form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
            form.querySelectorAll('.entry-invalid').forEach(field => field.classList.remove('entry-invalid'));
            const trigger = document.getElementById('entrada-select-trigger');
            trigger.classList.remove('entry-select-invalid');
            trigger.querySelector('.select-placeholder').textContent = 'Selecione o Nome do Aluno';
            trigger.querySelector('.select-placeholder').style.color = '';
            document.querySelectorAll('#entrada-options-container .select-option').forEach(option => option.classList.remove('selected'));
        }

        async function saveEntry(printReceipt = false) {
            if (savingEntry) return;
            savingEntry = true;
            confirmationModal.setSaving(true);
            confirmationModal.error.hidden = true;
            const receiptData = printReceipt ? captureReceiptData() : null;
            const printWindow = printReceipt ? window.prepareThermalReceiptWindow() : null;
            const payload = new FormData(form);
            payload.set('entrada_ajax', '1');
            try {
                const response = await fetch(form.action, {method: 'POST', body: payload, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Não foi possível salvar o registro.');
                confirmationModal.close();
                showToast(result.message);
                resetEntryForm();
                if (receiptData && result.tipo_registro !== 'estagio') window.printEntryReceipt(receiptData, printWindow);
                else window.cancelThermalReceiptWindow(printWindow);
                if (result.tipo_registro === 'estagio') {
                    document.dispatchEvent(new CustomEvent('stage-exit-recorded'));
                } else {
                    document.dispatchEvent(new CustomEvent('manual-delay-recorded'));
                }
            } catch (error) {
                window.cancelThermalReceiptWindow(printWindow);
                confirmationModal.showError(error.message || 'Falha de comunicação ao salvar. Tente novamente.');
            } finally {
                savingEntry = false;
                confirmationModal.setSaving(false);
                if (!confirmationModal.root.hidden) confirmationModal.buttons[0].focus();
            }
        }

        function clearEntryFieldError(field) {
            if (!String(field.value).trim()) return;
            field.closest('.form-group')?.querySelector('.entry-field-error')?.remove();
            field.removeAttribute('aria-invalid');
            field.classList.remove('entry-invalid', 'border-red-500');
            field.classList.add('border-gray-300');
            if (field.name === 'id_aluno') {
                const trigger = document.getElementById('entrada-select-trigger');
                trigger.classList.remove('entry-select-invalid');
                trigger.removeAttribute('aria-invalid');
            }
        }

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                    clearEntryFieldError(this);
                }
            });

            input.addEventListener('input', () => clearEntryFieldError(input));
            input.addEventListener('change', () => clearEntryFieldError(input));
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateEntryForm()) confirmationModal.open();
        });
        confirmationModal.buttons.find(button => button.dataset.entryAction === 'cancel').addEventListener('click', () => confirmationModal.close());
        confirmationModal.buttons.find(button => button.dataset.entryAction === 'print').addEventListener('click', () => saveEntry(true));
})();
</script>

<section class="page-section" id="saida" aria-label="Registrar Saída">

    
    

    <!-- Main Container -->
    <div class="modal-form-shell modal-form-shell-lg exit-registration-shell">
        <header class="modal-form-header">
            <div class="modal-form-title">
                <div class="modal-form-icon"><i class="fas fa-arrow-right-from-bracket"></i></div>
                <div>
                    <h2>Registrar Saída do Aluno</h2>
                    <p>Preencha os dados para registrar a saída do aluno</p>
                </div>
            </div>
        </header>

                <form id="registro-s" action="../control/control_index.php" method="POST" class="modal-form-form" novalidate>
                    <div class="modal-form-scroll registration-form-scroll">
                        <div class="modal-form-body modal-form-body-registration exit-registration-body">
                    <input type="hidden" name="saida" value="1">
                    <?php if ($mensagemRegistro !== '' && $statusRegistro !== 'success' && $sectionInicial === 'saida') { ?>
                        <p class="rounded-lg border px-4 py-3 text-sm <?= $classeMensagemRegistro ?>" role="status"><?= htmlspecialchars($mensagemRegistro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } ?>
                    
                    <!-- Seção: Dados do Aluno -->
                    <div class="modal-form-section" style="grid-area: student;">
                        <h3>
                            <i class="fas fa-user-graduate"></i>
                            Dados do Aluno
                        </h3>
                        <div class="form-group">
                            <label for="saida-id_aluno" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                Nome do Aluno
                            </label>
                            <div class="relative">
                                <div class="custom-select-container">
                                    <div class="select-trigger" id="saida-select-trigger">
                                        <span class="select-placeholder">Selecione o Nome do Aluno</span>
                                        <i class="fas fa-chevron-down select-arrow"></i>
                                    </div>
                                    <div class="select-dropdown" id="saida-select-dropdown">
                                        <div class="search-container">
                                            <input type="text" id="saida-search_aluno" placeholder="Digite para pesquisar..." class="search-input">
                                            <i class="fas fa-search search-icon"></i>
                                        </div>
                                        <div class="options-container" id="saida-options-container">
                                            <?php
                                            $dados = $select->select_alunos();
                                            foreach ($dados as $dado) {
                                                $matriculaAluno = htmlspecialchars((string) ($dado['matricula'] ?? ''), ENT_QUOTES, 'UTF-8');
                                                $turmaAluno = htmlspecialchars($turmasPorId[(int) ($dado['id_turma'] ?? 0)] ?? 'Não informada', ENT_QUOTES, 'UTF-8');
                                                $cursoAluno = htmlspecialchars($cursosPorId[(int) ($dado['id_curso'] ?? 0)] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
                                            ?>
                                            <div class="select-option" data-value="<?=$dado['id_aluno']?>" data-nome="<?=strtolower($dado['nome'])?>" data-matricula="<?= $matriculaAluno ?>" data-turma="<?= $turmaAluno ?>" data-curso="<?= $cursoAluno ?>">
                                                <?=$dado['nome']?>
                                            </div>
                                            <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <select id="saida-id_aluno" name="id_aluno" class="hidden-select" required>
                                        <option value="" disabled selected>Selecione o Nome do Aluno</option>
                                        <?php
                                        foreach ($dados as $dado) {
                                            $matriculaAluno = htmlspecialchars((string) ($dado['matricula'] ?? ''), ENT_QUOTES, 'UTF-8');
                                            $turmaAluno = htmlspecialchars($turmasPorId[(int) ($dado['id_turma'] ?? 0)] ?? 'Não informada', ENT_QUOTES, 'UTF-8');
                                            $cursoAluno = htmlspecialchars($cursosPorId[(int) ($dado['id_curso'] ?? 0)] ?? 'Não informado', ENT_QUOTES, 'UTF-8');
                                        ?>
                                        <option value="<?=$dado['id_aluno']?>" data-matricula="<?= $matriculaAluno ?>" data-turma="<?= $turmaAluno ?>" data-curso="<?= $cursoAluno ?>"><?=$dado['nome']?></option>
                                        <?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-form-section" style="grid-area: observation;">
                        <h3>
                            <i class="fas fa-comment-dots"></i>
                            Justificativa
                        </h3>
                        <div class="form-group">
                            <label for="saida-observacao" class="block text-sm font-medium text-gray-700 mb-2">
                                Justificativa
                            </label>
                            <textarea id="saida-observacao" name="observacao" rows="2" placeholder="Digite a justificativa" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green resize-y"></textarea>
                        </div>
                    </div>

                    <!-- Seção: Dados do Responsável -->
                    <div class="modal-form-section" style="grid-area: responsible;">
                        <h3>
                            <i class="fas fa-user-shield"></i>
                            Quem autoriza?
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="saida-nome_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Nome do Responsável
                                </label>
                                <input type="text" id="saida-nome_responsavel" name="nome_responsavel" 
                                       placeholder="Digite o nome completo" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="saida-id_tipo_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Responsável
                                </label>
                                <select id="saida-id_tipo_responsavel" name="id_tipo_responsavel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
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
                    <div class="modal-form-section" style="grid-area: companion;">
                        <h3>
                            <i class="fas fa-car"></i>
                            Dados do Conducente
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="saida-nome_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Conducente
                                </label>
                                <input type="text" id="saida-nome_conducente" name="nome_conducente" 
                                       placeholder="Digite o nome do conducente" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green">
                            </div>
                            <div class="form-group">
                                <label for="saida-id_tipo_conducente" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Conducente
                                </label>
                                <select id="saida-id_tipo_conducente" name="id_tipo_conducente" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
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
                    <div class="modal-form-section" style="grid-area: exit;">
                        <h3>
                            <i class="fas fa-calendar-alt"></i>
                            Dados da Saída
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="saida-id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo da Saída
                                </label>
                                <select id="saida-id_motivo" name="id_motivo" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
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
                                <label for="saida-usuario-fixo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Usuário
                                </label>
                                <input type="text" id="saida-usuario-fixo" value="<?= htmlspecialchars((string) ($_SESSION['nome'] ?? 'Usuário não autenticado') . (!empty($_SESSION['login_teste_local']) ? ' (teste local)' : ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg" readonly aria-readonly="true" tabindex="-1">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="saida-data-fixa" class="block text-sm font-medium text-gray-700 mb-2">
                                    Data
                                </label>
                                <input type="date" id="saida-data-fixa" value="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d') ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg" readonly aria-readonly="true" tabindex="-1">
                            </div>
                            <div class="form-group">
                                <label for="saida-hora-fixa" class="block text-sm font-medium text-gray-700 mb-2">
                                    Horário
                                </label>
                                <input type="time" id="saida-hora-fixa" value="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('H:i:s') ?>" step="1" class="w-full px-4 py-3 border border-gray-300 rounded-lg" readonly aria-readonly="true" tabindex="-1">
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <div class="modal-form-footer">
                <button type="button" class="modal-form-btn ghost" data-section-target="inicio">Cancelar</button>
                <button type="submit" class="modal-form-btn primary">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Registrar Saída
                </button>
            </div>
        </form>
    </div>

    <div id="saida-confirmacao" class="entry-confirm-overlay" hidden>
        <section class="entry-confirm-card" role="dialog" aria-modal="true" aria-labelledby="saida-confirmacao-titulo" aria-describedby="saida-confirmacao-descricao" tabindex="-1">
            <h2 id="saida-confirmacao-titulo">Salvar registro de saída?</h2>
            <p id="saida-confirmacao-descricao">Impressão de Saída salva o registro e abre a impressão. Cancelar fecha sem salvar.</p>
            <p id="saida-confirmacao-erro" class="entry-confirm-error" role="alert" hidden></p>
            <div class="entry-confirm-actions">
                <button type="button" data-exit-action="cancel">Cancelar</button>
                <button type="button" class="primary" data-exit-action="print"><i class="fas fa-print" aria-hidden="true"></i> Impressão de Saída</button>
            </div>
        </section>
    </div>


    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Custom Select Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const selectTrigger = document.getElementById('saida-select-trigger');
            const selectDropdown = document.getElementById('saida-select-dropdown');
            const searchInput = document.getElementById('saida-search_aluno');
            const optionsContainer = document.getElementById('saida-options-container');
            const selectOptions = optionsContainer.querySelectorAll('.select-option');
            const hiddenSelect = document.getElementById('saida-id_aluno');
            const placeholder = selectTrigger.querySelector('.select-placeholder');

            hiddenSelect.addEventListener('invalid', function(event) {
                event.preventDefault();
                selectDropdown.classList.add('active');
                selectTrigger.classList.add('active');
                selectTrigger.style.borderColor = '#dc2626';
                searchInput.focus();
            });

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
                    hiddenSelect.dispatchEvent(new Event('change', {bubbles: true}));
                    
                    // Update trigger display
                    placeholder.textContent = text;
                    placeholder.style.color = '#374151';
                    selectTrigger.style.borderColor = '';
                    
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

        // Validação em tempo real e confirmação antes de persistir a saída.
        const form = document.getElementById('registro-s');
        const requiredFields = ['id_aluno', 'nome_responsavel', 'id_tipo_responsavel', 'id_tipo_conducente', 'id_motivo'];
        const inputs = form.querySelectorAll('input, select');
        const fixedDateField = document.getElementById('saida-data-fixa');
        const fixedTimeField = document.getElementById('saida-hora-fixa');
        let savingExit = false;

        function updateFixedExitDateTime() {
            const now = new Date();
            const timeZone = 'America/Sao_Paulo';
            const dateParts = Object.fromEntries(new Intl.DateTimeFormat('en-CA', {
                timeZone,
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            }).formatToParts(now).map(part => [part.type, part.value]));
            const timeParts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {
                timeZone,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hourCycle: 'h23'
            }).formatToParts(now).map(part => [part.type, part.value]));
            fixedDateField.value = `${dateParts.year}-${dateParts.month}-${dateParts.day}`;
            fixedTimeField.value = `${timeParts.hour}:${timeParts.minute}:${timeParts.second}`;
        }

        updateFixedExitDateTime();
        window.setInterval(updateFixedExitDateTime, 1000);

        class ModalConfirmacaoSaida {
            constructor(root) {
                this.root = root;
                this.buttons = [...root.querySelectorAll('[data-exit-action]')];
                this.error = root.querySelector('#saida-confirmacao-erro');
                this.previousFocus = null;
                root.addEventListener('keydown', event => this.handleKeydown(event));
                root.addEventListener('click', event => {
                    if (event.target === root && !savingExit) this.close();
                });
            }

            open() {
                this.previousFocus = document.activeElement;
                this.error.hidden = true;
                this.root.hidden = false;
                this.buttons[0].focus();
            }

            close() {
                this.root.hidden = true;
                this.previousFocus?.focus();
            }

            setSaving(saving) {
                this.buttons.forEach(button => button.disabled = saving);
            }

            showError(message) {
                this.error.textContent = message;
                this.error.hidden = false;
            }

            handleKeydown(event) {
                if (event.key === 'Escape' && !savingExit) {
                    event.preventDefault();
                    this.close();
                    return;
                }
                if (event.key !== 'Tab') return;
                const first = this.buttons[0];
                const last = this.buttons[this.buttons.length - 1];
                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            }
        }

        const confirmationModal = new ModalConfirmacaoSaida(document.getElementById('saida-confirmacao'));

        function validateExitForm() {
            let firstInvalid = null;
            requiredFields.forEach(name => {
                const field = form.elements.namedItem(name);
                const valid = Boolean(field && String(field.value).trim());
                const group = field.closest('.form-group');
                let error = group.querySelector('.entry-field-error');
                field.setAttribute('aria-invalid', valid ? 'false' : 'true');
                if (name === 'id_aluno') {
                    const trigger = document.getElementById('saida-select-trigger');
                    trigger.classList.toggle('entry-select-invalid', !valid);
                    trigger.setAttribute('aria-invalid', valid ? 'false' : 'true');
                } else {
                    field.classList.toggle('entry-invalid', !valid);
                }
                if (!valid) {
                    if (!error) {
                        error = document.createElement('span');
                        error.className = 'entry-field-error';
                        error.textContent = 'Este campo é obrigatório.';
                        error.setAttribute('role', 'alert');
                        group.append(error);
                    }
                    firstInvalid ||= name === 'id_aluno' ? document.getElementById('saida-select-trigger') : field;
                } else if (error) {
                    error.remove();
                }
            });
            firstInvalid?.focus();
            return !firstInvalid;
        }

        function selectedText(name) {
            const field = form.elements.namedItem(name);
            return field?.selectedOptions?.[0]?.textContent.trim() || '';
        }

        function captureReceiptData() {
            const alunoSelecionado = form.elements.namedItem('id_aluno').selectedOptions[0];
            const opcaoAlunoVisivel = document.querySelector('#saida-options-container .select-option.selected');
            const turma = opcaoAlunoVisivel?.dataset.turma || alunoSelecionado?.dataset.turma || '';
            const curso = opcaoAlunoVisivel?.dataset.curso || alunoSelecionado?.dataset.curso || 'Não informado';
            const ano = turma.match(/\d+/)?.[0];
            return {
                aluno: opcaoAlunoVisivel?.textContent.trim() || selectedText('id_aluno'),
                matricula: opcaoAlunoVisivel?.dataset.matricula || alunoSelecionado?.dataset.matricula || '',
                turma: ano ? `${ano}º ${curso}` : curso,
                responsavel: form.elements.namedItem('nome_responsavel').value.trim(),
                tipoResponsavel: selectedText('id_tipo_responsavel'),
                motivo: selectedText('id_motivo'),
                observacao: form.elements.namedItem('observacao').value.trim()
            };
        }

        function resetExitForm() {
            form.reset();
            form.querySelectorAll('.entry-field-error').forEach(error => error.remove());
            form.querySelectorAll('[aria-invalid]').forEach(field => field.removeAttribute('aria-invalid'));
            form.querySelectorAll('.entry-invalid').forEach(field => field.classList.remove('entry-invalid'));
            const trigger = document.getElementById('saida-select-trigger');
            trigger.classList.remove('entry-select-invalid');
            trigger.querySelector('.select-placeholder').textContent = 'Selecione o Nome do Aluno';
            trigger.querySelector('.select-placeholder').style.color = '';
            document.querySelectorAll('#saida-options-container .select-option').forEach(option => option.classList.remove('selected'));
        }

        async function saveExit(printReceipt = false) {
            if (savingExit) return;
            savingExit = true;
            confirmationModal.setSaving(true);
            confirmationModal.error.hidden = true;
            const receiptData = printReceipt ? captureReceiptData() : null;
            const printWindow = printReceipt ? window.prepareThermalReceiptWindow() : null;
            const payload = new FormData(form);
            payload.set('saida_ajax', '1');
            try {
                const response = await fetch(form.action, {method: 'POST', body: payload, headers: {'X-Requested-With': 'XMLHttpRequest'}});
                const result = await response.json();
                if (!response.ok || !result.success) throw new Error(result.message || 'Não foi possível salvar o registro.');
                confirmationModal.close();
                showToast(result.message);
                resetExitForm();
                if (receiptData) window.printExitReceipt(receiptData, printWindow);
                document.dispatchEvent(new CustomEvent('manual-early-exit-recorded'));
            } catch (error) {
                window.cancelThermalReceiptWindow(printWindow);
                confirmationModal.showError(error.message || 'Falha de comunicação ao salvar. Tente novamente.');
            } finally {
                savingExit = false;
                confirmationModal.setSaving(false);
                if (!confirmationModal.root.hidden) confirmationModal.buttons[0].focus();
            }
        }

        function clearExitFieldError(field) {
            if (!String(field.value).trim()) return;
            field.closest('.form-group')?.querySelector('.entry-field-error')?.remove();
            field.removeAttribute('aria-invalid');
            field.classList.remove('entry-invalid', 'border-red-500');
            field.classList.add('border-gray-300');
            if (field.name === 'id_aluno') {
                const trigger = document.getElementById('saida-select-trigger');
                trigger.classList.remove('entry-select-invalid');
                trigger.removeAttribute('aria-invalid');
            }
        }

        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value) {
                    this.classList.add('border-red-500');
                    this.classList.remove('border-gray-300');
                } else {
                    this.classList.remove('border-red-500');
                    this.classList.add('border-gray-300');
                    clearExitFieldError(this);
                }
            });

            input.addEventListener('input', () => clearExitFieldError(input));
            input.addEventListener('change', () => clearExitFieldError(input));
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (validateExitForm()) confirmationModal.open();
        });
        confirmationModal.buttons.find(button => button.dataset.exitAction === 'cancel').addEventListener('click', () => confirmationModal.close());
        confirmationModal.buttons.find(button => button.dataset.exitAction === 'print').addEventListener('click', () => saveExit(true));
})();
</script>

<section class="page-section" id="estagio" aria-label="Registrar Saída-Estágio">

    
    

    <!-- Main Content -->
    <div class="modal-form-shell modal-form-shell-sm">
        <header class="modal-form-header">
            <div class="modal-form-title">
                <div class="modal-form-icon"><i class="fas fa-briefcase"></i></div>
                <div>
                    <h2>Registro de Saída Estágio</h2>
                    <p>Registre a saída dos alunos para estágio</p>
                </div>
            </div>
        </header>

                <form id="saida-estagio" action="../control/control_index.php" method="POST" class="modal-form-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <div class="modal-form-scroll">
            <div class="modal-form-body">
                <div id="estagio-successMessage" class="hidden mb-6 p-4 bg-green-50 border border-green-200 rounded-lg success-message">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <span class="text-green-700 font-medium">Saída registrada com sucesso!</span>
                    </div>
                </div>

                    <!-- Aluno Selection -->
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-ceara-green text-sm"></i>
                            </div>
                            <label for="estagio-id_aluno" class="text-base font-semibold text-gray-800">
                                Aluno
                            </label>
                        </div>
                        
                        <div class="relative">
                            <select 
                                class="js-example-basic-single w-full p-4 border-2 border-gray-200 rounded-lg focus:border-ceara-green input-focus-ring outline-none transition-all duration-300 text-base bg-white hover:border-gray-300"
                                name="id_aluno" 
                                required
                                id="estagio-id_aluno"
                            >
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunosE();
                                foreach ($dados as $dado) {
                                ?>
                                    <option value="<?= (int) $dado['id_aluno'] ?>" data-matricula="<?= htmlspecialchars((string) $dado['matricula'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $dado['nome'], ENT_QUOTES, 'UTF-8') ?></option>
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
                                <label for="estagio-data" class="block text-xs font-medium text-gray-600">
                                    Data
                                </label>
                                <input 
                                    type="date" 
                                    id="estagio-data"
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

            </div>
          </div>
          <div class="modal-form-footer">
            <a href="#" data-section-target="ultimas-saidas" class="modal-form-btn ghost" title="Ver em tempo real" aria-label="Ver em tempo real"><i class="fas fa-eye"></i></a>
            <button type="button" class="modal-form-btn ghost" data-section-target="inicio">Cancelar</button>
            <button type="submit" id="estagio-submitBtn" name="Registrar" class="modal-form-btn primary">
                <span id="estagio-submitText" class="flex items-center justify-center gap-2">
                    <i class="fas fa-save"></i>
                    Registrar Saída
                </span>
                <span id="submitLoading" class="hidden items-center justify-center gap-2">
                    <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
                    Registrando...
                </span>
            </button>
          </div>
        </form>
    </div>

    <!-- Footer -->
    

    <!-- Modal de Erro -->
    <div id="errorModal" class="app-modal-overlay hidden" aria-hidden="true">
        <div class="app-modal-card error" id="errorModalContent" role="dialog" aria-modal="true" aria-labelledby="errorModalTitle">
            <div class="app-modal-body">
                <div class="app-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18A2 2 0 003.53 21H20.47A2 2 0 0022.18 18L13.71 3.86A2 2 0 0010.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 id="errorModalTitle">Erro!</h3>
                <p id="errorMessage">Ocorreu um erro ao registrar a saída.</p>
            </div>
            <div class="app-modal-actions">
                <button type="button" data-close-modal="error">Tentar Novamente</button>
            </div>
        </div>
    </div>

    <!-- Modal de Aviso -->
    <div id="warningModal" class="app-modal-overlay hidden" aria-hidden="true">
        <div class="app-modal-card warning" id="warningModalContent" role="dialog" aria-modal="true" aria-labelledby="warningModalTitle">
            <div class="app-modal-body">
                <div class="app-modal-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18A2 2 0 003.53 21H20.47A2 2 0 0022.18 18L13.71 3.86A2 2 0 0010.29 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <h3 id="warningModalTitle">Aviso!</h3>
                <p>Este aluno já possui um registro de saída para hoje.</p>
            </div>
            <div class="app-modal-actions">
                <button type="button" data-close-modal="warning">Entendi</button>
            </div>
        </div>
    </div>

    

</section>

<script>
$(document).ready(function() {
            $('#estagio .js-example-basic-single').select2({
                placeholder: 'Selecione o aluno',
                allowClear: true,
                dropdownParent: $('#estagio'),
                width: '100%',
                language: 'pt-BR',
                minimumResultsForSearch: 0
            });
        });

        class SaidaEstagioForm {
            constructor() {
                this.form = document.getElementById('saida-estagio');
                this.fields = {
                    aluno: document.getElementById('estagio-id_aluno'),
                    data: document.getElementById('estagio-data'),
                    hora: document.getElementById('hora')
                };
                this.submitBtn = document.getElementById('estagio-submitBtn');
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
                const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
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

            async handleSubmit(e) {
                if (!this.validateForm()) {
                    e.preventDefault();
                    
                    // Show error modal for validation failures
                    showErrorModal('Por favor, preencha todos os campos obrigatórios.');
                    
                    // Shake effect for invalid form
                    const container = this.form.closest('.page-section').querySelector('.modal-form-shell');
                    container.classList.add('shake');
                    
                    setTimeout(() => {
                        container.classList.remove('shake');
                    }, 500);
                    
                    return;
                }

                e.preventDefault();
                this.showLoadingState();
                const payload = new FormData(this.form);
                payload.set('saida_estagio_ajax', '1');
                try {
                    const response = await fetch(this.form.action, {
                        method: 'POST',
                        body: payload,
                        headers: {'X-Requested-With': 'XMLHttpRequest'}
                    });
                    const result = await response.json();
                    if (!response.ok || !result.success) {
                        throw new Error(result.message || 'Não foi possível registrar a saída.');
                    }

                    this.showSuccessState();
                    document.dispatchEvent(new CustomEvent('stage-exit-recorded'));
                } catch (error) {
                    this.showErrorState(error.message || 'Falha de comunicação ao salvar. Tente novamente.');
                }
            }

            showLoadingState() {
                const submitText = document.getElementById('estagio-submitText');
                const submitLoading = document.getElementById('submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                // Reset button state
                const submitText = document.getElementById('estagio-submitText');
                const submitLoading = document.getElementById('submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                showToast('A saída do aluno foi registrada com sucesso.');
                
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
                const submitText = document.getElementById('estagio-submitText');
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

        function showToast(message, isError = false) {
            const toast = document.getElementById('successToast');
            const toastMessage = toast?.querySelector('.success-toast-message');
            const toastIcon = toast?.querySelector('.success-toast-icon i');
            const progress = toast?.querySelector('.success-toast-progress');

            if (!toast || !toastMessage || !toastIcon || !progress) {
                console.error('Notification elements not found');
                return;
            }

            toastMessage.textContent = message;
            toast.classList.toggle('error', isError);
            toastIcon.className = `fas ${isError ? 'fa-exclamation' : 'fa-check'}`;
            toast.hidden = false;
            toast.classList.remove('visible');
            progress.style.animation = 'none';
            void progress.offsetWidth;
            progress.style.animation = '';
            requestAnimationFrame(() => toast.classList.add('visible'));

            window.setTimeout(() => {
                toast.classList.remove('visible');
                window.setTimeout(() => {
                    toast.hidden = true;
                }, 250);
            }, 4000);
        }

        // Handle URL parameters for success messages
        const urlParams = new URLSearchParams(window.location.search);
        const editStatus = urlParams.get('edit_status');
        const statusEdicaoValido = ['success', 'duplicate', 'justification', 'not_found', 'invalid', 'error'].includes(editStatus);
        if (urlParams.get('status') === 'success' || statusEdicaoValido) {
            const toast = document.getElementById('successToast');
            if (toast) {
                showToast(
                    toast.querySelector('.success-toast-message').textContent,
                    Boolean(statusEdicaoValido && editStatus !== 'success')
                );
            }
            urlParams.delete('status');
            if (statusEdicaoValido) urlParams.delete('edit_status');
            const queryString = urlParams.toString();
            window.history.replaceState(
                {},
                document.title,
                `${window.location.pathname}${queryString ? `?${queryString}` : ''}${window.location.hash}`
            );
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

        const statusRegistro = urlParams.get('status');
        const statusValidacao = urlParams.get('validacao');
        const mensagensValidacao = {
            aprovada: 'A justificativa foi aprovada e o histórico foi atualizado.',
            recusada: 'A justificativa foi recusada e o histórico foi atualizado.',
            ja_processada: 'Este atraso já foi validado ou não está mais disponível.',
            erro: 'Não foi possível enviar a validação. Confira os dados e tente novamente.'
        };
        if (mensagensValidacao[statusValidacao]) {
            setTimeout(() => showToast(mensagensValidacao[statusValidacao], statusValidacao === 'erro' || statusValidacao === 'ja_processada'), 100);
            urlParams.delete('validacao');
            const queryString = urlParams.toString();
            window.history.replaceState(
                {},
                document.title,
                `${window.location.pathname}${queryString ? `?${queryString}` : ''}${window.location.hash}`
            );
        }
        const mensagensErroRegistro = {
            campos_obrigatorios: 'Preencha os campos obrigatórios, incluindo o tipo de acompanhante, para concluir o registro.',
            data_hora_futura: 'Não é permitido registrar uma data ou horário futuro. Escolha um horário até o momento atual.',
            erro_interno: 'Não foi possível gravar o registro no banco de dados. Confira os dados e tente novamente.',
            aluno_nao_encontrado: 'O aluno selecionado não foi encontrado no banco de dados.',
            qr_atraso_estrutura: 'O banco da hospedagem ainda não tem todos os campos de validação. Execute a migration de validação de atraso e tente novamente.',
            qr_atraso_opcoes: 'Faltam as opções padrão de responsável ou motivo no banco. Confira os cadastros de opções e tente novamente.',
            usuario_inativo: 'Não há um usuário autenticado na sessão para registrar. Saia e entre novamente no sistema.',
            erro_desconhecido: 'O registro não foi concluído. Atualize a página e tente novamente.'
        };
        if (mensagensErroRegistro[statusRegistro]) {
            const erroDoQr = ['qr_atraso_estrutura', 'qr_atraso_opcoes', 'usuario_inativo'].includes(statusRegistro);
            setTimeout(() => {
                if (erroDoQr) {
                    showToast(mensagensErroRegistro[statusRegistro], true);
                } else {
                    showErrorModal(mensagensErroRegistro[statusRegistro]);
                }
            }, 100);
        }

        // Modal Functions
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
            if (e.target.id === 'errorModal' || e.target.id === 'warningModal') {
                closeErrorModal();
                closeWarningModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeErrorModal();
                closeWarningModal();
            }
        });

        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const modalType = button.dataset.closeModal;
                if (modalType === 'error') closeErrorModal();
                if (modalType === 'warning') closeWarningModal();
            });
        });
</script>

<section class="page-section" id="relatorios" aria-label="Relatórios">

    

    <div class="flex-1 container mx-auto px-4 ">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatórios do Sistema</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-4">
                <a href="#" data-section-target="relatorio-entrada" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-blue-50 text-blue-600">
    <i class="fas fa-right-to-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Entradas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de entradas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="#" data-section-target="relatorio-saida" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-red-50 text-red-600">
    <i class="fas fa-right-from-bracket text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas dos alunos</p>
                        </div>
                    </div>
                </a>

                <a href="relatorios/relatorio_diario_estagio.php" target="_blank" rel="noopener" class="report-card block w-full p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-violet-50 text-violet-600">
    <i class="fas fa-user-tie text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio</h3>
                            <p class="text-sm text-gray-500">Visualize o histórico de saídas para estágio</p>
                        </div>
                    </div>
                </a>

                <a href="#" data-section-target="relatorio-dia" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-amber-50 text-amber-600">
    <i class="fas fa-clock-rotate-left text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de atrasos registrados</h3>
                            <p class="text-sm text-gray-500">Gere um relatório completo dos atrasos registrados no sistema</p>
                        </div>
                    </div>
                </a>
                
                <a href="relatorios/pre_estagio.php" target="_blank" rel="noopener" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-emerald-50 text-emerald-600">
    <i class="fas fa-graduation-cap text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Preparação para estágio - Frequência</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
                        </div>
                    </div>
                </a>
                
                <a href="#" data-section-target="qrcode" class="report-card block p-6 text-left">
                    <div class="flex items-center gap-4">
<div class="report-icon bg-cyan-50 text-cyan-600">
    <i class="fas fa-qrcode text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório Geral de QRCodes</h3>
                            <p class="text-sm text-gray-500">Gere QRCodes para todos os alunos</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    

</section>

<section class="page-section" id="relatorio-entrada" aria-label="Relatório de Entrada">

    
    

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-sign-in-alt"></i>
                </div>
                <h1 class="gradient-text">Relatório de Entrada</h1>
                <p class="subtitle">Gere relatórios detalhados de entradas no sistema</p>
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
                <div class="tab-content active" id="relatorio-entrada-tab-aluno">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Aluno</label>
                            <select class="js-example-basic-single" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunos();
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

                        <input type="hidden" name="GerarRelatorio" value="por_alunoEntrada">
                        <input type="hidden" name="form_id" value="entrada">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Ano -->
                <div class="tab-content" id="relatorio-entrada-tab-ano">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione o Ano</label>
                            <select name="Ano" class="form-select" required>
                                <option value="" disabled selected>Selecione o ano</option>
                                <option value="1">1° Anos</option>
                                <option value="2">2° Anos</option>
                                <option value="3">3° Anos</option>
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

                        <input type="hidden" name="GerarRelatorio" value="ano_geralEntrada">
                        <input type="hidden" name="form_id" value="entradaA">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Turma -->
                <div class="tab-content" id="relatorio-entrada-tab-turma">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label">Selecione a Turma</label>
                            <select name="Turma" class="form-select" required>
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

                        <input type="hidden" name="GerarRelatorio" value="por_turmaEntrada">
                        <input type="hidden" name="form_id" value="entradaT">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
            <div class="info-card">
                <div class="info-icon">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="info-content">
                    <h3>Informação</h3>
                    <p>Os relatórios de entrada mostram dados sobre o acesso dos estudantes ao sistema. Escolha o período desejado para análises mais precisas.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Initialize Select2 with enhanced options
        $(document).ready(function() {
            $('#relatorio-entrada .js-example-basic-single').select2({
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
            const section = document.getElementById('relatorio-entrada');
            const tabBtns = section.querySelectorAll('.tab-btn');
            const tabContents = section.querySelectorAll('.tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetTab = this.getAttribute('data-tab');

                    // Remove active class from all tabs and contents
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));

                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    section.querySelector('#relatorio-entrada-tab-' + targetTab).classList.add('active');
                });
            });

            // Form validation
            const forms = section.querySelectorAll('form');
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
                        if (!select.value) {
                            isValid = false;
                            select.style.borderColor = '#ef4444';
                            setTimeout(() => {
                                select.style.borderColor = '#e5e7eb';
                            }, 2000);
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                    } else {
                        // Show loading state
                        const button = this.querySelector('.btn-primary');
                        button.disabled = true;
                        button.innerHTML = '<div style="width: 16px; height: 16px; border: 2px solid #ffffff; border-top: 2px solid transparent; border-radius: 50%; animation: spin 1s linear infinite; margin-right: 8px;"></div> Gerando...';
                    }
                });
            });
        });

        // Add spin animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        `;
        document.head.appendChild(style);
})();
</script>

<section class="page-section" id="consulta-saida" aria-label="Consulta de saídas antecipadas" data-exit-consultation data-empty-message="Ainda não há saídas antecipadas registradas.">
    <div class="saida-consulta-shell">
        <header class="saida-consulta-header">
            <div class="saida-consulta-title">
                <span class="saida-consulta-title-mark" aria-hidden="true"><i class="fas fa-person-walking-arrow-right"></i></span>
                <div>
                    <h1>Saídas antecipadas</h1>
                    <p>Consulte os registros com dados do aluno, responsáveis e horário.</p>
                </div>
            </div>
            <div class="saida-consulta-count" aria-live="polite"><i class="fas fa-list-check" aria-hidden="true"></i><span id="saida-consulta-count" data-consulta-count><?= count($saidasAntecipadas) ?></span><span id="saida-consulta-count-label" data-consulta-count-label><?= count($saidasAntecipadas) === 1 ? 'registro encontrado' : 'registros encontrados' ?></span></div>
        </header>

        <div class="saida-consulta-filters" aria-label="Filtros das saídas">
            <label class="saida-consulta-filter" for="saida-consulta-search">Pesquisar
                <input type="search" id="saida-consulta-search" data-consulta-search placeholder="Aluno, responsável, motivo...">
            </label>
            <label class="saida-consulta-filter" for="saida-consulta-turma">Turma
                <select id="saida-consulta-turma" data-consulta-class>
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmasConsultaSaida as $turmaConsulta) { ?>
                        <option value="<?= (int) $turmaConsulta['id_turma'] ?>"><?= htmlspecialchars(ucfirst((string) $turmaConsulta['ano']) . ' ' . strtoupper((string) $turmaConsulta['turma']), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="saida-consulta-filter" for="saida-consulta-data">Data
                <input type="date" id="saida-consulta-data" data-consulta-date>
            </label>
            <button type="button" class="saida-consulta-reset" id="saida-consulta-reset" data-consulta-reset><i class="fas fa-rotate-left" aria-hidden="true"></i>Limpar</button>
        </div>

        <div class="saida-consulta-results">
            <div class="saida-consulta-table-wrap" data-consulta-table-wrap>
                <table class="saida-consulta-table" aria-label="Registros de saídas antecipadas">
                    <thead>
                        <tr><th>Data e hora</th><th>Aluno</th><th>Turma</th><th>Responsável</th><th>Acompanhante</th><th>Motivo</th><th>Registrado por</th><th>Ações</th></tr>
                    </thead>
                    <tbody id="saida-consulta-rows">
                        <?php foreach ($saidasAntecipadas as $registroSaida) {
                            $dataHoraSaida = !empty($registroSaida['date_time']) ? strtotime($registroSaida['date_time']) : false;
                            $rotuloTurmaSaida = trim((string) ($registroSaida['ano'] ?? '') . ' ' . strtoupper((string) ($registroSaida['turma'] ?? '')));
                        ?>
                            <tr data-consulta-row data-consulta-id="<?= (int) $registroSaida['id_registro_saida'] ?>" data-consulta-type="saida" data-turma="<?= (int) ($registroSaida['id_turma'] ?? 0) ?>" data-data="<?= htmlspecialchars(substr((string) ($registroSaida['date_time'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?>">
                                <td data-label="Data e hora"><?= $dataHoraSaida ? date('d/m/Y H:i', $dataHoraSaida) : 'Não informado' ?></td>
                                <td data-label="Aluno"><span class="saida-consulta-primary"><?= htmlspecialchars((string) ($registroSaida['nome'] ?? 'Aluno não identificado'), ENT_QUOTES, 'UTF-8') ?></span><span class="saida-consulta-secondary">Matrícula: <?= htmlspecialchars((string) ($registroSaida['matricula'] ?? 'Não informada'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td data-label="Turma"><?= htmlspecialchars($rotuloTurmaSaida !== '' ? $rotuloTurmaSaida : 'Não informada', ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Responsável"><span class="saida-consulta-primary"><?= htmlspecialchars((string) ($registroSaida['nome_responsavel'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?></span><span class="saida-consulta-secondary"><?= htmlspecialchars((string) ($registroSaida['tipo_responsavel'] ?? 'Tipo não informado'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td data-label="Acompanhante"><span class="saida-consulta-primary"><?= htmlspecialchars((string) ($registroSaida['nome_conducente'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?></span><span class="saida-consulta-secondary"><?= htmlspecialchars((string) ($registroSaida['tipo_conducente'] ?? 'Tipo não informado'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td data-label="Motivo"><?= htmlspecialchars((string) ($registroSaida['motivo'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Registrado por"><?= htmlspecialchars((string) ($registroSaida['administrador'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Ações">
                                    <button
                                        type="button"
                                        class="saida-consulta-page-button"
                                        data-edit-consulta-record
                                        data-edit-record="<?= htmlspecialchars(json_encode([
                                            'tipo' => 'saida',
                                            'id' => (int) $registroSaida['id_registro_saida'],
                                            'aluno' => (int) ($registroSaida['id_aluno'] ?? 0),
                                            'dataHora' => (string) ($registroSaida['date_time'] ?? ''),
                                            'nomeResponsavel' => (string) ($registroSaida['nome_responsavel'] ?? ''),
                                            'tipoResponsavel' => (int) ($registroSaida['id_tipo_responsavel'] ?? 0),
                                            'nomeConducente' => (string) ($registroSaida['nome_conducente'] ?? ''),
                                            'tipoConducente' => (int) ($registroSaida['id_tipo_conducente'] ?? 0),
                                            'motivo' => (int) ($registroSaida['id_motivo'] ?? 0)
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Editar registro de saída"
                                        title="Editar registro"
                                    ><i class="fas fa-pen" aria-hidden="true"></i></button>
                                    <button
                                        type="button"
                                        class="saida-consulta-page-button"
                                        data-delete-consulta-record
                                        data-delete-record="<?= htmlspecialchars(json_encode([
                                            'tipo' => 'saida',
                                            'id' => (int) $registroSaida['id_registro_saida'],
                                            'nome' => (string) ($registroSaida['nome'] ?? ''),
                                            'matricula' => (string) ($registroSaida['matricula'] ?? ''),
                                            'idTurma' => (int) ($registroSaida['id_turma'] ?? 0),
                                            'turma' => $rotuloTurmaSaida,
                                            'dataHora' => (string) ($registroSaida['date_time'] ?? '')
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Excluir registro de saída"
                                        title="Excluir registro"
                                    ><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <p class="saida-consulta-empty" id="saida-consulta-empty" data-consulta-empty hidden><i class="fas fa-clipboard-list" aria-hidden="true"></i><span id="saida-consulta-empty-message" data-consulta-empty-message>Nenhum registro encontrado para esses filtros.</span></p>
        </div>
        <nav class="saida-consulta-pagination" id="saida-consulta-pagination" data-consulta-pagination aria-label="Paginação das saídas antecipadas" hidden>
            <button type="button" class="saida-consulta-page-button" id="saida-consulta-previous" data-consulta-previous aria-label="Página anterior" disabled><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <span id="saida-consulta-page-status" data-consulta-page-status aria-live="polite">Página 1 de 1</span>
            <button type="button" class="saida-consulta-page-button" id="saida-consulta-next" data-consulta-next aria-label="Próxima página" disabled><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </nav>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const pageSize = 25;
    document.querySelectorAll('[data-exit-consultation]').forEach(section => {
        const search = section.querySelector('[data-consulta-search]');
        const turma = section.querySelector('[data-consulta-class]');
        const data = section.querySelector('[data-consulta-date]');
        let rows = [...section.querySelectorAll('[data-consulta-row]')];
        const count = section.querySelector('[data-consulta-count]');
        const countLabel = section.querySelector('[data-consulta-count-label]');
        const tableWrap = section.querySelector('[data-consulta-table-wrap]');
        const empty = section.querySelector('[data-consulta-empty]');
        const emptyMessage = section.querySelector('[data-consulta-empty-message]');
        const pagination = section.querySelector('[data-consulta-pagination]');
        const previousPage = section.querySelector('[data-consulta-previous]');
        const nextPage = section.querySelector('[data-consulta-next]');
        const pageStatus = section.querySelector('[data-consulta-page-status]');
        let currentPage = 1;
        let filteredRows = [];

        const fitCompleteRows = matchingRows => {
            tableWrap.style.flex = '';
            requestAnimationFrame(() => {
                if (tableWrap.hidden || matchingRows.length === 0) {
                    return;
                }

                const availableHeight = tableWrap.getBoundingClientRect().height;
                const tableHead = section.querySelector('thead');
                const borderHeight = parseFloat(getComputedStyle(tableWrap).borderTopWidth)
                    + parseFloat(getComputedStyle(tableWrap).borderBottomWidth);
                let fittedHeight = tableHead.getBoundingClientRect().height + borderHeight;
                const pageRows = matchingRows.slice((currentPage - 1) * pageSize, currentPage * pageSize);

                for (const row of pageRows) {
                    const rowHeight = row.getBoundingClientRect().height;
                    if (fittedHeight + rowHeight > availableHeight) {
                        break;
                    }
                    fittedHeight += rowHeight;
                }

                if (fittedHeight < availableHeight) {
                    tableWrap.style.flex = `0 0 ${fittedHeight}px`;
                }
            });
        };

        const renderPage = matchingRows => {
            const totalPages = Math.ceil(matchingRows.length / pageSize);
            currentPage = Math.min(currentPage, Math.max(totalPages, 1));
            rows.forEach(row => { row.hidden = true; });
            matchingRows
                .slice((currentPage - 1) * pageSize, currentPage * pageSize)
                .forEach(row => { row.hidden = false; });
            pagination.hidden = totalPages <= 1;
            pageStatus.textContent = `Página ${currentPage} de ${totalPages}`;
            previousPage.disabled = currentPage <= 1;
            nextPage.disabled = currentPage >= totalPages;
            tableWrap.hidden = matchingRows.length === 0;
            fitCompleteRows(matchingRows);
        };

        const filterRows = () => {
            rows = [...section.querySelectorAll('[data-consulta-row]')];
            const term = search.value.trim().toLocaleLowerCase('pt-BR');
            filteredRows = rows.filter(row =>
                row.textContent.toLocaleLowerCase('pt-BR').includes(term)
                    && (!turma.value || row.dataset.turma === turma.value)
                    && (!data.value || row.dataset.data === data.value)
            );
            count.textContent = filteredRows.length;
            countLabel.textContent = filteredRows.length === 1 ? 'registro encontrado' : 'registros encontrados';
            emptyMessage.textContent = rows.length === 0
                ? section.dataset.emptyMessage
                : 'Nenhum registro corresponde aos filtros aplicados.';
            empty.hidden = filteredRows.length > 0;
            currentPage = 1;
            renderPage(filteredRows);
        };

        section.refreshConsultationRows = filterRows;

        previousPage.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage -= 1;
                renderPage(filteredRows);
            }
        });
        nextPage.addEventListener('click', () => {
            if (currentPage < Math.ceil(filteredRows.length / pageSize)) {
                currentPage += 1;
                renderPage(filteredRows);
            }
        });

        search.addEventListener('input', filterRows);
        [turma, data].forEach(filter => filter.addEventListener('change', filterRows));
        section.querySelector('[data-consulta-reset]').addEventListener('click', () => {
            search.value = '';
            turma.value = '';
            data.value = '';
            filterRows();
        });
        window.addEventListener('resize', () => renderPage(filteredRows));
        filterRows();
    });
});
</script>

<section class="page-section" id="consulta-estagio" aria-label="Consulta de saídas de estágio" data-exit-consultation data-empty-message="Ainda não há saídas de estágio registradas.">
    <div class="saida-consulta-shell">
        <header class="saida-consulta-header">
            <div class="saida-consulta-title">
                <span class="saida-consulta-title-mark" aria-hidden="true"><i class="fas fa-user-tie"></i></span>
                <div>
                    <h1>Saídas de estágio</h1>
                    <p>Consulte o histórico de saídas de estágio por aluno, turma e data.</p>
                </div>
            </div>
            <div class="saida-consulta-count" aria-live="polite"><i class="fas fa-list-check" aria-hidden="true"></i><span data-consulta-count><?= count($saidasEstagio) ?></span><span data-consulta-count-label><?= count($saidasEstagio) === 1 ? 'registro encontrado' : 'registros encontrados' ?></span></div>
        </header>

        <div class="saida-consulta-filters" aria-label="Filtros das saídas de estágio">
            <label class="saida-consulta-filter" for="estagio-consulta-search">Pesquisar
                <input type="search" id="estagio-consulta-search" data-consulta-search placeholder="Nome ou matrícula do aluno...">
            </label>
            <label class="saida-consulta-filter" for="estagio-consulta-turma">Turma
                <select id="estagio-consulta-turma" data-consulta-class>
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmasConsultaSaida as $turmaConsulta) { ?>
                        <option value="<?= (int) $turmaConsulta['id_turma'] ?>"><?= htmlspecialchars(ucfirst((string) $turmaConsulta['ano']) . ' ' . strtoupper((string) $turmaConsulta['turma']), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php } ?>
                </select>
            </label>
            <label class="saida-consulta-filter" for="estagio-consulta-data">Data
                <input type="date" id="estagio-consulta-data" data-consulta-date>
            </label>
            <button type="button" class="saida-consulta-reset" data-consulta-reset><i class="fas fa-rotate-left" aria-hidden="true"></i>Limpar</button>
        </div>

        <div class="saida-consulta-results">
            <div class="saida-consulta-table-wrap" data-consulta-table-wrap>
                <table class="saida-consulta-table" aria-label="Registros de saídas de estágio">
                    <thead>
                        <tr><th>Data e hora</th><th>Aluno</th><th>Turma</th><th>Registrado por</th><th>Ações</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($saidasEstagio as $registroEstagio) {
                            $dataHoraEstagio = !empty($registroEstagio['dae']) ? strtotime($registroEstagio['dae']) : false;
                            $rotuloTurmaEstagio = trim((string) ($registroEstagio['ano'] ?? '') . ' ' . strtoupper((string) ($registroEstagio['turma'] ?? '')));
                        ?>
                            <tr data-consulta-row data-consulta-id="<?= (int) $registroEstagio['id_saida'] ?>" data-consulta-type="estagio" data-turma="<?= (int) ($registroEstagio['id_turma'] ?? 0) ?>" data-data="<?= htmlspecialchars(substr((string) ($registroEstagio['dae'] ?? ''), 0, 10), ENT_QUOTES, 'UTF-8') ?>">
                                <td data-label="Data e hora"><?= $dataHoraEstagio ? date('d/m/Y H:i', $dataHoraEstagio) : 'Não informado' ?></td>
                                <td data-label="Aluno"><span class="saida-consulta-primary"><?= htmlspecialchars((string) ($registroEstagio['nome'] ?? 'Aluno não identificado'), ENT_QUOTES, 'UTF-8') ?></span><span class="saida-consulta-secondary">Matrícula: <?= htmlspecialchars((string) ($registroEstagio['matricula'] ?? 'Não informada'), ENT_QUOTES, 'UTF-8') ?></span></td>
                                <td data-label="Turma"><?= htmlspecialchars($rotuloTurmaEstagio !== '' ? $rotuloTurmaEstagio : 'Não informada', ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Registrado por"><?= htmlspecialchars((string) ($registroEstagio['usuario_nome'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-label="Ações">
                                    <button
                                        type="button"
                                        class="saida-consulta-page-button"
                                        data-edit-consulta-record
                                        data-edit-record="<?= htmlspecialchars(json_encode([
                                            'tipo' => 'estagio',
                                            'id' => (int) $registroEstagio['id_saida'],
                                            'aluno' => (int) ($registroEstagio['id_aluno'] ?? 0),
                                            'dataHora' => (string) ($registroEstagio['dae'] ?? '')
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Editar registro de saída para estágio"
                                        title="Editar registro"
                                    ><i class="fas fa-pen" aria-hidden="true"></i></button>
                                    <button
                                        type="button"
                                        class="saida-consulta-page-button"
                                        data-delete-consulta-record
                                        data-delete-record="<?= htmlspecialchars(json_encode([
                                            'tipo' => 'estagio',
                                            'id' => (int) $registroEstagio['id_saida'],
                                            'nome' => (string) ($registroEstagio['nome'] ?? ''),
                                            'matricula' => (string) ($registroEstagio['matricula'] ?? ''),
                                            'idTurma' => (int) ($registroEstagio['id_turma'] ?? 0),
                                            'turma' => $rotuloTurmaEstagio,
                                            'dataHora' => (string) ($registroEstagio['dae'] ?? '')
                                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="Excluir registro de saída de estágio"
                                        title="Excluir registro"
                                    ><i class="fas fa-trash" aria-hidden="true"></i></button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <p class="saida-consulta-empty" data-consulta-empty hidden><i class="fas fa-clipboard-list" aria-hidden="true"></i><span data-consulta-empty-message>Nenhum registro encontrado para esses filtros.</span></p>
        </div>
        <nav class="saida-consulta-pagination" data-consulta-pagination aria-label="Paginação das saídas de estágio" hidden>
            <button type="button" class="saida-consulta-page-button" data-consulta-previous aria-label="Página anterior" disabled><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <span data-consulta-page-status aria-live="polite">Página 1 de 1</span>
            <button type="button" class="saida-consulta-page-button" data-consulta-next aria-label="Próxima página" disabled><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </nav>
    </div>
</section>

<section class="page-section" id="consulta-ausencias" aria-label="Ausências registradas">
    <style>
        #consulta-ausencias .absence-shell{padding:24px;border:1px solid #e1e8e3;border-radius:10px;background:#fff;box-shadow:0 2px 8px #143b2110}
        #consulta-ausencias .absence-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;padding-bottom:20px;border-bottom:1px solid #e7ece8}
        #consulta-ausencias .absence-title{display:flex;align-items:center;gap:14px}
        #consulta-ausencias .absence-school-name{display:none}
        #consulta-ausencias .absence-print-title{display:none}
        #consulta-ausencias .absence-signature{display:none}
        #consulta-ausencias .absence-title-mark{display:grid;width:44px;height:44px;flex:0 0 auto;place-items:center;border-radius:8px;background:#fff0ed;color:#b42318;font-size:18px}
        #consulta-ausencias h1{margin:0;color:#14251c;font-size:24px;font-weight:700}
        #consulta-ausencias .absence-lead,#consulta-ausencias .absence-report-date{margin:5px 0 0;color:#64736a;font-size:13px}
        #consulta-ausencias .absence-count{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid #e7ece8;border-radius:7px;color:#33443a;font-size:13px;white-space:nowrap}
        #consulta-ausencias .absence-count strong{color:#b42318;font-size:18px;font-variant-numeric:tabular-nums}
        #consulta-ausencias .absence-controls{display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;padding:18px 0}
        #consulta-ausencias .absence-field{display:grid;gap:6px;color:#53615a;font-size:12px;font-weight:600}
        #consulta-ausencias .absence-field input,#consulta-ausencias .absence-field select{min-width:200px;height:40px;padding:0 11px;border:1px solid #d8e0da;border-radius:6px;background:#fff;color:#24352b;font:400 13px 'Inter',sans-serif}
        #consulta-ausencias .absence-report{height:40px;padding:0 15px;border:0;border-radius:6px;background:#08723a;color:#fff;font:600 13px 'Inter',sans-serif;cursor:pointer}
        #consulta-ausencias .absence-report:disabled{cursor:not-allowed;opacity:.5}
        #consulta-ausencias .absence-notice{min-height:20px;margin:0 0 12px;color:#64736a;font-size:13px}
        #consulta-ausencias .absence-notice[data-state="warning"]{color:#9a5500}
        #consulta-ausencias .absence-table-wrap{overflow-x:auto;border:1px solid #e7ece8;border-radius:7px}
        #consulta-ausencias .absence-table{width:100%;border-collapse:collapse}
        #consulta-ausencias .absence-table th,#consulta-ausencias .absence-table td{padding:11px 14px;border-bottom:1px solid #edf0ee;text-align:left;font-size:13px}
        #consulta-ausencias .absence-table th{background:#f4f8f3;color:#53615a;font-size:11px;font-weight:700;text-transform:uppercase}
        #consulta-ausencias .absence-table tr:last-child td{border-bottom:0}
        #consulta-ausencias .absence-empty{padding:30px 14px;color:#64736a;text-align:center;font-size:14px}
        @media(max-width:600px){#consulta-ausencias .absence-shell{padding:16px}#consulta-ausencias .absence-header{flex-direction:column}#consulta-ausencias .absence-count{align-self:flex-start}#consulta-ausencias .absence-controls{align-items:stretch;flex-direction:column}#consulta-ausencias .absence-field input,#consulta-ausencias .absence-field select,#consulta-ausencias .absence-report{width:100%}}
        @media print{
            @page{size:80mm auto;margin:0}
            html,body{width:80mm!important;min-width:80mm!important;margin:0!important;padding:0!important;background:#fff!important}
            body *{visibility:hidden!important}
            body.printing-absence-report .thermal-receipt,body.printing-absence-report .thermal-receipt *{display:none!important;visibility:hidden!important}
            #consulta-ausencias,#consulta-ausencias *{visibility:visible!important}
            #consulta-ausencias,#consulta-ausencias *{font-family:monospace!important;font-size:10pt!important;line-height:1.35!important;color:#000!important;letter-spacing:0!important}
            #consulta-ausencias{position:absolute!important;top:0!important;left:0!important;display:block!important;width:80mm!important;max-width:80mm!important;margin:0!important;padding:0!important;background:#fff!important;color:#000!important;font:10pt/1.35 monospace!important}
            #consulta-ausencias .absence-shell{width:80mm;min-height:0;padding:3mm;border:0;border-radius:0;box-shadow:none;background:#fff;color:#000;font:inherit;box-sizing:border-box}
            #consulta-ausencias .absence-header{display:block;padding:0 0 3mm;border:0;border-bottom:1px dashed #000;text-align:center}
            #consulta-ausencias .absence-title{display:block}
            #consulta-ausencias .absence-title-mark{display:none}
            #consulta-ausencias .absence-school-name{display:block;margin:0 0 3mm;font:inherit;text-align:center}
            #consulta-ausencias .absence-print-title{display:block;margin:0 0 1mm;font-weight:bold!important;text-align:center;text-transform:uppercase}
            #consulta-ausencias h1{display:none}
            #consulta-ausencias .absence-lead,#consulta-ausencias .absence-report-date{margin:1mm 0 0;color:#000;font:inherit;text-align:center}
            #consulta-ausencias .absence-count{display:block;margin-top:3mm;padding:0;border:0;border-radius:0;color:#000;font:inherit;text-align:left;white-space:normal}
            #consulta-ausencias .absence-count i{display:none}
            #consulta-ausencias .absence-count strong{color:#000;font:inherit;font-weight:bold}
            #consulta-ausencias .absence-controls,#consulta-ausencias .absence-notice{display:none!important}
            #consulta-ausencias .absence-table-wrap{overflow:visible;border:0;border-radius:0}
            #consulta-ausencias .absence-table{width:100%;border-collapse:collapse;font:inherit}
            #consulta-ausencias .absence-table thead{display:none}
            #consulta-ausencias .absence-table tr{display:block;padding:2mm 0;border-bottom:1px dashed #000;break-inside:avoid}
            #consulta-ausencias .absence-table td{display:block;padding:0;border:0;color:#000;text-align:left;font:inherit;overflow-wrap:anywhere}
            #consulta-ausencias .absence-table td::before{font-weight:bold}
            #consulta-ausencias .absence-table td:nth-child(1)::before{content:"# ";}
            #consulta-ausencias .absence-table td:nth-child(2)::before{content:"Aluno: ";}
            #consulta-ausencias .absence-table td:nth-child(3)::before{content:"Matrícula: ";}
            #consulta-ausencias .absence-table td:nth-child(4)::before{content:"Turma: ";}
            #consulta-ausencias .absence-table td:nth-child(5)::before{content:"Situação: ";}
            #consulta-ausencias .absence-empty{padding:3mm 0;color:#000;text-align:left;font:inherit}
            #consulta-ausencias .absence-signature{display:block;margin-top:12mm;padding-top:2mm;border-top:1px solid #000;text-align:center}
            body:not(.printing-absence-report) #consulta-ausencias{display:none!important}
        }
    </style>
    <div class="absence-shell" data-absence-consultation>
        <header class="absence-header">
            <div class="absence-title">
                <span class="absence-title-mark" aria-hidden="true"><i class="fas fa-user-xmark"></i></span>
                <div><p class="absence-print-title">Comprovante de ausências</p><p class="absence-school-name">ESCOLA SALABERGA</p><h1>Ausências Registradas</h1><p class="absence-lead">Alunos sem saída por QR Code registrada até 13h20.</p><p class="absence-report-date" data-absence-report-date></p></div>
            </div>
            <div class="absence-count" aria-live="polite"><i class="fas fa-list-check" aria-hidden="true"></i><strong data-absence-count>0</strong><span data-absence-count-label>ausentes</span></div>
        </header>
        <div class="absence-controls" aria-label="Filtros de ausências">
            <label class="absence-field" for="absence-date">Data
                <input type="date" id="absence-date" data-absence-date value="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" max="<?= htmlspecialchars((new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="absence-field" for="absence-class">Turma
                <select id="absence-class" data-absence-class>
                    <option value="">Todas as turmas</option>
                    <?php foreach ($turmasConsultaSaida as $turmaConsulta) {
                        if (!in_array((int) $turmaConsulta['id_turma'], [9, 10, 11, 12], true)) continue;
                    ?>
                        <option value="<?= (int) $turmaConsulta['id_turma'] ?>"><?= htmlspecialchars(ucfirst((string) $turmaConsulta['ano']) . ' ' . strtoupper((string) $turmaConsulta['turma']), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php } ?>
                </select>
            </label>
            <button type="button" class="absence-report" data-absence-print disabled><i class="fas fa-print" aria-hidden="true"></i> Gerar relatório</button>
        </div>
        <p class="absence-notice" data-absence-notice role="status" aria-live="polite">Carregando ausências...</p>
        <div class="absence-table-wrap" data-absence-table-wrap hidden>
            <table class="absence-table" aria-label="Alunos ausentes">
                <thead><tr><th>#</th><th>Aluno</th><th>Matrícula</th><th>Turma</th><th>Situação</th></tr></thead>
                <tbody data-absence-rows></tbody>
            </table>
        </div>
        <p class="absence-empty" data-absence-empty hidden>Nenhuma ausência registrada para os filtros selecionados.</p>
        <p class="absence-signature">Assinatura do responsável</p>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const section = document.querySelector('[data-absence-consultation]');
    if (!section) return;

    const dateInput = section.querySelector('[data-absence-date]');
    const classInput = section.querySelector('[data-absence-class]');
    const count = section.querySelector('[data-absence-count]');
    const countLabel = section.querySelector('[data-absence-count-label]');
    const notice = section.querySelector('[data-absence-notice]');
    const rowsContainer = section.querySelector('[data-absence-rows]');
    const tableWrap = section.querySelector('[data-absence-table-wrap]');
    const empty = section.querySelector('[data-absence-empty]');
    const printButton = section.querySelector('[data-absence-print]');
    const reportDate = section.querySelector('[data-absence-report-date]');
    const csrfToken = <?= json_encode($_SESSION['csrf_token'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    let requestSequence = 0;

    const mostrarData = value => {
        const [year, month, day] = value.split('-');
        return `${day}/${month}/${year}`;
    };

    const consultarAusencias = async () => {
        const sequence = ++requestSequence;
        const formData = new FormData();
        formData.set('acao', 'listar_ausencias_estagio');
        formData.set('csrf_token', csrfToken);
        formData.set('data', dateInput.value);
        formData.set('id_turma', classInput.value);
        reportDate.textContent = `Data da apuração: ${mostrarData(dateInput.value)}`;
        notice.textContent = 'Consultando ausências...';
        notice.dataset.state = '';
        rowsContainer.replaceChildren();
        tableWrap.hidden = true;
        empty.hidden = true;
        count.textContent = '0';
        countLabel.textContent = 'ausentes';
        printButton.disabled = true;

        try {
            const response = await fetch('../control/control_index.php', {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            });
            const resultado = await response.json();
            if (sequence !== requestSequence) return;
            if (!response.ok || !resultado.success) {
                throw new Error(resultado.message || 'Não foi possível consultar as ausências.');
            }

            notice.textContent = resultado.message || 'Apuração concluída para esta data.';
            notice.dataset.state = resultado.apuracao_disponivel ? '' : 'warning';
            printButton.disabled = !resultado.apuracao_disponivel;
            const ausencias = resultado.ausencias || [];
            count.textContent = String(ausencias.length);
            countLabel.textContent = ausencias.length === 1 ? 'ausente' : 'ausentes';

            if (ausencias.length === 0 && resultado.apuracao_disponivel) {
                empty.hidden = false;
                return;
            }
            if (ausencias.length === 0) return;

            ausencias.forEach((aluno, index) => {
                const row = document.createElement('tr');
                [String(index + 1), aluno.nome, aluno.matricula || 'Não informada', aluno.nome_turma, 'Ausente'].forEach(value => {
                    const cell = document.createElement('td');
                    cell.textContent = value || 'Não informado';
                    row.appendChild(cell);
                });
                rowsContainer.appendChild(row);
            });
            tableWrap.hidden = false;
        } catch (error) {
            if (sequence !== requestSequence) return;
            notice.textContent = error.message || 'Não foi possível consultar as ausências.';
            notice.dataset.state = 'warning';
        }
    };

    dateInput.addEventListener('change', consultarAusencias);
    classInput.addEventListener('change', consultarAusencias);
    printButton.addEventListener('click', () => {
        document.body.classList.add('printing-absence-report');
        window.addEventListener('afterprint', () => document.body.classList.remove('printing-absence-report'), {once: true});
        window.print();
    });
    consultarAusencias();
});
</script>

<dialog class="edit-record-dialog" id="consultaEditDialog" aria-labelledby="consultaEditTitle">
    <header class="edit-record-header">
        <div class="edit-record-title">
            <span class="edit-record-title-icon" aria-hidden="true"><i class="fas fa-pen-to-square"></i></span>
            <div>
                <h2 id="consultaEditTitle">Editar registro</h2>
                <p>Atualize os dados e salve as alterações.</p>
            </div>
        </div>
    </header>
    <form class="edit-record-form" action="../control/control_index.php" method="post">
        <input type="hidden" name="acao" value="editar_registro_consulta">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="tipo_registro" id="consulta-edit-type">
        <input type="hidden" name="id_registro" id="consulta-edit-id">
        <div class="edit-record-fields">
            <label class="edit-record-field" for="consulta-edit-aluno">Aluno
                <select id="consulta-edit-aluno" name="id_aluno" required>
                    <option value="">Selecione o aluno</option>
                    <?php foreach ($alunosConsultaEdicao as $alunoEdicao) { ?>
                        <option value="<?= (int) $alunoEdicao['id_aluno'] ?>"><?= htmlspecialchars((string) $alunoEdicao['nome'] . ' — ' . (string) $alunoEdicao['matricula'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php } ?>
                </select>
            </label>
            <div class="edit-record-grid">
                <label class="edit-record-field" for="consulta-edit-date">Data
                    <input type="date" id="consulta-edit-date" name="data" required>
                </label>
                <label class="edit-record-field" for="consulta-edit-time">Hora
                    <input type="time" id="consulta-edit-time" name="hora" required>
                </label>
            </div>
            <div id="consulta-edit-saida-fields" class="edit-record-fields" style="padding:0;overflow:visible">
                <label class="edit-record-field" for="consulta-edit-responsavel">Nome do responsável
                    <input type="text" id="consulta-edit-responsavel" name="nome_responsavel" maxlength="100" required>
                </label>
                <div class="edit-record-grid">
                    <label class="edit-record-field" for="consulta-edit-tipo-responsavel">Tipo de responsável
                        <select id="consulta-edit-tipo-responsavel" name="id_tipo_responsavel" required>
                            <option value="">Selecione</option>
                            <?php foreach ($tiposResponsaveisEdicao as $tipoResponsavelEdicao) { ?>
                                <option value="<?= (int) $tipoResponsavelEdicao['id_tipo_responsavel'] ?>"><?= htmlspecialchars((string) $tipoResponsavelEdicao['tipo'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php } ?>
                        </select>
                    </label>
                    <label class="edit-record-field" for="consulta-edit-tipo-conducente">Tipo de acompanhante
                        <select id="consulta-edit-tipo-conducente" name="id_tipo_conducente" required>
                            <option value="">Selecione</option>
                            <?php foreach ($tiposConducentesEdicao as $tipoConducenteEdicao) { ?>
                                <option value="<?= (int) $tipoConducenteEdicao['id_tipo_conducente'] ?>"><?= htmlspecialchars((string) $tipoConducenteEdicao['tipo'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php } ?>
                        </select>
                    </label>
                </div>
                <label class="edit-record-field" for="consulta-edit-conducente">Nome do acompanhante
                    <input type="text" id="consulta-edit-conducente" name="nome_conducente" maxlength="100">
                </label>
                <label class="edit-record-field" for="consulta-edit-motivo">Motivo
                    <select id="consulta-edit-motivo" name="id_motivo" required>
                        <option value="">Selecione o motivo</option>
                        <?php foreach ($motivosEdicao as $motivoEdicao) { ?>
                            <option value="<?= (int) $motivoEdicao['id_motivo'] ?>"><?= htmlspecialchars((string) $motivoEdicao['motivo'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php } ?>
                    </select>
                </label>
            </div>
        </div>
        <footer class="edit-record-footer">
            <button type="button" class="edit-record-button" data-edit-dialog-close>Cancelar</button>
            <button type="submit" class="edit-record-button primary"><i class="fas fa-check" aria-hidden="true"></i>Salvar alterações</button>
        </footer>
    </form>
</dialog>

<script>
(() => {
    const dialog = document.getElementById('consultaEditDialog');
    const form = dialog?.querySelector('form');
    const alunoSelect = document.getElementById('consulta-edit-aluno');
    const saidaFields = document.getElementById('consulta-edit-saida-fields');
    const title = document.getElementById('consultaEditTitle');
    if (!dialog || !form || !alunoSelect || !saidaFields || !title) return;

    document.addEventListener('click', event => {
        const editButton = event.target.closest('[data-edit-consulta-record]');
        if (editButton) {
            let record;
            try {
                record = JSON.parse(editButton.dataset.editRecord);
            } catch (error) {
                console.error('Não foi possível carregar os dados do registro para edição.', error);
                return;
            }

            form.elements.tipo_registro.value = record.tipo;
            form.elements.id_registro.value = String(record.id);
            form.elements.data.value = record.dataHora.slice(0, 10);
            form.elements.hora.value = record.dataHora.slice(11, 16);
            saidaFields.hidden = record.tipo !== 'saida';
            saidaFields.querySelectorAll('input, select').forEach(field => {
                field.disabled = record.tipo !== 'saida';
            });
            title.textContent = record.tipo === 'estagio' ? 'Editar saída de estágio' : 'Editar saída antecipada';

            if (window.jQuery && jQuery.fn.select2) {
                const $alunoSelect = jQuery(alunoSelect);
                if (!$alunoSelect.hasClass('select2-hidden-accessible')) {
                    $alunoSelect.select2({
                        dropdownParent: jQuery(dialog),
                        width: '100%',
                        placeholder: 'Pesquise e selecione o aluno'
                    });
                }
                $alunoSelect.val(String(record.aluno)).trigger('change');
            } else {
                alunoSelect.value = String(record.aluno);
            }

            if (record.tipo === 'saida') {
                form.elements.nome_responsavel.value = record.nomeResponsavel;
                form.elements.id_tipo_responsavel.value = String(record.tipoResponsavel);
                form.elements.nome_conducente.value = record.nomeConducente;
                form.elements.id_tipo_conducente.value = String(record.tipoConducente);
                form.elements.id_motivo.value = String(record.motivo);
            }
            dialog.showModal();
            return;
        }

        if (event.target.closest('[data-edit-dialog-close]')) {
            dialog.close();
        }
        if (event.target === dialog) {
            dialog.close();
        }
    });
})();
</script>

<section class="page-section" id="relatorio-saida" aria-label="Relatório de Saída">
    <div class="main-content">
        <div class="container">
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-person-walking-arrow-right"></i>
                </div>
                <h1 class="gradient-text">Relatório de Saída-Antecipada</h1>
                <p class="subtitle">Gere relatórios detalhados de saídas antecipadas</p>
            </div>

            <div class="form-card">
                <div class="tabs-nav">
                    <button class="tab-btn active" type="button" data-tab="aluno">
                        <i class="fas fa-user"></i> Por Aluno
                    </button>
                    <button class="tab-btn" type="button" data-tab="ano">
                        <i class="fas fa-calendar-alt"></i> Por Ano
                    </button>
                    <button class="tab-btn" type="button" data-tab="turma">
                        <i class="fas fa-users"></i> Por Turma
                    </button>
                </div>

                <div class="tab-content active" id="relatorio-saida-tab-aluno">
                    <form target="_blank" id="relatorio-saida-form" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="relatorio-saida-id_aluno">Selecione o Aluno</label>
                            <select id="relatorio-saida-id_aluno" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $dados = $select->select_alunos();
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

                        <input type="hidden" name="GerarRelatorio" value="por_alunoSaida">
                        <input type="hidden" name="form_id" value="entrada">
                        <button type="submit" name="btn" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <div class="tab-content" id="relatorio-saida-tab-ano">
                    <form target="_blank" id="saidaA" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="ano">Selecione o Ano</label>
                            <select id="ano" name="Ano" required>
                                <option value="" disabled selected>Selecione o ano</option>
                                <option value="1">1° Anos</option>
                                <option value="2">2° Anos</option>
                                <option value="3">3° Anos</option>
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

                        <input type="hidden" name="GerarRelatorio" value="ano_geralSaida">
                        <input type="hidden" name="form_id" value="entradaA">
                        <button type="submit" name="btn" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <div class="tab-content" id="relatorio-saida-tab-turma">
                    <form target="_blank" id="saidaT" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="Turma">Selecione a Turma</label>
                            <select id="Turma" name="Turma" required>
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

                        <input type="hidden" name="GerarRelatorio" value="por_turmaSaida">
                        <input type="hidden" name="form_id" value="saidaT">
                        <button type="submit" name="btn" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="page-section" id="relatorio-estagio" aria-label="Relatório de Saídas-Estágio">

    
    

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Title Section -->
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-briefcase"></i>
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
                <div class="tab-content active" id="relatorio-estagio-tab-aluno">
                    <form target="_blank" action="../control/control_index.php" method="POST">
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

                        <input type="hidden" name="GerarRelatorio" value="por_alunoEstagio">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Ano -->
                <div class="tab-content" id="relatorio-estagio-tab-ano">
                    <form target="_blank" action="../control/control_index.php" method="POST">
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

                        <input type="hidden" name="GerarRelatorio" value="3_ano_geralEstagio">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>

                <!-- Por Turma -->
                <div class="tab-content" id="relatorio-estagio-tab-turma">
                    <form target="_blank" action="../control/control_index.php" method="POST">
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

                        <input type="hidden" name="GerarRelatorio" value="por_turmaEstagio">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-file-export"></i>
                            Gerar Relatório
                        </button>
                    </form>
                </div>
            </div>

            <!-- Info Card -->
           
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
// Initialize Select2 with enhanced options
        $(document).ready(function() {
            $('#relatorio-estagio .js-example-basic-single').select2({
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
            ['relatorio-dia', 'relatorio-saida', 'relatorio-estagio'].forEach(sectionId => {
                const section = document.getElementById(sectionId);
                const tabBtns = section.querySelectorAll('.tab-btn');
                const tabContents = section.querySelectorAll('.tab-content');

                tabBtns.forEach(btn => {
                    btn.addEventListener('click', function() {
                        const targetTab = this.getAttribute('data-tab');

                        tabBtns.forEach(tabBtn => tabBtn.classList.remove('active'));
                        tabContents.forEach(content => content.classList.remove('active'));

                        this.classList.add('active');
                        section.querySelector('#' + sectionId + '-tab-' + targetTab).classList.add('active');
                    });
                });

                const forms = section.querySelectorAll('form');
                forms.forEach(form => {
                    form.addEventListener('submit', function(e) {
                        const radios = form.querySelectorAll('input[type="radio"]');
                        const selects = form.querySelectorAll('select');
                        let isValid = true;
                        const radioGroups = {};

                        radios.forEach(radio => {
                            const name = radio.name;
                            if (!radioGroups[name]) radioGroups[name] = [];
                            radioGroups[name].push(radio);
                        });

                        Object.values(radioGroups).forEach(group => {
                            if (!group.some(radio => radio.checked)) isValid = false;
                        });

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
        });
})();
</script>

<section class="page-section" id="relatorio-dia" aria-label="Relatório de atrasos registrados">
    <div class="main-content">
        <div class="container">
            <div class="title-section">
                <div class="icon-container">
                    <i class="fas fa-clock-rotate-left"></i>
                </div>
                <h1 class="gradient-text">Relatório de atrasos registrados</h1>
                <p class="subtitle">Filtre por aluno, ano ou turma e acompanhe as justificativas e validações.</p>
            </div>

            <div class="form-card">
                <div class="tabs-nav">
                    <button class="tab-btn active" type="button" data-tab="aluno">
                        <i class="fas fa-user"></i> Por Aluno
                    </button>
                    <button class="tab-btn" type="button" data-tab="ano">
                        <i class="fas fa-calendar-alt"></i> Por Ano
                    </button>
                    <button class="tab-btn" type="button" data-tab="turma">
                        <i class="fas fa-users"></i> Por Turma
                    </button>
                </div>

                <?php if (isset($_GET['error'])) { ?>
                    <p class="report-error" role="alert">Não foi possível gerar o relatório. Confira os filtros e tente novamente.</p>
                <?php } ?>

                <div class="tab-content active" id="relatorio-dia-tab-aluno">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="relatorio-atrasos-id-aluno">Selecione o Aluno</label>
                            <select id="relatorio-atrasos-id-aluno" name="id_aluno" required>
                                <option value="" disabled selected>Selecione o aluno</option>
                                <?php
                                $alunosComAtrasos = $select->select_alunosE();
                                foreach ($alunosComAtrasos as $alunoAtraso) {
                                ?>
                                    <option value="<?= (int) $alunoAtraso['id_aluno'] ?>"><?= htmlspecialchars($alunoAtraso['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required><span class="radio-custom"></span><span class="radio-label">Dia Atual</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 30 Dias</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 12 Meses</span></label>
                            </div>
                        </div>
                        <input type="hidden" name="GerarRelatorio" value="atrasos_por_aluno">
                        <button type="submit" class="btn-primary"><i class="fas fa-file-export"></i> Gerar Relatório</button>
                    </form>
                </div>

                <div class="tab-content" id="relatorio-dia-tab-ano">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="relatorio-atrasos-ano">Selecione o Ano</label>
                            <select id="relatorio-atrasos-ano" name="Ano" required>
                                <option value="" disabled selected>Selecione o ano</option>
                                <option value="3">3º Ano</option>
                            </select>
                            <p class="report-hint">Os atrasos registrados são contabilizados para as turmas do 3º ano.</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required><span class="radio-custom"></span><span class="radio-label">Dia Atual</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 30 Dias</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 12 Meses</span></label>
                            </div>
                        </div>
                        <input type="hidden" name="GerarRelatorio" value="atrasos_por_ano">
                        <button type="submit" class="btn-primary"><i class="fas fa-file-export"></i> Gerar Relatório</button>
                    </form>
                </div>

                <div class="tab-content" id="relatorio-dia-tab-turma">
                    <form target="_blank" action="../control/control_index.php" method="POST">
                        <div class="form-group">
                            <label class="form-label" for="relatorio-atrasos-turma">Selecione a Turma</label>
                            <select id="relatorio-atrasos-turma" name="Turma" required>
                                <option value="" disabled selected>Selecione a turma</option>
                                <option value="9">3º Ano A</option>
                                <option value="10">3º Ano B</option>
                                <option value="11">3º Ano C</option>
                                <option value="12">3º Ano D</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Período do Relatório</label>
                            <div class="radio-group">
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="dia_atual" class="radio-input" required><span class="radio-custom"></span><span class="radio-label">Dia Atual</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_30_dias" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 30 Dias</span></label>
                                <label class="radio-item"><input type="radio" name="tipo_relatorio" value="ultimos_12_meses" class="radio-input"><span class="radio-custom"></span><span class="radio-label">Últimos 12 Meses</span></label>
                            </div>
                        </div>
                        <input type="hidden" name="GerarRelatorio" value="atrasos_por_turma">
                        <button type="submit" class="btn-primary"><i class="fas fa-file-export"></i> Gerar Relatório</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="page-section" id="qrcode" aria-label="Gerar QR Codes">

    
    

    <!-- Main Content -->
    <div class="flex-1 flex items-center justify-center px-4 pt-16">
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
                <form target="_blank" id="turmaForm" action="QRCode/qrcode.php" method="post" class="space-y-6">
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
                        <div id="qrcode-turma-error" class="hidden text-red-500 text-xs flex items-center mt-1">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="qrcode-submitBtn"
                        aria-label="Gerar QR Code"
                        title="Gerar QR Code"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="qrcode-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode" aria-hidden="true"></i>
                            Gerar QR Code
                        </span>
                        <span id="qrcode-submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Gerando...
                        </span>
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
class TurmaSelector {
            constructor() {
                this.form = document.getElementById('turmaForm');
                this.select = document.getElementById('turmajs');
                this.submitBtn = document.getElementById('qrcode-submitBtn');
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
                const errorElement = document.getElementById('qrcode-turma-error');
                
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
                const submitText = document.getElementById('qrcode-submitText');
                const submitLoading = document.getElementById('qrcode-submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;

                // Add pulse effect to the form
                const formCard = document.getElementById('qrcode').querySelector('.form-card');
                formCard.classList.add('pulse-effect');

                // The PDF opens in a new tab, so this page needs to clear its own loading state.
                window.setTimeout(() => {
                    submitText.classList.remove('hidden');
                    submitLoading.classList.add('hidden');
                    this.submitBtn.disabled = false;
                    formCard.classList.remove('pulse-effect');
                }, 2000);
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
})();
</script>

<section class="page-section" id="atrasos" aria-label="Atrasos registrados">
    <div class="delays-shell">
        <header class="delays-heading">
            <div class="delays-topline">
                <h1 class="delays-title gradient-text">Atrasos registrados</h1>
                <time class="delays-clock" id="atrasos-relogio" aria-live="off"></time>
            </div>
            <p class="delays-note"><i class="fas fa-circle-info" aria-hidden="true"></i> Registros entre 07:40 e 11:40 são considerados atraso.</p>
            <p data-qr-reader-status class="mt-2 max-w-full break-all text-sm text-gray-500" role="status" aria-live="polite">Leitor pronto para receber um crachá.</p>
            <?php if (!$validacaoAtrasosDisponivel) { ?>
                <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-gray-700" role="status">A estrutura do banco não está pronta para habilitar a validação. Consulte o README para configurar o banco de dados.</p>
            <?php } ?>
        </header>
        <div class="delays-grid">
            <?php foreach ([9 => 'A', 10 => 'B', 11 => 'C', 12 => 'D'] as $idTurma => $turma) { ?>
                <?php $registrosTurma = $atrasosPorTurma[$idTurma]; ?>
                <article class="delay-card" aria-label="Atrasos do 3º Ano <?= $turma ?>">
                    <div class="delay-card-header turma-3<?= strtolower($turma) ?>">
                        <div class="delay-card-heading">
                            <h2><i class="fas fa-users" aria-hidden="true"></i><span>3º Ano <?= $turma ?></span></h2>
                            <span class="delay-count"><?= count($registrosTurma) ?></span>
                        </div>
                        <input class="delay-search" type="search" data-delay-search="<?= strtolower($turma) ?>" placeholder="Buscar aluno..." aria-label="Buscar aluno do 3º Ano <?= $turma ?>">
                    </div>
                    <div class="delay-list" id="atrasos-turma-<?= strtolower($turma) ?>">
                        <?php if ($registrosTurma) { ?>
                            <?php foreach ($registrosTurma as $registroAtraso) { ?>
                                <?php $jsonDetalhes = json_encode($detalhesAtraso($registroAtraso), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE); ?>
                                <div class="delay-entry" data-delay-entry data-name="<?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>" data-delay-record-id="<?= (int) ($registroAtraso['id_registro_entrada'] ?? 0) ?>" data-delay-matricula="<?= htmlspecialchars((string) ($registroAtraso['matricula'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-delay-turma="3º Ano <?= htmlspecialchars(strtoupper((string) $registroAtraso['letra_turma']), ENT_QUOTES, 'UTF-8') ?>" data-delay-datetime="<?= htmlspecialchars((string) $registroAtraso['date_time'], ENT_QUOTES, 'UTF-8') ?>" tabindex="0" role="button" aria-label="Opções para o atraso de <?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>" title="Clique para ver as opções deste registro">
                                    <div class="delay-entry-name"><?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="delay-entry-meta">
                                        <time><?= date('H:i', strtotime($registroAtraso['date_time'])) ?></time>
                                            <?php $statusAtraso = $registroAtraso['status_justificativa'] ?? 'pendente'; $acaoAtraso = $statusAtraso === 'pendente' && $validacaoAtrasosDisponivel ? 'Validar' : 'Detalhes'; ?>
                                            <button type="button" class="delay-detail-button <?= $acaoAtraso === 'Detalhes' ? 'delay-detail-button-secondary' : '' ?>" data-delay-details="<?= htmlspecialchars($jsonDetalhes, ENT_QUOTES, 'UTF-8') ?>" data-record-id="<?= (int) ($registroAtraso['id_registro_entrada'] ?? 0) ?>" data-motive-id="<?= (int) ($registroAtraso['id_motivo'] ?? 0) ?>" data-status="<?= htmlspecialchars($statusAtraso, ENT_QUOTES, 'UTF-8') ?>" data-can-validate="<?= $validacaoAtrasosDisponivel && $statusAtraso === 'pendente' ? '1' : '0' ?>"><?= $acaoAtraso === 'Validar' ? '<i class="fas fa-check" aria-hidden="true"></i>' : '<i class="fas fa-eye" aria-hidden="true"></i>' ?><?= htmlspecialchars($acaoAtraso, ENT_QUOTES, 'UTF-8') ?></button>
                                    </div>
                                </div>
                            <?php } ?>
                            <p class="delay-empty" data-delay-no-results hidden>Nenhum aluno encontrado</p>
                        <?php } else { ?>
                            <p class="delay-empty">Nenhum atraso registrado hoje</p>
                        <?php } ?>
                    </div>
                </article>
            <?php } ?>
        </div>

        <section class="delay-history" aria-labelledby="historico-atrasos-titulo">
            <h2 id="historico-atrasos-titulo">Histórico de atrasos</h2>
            <p class="delay-history-note">Consulte os registros anteriores por turma, aluno, data e situação.</p>
            <div class="delay-history-grid" id="delay-history-grid">
                <?php foreach ([9 => 'A', 10 => 'B', 11 => 'C', 12 => 'D'] as $idTurma => $turma) { ?>
                    <article class="delay-history-card turma-3<?= strtolower($turma) ?>" data-history-card="<?= $idTurma ?>">
                        <header class="delay-history-card-header"><h3><i class="fas fa-users" aria-hidden="true"></i> 3º Ano <?= $turma ?></h3><span class="delay-history-count" data-history-count><?= count($historicoPorTurma[$idTurma]) ?></span></header>
                        <div class="delay-history-card-filters" aria-label="Filtros do 3º Ano <?= $turma ?>">
                            <label class="delay-history-filter">Aluno<input type="search" data-history-filter-name placeholder="Buscar aluno"></label>
                            <label class="delay-history-filter">Situação<select data-history-filter-status><option value="todas">Todas</option><option value="pendente">Pendente</option><option value="aprovada">Aprovada</option><option value="recusada">Recusada</option></select></label>
                            <label class="delay-history-filter">Data<input type="date" data-history-filter-date max="<?= (new DateTimeImmutable('now', new DateTimeZone('America/Sao_Paulo')))->format('Y-m-d') ?>" inputmode="none" autocomplete="off"></label>
                            <button type="button" class="delay-history-reset" data-history-filter-reset><i class="fas fa-rotate-left" aria-hidden="true"></i> Limpar</button>
                        </div>
                        <div class="delay-history-list">
                            <?php if ($historicoPorTurma[$idTurma]) { ?>
                                <?php foreach ($historicoPorTurma[$idTurma] as $registroAtraso) { ?>
                                    <?php $jsonDetalhes = json_encode($detalhesAtraso($registroAtraso), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE); $statusHistorico = $registroAtraso['status_justificativa'] ?? 'pendente'; ?>
                                    <article class="delay-history-entry" data-history-entry data-history-class="<?= (int) $registroAtraso['id_turma'] ?>" data-history-date="<?= date('Y-m-d', strtotime($registroAtraso['date_time'])) ?>" data-history-status="<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>" data-history-name="<?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>" tabindex="0" role="group" aria-label="Registro histórico de atraso de <?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="delay-history-entry-name-group"><div class="delay-history-entry-name"><?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?></div><button type="button" class="delay-print-button" data-delay-print="<?= htmlspecialchars($jsonDetalhes, ENT_QUOTES, 'UTF-8') ?>" aria-label="Imprimir registro de atraso de <?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>" title="Imprimir registro de atraso"><i class="fas fa-print" aria-hidden="true"></i></button></div>
                                        <div class="delay-history-entry-meta"><time datetime="<?= date('Y-m-d\TH:i:s', strtotime($registroAtraso['date_time'])) ?>"><?= date('d/m/Y', strtotime($registroAtraso['date_time'])) ?> às <?= date('H:i', strtotime($registroAtraso['date_time'])) ?></time><span class="delay-status delay-status-<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'][$statusHistorico] ?? 'Pendente', ENT_QUOTES, 'UTF-8') ?></span></div>
                                        <div class="delay-history-entry-actions">
                                            <?php $podeValidarHistorico = $validacaoAtrasosDisponivel && $statusHistorico === 'pendente'; ?>
                                            <button type="button" class="delay-detail-button delay-history-entry-action <?= $podeValidarHistorico ? '' : 'delay-detail-button-secondary' ?>" data-delay-details="<?= htmlspecialchars($jsonDetalhes, ENT_QUOTES, 'UTF-8') ?>" data-record-id="<?= (int) $registroAtraso['id_registro_entrada'] ?>" data-motive-id="<?= (int) ($registroAtraso['id_motivo'] ?? 0) ?>" data-status="<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>" data-can-validate="<?= $podeValidarHistorico ? '1' : '0' ?>"><i class="fas <?= $podeValidarHistorico ? 'fa-check' : 'fa-eye' ?>" aria-hidden="true"></i> <?= $podeValidarHistorico ? 'Validar' : 'Ver detalhes' ?></button>
                                        </div>
                                    </article>
                                <?php } ?>
                                <p class="delay-history-empty" data-history-no-results hidden>Nenhum registro encontrado com esses filtros.</p>
                            <?php } else { ?>
                                <p class="delay-history-empty">Nenhum atraso registrado nesta turma.</p>
                            <?php } ?>
                        </div>
                    </article>
                <?php } ?>
            </div>
        </section>
    </div>
    <dialog class="delay-dialog" id="delay-details-dialog" aria-labelledby="delay-dialog-title">
        <div class="delay-dialog-header">
            <div class="delay-dialog-heading"><div><h2 id="delay-dialog-title">Validar atraso</h2><p id="delay-dialog-subtitle">Confirme o registro do atraso e informe sua avaliação.</p></div></div>
        </div>
        <div class="delay-dialog-content" id="delay-dialog-content"></div>
        <form class="delay-validation-form border-t border-gray-200 px-5 py-4" id="delay-validation-form" action="../control/control_index.php" method="post" hidden>
            <input type="hidden" name="acao" value="validar_justificativa_atraso">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_registro_entrada" id="delay-record-id">
            <div class="delay-form-field"><span class="delay-form-label">Avaliação</span><input type="hidden" id="delay-decision" name="decisao"><div class="delay-decision-options" role="group" aria-label="Avaliação da justificativa"><button class="delay-decision-option delay-decision-option-approve" type="button" data-delay-decision="aprovada" aria-pressed="false"><i class="fas fa-check" aria-hidden="true"></i>Aprovar</button><button class="delay-decision-option delay-decision-option-reject" type="button" data-delay-decision="recusada" aria-pressed="false"><i class="fas fa-xmark" aria-hidden="true"></i>Recusar</button></div></div>
            <div class="delay-form-field" id="delay-validation-motive-field"><label class="delay-form-label" for="delay-validation-motive">Motivo</label><select class="delay-form-control" id="delay-validation-motive" name="id_motivo" form="delay-validation-form" required><option value="">Selecione o motivo</option><?php foreach ($motivosEdicao as $motivoEdicao) { ?><option value="<?= (int) $motivoEdicao['id_motivo'] ?>"><?= htmlspecialchars((string) $motivoEdicao['motivo'], ENT_QUOTES, 'UTF-8') ?></option><?php } ?></select></div>
            <div class="delay-form-field"><span class="delay-form-label">Responsável</span><div class="delay-form-control" aria-readonly="true"><?= htmlspecialchars((string) ($_SESSION['nome'] ?? 'Usuário não autenticado') . (!empty($_SESSION['login_teste_local']) ? ' (teste local)' : ''), ENT_QUOTES, 'UTF-8') ?></div></div>
            <button class="delay-validation-submit" id="delay-validation-submit" type="submit" disabled><i class="fas fa-check" aria-hidden="true"></i><span id="delay-validation-submit-label">Escolha uma avaliação</span></button>
        </form>
    </dialog>
    <script>
        (() => {
            const clock = document.getElementById('atrasos-relogio');
            const updateClock = () => {
                const now = new Date();
                clock.textContent = `${now.toLocaleDateString('pt-BR')} | ${now.toLocaleTimeString('pt-BR')}`;
            };
            updateClock();
            window.setInterval(updateClock, 1000);

            document.querySelectorAll('[data-delay-search]').forEach((input) => {
                input.addEventListener('input', () => {
                    const container = document.getElementById(`atrasos-turma-${input.dataset.delaySearch}`);
                    const entries = container.querySelectorAll('[data-delay-entry]');
                    let visibleEntries = 0;
                    entries.forEach((entry) => {
                        const matches = entry.dataset.name.toLocaleLowerCase('pt-BR').includes(input.value.trim().toLocaleLowerCase('pt-BR'));
                        entry.hidden = !matches;
                        if (matches) visibleEntries += 1;
                    });
                    const noResults = container.querySelector('[data-delay-no-results]');
                    if (noResults) noResults.hidden = visibleEntries > 0;
                });
            });

            document.querySelectorAll('[data-history-card]').forEach((card) => {
                const nameFilter = card.querySelector('[data-history-filter-name]');
                const statusFilter = card.querySelector('[data-history-filter-status]');
                const dateFilter = card.querySelector('[data-history-filter-date]');
                const classId = card.dataset.historyCard;
                const filterHistory = () => {
                    if (dateFilter.value > dateFilter.max) dateFilter.value = dateFilter.max;
                    const name = nameFilter.value.trim().toLocaleLowerCase('pt-BR');
                    let visibleEntries = 0;
                    card.querySelectorAll('[data-history-entry]').forEach((entry) => {
                        const matches = entry.dataset.historyClass === classId
                            && entry.dataset.historyName.toLocaleLowerCase('pt-BR').includes(name)
                            && (statusFilter.value === 'todas' || entry.dataset.historyStatus === statusFilter.value)
                            && (!dateFilter.value || entry.dataset.historyDate === dateFilter.value);
                        entry.hidden = !matches;
                        if (matches) visibleEntries += 1;
                    });
                    card.querySelector('[data-history-count]').textContent = visibleEntries;
                    const noResults = card.querySelector('[data-history-no-results]');
                    if (noResults) noResults.hidden = visibleEntries > 0;
                };
                dateFilter.addEventListener('keydown', (event) => {
                    if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) event.preventDefault();
                });
                dateFilter.addEventListener('paste', (event) => event.preventDefault());
                [nameFilter, statusFilter, dateFilter].forEach((filter) => {
                    filter.addEventListener('input', filterHistory);
                    filter.addEventListener('change', filterHistory);
                });
                card.querySelector('[data-history-filter-reset]').addEventListener('click', () => {
                    nameFilter.value = '';
                    statusFilter.value = 'todas';
                    dateFilter.value = '';
                    filterHistory();
                });
                filterHistory();
            });

            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-delay-print]');
                if (!button) return;

                event.preventDefault();
                event.stopPropagation();
                try {
                    const details = JSON.parse(button.dataset.delayPrint);
                    if (!details || typeof details !== 'object' || Array.isArray(details)) {
                        throw new Error('Os dados deste registro de atraso estão em um formato inválido.');
                    }
                    window.printDelayReceipt(details);
                } catch (error) {
                    console.error('Não foi possível imprimir este registro de atraso.', error);
                    window.alert('Não foi possível preparar a impressão deste registro de atraso.');
                }
            });

            document.addEventListener('click', (event) => {
                const entry = event.target.closest('[data-history-entry]');
                if (!entry || event.target.closest('button, a, input, select, textarea, summary')) return;
                entry.querySelector('[data-delay-details]')?.click();
            });

            document.addEventListener('keydown', (event) => {
                const entry = event.target.closest('[data-history-entry]');
                if (!entry || event.target !== entry || !['Enter', ' '].includes(event.key)) return;

                event.preventDefault();
                entry.querySelector('[data-delay-details]')?.click();
            });

            const dialog = document.getElementById('delay-details-dialog');
            const dialogContent = document.getElementById('delay-dialog-content');
            const validationForm = document.getElementById('delay-validation-form');
            const motiveField = document.getElementById('delay-validation-motive-field');
            const motiveInput = document.getElementById('delay-validation-motive');
            const validationSubmitLabel = document.getElementById('delay-validation-submit-label');
            const recordId = document.getElementById('delay-record-id');
            const decisionInput = document.getElementById('delay-decision');
            const decisionButtons = validationForm.querySelectorAll('[data-delay-decision]');
            const validationSubmit = document.getElementById('delay-validation-submit');
            const dialogTitle = document.getElementById('delay-dialog-title');
            const dialogSubtitle = document.getElementById('delay-dialog-subtitle');
            const updateDecision = (decision) => {
                const justificationInput = validationForm.elements.namedItem('justificativa_atraso');
                decisionInput.value = decision;
                decisionButtons.forEach((button) => {
                    button.setAttribute('aria-pressed', String(button.dataset.delayDecision === decision));
                });
                validationSubmit.disabled = !decision || !motiveInput.value || !justificationInput?.value.trim();
                validationSubmit.classList.toggle('is-rejection', decision === 'recusada');
                const label = decision === 'aprovada' ? 'Aprovar atraso' : decision === 'recusada' ? 'Confirmar recusa' : 'Escolha uma avaliação';
                validationSubmitLabel.textContent = label;
                const icon = validationSubmit.querySelector('i');
                icon.classList.toggle('fa-check', decision !== 'recusada');
                icon.classList.toggle('fa-xmark', decision === 'recusada');
            };
            decisionButtons.forEach((button) => {
                button.addEventListener('click', () => updateDecision(button.dataset.delayDecision));
            });
            motiveInput.addEventListener('change', () => updateDecision(decisionInput.value));
            document.addEventListener('click', (event) => {
                const button = event.target.closest('[data-delay-details]');
                if (!button) return;

                try {
                    const details = JSON.parse(button.dataset.delayDetails);
                    if (!details || typeof details !== 'object' || Array.isArray(details)) {
                        throw new Error('Os detalhes do atraso estão em um formato inválido.');
                    }
                    dialogContent.replaceChildren();
                    const canValidate = button.dataset.canValidate === '1' && button.dataset.status === 'pendente';
                    const showValidationForm = canValidate;
                    dialogTitle.textContent = showValidationForm ? 'Validar atraso' : 'Detalhes do atraso';
                    dialogSubtitle.textContent = canValidate ? 'Confirme o registro do atraso e informe sua avaliação.' : 'Consulte os dados e o resultado da validação.';
                    const summary = document.createElement('section');
                    summary.className = 'delay-validation-summary';
                    const summaryLabel = document.createElement('p');
                    summaryLabel.className = 'delay-validation-summary-label';
                    summaryLabel.textContent = 'Registro de atraso';
                    const summaryName = document.createElement('p');
                    summaryName.className = 'delay-validation-summary-name';
                    summaryName.textContent = details.Aluno || 'Aluno';
                    const summaryMeta = document.createElement('p');
                    summaryMeta.className = 'delay-validation-summary-meta';
                    summaryMeta.textContent = `${details.Turma || ''} · ${details['Data e hora'] || ''}`;
                    summary.append(summaryLabel, summaryName, summaryMeta);
                    dialogContent.appendChild(summary);
                    motiveField.hidden = !showValidationForm;
                    dialogContent.appendChild(motiveField);
                    const justificationLabel = document.createElement('label');
                    justificationLabel.className = 'delay-justification-label';
                    justificationLabel.textContent = 'Justificativa do atraso';
                    const justification = document.createElement('textarea');
                    justification.className = 'delay-justification';
                    justification.name = 'justificativa_atraso';
                    justification.setAttribute('form', 'delay-validation-form');
                    justification.maxLength = 2000;
                    justification.readOnly = !showValidationForm;
                    justification.required = showValidationForm;
                    justification.placeholder = showValidationForm ? 'Informe a justificativa do atraso' : '';
                    justification.value = details['Justificativa do atraso'] === 'Não informada'
                        ? ''
                        : details['Justificativa do atraso'] || '';
                    justification.addEventListener('input', () => updateDecision(decisionInput.value));
                    dialogContent.append(justificationLabel, justification);

                    const extraDetails = document.createElement('details');
                    extraDetails.className = 'delay-extra-details';
                    const extraSummary = document.createElement('summary');
                    extraSummary.textContent = 'Dados completos do registro';
                    extraDetails.appendChild(extraSummary);
                    Object.entries(details).filter(([label]) => ![
                        'Aluno',
                        'Turma',
                        'Data e hora',
                        'Responsável',
                        'Tipo de responsável',
                        'Acompanhante',
                        'Tipo de acompanhante',
                        'Motivo'
                    ].includes(label)).forEach(([label, value]) => {
                        const row = document.createElement('div');
                        row.className = 'delay-dialog-row';
                        const labelElement = document.createElement('span');
                        labelElement.className = 'delay-dialog-label';
                        labelElement.textContent = label;
                        const valueElement = document.createElement('span');
                        valueElement.className = 'delay-dialog-value';
                        valueElement.textContent = value;
                        row.append(labelElement, valueElement);
                        extraDetails.appendChild(row);
                    });
                    extraDetails.hidden = showValidationForm;
                    dialogContent.appendChild(extraDetails);
                    validationForm.hidden = !showValidationForm;
                    motiveInput.value = button.dataset.motiveId || '';
                    updateDecision('');
                    recordId.value = button.dataset.recordId || '';
                    if (!dialog.open) dialog.showModal();
                } catch (error) {
                    console.error('Não foi possível abrir os dados deste atraso.', error);
                    showToast('Não foi possível abrir os dados deste atraso.', true);
                }
            });
            validationForm.addEventListener('submit', (event) => {
                const justificationInput = validationForm.elements.namedItem('justificativa_atraso');
                if (!decisionInput.value || !motiveInput.value || !justificationInput?.value.trim()) {
                    event.preventDefault();
                    return;
                }
            });
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        })();
    </script>
</section>

<section class="page-section" id="ultimas-saidas" aria-label="Últimas Saídas">

    <!-- QR Code Reader -->
    <div id="reader" style="position: fixed; top: 20px; right: 20px; z-index: 1000;"></div>
    <input type="text" id="urlInput" class="qr-input-capture" aria-label="Captura do leitor QR" autocomplete="off" autocapitalize="off" spellcheck="false" />

    <div class="main-container max-w-7xl mx-auto p-6 lg:p-8 ">
        
        <div class="text-center mb-8">
            <div class="flex flex-col lg:flex-row items-center justify-center gap-4">
                <h1 class="text-2xl lg:text-3xl font-semibold">
                    <span class="gradient-text">Frequência em tempo real</span>
                </h1>
                
                
                <div class="inline-flex items-center bg-white rounded-lg px-4 py-2 shadow-sm border min-w-[235px] justify-center">
                    <span id="relogio" 
                          class="text-base font-bold text-gray-700 tabular-nums" 
                          style="font-family:sans-serif; font-size:20px;Letter-spacing:1px;">
                    </span>
                    
                    
                    
                </div>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                No caso de problemas com o registro da frequência, procure a recepção.
            </div>
            <p id="qr-reader-status" data-qr-reader-status class="mt-2 max-w-full break-all text-sm text-gray-500" role="status" aria-live="polite">Leitor pronto para receber um crachá.</p>
        </div>
        
        
        
        
        
        

        <!-- Vista Desktop (Tabelas) -->   
        
        <div class="desktop-view">
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <!-- 3º Ano A -->
                <div class="table-container turma-3a">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano A
                            </h2>
                            <?php
                            $dados_3a = $select->saida_estagio_3A();
                            $count_3a = count($dados_3a);
                            ?>
                            <span data-exit-count="3a" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3a ?> alunos
                            </span>
                        </div>
                        <input type="search" class="search-input search-3a" placeholder="Buscar aluno..." oninput="filterTable('3a')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3a">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3a as $dado) { ?>
                                    <tr class="table-row"<?= exitRecordDataAttributes($dado) ?>>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-danger"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3a)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano B -->
                <div class="table-container turma-3b">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano B
                            </h2>
                            <?php
                            $dados_3b = $select->saida_estagio_3B();
                            $count_3b = count($dados_3b);
                            ?>
                            <span data-exit-count="3b" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3b ?> alunos
                            </span>
                        </div>
                        <input type="search" class="search-input search-3b" placeholder="Buscar aluno..." oninput="filterTable('3b')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3b">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3b as $dado) { ?>
                                    <tr class="table-row"<?= exitRecordDataAttributes($dado) ?>>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-info"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3b)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano C -->
                <div class="table-container turma-3c">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano C
                            </h2>
                            <?php
                            $dados_3c = $select->saida_estagio_3C();
                            $count_3c = count($dados_3c);
                            ?>
                            <span data-exit-count="3c" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3c ?> alunos
                            </span>
                        </div>
                        <input type="search" class="search-input search-3c" placeholder="Buscar aluno..." oninput="filterTable('3c')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3c">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3c as $dado) { ?>
                                    <tr class="table-row"<?= exitRecordDataAttributes($dado) ?>>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-admin"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3c)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3º Ano D -->
                <div class="table-container turma-3d">
                    <div class="table-header">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano D
                            </h2>
                            <?php
                            $dados_3d = $select->saida_estagio_3D();
                            $count_3d = count($dados_3d);
                            ?>
                            <span data-exit-count="3d" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3d ?> alunos
                            </span>
                        </div>
                        <input type="search" class="search-input search-3d" placeholder="Buscar aluno..." oninput="filterTable('3d')">
                    </div>
                    <div class="table-content custom-scrollbar">
                        <table class="w-full compact-table" id="table-3d">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-user mr-1"></i>Nome do Aluno
                                    </th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <i class="fas fa-clock mr-1"></i>Horário
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($dados_3d as $dado) { ?>
                                    <tr class="table-row"<?= exitRecordDataAttributes($dado) ?>>
                                        <td class="px-4 py-2 text-sm text-gray-900">
                                            <div class="flex items-center">
                                                <i class="fas fa-user-graduate mr-2 text-grey"></i>
                                                <?= htmlspecialchars($dado['nome']) ?>
                                            </div>
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">
                                            <?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (empty($dados_3d)) { ?>
                                    <tr>
                                        <td colspan="2" class="px-4 py-8 text-center text-gray-500 italic">
                                            Nenhum aluno registrado hoje
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vista Mobile (Cards) -->
        <div class="mobile-view">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
                <!-- 3º Ano A -->
                <div class="class-card">
                    <div class="card-header-3a p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano A
                            </h2>
                            <span data-exit-count="3a" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3a ?>
                            </span>
                        </div>
                        <input type="search" class="search-input search-mobile-3a" placeholder="Buscar aluno..." oninput="filterCards('3a')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3a">
                        <?php if ($count_3a > 0) { ?>
                            <?php foreach ($dados_3a as $index => $dado) { ?>
                                <div class="student-card compact-card"<?= exitRecordDataAttributes($dado) ?>>
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-red-100 text-danger text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3a > 10) { ?>
                                <div class="pagination" id="pagination-3a">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano B -->
                <div class="class-card">
                    <div class="card-header-3b p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano B
                            </h2>
                            <span data-exit-count="3b" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3b ?>
                            </span>
                        </div>
                        <input type="search" class="search-input search-mobile-3b" placeholder="Buscar aluno..." oninput="filterCards('3b')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3b">
                        <?php if ($count_3b > 0) { ?>
                            <?php foreach ($dados_3b as $index => $dado) { ?>
                                <div class="student-card compact-card"<?= exitRecordDataAttributes($dado) ?>>
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-blue-100 text-info text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3b > 10) { ?>
                                <div class="pagination" id="pagination-3b">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano C -->
                <div class="class-card">
                    <div class="card-header-3c p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano C
                            </h2>
                            <span data-exit-count="3c" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3c ?>
                            </span>
                        </div>
                        <input type="search" class="search-input search-mobile-3c" placeholder="Buscar aluno..." oninput="filterCards('3c')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3c">
                        <?php if ($count_3c > 0) { ?>
                            <?php foreach ($dados_3c as $index => $dado) { ?>
                                <div class="student-card compact-card"<?= exitRecordDataAttributes($dado) ?>>
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-cyan-100 text-admin text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3c > 10) { ?>
                                <div class="pagination" id="pagination-3c">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <!-- 3º Ano D -->
                <div class="class-card">
                    <div class="card-header-3d p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h2 class="text-lg font-semibold flex items-center text-white">
                                <i class="fas fa-users mr-2"></i>
                                3º Ano D
                            </h2>
                            <span data-exit-count="3d" class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3d ?>
                            </span>
                        </div>
                        <input type="search" class="search-input search-mobile-3d" placeholder="Buscar aluno..." oninput="filterCards('3d')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3d">
                        <?php if ($count_3d > 0) { ?>
                            <?php foreach ($dados_3d as $index => $dado) { ?>
                                <div class="student-card compact-card"<?= exitRecordDataAttributes($dado) ?>>
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                        <div class="flex items-center mb-2 md:mb-0">
                                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full bg-gray-100 text-grey text-sm font-medium">
                                                <?= $index + 1 ?>
                                            </div>
                                            <span class="ml-3 font-medium text-gray-900 text-base"><?= htmlspecialchars($dado['nome']) ?></span>
                                        </div>
                                        <div class="text-sm text-gray-500 flex items-center">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span class="font-medium"><?= isset($dado['dae']) ? date('H:i', strtotime($dado['dae'])) : '--:--' ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($count_3d > 10) { ?>
                                <div class="pagination" id="pagination-3d">
                                    <!-- Paginação será gerada via JavaScript -->
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="text-center py-8 text-gray-500 italic">
                                Nenhum aluno registrado hoje
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>



        <!-- Footer com botão de atualização manual -->
        <!-- Coloquei a atualização na função que registra a saída, por isso documentei este botão. Otávio 18/06/2026 -->
        <!-- 
        <div class="text-center mt-8">
            <button type="button" data-refresh-section="ultimas-saidas" class="inline-flex items-center text-gray-600 bg-white rounded-lg px-4 py-2 shadow-sm border hover:bg-gray-50 transition-colors">
                <i class="fas fa-sync-alt mr-2 text-ceara-green"></i>
                <span class="text-sm">Atualizar dados</span>
            </button>
        </div>
        -->
    <br>
    </div>

    <!-- Footer geométrico -->
    <div class="geometric-footer">
        <div class="geometric-shape shape-1"></div>
        <div class="geometric-shape shape-2"></div>
        <div class="geometric-shape shape-3"></div>
        <div class="geometric-shape shape-4"></div>
        <div class="geometric-shape shape-5"></div>
        <div class="geometric-shape shape-6"></div>
    </div>

    
    

</section>

<script>
(() => {
function atualizarRelogio() {
                            const agora = new Date();
                            const dataHora = `${agora.getDate().toString().padStart(2,'0')}/${(agora.getMonth()+1).toString().padStart(2,'0')}/${agora.getFullYear()} | `
                                          + `${agora.getHours().toString().padStart(2,'0')}:${agora.getMinutes().toString().padStart(2,'0')}:${agora.getSeconds().toString().padStart(2,'0')}`;
                            
                            document.getElementById('relogio').textContent = dataHora;
                        }
                
                        atualizarRelogio();
                        setInterval(atualizarRelogio, 1000);
})();
</script>

<script>
(() => {
function normalizeStudentSearch(value) {
    return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
}

// Função para filtrar tabelas
        function filterTable(turma) {
            const input = document.querySelector(`.search-${turma}`);
            const filter = normalizeStudentSearch(input.value);
            const table = document.getElementById(`table-${turma}`);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Começar do 1 para pular o cabeçalho
                const nameCell = rows[i].getElementsByTagName('td')[0];
                if (nameCell) {
                    const nameText = normalizeStudentSearch(rows[i].dataset.exitStudent || nameCell.textContent || nameCell.innerText);
                    if (nameText.includes(filter)) {
                        rows[i].style.display = '';
                    } else {
                        rows[i].style.display = 'none';
                    }
                }
            }
        }

        // Função para filtrar cards
        function filterCards(turma) {
            const input = document.querySelector(`.search-mobile-${turma}`);
            const filter = normalizeStudentSearch(input.value);
            const container = document.getElementById(`cards-${turma}`);
            const cards = container.getElementsByClassName('student-card');

            for (let i = 0; i < cards.length; i++) {
                const nameText = normalizeStudentSearch(cards[i].dataset.exitStudent || cards[i].querySelector('span')?.textContent || '');
                if (nameText.includes(filter)) {
                    cards[i].style.display = '';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        }

        // Função para inicializar paginação
        function initPagination() {
            const turmas = ['3a', '3b', '3c', '3d'];
            const itemsPerPage = 10;

            turmas.forEach(turma => {
                const container = document.getElementById(`cards-${turma}`);
                if (!container) return;

                const cards = container.getElementsByClassName('student-card');
                const totalPages = Math.ceil(cards.length / itemsPerPage);
                let paginationContainer = document.getElementById(`pagination-${turma}`);
                if (paginationContainer) paginationContainer.remove();
                if (totalPages <= 1) {
                    Array.from(cards).forEach(card => { card.style.display = ''; });
                    return;
                }

                paginationContainer = document.createElement('div');
                paginationContainer.className = 'pagination';
                paginationContainer.id = `pagination-${turma}`;
                container.appendChild(paginationContainer);

                // Criar botões de paginação
                let paginationHTML = '';
                for (let i = 1; i <= totalPages; i++) {
                    paginationHTML += `<span class="pagination-btn ${i === 1 ? 'active' : ''}" data-page="${i}">${i}</span>`;
                }
                paginationContainer.innerHTML = paginationHTML;

                // Mostrar apenas a primeira página inicialmente
                showPage(turma, 1, itemsPerPage);

                // Adicionar event listeners aos botões
                const buttons = paginationContainer.getElementsByClassName('pagination-btn');
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].addEventListener('click', function() {
                        const page = parseInt(this.getAttribute('data-page'));
                        showPage(turma, page, itemsPerPage);

                        // Atualizar classe ativa
                        for (let j = 0; j < buttons.length; j++) {
                            buttons[j].classList.remove('active');
                        }
                        this.classList.add('active');
                    });
                }
            });
        }

        // Função para mostrar uma página específica
        function showPage(turma, page, itemsPerPage) {
            const container = document.getElementById(`cards-${turma}`);
            const cards = container.getElementsByClassName('student-card');

            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;

            for (let i = 0; i < cards.length; i++) {
                if (i >= startIndex && i < endIndex) {
                    cards[i].style.display = '';
                } else {
                    cards[i].style.display = 'none';
                }
            }
        }

        window.filterTable = filterTable;
        window.filterCards = filterCards;
        window.initExitCardsPagination = initPagination;

        // Inicializar paginação quando o documento estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            initPagination();
        });
})();
</script>

<script>
(() => {
// QR Code Reader functionality
        const input = document.getElementById('urlInput');
        const readerDiv = document.getElementById('reader');
        const delayDialog = document.getElementById('delay-details-dialog');
        let leituraAtiva = false;
        let leitorIniciando = false;
        let ultimaUrlAberta = null;
        let html5QrCode;

        function atualizarStatusLeitor(mensagem, erro = false) {
            document.querySelectorAll('[data-qr-reader-status]').forEach((status) => {
                status.textContent = mensagem;
                status.classList.toggle('text-red-600', erro);
                status.classList.toggle('text-gray-500', !erro);
            });
        }

        document.addEventListener('stage-qr-status', (event) => {
            atualizarStatusLeitor(event.detail.message, event.detail.error === true);
        });

        function extrairIdentificadorQr(conteudo) {
            const valor = String(conteudo)
                .replace(/[\u0000-\u001f\u007f]/g, '')
                .trim();
            if (/^\d+$/.test(valor)) return valor;
            const normalizarIdentificador = (identificador) => {
                const normalizado = String(identificador ?? '').replace(/[\u0000-\u001f\u007f]/g, '').trim();
                return normalizado.length > 0 && normalizado.length <= 100 ? normalizado : null;
            };

            try {
                const dados = JSON.parse(valor);
                const identificadorJson = dados.id_aluno ?? dados.matricula ?? dados.id ?? dados.codigo ?? dados.nome;
                const identificador = normalizarIdentificador(identificadorJson);
                if (identificador) return identificador;
            } catch (error) {}

            try {
                const urlQr = new URL(valor, window.location.href);
                for (const chave of ['id_aluno', 'matricula', 'id', 'codigo', 'nome']) {
                    const identificador = normalizarIdentificador(urlQr.searchParams.get(chave));
                    if (identificador) return identificador;
                }
            } catch (error) {}

            const parametro = valor.match(/(?:^|[?&;])(?:id_aluno|matricula|id|codigo|nome)=([^&#;]+)/i);
            if (parametro) {
                try {
                    return normalizarIdentificador(decodeURIComponent(parametro[1].replace(/\+/g, ' ')));
                } catch (error) {
                    return normalizarIdentificador(parametro[1].replace(/\+/g, ' '));
                }
            }

            if (/^[a-z][a-z\d+.-]*:/i.test(valor) || /^www\./i.test(valor)) return null;
            const nomeBruto = valor.replace(/^[\s-]+/, '').replace(/\+/g, ' ').trim();
            return /^[\p{L}\p{M}][\p{L}\p{M}\s'.()-]*$/u.test(nomeBruto)
                ? normalizarIdentificador(nomeBruto)
                : null;
        }

        // Função para manter o input sempre focado
        function manterFoco() {
            if (!leituraAtiva || delayDialog?.open) return;
            const activeElement = document.activeElement;
            const editingField = activeElement !== input
                && activeElement?.matches('input:not([type="hidden"]), textarea, select, [contenteditable="true"]');
            if (editingField) {
                setTimeout(manterFoco, 100);
                return;
            }
            input.focus();
            setTimeout(manterFoco, 100);
        }

        delayDialog?.addEventListener('close', () => {
            if (!leituraAtiva) return;
            input.focus();
            manterFoco();
        });

        function abrirEmNovaAba(url) {
            if (!url || delayDialog?.open || url === ultimaUrlAberta) return;
            const identificador = extrairIdentificadorQr(url);
            if (!identificador) {
                const conteudo = String(url).replace(/[\u0000-\u001f\u007f]/g, '').trim().slice(0, 120);
                atualizarStatusLeitor(`QR não reconhecido: ${conteudo || 'código vazio'}`, true);
                return;
            }

            ultimaUrlAberta = url;
            atualizarStatusLeitor('Crachá lido. Consultando o cadastro...');
            document.dispatchEvent(new CustomEvent('stage-qr-scanned', { detail: { identifier: identificador } }));
            window.setTimeout(() => {
                if (ultimaUrlAberta === url) ultimaUrlAberta = null;
            }, 2000);
        }

        function onQRCodeScanned(decodedText) {
            abrirEmNovaAba(decodedText);
            input.value = '';
        }

        // Inicia o leitor QR automaticamente quando a página carrega
        document.body.append(readerDiv, input);

        function ativarLeitorQr() {
            if (!leituraAtiva) {
                leituraAtiva = true;
                input.focus();
                manterFoco();
                atualizarStatusLeitor('Leitor pronto para receber um crachá.');
            }
            if (leitorIniciando || html5QrCode) return;

            // Limpa o histórico de URLs abertas
            ultimaUrlAberta = null;
            leituraAtiva = true;
            input.focus();
            manterFoco();

            // Inicia o leitor QR apenas em dispositivos não-mobile (>= 768px)
            if (window.innerWidth >= 768 && typeof Html5Qrcode === 'function') {
                readerDiv.style.display = 'block';
                html5QrCode = new Html5Qrcode("reader");
                leitorIniciando = true;

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: 250 },
                    onQRCodeScanned
                ).then(() => {
                    leitorIniciando = false;
                    leituraAtiva = true;
                    input.focus();
                }).catch(() => {
                    leitorIniciando = false;
                    html5QrCode = null;
                    readerDiv.style.display = 'none';
                    atualizarStatusLeitor('Câmera indisponível. O leitor físico continua pronto.');
                    input.focus();
                });
            } else {
                readerDiv.style.display = 'none';
                if (window.innerWidth >= 768) {
                    atualizarStatusLeitor('Câmera indisponível. O leitor físico continua pronto.');
                }
            }
        }

        document.addEventListener('section:activate:qr-reader', ativarLeitorQr);

        // Adiciona evento para abrir URL quando o usuário digita
        let timeoutId;
        input.addEventListener('input', (e) => {
            if (timeoutId) clearTimeout(timeoutId);
            if (e.target.value.trim()) {
                timeoutId = setTimeout(() => {
                    const scannedValue = input.value.trim();
                    input.value = '';
                    abrirEmNovaAba(scannedValue);
                }, 900);
            }
        });
        input.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter') return;
            event.preventDefault();
            if (timeoutId) clearTimeout(timeoutId);
            const scannedValue = input.value.trim();
            input.value = '';
            abrirEmNovaAba(scannedValue);
        });

        // Previne que o usuário perca o foco
        readerDiv.addEventListener('click', () => input.focus());

        // Força recarregamento da página se vier do cache
        

        // Limpa o cache quando a página carrega
        
})();
</script>

<section class="page-section" id="cadastro" aria-label="Cadastrar Aluno">

    
    

    <!-- Main Content -->
    <div class="modal-form-shell modal-form-shell-sm">
        <header class="modal-form-header">
            <div class="modal-form-title">
                <div class="modal-form-icon"><i class="fas fa-user-plus"></i></div>
                <div>
                    <h2>Cadastro de Aluno</h2>
                    <p>Adicione um novo aluno ao sistema</p>
                </div>
            </div>
        </header>

                <form id="cadastroForm" action="../control/control_index.php" method="post" class="modal-form-form">
                    <div class="modal-form-scroll">
            <div class="modal-form-body">
                <?php if ($sectionInicial === 'cadastro' && $mensagemCadastro !== '' && $statusCadastro !== 'success') { ?>
                    <div role="status" class="mb-2 rounded-lg border p-3 text-sm <?= $statusCadastro === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' ?>">
                        <i class="fas <?= $statusCadastro === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2" aria-hidden="true"></i>
                        <?= htmlspecialchars($mensagemCadastro, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php } ?>

                    <input type="hidden" name="cadastrar" value="1">

                    <!-- Nome Field -->
                    <div class="input-group cadastro-field">
                        <label for="nome" class="required-field">
                            <i class="fas fa-user" aria-hidden="true"></i>
                            Nome Completo
                        </label>
                        <input 
                            type="text" 
                            id="nome" 
                            name="nome" 
                            class="input-field"
                            required
                        >
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
                    <div class="input-group cadastro-field">
                        <label for="matricula" class="required-field">
                            <i class="fas fa-id-card" aria-hidden="true"></i>
                            Matrícula (7 dígitos)
                        </label>
                        <input 
                            type="text" 
                            id="matricula" 
                            name="matricula" 
                            maxlength="7"
                            class="input-field"
                            required
                        >
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
                    <div class="input-group cadastro-field">
                        <label for="id_turma">
                            <i class="fas fa-users" aria-hidden="true"></i>
                            Turma
                        </label>
                        <select 
                            id="id_turma" 
                            name="id_turma" 
                            class="form-select"
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
                        <div id="cadastro-turma-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            <span>Por favor, selecione uma turma</span>
                        </div>
                    </div>

                    <!-- Curso Field -->
                    <div class="input-group cadastro-field">
                        <label for="id_curso">
                            <i class="fas fa-book" aria-hidden="true"></i>
                            Curso
                        </label>
                        <select 
                            id="id_curso" 
                            name="id_curso" 
                            class="form-select"
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

            </div>
          </div>
          <div class="modal-form-footer">
            <button type="button" class="modal-form-btn ghost" data-section-target="inicio">Cancelar</button>
            <button type="submit" id="cadastro-submitBtn" class="modal-form-btn primary">
                <span id="cadastro-submitText" class="flex items-center justify-center gap-2">
                    <i class="fas fa-user-plus text-sm"></i>
                    Cadastrar Aluno
                </span>
                <span id="cadastro-submitLoading" class="hidden flex items-center justify-center gap-2">
                    <div class="loading-spinner"></div>
                    Cadastrando...
                </span>
            </button>
          </div>
        </form>
    </div>

    <!-- Footer -->
    

    

</section>

<script>
(() => {
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
                this.submitBtn = document.getElementById('cadastro-submitBtn');
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
                    const container = this.form.closest('.page-section').querySelector('.modal-form-shell');
                    container.classList.add('shake');
                    setTimeout(() => container.classList.remove('shake'), 500);
                    return;
                }

                // Show loading state
                this.showLoadingState();
                this.form.submit();
            }

            showLoadingState() {
                const submitText = document.getElementById('cadastro-submitText');
                const submitLoading = document.getElementById('cadastro-submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

        }
})();
</script>
<script>
(() => {
    const forms = ['registro-e', 'registro-s', 'saida-estagio'];
    const formatDate = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const formatTime = (date) => `${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;

    forms.forEach((formId) => {
        const form = document.getElementById(formId);
        if (!form) return;

        const dateInput = form.querySelector('input[name="data"]');
        const timeInput = form.querySelector('input[name="hora"]');
        if (!dateInput || !timeInput) return;

        const updateDateTimeLimits = () => {
            const now = new Date();
            const today = formatDate(now);
            const currentTime = formatTime(now);
            dateInput.max = today;
            dateInput.setCustomValidity('');
            timeInput.setCustomValidity('');

            if (dateInput.value > today) {
                dateInput.setCustomValidity('Não é permitido selecionar uma data futura.');
                timeInput.removeAttribute('max');
                return;
            }

            if (dateInput.value === today) {
                timeInput.max = currentTime;
                if (timeInput.value && timeInput.value > currentTime) {
                    timeInput.setCustomValidity('No dia de hoje, selecione um horário até o momento atual.');
                }
            } else {
                timeInput.removeAttribute('max');
            }
        };

        dateInput.addEventListener('input', updateDateTimeLimits);
        dateInput.addEventListener('change', updateDateTimeLimits);
        timeInput.addEventListener('input', updateDateTimeLimits);
        timeInput.addEventListener('change', updateDateTimeLimits);
        updateDateTimeLimits();
    });
})();
</script>
<script>
(() => {
  const navItems = document.querySelectorAll('.nav-item[data-target]');
  const sections = document.querySelectorAll('.page-section');
    const sidebarGroups = document.querySelectorAll('.nav-group[data-sidebar-group]');
    const sidebarStorageKey = 'salaberga-sidebar-groups';
    let savedSidebarGroups = {};
    try {
        savedSidebarGroups = JSON.parse(localStorage.getItem(sidebarStorageKey) || '{}');
        if (!savedSidebarGroups || typeof savedSidebarGroups !== 'object') savedSidebarGroups = {};
    } catch {}
    sidebarGroups.forEach(group => {
        const groupKey = group.dataset.sidebarGroup;
        if (Object.prototype.hasOwnProperty.call(savedSidebarGroups, groupKey)) {
            group.open = savedSidebarGroups[groupKey] === true;
        }
        group.addEventListener('toggle', () => {
            savedSidebarGroups[groupKey] = group.open;
            try {
                localStorage.setItem(sidebarStorageKey, JSON.stringify(savedSidebarGroups));
            } catch {}
        });
    });
    const menuToggle = document.querySelector('.mobile-menu');
    const sidebarElement = document.querySelector('.sidebar');
    const sidebarCollapseToggle = document.querySelector('.sidebar-collapse-toggle');
    const desktopSidebarQuery = window.matchMedia('(min-width: 761px)');
    const sidebarCollapseStorageKey = 'salaberga-sidebar-collapsed';
    let sidebarCollapsedPreference = false;
    try {
        sidebarCollapsedPreference = localStorage.getItem(sidebarCollapseStorageKey) === 'true';
    } catch {}
    const updateSidebarCollapse = () => {
        const collapsed = desktopSidebarQuery.matches && sidebarCollapsedPreference;
        document.body.classList.toggle('sidebar-collapsed', collapsed);
        sidebarElement?.setAttribute('aria-hidden', String(collapsed));
        if (sidebarElement) sidebarElement.inert = collapsed;
        const label = collapsed ? 'Mostrar menu lateral' : 'Ocultar menu lateral';
        sidebarCollapseToggle?.setAttribute('aria-expanded', String(!collapsed));
        sidebarCollapseToggle?.setAttribute('aria-label', label);
        sidebarCollapseToggle?.setAttribute('title', label);
    };
    updateSidebarCollapse();
    sidebarCollapseToggle?.addEventListener('click', () => {
        sidebarCollapsedPreference = !sidebarCollapsedPreference;
        try {
            localStorage.setItem(sidebarCollapseStorageKey, String(sidebarCollapsedPreference));
        } catch {}
        updateSidebarCollapse();
    });
    desktopSidebarQuery.addEventListener('change', updateSidebarCollapse);
    const closeMobileMenu = () => {
        document.body.classList.remove('sidebar-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    };
  const activate = (target, selectedItem) => {
    navItems.forEach(nav => nav.classList.remove('active'));
    sections.forEach(section => section.classList.remove('active'));
    const receptionHomeLink = document.querySelector('[data-reception-home]');
    if (receptionHomeLink) receptionHomeLink.hidden = target === 'inicio';
    selectedItem?.classList.add('active');
        const selectedGroup = selectedItem?.closest('.nav-group[data-sidebar-group]');
        if (selectedGroup && !Object.prototype.hasOwnProperty.call(savedSidebarGroups, selectedGroup.dataset.sidebarGroup)) {
            selectedGroup.open = true;
        }
    const selectedSection = document.getElementById(target);
    if (selectedSection) selectedSection.classList.add('active');
        if (selectedSection) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('section', target);
            window.history.replaceState(window.history.state, '', currentUrl);
        }
    document.dispatchEvent(new CustomEvent('section:activate:qr-reader'));
    closeMobileMenu();
  };
  navItems.forEach(item => item.addEventListener('click', () => activate(item.dataset.target, item)));
  document.querySelectorAll('[data-section-target]').forEach(item => item.addEventListener('click', event => {
    event.preventDefault();
    activate(item.dataset.sectionTarget, document.querySelector(`.nav-item[data-target="${item.dataset.sectionTarget}"]`));
  }));
    menuToggle?.addEventListener('click', () => {
    const isOpen = document.body.classList.toggle('sidebar-open');
        menuToggle.setAttribute('aria-expanded', String(isOpen));
  });
    document.querySelector('.sidebar-scrim')?.addEventListener('click', closeMobileMenu);
    const initialSection = <?php echo json_encode($sectionInicial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    activate(initialSection, document.querySelector(`.nav-item[data-target="${initialSection}"]`));
})();
</script>
</main>
<?php if ($ehRecepcao) { ?>
<footer class="reception-footer">© <?= date('Y') ?> Sistema Escolar Salaberga. Todos os direitos reservados.</footer>
<?php } ?>
<dialog class="delete-exit-dialog" id="delete-exit-dialog" aria-labelledby="delete-exit-title">
    <div class="early-exit-heading">
        <span class="early-exit-heading-icon" aria-hidden="true"><i class="fas fa-trash-alt"></i></span>
        <div>
            <h2 id="delete-exit-title">Excluir registro de saída?</h2>
            <p>Tem certeza de que deseja excluir este registro? Esta ação não pode ser desfeita.</p>
        </div>
    </div>
    <dl class="early-exit-info">
        <div><dt>Aluno</dt><dd id="delete-exit-aluno"></dd></div>
        <div><dt>Matrícula</dt><dd id="delete-exit-matricula"></dd></div>
        <div><dt>Turma</dt><dd id="delete-exit-turma"></dd></div>
        <div><dt>Data e hora</dt><dd id="delete-exit-data-hora"></dd></div>
    </dl>
    <p class="early-exit-error" id="delete-exit-error" role="alert" aria-live="polite"></p>
    <div class="early-exit-actions">
        <button class="early-exit-cancel" id="delete-exit-cancel" type="button">Manter registro</button>
        <button class="early-exit-confirm delete-exit-confirm" id="delete-exit-confirm" type="button">Excluir registro</button>
    </div>
</dialog>
<script>
(() => {
    const deleteExitDialog = document.getElementById('delete-exit-dialog');
    const form = document.getElementById('saida-estagio');
    const apiUrl = '../control/control_index.php';
    const csrfToken = form.querySelector('[name="csrf_token"]').value;
    let pendingDeleteExit = null;
    let qrBusy = false;
    let exitConfirmationTimeout = null;
    let exitConfirmationHideTimeout = null;

    function formatDate(date) {
        return typeof date === 'string' ? date.split('-').reverse().join('/') : '';
    }

    function notify(message, isError = false) {
        const toast = document.getElementById('successToast');
        const toastMessage = toast?.querySelector('.success-toast-message');
        const toastIcon = toast?.querySelector('.success-toast-icon i');
        const progress = toast?.querySelector('.success-toast-progress');
        if (!toast || !toastMessage || !toastIcon || !progress) {
            console.error('Notification elements not found.');
            return;
        }
        toastMessage.textContent = message;
        toast.classList.toggle('error', isError);
        toastIcon.className = `fas ${isError ? 'fa-exclamation' : 'fa-check'}`;
        toast.hidden = false;
        toast.classList.remove('visible');
        progress.style.animation = 'none';
        void progress.offsetWidth;
        progress.style.animation = '';
        requestAnimationFrame(() => toast.classList.add('visible'));
        window.setTimeout(() => {
            toast.classList.remove('visible');
            window.setTimeout(() => { toast.hidden = true; }, 250);
        }, 4000);
    }

    function showExitConfirmation(exit, isError = false, errorMessage = '', options = {}) {
        const confirmation = document.getElementById('stage-exit-confirmation');
        const title = document.getElementById('stage-exit-confirmation-title');
        const name = confirmation?.querySelector('.stage-exit-confirmation-name');
        const details = document.getElementById('stage-exit-confirmation-details');
        const icon = confirmation?.querySelector('.stage-exit-confirmation-icon i');
        const loading = confirmation?.querySelector('.stage-exit-confirmation-loading');
        if (!confirmation || !title || !name || !details || !icon || !loading) {
            console.error('Stage exit confirmation elements not found.');
            return;
        }

        window.clearTimeout(exitConfirmationTimeout);
        window.clearTimeout(exitConfirmationHideTimeout);
        const isPending = options.pending === true;
        confirmation.classList.toggle('is-error', isError && !isPending);
        confirmation.classList.toggle('is-pending', isPending);
        title.textContent = isPending
            ? 'Atraso aguardando validação'
            : isError
                ? `Aluno(a) ${exit.nome || ''} não está apto para estagiar!`
                : `Aluno(a) ${exit.nome || ''} saiu com sucesso!`;
        name.textContent = isPending
            ? `Aluno(a) ${exit.nome || ''} ainda está com o atraso em validação.`
            : isError
                ? errorMessage || 'A justificativa do atraso foi recusada. O estágio está bloqueado hoje.'
                : 'Sua saída foi processada e confirmada em nosso sistema.';
        icon.className = `fas ${isPending ? 'fa-hourglass-half' : isError ? 'fa-xmark' : 'fa-check'}`;
        loading.hidden = isError || isPending;
        const registeredDateTime = options.dateTime || '';
        const detailsParts = isPending
            ? [
                `Matrícula: ${exit.matricula || 'Não informada'}`,
                exit.turma ? `Turma: ${exit.turma}` : '',
                `Atraso registrado em: ${registeredDateTime
                    ? `${formatDate(registeredDateTime.slice(0, 10))} às ${registeredDateTime.slice(11, 16)}`
                    : 'data e horário não informados'}`
            ]
            : [
                `Matrícula: ${exit.matricula || 'Não informada'}`,
                exit.turma ? `Turma: ${exit.turma}` : '',
                `${formatDate(exit.data)} às ${(exit.hora || '').slice(0, 5)}`
            ].filter(Boolean);
        details.textContent = detailsParts.join(' · ');
        confirmation.hidden = false;
        confirmation.classList.remove('visible');
        requestAnimationFrame(() => confirmation.classList.add('visible'));
        exitConfirmationTimeout = window.setTimeout(() => {
            confirmation.classList.remove('visible');
            exitConfirmationHideTimeout = window.setTimeout(() => {
                confirmation.hidden = true;
                exitConfirmationHideTimeout = null;
            }, 220);
            exitConfirmationTimeout = null;
        }, 1500);
    }

    function abrirConfirmacaoExclusao(saida) {
        pendingDeleteExit = saida;
        const ehAtraso = saida.origem === 'atraso';
        document.getElementById('delete-exit-title').textContent = ehAtraso
            ? 'Excluir registro de atraso?'
            : 'Excluir registro de saída?';
        document.querySelector('#delete-exit-title + p').textContent = ehAtraso
            ? 'Tem certeza de que deseja excluir este atraso? Esta ação não pode ser desfeita.'
            : 'Tem certeza de que deseja excluir este registro? Esta ação não pode ser desfeita.';
        document.getElementById('delete-exit-confirm').textContent = ehAtraso
            ? 'Excluir atraso'
            : 'Excluir registro';
        document.getElementById('delete-exit-aluno').textContent = saida.nome || 'Aluno';
        document.getElementById('delete-exit-matricula').textContent = saida.matricula || 'Não informada';
        const turmaEstagio = exitGroups[saida.id_turma]?.letter;
        document.getElementById('delete-exit-turma').textContent =
            saida.turma || (turmaEstagio ? `3º Ano ${turmaEstagio}` : 'Não informada');
        const dataHora = saida.dae || saida.dateTime || '';
        document.getElementById('delete-exit-data-hora').textContent =
            `${formatDate(dataHora.slice(0, 10))} às ${dataHora.slice(11, 16)}`;
        document.getElementById('delete-exit-error').textContent = '';
        deleteExitDialog.showModal();
        document.getElementById('delete-exit-cancel').focus();
    }

    document.addEventListener('click', (event) => {
        const entry = event.target.closest('[data-delay-entry]');
        if (!entry || event.target.closest('button, a, input, select, textarea, summary')) return;

        abrirConfirmacaoExclusao({
            id_registro_entrada: entry.dataset.delayRecordId,
            nome: entry.dataset.name,
            matricula: entry.dataset.delayMatricula,
            turma: entry.dataset.delayTurma,
            dateTime: entry.dataset.delayDatetime,
            origem: 'atraso'
        });
    });

    document.addEventListener('keydown', (event) => {
        const entry = event.target.closest('[data-delay-entry]');
        if (!entry || event.target !== entry || !['Enter', ' '].includes(event.key)) return;

        event.preventDefault();
        abrirConfirmacaoExclusao({
            id_registro_entrada: entry.dataset.delayRecordId,
            nome: entry.dataset.name,
            matricula: entry.dataset.delayMatricula,
            turma: entry.dataset.delayTurma,
            dateTime: entry.dataset.delayDatetime,
            origem: 'atraso'
        });
    });

    function tornarRegistroAcionavel(element, saida) {
        element.classList.add('exit-record-clickable');
        element.tabIndex = 0;
        element.setAttribute('role', 'button');
        element.setAttribute('aria-label', `Ver opções para o registro de ${saida.nome}, às ${(saida.dae || '').slice(11, 16)}`);
        element.title = 'Clique para ver as opções deste registro';
        element.addEventListener('click', () => abrirConfirmacaoExclusao(saida));
        element.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                abrirConfirmacaoExclusao(saida);
            }
        });
    }

    document.addEventListener('click', (event) => {
        const deleteButton = event.target.closest('[data-delete-consulta-record]');
        if (!deleteButton) return;

        try {
            const record = JSON.parse(deleteButton.dataset.deleteRecord);
            abrirConfirmacaoExclusao({
                ...record,
                id_turma: Number(record.idTurma),
                dae: record.dataHora,
                origem: 'consulta'
            });
        } catch (error) {
            console.error('Não foi possível abrir a confirmação de exclusão do registro.', error);
            notify('Não foi possível carregar os dados desse registro.', true);
        }
    });

    async function requestApi(action, values = {}) {
        const body = new FormData();
        body.set('acao', action);
        body.set('csrf_token', csrfToken);
        Object.entries(values).forEach(([key, value]) => body.set(key, value));
        const response = await fetch(apiUrl, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        });
        let result;
        try {
            result = await response.json();
        } catch (error) {
            throw new Error('O servidor não retornou uma resposta válida.');
        }
        if (!response.ok || !result.success) {
            const error = new Error(result.message || 'Não foi possível registrar a saída.');
            error.status = result.status;
            throw error;
        }
        return result;
    }

    document.getElementById('delete-exit-cancel').addEventListener('click', () => {
        deleteExitDialog.close();
    });

    deleteExitDialog.addEventListener('close', () => {
        pendingDeleteExit = null;
    });

    document.getElementById('delete-exit-confirm').addEventListener('click', async (event) => {
        if (!pendingDeleteExit) return;

        const record = pendingDeleteExit;
        const button = event.currentTarget;
        const errorMessage = document.getElementById('delete-exit-error');
        button.disabled = true;
        button.textContent = 'Excluindo...';
        errorMessage.textContent = '';
        try {
            if (record.origem === 'atraso') {
                await requestApi('excluir_atraso_pendente_qr', {
                    id_registro_entrada: String(record.id_registro_entrada)
                });
            } else if (record.origem === 'consulta') {
                await requestApi('excluir_registro_consulta', {
                    tipo_registro: record.tipo,
                    id_registro: String(record.id)
                });
            } else {
                await requestApi('excluir_saida_estagio_qr', {
                    id_saida: String(record.id_saida)
                });
            }
            deleteExitDialog.close();
            if (record.origem === 'atraso') {
                const entry = Array.from(document.querySelectorAll('[data-delay-entry]'))
                    .find((item) => item.dataset.delayRecordId === String(record.id_registro_entrada));
                const card = entry?.closest('.delay-card');
                const list = entry?.closest('.delay-list');
                entry?.remove();
                const remainingEntries = list?.querySelectorAll('[data-delay-entry]').length || 0;
                if (card) card.querySelector('.delay-count').textContent = String(remainingEntries);
                const emptyMessage = list?.querySelector('[data-delay-no-results]');
                if (emptyMessage) {
                    const visibleEntries = Array.from(list.querySelectorAll('[data-delay-entry]'))
                        .some((item) => !item.hidden);
                    emptyMessage.hidden = visibleEntries;
                    emptyMessage.textContent = remainingEntries
                        ? 'Nenhum aluno encontrado'
                        : 'Nenhum atraso registrado hoje';
                }
                notify('Atraso excluído com sucesso.');
                return;
            }
            const deletedType = record.origem === 'consulta' ? record.tipo : 'estagio';
            const deletedId = record.origem === 'consulta' ? record.id : record.id_saida;
            document.querySelectorAll('[data-consulta-row]').forEach((row) => {
                if (row.dataset.consultaType === deletedType && row.dataset.consultaId === String(deletedId)) {
                    row.remove();
                }
            });
            document.querySelectorAll('[data-exit-consultation]').forEach((section) => {
                section.refreshConsultationRows?.();
            });
            let listaAtualizada = true;
            if (record.tipo === 'estagio' || record.origem !== 'consulta') {
                try {
                    await atualizarListaSaidas();
                } catch (error) {
                    listaAtualizada = false;
                    console.error('O registro foi excluído, mas as últimas saídas não puderam ser atualizadas.', error);
                }
            }
            notify(listaAtualizada
                ? 'Registro excluído com sucesso.'
                : 'Registro excluído, mas não foi possível atualizar a lista de últimas saídas. Atualize a página.');
        } catch (error) {
            errorMessage.textContent = error.message;
        } finally {
            button.disabled = false;
            button.textContent = pendingDeleteExit?.origem === 'atraso' ? 'Excluir atraso' : 'Excluir registro';
        }
    });

    const exitGroups = {
        9: { key: '3a', letter: 'A', icon: 'text-danger', avatar: 'bg-red-100 text-danger' },
        10: { key: '3b', letter: 'B', icon: 'text-info', avatar: 'bg-blue-100 text-info' },
        11: { key: '3c', letter: 'C', icon: 'text-admin', avatar: 'bg-cyan-100 text-admin' },
        12: { key: '3d', letter: 'D', icon: 'text-grey', avatar: 'bg-gray-100 text-grey' }
    };

    document.querySelectorAll('[data-exit-id]').forEach((element) => {
        const { exitId, exitStudent, exitMatricula, exitTurma, exitDatetime } = element.dataset;
        tornarRegistroAcionavel(element, {
            id_saida: exitId,
            nome: exitStudent,
            matricula: exitMatricula,
            id_turma: Number(exitTurma),
            dae: exitDatetime,
            tipo: 'estagio',
            origem: 'ultimas-saidas'
        });
    });

    async function atualizarListaSaidas() {
        const result = await requestApi('listar_saidas_estagio_hoje');
        const grouped = { '3a': [], '3b': [], '3c': [], '3d': [] };
        result.saidas.forEach((saida) => {
            const group = exitGroups[saida.id_turma];
            if (group) grouped[group.key].push(saida);
        });

        Object.entries(grouped).forEach(([turma, saidas]) => {
            const tableBody = document.querySelector(`#table-${turma} tbody`);
            if (tableBody) {
                tableBody.replaceChildren();
                if (saidas.length === 0) {
                    const row = document.createElement('tr');
                    const cell = document.createElement('td');
                    cell.colSpan = 2;
                    cell.className = 'px-4 py-8 text-center text-gray-500 italic';
                    cell.textContent = 'Nenhum aluno registrado hoje';
                    row.appendChild(cell);
                    tableBody.appendChild(row);
                } else {
                    saidas.forEach((saida) => {
                        const row = document.createElement('tr');
                        row.className = 'table-row';
                        tornarRegistroAcionavel(row, {
                            ...saida,
                            tipo: 'estagio',
                            origem: 'ultimas-saidas'
                        });
                        const nameCell = document.createElement('td');
                        nameCell.className = 'px-4 py-2 text-sm text-gray-900';
                        const nameWrap = document.createElement('div');
                        nameWrap.className = 'flex items-center';
                        const icon = document.createElement('i');
                        icon.className = `fas fa-user-graduate mr-2 ${exitGroups[saida.id_turma].icon}`;
                        nameWrap.append(icon, document.createTextNode(saida.nome));
                        nameCell.appendChild(nameWrap);
                        const timeCell = document.createElement('td');
                        timeCell.className = 'px-4 py-2 text-sm text-gray-500';
                        timeCell.textContent = saida.dae ? saida.dae.slice(11, 16) : '--:--';
                        row.append(nameCell, timeCell);
                        tableBody.appendChild(row);
                    });
                }
            }

            const cards = document.getElementById(`cards-${turma}`);
            if (cards) {
                cards.replaceChildren();
                if (saidas.length === 0) {
                    const emptyMessage = document.createElement('div');
                    emptyMessage.className = 'text-center py-8 text-gray-500 italic';
                    emptyMessage.textContent = 'Nenhum aluno registrado hoje';
                    cards.appendChild(emptyMessage);
                } else {
                    saidas.forEach((saida, index) => {
                        const card = document.createElement('div');
                        card.className = 'student-card compact-card';
                        tornarRegistroAcionavel(card, {
                            ...saida,
                            tipo: 'estagio',
                            origem: 'ultimas-saidas'
                        });
                        const row = document.createElement('div');
                        row.className = 'flex flex-col md:flex-row md:items-center md:justify-between';
                        const student = document.createElement('div');
                        student.className = 'flex items-center mb-2 md:mb-0';
                        const number = document.createElement('div');
                        number.className = `flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-full text-sm font-medium ${exitGroups[saida.id_turma].avatar}`;
                        number.textContent = String(index + 1);
                        const name = document.createElement('span');
                        name.className = 'ml-3 font-medium text-gray-900 text-base';
                        name.textContent = saida.nome;
                        student.append(number, name);
                        const time = document.createElement('div');
                        time.className = 'text-sm text-gray-500 flex items-center';
                        const clock = document.createElement('i');
                        clock.className = 'fas fa-clock mr-2';
                        const timeText = document.createElement('span');
                        timeText.className = 'font-medium';
                        timeText.textContent = saida.dae ? saida.dae.slice(11, 16) : '--:--';
                        time.append(clock, timeText);
                        row.append(student, time);
                        card.appendChild(row);
                        cards.appendChild(card);
                    });
                }
            }

            document.querySelectorAll(`[data-exit-count="${turma}"]`).forEach((count) => {
                count.textContent = count.closest('.mobile-view') ? String(saidas.length) : `${saidas.length} alunos`;
            });
        });

        window.initExitCardsPagination?.();
        ['3a', '3b', '3c', '3d'].forEach((turma) => {
            const desktopFilter = document.querySelector(`.search-${turma}`);
            const mobileFilter = document.querySelector(`.search-mobile-${turma}`);
            if (desktopFilter?.value) window.filterTable(turma);
            if (mobileFilter?.value) window.filterCards(turma);
        });
    }

    async function atualizarConsultaSaidasEstagio() {
        const section = document.getElementById('consulta-estagio');
        const tableBody = section?.querySelector('tbody');
        if (!section || !tableBody) return;

        const result = await requestApi('listar_consulta_saidas_estagio');
        const rows = result.saidas.map((saida) => {
            const row = document.createElement('tr');
            row.dataset.consultaRow = '';
            row.dataset.consultaId = String(saida.id_saida);
            row.dataset.consultaType = 'estagio';
            row.dataset.turma = String(saida.id_turma || '');
            row.dataset.data = (saida.dae || '').slice(0, 10);

            const dateCell = document.createElement('td');
            dateCell.dataset.label = 'Data e hora';
            const dateTime = saida.dae || '';
            dateCell.textContent = dateTime
                ? `${formatDate(dateTime.slice(0, 10))} ${dateTime.slice(11, 16)}`
                : 'Não informado';

            const studentCell = document.createElement('td');
            studentCell.dataset.label = 'Aluno';
            const studentName = document.createElement('span');
            studentName.className = 'saida-consulta-primary';
            studentName.textContent = saida.nome || 'Aluno não identificado';
            const studentEnrollment = document.createElement('span');
            studentEnrollment.className = 'saida-consulta-secondary';
            studentEnrollment.textContent = `Matrícula: ${saida.matricula || 'Não informada'}`;
            studentCell.append(studentName, studentEnrollment);

            const classCell = document.createElement('td');
            classCell.dataset.label = 'Turma';
            classCell.textContent = `${saida.ano || ''} ${saida.turma ? saida.turma.toUpperCase() : ''}`.trim() || 'Não informada';

            const operatorCell = document.createElement('td');
            operatorCell.dataset.label = 'Registrado por';
            operatorCell.textContent = saida.usuario_nome || 'Não informado';

            const actionsCell = document.createElement('td');
            actionsCell.dataset.label = 'Ações';
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'saida-consulta-page-button';
            editButton.dataset.editConsultaRecord = '';
            editButton.dataset.editRecord = JSON.stringify({
                tipo: 'estagio',
                id: Number(saida.id_saida),
                aluno: Number(saida.id_aluno),
                dataHora: dateTime
            });
            editButton.setAttribute('aria-label', 'Editar registro de saída para estágio');
            editButton.title = 'Editar registro';
            const editIcon = document.createElement('i');
            editIcon.className = 'fas fa-pen';
            editIcon.setAttribute('aria-hidden', 'true');
            editButton.appendChild(editIcon);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'saida-consulta-page-button';
            deleteButton.dataset.deleteConsultaRecord = '';
            deleteButton.dataset.deleteRecord = JSON.stringify({
                tipo: 'estagio',
                id: Number(saida.id_saida),
                nome: saida.nome || '',
                matricula: saida.matricula || '',
                idTurma: Number(saida.id_turma),
                turma: `${saida.ano || ''} ${saida.turma ? saida.turma.toUpperCase() : ''}`.trim(),
                dataHora: dateTime
            });
            deleteButton.setAttribute('aria-label', 'Excluir registro de saída de estágio');
            deleteButton.title = 'Excluir registro';
            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'fas fa-trash';
            deleteIcon.setAttribute('aria-hidden', 'true');
            deleteButton.appendChild(deleteIcon);
            actionsCell.append(editButton, deleteButton);

            row.append(dateCell, studentCell, classCell, operatorCell, actionsCell);
            return row;
        });

        tableBody.replaceChildren(...rows);
        section.refreshConsultationRows?.();
    }

    async function atualizarConsultaSaidasAntecipadas() {
        const section = document.getElementById('consulta-saida');
        const tableBody = section?.querySelector('tbody');
        if (!section || !tableBody) return;

        const result = await requestApi('listar_consulta_saidas_antecipadas');
        const rows = result.saidas.map((saida) => {
            const row = document.createElement('tr');
            const dateTime = saida.date_time || '';
            const turma = `${saida.ano || ''} ${saida.turma ? saida.turma.toUpperCase() : ''}`.trim();
            row.dataset.consultaRow = '';
            row.dataset.consultaId = String(saida.id_registro_saida);
            row.dataset.consultaType = 'saida';
            row.dataset.turma = String(saida.id_turma || '');
            row.dataset.data = dateTime.slice(0, 10);

            const appendCell = (label, text, className = '') => {
                const cell = document.createElement('td');
                cell.dataset.label = label;
                if (className) {
                    const primary = document.createElement('span');
                    primary.className = className;
                    primary.textContent = text;
                    cell.appendChild(primary);
                } else {
                    cell.textContent = text;
                }
                row.appendChild(cell);
                return cell;
            };
            appendCell('Data e hora', dateTime
                ? `${formatDate(dateTime.slice(0, 10))} ${dateTime.slice(11, 16)}`
                : 'Não informado');
            const studentCell = appendCell('Aluno', saida.nome || 'Aluno não identificado', 'saida-consulta-primary');
            const enrollment = document.createElement('span');
            enrollment.className = 'saida-consulta-secondary';
            enrollment.textContent = `Matrícula: ${saida.matricula || 'Não informada'}`;
            studentCell.appendChild(enrollment);
            appendCell('Turma', turma || 'Não informada');

            const responsibleCell = appendCell('Responsável', saida.nome_responsavel || 'Não informado', 'saida-consulta-primary');
            const responsibleType = document.createElement('span');
            responsibleType.className = 'saida-consulta-secondary';
            responsibleType.textContent = saida.tipo_responsavel || 'Tipo não informado';
            responsibleCell.appendChild(responsibleType);

            const companionCell = appendCell('Acompanhante', saida.nome_conducente || 'Não informado', 'saida-consulta-primary');
            const companionType = document.createElement('span');
            companionType.className = 'saida-consulta-secondary';
            companionType.textContent = saida.tipo_conducente || 'Tipo não informado';
            companionCell.appendChild(companionType);
            appendCell('Motivo', saida.motivo || 'Não informado');
            appendCell('Registrado por', saida.administrador || 'Não informado');

            const actionsCell = document.createElement('td');
            actionsCell.dataset.label = 'Ações';
            const editButton = document.createElement('button');
            editButton.type = 'button';
            editButton.className = 'saida-consulta-page-button';
            editButton.dataset.editConsultaRecord = '';
            editButton.dataset.editRecord = JSON.stringify({
                tipo: 'saida',
                id: Number(saida.id_registro_saida),
                aluno: Number(saida.id_aluno),
                dataHora: dateTime,
                nomeResponsavel: saida.nome_responsavel || '',
                tipoResponsavel: Number(saida.id_tipo_responsavel || 0),
                nomeConducente: saida.nome_conducente || '',
                tipoConducente: Number(saida.id_tipo_conducente || 0),
                motivo: Number(saida.id_motivo || 0)
            });
            editButton.setAttribute('aria-label', 'Editar registro de saída');
            editButton.title = 'Editar registro';
            const editIcon = document.createElement('i');
            editIcon.className = 'fas fa-pen';
            editIcon.setAttribute('aria-hidden', 'true');
            editButton.appendChild(editIcon);

            const deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.className = 'saida-consulta-page-button';
            deleteButton.dataset.deleteConsultaRecord = '';
            deleteButton.dataset.deleteRecord = JSON.stringify({
                tipo: 'saida',
                id: Number(saida.id_registro_saida),
                nome: saida.nome || '',
                matricula: saida.matricula || '',
                idTurma: Number(saida.id_turma || 0),
                turma,
                dataHora: dateTime
            });
            deleteButton.setAttribute('aria-label', 'Excluir registro de saída');
            deleteButton.title = 'Excluir registro';
            const deleteIcon = document.createElement('i');
            deleteIcon.className = 'fas fa-trash';
            deleteIcon.setAttribute('aria-hidden', 'true');
            deleteButton.appendChild(deleteIcon);
            actionsCell.append(editButton, deleteButton);
            row.appendChild(actionsCell);
            return row;
        });

        tableBody.replaceChildren(...rows);
        section.refreshConsultationRows?.();
    }

    async function registrarEAtualizarLista(exit) {
        await registrarSaidaQr(exit);
        showExitConfirmation(exit);
        const updates = await Promise.allSettled([
            atualizarListaSaidas(),
            atualizarConsultaSaidasEstagio()
        ]);
        const failedUpdate = updates.find((result) => result.status === 'rejected');
        if (failedUpdate) {
            const error = failedUpdate.reason;
            console.error('Saída registrada, mas uma das listas não pôde ser atualizada.', error);
            notify(`Saída registrada, mas uma lista não pôde ser atualizada: ${error.message}`, true);
        }
    }

    async function registrarSaidaQr(exit) {
        return requestApi('registrar_saida_estagio_qr', {
            id_aluno: exit.id,
            data: exit.data,
            hora: exit.hora
        });
    }

    async function registrarAtrasoQr(exit) {
        return requestApi('registrar_atraso_qr', {id_aluno: exit.id});
    }

    async function atualizarAtrasosHoje() {
        const validacaoAtrasosDisponivel = <?= $validacaoAtrasosDisponivel ? 'true' : 'false' ?>;
        const result = await requestApi('listar_atrasos_hoje');
        const registrosPorTurma = new Map([[9, []], [10, []], [11, []], [12, []]]);
        result.atrasos.forEach(registro => registrosPorTurma.get(Number(registro.id_turma))?.push(registro));

        for (const [idTurma, letra] of [[9, 'a'], [10, 'b'], [11, 'c'], [12, 'd']]) {
            const lista = document.getElementById(`atrasos-turma-${letra}`);
            const card = lista?.closest('.delay-card');
            if (!lista || !card) continue;

            const registros = registrosPorTurma.get(idTurma) || [];
            const busca = document.querySelector(`[data-delay-search="${letra}"]`)?.value.trim().toLocaleLowerCase('pt-BR') || '';
            const linhas = registros.map(registro => {
                const dateTime = String(registro.date_time || '');
                const [data, horaCompleta = ''] = dateTime.split(' ');
                const turma = `3º Ano ${String(registro.letra_turma || letra).toUpperCase()}`;
                const detalhes = {
                    'Aluno': registro.nome || 'Aluno',
                    'Turma': turma,
                    'Data e hora': `${data.split('-').reverse().join('/')} ${horaCompleta.slice(0, 5)}`,
                    'Registrado por': registro.usuario_nome || 'Não informado',
                    'Responsável': registro.nome_responsavel || 'Não informado',
                    'Tipo de responsável': registro.tipo_responsavel || 'Não informado',
                    'Acompanhante': registro.nome_conducente || 'Não informado',
                    'Tipo de acompanhante': registro.tipo_conducente || 'Não informado',
                    'Motivo': registro.motivo || 'Não informado',
                    'Justificativa do atraso': registro.justificativa_atraso || 'Não informada',
                    'Status da justificativa': registro.status_justificativa || 'pendente',
                    'Validado por': registro.validado_por || 'Ainda não validada',
                    'Validado em': registro.validado_em || 'Ainda não validada',
                    'Justificativa da validação': registro.observacao_validacao || 'Nenhuma justificativa'
                };
                const entry = document.createElement('div');
                entry.className = 'delay-entry';
                entry.dataset.delayEntry = '';
                entry.dataset.name = registro.nome || '';
                entry.dataset.delayRecordId = String(registro.id_registro_entrada || '');
                entry.dataset.delayMatricula = registro.matricula || '';
                entry.dataset.delayTurma = turma;
                entry.dataset.delayDatetime = dateTime;
                entry.tabIndex = 0;
                entry.setAttribute('role', 'button');
                entry.setAttribute('aria-label', `Opções para o atraso de ${registro.nome || 'aluno'}`);
                entry.title = 'Clique para ver as opções deste registro';
                entry.hidden = !String(registro.nome || '').toLocaleLowerCase('pt-BR').includes(busca);

                const name = document.createElement('div');
                name.className = 'delay-entry-name';
                name.textContent = registro.nome || 'Aluno';
                const meta = document.createElement('div');
                meta.className = 'delay-entry-meta';
                const time = document.createElement('time');
                time.textContent = horaCompleta.slice(0, 5);
                const validateButton = document.createElement('button');
                validateButton.type = 'button';
                const status = registro.status_justificativa || 'pendente';
                const canValidate = validacaoAtrasosDisponivel && status === 'pendente';
                validateButton.className = `delay-detail-button ${canValidate ? '' : 'delay-detail-button-secondary'}`;
                validateButton.dataset.delayDetails = JSON.stringify(detalhes);
                validateButton.dataset.recordId = String(registro.id_registro_entrada);
                validateButton.dataset.motiveId = String(registro.id_motivo || '');
                validateButton.dataset.status = status;
                validateButton.dataset.canValidate = canValidate ? '1' : '0';
                const icon = document.createElement('i');
                icon.className = `fas ${canValidate ? 'fa-check' : 'fa-eye'}`;
                icon.setAttribute('aria-hidden', 'true');
                validateButton.append(icon, document.createTextNode(canValidate ? 'Validar' : 'Detalhes'));
                meta.append(time, validateButton);
                entry.append(name, meta);
                return entry;
            });

            lista.replaceChildren(...linhas);
            const noResults = document.createElement('p');
            noResults.className = 'delay-empty';
            noResults.dataset.delayNoResults = '';
            noResults.hidden = linhas.some(linha => !linha.hidden);
            noResults.textContent = registros.length ? 'Nenhum aluno encontrado' : 'Nenhum atraso registrado hoje';
            lista.appendChild(noResults);
            card.querySelector('.delay-count').textContent = String(registros.length);
        }
    }

    function getStudentIdentifier(decodedText) {
        const scannedValue = typeof decodedText === 'string' ? decodedText.trim() : '';
        if (!scannedValue) return null;

        try {
            const qrUrl = new URL(scannedValue, window.location.href);
            const params = qrUrl.searchParams;
            const identifier = params.get('id_aluno') || params.get('matricula') || params.get('nome');
            if (identifier) return identifier.trim();
        } catch (error) {}

        if (/^[a-z][a-z\d+.-]*:/i.test(scannedValue) || /^www\./i.test(scannedValue)) {
            return null;
        }

        try {
            return decodeURIComponent(scannedValue.replace(/\+/g, ' ')).trim();
        } catch (error) {
            return scannedValue.replace(/\+/g, ' ').trim();
        }
    }

    async function handleScannedQr(decodedText) {
        const identifier = getStudentIdentifier(decodedText);
        if (!identifier) {
            notify('Este QR Code não contém ID, matrícula ou nome reconhecido do aluno.', true);
            return;
        }

        qrBusy = true;
        try {
            const prepared = await requestApi('preparar_saida_estagio_qr', {id_aluno: identifier});
            const exit = {
                id: String(prepared.aluno.id_aluno),
                matricula: prepared.aluno.matricula,
                nome: prepared.aluno.nome,
                turma: prepared.aluno.letra_turma ? `3º Ano ${prepared.aluno.letra_turma}` : '',
                data: prepared.data,
                hora: prepared.hora
            };
            const isDelayWindow = prepared.isDelayWindow === true;
            const destination = isDelayWindow ? 'atrasos' : 'ultimas-saidas';
            if (!document.getElementById(destination)?.classList.contains('active')) {
                document.querySelector(`.nav-item[data-target="${destination}"]`)?.click();
            }

            if (isDelayWindow) {
                if (prepared.delayStatus) {
                    const messages = {
                        pendente: 'Este atraso já está registrado e aguarda validação.',
                        aprovada: 'Este aluno já possui um atraso aprovado hoje.',
                        recusada: 'Este aluno já possui um atraso recusado hoje.'
                    };
                    if (prepared.delayStatus === 'pendente' || prepared.delayStatus === 'recusada') {
                        if (prepared.delayStatus === 'pendente') {
                            showExitConfirmation(exit, false, '', {
                                pending: true,
                                dateTime: prepared.delayRecord?.date_time || ''
                            });
                        } else {
                            showExitConfirmation(
                                exit,
                                true,
                                'A justificativa do atraso foi recusada. O estágio está bloqueado hoje.'
                            );
                        }
                    } else {
                        notify(messages[prepared.delayStatus] || 'Este aluno já possui um atraso registrado hoje.', true);
                    }
                    qrBusy = false;
                    document.getElementById('urlInput')?.focus();
                    return;
                }

                const result = await registrarAtrasoQr(exit);
                try {
                    await atualizarAtrasosHoje();
                    notify(result.message);
                } catch (error) {
                    notify('Atraso registrado, mas a lista não pôde ser atualizada. Abra Atrasos registrados para conferir.', true);
                }
                document.dispatchEvent(new CustomEvent('stage-qr-status', {
                    detail: {message: 'Atraso registrado. Leitor pronto para o próximo crachá.', error: false}
                }));
                qrBusy = false;
                document.getElementById('urlInput')?.focus();
                return;
            }

            if (!prepared.priorizarSaidaEstagio && prepared.delayStatus === 'recusada') {
                showExitConfirmation(exit, true);
                qrBusy = false;
                document.getElementById('urlInput')?.focus();
                return;
            }

            if (!prepared.priorizarSaidaEstagio && prepared.delayStatus === 'pendente') {
                showExitConfirmation(
                    exit,
                    false,
                    '',
                    {
                        pending: true,
                        dateTime: prepared.delayRecord?.date_time || ''
                    }
                );
                qrBusy = false;
                document.getElementById('urlInput')?.focus();
                return;
            }

            await registrarEAtualizarLista(exit);
            qrBusy = false;
            document.getElementById('urlInput')?.focus();
        } catch (error) {
            qrBusy = false;
            const qrInput = document.getElementById('urlInput');
            if (qrInput) qrInput.value = '';
            notify(error.message, true);
            document.dispatchEvent(new CustomEvent('stage-qr-status', {
                detail: { message: error.message, error: true }
            }));
        }
    }

    document.addEventListener('stage-qr-scanned', (event) => {
        if (!qrBusy) handleScannedQr(event.detail.identifier);
    });

    document.addEventListener('stage-exit-recorded', async () => {
        const updates = await Promise.allSettled([
            atualizarListaSaidas(),
            atualizarConsultaSaidasEstagio()
        ]);
        document.querySelector('.nav-item[data-target="ultimas-saidas"]')?.click();
        const failedUpdate = updates.find((result) => result.status === 'rejected');
        if (failedUpdate) {
            const error = failedUpdate.reason;
            console.error('Saída de estágio registrada, mas uma das listas não pôde ser atualizada.', error);
            notify(`Saída para estágio registrada, mas uma lista não pôde ser atualizada: ${error.message}`, true);
        }
    });

    document.addEventListener('manual-delay-recorded', async () => {
        try {
            await atualizarAtrasosHoje();
        } catch (error) {
            console.error('Atraso registrado, mas a lista não pôde ser atualizada.', error);
            notify(`Atraso registrado, mas a lista não pôde ser atualizada: ${error.message}`, true);
        }
        document.querySelector('.nav-item[data-target="atrasos"]')?.click();
    });

    document.addEventListener('manual-early-exit-recorded', async () => {
        try {
            await atualizarConsultaSaidasAntecipadas();
        } catch (error) {
            console.error('Saída antecipada registrada, mas a consulta não pôde ser atualizada.', error);
            notify(`Saída registrada, mas a consulta não pôde ser atualizada: ${error.message}`, true);
        }
        document.querySelector('.nav-item[data-target="consulta-saida"]')?.click();
    });

})();
</script>
</body>
</html>