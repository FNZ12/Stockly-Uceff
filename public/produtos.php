<?php
$titulo = 'Produtos'; $ativo = 'produtos';
require __DIR__ . '/../includes/header.php';
$pdo = db();
$f = ['q' => trim($_GET['q'] ?? ''), 'categoria' => $_GET['categoria'] ?? ''];
$produtos = Estoque::produtos($pdo, $f);
$cats = Estoque::categorias($pdo);
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="d-flex flex-wrap gap-2" method="get">
    <input class="form-control" style="width:240px" name="q" placeholder="Buscar por nome ou código" value="<?= e($f['q']) ?>">
    <select class="form-select" style="width:200px" name="categoria"><option value="">Todas as categorias</option>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id_categoria'] ?>" <?= $f['categoria'] == $c['id_categoria'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option><?php endforeach; ?></select>
    <button class="btn btn-outline-primary"><i class="bi bi-search"></i> Filtrar</button>
  </form>
  <button class="btn btn-primary" data-new data-titulo="Novo produto" data-bs-toggle="modal" data-bs-target="#mProduto"><i class="bi bi-plus-lg"></i> Novo produto</button>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Código</th><th>Nome</th><th>Categoria</th><th class="text-end">Preço</th><th class="text-end">Qtd.</th><th class="text-end">Mínimo</th><th>Situação</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($produtos as $p): ?>
<tr class="<?= $p['status'] === 'inativo' ? 'text-muted' : '' ?>">
  <td><?= e($p['codigo']) ?></td><td><?= e($p['nome']) ?></td><td><?= e($p['categoria']) ?></td>
  <td class="text-end"><?= brl($p['preco']) ?></td><td class="text-end"><?= (int)$p['quantidade'] ?></td><td class="text-end"><?= (int)$p['estoque_minimo'] ?></td>
  <td><?= $p['status'] === 'ativo' ? badge_situacao($p['situacao']) : '—' ?></td>
  <td><?= $p['status'] === 'ativo' ? '<span class="badge text-bg-primary">Ativo</span>' : '<span class="badge text-bg-secondary">Inativo</span>' ?></td>
  <td class="text-end text-nowrap">
    <button class="btn btn-sm btn-outline-primary" data-edit="<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>" data-titulo-edit="Editar produto" data-bs-toggle="modal" data-bs-target="#mProduto" title="Editar"><i class="bi bi-pencil"></i></button>
    <?php if ($p['status'] === 'ativo'): ?><button class="btn btn-sm btn-outline-danger" data-inativar="<?= $p['id_produto'] ?>" title="Inativar"><i class="bi bi-slash-circle"></i></button><?php endif; ?>
  </td></tr>
<?php endforeach; if (!$produtos): ?><tr><td colspan="9" class="text-center text-muted py-4">Nenhum produto encontrado.</td></tr><?php endif; ?>
</tbody></table></div></div>

<div class="modal fade" id="mProduto" tabindex="-1"><div class="modal-dialog modal-lg"><form class="modal-content needs-validation" data-route="produtos" novalidate>
  <div class="modal-header"><h5 class="modal-title">Produto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
    <div class="col-md-4"><label class="form-label">Código</label><input name="codigo" class="form-control" maxlength="30" required></div>
    <div class="col-md-8"><label class="form-label">Nome</label><input name="nome" class="form-control" maxlength="150" required></div>
    <div class="col-md-6"><label class="form-label">Categoria</label><select name="id_categoria" class="form-select" required><option value="">Selecione…</option>
      <?php foreach ($cats as $c): ?><option value="<?= $c['id_categoria'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Preço unitário (R$)</label><input name="preco" type="number" step="0.01" min="0" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Quantidade inicial</label><input name="quantidade" type="number" min="0" value="0" class="form-control" data-only-new>
      <div class="form-text">Após o cadastro, ajuste o saldo por Movimentações.</div></div>
    <div class="col-md-4"><label class="form-label">Estoque mínimo</label><input name="estoque_minimo" type="number" min="0" value="0" class="form-control" required></div>
    <div class="col-md-4 d-none" data-only-edit><label class="form-label">Status</label><select name="status" class="form-select"><option value="ativo">Ativo</option><option value="inativo">Inativo</option></select></div>
  </div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php';
