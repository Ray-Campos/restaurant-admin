<?php
require_once '../config/config.php';
include '../includes/header.php';

try {
    $total_clientes = $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn();
    $clientes_ativos = $pdo->query("SELECT COUNT(DISTINCT id_cliente) FROM pedidos WHERE status = 'aberto' AND id_cliente IS NOT NULL")->fetchColumn();
} catch (PDOException $e) {
    die("Erro ao carregar indicadores: " . $e->getMessage());
}

if (isset($_GET['delete_cliente'])) {
    $id_del = intval($_GET['delete_cliente']);
    try {
        $stmt = $pdo->prepare("DELETE FROM clientes WHERE id_cliente = ?");
        $stmt->execute([$id_del]);
    } catch (PDOException $e) {
        $erro_msg = "Erro ao deletar: Este cliente possui pedidos registrados no sistema.";
    }
    
    if (!isset($erro_msg)) {
        header("Location: clientes.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_cliente'])) {
    $nome = trim($_POST['nome']);
    $cpf = trim($_POST['cpf']);
    $telefone = trim($_POST['telefone']);
    $email = trim($_POST['email']);
    $id_editando = !empty($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null;

    if ($id_editando) {
        $stmt = $pdo->prepare("UPDATE clientes SET nome = ?, cpf = ?, telefone = ?, email = ? WHERE id_cliente = ?");
        $stmt->execute([$nome, $cpf, $telefone, $email, $id_editando]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, cpf, telefone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf, $telefone, $email]);
    }
    
    header("Location: clientes.php");
    exit;
}

$cliente_edit = null;
if (isset($_GET['edit_cliente'])) {
    $id_edit = intval($_GET['edit_cliente']);
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
    $stmt->execute([$id_edit]);
    $cliente_edit = $stmt->fetch();
}

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll();
?>

<h2>Controle de Clientes</h2>
<p style="color: #666; margin-bottom: 1.5rem;">Gerencie a base de clientes cadastrados no restaurante.</p>

<div class="kpi-container">
    <div class="kpi-card">
        <h4>Total de Clientes</h4>
        <p><?= $total_clientes; ?></p>
    </div>
    <div class="kpi-card green">
        <h4>Clientes com Pedido Aberto</h4>
        <p><?= $clientes_ativos; ?></p>
    </div>
</div>

<div class="coluna-painel">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        
        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <h3 style="margin-bottom: 1rem;">
                <?= $cliente_edit ? 'Editar Cliente' : 'Novo Cliente'; ?>
            </h3>
            
            <?php if (isset($erro_msg)): ?>
                <div class="error-msg"><?= $erro_msg; ?></div>
            <?php endif; ?>

            <form action="clientes.php" method="POST">
                <input type="hidden" name="salvar_cliente" value="1"> 
                <input type="hidden" name="id_cliente" value="<?= $cliente_edit['id_cliente'] ?? ''; ?>">
                
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($cliente_edit['nome'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>CPF</label>
                    <input type="text" name="cpf" value="<?= htmlspecialchars($cliente_edit['cpf'] ?? ''); ?>" placeholder="000.000.000-00">
                </div>
                
                <div class="form-group">
                    <label>Telefone</label>
                    <input type="text" name="telefone" value="<?= htmlspecialchars($cliente_edit['telefone'] ?? ''); ?>" placeholder="(00) 00000-0000">
                </div>

                <div class="form-group">
                    <label>E-mail</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($cliente_edit['email'] ?? ''); ?>">
                </div>

                <button type="submit" class="btn" style="width: 100%; <?= $cliente_edit ? 'background-color: #2ecc71;' : ''; ?>">
                    <?= $cliente_edit ? 'Atualizar Dados' : 'Cadastrar Cliente'; ?>
                </button>

                <?php if ($cliente_edit): ?>
                    <a href="clientes.php" style="display: block; text-align: center; margin-top: 1rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 2;">
            <h3 style="margin-bottom: 1rem;">Clientes Cadastrados</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Documento (CPF)</th>
                        <th>Contato</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="5" style="text-align:center; color: #64748b;">Nenhum cliente cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cli): ?>
                            <tr>
                                <td><?= $cli['id_cliente']; ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($cli['nome']); ?></strong><br>
                                    <small style="color: #94a3b8;">Cadastrado em: <?= date('d/m/Y', strtotime($cli['data_cadastro'])); ?></small>
                                </td>
                                <td><?= htmlspecialchars($cli['cpf'] ?? 'Não informado'); ?></td>
                                <td>
                                    <?= htmlspecialchars($cli['telefone']); ?><br>
                                    <small><?= htmlspecialchars($cli['email']); ?></small>
                                </td>
                                <td>
                                    <a href="clientes.php?edit_cliente=<?= $cli['id_cliente']; ?>" class="btn-acao" style="color: #f39c12;">Editar</a>
                                    |
                                    <a href="clientes.php?delete_cliente=<?= $cli['id_cliente']; ?>" 
                                       class="btn-acao" 
                                       style="color: #e74c3c;"
                                       onclick="return confirm('Deseja realmente apagar este cliente?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>