<?php
$titulo = 'Relatórios'; $ativo = 'relatorios';
require __DIR__ . '/../includes/header.php';
$pdo = db();
$tipos = ['estoque' => 'Geral de estoque', 'por_categoria' => 'Produtos por categoria', 'movimentacoes' => 'Movimentações', 'abaixo_minimo' => 'Abaixo do estoque mínimo'];
$tipo = array_key_exists($_GET['tipo'] ?? '', $tipos) ? $_GET['tipo'] : 'estoque';
$f = ['produto' => $_GET['produto'] ?? '', 'categoria' => $_GET['categoria'] ?? '', 'de' => $_GET['de'] ?? '', 'ate' => $_GET['ate'] ?? ''];
$rel = Estoque::relatorio($pdo, $tipo, $f);
$cats = Estoque::categorias($pdo);
$prods = Estoque::produtos($pdo);
?>
<form class="card p-3 mb-3 no-print" method="get"><div class="row g-2 align-items-end">
  <div class="col-md-3"><label class="form-label small">Relatório</label><select name="tipo" class="form-select"><?php foreach ($tipos as $k => $v): ?><option value="<?= $k ?>" <?= $k === $tipo ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
  <div class="col-md-3"><label class="form-label small">Produto</label><select name="produto" class="form-select"><option value="">Todos</option><?php foreach ($prods as $p): ?><option value="<?= $p['id_produto'] ?>" <?= $f['produto'] == $p['id_produto'] ? 'selected' : '' ?>><?= e($p['codigo'] . ' · ' . $p['nome']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><label class="form-label small">Categoria</label><select name="categoria" class="form-select"><option value="">Todas</option><?php foreach ($cats as $c): ?><option value="<?= $c['id_categoria'] ?>" <?= $f['categoria'] == $c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
  <div class="col-md-2"><label class="form-label small">De (movimentações)</label><input type="date" name="de" value="<?= e($f['de']) ?>" class="form-control"></div>
  <div class="col-md-2"><label class="form-label small">Até</label><input type="date" name="ate" value="<?= e($f['ate']) ?>" class="form-control"></div>
  <div class="col-12 d-flex gap-2"><button class="btn btn-primary"><i class="bi bi-funnel"></i> Gerar</button><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button></div>
</div></form>
<div class="card p-3"><h5><?= e($rel['titulo']) ?></h5><small class="text-muted mb-3">Gerado em <?= date('d/m/Y H:i') ?> por <?= e($u['nome']) ?> · <?= count($rel['linhas']) ?> registro(s)</small>
<div class="table-responsive"><table class="table table-sm table-striped align-middle mb-0"><thead><tr><?php foreach ($rel['colunas'] as $c): ?><th><?= e($c) ?></th><?php endforeach; ?></tr></thead><tbody>
<?php foreach ($rel['linhas'] as $l): ?><tr><?php foreach ($rel['campos'] as $k): ?><td><?= e($l[$k] ?? '') ?></td><?php endforeach; ?></tr>
<?php endforeach; if (!$rel['linhas']): ?><tr><td colspan="<?= count($rel['colunas']) ?>" class="text-center text-muted py-3">Sem dados para os filtros informados.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php require __DIR__ . '/../includes/footer.php';
