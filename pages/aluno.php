<?php
/** Ficha do aluno (visão do treinador) — abas: resumo, treinos, dieta, avaliações, anamnese, financeiro */
exige('treinador');
$T = tenant_id();
$a = aluno_do_tenant(id_get());
$aba = (string) get('aba', 'resumo');

$abas = ['resumo' => 'Resumo', 'treinos' => 'Treinos', 'dieta' => 'Dieta',
         'avaliacoes' => 'Avaliações', 'anamnese' => 'Anamnese', 'financeiro' => 'Financeiro'];

$sub = ($a['objetivo'] ? $a['objetivo'] . ' · ' : '') . ($a['email']);
topo($a['nome'], $sub, true);
?>
<div class="d-flex justify-content-end mb-2">
  <a href="<?= url('entrar-como', ['id' => $a['id']]) ?>" class="btn-outline-soft"
     onclick="return confirm('Entrar no app como <?= e($a['nome']) ?>? Você poderá voltar com um clique.')">
    <i class="fa-solid fa-user-secret"></i> Ver o app como <?= e(explode(' ', $a['nome'])[0]) ?></a>
</div>

<div class="abas">
  <?php foreach ($abas as $k => $lb): ?>
    <a href="<?= url('aluno', ['id' => $a['id'], 'aba' => $k]) ?>" class="<?= $aba === $k ? 'ativo' : '' ?>"><?= e($lb) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($aba === 'resumo'):
    $treinos  = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida'", [$a['id']]);
    $treinos7 = (int) valor("SELECT COUNT(*) FROM tr_sessoes WHERE aluno_id=? AND status='concluida' AND data>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)", [$a['id']]);
    $av       = um('SELECT * FROM tr_avaliacoes WHERE aluno_id=? ORDER BY data DESC LIMIT 1', [$a['id']]);
    $devendo  = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE aluno_id=? AND status='aberta'", [$a['id']]);
    $ultimas  = todos("SELECT s.*,d.nome AS treino FROM tr_sessoes s JOIN tr_ficha_dias d ON d.id=s.dia_id
                       WHERE s.aluno_id=? AND s.status='concluida' ORDER BY s.data DESC LIMIT 10", [$a['id']]);
?>
  <div class="row g-3 mb-2">
    <?php
    card_stat('Treinos concluídos', (string) $treinos, 'fa-dumbbell');
    card_stat('Últimos 7 dias', (string) $treinos7, 'fa-fire', '#1E9E57');
    card_stat('Peso atual', $av && $av['peso'] ? number_format((float) $av['peso'], 1, ',', '') . ' kg' : '—', 'fa-weight-scale', '#2D7FF9');
    card_stat('Em aberto', dinheiro($devendo), 'fa-dollar-sign', $devendo > 0 ? '#D9433A' : '#1E9E57');
    ?>
  </div>
  <?php
  $nivel   = nivel_do_xp(xp_total((int) $a['id']));
  $streak  = streak_treinos((int) $a['id']);
  $meds    = array_column(todos('SELECT chave FROM tr_conquistas WHERE aluno_id=?', [$a['id']]), 'chave');
  $cat     = catalogo_conquistas();
  $jAtivo  = jejum_ativo((int) $a['id']);
  $jTotal  = (int) valor("SELECT COUNT(*) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$a['id']]);
  $jMedia  = (float) (valor("SELECT AVG(horas) FROM tr_jejum WHERE aluno_id=? AND status='concluido'", [$a['id']]) ?: 0);
  $aguaSem = (int) valor('SELECT COUNT(*) FROM tr_agua WHERE aluno_id=? AND litros>=? AND data>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)',
                         [$a['id'], (float) ($a['meta_agua'] ?: 3)]);
  ?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-gamepad"></i> Engajamento
      <span class="acoes">
        <?php if ($streak > 0): ?><span class="streak-chip"><i class="fa-solid fa-fire"></i> <?= $streak ?> dias</span><?php endif; ?>
        <span class="chip">Nível <?= (int) $nivel['nivel'] ?> · <?= e($nivel['titulo']) ?></span>
      </span>
    </div>
    <div class="row g-3 mb-2">
      <?php
      card_stat('Jejuns concluídos', (string) $jTotal, 'fa-hourglass-end', '#1E9E57');
      card_stat('Média de jejum', $jMedia ? number_format($jMedia, 1, ',', '') . 'h' : '—', 'fa-clock', '#D98E0B');
      card_stat('Metas de água (7d)', $aguaSem . '/7', 'fa-droplet', '#2D7FF9');
      card_stat('Medalhas', count($meds) . '/' . count($cat), 'fa-medal', '#7A28C7');
      ?>
    </div>
    <?php if ($jAtivo):
        $hj = (time() - strtotime($jAtivo['inicio'])) / 3600; $fs = fase_atual($hj); ?>
      <p class="mb-2" style="font-size:.9rem">
        <span class="badge-status" style="background:<?= e($fs[3]) ?>1f;color:<?= e($fs[3]) ?>">
          <i class="fa-solid <?= e($fs[2]) ?>"></i> Em jejum há <?= number_format($hj, 1, ',', '') ?>h — <?= e($fs[1]) ?>
        </span>
        <small class="text-muted">meta <?= number_format((float) $jAtivo['meta_horas'], 0) ?>h (<?= e($jAtivo['protocolo']) ?>)</small>
      </p>
    <?php endif; ?>
    <?php if ($meds): ?>
      <div class="medalhas">
        <?php foreach ($meds as $ch): if (!isset($cat[$ch])) continue; ?>
          <div class="medalha">
            <span class="mi" style="background:<?= e($cat[$ch][2]) ?>"><i class="fa-solid <?= e($cat[$ch][1]) ?>"></i></span>
            <small><?= e($cat[$ch][0]) ?></small>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <div class="card-soft">
        <div class="ch"><i class="fa-solid fa-user"></i> Dados</div>
        <table class="tabela">
          <tr><td class="text-muted">Idade</td><td><?= idade($a['nascimento']) !== null ? idade($a['nascimento']) . ' anos' : '—' ?></td></tr>
          <tr><td class="text-muted">Telefone</td><td><?= e($a['telefone'] ?: '—') ?></td></tr>
          <tr><td class="text-muted">Objetivo</td><td><?= e($a['objetivo'] ?: '—') ?></td></tr>
          <tr><td class="text-muted">Meta de água</td><td><?= $a['meta_agua'] ? number_format((float) $a['meta_agua'], 1, ',', '') . ' L/dia' : '—' ?></td></tr>
          <tr><td class="text-muted">Cadastro</td><td><?= data_br($a['criado_em']) ?></td></tr>
          <tr><td class="text-muted">Último acesso</td><td><?= $a['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($a['ultimo_acesso'])) : 'nunca' ?></td></tr>
        </table>
        <?php if ($a['obs']): ?><p class="text-muted small mt-2 mb-0"><?= nl2br(e($a['obs'])) ?></p><?php endif; ?>
      </div>
    </div>
    <div class="col-lg-7">
      <div class="card-soft">
        <div class="ch"><i class="fa-solid fa-clock-rotate-left"></i> Histórico de treinos</div>
        <?php if (!$ultimas) { vazio('Nenhum treino registrado.'); } else { ?>
          <table class="tabela">
            <?php foreach ($ultimas as $s): ?>
              <tr><td><?= data_br($s['data']) ?></td><td><strong><?= e($s['treino']) ?></strong></td>
                  <td><?= $s['nota'] ? '⭐ ' . (int) $s['nota'] : '' ?></td>
                  <td class="text-muted small"><?= e(mb_strimwidth((string) $s['feedback'], 0, 40, '...')) ?></td></tr>
            <?php endforeach; ?>
          </table>
        <?php } ?>
      </div>
    </div>
  </div>

<?php elseif ($aba === 'treinos'):
    $fichas = todos('SELECT * FROM tr_fichas WHERE aluno_id=? ORDER BY status, criado_em DESC', [$a['id']]);
?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-clipboard-list"></i> Fichas de treino
      <span class="acoes"><button class="btn-acao" data-modal="mFicha"><i class="fa-solid fa-plus"></i> Nova ficha</button></span>
    </div>
    <?php if (!$fichas) { vazio('Nenhuma ficha criada para este aluno.', 'fa-clipboard-list'); } else { ?>
      <table class="tabela">
        <thead><tr><th>Ficha</th><th>Período</th><th>Treinos</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($fichas as $f):
            $nd = (int) valor('SELECT COUNT(*) FROM tr_ficha_dias WHERE ficha_id=?', [$f['id']]); ?>
          <tr>
            <td><a href="<?= url('ficha', ['id' => $f['id']]) ?>"><strong><?= e($f['nome']) ?></strong></a>
                <br><small class="text-muted"><?= e($f['objetivo'] ?: '') ?></small></td>
            <td><small><?= data_br($f['inicio']) ?> — <?= data_br($f['fim']) ?></small></td>
            <td><?= $nd ?> divisões</td>
            <td><?= $f['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?></td>
            <td class="text-end">
              <a href="<?= url('ficha', ['id' => $f['id']]) ?>" class="btn-icone"><i class="fa-solid fa-pen"></i></a>
              <button class="btn-icone" title="Duplicar" data-click="ficha_duplicar" data-dados='{"id":<?= (int) $f['id'] ?>,"aluno_id":<?= (int) $a['id'] ?>}'><i class="fa-solid fa-copy"></i></button>
              <button class="btn-icone perigo" data-click="ficha_excluir" data-dados='{"id":<?= (int) $f['id'] ?>}' data-confirma="Excluir a ficha?"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>
  </div>
  <div class="modal-bg" id="mFicha"><div class="modal-cx">
    <button class="fechar" data-fecha="mFicha"><i class="fa-solid fa-xmark"></i></button>
    <h3>Nova ficha de treino</h3>
    <form data-acao="ficha_salvar" data-redir="1">
      <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
      <div class="row g-3">
        <div class="col-md-7"><label class="form-label">Nome da ficha *</label>
          <input name="nome" class="form-control" required placeholder="Hipertrofia ABC - Fase 1"></div>
        <div class="col-md-5"><label class="form-label">Objetivo</label><input name="objetivo" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">Início</label><input name="inicio" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-6"><label class="form-label">Fim</label><input name="fim" type="date" class="form-control" value="<?= date('Y-m-d', strtotime('+60 days')) ?>"></div>
        <div class="col-12"><label class="form-label">Observações gerais</label><textarea name="obs" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-outline-soft" data-fecha="mFicha">Cancelar</button>
        <button class="btn-acao">Criar ficha</button>
      </div>
    </form>
  </div></div>

<?php elseif ($aba === 'dieta'):
    $dietas = todos('SELECT * FROM tr_dietas WHERE aluno_id=? ORDER BY status, criado_em DESC', [$a['id']]);
?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-apple-whole"></i> Fichas de dieta
      <span class="acoes"><button class="btn-acao" data-modal="mDieta"><i class="fa-solid fa-plus"></i> Nova dieta</button></span>
    </div>
    <?php if (!$dietas) { vazio('Nenhuma dieta criada.', 'fa-utensils'); } else { ?>
      <table class="tabela">
        <thead><tr><th>Dieta</th><th>Meta</th><th>Refeições</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($dietas as $d):
            $nr = (int) valor('SELECT COUNT(*) FROM tr_dieta_refeicoes WHERE dieta_id=?', [$d['id']]); ?>
          <tr>
            <td><a href="<?= url('dieta', ['id' => $d['id']]) ?>"><strong><?= e($d['nome']) ?></strong></a>
                <br><small class="text-muted"><?= e($d['objetivo'] ?: '') ?></small></td>
            <td><?= $d['kcal_alvo'] ? (int) $d['kcal_alvo'] . ' kcal' : '—' ?></td>
            <td><?= $nr ?></td>
            <td><?= $d['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?></td>
            <td class="text-end">
              <a href="<?= url('dieta', ['id' => $d['id']]) ?>" class="btn-icone"><i class="fa-solid fa-pen"></i></a>
              <button class="btn-icone perigo" data-click="dieta_excluir" data-dados='{"id":<?= (int) $d['id'] ?>}' data-confirma="Excluir a dieta?"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>
  </div>
  <div class="modal-bg" id="mDieta"><div class="modal-cx">
    <button class="fechar" data-fecha="mDieta"><i class="fa-solid fa-xmark"></i></button>
    <h3>Nova ficha de dieta</h3>
    <form data-acao="dieta_salvar" data-redir="1">
      <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
      <div class="row g-3">
        <div class="col-md-7"><label class="form-label">Nome *</label><input name="nome" class="form-control" required placeholder="Bulking Masculino"></div>
        <div class="col-md-5"><label class="form-label">Meta de calorias</label><input name="kcal_alvo" type="number" class="form-control" placeholder="2400"></div>
        <div class="col-12"><label class="form-label">Objetivo</label><input name="objetivo" class="form-control"></div>
        <div class="col-12"><label class="form-label">Orientações gerais</label><textarea name="obs" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-outline-soft" data-fecha="mDieta">Cancelar</button>
        <button class="btn-acao">Criar dieta</button>
      </div>
    </form>
  </div></div>

<?php elseif ($aba === 'avaliacoes'):
    $avs = todos('SELECT * FROM tr_avaliacoes WHERE aluno_id=? ORDER BY data DESC', [$a['id']]);
    $serie = array_reverse(array_map(function ($x) {
        return ['d' => date('m/y', strtotime($x['data'])), 'p' => (float) $x['peso'], 'g' => (float) $x['gordura']];
    }, $avs));
?>
  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card-soft">
        <div class="ch"><i class="fa-solid fa-ruler"></i> Avaliações físicas
          <span class="acoes"><button class="btn-acao" data-modal="mAval" onclick="novaAval()"><i class="fa-solid fa-plus"></i> Nova</button></span>
        </div>
        <?php if (!$avs) { vazio('Nenhuma avaliação registrada.', 'fa-ruler'); } else { ?>
          <div class="tabela-wrap"><table class="tabela">
            <thead><tr><th>Data</th><th>Peso</th><th>%G</th><th>Massa magra</th><th>IMC</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($avs as $v):
                $imc = ($v['peso'] && $v['altura']) ? $v['peso'] / pow($v['altura'] / 100, 2) : null; ?>
              <tr>
                <td><?= data_br($v['data']) ?></td>
                <td><?= $v['peso'] ? number_format((float) $v['peso'], 1, ',', '') . ' kg' : '—' ?></td>
                <td><?= $v['gordura'] ? number_format((float) $v['gordura'], 1, ',', '') . '%' : '—' ?></td>
                <td><?= $v['massa_magra'] ? number_format((float) $v['massa_magra'], 1, ',', '') . ' kg' : '—' ?></td>
                <td><?= $imc ? number_format($imc, 1, ',', '') : '—' ?></td>
                <td class="text-end">
                  <button class="btn-icone" onclick='editarAval(<?= json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-pen"></i></button>
                  <button class="btn-icone perigo" data-click="avaliacao_excluir" data-dados='{"id":<?= (int) $v['id'] ?>}' data-confirma="Excluir avaliação?"><i class="fa-solid fa-trash"></i></button>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table></div>
        <?php } ?>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card-soft">
        <div class="ch"><i class="fa-solid fa-chart-line"></i> Evolução</div>
        <canvas id="grafEvo" height="200"></canvas>
      </div>
    </div>
  </div>

  <div class="modal-bg" id="mAval"><div class="modal-cx">
    <button class="fechar" data-fecha="mAval"><i class="fa-solid fa-xmark"></i></button>
    <h3>Avaliação física</h3>
    <form data-acao="avaliacao_salvar" id="formAval">
      <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
      <input type="hidden" name="id" id="v_id">
      <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Data</label><input name="data" id="v_data" type="date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="col-md-3"><label class="form-label">Peso (kg)</label><input name="peso" id="v_peso" type="number" step="0.1" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Altura (cm)</label><input name="altura" id="v_altura" type="number" step="0.1" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">% Gordura</label><input name="gordura" id="v_gordura" type="number" step="0.1" class="form-control"></div>
        <div class="col-md-3"><label class="form-label">Massa magra</label><input name="massa_magra" id="v_massa_magra" type="number" step="0.1" class="form-control"></div>
        <div class="col-12"><hr class="my-1"><strong class="small text-muted">MEDIDAS (cm)</strong></div>
        <?php foreach (['torax' => 'Tórax', 'cintura' => 'Cintura', 'abdomen' => 'Abdômen', 'quadril' => 'Quadril',
                        'braco_d' => 'Braço D', 'braco_e' => 'Braço E', 'coxa_d' => 'Coxa D', 'coxa_e' => 'Coxa E',
                        'panturrilha' => 'Panturrilha'] as $k => $lb): ?>
          <div class="col-6 col-md-3"><label class="form-label"><?= $lb ?></label>
            <input name="m_<?= $k ?>" id="v_m_<?= $k ?>" type="number" step="0.1" class="form-control form-control-sm"></div>
        <?php endforeach; ?>
        <div class="col-12"><label class="form-label">Observações</label><textarea name="obs" id="v_obs" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-outline-soft" data-fecha="mAval">Cancelar</button>
        <button class="btn-acao">Salvar avaliação</button>
      </div>
    </form>
  </div></div>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
  var serie = <?= json_encode($serie) ?>;
  if (serie.length) {
    new Chart(document.getElementById('grafEvo'), {
      type:'line',
      data:{labels:serie.map(function(s){return s.d}),datasets:[
        {label:'Peso (kg)',data:serie.map(function(s){return s.p}),borderColor:'#2D7FF9',tension:.35},
        {label:'% Gordura',data:serie.map(function(s){return s.g}),borderColor:'#D98E0B',tension:.35}
      ]},
      options:{plugins:{legend:{position:'bottom'}},scales:{y:{beginAtZero:false}}}
    });
  }
  function novaAval(){ document.getElementById('formAval').reset(); document.getElementById('v_id').value=''; }
  function editarAval(v){
    document.getElementById('formAval').reset();
    ['id','data','peso','altura','gordura','massa_magra','obs'].forEach(function(k){
      var el=document.getElementById('v_'+k); if(el) el.value = v[k]==null?'':v[k];
    });
    var md={}; try{ md=JSON.parse(v.medidas||'{}'); }catch(e){}
    Object.keys(md).forEach(function(k){ var el=document.getElementById('v_m_'+k); if(el) el.value=md[k]; });
    abrirModal('mAval');
  }
  </script>

<?php elseif ($aba === 'anamnese'):
    $an = um('SELECT * FROM tr_anamnese WHERE aluno_id=?', [$a['id']]);
    $r  = $an ? (json_decode((string) $an['respostas'], true) ?: []) : [];
    $perguntas = [
        'saude'      => 'Possui alguma doença/condição de saúde?',
        'lesao'      => 'Tem ou já teve alguma lesão? Onde?',
        'medicamento'=> 'Usa algum medicamento contínuo?',
        'cirurgia'   => 'Já fez alguma cirurgia?',
        'dor'        => 'Sente dores ao treinar? Onde?',
        'experiencia'=> 'Há quanto tempo treina?',
        'frequencia' => 'Quantos dias por semana pode treinar?',
        'local'      => 'Onde vai treinar (academia, casa, ar livre)?',
        'sono'       => 'Quantas horas dorme por noite?',
        'alimentacao'=> 'Como é a sua alimentação hoje?',
        'restricao'  => 'Tem alguma restrição/alergia alimentar?',
        'suplemento' => 'Usa suplementos? Quais?',
        'alcool'     => 'Consome álcool? Com que frequência?',
        'objetivo'   => 'Qual seu principal objetivo?',
    ];
?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-clipboard-question"></i> Anamnese
      <?php if ($an): ?><span class="acoes"><small class="text-muted">Atualizada em <?= date('d/m/Y H:i', strtotime($an['atualizado_em'])) ?></small></span><?php endif; ?>
    </div>
    <form data-acao="anamnese_salvar">
      <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
      <div class="row g-3">
        <?php foreach ($perguntas as $k => $p): ?>
          <div class="col-md-6">
            <label class="form-label"><?= e($p) ?></label>
            <input name="r[<?= $k ?>]" class="form-control" value="<?= e($r[$k] ?? '') ?>">
          </div>
        <?php endforeach; ?>
      </div>
      <div class="text-end mt-3"><button class="btn-acao">Salvar anamnese</button></div>
    </form>
  </div>

<?php else:
    $cobs   = todos('SELECT * FROM tr_cobrancas WHERE aluno_id=? ORDER BY vencimento DESC', [$a['id']]);
    $planos = todos('SELECT * FROM tr_planos WHERE tenant_id=? AND ativo=1 ORDER BY nome', [$T]);
?>
  <div class="card-soft">
    <div class="ch"><i class="fa-solid fa-receipt"></i> Cobranças do aluno
      <span class="acoes"><button class="btn-acao" data-modal="mCob"><i class="fa-solid fa-plus"></i> Nova cobrança</button></span>
    </div>
    <?php if (!$cobs) { vazio('Nenhuma cobrança lançada.', 'fa-receipt'); } else { ?>
      <table class="tabela">
        <thead><tr><th>Descrição</th><th>Vencimento</th><th>Valor</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cobs as $c):
            $venc = $c['status'] === 'aberta' && $c['vencimento'] < date('Y-m-d'); ?>
          <tr>
            <td><?= e($c['descricao']) ?></td>
            <td><?= data_br($c['vencimento']) ?></td>
            <td><?= dinheiro($c['valor']) ?></td>
            <td><?= $c['status'] === 'paga' ? badge('Paga ' . data_br($c['pago_em']), 'ok') : ($venc ? badge('Vencida', 'erro') : badge('Em aberto', 'pendente')) ?></td>
            <td class="text-end">
              <button class="btn-icone" title="<?= $c['status'] === 'paga' ? 'Desfazer' : 'Marcar paga' ?>"
                      data-click="cobranca_pagar" data-dados='{"id":<?= (int) $c['id'] ?>}'><i class="fa-solid fa-circle-check"></i></button>
              <button class="btn-icone perigo" data-click="cobranca_excluir" data-dados='{"id":<?= (int) $c['id'] ?>}' data-confirma="Excluir cobrança?"><i class="fa-solid fa-trash"></i></button>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php } ?>
  </div>
  <div class="modal-bg" id="mCob"><div class="modal-cx">
    <button class="fechar" data-fecha="mCob"><i class="fa-solid fa-xmark"></i></button>
    <h3>Nova cobrança</h3>
    <form data-acao="cobranca_salvar">
      <input type="hidden" name="aluno_id" value="<?= (int) $a['id'] ?>">
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Plano</label>
          <select name="plano_id" class="form-select" onchange="var o=this.selectedOptions[0];if(o.dataset.valor){this.form.valor.value=o.dataset.valor;this.form.descricao.value=o.textContent.trim()}">
            <option value="">Avulso</option>
            <?php foreach ($planos as $pl): ?>
              <option value="<?= (int) $pl['id'] ?>" data-valor="<?= e($pl['valor']) ?>"><?= e($pl['nome']) ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="col-md-6"><label class="form-label">Descrição</label><input name="descricao" class="form-control" value="Mensalidade"></div>
        <div class="col-md-6"><label class="form-label">Valor (R$)</label><input name="valor" class="form-control" required></div>
        <div class="col-md-6"><label class="form-label">Vencimento</label><input name="vencimento" type="date" class="form-control" value="<?= date('Y-m-d', strtotime('+5 days')) ?>"></div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-3">
        <button type="button" class="btn-outline-soft" data-fecha="mCob">Cancelar</button>
        <button class="btn-acao">Lançar cobrança</button>
      </div>
    </form>
  </div></div>
<?php endif;
rodape();
