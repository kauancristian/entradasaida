<?php
require_once __DIR__ . '/../model/select_model.php';
require_once __DIR__ . '/../model/sessions.php';
$select = new select_model();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$validacaoAtrasosDisponivel = $select->suportaValidacaoAtrasos();
$sectionInicial = $_GET['section'] ?? 'entrada';
$sectionsPermitidas = ['inicio', 'entrada', 'saida', 'estagio', 'relatorios', 'relatorio-entrada', 'relatorio-saida', 'relatorio-estagio', 'relatorio-dia', 'qrcode', 'ultimas-saidas', 'atrasos', 'cadastro'];
if (!in_array($sectionInicial, $sectionsPermitidas, true)) {
    $sectionInicial = 'entrada';
}
$statusRegistro = $_GET['status'] ?? '';
$mensagensRegistro = [
    'success' => 'Registro salvo com sucesso no banco de dados.',
    'ja_registrado' => 'Já existe um registro para este aluno nesta data.',
    'aluno_nao_encontrado' => 'O aluno selecionado não foi encontrado.',
    'campos_obrigatorios' => 'Preencha todos os campos obrigatórios, incluindo o tipo de acompanhante.',
    'data_hora_futura' => 'Não é permitido registrar uma data ou horário futuro. Escolha um horário até o momento atual.',
    'erro_interno' => 'Não foi possível salvar no banco. Confira os dados e tente novamente.',
    'erro_desconhecido' => 'O registro não foi concluído. Atualize a página e tente novamente.'
];
$mensagemRegistro = $mensagensRegistro[$statusRegistro] ?? '';
$classeMensagemRegistro = $statusRegistro === 'success'
    ? 'border-green-200 bg-green-50 text-green-800'
    : ($statusRegistro === 'ja_registrado'
        ? 'border-amber-200 bg-amber-50 text-amber-900'
        : 'border-red-200 bg-red-50 text-red-800');
$atrasosPorTurma = [9 => [], 10 => [], 11 => [], 12 => []];
foreach ($select->atrasosHoje() as $registroAtraso) {
    $idTurmaAtraso = (int) $registroAtraso['id_turma'];
    if (isset($atrasosPorTurma[$idTurmaAtraso])) {
        $atrasosPorTurma[$idTurmaAtraso][] = $registroAtraso;
    }
}
$mostrarAtrasoTeste = ($_GET['demo_atrasos'] ?? '') === '1';
if ($mostrarAtrasoTeste) {
    $atrasosPorTurma[9][] = [
        'id_turma' => 9,
        'nome' => 'ALUNO TESTE',
        'letra_turma' => 'a',
        'date_time' => date('Y-m-d') . ' 08:30:00',
        'nome_responsavel' => 'Responsável de teste',
        'tipo_responsavel' => 'Responsável',
        'nome_conducente' => 'Acompanhante de teste',
        'tipo_conducente' => 'Responsável',
        'motivo' => 'Demonstração',
        'is_test' => true
    ];
}
$mostrarAtrasoTeste = ($_GET['demo_atrasos'] ?? '') === '1';
if ($mostrarAtrasoTeste) {
    $atrasosPorTurma[9][] = [
        'id_turma' => 9,
        'nome' => 'ALUNO TESTE',
        'letra_turma' => 'a',
        'date_time' => date('Y-m-d') . ' 08:30:00',
        'nome_responsavel' => 'Responsável de teste',
        'tipo_responsavel' => 'Responsável',
        'nome_conducente' => 'Acompanhante de teste',
        'tipo_conducente' => 'Responsável',
        'motivo' => 'Demonstração',
        'status_justificativa' => 'pendente',
        'is_test' => true
    ];
}
$historicoAtrasos = $select->historicoAtrasos();
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
        'Turma' => '3º Ano ' . strtoupper($registro['letra_turma']),
        'Data e hora' => date('d/m/Y H:i', strtotime($registro['date_time'])),
        'Responsável' => $registro['nome_responsavel'] ?: 'Não informado',
        'Tipo de responsável' => $registro['tipo_responsavel'] ?: 'Não informado',
        'Acompanhante' => $registro['nome_conducente'] ?: 'Não informado',
        'Tipo de acompanhante' => $registro['tipo_conducente'] ?: 'Não informado',
        'Motivo' => $registro['motivo'] ?: 'Não informado',
        'Status da justificativa' => [
            'pendente' => 'Pendente',
            'aprovada' => 'Aprovada',
            'recusada' => 'Recusada'
        ][$registro['status_justificativa'] ?? 'pendente'] ?? 'Pendente',
        'Validado por' => ($registro['validado_por'] ?? '') ?: 'Ainda não validada',
        'Validado em' => !empty($registro['validado_em']) ? date('d/m/Y H:i', strtotime($registro['validado_em'])) : 'Ainda não validada',
        'Observação da validação' => ($registro['observacao_validacao'] ?? '') ?: 'Nenhuma observação'
    ];
    if (!empty($registro['is_test'])) {
        $detalhes['Aviso'] = 'Exemplo de demonstração; não foi gravado no banco.';
    }
    return $detalhes;
};
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistema Escolar Salaberga</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>tailwind={config:{theme:{extend:{colors:{'ceara-green':'#008C45','ceara-light-green':'#3CB371','ceara-olive':'#8CA03E','ceara-orange':'#FFA500',primary:'#4CAF50',secondary:'#FFB74D',danger:'#dc3545',admin:'#0dcaf0',grey:'#6c757d',info:'#4169E1'}},fontFamily:{inter:['Inter','sans-serif']}}}};</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
:root{--salaberga-dark:#064c2b;--salaberga-green:#008c45;--salaberga-active:#276448;--salaberga-amber:#f3a11a;--salaberga-workspace:#eaf3e8;--salaberga-ink:#14251c;--salaberga-muted:#64736a}
*{box-sizing:border-box}
html,body{margin:0;min-height:100%;font-family:'Inter',sans-serif;color:var(--salaberga-ink);background:var(--salaberga-workspace)}
.sidebar{position:fixed;inset:0 auto 0 0;z-index:1000;width:256px;display:flex;flex-direction:column;padding:25px 16px 18px;background:var(--salaberga-dark);color:#f5fff8}
.brand{display:flex;align-items:center;gap:11px;margin:2px 7px 35px;color:#fff;text-decoration:none;font-size:15px;font-weight:700}.brand-mark{display:grid;width:40px;height:40px;place-items:center;border:1px solid #ffffff55;border-radius:11px;color:#ffc04d;font-size:20px}.brand small{display:block;margin-top:4px;color:#b6d4c1;font-size:10px;font-weight:400}
.sidebar>nav{flex:1;min-height:0;overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#3c7254 transparent}.nav-group{margin-bottom:20px}.nav-label{margin:0 10px 8px;color:#8eb9a0;font-size:9px;font-weight:700;letter-spacing:.8px;text-transform:uppercase}.nav-group>summary.nav-label{display:flex;align-items:center;justify-content:space-between;cursor:pointer;list-style:none}.nav-group>summary.nav-label::-webkit-details-marker{display:none}.nav-group>summary.nav-label::after{width:6px;height:6px;margin-right:3px;border-right:1.5px solid currentColor;border-bottom:1.5px solid currentColor;content:'';transform:rotate(45deg);transition:transform .18s ease}.nav-group[open]>summary.nav-label::after{transform:rotate(225deg)}.nav-group>summary.nav-label:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:3px}.nav-item{position:relative;width:100%;min-height:40px;display:flex;align-items:center;gap:12px;margin:3px 0;padding:0 11px;border:0;border-radius:7px;background:transparent;color:#e1f0e6;text-align:left;font:600 12px 'Inter',sans-serif;cursor:pointer}.nav-item i{width:16px;color:#b8d7c3;text-align:center}.nav-item:hover{background:#ffffff1a;color:#fff}.nav-item.active{background:var(--salaberga-active);color:#ffc04d}.nav-item.active:before{position:absolute;inset:8px auto 8px 0;width:3px;border-radius:0 3px 3px 0;background:var(--salaberga-amber);content:''}.nav-item.active i{color:#ffc04d}.sidebar-bottom{margin-top:auto}.nav-item.logout{color:#f0d5ce}.nav-item.logout i{color:#f1aa91}
.nav-group>summary.nav-label{min-height:34px;margin:0 2px 8px;padding:0 10px;border:1px solid #ffffff24;border-radius:6px;background:#ffffff0a;color:#c5dfce;font-size:10px;transition:background .18s ease,color .18s ease}.nav-group>summary.nav-label:hover{background:#ffffff16;color:#fff}.nav-item{font-size:13px}.nav-item i{color:#c5dfce}.nav-item:focus-visible{outline:2px solid var(--salaberga-amber);outline-offset:2px}.nav-group[open]>.nav-item{animation:sidebar-submenu-in .2s ease both}.nav-group[open]>.nav-item:nth-child(3){animation-delay:35ms}.nav-group[open]>.nav-item:nth-child(4){animation-delay:70ms}.nav-group[open]>.nav-item:nth-child(5){animation-delay:105ms}.nav-group[open]>.nav-item:nth-child(6){animation-delay:140ms}@keyframes sidebar-submenu-in{from{opacity:0;transform:translateY(-5px)}to{opacity:1;transform:translateY(0)}}
.main-content{min-height:100vh;margin-left:256px;padding:34px clamp(20px,4vw,60px);}.page-section{display:none;max-width:1200px;margin:0 auto}.page-section.active{display:block}.section-title{margin:0 0 22px;color:#08723a;font-size:25px}.section-lead{margin:-14px 0 22px;color:var(--salaberga-muted);font-size:13px}.page-section [class*="max-w-"]{max-width:100%}.page-section .container{width:100%;max-width:100%;margin-left:auto;margin-right:auto}.page-section .fixed{z-index:900}
#atrasos{max-width:none;width:100%;margin:0}.delays-shell{position:relative;min-height:calc(100dvh - 68px);padding:16px;border-radius:12px;background:#fff;box-shadow:0 2px 8px #143b2114}.delays-heading{text-align:center;margin:14px 0 30px}.delays-topline{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}.delays-back{position:absolute;top:16px;left:16px;display:inline-flex;align-items:center;gap:9px;padding:9px 14px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#53615a;box-shadow:0 2px 6px #00000012;font:500 13px 'Inter',sans-serif;cursor:pointer}.delays-back i{color:#008c45}.delays-title{margin:0;font-size:30px;font-weight:700}.delays-clock{padding:8px 16px;border:1px solid #e5e7eb;border-radius:8px;background:#fff;color:#344254;box-shadow:0 1px 3px #0000000c;font-size:19px;font-weight:700;font-variant-numeric:tabular-nums}.delays-note{margin:14px 0 0;color:#64736a;font-size:13px}.delays-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.delay-card{min-height:350px;overflow:hidden;border:1px solid #e5e7eb;border-radius:12px;background:#fff;box-shadow:0 2px 6px #143b2118}.delay-card-header{min-height:99px;padding:16px;color:white}.delay-card-header.turma-3a{background:#cf2035}.delay-card-header.turma-3b{background:#3854d9}.delay-card-header.turma-3c{background:#0db5d0}.delay-card-header.turma-3d{background:#59636b}.delay-card-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px}.delay-card-heading h2{margin:0;font-size:17px;font-weight:700}.delay-count{width:34px;height:28px;border-radius:18px;background:#fff}.delay-search{width:min(100%,200px);height:32px;padding:0 12px;border:1px solid #ffffff70;border-radius:6px;background:#ffffff30;color:#fff;font:400 13px 'Inter',sans-serif}.delay-search::placeholder{color:#ffffffc9}.delay-search:focus{outline:2px solid #fff;outline-offset:1px}.delay-empty{padding:44px 14px;color:#64748b;text-align:center;font-size:15px;font-style:italic}
#relatorio-entrada > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
.delay-list{min-height:230px;max-height:420px;overflow-y:auto;padding:8px 12px}.delay-entry{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:10px 2px;border-bottom:1px solid #edf0ee}.delay-entry:last-child{border-bottom:0}.delay-entry-name{overflow:hidden;color:#26352c;font-size:13px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.delay-entry-meta{display:flex;align-items:center;gap:10px;color:#64736a;font-size:12px;white-space:nowrap}.delay-detail-button{border:0;background:transparent;color:#08723a;font:600 12px 'Inter',sans-serif;cursor:pointer}.delay-detail-button:hover{text-decoration:underline}.delay-count{display:grid;place-items:center;color:#23352a;font-size:12px;font-weight:700}.delay-search:disabled{opacity:1;cursor:not-allowed}.delay-history{margin-top:30px;padding-top:20px;border-top:1px solid #e7ece8}.delay-history h2{margin:0;color:#08723a;font-size:21px;font-weight:700}.delay-history-note{margin:6px 0 14px;color:#64736a;font-size:13px}.delay-history-table{width:100%;border-collapse:collapse;background:#fff}.delay-history-table th,.delay-history-table td{padding:11px 12px;border-bottom:1px solid #e7ece8;text-align:left;font-size:13px}.delay-history-table th{background:#f4f8f3;color:#53615a;font-weight:700}.delay-history-empty{padding:24px 12px;border:1px solid #e7ece8;border-radius:8px;color:#64736a;text-align:center;font-size:14px}.delay-dialog{width:min(520px,calc(100vw - 32px));max-height:calc(100dvh - 32px);padding:0;border:0;border-radius:10px;box-shadow:0 16px 48px #102b1c40}.delay-dialog::backdrop{background:#10251bb0}.delay-dialog-header{display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e7ece8}.delay-dialog-header h2{margin:0;color:#08723a;font-size:19px}.delay-dialog-close{width:34px;height:34px;border:0;border-radius:6px;background:#f1f5f2;color:#43534a;cursor:pointer}.delay-dialog-content{padding:18px 20px}.delay-dialog-row{display:grid;grid-template-columns: minmax(130px, 1fr) 2fr;gap:12px;padding:9px 0;border-bottom:1px solid #edf0ee;font-size:14px}.delay-dialog-row:last-child{border-bottom:0}.delay-dialog-label{color:#64736a}.delay-dialog-value{color:#203128;font-weight:600;overflow-wrap:anywhere}
#relatorio-estagio > .main-content{width:100%;min-height:0;margin-left:0;padding:0}
#ultimas-saidas{width:calc(100% + min(16px,1.5vw) - 40px);max-width:none;margin-left:12px;margin-right:calc(-1 * min(16px,1.5vw))}
#ultimas-saidas .main-container{width:100%;max-width:none}
.page-section [id$="Modal"]:not([id$="ModalContent"]){z-index:1100;max-height:100dvh;padding:16px;overflow-y:auto;overscroll-behavior:contain}
.page-section [id$="ModalContent"]{width:min(100%,28rem);height:min(520px,calc(100dvh - 32px));max-height:calc(100dvh - 32px);margin:0;overflow-y:auto;overscroll-behavior:contain;scrollbar-gutter:stable;display:flex;flex-direction:column;justify-content:center}
@media(max-width:1100px){#ultimas-saidas{width:100%;margin-left:0;margin-right:0}}
@media(max-width:1100px){.delays-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.delays-back{position:static;margin-bottom:18px}.delays-shell{min-height:calc(100dvh - 100px)}}
@media(max-width:600px){.delays-grid{grid-template-columns:1fr}.delay-card{min-height:300px}.delays-title{font-size:25px}.delays-clock{font-size:16px}.delays-heading{margin:8px 0 22px}}
.page-section .bg-white{background:#fff}.page-section .rounded-xl,.page-section .rounded-2xl{border-radius:8px}.page-section .shadow-lg,.page-section .shadow-md{box-shadow:0 2px 7px #143b2114}
.page-section .bg-gradient-to-r.from-ceara-green.to-ceara-light-green{background:linear-gradient(100deg,var(--salaberga-dark),var(--salaberga-green))!important}
.page-section .bg-gray-50,.page-section .bg-slate-50,.page-section .bg-gray-100{background:#f4f8f3}.page-section .text-ceara-green,.page-section .text-green-600,.page-section .text-green-700{color:var(--salaberga-green)}.page-section .bg-ceara-green,.page-section .bg-green-600,.page-section .bg-green-700{background-color:var(--salaberga-green)}
.mobile-menu,.sidebar-close,.sidebar-scrim{display:none}
@media(max-width:760px){.sidebar{width:min(290px,84vw);transform:translateX(-102%);transition:transform .22s ease}.sidebar-open .sidebar{transform:translateX(0)}.sidebar-close{position:absolute;top:20px;right:14px;display:grid;width:34px;height:34px;place-items:center;border:1px solid #ffffff44;border-radius:7px;background:transparent;color:white}.brand{margin-right:38px}.mobile-menu{position:fixed;top:10px;left:12px;z-index:999;display:grid;width:38px;height:38px;place-items:center;border:0;border-radius:7px;background:var(--salaberga-dark);color:#fff}.sidebar-scrim{position:fixed;inset:0;z-index:998;display:none;border:0;background:#081f126b}.sidebar-open .sidebar-scrim{display:block}.main-content{margin-left:0;padding:62px 15px 24px}#ultimas-saidas{width:100%;margin-left:0;margin-right:0}.page-section [id$="Modal"]:not([id$="ModalContent"]){padding:12px}.page-section [id$="ModalContent"]{height:min(520px,calc(100dvh - 24px));max-height:calc(100dvh - 24px);padding:24px}}
@media(prefers-reduced-motion:reduce){*,*::before,*::after{scroll-behavior:auto!important;transition-duration:.01ms!important}.nav-group[open]>.nav-item{animation:none!important}}

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

        #relatorio-dia .report-generate-button {
            display: inline-flex;
            min-height: 46px;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 24px;
            border: 1px solid #08733e;
            border-radius: 8px;
            background: linear-gradient(110deg, #08733e, #20a45a);
            color: #fff;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(8, 115, 62, 0.2);
            transition: background 160ms ease, box-shadow 160ms ease, transform 160ms ease;
        }

        #relatorio-dia .report-generate-button:hover {
            background: linear-gradient(110deg, #065c32, #168849);
            box-shadow: 0 5px 12px rgba(8, 115, 62, 0.26);
            transform: translateY(-1px);
        }

        #relatorio-dia .report-generate-button:focus-visible {
            outline: 3px solid rgba(32, 164, 90, 0.35);
            outline-offset: 3px;
        }

        #relatorio-dia .report-generate-button i {
            font-size: 16px;
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
    
 .delay-history-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}.delay-history-table{border:1px solid #e7ece8;border-radius:8px;overflow:hidden}.delay-history-table th{position:sticky;top:0;z-index:1}.delay-history-table tbody tr:nth-child(even){background:#fafcf9}.delay-history-table tbody tr:hover{background:#f1f7f1}.delay-status{display:inline-flex;align-items:center;padding:4px 9px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}.delay-status-pendente{background:#fff4d6;color:#8b5a00}.delay-status-aprovada{background:#e4f5e9;color:#176b38}.delay-status-recusada{background:#fde8e7;color:#9b2c25}.delay-dialog{width:min(540px,calc(100vw - 28px));overflow-y:auto}.delay-dialog-header{align-items:flex-start}.delay-dialog-heading{min-width:0}.delay-dialog-heading p{margin:5px 0 0;color:#64736a;font-size:13px}.delay-dialog-content{max-height:min(42dvh,360px);overflow-y:auto}.delay-validation-summary{margin-bottom:16px;padding:14px 16px;border:1px solid #ffdf83;border-radius:8px;background:#fffbed}.delay-validation-summary-label{margin:0 0 5px;color:#9a4b08;font-size:11px;font-weight:700;text-transform:uppercase}.delay-validation-summary-name{margin:0;color:#17251c;font-size:16px;font-weight:700}.delay-validation-summary-meta{margin:4px 0 0;color:#47574c;font-size:13px}.delay-justification-label,.delay-form-label{display:block;margin:0 0 7px;color:#26372c;font-size:13px;font-weight:700}.delay-justification{width:100%;min-height:74px;resize:vertical;padding:10px 12px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#17251c;font:400 14px/1.5 'Inter',sans-serif}.delay-extra-details{margin-top:14px;border-top:1px solid #e7ece8}.delay-extra-details summary{padding:12px 0 4px;color:#08723a;font-size:12px;font-weight:700;cursor:pointer}.delay-extra-details .delay-dialog-row{padding:8px 0}.delay-validation-form{display:grid;gap:14px}.delay-form-field{display:grid;gap:6px}.delay-form-control{width:100%;min-height:42px;padding:9px 12px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#17251c;font:400 14px 'Inter',sans-serif}.delay-form-control:focus,.delay-justification:focus{outline:2px solid #008c4530;border-color:#008c45}.delay-form-control:disabled{background:#f2f5f2;color:#43534a;opacity:1}.delay-validation-submit{display:flex;min-height:46px;align-items:center;justify-content:center;gap:9px;border:0;border-radius:7px;background:#008c45;color:#fff;font:700 14px 'Inter',sans-serif;cursor:pointer}.delay-validation-submit:hover{background:#06753e}
.delay-dialog[open]{position:fixed;inset:0;margin:auto;width:min(455px,calc(100vw - 20px));max-height:calc(100dvh - 12px);overflow-y:auto;border:1px solid #dfe5e1;border-radius:10px;background:#fff;box-shadow:0 18px 56px #071c2d44}.delay-dialog::backdrop{background:#071426c7}.delay-dialog-header{padding:17px 20px;background:#f8faf9}.delay-dialog-heading{display:flex;align-items:flex-start;gap:10px}.delay-dialog-heading::before{content:'\f058';flex:none;margin-top:2px;color:#008c45;font-family:'Font Awesome 6 Free';font-size:17px;font-weight:900}.delay-dialog-heading h2{font-size:19px}.delay-dialog-heading p{line-height:1.45}.delay-dialog-content{max-height:none;overflow:visible;padding:16px 20px}.delay-validation-summary{padding:12px 15px}.delay-justification{min-height:88px}.delay-demo-note{margin:0 0 17px;color:#64736a;font-size:12px;line-height:1.5}.delay-validation-form{gap:12px;padding:14px 20px 18px}.delay-form-control{min-height:40px}.delay-validation-form[hidden],.delay-form-field[hidden]{display:none}.delay-detail-button{display:inline-flex;min-height:32px;align-items:center;justify-content:center;gap:7px;padding:0 11px;border:1px solid #008c45;border-radius:6px;background:#008c45;color:#fff;font:600 12px 'Inter',sans-serif;text-decoration:none;cursor:pointer}.delay-detail-button:hover{background:#06753e;text-decoration:none}.delay-detail-button-secondary{border-color:#d5e3d9;background:#f2f8f3;color:#08723a}.delay-detail-button-secondary:hover{background:#e4f1e7}.delay-decision-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.delay-decision-option{display:flex;min-height:48px;align-items:center;justify-content:center;gap:8px;padding:9px 10px;border:1px solid #cbd5ce;border-radius:7px;background:#fff;color:#43534a;font:600 13px 'Inter',sans-serif;cursor:pointer}.delay-decision-option-approve{border-color:#b5ddc2;color:#176b38}.delay-decision-option-approve:hover,.delay-decision-option-approve[aria-pressed="true"]{border-color:#008c45;background:#e7f5eb;color:#08723a;box-shadow:inset 0 0 0 1px #008c45}.delay-decision-option-reject{border-color:#f0c4c1;color:#9b2c25}.delay-decision-option-reject:hover,.delay-decision-option-reject[aria-pressed="true"]{border-color:#c9362b;background:#fff0ef;color:#a62f26;box-shadow:inset 0 0 0 1px #c9362b}.delay-validation-submit.is-rejection{background:#c9362b}.delay-validation-submit.is-rejection:hover{background:#a62f26}.delay-validation-submit:disabled{opacity:.55;cursor:not-allowed}
.delay-history-filters{display:grid;grid-template-columns:minmax(180px,1.5fr) repeat(4,minmax(130px,1fr)) auto;align-items:end;gap:10px;margin:18px 0 20px;padding:14px;border:1px solid #e3eae5;border-radius:8px;background:#f8faf8}.delay-history-filter{display:grid;gap:6px;color:#53615a;font-size:11px;font-weight:700}.delay-history-filter input,.delay-history-filter select{width:100%;height:39px;padding:0 10px;border:1px solid #d5ded8;border-radius:6px;background:#fff;color:#26352c;font:400 13px 'Inter',sans-serif}.delay-history-filter input:focus,.delay-history-filter select:focus{outline:2px solid #008c4555;border-color:#008c45}.delay-history-reset{height:39px;padding:0 13px;border:1px solid #d5ded8;border-radius:6px;background:#fff;color:#43534a;font:600 12px 'Inter',sans-serif;cursor:pointer}.delay-history-reset:hover{background:#edf5ef}.delay-history-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.delay-history-card{overflow:hidden;border:1px solid #e1e8e3;border-radius:8px;background:#fff}.delay-history-card-header{display:flex;align-items:center;justify-content:space-between;padding:13px 15px;border-bottom:1px solid #e7ece8;background:#f7faf7}.delay-history-card-header h3{margin:0;color:#183c27;font-size:15px;font-weight:700}.delay-history-count{display:grid;min-width:27px;height:25px;place-items:center;padding:0 7px;border-radius:14px;background:#e5f3e9;color:#176b38;font-size:11px;font-weight:700}.delay-history-list{max-height:330px;overflow-y:auto;padding:0 14px}.delay-history-entry{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:7px 12px;padding:12px 1px;border-bottom:1px solid #edf0ee}.delay-history-entry:last-child{border-bottom:0}.delay-history-entry[hidden],.delay-history-card[hidden]{display:none}.delay-history-entry-name{overflow:hidden;color:#26352c;font-size:13px;font-weight:600;text-overflow:ellipsis;white-space:nowrap}.delay-history-entry-meta{display:flex;align-items:center;gap:8px;color:#64736a;font-size:11px;white-space:nowrap}.delay-history-entry-action{grid-column:1/-1;justify-self:start}.delay-history-empty{padding:22px 14px;color:#64736a;text-align:center;font-size:13px}.delay-history-no-results{margin:14px 0 0;padding:20px;border:1px dashed #cbd8cf;border-radius:8px;color:#64736a;text-align:center;font-size:13px}.delay-history-no-results[hidden]{display:none}
@media(max-width:1100px){.delay-history-filters{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:600px){.delay-history-filters{grid-template-columns:repeat(2,minmax(0,1fr));padding:11px}.delay-history-filter:first-child{grid-column:1/-1}.delay-history-grid{grid-template-columns:1fr}.delay-history-entry{grid-template-columns:minmax(0,1fr)}.delay-history-entry-meta{flex-wrap:wrap}}
.delay-history-filters{grid-template-columns:minmax(170px,1.4fr) minmax(145px,.9fr) minmax(145px,.9fr) minmax(260px,1.5fr) auto;gap:12px}.delay-history-date-range{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;min-width:0}.delay-history-filter input,.delay-history-filter select{min-width:0}.delay-history-grid{align-items:start;gap:16px}.delay-history-card{border-color:#dce5df;box-shadow:0 2px 7px #143b2110}.delay-history-card-header{min-height:54px;border-bottom:0;background:#59636b;color:#fff}.delay-history-card-header h3{color:#fff}.delay-history-card-header h3 i{margin-right:4px}.delay-history-count{background:#ffffff2e;color:#fff}.delay-history-card.turma-3a .delay-history-card-header{background:#cf2035}.delay-history-card.turma-3b .delay-history-card-header{background:#3854d9}.delay-history-card.turma-3c .delay-history-card-header{background:#0b9bb2}.delay-history-card.turma-3d .delay-history-card-header{background:#59636b}.delay-history-list{max-height:360px;padding:0 16px}.delay-history-entry{grid-template-columns:minmax(0,1fr) auto auto;gap:10px;padding:13px 1px}.delay-history-entry-meta{gap:9px}.delay-history-entry-action{grid-column:auto}.delay-history-empty{margin:0;padding:20px 8px;border:0;border-radius:0;background:transparent;color:#758078;font-size:12px}
@media(max-width:1100px){.delay-history-filters{grid-template-columns:repeat(3,minmax(0,1fr))}.delay-history-date-range{grid-column:span 2}}
@media(max-width:600px){.delay-history-filters{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.delay-history-filter:first-child,.delay-history-date-range{grid-column:1/-1}.delay-history-date-range{gap:8px}.delay-history-grid{grid-template-columns:1fr}.delay-history-entry{grid-template-columns:minmax(0,1fr) auto;gap:8px}.delay-history-entry-meta{grid-column:1/-1;grid-row:2;flex-wrap:wrap}.delay-history-entry-action{grid-column:2;grid-row:1}}
</style>
</head>
<body><aside class="sidebar" aria-label="Navegação principal">
  <button class="sidebar-close" type="button" aria-label="Fechar menu"><i class="fas fa-times" aria-hidden="true"></i></button>
  <a class="brand" href="#" data-section-target="inicio"><span class="brand-mark">S</span><span>Sistema Salaberga<small>Entradas e saídas escolares</small></span></a>
    <nav>
        <details class="nav-group" data-sidebar-group="registros"><summary class="nav-label">Registros</summary><button class="nav-item" type="button" data-target="entrada"><i class="fas fa-arrow-right-to-bracket"></i>Manual de entrada</button><button class="nav-item" type="button" data-target="saida"><i class="fas fa-arrow-right-from-bracket"></i>Manual de saída</button><button class="nav-item" type="button" data-target="estagio"><i class="fas fa-briefcase"></i>Saída para estágio</button><button class="nav-item" type="button" data-target="cadastro"><i class="fas fa-user-plus"></i>Cadastrar aluno</button></details>
        <details class="nav-group" data-sidebar-group="consultas"><summary class="nav-label">Consultas</summary><button class="nav-item" type="button" data-target="relatorio-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saída antecipada</button><button class="nav-item" type="button" data-target="relatorio-estagio"><i class="fas fa-user-tie"></i>Saídas de estágio</button><button class="nav-item" type="button" data-target="atrasos"><i class="fas fa-clock-rotate-left"></i>Atrasos registrados</button><button class="nav-item" type="button" data-target="ultimas-saidas"><i class="fas fa-clock"></i>Últimas Saídas</button></details>
        <details class="nav-group" data-sidebar-group="relatorios"><summary class="nav-label">Relatórios</summary><button class="nav-item report-nav" type="button" data-target="relatorio-dia"><i class="fas fa-calendar-day"></i>Atrasos registrados</button><button class="nav-item report-nav" type="button" data-target="relatorio-saida"><i class="fas fa-arrow-right-from-bracket"></i>Saídas antecipadas</button><button class="nav-item report-nav" type="button" data-target="relatorio-estagio"><i class="fas fa-user-tie"></i>Saídas de estágio</button><a class="nav-item report-nav" href="relatorios/pre_estagio.php" target="_blank" rel="noopener"><i class="fas fa-graduation-cap"></i>Preparação para estágio</a><button class="nav-item report-nav" type="button" data-target="qrcode"><i class="fas fa-qrcode"></i>QR Code</button></details>
  </nav>
  <div class="sidebar-bottom"><a class="nav-item logout" href="../model/sessions.php?sair"><i class="fas fa-right-from-bracket"></i>Sair</a></div>
</aside>
<button class="sidebar-scrim" type="button" aria-label="Fechar menu"></button>
<button class="mobile-menu" type="button" aria-label="Abrir menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
<main class="main-content"><section class="page-section" id="inicio" aria-label="Visão geral">

  

  <div class="flex-1 container mx-auto px-4 py-8 mt-16">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Sistema de Entradas e Saídas</span>
        </h1>
        <p class="text-gray-600">Gerencie as entradas e saídas dos alunos de forma eficiente</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <a href="#" data-section-target="entrada" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-blue-50 text-blue-600">
            <i class="fas fa-sign-in-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">Registrar Entrada</h3>
            <p class="text-sm text-gray-500">Registre a entrada dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="saida" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-red-50 text-red-600">
            <i class="fas fa-sign-out-alt text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-red-600 transition-colors">Registrar Saída</h3>
            <p class="text-sm text-gray-500">Registre a saída dos alunos</p>
          </div>
        </a>

        <a href="#" data-section-target="estagio" class="menu-card p-6 flex items-center gap-4 group">
          <div class="menu-icon bg-purple-50 text-purple-600">
            <i class="fas fa-briefcase text-xl"></i>
          </div>
          <div>
            <h3 class="font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">Registrar Saída-Estágio</h3>
            <p class="text-sm text-gray-500">Registre saídas para estágio</p>
          </div>
        </a>

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
      </div>
    </div>
  </div>

  

</section>

<section class="page-section active" id="entrada" aria-label="Registrar Entrada">

    
    

    <!-- Main Container -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Form Header -->
            <div class="bg-gradient-to-r from-ceara-green to-ceara-light-green text-white px-6 py-8 text-center">
                <h2 class="text-2xl font-bold mb-2">
                    <i class="fas fa-sign-in-alt mr-3"></i>
                    Registrar Entrada de Aluno
                </h2>
                <p class="text-lg opacity-90">
                    Preencha os dados para registrar a entrada do aluno
                </p>
            </div>

            <!-- Form Content -->
            <div class="p-6 lg:p-8">
                <form id="registro-e" action="../control/control_index.php" method="POST" class="space-y-6">
                    <input type="hidden" name="entrada" value="1">
                    <?php if ($mensagemRegistro !== '' && $sectionInicial === 'entrada') { ?>
                        <p class="rounded-lg border px-4 py-3 text-sm <?= $classeMensagemRegistro ?>" role="status"><?= htmlspecialchars($mensagemRegistro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } ?>

                    <!-- Seção: Dados do Aluno -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-graduate text-ceara-green mr-3"></i>
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
                                            ?>
                                                <div class="select-option" data-value="<?= $dado['id_aluno'] ?>" data-nome="<?= strtolower($dado['nome']) ?>">
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
                                        ?>
                                            <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
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
                                <label for="entrada-nome_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Nome do Responsável
                                </label>
                                <input type="text" id="entrada-nome_responsavel" name="nome_responsavel"
                                    placeholder="Digite o nome completo"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green" required>
                            </div>
                            <div class="form-group">
                                <label for="entrada-id_tipo_responsavel" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Responsável
                                </label>
                                <select id="entrada-id_tipo_responsavel" name="id_tipo_responsavel" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o tipo</option>
                                    <?php
                                    $dados = $select->select_responsavel();
                                    foreach ($dados as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_tipo_responsavel']?>"><?=$dado['tipo']?></option>
                                    <?php } ?>
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Dados do Conducente -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-car text-secondary mr-3"></i>
                            Dados do Acompanhante
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="entrada-nome_conducente" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Acompanhante
                                </label>
                                <input type="text" id="entrada-nome_conducente" name="nome_conducente"
                                    placeholder="Digite o nome do acompanhante"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green">
                            </div>
                            <div class="form-group">
                                <label for="entrada-id_tipo_conducente" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Tipo de Acompanhante
                                </label>
                                <select id="entrada-id_tipo_conducente" name="id_tipo_conducente" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
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

                    <!-- Seção: Dados da Entrada -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-danger mr-3"></i>
                            Dados da Entrada
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div class="form-group">
                                <label for="entrada-id_motivo" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Motivo da Entrada
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
                                <label for="entrada-id_usuario" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Administrador
                                </label>
                                <select id="entrada-id_usuario" name="id_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o administrador</option>
                                    <?php
                                    $dadosAdministradores = $select->select_administradores();
                                    foreach ($dadosAdministradores as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_usuario']?>"><?=$dado['nome']?></option>
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
                    <button type="submit" class="w-full bg-gradient-to-r from-ceara-green to-ceara-light-green text-white font-semibold py-4 px-6 rounded-lg hover:from-ceara-light-green hover:to-ceara-green transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-ceara-green focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Registrar Entrada
                    </button>
                </form>
            </div>
        </div>
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

        // Validação em tempo real
        const form = document.getElementById('registro-e');
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
})();
</script>

<section class="page-section" id="saida" aria-label="Registrar Saída">

    
    

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
                <form id="registro-s" action="../control/control_index.php" method="POST" class="space-y-6">
                    <input type="hidden" name="saida" value="1">
                    <?php if ($mensagemRegistro !== '' && $sectionInicial === 'saida') { ?>
                        <p class="rounded-lg border px-4 py-3 text-sm <?= $classeMensagemRegistro ?>" role="status"><?= htmlspecialchars($mensagemRegistro, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php } ?>
                    
                    <!-- Seção: Dados do Aluno -->
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-user-graduate text-ceara-green mr-3"></i>
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
                                            ?>
                                            <div class="select-option" data-value="<?=$dado['id_aluno']?>" data-nome="<?=strtolower($dado['nome'])?>">
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
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-car text-secondary mr-3"></i>
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
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-calendar-alt text-danger mr-3"></i>
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
                                <label for="saida-id_usuario" class="block text-sm font-medium text-gray-700 mb-2 required-field">
                                    Administrador
                                </label>
                                <select id="saida-id_usuario" name="id_usuario" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-ceara-green focus:border-ceara-green form-select" required>
                                    <option value="" disabled selected>Selecione o administrador</option>
                                    <?php
                                    $dadosAdministradores = $select->select_administradores();
                                    foreach ($dadosAdministradores as $dado) {
                                    ?>

                                        <option value="<?=$dado['id_usuario']?>"><?=$dado['nome']?></option>
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
                    <button type="submit" class="w-full bg-gradient-to-r from-ceara-green to-ceara-light-green text-white font-semibold py-4 px-6 rounded-lg hover:from-ceara-light-green hover:to-ceara-green transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-4 focus:ring-ceara-green focus:ring-opacity-50">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Registrar Saída
                    </button>
                </form>
            </div>
        </div>
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
})();
</script>

<section class="page-section" id="estagio" aria-label="Registrar Saída-Estágio">

    
    

    <!-- Main Content -->
    <div class="flex-1 container mx-auto px-4 py-8 mt-16">
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
            <div id="estagio-successMessage" class="hidden mb-6 p-4 bg-green-50 border border-green-200 rounded-lg success-message">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <span class="text-green-700 font-medium">Saída registrada com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-8 border-t-4 border-ceara-green">
                <form id="saida-estagio" action="../control/control_index.php" method="POST" class="space-y-6">
                    
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

                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        id="estagio-submitBtn"
                        name="Registrar"
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="estagio-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-save"></i>
                            Registrar Saída
                        </span>
        
                    </button>
                </form>

                <!-- Back Links -->
                <div class="flex flex-col sm:flex-row gap-3 mt-6 text-center">
                    <a href="#" data-section-target="inicio" class="flex-1 inline-flex items-center justify-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                    <a href="#" data-section-target="ultimas-saidas" class="flex-1 inline-flex items-center justify-center gap-2 text-blue-600 hover:text-blue-700 font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-eye text-sm"></i>
                        Ver em Tempo Real
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    

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
                <button type="button" data-close-modal="success" class="w-full bg-green-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-green-700 transition-colors">
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
                <button type="button" data-close-modal="error" class="w-full bg-red-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-red-700 transition-colors">
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
                <button type="button" data-close-modal="warning" class="w-full bg-yellow-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-yellow-700 transition-colors">
                    Entendi
                </button>
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

            handleSubmit(e) {
                if (!this.validateForm()) {
                    e.preventDefault();
                    
                    // Show error modal for validation failures
                    showErrorModal('Por favor, preencha todos os campos obrigatórios.');
                    
                    // Shake effect for invalid form
                    const container = this.form.closest('.page-section').querySelector('.form-card');
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

        const statusRegistro = urlParams.get('status');
        const mensagensErroRegistro = {
            campos_obrigatorios: 'Preencha os campos obrigatórios, incluindo o tipo de acompanhante, para concluir o registro.',
            data_hora_futura: 'Não é permitido registrar uma data ou horário futuro. Escolha um horário até o momento atual.',
            erro_interno: 'Não foi possível gravar o registro no banco de dados. Confira os dados e tente novamente.',
            aluno_nao_encontrado: 'O aluno selecionado não foi encontrado no banco de dados.',
            erro_desconhecido: 'O registro não foi concluído. Atualize a página e tente novamente.'
        };
        if (mensagensErroRegistro[statusRegistro]) {
            setTimeout(() => showErrorModal(mensagensErroRegistro[statusRegistro]), 100);
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

        document.querySelectorAll('[data-close-modal]').forEach(button => {
            button.addEventListener('click', () => {
                const modalType = button.dataset.closeModal;
                if (modalType === 'success') closeSuccessModal();
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
    <i class="fas fa-calendar-day text-xl"></i>
</div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Relatório de Saídas-Estágio no dia especifico</h3>
                            <p class="text-sm text-gray-500">Visualize informações gerais sobre todos os alunos por dia</p>
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

<section class="page-section" id="relatorio-saida" aria-label="Relatório de Saída">

    

    <div class="flex-1 container mx-auto px-4 py-8 mt-16">
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-2">
                    <span class="gradient-text">Relatório de Saída</span>
                </h1>
                <p class="text-gray-600">Selecione o tipo de relatório que deseja gerar</p>
            </div>

            <div class="space-y-8">
                <!-- Relatório por Aluno -->
                <div class="report-card p-6">
                    <div class="card-icon aluno">
                        <i class="fas fa-user-graduate text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Aluno</h2>
                    <form target="_blank" id="relatorio-saida-form" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="relatorio-saida-id_aluno" name="id_aluno" class="select-field" required>
                            <option value="" disabled selected>Selecione o Nome do Aluno</option>
                            <?php
                            $dados = $select->select_alunos();
                            foreach ($dados as $dado) {
                            ?>
                                <option value="<?= $dado['id_aluno'] ?>"><?= $dado['nome'] ?></option>
                            <?php
                            }
                            ?>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_alunoSaida">
                        <input type="hidden" name="form_id" value="entrada">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Ano -->
                <div class="report-card p-6">
                    <div class="card-icon ano">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Ano</h2>
                    <form target="_blank" id="saidaA" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="ano" name="Ano" class="select-field" required>
                            <option value="" disabled selected>Selecione o ano</option>
                            <option value="1">1° Anos</option>
                            <option value="2">2° Anos</option>
                            <option value="3">3° Anos</option>
                        </select>

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="ano_geralSaida">
                        <input type="hidden" name="form_id" value="entradaA">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Relatório por Turma -->
                <div class="report-card p-6">
                    <div class="card-icon turma">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Por Turma</h2>
                    <form target="_blank" id="saidaT" action="../control/control_index.php" method="POST" class="space-y-4">
                        <select id="Turma" name="Turma" class="select-field" required>
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

                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="dia_atual" required>
                                <span>Dia Atual</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_30_dias">
                                <span>Últimos 30 Dias</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="tipo_relatorio" value="ultimos_12_meses">
                                <span>Últimos 12 Meses</span>
                            </label>
                        </div>

                        <input type="hidden" name="GerarRelatorio" value="por_turmaSaida">
                        <input type="hidden" name="form_id" value="saidaT">
                        <div class="flex justify-center">
                            <button type="submit" name="btn" class="btn-submit">
                                <i class="fas fa-file-alt"></i>
                                Gerar Relatório
                            </button>
                        </div>
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
            const section = document.getElementById('relatorio-estagio');
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
                    section.querySelector('#relatorio-estagio-tab-' + targetTab).classList.add('active');
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
                        if (!select.value) isValid = false;
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos obrigatórios.');
                    }
                });
            });
        });
})();
</script>

<section class="page-section" id="relatorio-dia" aria-label="Relatório por Dia">

  

  <div class="flex-1 container mx-auto px-4 py-8 mt-16">
    <div class="max-w-4xl mx-auto">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold mb-2">
          <span class="gradient-text">Relatório por Dia</span>
        </h1>
        <p class="text-gray-600">Selecione a data para gerar o relatório de entradas e saídas</p>
      </div>

      <div class="form-card p-6 flex flex-col items-center gap-4">
        <form target="_blank" action="relatorios/alunos_geral_dia.php" method="post" class="flex flex-col sm:flex-row gap-4 items-center">
          <div class="flex items-center gap-2">
            <i class="fas fa-calendar-alt text-xl text-gray-600"></i>
            <input type="date" name="data" id="relatorio-dia-data" required>
          </div>
          <button type="submit" class="report-generate-button"><i class="fas fa-file-pdf" aria-hidden="true"></i>Gerar Relatório</button>
        </form>
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
                        class="w-full btn-primary text-white font-medium py-4 px-6 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                    >
                        <span id="qrcode-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-qrcode"></i>
                            Gerar QR Code
                        </span>
                        <span id="qrcode-submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Gerando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-6">
                    <a href="#" data-section-target="relatorios" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
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
                document.getElementById('qrcode').querySelector('.form-card').classList.add('pulse-effect');
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
            <?php if (!$validacaoAtrasosDisponivel) { ?>
                <p class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-900" role="status">A validação ainda não está habilitada. Aplique app/main/docs/migracao_validacao_atrasos.sql no banco.</p>
            <?php } ?>
        </header>
        <div class="delays-grid">
            <?php foreach ([9 => 'A', 10 => 'B', 11 => 'C', 12 => 'D'] as $idTurma => $turma) { ?>
                <?php $registrosTurma = $atrasosPorTurma[$idTurma]; ?>
                <article class="delay-card" aria-label="Atrasos do 3º Ano <?= $turma ?>">
                    <div class="delay-card-header turma-3<?= strtolower($turma) ?>">
                        <div class="delay-card-heading">
                            <h2><i class="fas fa-users" aria-hidden="true"></i> 3º Ano <?= $turma ?></h2>
                            <span class="delay-count"><?= count($registrosTurma) ?></span>
                        </div>
                        <input class="delay-search" type="search" data-delay-search="<?= strtolower($turma) ?>" placeholder="Buscar aluno..." aria-label="Buscar aluno do 3º Ano <?= $turma ?>">
                    </div>
                    <div class="delay-list" id="atrasos-turma-<?= strtolower($turma) ?>">
                        <?php if ($registrosTurma) { ?>
                            <?php foreach ($registrosTurma as $registroAtraso) { ?>
                                <?php $jsonDetalhes = json_encode($detalhesAtraso($registroAtraso), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>
                                <div class="delay-entry" data-delay-entry data-name="<?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="delay-entry-name">
                                        <?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (!empty($registroAtraso['is_test'])) { ?><span class="ml-2 rounded bg-amber-100 px-2 py-1 text-[10px] font-bold text-amber-800">TESTE</span><?php } ?>
                                    </div>
                                    <div class="delay-entry-meta">
                                        <time><?= date('H:i', strtotime($registroAtraso['date_time'])) ?></time>
                                        <?php $statusAtraso = $registroAtraso['status_justificativa'] ?? 'pendente'; $acaoAtraso = !empty($registroAtraso['is_test']) ? 'Testar' : ($statusAtraso === 'pendente' && $validacaoAtrasosDisponivel ? 'Validar' : 'Detalhes'); ?>
                                        <button type="button" class="delay-detail-button <?= $acaoAtraso === 'Detalhes' ? 'delay-detail-button-secondary' : '' ?>" data-delay-details="<?= htmlspecialchars($jsonDetalhes, ENT_QUOTES, 'UTF-8') ?>" data-record-id="<?= (int) ($registroAtraso['id_registro_entrada'] ?? 0) ?>" data-status="<?= htmlspecialchars($statusAtraso, ENT_QUOTES, 'UTF-8') ?>" data-can-validate="<?= empty($registroAtraso['is_test']) && $validacaoAtrasosDisponivel ? '1' : '0' ?>"><?= $acaoAtraso === 'Validar' ? '<i class="fas fa-check" aria-hidden="true"></i>' : ($acaoAtraso === 'Testar' ? '<i class="fas fa-flask" aria-hidden="true"></i>' : '<i class="fas fa-eye" aria-hidden="true"></i>') ?><?= htmlspecialchars($acaoAtraso, ENT_QUOTES, 'UTF-8') ?></button>
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
            <p class="delay-history-note">Consulte os registros anteriores por turma, aluno, período e situação.</p>
            <div class="delay-history-filters" aria-label="Filtros do histórico">
                <label class="delay-history-filter">Aluno<input type="search" id="history-filter-name" placeholder="Buscar aluno"></label>
                <label class="delay-history-filter">Turma<select id="history-filter-class"><option value="todas">Todas as turmas</option><option value="9">3º Ano A</option><option value="10">3º Ano B</option><option value="11">3º Ano C</option><option value="12">3º Ano D</option></select></label>
                <label class="delay-history-filter">Situação<select id="history-filter-status"><option value="todas">Todas</option><option value="pendente">Pendente</option><option value="aprovada">Aprovada</option><option value="recusada">Recusada</option></select></label>
                <div class="delay-history-date-range" aria-label="Período">
                    <label class="delay-history-filter">De<input type="date" id="history-filter-from"></label>
                    <label class="delay-history-filter">Até<input type="date" id="history-filter-to"></label>
                </div>
                <button type="button" class="delay-history-reset" id="history-filter-reset"><i class="fas fa-rotate-left" aria-hidden="true"></i> Limpar</button>
            </div>
            <div class="delay-history-grid" id="delay-history-grid">
                <?php foreach ([9 => 'A', 10 => 'B', 11 => 'C', 12 => 'D'] as $idTurma => $turma) { ?>
                    <article class="delay-history-card turma-3<?= strtolower($turma) ?>" data-history-card="<?= $idTurma ?>">
                        <header class="delay-history-card-header"><h3><i class="fas fa-users" aria-hidden="true"></i> 3º Ano <?= $turma ?></h3><span class="delay-history-count" data-history-count><?= count($historicoPorTurma[$idTurma]) ?></span></header>
                        <div class="delay-history-list">
                            <?php if ($historicoPorTurma[$idTurma]) { ?>
                                <?php foreach ($historicoPorTurma[$idTurma] as $registroAtraso) { ?>
                                    <?php $jsonDetalhes = json_encode($detalhesAtraso($registroAtraso), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); $statusHistorico = $registroAtraso['status_justificativa'] ?? 'pendente'; ?>
                                    <article class="delay-history-entry" data-history-entry data-history-date="<?= date('Y-m-d', strtotime($registroAtraso['date_time'])) ?>" data-history-status="<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>" data-history-name="<?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?>">
                                        <div class="delay-history-entry-name"><?= htmlspecialchars($registroAtraso['nome'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="delay-history-entry-meta"><time datetime="<?= date('Y-m-d\TH:i:s', strtotime($registroAtraso['date_time'])) ?>"><?= date('d/m/Y', strtotime($registroAtraso['date_time'])) ?> às <?= date('H:i', strtotime($registroAtraso['date_time'])) ?></time><span class="delay-status delay-status-<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(['pendente' => 'Pendente', 'aprovada' => 'Aprovada', 'recusada' => 'Recusada'][$statusHistorico] ?? 'Pendente', ENT_QUOTES, 'UTF-8') ?></span></div>
                                        <button type="button" class="delay-detail-button delay-history-entry-action" data-delay-details="<?= htmlspecialchars($jsonDetalhes, ENT_QUOTES, 'UTF-8') ?>" data-record-id="<?= (int) $registroAtraso['id_registro_entrada'] ?>" data-status="<?= htmlspecialchars($statusHistorico, ENT_QUOTES, 'UTF-8') ?>" data-can-validate="0"><i class="fas fa-eye" aria-hidden="true"></i> Ver detalhes</button>
                                    </article>
                                <?php } ?>
                            <?php } else { ?>
                                <p class="delay-history-empty">Nenhum atraso registrado nesta turma.</p>
                            <?php } ?>
                        </div>
                    </article>
                <?php } ?>
            </div>
            <p class="delay-history-no-results" id="history-no-results" hidden>Nenhum registro encontrado com esses filtros.</p>
        </section>
    </div>
    <dialog class="delay-dialog" id="delay-details-dialog" aria-labelledby="delay-dialog-title">
        <div class="delay-dialog-header">
            <div class="delay-dialog-heading"><div><h2 id="delay-dialog-title">Validar justificativa</h2><p id="delay-dialog-subtitle">Registre sua avaliação da justificativa informada pelo aluno.</p></div></div>
            <button type="button" class="delay-dialog-close" data-delay-dialog-close aria-label="Fechar modal"><i class="fas fa-xmark" aria-hidden="true"></i></button>
        </div>
        <div class="delay-dialog-content" id="delay-dialog-content"></div>
        <form class="delay-validation-form border-t border-gray-200 px-5 py-4" id="delay-validation-form" action="../control/control_index.php" method="post" hidden>
            <input type="hidden" name="acao" value="validar_justificativa_atraso">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_registro_entrada" id="delay-record-id">
            <div class="delay-form-field"><span class="delay-form-label">Avaliação</span><input type="hidden" id="delay-decision" name="decisao"><div class="delay-decision-options" role="group" aria-label="Avaliação da justificativa"><button class="delay-decision-option delay-decision-option-approve" type="button" data-delay-decision="aprovada" aria-pressed="false"><i class="fas fa-check" aria-hidden="true"></i>Aprovar</button><button class="delay-decision-option delay-decision-option-reject" type="button" data-delay-decision="recusada" aria-pressed="false"><i class="fas fa-xmark" aria-hidden="true"></i>Recusar</button></div></div>
            <div class="delay-form-field"><label class="delay-form-label" for="delay-validator">Responsável</label><select class="delay-form-control" id="delay-validator" name="responsavel_validacao" required><option value="">Selecione Rosana ou Adriana</option><option value="Rosana">Rosana</option><option value="Adriana">Adriana</option></select></div>
            <div class="delay-form-field" id="delay-validation-note-field" hidden><label class="delay-form-label" for="delay-validation-note">Observação da recusa</label><textarea class="delay-form-control" id="delay-validation-note" name="observacao_validacao" rows="3" maxlength="500" placeholder="Informe o motivo da recusa"></textarea></div>
            <button class="delay-validation-submit" id="delay-validation-submit" type="submit" disabled><i class="fas fa-check" aria-hidden="true"></i><span id="delay-validation-submit-label">Escolha uma avaliação</span></button>
            <p class="delay-demo-note" id="delay-demo-feedback" role="status" hidden>Teste concluído. Nenhum dado foi salvo.</p>
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

            const historyCards = document.querySelectorAll('[data-history-card]');
            const historyEntries = document.querySelectorAll('[data-history-entry]');
            const historyFilters = {
                name: document.getElementById('history-filter-name'),
                turma: document.getElementById('history-filter-class'),
                status: document.getElementById('history-filter-status'),
                from: document.getElementById('history-filter-from'),
                to: document.getElementById('history-filter-to')
            };
            const filterHistory = () => {
                const name = historyFilters.name.value.trim().toLocaleLowerCase('pt-BR');
                let visibleEntries = 0;
                historyEntries.forEach((entry) => {
                    const matches = entry.dataset.historyName.toLocaleLowerCase('pt-BR').includes(name)
                        && (historyFilters.status.value === 'todas' || entry.dataset.historyStatus === historyFilters.status.value)
                        && (!historyFilters.from.value || entry.dataset.historyDate >= historyFilters.from.value)
                        && (!historyFilters.to.value || entry.dataset.historyDate <= historyFilters.to.value);
                    entry.hidden = !matches;
                    if (matches) visibleEntries += 1;
                });
                historyCards.forEach((card) => {
                    const matchingTurma = historyFilters.turma.value === 'todas' || card.dataset.historyCard === historyFilters.turma.value;
                    const entries = card.querySelectorAll('[data-history-entry]');
                    const count = Array.from(entries).filter((entry) => !entry.hidden).length;
                    card.hidden = !matchingTurma || count === 0;
                    card.querySelector('[data-history-count]').textContent = count;
                });
                document.getElementById('history-no-results').hidden = visibleEntries > 0;
            };
            Object.values(historyFilters).forEach((filter) => filter.addEventListener('input', filterHistory));
            document.getElementById('history-filter-reset').addEventListener('click', () => {
                Object.values(historyFilters).forEach((filter) => { filter.value = filter.tagName === 'SELECT' ? 'todas' : ''; });
                filterHistory();
            });

            const dialog = document.getElementById('delay-details-dialog');
            const dialogContent = document.getElementById('delay-dialog-content');
            const validationForm = document.getElementById('delay-validation-form');
            const validationNote = document.getElementById('delay-validation-note');
            const validationNoteField = document.getElementById('delay-validation-note-field');
            const validatorInput = document.getElementById('delay-validator');
            const validationSubmitLabel = document.getElementById('delay-validation-submit-label');
            const demoFeedback = document.getElementById('delay-demo-feedback');
            const recordId = document.getElementById('delay-record-id');
            const decisionInput = document.getElementById('delay-decision');
            const decisionButtons = validationForm.querySelectorAll('[data-delay-decision]');
            const validationSubmit = document.getElementById('delay-validation-submit');
            const dialogTitle = document.getElementById('delay-dialog-title');
            const dialogSubtitle = document.getElementById('delay-dialog-subtitle');
            const updateDecision = (decision) => {
                decisionInput.value = decision;
                decisionButtons.forEach((button) => {
                    button.setAttribute('aria-pressed', String(button.dataset.delayDecision === decision));
                });
                validationNote.required = decision === 'recusada';
                validationNoteField.hidden = !validationNote.required;
                validationSubmit.disabled = !decision || !validatorInput.value;
                validationSubmit.classList.toggle('is-rejection', decision === 'recusada');
                const label = decision === 'aprovada' ? 'Aprovar justificativa' : decision === 'recusada' ? 'Confirmar recusa' : 'Escolha uma avaliação';
                validationSubmitLabel.textContent = validationForm.dataset.demo === '1' && decision ? `Testar ${decision === 'aprovada' ? 'aprovação' : 'recusa'}` : label;
                const icon = validationSubmit.querySelector('i');
                icon.classList.toggle('fa-check', decision !== 'recusada');
                icon.classList.toggle('fa-xmark', decision === 'recusada');
            };
            decisionButtons.forEach((button) => {
                button.addEventListener('click', () => updateDecision(button.dataset.delayDecision));
            });
            validatorInput.addEventListener('change', () => {
                validationSubmit.disabled = !decisionInput.value || !validatorInput.value;
            });
            document.querySelectorAll('[data-delay-details]').forEach((button) => {
                button.addEventListener('click', () => {
                    const details = JSON.parse(button.dataset.delayDetails);
                    dialogContent.replaceChildren();
                    const canValidate = button.dataset.canValidate === '1' && button.dataset.status === 'pendente';
                    const isDemo = Boolean(details.Aviso);
                    const showValidationForm = canValidate || isDemo;
                    dialogTitle.textContent = showValidationForm ? 'Validar justificativa' : 'Detalhes do atraso';
                    dialogSubtitle.textContent = isDemo ? 'Registre o que o aluno informou e avalie a justificativa.' : canValidate ? 'Registre sua avaliação da justificativa informada pelo aluno.' : 'Consulte os dados e o resultado da validação.';
                    const summary = document.createElement('section');
                    summary.className = 'delay-validation-summary';
                    const summaryLabel = document.createElement('p');
                    summaryLabel.className = 'delay-validation-summary-label';
                    summaryLabel.textContent = details.Aviso ? 'Demonstração' : 'Registro de atraso';
                    const summaryName = document.createElement('p');
                    summaryName.className = 'delay-validation-summary-name';
                    summaryName.textContent = details.Aluno || 'Aluno';
                    const summaryMeta = document.createElement('p');
                    summaryMeta.className = 'delay-validation-summary-meta';
                    summaryMeta.textContent = `${details.Turma || ''} · ${details['Data e hora'] || ''}`;
                    summary.append(summaryLabel, summaryName, summaryMeta);
                    dialogContent.appendChild(summary);
                    if (details.Aviso) {
                        const demoNote = document.createElement('p');
                        demoNote.className = 'delay-demo-note';
                        demoNote.textContent = details.Aviso;
                        dialogContent.appendChild(demoNote);
                    }

                    const justificationLabel = document.createElement('label');
                    justificationLabel.className = 'delay-justification-label';
                    justificationLabel.textContent = 'Justificativa informada pelo aluno';
                    const justification = document.createElement('textarea');
                    justification.className = 'delay-justification';
                    justification.readOnly = true;
                    justification.value = details.Motivo || 'Nenhuma justificativa informada.';
                    dialogContent.append(justificationLabel, justification);

                    const extraDetails = document.createElement('details');
                    extraDetails.className = 'delay-extra-details';
                    const extraSummary = document.createElement('summary');
                    extraSummary.textContent = 'Dados completos do registro';
                    extraDetails.appendChild(extraSummary);
                    Object.entries(details).filter(([label]) => !['Aluno', 'Turma', 'Data e hora', 'Motivo'].includes(label)).forEach(([label, value]) => {
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
                    validationForm.dataset.demo = isDemo ? '1' : '0';
                    demoFeedback.hidden = true;
                    validationNote.value = '';
                    validatorInput.value = '';
                    updateDecision('');
                    recordId.value = button.dataset.recordId || '';
                    dialog.showModal();
                });
            });
            validationForm.addEventListener('submit', (event) => {
                if (!decisionInput.value || !validatorInput.value) {
                    event.preventDefault();
                    return;
                }
                if (validationForm.dataset.demo === '1') {
                    event.preventDefault();
                    demoFeedback.hidden = false;
                }
            });
            dialog.querySelector('[data-delay-dialog-close]').addEventListener('click', () => dialog.close());
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        })();
    </script>
</section>

<section class="page-section" id="ultimas-saidas" aria-label="Últimas Saídas">

    <!-- QR Code Reader -->
    <div id="reader" style="position: fixed; top: 20px; right: 20px; z-index: 1000;"></div>
    <input type="text" id="urlInput" placeholder="URL será inserida aqui automaticamente" style="position: fixed; top: -100px; opacity: 0;" />

    <div class="main-container max-w-7xl mx-auto p-6 lg:p-8 ">
        
        <div class="text-center mb-8">
            <!-- Botão Voltar -->
            <div class="flex justify-start mb-4">
                <button type="button" data-section-target="inicio" class="inline-flex items-center text-gray-600 bg-white rounded-lg px-4 py-2 shadow-sm border hover:bg-gray-50 transition-colors">
                    <i class="fas fa-arrow-left mr-2 text-ceara-green"></i>
                    <span class="text-sm">Voltar</span>
                </button>
            </div>
            
            <div class="flex flex-col lg:flex-row items-center justify-center gap-4">
                <h1 class="text-2xl lg:text-3xl font-semibold">
                    <span class="gradient-text">Frequencia em tempo real </span>
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
                No caso de problemas com o registro da frequencia, procure a recepção.
            </div>
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3a ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3a" placeholder="Buscar aluno..." onkeyup="filterTable('3a')">
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
                                    <tr class="table-row">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3b ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3b" placeholder="Buscar aluno..." onkeyup="filterTable('3b')">
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
                                    <tr class="table-row">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3c ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3c" placeholder="Buscar aluno..." onkeyup="filterTable('3c')">
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
                                    <tr class="table-row">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium">
                                <?= $count_3d ?> alunos
                            </span>
                        </div>
                        <input type="text" class="search-input search-3d" placeholder="Buscar aluno..." onkeyup="filterTable('3d')">
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
                                    <tr class="table-row">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3a ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3a" placeholder="Buscar aluno..." onkeyup="filterCards('3a')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3a">
                        <?php if ($count_3a > 0) { ?>
                            <?php foreach ($dados_3a as $index => $dado) { ?>
                                <div class="student-card compact-card">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3b ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3b" placeholder="Buscar aluno..." onkeyup="filterCards('3b')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3b">
                        <?php if ($count_3b > 0) { ?>
                            <?php foreach ($dados_3b as $index => $dado) { ?>
                                <div class="student-card compact-card">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3c ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3c" placeholder="Buscar aluno..." onkeyup="filterCards('3c')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3c">
                        <?php if ($count_3c > 0) { ?>
                            <?php foreach ($dados_3c as $index => $dado) { ?>
                                <div class="student-card compact-card">
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
                            <span class="bg-white bg-opacity-20 px-3 py-1 rounded-full text-sm font-medium text-white">
                                <?= $count_3d ?>
                            </span>
                        </div>
                        <input type="text" class="search-input search-mobile-3d" placeholder="Buscar aluno..." onkeyup="filterCards('3d')">
                    </div>
                    <div class="p-4 compact-cards custom-scrollbar" id="cards-3d">
                        <?php if ($count_3d > 0) { ?>
                            <?php foreach ($dados_3d as $index => $dado) { ?>
                                <div class="student-card compact-card">
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
// Função para filtrar tabelas
        function filterTable(turma) {
            const input = document.querySelector(`.search-${turma}`);
            const filter = input.value.toUpperCase();
            const table = document.getElementById(`table-${turma}`);
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) { // Começar do 1 para pular o cabeçalho
                const nameCell = rows[i].getElementsByTagName('td')[0];
                if (nameCell) {
                    const nameText = nameCell.textContent || nameCell.innerText;
                    if (nameText.toUpperCase().indexOf(filter) > -1) {
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
            const filter = input.value.toUpperCase();
            const container = document.getElementById(`cards-${turma}`);
            const cards = container.getElementsByClassName('student-card');

            for (let i = 0; i < cards.length; i++) {
                const nameText = cards[i].querySelector('span').textContent || cards[i].querySelector('span').innerText;
                if (nameText.toUpperCase().indexOf(filter) > -1) {
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

                if (totalPages <= 1) return;

                const paginationContainer = document.getElementById(`pagination-${turma}`);
                if (!paginationContainer) return;

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
        let leituraAtiva = false;
        let leitorIniciando = false;
        let ultimaUrlAberta = null;
        let html5QrCode;

        // Função para manter o input sempre focado
        function manterFoco() {
            if (!leituraAtiva) return;
            input.focus();
            setTimeout(manterFoco, 100);
        }

        function abrirEmNovaAba(url) {
            if (!url || url === ultimaUrlAberta) return;
            if (!url.startsWith('http://') && !url.startsWith('https://')) {
                url = 'https://' + url;
            }
            ultimaUrlAberta = url;
            window.open(url, '_blank', 'noopener');
        }

        function onQRCodeScanned(decodedText) {
            input.value = decodedText;
            abrirEmNovaAba(decodedText);
        }

        // Inicia o leitor QR automaticamente quando a página carrega
        document.addEventListener('section:activate:ultimas-saidas', () => {
            if (leituraAtiva || leitorIniciando) return;

            // Limpa o histórico de URLs abertas
            ultimaUrlAberta = null;

            // Inicia o leitor QR apenas em dispositivos não-mobile (>= 768px)
            if (window.innerWidth >= 768) { 
                readerDiv.style.display = 'block';
                html5QrCode = new Html5Qrcode("reader");
                leitorIniciando = true;

                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: 250 },
                    onQRCodeScanned
                ).then(() => {
                    leitorIniciando = false;
                    if (!document.getElementById('ultimas-saidas').classList.contains('active')) {
                        const scanner = html5QrCode;
                        html5QrCode = null;
                        scanner.stop().then(() => scanner.clear()).catch(() => {});
                        return;
                    }
                    leituraAtiva = true;
                    input.focus();
                    manterFoco();
                }).catch(() => {
                    leitorIniciando = false;
                    leituraAtiva = false;
                    html5QrCode = null;
                    readerDiv.style.display = 'none';
                });
            } else {
                readerDiv.style.display = 'none'; // Hide the reader div on mobile
                leituraAtiva = false;
            }
        });

        document.addEventListener('section:deactivate:ultimas-saidas', () => {
            leituraAtiva = false;
            readerDiv.style.display = 'none';
            if (html5QrCode && !leitorIniciando) {
                const scanner = html5QrCode;
                html5QrCode = null;
                scanner.stop().then(() => scanner.clear()).catch(() => {});
            }
        });

        // Adiciona evento para abrir URL quando o usuário digita
        let timeoutId;
        input.addEventListener('input', (e) => {
            const url = e.target.value.trim();
            if (url) {
                if (timeoutId) clearTimeout(timeoutId);
                timeoutId = setTimeout(() => {
                    abrirEmNovaAba(url);
                }, 100);
            }
        });

        // Previne que o usuário perca o foco
        readerDiv.addEventListener('click', () => input.focus());

        // Força recarregamento da página se vier do cache
        

        // Limpa o cache quando a página carrega
        
})();
</script>

<section class="page-section" id="cadastro" aria-label="Cadastrar Aluno">

    
    

    <!-- Main Content -->
    <div class="flex-1 container mx-auto px-4 py-8">
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
            <div id="cadastro-successMessage" class="hidden mb-4 p-3 bg-green-50 border border-green-200 rounded-lg slide-in">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2 text-sm"></i>
                    <span class="text-green-700 font-medium text-sm">Aluno cadastrado com sucesso!</span>
                </div>
            </div>

            <!-- Form Container -->
            <div class="form-card p-6 border-t-4 border-ceara-green">
                <form target="_blank" id="cadastroForm" action="../control/control_index.php" method="post" class="space-y-5">
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
                        <div id="cadastro-turma-error" class="error-message hidden mt-1 text-red-500 text-xs flex items-center">
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
                        id="cadastro-submitBtn"
                        class="w-full btn-primary text-white font-medium py-3 px-4 rounded-lg focus:outline-none focus:ring-4 focus:ring-green-200 disabled:opacity-50 disabled:cursor-not-allowed text-sm mt-6"
                    >
                        <span id="cadastro-submitText" class="flex items-center justify-center gap-2">
                            <i class="fas fa-user-plus text-sm"></i>
                            Cadastrar Aluno
                        </span>
                        <span id="cadastro-submitLoading" class="hidden flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Cadastrando...
                        </span>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="text-center mt-4">
                    <a href="#" data-section-target="inicio" class="inline-flex items-center gap-2 text-ceara-green hover:text-ceara-light-green font-medium transition-colors duration-300 text-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                        Voltar ao Menu
                    </a>
                </div>
            </div>
        </div>
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
                    const container = this.form.closest('.page-section').querySelector('.form-card');
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
                const submitText = document.getElementById('cadastro-submitText');
                const submitLoading = document.getElementById('cadastro-submitLoading');
                
                submitText.classList.add('hidden');
                submitLoading.classList.remove('hidden');
                this.submitBtn.disabled = true;
            }

            showSuccessState() {
                const successMessage = document.getElementById('cadastro-successMessage');
                const container = this.form.closest('.page-section').querySelector('.form-card');
                
                // Reset button state
                const submitText = document.getElementById('cadastro-submitText');
                const submitLoading = document.getElementById('cadastro-submitLoading');
                submitText.classList.remove('hidden');
                submitLoading.classList.add('hidden');
                this.submitBtn.disabled = false;

                // Show success message
                successMessage.classList.remove('hidden');
                container.classList.add('pulse-success');
                
                // Reset form
                this.form.reset();
                this.form.querySelectorAll('.input-group').forEach(group => {
                    group.classList.remove('has-value');
                });
                this.form.querySelectorAll('.border-green-500').forEach(field => {
                    field.classList.remove('border-green-500');
                });
                this.form.closest('.page-section').querySelectorAll('.success-message').forEach(msg => {
                    msg.classList.add('hidden');
                });

                // Hide success message after 5 seconds
                setTimeout(() => {
                    successMessage.classList.add('hidden');
                    container.classList.remove('pulse-success');
                }, 5000);
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
    const closeMobileMenu = () => {
        document.body.classList.remove('sidebar-open');
        menuToggle?.setAttribute('aria-expanded', 'false');
    };
  const activate = (target, selectedItem) => {
    navItems.forEach(nav => nav.classList.remove('active'));
    sections.forEach(section => section.classList.remove('active'));
    selectedItem?.classList.add('active');
        const selectedGroup = selectedItem?.closest('.nav-group[data-sidebar-group]');
        if (selectedGroup && !Object.prototype.hasOwnProperty.call(savedSidebarGroups, selectedGroup.dataset.sidebarGroup)) {
            selectedGroup.open = true;
        }
    const selectedSection = document.getElementById(target);
    if (selectedSection) selectedSection.classList.add('active');
    document.dispatchEvent(new CustomEvent('section:deactivate:ultimas-saidas'));
    if (target === 'ultimas-saidas') document.dispatchEvent(new CustomEvent('section:activate:ultimas-saidas'));
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
    document.querySelector('.sidebar-close')?.addEventListener('click', closeMobileMenu);
    document.querySelector('.sidebar-scrim')?.addEventListener('click', closeMobileMenu);
    const initialSection = <?php echo json_encode($sectionInicial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    activate(initialSection, document.querySelector(`.nav-item[data-target="${initialSection}"]`));
})();
</script>
</main>
</body>
</html>
