<?php
/** Todas as fichas de treino do tenant */
exige('treinador');
$T = tenant_id();
$lista = todos("SELECT f.*, u.nome AS aluno,
                (SELECT COUNT(*) FROM tr_ficha_dias d WHERE d.ficha_id=f.id) AS divisoes
                FROM tr_fichas f JOIN tr_usuarios u ON u.id=f.aluno_id
                WHERE f.tenant_id=? ORDER BY f.status, f.criado_em DESC", [$T]);
topo('Fichas de treino', count($lista) . ' ficha(s)');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-clipboard-list"></i> Todas as fichas
    <span class="acoes"><a href="<?= url('alunos') ?>" class="btn-acao"><i class="fa-solid fa-plus"></i> Criar pelo aluno</a></span>
  </div>
  <?php if (!$lista) { vazio('Nenhuma ficha criada ainda. Abra um aluno e monte a primeira.', 'fa-clipboard-list'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Ficha</th><th>Aluno</th><th>Período</th><th>Divisões</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $f): ?>
        <tr>
          <td><a href="<?= url('ficha', ['id' => $f['id']]) ?>"><strong><?= e($f['nome']) ?></strong></a>
              <br><small class="text-muted"><?= e($f['objetivo'] ?: '') ?></small></td>
          <td><a href="<?= url('aluno', ['id' => $f['aluno_id']]) ?>"><?= e($f['aluno']) ?></a></td>
          <td><small><?= data_br($f['inicio']) ?> — <?= data_br($f['fim']) ?></small></td>
          <td><?= (int) $f['divisoes'] ?></td>
          <td><?= $f['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?></td>
          <td class="text-end">
            <a href="<?= url('ficha', ['id' => $f['id']]) ?>" class="btn-icone"><i class="fa-solid fa-pen"></i></a>
            <button class="btn-icone" data-click="ficha_duplicar" data-dados='{"id":<?= (int) $f['id'] ?>}' title="Duplicar"><i class="fa-solid fa-copy"></i></button>
            <button class="btn-icone perigo" data-click="ficha_excluir" data-dados='{"id":<?= (int) $f['id'] ?>}' data-confirma="Excluir ficha?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>
<?php rodape();
