<?php

if (isset($_GET['delete_despesa'])) {
    $id_del = intval($_GET['delete_despesa']);
    try {
        $stmt = $pdo->prepare("DELETE FROM despesas WHERE id_despesa = ?");
        $stmt->execute([$id_del]);
    } catch (PDOException $e) {
        $erro_despesa = "Erro ao deletar a despesa: " . $e->getMessage();
    }
    
    if (!isset($erro_despesa)) {
        header("Location: administracao.php?tab=despesas");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_despesa'])) {
    $descricao = trim($_POST['descricao']);
    $categoria = trim($_POST['categoria']);
    $valor = floatval($_POST['valor']);
    $data_despesa = $_POST['data_despesa'];
    $id_editando = !empty($_POST['id_despesa']) ? intval($_POST['id_despesa']) : null;

    if ($id_editando) {
        $stmt = $pdo->prepare("UPDATE despesas SET descricao = ?, categoria = ?, valor = ?, data_despesa = ? WHERE id_despesa = ?");
        $stmt->execute([$descricao, $categoria, $valor, $data_despesa, $id_editando]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO despesas (descricao, categoria, valor, data_despesa) VALUES (?, ?, ?, ?)");
        $stmt->execute([$descricao, $categoria, $valor, $data_despesa]);
    }
    
    header("Location: administracao.php?tab=despesas");
    exit;
}

$desp_edit = null;
if (isset($_GET['edit_despesa'])) {
    $id_edit = intval($_GET['edit_despesa']);
    $stmt = $pdo->prepare("SELECT * FROM despesas WHERE id_despesa = ?");
    $stmt->execute([$id_edit]);
    $desp_edit = $stmt->fetch();
}

$despesas = $pdo->query("SELECT * FROM despesas ORDER BY data_despesa DESC, id_despesa DESC")->fetchAll();
?>

<div class="coluna-painel">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        
        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <h3 style="margin-bottom: 1rem;">
                <?= $desp_edit ? 'Editar Despesa' : 'Nova Despesa'; ?>
            </h3>
            
            <?php if (isset($erro_despesa)): ?>
                <div class="error-msg"><?= $erro_despesa; ?></div>
            <?php endif; ?>

            <form action="administracao.php?tab=despesas" method="POST">
                <input type="hidden" name="salvar_despesa" value="1"> 
                <input type="hidden" name="id_despesa" value="<?= $desp_edit['id_despesa'] ?? ''; ?>">
                
                <div class="form-group">
                    <label>Descrição</label>
                    <input type="text" name="descricao" value="<?= htmlspecialchars($desp_edit['descricao'] ?? ''); ?>" placeholder="Ex: Conta de Luz - Maio" required>
                </div>
                
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="categoria" required>
                        <option value="">-- Selecione --</option>
                        <?php 
                            $categorias_opcoes = ["Insumos/Alimentos", "Operacional", "Impostos/Taxas", "Manutenção", "Salários/Benefícios", "Outros"];
                            $cat_atual = $desp_edit['categoria'] ?? '';
                            foreach ($categorias_opcoes as $op) {
                                $selected = ($cat_atual === $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Valor (R$)</label>
                        <input type="number" name="valor" value="<?= $desp_edit['valor'] ?? ''; ?>" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Data</label>
                        <input type="date" name="data_despesa" value="<?= $desp_edit['data_despesa'] ?? date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%; <?= $desp_edit ? 'background-color: #2ecc71;' : 'background-color: #e74c3c;'; ?>">
                    <?= $desp_edit ? 'Atualizar Despesa' : 'Registrar Despesa'; ?>
                </button>

                <?php if ($desp_edit): ?>
                    <a href="administracao.php?tab=despesas" style="display: block; text-align: center; margin-top: 1rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 2;">
            <h3 style="margin-bottom: 1rem;">Histórico de Despesas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($despesas)): ?>
                        <tr><td colspan="5" style="text-align:center;">Nenhuma despesa registrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($despesas as $desp): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($desp['data_despesa'])); ?></td>
                                <td><strong><?= htmlspecialchars($desp['descricao']); ?></strong></td>
                                <td><small style="color: #64748b;"><?= htmlspecialchars($desp['categoria']); ?></small></td>
                                <td style="color: #e74c3c; font-weight: bold;">R$ <?= number_format($desp['valor'], 2, ',', '.'); ?></td>
                                <td>
                                    <a href="administracao.php?tab=despesas&edit_despesa=<?= $desp['id_despesa']; ?>" class="btn-acao" style="color: #f39c12;">Editar</a>
                                    |
                                    <a href="administracao.php?tab=despesas&delete_despesa=<?= $desp['id_despesa']; ?>" 
                                       class="btn-acao" 
                                       style="color: #e74c3c;"
                                       onclick="return confirm('Deseja realmente apagar este registro de despesa?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>