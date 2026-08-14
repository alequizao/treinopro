<?php
/** Todas as fichas de dieta do tenant */
exige('treinador');
$T = tenant_id();
$lista = todos("SELECT d.*, u.nome AS aluno,
                (SELECT COUNT(*) FROM tr_dieta_refeicoes r WHERE r.dieta_id=d.id) AS refeicoes
                FROM tr_dietas d JOIN tr_usuarios u ON u.id=d.aluno_id
                WHERE d.tenant_id=? ORDER BY d.status, d.criado_em DESC", [$T]);
topo('Fichas de dieta', count($lista) . ' ficha(s)');
?>
<div class="card-soft">
  <div class="ch"><i class="fa-solid fa-apple-whole"></i> Todas as dietas
    <span class="acoes"><a href="<?= url('alunos') ?>" class="btn-acao"><i class="fa-solid fa-plus"></i> Criar pelo aluno</a></span>
  </div>
  <?php if (!$lista) { vazio('Nenhuma dieta criada ainda.', 'fa-utensils'); } else { ?>
    <div class="tabela-wrap"><table class="tabela">
      <thead><tr><th>Dieta</th><th>Aluno</th><th>Meta</th><th>Refeições</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($lista as $d): ?>
        <tr>
          <td><a href="<?= url('dieta', ['id' => $d['id']]) ?>"><strong><?= e($d['nome']) ?></strong></a>
              <br><small class="text-muted"><?= e($d['objetivo'] ?: '') ?></small></td>
          <td><a href="<?= url('aluno', ['id' => $d['aluno_id']]) ?>"><?= e($d['aluno']) ?></a></td>
          <td><?= $d['kcal_alvo'] ? (int) $d['kcal_alvo'] . ' kcal' : '—' ?></td>
          <td><?= (int) $d['refeicoes'] ?></td>
          <td><?= $d['status'] === 'ativa' ? badge('Ativa', 'ok') : badge('Arquivada', 'pausa') ?></td>
          <td class="text-end">
            <a href="<?= url('dieta', ['id' => $d['id']]) ?>" class="btn-icone"><i class="fa-solid fa-pen"></i></a>
            <button class="btn-icone perigo" data-click="dieta_excluir" data-dados='{"id":<?= (int) $d['id'] ?>}' data-confirma="Excluir dieta?"><i class="fa-solid fa-trash"></i></button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php } ?>
</div>
<?php rodape();
