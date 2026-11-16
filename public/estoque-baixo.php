<?php
$titulo = 'Estoque baixo'; $ativo = 'estoque-baixo';
require __DIR__ . '/../includes/header.php';
$itens = Estoque::estoqueBaixo(db());
?>
<div class="alert alert-light border small">
  <span class="badge text-bg-success">Normal</span> acima do mínimo ·
  <span class="badge text-bg-warning">Baixo</span> igual ou abaixo do mínimo ·
  <span class="badge text-bg-danger">Crítico</span> zerado ou com 50% do mínimo ou menos
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Produto</th><th>Código</th><th>Categoria</th><th class="text-end">Qtd. atual</th><th class="text-end">Mínimo</th><th>Situação</th></tr></thead><tbody>
<?php foreach ($itens as $i): ?><tr><td class="fw-semibold"><?= e($i['nome']) ?></td><td><?= e($i['codigo']) ?></td><td><?= e($i['categoria']) ?></td>
<td class="text-end"><?= (int)$i['quantidade'] ?></td><td class="text-end"><?= (int)$i['estoque_minimo'] ?></td><td><?= badge_situacao($i['situacao']) ?></td></tr>
<?php endforeach; if (!$itens): ?><tr><td colspan="6" class="text-center text-muted py-4">Todos os produtos ativos estão com estoque normal.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
