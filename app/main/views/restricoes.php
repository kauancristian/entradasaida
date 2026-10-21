<?php
$restrictionList = isset($restrictionList) && is_array($restrictionList) ? $restrictionList : [];
$restrictionClasses = isset($restrictionClasses) && is_array($restrictionClasses) ? $restrictionClasses : [];
$restrictionAssignments = isset($restrictionAssignments) && is_array($restrictionAssignments) ? $restrictionAssignments : [];
$restrictionModelError = isset($restrictionModelError) && $restrictionModelError;
$restrictionMessages = [
    'success' => 'Restrição salva com sucesso.',
    'assigned' => 'Restrições da turma atualizadas.',
    'deleted' => 'Restrição excluída.',
    'invalid' => 'Confira os dados da restrição e tente novamente.',
    'error' => 'Não foi possível salvar as alterações. Confira a instalação da migration.'
];
$restrictionStatus = is_string($_GET['restriction_status'] ?? null) ? $_GET['restriction_status'] : '';
?>
<style>
#restricoes{max-width:1160px;width:100%;margin:0 auto}.restriction-page{--restriction-green:#08723a;--restriction-ink:#18271f;--restriction-muted:#64736a;color:var(--restriction-ink)}.restriction-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin:6px 0 24px}.restriction-heading h1{margin:0;color:var(--restriction-green);font-size:28px;font-weight:700}.restriction-heading p{margin:7px 0 0;color:var(--restriction-muted);font-size:14px}.restriction-tabs{display:flex;gap:6px;margin-bottom:20px;border-bottom:1px solid #dfe8e1}.restriction-tab{min-height:42px;padding:0 16px;border:0;border-bottom:3px solid transparent;background:transparent;color:#56645b;font:600 13px 'Inter',sans-serif;cursor:pointer}.restriction-tab[aria-selected=true]{border-bottom-color:#008c45;color:#08723a}.restriction-panel[hidden]{display:none}.restriction-layout{display:grid;grid-template-columns:minmax(280px,.8fr) minmax(0,1.2fr);gap:20px;align-items:start}.restriction-form,.restriction-card,.restriction-class-card{border:1px solid #dfe8e1;border-radius:8px;background:#fff}.restriction-form{padding:20px}.restriction-form h2,.restriction-list-title{margin:0 0 15px;color:#203229;font-size:17px;font-weight:700}.restriction-field{display:grid;gap:6px;margin-bottom:14px}.restriction-field label{color:#3f5146;font-size:12px;font-weight:600}.restriction-field input,.restriction-field select{width:100%;min-height:40px;padding:8px 10px;border:1px solid #cfd9d2;border-radius:6px;background:#fff;color:#24352b;font:400 13px 'Inter',sans-serif}.restriction-field input:focus,.restriction-field select:focus{outline:2px solid #008c4540;border-color:#008c45}.restriction-time-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}.restriction-form-actions{display:flex;gap:9px;margin-top:18px}.restriction-primary,.restriction-secondary,.restriction-danger,.restriction-edit{min-height:38px;padding:0 13px;border:1px solid transparent;border-radius:6px;font:600 12px 'Inter',sans-serif;cursor:pointer}.restriction-primary{background:#08723a;color:white}.restriction-secondary{border-color:#d6e0d9;background:white;color:#3f5146}.restriction-danger{border-color:#efcfcc;background:#fff7f6;color:#a32620}.restriction-edit{border-color:#cfe4d5;background:#f2f8f3;color:#08723a}.restriction-feedback{margin:0 0 18px;padding:12px 14px;border:1px solid #c7e4ce;border-radius:6px;background:#f1faf3;color:#146c36;font-size:13px}.restriction-feedback.error{border-color:#f1ccca;background:#fff6f5;color:#a32620}.restriction-list{display:grid;gap:10px}.restriction-card{padding:15px}.restriction-card-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.restriction-card h3,.restriction-class-card h3{margin:0;color:#203229;font-size:15px;font-weight:700}.restriction-card p{margin:7px 0 0;color:#64736a;font-size:12px;line-height:1.5}.restriction-card-actions{display:flex;gap:6px;flex-shrink:0}.restriction-empty{padding:24px 15px;border:1px dashed #cfd9d2;border-radius:7px;color:#64736a;text-align:center;font-size:13px}.restriction-class-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.restriction-class-card{padding:17px}.restriction-class-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:8px}.restriction-assigned-count{display:grid;min-width:27px;height:27px;place-items:center;border-radius:50%;background:#eaf4ec;color:#08723a;font-size:12px;font-weight:700}.restriction-class-card p{margin:0 0 13px;color:#64736a;font-size:12px}.restriction-assignment{display:grid;gap:9px;margin:12px 0 15px}.restriction-check{display:flex;align-items:flex-start;gap:9px;color:#35463b;font-size:12px;line-height:1.4}.restriction-check input{margin:1px 0 0;accent-color:#08723a}.restriction-class-actions{display:flex;align-items:center;justify-content:space-between;gap:10px}.restriction-class-actions .restriction-primary{white-space:nowrap}.restriction-tags{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}.restriction-tag{padding:4px 7px;border-radius:4px;background:#f0f6f1;color:#376148;font-size:10px;font-weight:600}.restriction-disabled-note{padding:14px;border:1px solid #f1d9a8;border-radius:6px;background:#fff9eb;color:#77510c;font-size:13px;line-height:1.5}
@media(max-width:900px){.restriction-layout{grid-template-columns:1fr}.restriction-class-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.restriction-heading{flex-direction:column}.restriction-heading h1{font-size:24px}.restriction-tab{padding:0 10px}.restriction-class-grid{grid-template-columns:1fr}.restriction-card-head{flex-direction:column}.restriction-card-actions{width:100%}.restriction-time-grid{gap:8px}}
.restriction-heading{align-items:center;justify-content:flex-start;margin:2px 0 18px}.restriction-heading h1{font-size:25px;line-height:1.2}.restriction-heading p{margin-top:5px;font-size:13px}.restriction-tabs{gap:8px;margin-bottom:18px}.restriction-tab{display:inline-flex;align-items:center;gap:8px;min-height:44px;padding:0 16px}.restriction-tab i{font-size:12px}.restriction-layout{grid-template-columns:minmax(0,1fr) minmax(0,1.04fr);gap:16px}.restriction-form,.restriction-list-panel{min-width:0;padding:22px;border:1px solid #e2eaf0;border-radius:10px;background:rgba(255,255,255,.92);box-shadow:0 5px 18px rgba(22,55,76,.07)}.restriction-form-heading,.restriction-list-heading{display:flex;align-items:center;gap:12px;margin-bottom:19px}.restriction-heading-icon,.restriction-list-icon{display:grid;width:34px;height:34px;flex:0 0 34px;place-items:center;border-radius:9px;background:#eaf5ee;color:#008c45;font-size:17px}.restriction-list-icon{background:#f0f5f8;color:#34576c;font-size:15px}.restriction-form h2,.restriction-list-title{margin:0;color:#17343e;font-size:17px;font-weight:700}.restriction-form-intro{margin:4px 0 0;color:#597084;font-size:12px}.restriction-field{gap:7px;margin-bottom:13px}.restriction-field label{color:#253f50;font-size:12px}.restriction-field input,.restriction-field select{min-height:43px;padding:9px 11px;border-color:#d6e0e8;border-radius:7px;color:#243c4e}.restriction-field input::placeholder{color:#8295a4}.restriction-time-grid{gap:14px}.restriction-form-actions{margin-top:17px}.restriction-primary{min-height:40px;border-radius:7px;background:#008c45;color:#fff}.restriction-primary:hover{background:#06743d}.restriction-form-actions .restriction-primary{width:100%;display:flex;align-items:center;justify-content:center;gap:9px;min-height:44px;font-size:13px}.restriction-list-heading{margin-bottom:17px}.restriction-list-heading .restriction-list-title{flex:1}.restriction-count{padding:6px 11px;border-radius:20px;background:#e6f6ec;color:#08723a;font-size:11px;font-weight:700;white-space:nowrap}.restriction-list{gap:9px}.restriction-empty{display:flex;min-height:330px;flex-direction:column;align-items:center;justify-content:center;gap:10px;padding:24px;border:1px solid #e5edf2;border-radius:9px;background:linear-gradient(145deg,#fff,#fbfdfe);color:#547084}.restriction-empty i{display:grid;width:62px;height:62px;place-items:center;border-radius:50%;background:#eff5f9;color:#7993a8;font-size:27px}.restriction-empty strong{color:#243e4f;font-size:14px}.restriction-empty span{font-size:12px;text-align:center}.restriction-card{border-color:#e2eaf0;border-radius:8px;box-shadow:0 2px 7px rgba(22,55,76,.04)}.restriction-class-card{border-color:#e2eaf0;border-radius:9px;box-shadow:0 3px 12px rgba(22,55,76,.05)}
#restricoes .restriction-panel{height:clamp(360px,calc(100dvh - 360px),650px);overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin;scrollbar-color:#b9cfc0 transparent}
.restriction-class-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:16px;align-items:stretch}.restriction-class-card{display:flex;min-height:230px;flex-direction:column;padding:20px;border:1px solid #f0d99d;border-radius:16px;background:#fff;box-shadow:0 8px 20px rgba(24,61,43,.07)}.restriction-class-head{display:flex;align-items:center;gap:14px}.restriction-class-icon{display:grid;width:52px;height:52px;flex:0 0 52px;place-items:center;border:1px solid #bfe5cd;border-radius:14px;background:#effaf2;color:#08723a;font-size:21px}.restriction-class-title{min-width:0}.restriction-class-title h3{margin:0;color:#1f2d3d;font-size:16px;font-weight:750;line-height:1.3;text-transform:uppercase}.restriction-class-title p{margin:4px 0 0;color:#697b8d;font-size:12px}.restriction-class-info{display:flex;align-items:flex-start;gap:10px;margin:17px 0 15px;color:#536b7d}.restriction-class-info>i{width:16px;margin-top:2px;color:#08723a;text-align:center}.restriction-class-summary{min-height:37px;max-height:39px;overflow:hidden;color:#5d7182;font-size:12px;line-height:1.55}.restriction-class-summary strong{color:#263f50}.restriction-class-summary .restriction-tag{display:inline-block;max-width:100%;margin:0 4px 4px 0;overflow:hidden;text-overflow:ellipsis;vertical-align:middle;white-space:nowrap}.restriction-class-rule-count{color:#263f50;font-size:12px;font-weight:700}.restriction-class-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:15px;border-top:1px solid #e3ebe6}.restriction-class-status{display:inline-flex;align-items:center;gap:6px;min-height:30px;padding:0 11px;border:1px solid #bfe5cd;border-radius:20px;background:#effaf2;color:#176a3b;font-size:10px;font-weight:750;letter-spacing:.04em}.restriction-class-status.empty{border-color:#e1e7ea;background:#f5f7f8;color:#637482}.restriction-class-status.inactive{border-color:#efd9aa;background:#fff9ed;color:#875a0b}.restriction-class-access{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;padding:0 16px;border:0;border-radius:9px;background:#124a36;color:#fff;box-shadow:0 5px 12px rgba(18,74,54,.16);font:600 12px 'Inter',sans-serif;cursor:pointer}.restriction-class-access:hover{background:#0b3b2a}.restriction-class-assignment{width:100%;margin-top:14px;padding-top:14px;border-top:1px solid #e3ebe6}.restriction-class-assignment[hidden]{display:none}.restriction-class-assignment .restriction-assignment{max-height:190px;overflow:auto;padding-right:4px}.restriction-class-assignment .restriction-class-actions{margin-top:12px}.restriction-class-assignment .restriction-tag{margin:0}
@media(max-width:900px){.restriction-layout{grid-template-columns:1fr}.restriction-form,.restriction-list-panel{padding:18px}}
@media(max-width:900px){.restriction-class-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.restriction-heading{flex-direction:row;align-items:center}.restriction-tabs{width:100%}.restriction-tab{flex:1;justify-content:center}.restriction-time-grid{gap:9px}.restriction-empty{min-height:240px}.restriction-class-grid{grid-template-columns:1fr}.restriction-class-card{min-height:215px;padding:17px}}
#restricoes.page-section.active{display:flex;min-height:calc(100dvh - 68px);flex-direction:column;justify-content:center;animation:restriction-section-enter .42s ease both}
@keyframes restriction-section-enter{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@media(max-width:760px){#restricoes.page-section.active{min-height:calc(100dvh - 86px)}}
.restriction-class-access,.restriction-primary{background:#008c45;color:#fff}.restriction-class-access:hover,.restriction-primary:hover{background:#06743d}
.restriction-class-assignment .restriction-class-actions{display:block;margin-top:12px}.restriction-class-assignment .restriction-class-actions .restriction-tag{display:none}.restriction-class-assignment .restriction-class-actions .restriction-primary{display:flex;width:100%;min-height:44px;align-items:center;justify-content:center;gap:8px;padding:0 16px;font-size:13px}
@media(prefers-reduced-motion:reduce){#restricoes.page-section.active{animation:none}}
.restriction-class-grid{grid-template-columns:repeat(3,minmax(0,1fr))}
@media(max-width:900px){.restriction-class-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.restriction-class-grid{grid-template-columns:1fr}}
</style>
<section class="page-section restriction-page" id="restricoes" aria-label="Restrições">
    <header class="restriction-heading">
        <div>
            <h1>Restrições</h1>
            <p>Configure regras de horário e aplique-as às turmas.</p>
        </div>
    </header>

    <?php if (isset($restrictionModelError) && $restrictionModelError) { ?>
        <div class="restriction-disabled-note" role="status">A estrutura necessária para esta área não está instalada. Consulte o README para configurar o banco de dados e, depois, atualize a página.</div>
    <?php } else { ?>
        <?php if (isset($restrictionMessages[$restrictionStatus])) { ?>
            <p class="restriction-feedback<?= in_array($restrictionStatus, ['error', 'invalid'], true) ? ' error' : '' ?>" role="status"><?= htmlspecialchars($restrictionMessages[$restrictionStatus], ENT_QUOTES, 'UTF-8') ?></p>
        <?php } ?>
        <div class="restriction-tabs" role="tablist" aria-label="Gerenciar restrições">
            <button class="restriction-tab" type="button" role="tab" id="restriction-tab-rules" aria-controls="restriction-panel-rules" aria-selected="true" data-restriction-tab="rules"><i class="fas fa-calendar-check" aria-hidden="true"></i>Regras</button>
            <button class="restriction-tab" type="button" role="tab" id="restriction-tab-classes" aria-controls="restriction-panel-classes" aria-selected="false" data-restriction-tab="classes"><i class="fas fa-users" aria-hidden="true"></i>Turmas</button>
        </div>

        <div class="restriction-panel" id="restriction-panel-rules" role="tabpanel" aria-labelledby="restriction-tab-rules" data-restriction-panel="rules">
            <div class="restriction-layout">
                <form class="restriction-form" action="../control/control_index.php" method="post" id="restriction-form">
                    <div class="restriction-form-heading">
                        <span class="restriction-heading-icon" aria-hidden="true"><i class="fas fa-shield-halved"></i></span>
                        <div><h2 id="restriction-form-title">Criar restrição</h2><p class="restriction-form-intro">Preencha os campos abaixo para criar uma nova regra de restrição.</p></div>
                    </div>
                    <input type="hidden" name="restriction_action" value="save">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_restricao" id="restriction-id" value="">
                    <div class="restriction-field">
                        <label for="restriction-name">Nome da restrição <span aria-hidden="true">*</span></label>
                        <input type="text" id="restriction-name" name="nome" maxlength="100" required placeholder="Ex.: Entrada após o início da aula">
                    </div>
                    <div class="restriction-field">
                        <label for="restriction-type">Aplicar em <span aria-hidden="true">*</span></label>
                        <select id="restriction-type" name="tipo_registro" required>
                            <option value="todos">Todos os registros</option>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saídas Antecipadas</option>
                            <option value="estagio">Saída para estágio</option>
                        </select>
                    </div>
                    <div class="restriction-field">
                        <label for="restriction-mode">Comportamento <span aria-hidden="true">*</span></label>
                        <select id="restriction-mode" name="modo" required>
                            <option value="bloquear">Bloquear no intervalo</option>
                            <option value="permitir">Permitir somente no intervalo</option>
                        </select>
                    </div>
                    <div class="restriction-time-grid">
                        <div class="restriction-field">
                            <label for="restriction-start">Início do intervalo <span aria-hidden="true">*</span></label>
                            <input type="time" id="restriction-start" name="hora_inicio" required>
                        </div>
                        <div class="restriction-field">
                            <label for="restriction-end">Fim do intervalo <span aria-hidden="true">*</span></label>
                            <input type="time" id="restriction-end" name="hora_fim" required>
                        </div>
                    </div>
                    <div class="restriction-form-actions">
                        <button class="restriction-primary" type="submit"><i class="fas fa-plus" aria-hidden="true"></i>Salvar restrição</button>
                        <button class="restriction-secondary" type="reset" id="restriction-reset" hidden>Cancelar edição</button>
                    </div>
                </form>

                <div class="restriction-list-panel">
                    <div class="restriction-list-heading">
                        <span class="restriction-list-icon" aria-hidden="true"><i class="fas fa-list-ul"></i></span>
                        <h2 class="restriction-list-title">Restrições cadastradas</h2>
                        <span class="restriction-count"><?= count($restrictionList) ?> <?= count($restrictionList) === 1 ? 'regra' : 'regras' ?></span>
                    </div>
                    <div class="restriction-list">
                        <?php if (empty($restrictionList)) { ?>
                            <div class="restriction-empty"><i class="fas fa-clipboard-list" aria-hidden="true"></i><strong>Nenhuma restrição criada.</strong><span>As regras cadastradas aparecerão aqui.</span></div>
                        <?php } ?>
                        <?php foreach ($restrictionList as $restriction) {
                            $restrictionId = (int) $restriction['id_restricao'];
                            $restrictionTypeLabels = ['todos' => 'Todos os registros', 'entrada' => 'Entrada', 'saida' => 'Saídas Antecipadas', 'estagio' => 'Saída para estágio'];
                            $restrictionModeLabel = $restriction['modo'] === 'permitir' ? 'Permitir somente no intervalo' : 'Bloquear no intervalo';
                        ?>
                            <article class="restriction-card">
                                <div class="restriction-card-head">
                                    <div>
                                        <h3><?= htmlspecialchars($restriction['nome'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p><?= htmlspecialchars($restrictionTypeLabels[$restriction['tipo_registro']] ?? 'Todos os registros', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($restrictionModeLabel, ENT_QUOTES, 'UTF-8') ?><br><?= htmlspecialchars(substr($restriction['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8') ?>–<?= htmlspecialchars(substr($restriction['hora_fim'], 0, 5), ENT_QUOTES, 'UTF-8') ?></p>
                                    </div>
                                    <div class="restriction-card-actions">
                                        <button class="restriction-edit" type="button" data-restriction-edit data-id="<?= $restrictionId ?>" data-name="<?= htmlspecialchars($restriction['nome'], ENT_QUOTES, 'UTF-8') ?>" data-type="<?= htmlspecialchars($restriction['tipo_registro'], ENT_QUOTES, 'UTF-8') ?>" data-mode="<?= htmlspecialchars($restriction['modo'], ENT_QUOTES, 'UTF-8') ?>" data-start="<?= htmlspecialchars(substr($restriction['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8') ?>" data-end="<?= htmlspecialchars(substr($restriction['hora_fim'], 0, 5), ENT_QUOTES, 'UTF-8') ?>">Editar</button>
                                        <form action="../control/control_index.php" method="post" onsubmit="return confirm('Excluir esta restrição? As atribuições às turmas também serão removidas.');">
                                            <input type="hidden" name="restriction_action" value="delete">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id_restricao" value="<?= $restrictionId ?>">
                                            <button class="restriction-danger" type="submit" aria-label="Excluir <?= htmlspecialchars($restriction['nome'], ENT_QUOTES, 'UTF-8') ?>">Excluir</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="restriction-panel" id="restriction-panel-classes" role="tabpanel" aria-labelledby="restriction-tab-classes" data-restriction-panel="classes" hidden>
            <?php if (empty($restrictionList)) { ?>
                <div class="restriction-empty">Crie uma restrição na aba “Regras” antes de atribuí-la às turmas.</div>
            <?php } else { ?>
                <div class="restriction-class-grid">
                    <?php foreach ($restrictionClasses as $restrictionClass) {
                        $classId = (int) $restrictionClass['id_turma'];
                        $assignedRestrictionIds = $restrictionAssignments[$classId] ?? [];
                        $className = trim((string) ($restrictionClass['ano'] ?? '') . ' ' . (string) ($restrictionClass['turma'] ?? ''));
                        $classRestrictions = array_values(array_filter($restrictionList, static function ($restriction) use ($assignedRestrictionIds) {
                            return in_array((int) $restriction['id_restricao'], $assignedRestrictionIds, true);
                        }));
                        $activeClassRestrictions = array_filter($classRestrictions, static function ($restriction) {
                            return (int) $restriction['ativo'] === 1;
                        });
                        $classStatus = count($activeClassRestrictions) > 0 ? 'ATIVA' : (count($classRestrictions) > 0 ? 'INATIVA' : 'SEM RESTRIÇÃO');
                        $classStatusClass = count($activeClassRestrictions) > 0 ? '' : (count($classRestrictions) > 0 ? ' inactive' : ' empty');
                    ?>
                        <article class="restriction-class-card">
                            <header class="restriction-class-head">
                                <span class="restriction-class-icon" aria-hidden="true"><i class="fas fa-users"></i></span>
                                <div class="restriction-class-title">
                                    <h3><?= htmlspecialchars(mb_strtoupper($className, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p>Gerenciamento de regras da turma</p>
                                </div>
                            </header>
                            <div class="restriction-class-info">
                                <i class="fas fa-shield-halved" aria-hidden="true"></i>
                                <div class="restriction-class-summary">
                                    <strong><?= count($activeClassRestrictions) ?> <?= count($activeClassRestrictions) === 1 ? 'restrição ativa' : 'restrições ativas' ?></strong>
                                    <?php if ($classRestrictions) { ?>
                                        <div><?php foreach (array_slice($classRestrictions, 0, 2) as $classRestriction) { ?><span class="restriction-tag"><?= htmlspecialchars($classRestriction['nome'], ENT_QUOTES, 'UTF-8') ?></span><?php } ?><?php if (count($classRestrictions) > 2) { ?><span class="restriction-tag">+<?= count($classRestrictions) - 2 ?></span><?php } ?></div>
                                    <?php } else { ?>
                                        <div>Nenhuma regra atribuída a esta turma.</div>
                                    <?php } ?>
                                </div>
                            </div>
                            <footer class="restriction-class-footer">
                                <span class="restriction-class-status<?= $classStatusClass ?>"><i class="fas fa-circle" aria-hidden="true"></i><?= htmlspecialchars($classStatus, ENT_QUOTES, 'UTF-8') ?></span>
                                <button class="restriction-class-access" type="button" data-class-access aria-expanded="false"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i><span>Acessar</span></button>
                            </footer>
                            <form class="restriction-class-assignment" action="../control/control_index.php" method="post" data-class-assignment hidden>
                                <input type="hidden" name="restriction_action" value="assign">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_turma" value="<?= $classId ?>">
                                <div class="restriction-assignment">
                                    <?php foreach ($restrictionList as $restriction) { ?>
                                        <label class="restriction-check">
                                            <input type="checkbox" name="restricoes[]" value="<?= (int) $restriction['id_restricao'] ?>"<?= in_array((int) $restriction['id_restricao'], $assignedRestrictionIds, true) ? ' checked' : '' ?>>
                                            <span><?= htmlspecialchars($restriction['nome'], ENT_QUOTES, 'UTF-8') ?><?= (int) $restriction['ativo'] !== 1 ? ' (inativa)' : '' ?></span>
                                        </label>
                                    <?php } ?>
                                </div>
                                <div class="restriction-class-actions">
                                    <span class="restriction-tag"><?= count($assignedRestrictionIds) ? 'Regras configuradas' : 'Sem restrições' ?></span>
                                    <button class="restriction-primary" type="submit"><i class="fas fa-floppy-disk" aria-hidden="true"></i>Salvar turma</button>
                                </div>
                            </form>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</section>
<script>
(() => {
    const section = document.getElementById('restricoes');
    if (!section) return;

    const tabs = Array.from(section.querySelectorAll('[data-restriction-tab]'));
    const panels = Array.from(section.querySelectorAll('[data-restriction-panel]'));
    tabs.forEach((tab) => tab.addEventListener('click', () => {
        const target = tab.dataset.restrictionTab;
        tabs.forEach((item) => item.setAttribute('aria-selected', String(item === tab)));
        panels.forEach((panel) => { panel.hidden = panel.dataset.restrictionPanel !== target; });
    }));

    const form = document.getElementById('restriction-form');
    const resetButton = document.getElementById('restriction-reset');
    const formTitle = document.getElementById('restriction-form-title');
    section.querySelectorAll('[data-restriction-edit]').forEach((button) => button.addEventListener('click', () => {
        form.elements.id_restricao.value = button.dataset.id;
        form.elements.nome.value = button.dataset.name;
        form.elements.tipo_registro.value = button.dataset.type;
        form.elements.modo.value = button.dataset.mode;
        form.elements.hora_inicio.value = button.dataset.start;
        form.elements.hora_fim.value = button.dataset.end;
        formTitle.textContent = 'Editar restrição';
        resetButton.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
        form.elements.nome.focus();
    }));
    section.querySelectorAll('[data-class-access]').forEach((button) => button.addEventListener('click', () => {
        const assignment = button.closest('.restriction-class-card')?.querySelector('[data-class-assignment]');
        if (!assignment) return;
        const isOpen = !assignment.hidden;
        assignment.hidden = isOpen;
        button.setAttribute('aria-expanded', String(!isOpen));
        button.querySelector('span').textContent = isOpen ? 'Acessar' : 'Fechar';
        button.querySelector('i').className = isOpen ? 'fas fa-arrow-up-right-from-square' : 'fas fa-xmark';
    }));
    form?.addEventListener('reset', () => window.setTimeout(() => {
        form.elements.id_restricao.value = '';
        formTitle.textContent = 'Criar restrição';
        resetButton.hidden = true;
    }, 0));
})();
</script>
