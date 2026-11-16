<?php
$titulo = 'Dashboard'; $ativo = 'dashboard';
require __DIR__ . '/../includes/header.php';
$d = Estoque::dashboard(db());
$c = $d['cards'];
$cards = [
  ['Produtos cadastrados', $c['produtos'], 'bi-box-seam', 'primary'],
  ['Categorias', $c['categorias'], 'bi-tags', 'info'],
  ['Itens em estoque', $c['itens'], 'bi-stack', 'success'],
  ['Estoque baixo', $c['estoque_baixo'], 'bi-exclamation-triangle', 'warning'],
  ['Movimentações', $c['movimentacoes'], 'bi-arrow-left-right', 'secondary'],
];
?>
<div class="row g-3 mb-3">
<?php foreach ($cards as [$t, $n, $i, $cor]): ?>
  <div class="col-6 col-xl"><div class="card stat">
    <div class="ico bg-<?= $cor ?>-subtle text-<?= $cor ?>-emphasis"><i class="bi <?= $i ?>"></i></div>
    <div><div class="num"><?= (int)$n ?></div><small class="text-muted"><?= $t ?></small></div>
  </div></div>
<?php endforeach; ?>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-8"><div class="card p-3 h-100"><h6 class="mb-3">Entradas e saídas (últimos 7 dias)</h6>
    <canvas id="grafico" height="110"></canvas></div></div>
  <div class="col-lg-4"><div class="card p-3 h-100"><h6 class="mb-3">Alertas de estoque mínimo</h6>
    <?php if (!$d['alertas']): ?><p class="text-muted mb-0">Nenhum produto abaixo do mínimo. 🎉</p><?php endif; ?>
    <?php foreach ($d['alertas'] as $a): ?>
      <div class="d-flex justify-content-between align-items-center border-bottom py-2">
        <div><div class="fw-semibold"><?= e($a['nome']) ?></div><small class="text-muted"><?= e($a['codigo']) ?> · <?= (int)$a['quantidade'] ?>/<?= (int)$a['estoque_minimo'] ?></small></div>
        <?= badge_situacao($a['situacao']) ?></div>
    <?php endforeach; ?>
    <a href="estoque-baixo.php" class="btn btn-sm btn-outline-primary mt-3">Ver todos</a></div></div>
</div>
<div class="row g-3">
  <div class="col-lg-5"><div class="card p-3 h-100"><h6>Menor quantidade em estoque</h6>
    <table class="table table-sm align-middle mb-0"><thead><tr><th>Produto</th><th class="text-end">Qtd.</th><th>Situação</th></tr></thead><tbody>
    <?php foreach ($d['menor_estoque'] as $p): ?>
      <tr><td><?= e($p['nome']) ?><br><small class="text-muted"><?= e($p['codigo']) ?></small></td><td class="text-end"><?= (int)$p['quantidade'] ?></td><td><?= badge_situacao($p['situacao']) ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div>
  <div class="col-lg-7"><div class="card p-3 h-100"><h6>Últimas movimentações</h6>
    <div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Data</th><th>Produto</th><th>Tipo</th><th class="text-end">Qtd.</th></tr></thead><tbody>
    <?php foreach ($d['ultimas'] as $m): ?>
      <tr><td><?= data_br($m['data_hora']) ?></td><td><?= e($m['produto']) ?></td>
      <td class="<?= $m['tipo'] ?>"><?= $m['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></td><td class="text-end"><?= (int)$m['quantidade'] ?></td></tr>
    <?php endforeach; ?></tbody></table></div></div></div>
</div>
<?php
$g = json_encode($d['grafico'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
$extraJs = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>const g=' . $g . ';new Chart(document.getElementById("grafico"),{type:"bar",data:{labels:g.labels,datasets:[
{label:"Entradas",data:g.entradas,backgroundColor:"#198754"},{label:"Saídas",data:g.saidas,backgroundColor:"#dc3545"}]},
options:{responsive:true,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});</script>';
require __DIR__ . '/../includes/footer.php';
