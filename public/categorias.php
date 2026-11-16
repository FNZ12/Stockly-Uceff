<?php
$titulo = 'Categorias'; $ativo = 'categorias';
require __DIR__ . '/../includes/header.php';
$cats = Estoque::categorias(db());
?>
<div class="d-flex justify-content-end mb-3">
  <button class="btn btn-primary" data-new data-titulo="Nova categoria" data-bs-toggle="modal" data-bs-target="#mCategoria"><i class="bi bi-plus-lg"></i> Nova categoria</button>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Nome</th><th>Descrição</th><th class="text-end">Produtos</th></tr></thead><tbody>
<?php foreach ($cats as $c): ?><tr><td class="fw-semibold"><?= e($c['nome']) ?></td><td><?= e($c['descricao']) ?></td><td class="text-end"><?= (int)$c['total_produtos'] ?></td></tr>
<?php endforeach; if (!$cats): ?><tr><td colspan="3" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="modal fade" id="mCategoria" tabindex="-1"><div class="modal-dialog"><form class="modal-content needs-validation" data-route="categorias" novalidate>
  <div class="modal-header"><h5 class="modal-title">Categoria</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id">
    <div class="mb-3"><label class="form-label">Nome</label><input name="nome" class="form-control" maxlength="80" required></div>
    <div><label class="form-label">Descrição</label><textarea name="descricao" class="form-control" maxlength="255" rows="3"></textarea></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php';
