<?php

require_once 'config/config.php';

if (isset($_SESSION['usuario'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha   = trim($_POST['senha'] ?? '');

    // Credenciais hardcoded 
    if ($usuario === 'admin' && $senha === 'admin123') {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['perfil'] = 'admin'; // Define o perfil como administrador
        header("Location: dashboard.php");
        exit;
    } elseif ($usuario === 'caixa' && $senha === 'caixa123') {
        $_SESSION['usuario'] = 'Operador de Caixa';
        $_SESSION['perfil'] = 'caixa'; // Define o perfil como caixa
        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Usuário ou senha incorretos!";
    }
}

include 'includes/header-main.php';
?>

<div class="login-container">
    <h2>Acesso ao Sistema</h2>
    <p style="color: #666; font-size: 0.9rem; margin-bottom: 1.5rem;">Utilize as credenciais de Administrador ou Caixa</p>
    
    <?php if (!empty($erro)): ?>
        <div class="error-msg"><?= $erro; ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        <div class="form-group">
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario" required autocomplete="off">
        </div>
        <div class="form-group">
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn" style="width: 100%;">Entrar</button>
    </form>
</div>

<?php include 'includes/footer.php'; ?>