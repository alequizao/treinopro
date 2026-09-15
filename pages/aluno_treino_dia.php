<?php
/*
 * TreinoPro · Desenvolvido por Alequizao <alequizao.dev@gmail.com>
 * https://github.com/alequizao · © 2026 Alequizao. Todos os direitos reservados.
 */
/** App do aluno — resumo do treino antes de iniciar */
exige('aluno');
$EU = (int) usuario()['id'];
$dia = um('SELECT d.*, f.nome AS ficha FROM tr_ficha_dias d JOIN tr_fichas f ON f.id=d.ficha_id
           WHERE d.id=? AND f.aluno_id=?', [id_get('dia'), $EU]);
if (!$dia) { topo('Treino'); vazio('Treino não encontrado.'); rodape(); exit; }

$itens = todos('SELECT x.*, e.nome AS ex_nome, e.grupo, e.equipamento, e.video_url, e.video_arquivo, e.thumb
                FROM tr_ficha_exercicios x JOIN tr_exercicios e ON e.id=x.exercicio_id
                WHERE x.dia_id=? ORDER BY x.ordem, x.id', [$dia['id']]);

// estimativas
$min = (int) ($dia['duracao_min'] ?: max(20, count($itens) * 6));
$kcal = (int) round($min * 7.5);
$cargaTotal = 0;
foreach ($itens as $x) {
    $s = max(1, (int) preg_replace('/\D/', '', $x['series']));
    $c = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $x['carga']));
    $r = (float) preg_replace('/\D/', '', (string) $x['repeticoes']);
    $cargaTotal += $s * $c * max(1, $r);
}
$ultima = um("SELECT * FROM tr_sessoes WHERE aluno_id=? AND dia_id=? AND status='concluida'
              ORDER BY data DESC LIMIT 1", [$EU, $dia['id']]);

topo($dia['nome'], $dia['ficha'], true);
?>
<div class="resumo-treino">
  <span><i class="fa-solid fa-clock"></i> <?= $min ?> min</span>
  <span><i class="fa-solid fa-fire"></i> <?= $kcal ?> kcal</span>
  <span><i class="fa-solid fa-dumbbell"></i> <?= number_format($cargaTotal / 1000, 1, ',', '') ?> ton</span>
</div>

<a href="<?= url('executar', ['dia' => $dia['id']]) ?>" class="btn-ios">
  <i class="fa-solid fa-play"></i> Iniciar Treino</a>

<?php if ($ultima): ?>
  <p style="color:var(--ios-txt2);font-size:.85rem;text-align:center;margin-top:12px">
    Último em <?= data_br($ultima['data']) ?>
    <?= $ultima['nota'] ? ' · você avaliou ' . str_repeat('⭐', (int) $ultima['nota']) : '' ?></p>
<?php endif; ?>

<div class="tit-ios"><h3>Exercícios</h3><span style="color:var(--ios-txt2);font-size:.9rem"><?= count($itens) ?></span></div>

<?php if (!$itens) { vazio('Nenhum exercício neste treino.', 'fa-dumbbell'); } else { ?>
  <div class="lista-ios">
    <?php foreach ($itens as $x):
        $video = $x['video_arquivo'] ? BASE_DIR . '/' . $x['video_arquivo'] : $x['video_url']; ?>
      <div class="li"<?= $video ? ' data-video="' . e($video) . '" data-titulo="' . e($x['ex_nome']) . '" style="cursor:pointer"' : '' ?>>
        <span class="ex-thumb">
          <?php if ($x['thumb']): ?><img src="<?= BASE_DIR ?>/<?= e($x['thumb']) ?>" alt="">
          <?php else: ?><i class="fa-solid fa-dumbbell"></i><?php endif; ?>
        </span>
        <span class="lbl">
          <strong style="display:block"><?= e($x['ex_nome']) ?></strong>
          <small style="color:var(--ios-txt2)">
            <?= e($x['series']) ?> série<?= (int) $x['series'] > 1 ? 's' : '' ?>
            • <?= e($x['repeticoes']) ?> reps<?= $x['carga'] ? ' • ' . e($x['carga']) : '' ?>
            <?= $x['tecnica'] ? ' • ' . e($x['tecnica']) : '' ?></small>
        </span>
        <?php if ($video): ?>
          <span class="ver-video"><i class="fa-solid fa-circle-play"></i> ver</span>
        <?php else: ?>
          <span class="ver-video off"><i class="fa-solid fa-video-slash"></i></span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php } ?>

<style>
.ex-thumb{width:52px;height:52px;border-radius:11px;background:#fff;display:grid;place-items:center;
  overflow:hidden;flex:0 0 auto;color:#1C1C1E;font-size:1.1rem}
.ex-thumb img{width:100%;height:100%;object-fit:cover}
.ver-video{color:var(--ios-laranja);font-weight:700;font-size:.82rem;display:flex;align-items:center;gap:5px;white-space:nowrap}
.ver-video.off{color:#5A5A5E}
.lista-ios .li[data-video]:active{background:var(--ios-card2)}
</style>
<?php rodape();
