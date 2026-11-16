<?php
require_once __DIR__ . '/bootstrap.php';
require_login();
$u = usuario();
$ativo = $ativo ?? '';
$titulo = $titulo ?? 'Stockly';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<title><?= e($titulo) ?> · Stockly</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/css/stockly.css" rel="stylesheet">
</head>
<body>
<div class="app">
<?php include __DIR__ . '/sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <button class="btn btn-light d-lg-none" id="menuBtn" aria-label="Menu"><i class="bi bi-list"></i></button>
    <h1 class="page-title"><?= e($titulo) ?></h1>
    <div class="ms-auto d-flex align-items-center gap-2">
      <div class="text-end lh-sm d-none d-sm-block">
        <div class="fw-semibold"><?= e($u['nome']) ?></div>
        <small class="text-muted"><?= $u['perfil'] === 'admin' ? 'Administrador' : 'Operador' ?></small>
      </div>
      <div class="avatar"><?= e(mb_strtoupper(mb_substr($u['nome'], 0, 1))) ?></div>
    </div>
  </header>
  <main class="content">
