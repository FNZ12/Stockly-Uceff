<?php
$itens = [
  ['dashboard', 'dashboard.php', 'bi-speedometer2', 'Dashboard'],
  ['produtos', 'produtos.php', 'bi-box-seam', 'Produtos'],
  ['categorias', 'categorias.php', 'bi-tags', 'Categorias'],
  ['movimentacoes', 'movimentacoes.php', 'bi-arrow-left-right', 'Movimentações'],
  ['estoque-baixo', 'estoque-baixo.php', 'bi-exclamation-triangle', 'Estoque baixo'],
  ['relatorios', 'relatorios.php', 'bi-file-earmark-bar-graph', 'Relatórios'],
];
if (($u['perfil'] ?? '') === 'admin') $itens[] = ['usuarios', 'usuarios.php', 'bi-people', 'Usuários'];
?>
<aside class="sidebar" id="sidebar">
  <div class="brand"><i class="bi bi-boxes"></i> Stockly</div>
  <nav>
    <?php foreach ($itens as [$k, $href, $ico, $txt]): ?>
      <a href="<?= $href ?>" class="<?= $ativo === $k ? 'active' : '' ?>"><i class="bi <?= $ico ?>"></i><?= $txt ?></a>
    <?php endforeach; ?>
  </nav>
  <a href="logout.php" class="logout"><i class="bi bi-box-arrow-left"></i>Sair</a>
</aside>
