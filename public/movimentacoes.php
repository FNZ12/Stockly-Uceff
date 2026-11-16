<?php
$titulo = 'Movimentações'; $ativo = 'movimentacoes';
require __DIR__ . '/../includes/header.php';
$pdo = db();
$f = ['produto' => $_GET['produto'] ?? '', 'tipo' => $_GET['tipo'] ?? '', 'de' => $_GET['de'] ?? '', 'ate' => $_GET['ate'] ?? '',
      'q' => trim($_GET['q'] ?? ''), 'ordem' => $_GET['ordem'] ?? 'desc'];
$movs = Estoque::movimentacoes($pdo, $f);
$todos = Estoque::produtos($pdo);
$ativos = array_filter($todos, fn($p) => $p['status'] === 'ativo');
?>
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
  <form class="row g-2 flex-grow-1" method="get">
    <div class="col-md-3"><select name="produto" class="form-select"><option value="">Todos os produtos</option>
      <?php foreach ($todos as $p): ?><option value="<?= $p['id_produto'] ?>" <?= $f['produto'] == $p['id_produto'] ? 'selected' : '' ?>><?= e($p['codigo'] . ' · ' . $p['nome']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-2"><select name="tipo" class="form-select"><option value="">Entradas e saídas</option>
      <option value="entrada" <?= $f['tipo'] === 'entrada' ? 'selected' : '' ?>>Entradas</option><option value="saida" <?= $f['tipo'] === 'saida' ? 'selected' : '' ?>>Saídas</option></select></div>
    <div class="col-md-2"><input type="date" name="de" class="form-control" value="<?= e($f['de']) ?>" title="De"></div>
    <div class="col-md-2"><input type="date" name="ate" class="form-control" value="<?= e($f['ate']) ?>" title="Até"></div>
    <div class="col-md-3"><input name="q" class="form-control" placeholder="Pesquisar…" value="<?= e($f['q']) ?>"></div>
    <div class="col-md-2"><select name="ordem" class="form-select"><option value="desc">Mais recentes</option><option value="asc" <?= $f['ordem'] === 'asc' ? 'selected' : '' ?>>Mais antigas</option></select></div>
    <div class="col-auto"><button class="btn btn-outline-primary"><i class="bi bi-funnel"></i> Filtrar</button></div>
  </form>
  <div class="d-flex gap-2">
    <button class="btn btn-success" data-new data-tipo="entrada" data-titulo="Registrar entrada" data-bs-toggle="modal" data-bs-target="#mMov"><i class="bi bi-box-arrow-in-down"></i> Entrada</button>
    <button class="btn btn-danger" data-new data-tipo="saida" data-titulo="Registrar saída" data-bs-toggle="modal" data-bs-target="#mMov"><i class="bi bi-box-arrow-up"></i> Saída</button>
  </div>
</div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
<thead><tr><th>Data/hora</th><th>Código</th><th>Produto</th><th>Tipo</th><th class="text-end">Qtd.</th><th>Usuário</th><th>Observação</th></tr></thead><tbody>
<?php foreach ($movs as $m): ?><tr><td><?= data_br($m['data_hora']) ?></td><td><?= e($m['codigo']) ?></td><td><?= e($m['produto']) ?></td>
  <td class="<?= $m['tipo'] ?>"><?= $m['tipo'] === 'entrada' ? '▲ Entrada' : '▼ Saída' ?></td><td class="text-end"><?= (int)$m['quantidade'] ?></td><td><?= e($m['usuario']) ?></td><td><?= e($m['observacao']) ?></td></tr>
<?php endforeach; if (!$movs): ?><tr><td colspan="7" class="text-center text-muted py-4">Nenhuma movimentação encontrada.</td></tr><?php endif; ?>
</tbody></table></div></div>
<div class="modal fade" id="mMov" tabindex="-1"><div class="modal-dialog"><form class="modal-content needs-validation" data-route="movimentacoes" novalidate>
  <div class="modal-header"><h5 class="modal-title">Movimentação</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><input type="hidden" name="tipo" value="entrada">
    <div class="mb-3"><label class="form-label">Produto</label><select name="id_produto" class="form-select" required><option value="">Selecione…</option>
      <?php foreach ($ativos as $p): ?><option value="<?= $p['id_produto'] ?>"><?= e($p['codigo'] . ' · ' . $p['nome'] . ' (saldo: ' . $p['quantidade'] . ')') ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label">Quantidade</label><input name="quantidade" type="number" min="1" step="1" class="form-control" required></div>
    <div><label class="form-label">Observação (opcional)</label><input name="observacao" maxlength="255" class="form-control"></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Confirmar</button></div>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php';
