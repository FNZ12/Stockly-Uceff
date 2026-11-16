<?php
require __DIR__ . '/../includes/bootstrap.php';
header('Location: ' . (usuario() ? 'dashboard.php' : 'login.php'));
