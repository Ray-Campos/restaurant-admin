<?php
require_once 'config/config.php';
include 'includes/header-main.php';
?>

<div style="background: #fff; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
    <h1>Painel de Controle</h1>
    <p style="margin-top: 0.5rem; color: #666;">
        Bem-vindo, <strong><?= htmlspecialchars($_SESSION['usuario']); ?></strong>. O sistema está conectado com sucesso ao banco MariaDB.
    </p>
    
    <div style="margin-top: 2.5rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
        
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 6px;">
            <h3 style="color: #2c3e50;">Operações de Caixa</h3>
            <p style="font-size: 0.9rem; color: #64748b; margin: 0.5rem 0 1rem 0;">Gerencie as comandas, aberturas de pedidos e o status das mesas do restaurante.</p>
            <a href="pages/caixa.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Abrir Módulo &rarr;</a>
        </div>
        
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 6px;">
            <h3 style="color: #2c3e50;">Controle de Clientes</h3>
            <p style="font-size: 0.9rem; color: #64748b; margin: 0.5rem 0 1rem 0;">Atualize a listagem e os dados de contato dos clientes cadastrados no sistema.</p>
            <a href="pages/clientes.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Ver Cadastros &rarr;</a>
        </div>

        <?php if (isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin'): ?>
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 6px;">
                <h3 style="color: #2c3e50;">Administração Interna</h3>
                <p style="font-size: 0.9rem; color: #64748b; margin: 0.5rem 0 1rem 0;">Controle o estoque de produtos, a equipe de funcionários e as despesas do estabelecimento.</p>
                <a href="pages/administracao.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Gerenciar Backoffice &rarr;</a>
            </div>
            
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 1.5rem; border-radius: 6px;">
                <h3 style="color: #2c3e50;">Módulo de Análise</h3>
                <p style="font-size: 0.9rem; color: #64748b; margin: 0.5rem 0 1rem 0;">Acesse relatórios gerenciais estruturados a partir dos dados consolidados das tabelas.</p>
                <a href="pages/relatorios.php" style="color: #3498db; text-decoration: none; font-weight: 600;">Ver Relatórios &rarr;</a>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include 'includes/footer.php'; ?>