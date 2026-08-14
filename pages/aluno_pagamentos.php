<?php
/** App do aluno — meus pagamentos */
exige('aluno');
$EU = (int) usuario()['id'];
$cobs = todos('SELECT * FROM tr_cobrancas WHERE aluno_id=? ORDER BY vencimento DESC', [$EU]);
$aberto = (float) valor("SELECT COALESCE(SUM(valor),0) FROM tr_cobrancas WHERE aluno_id=? AND status='aberta'", [$EU]);
$t = tenant();
topo('Pagamentos', 'Suas mensalidades');
?>
<div class="row g-3 mb-2">
  <?php
  card_stat('Em aberto', dinheiro($aberto), 'fa-hourglass-half', $aberto > 0 ? '#D98E0B' : '#1E9E57');
  card_stat('Cobranças', (string) count($cobs), 'fa-receipt');
  ?>
</div>

<?php if ($aberto > 0 && !empty($t['whatsapp'])): ?>
  <a class="btn-acao w-100 justify-content-center mb-3"
     href="https://wa.me/<?= e(preg_replace('/\D/', '', $t['whatsapp'])) ?>?text=<?= rawurlencode('Olá! Quero acertar minha mensalidade.') ?>"
     target="_blank"><i class="fa-brands fa-whatsapp"></i> Falar com o treinador</a>
<?php endif; ?>

<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-receipt"></i> Histórico</div>
  <?php if (!$cobs) { vazio('Nenhuma cobrança registrada.', 'fa-receipt'); } else { ?>
    <table class="tabela">
      <?php foreach ($cobs as $c): $venc = $c['status'] === 'aberta' && $c['vencimento'] < date('Y-m-d'); ?>
        <tr>
          <td><strong><?= e($c['descricao']) ?></strong><br>
            <small style="color:#8B9099">Venc. <?= data_br($c['vencimento']) ?></small></td>
          <td class="text-end"><?= dinheiro($c['valor']) ?><br>
            <?= $c['status'] === 'paga' ? badge('Paga', 'ok') : ($venc ? badge('Vencida', 'erro') : badge('Em aberto', 'pendente')) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php } ?>
</div>
<?php rodape();
