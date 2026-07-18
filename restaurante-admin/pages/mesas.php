<?php

if (isset($_GET['delete_mesa'])) {
    $id_del = intval($_GET['delete_mesa']);
    
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM pedidos WHERE id_mesa = ? AND status = 'aberto'");
    $stmt_check->execute([$id_del]);
    $tem_pedido = $stmt_check->fetchColumn();

    if ($tem_pedido > 0) {
        $erro_mesa = "Não é possível excluir: Esta mesa possui pedidos abertos.";
    } else {
        try {
            $pdo->prepare("DELETE FROM mesas WHERE id_mesa = ?")->execute([$id_del]);
        } catch (PDOException $e) {
            $erro_mesa = "Erro ao deletar: Mesa vinculada a pedidos históricos.";
        }
    }
    
    if (!isset($erro_mesa)) {
        header("Location: caixa.php?tab=mesas");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_mesa'])) {
    $numero = intval($_POST['numero']);
    $capacidade = intval($_POST['capacidade']);
    $status = $_POST['status'];
    $id_editando = !empty($_POST['id_mesa']) ? intval($_POST['id_mesa']) : null;

    try {
        if ($id_editando) {
            $stmt = $pdo->prepare("UPDATE mesas SET numero = ?, capacidade = ?, status = ? WHERE id_mesa = ?");
            $stmt->execute([$numero, $capacidade, $status, $id_editando]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO mesas (numero, capacidade, status) VALUES (?, ?, ?)");
            $stmt->execute([$numero, $capacidade, $status]);
        }
        header("Location: caixa.php?tab=mesas");
        exit;
    } catch (PDOException $e) {
        $erro_mesa = "Erro ao salvar: O número da mesa já pode estar em uso.";
    }
}

$mesa_edit = null;
if (isset($_GET['edit_mesa'])) {
    $id_edit = intval($_GET['edit_mesa']);
    $stmt = $pdo->prepare("SELECT * FROM mesas WHERE id_mesa = ?");
    $stmt->execute([$id_edit]);
    $mesa_edit = $stmt->fetch();
}

$mesas = $pdo->query("SELECT * FROM mesas ORDER BY numero ASC")->fetchAll();
?>

<div class="coluna-painel">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        
        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <h3 style="margin-bottom: 1rem;"><?= $mesa_edit ? 'Editar Mesa' : 'Nova Mesa'; ?></h3>
            
            <?php if (isset($erro_mesa)): ?>
                <div class="error-msg"><?= $erro_mesa; ?></div>
            <?php endif; ?>

            <form action="caixa.php?tab=mesas" method="POST">
                <input type="hidden" name="salvar_mesa" value="1"> 
                <input type="hidden" name="id_mesa" value="<?= $mesa_edit['id_mesa'] ?? ''; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Número da Mesa</label>
                        <input type="number" name="numero" value="<?= $mesa_edit['numero'] ?? ''; ?>" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Capacidade (Pessoas)</label>
                        <input type="number" name="capacidade" value="<?= $mesa_edit['capacidade'] ?? '4'; ?>" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <?php 
                            $status_opcoes = ['livre', 'ocupada', 'reservada'];
                            $status_atual = $mesa_edit['status'] ?? 'livre';
                            foreach ($status_opcoes as $op) {
                                $selected = ($status_atual === $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>" . ucfirst($op) . "</option>";
                            }
                        ?>
                    </select>
                </div>

                <button type="submit" class="btn" style="width: 100%; <?= $mesa_edit ? 'background-color: #2ecc71;' : ''; ?>">
                    <?= $mesa_edit ? 'Atualizar Mesa' : 'Cadastrar Mesa'; ?>
                </button>

                <?php if ($mesa_edit): ?>
                    <a href="caixa.php?tab=mesas" style="display: block; text-align: center; margin-top: 1rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 2;">
            <h3 style="margin-bottom: 1rem;">Mesas Cadastradas</h3>
            <table>
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Capacidade</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($mesas)): ?>
                        <tr><td colspan="4" style="text-align:center;">Nenhuma mesa cadastrada.</td></tr>
                    <?php else: ?>
                        <?php foreach ($mesas as $m): ?>
                            <tr>
                                <td><strong>Mesa <?= htmlspecialchars($m['numero']); ?></strong></td>
                                <td><?= $m['capacidade']; ?> lugares</td>
                                <td>
                                    <?php
                                        $cor = $m['status'] == 'livre' ? 'green' : ($m['status'] == 'ocupada' ? 'red' : 'orange');
                                    ?>
                                    <span style="color: <?= $cor ?>; font-weight: bold;"><?= ucfirst($m['status']); ?></span>
                                </td>
                                <td>
                                    <?php if ($m['status'] == 'ocupada'): ?>
                                        <span style="color: #e67e22;">Ocupada (Bloqueado)</span>
                                    <?php else: ?>
                                        <a href="caixa.php?tab=mesas&edit_mesa=<?= $m['id_mesa']; ?>" class="btn-acao" style="color: #f39c12;">Editar</a> |
                                        <a href="caixa.php?tab=mesas&delete_mesa=<?= $m['id_mesa']; ?>" class="btn-acao" style="color: #e74c3c;" onclick="return confirm('Deseja deletar?');">Excluir</a>
                                    <?php endif; ?> 
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>