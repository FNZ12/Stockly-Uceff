<?php
require __DIR__ . '/../includes/bootstrap.php';
if (usuario()) { header('Location: dashboard.php'); exit; }
?><!DOCTYPE html>
<html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title>Entrar · Stockly</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/stockly.css" rel="stylesheet">
</head><body>
<div class="login-wrap">
  <div class="card login-card">
    <div class="text-center mb-4"><div class="fs-2 fw-bold text-primary"><i class="bi bi-boxes"></i> Stockly</div>
      <div class="text-muted">Sistema de Controle de Estoque</div></div>
    <form data-route="auth/login" data-redirect="dashboard.php" novalidate class="needs-validation">
      <div class="mb-3"><label class="form-label">E-mail</label>
        <input type="email" name="email" class="form-control" required autofocus>
        <div class="invalid-feedback">Informe um e-mail válido.</div></div>
      <div class="mb-4"><label class="form-label">Senha</label>
        <input type="password" name="senha" class="form-control" required>
        <div class="invalid-feedback">Informe a senha.</div></div>
      <button class="btn btn-primary w-100" type="submit">Entrar</button>
    </form>
  </div>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3" id="toasts"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body></html>
