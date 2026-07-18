<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pagina_atual = basename($_SERVER['PHP_SELF']);

if ($pagina_atual !== 'index.php' && !isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Restaurante</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<?php if (isset($_SESSION['usuario'])): ?>
<nav class="navbar">
    <div class="nav-container">
        <a href="../dashboard.php" class="brand">Restaurante Las Cubanas</a>
        <ul class="nav-links">
            <li><a href="../dashboard.php">Início</a></li>
            <li><a href="caixa.php">Caixa</a></li>
            <li><a href="clientes.php">Clientes</a></li>
            
            <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
                <li><a href="administracao.php">Administração</a></li>
                <li><a href="relatorios.php">Relatórios</a></li>
            <?php endif; ?>
            
            <li><a href="../logout.php" class="btn-logout">Sair</a></li>
        </ul>
    </div>
</nav>
<?php endif; ?>

<main class="content">