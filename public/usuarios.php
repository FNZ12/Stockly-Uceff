<?php
$titulo = 'Usuários'; $ativo = 'usuarios';
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require __DIR__ . '/../includes/header.php';
$usuarios = Estoque::usuarios(db());
?>
<div class="d-flex justify-content-end mb-3"><button class="btn btn-primary" data-new data-titulo="Novo usuário" data-bs-toggle="modal" data-bs-target="#mUsuario"><i class="bi bi-plus-lg"></i> Novo usuário</button></div>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Criado em</th></tr></thead><tbody>
<?php foreach ($usuarios as $x): ?><tr><td><?= e($x['nome']) ?></td><td><?= e($x['email']) ?></td><td><?= e($x['perfil']) ?></td><td><?= data_br($x['criado_em']) ?></td></tr><?php endforeach; ?>
</tbody></table></div></div>
<div class="modal fade" id="mUsuario" tabindex="-1"><div class="modal-dialog"><form class="modal-content needs-validation" data-route="usuarios" novalidate>
  <div class="modal-header"><h5 class="modal-title">Usuário</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id">
    <div class="mb-3"><label class="form-label">Nome</label><input name="nome" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">E-mail</label><input name="email" type="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Senha (mín. 6)</label><input name="senha" type="password" minlength="6" class="form-control" required></div>
    <div><label class="form-label">Perfil</label><select name="perfil" class="form-select"><option value="operador">Operador</option><option value="admin">Administrador</option></select></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Salvar</button></div>
</form></div></div>
<?php require __DIR__ . '/../includes/footer.php';
