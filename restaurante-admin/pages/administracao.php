<?php
require_once '../config/config.php';
include '../includes/header.php';

try {
    $total_funcionarios = $pdo->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
    $folha_pagamento = $pdo->query("SELECT SUM(salario) FROM funcionarios")->fetchColumn() ?: 0;
    $total_despesas = $pdo->query("SELECT SUM(valor) FROM despesas")->fetchColumn() ?: 0;
    $total_produtos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
} catch (PDOException $e) {
    die("Erro ao carregar KPIs: " . $e->getMessage());
}

$aba_ativa = $_GET['tab'] ?? 'produtos';
$abas_permitidas = ['produtos', 'funcionarios', 'despesas'];

if (!in_array($aba_ativa, $abas_permitidas)) {
    $aba_ativa = 'produtos';
}
?>

<h2>Administração Interna</h2>
<p style="color: #666; margin-bottom: 1.5rem;">Gerencie o estoque, a equipe e as despesas do restaurante.</p>

<div class="kpi-container" style="margin-bottom: 1.5rem;">
    <div class="kpi-card">
        <h4>Equipe Ativa</h4>
        <p><?= $total_funcionarios; ?></p>
    </div>
    <div class="kpi-card orange">
        <h4>Folha de Pagamento</h4>
        <p>R$ <?= number_format($folha_pagamento, 2, ',', '.'); ?></p>
    </div>
    <div class="kpi-card" style="border-left-color: #e74c3c;">
        <h4>Total Despesas</h4>
        <p>R$ <?= number_format($total_despesas, 2, ',', '.'); ?></p>
    </div>
    <div class="kpi-card green">
        <h4>Produtos Cadastrados</h4>
        <p><?= $total_produtos; ?></p>
    </div>
</div>

<div class="tabs-container">
    <a href="administracao.php?tab=produtos" class="tab-link <?= $aba_ativa === 'produtos' ? 'active' : '' ?>">Produtos & Estoque</a>
    <a href="administracao.php?tab=funcionarios" class="tab-link <?= $aba_ativa === 'funcionarios' ? 'active' : '' ?>">Funcionários</a>
    <a href="administracao.php?tab=despesas" class="tab-link <?= $aba_ativa === 'despesas' ? 'active' : '' ?>">Despesas</a>
</div>

<div class="aba-conteudo">
    <?php
        include $aba_ativa . '.php';
    ?>
</div>

<?php include '../includes/footer.php'; ?>