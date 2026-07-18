<?php
require_once '../config/config.php';
include '../includes/header.php';

try {
    $pedidos_ativos = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE status = 'aberto'")->fetchColumn();
    $mesas_livres = $pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'livre'")->fetchColumn();
    $mesas_ocupadas = $pdo->query("SELECT COUNT(*) FROM mesas WHERE status = 'ocupada'")->fetchColumn();
} catch (PDOException $e) {
    die("Erro ao carregar KPIs: " . $e->getMessage());
}

$aba_ativa = $_GET['tab'] ?? 'pedidos';
$abas_permitidas = ['pedidos', 'mesas'];

if (!in_array($aba_ativa, $abas_permitidas)) {
    $aba_ativa = 'pedidos';
}
?>

<h2>Caixa e Atendimento</h2>
<p style="color: #666; margin-bottom: 1.5rem;">Gerencie as comandas, status das mesas e o fluxo de caixa.</p>

<div class="kpi-container" style="margin-bottom: 1.5rem;">
    <div class="kpi-card">
        <h4>Pedidos Ativos</h4>
        <p><?= $pedidos_ativos; ?></p>
    </div>
    <div class="kpi-card green">
        <h4>Mesas Livres</h4>
        <p><?= $mesas_livres; ?></p>
    </div>
    <div class="kpi-card orange">
        <h4>Mesas Ocupadas</h4>
        <p><?= $mesas_ocupadas; ?></p>
    </div>
</div>

<div class="tabs-container">
    <a href="caixa.php?tab=pedidos" class="tab-link <?= $aba_ativa === 'pedidos' ? 'active' : '' ?>">Controle de Pedidos</a>
    <a href="caixa.php?tab=mesas" class="tab-link <?= $aba_ativa === 'mesas' ? 'active' : '' ?>">Gerenciamento de Mesas</a>
</div>

<div class="aba-conteudo">
    <?php include $aba_ativa . '.php'; ?>
</div>

<?php include '../includes/footer.php'; ?>