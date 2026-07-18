<?php

if (isset($_GET['delete_func'])) {
    $id_del = intval($_GET['delete_func']);
    try {
        $stmt = $pdo->prepare("DELETE FROM funcionarios WHERE id_funcionario = ?");
        $stmt->execute([$id_del]);
    } catch (PDOException $e) {
        $erro_func = "Erro ao deletar: Este funcionário pode estar vinculado a pedidos existentes.";
    }
    
    if (!isset($erro_func)) {
        header("Location: administracao.php?tab=funcionarios");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_func'])) {
    $nome = trim($_POST['nome']);
    $cargo = trim($_POST['cargo']);
    $salario = floatval($_POST['salario']);
    $data_contratacao = $_POST['data_contratacao'];
    $id_editando = !empty($_POST['id_funcionario']) ? intval($_POST['id_funcionario']) : null;

    if ($id_editando) {
        $stmt = $pdo->prepare("UPDATE funcionarios SET nome = ?, cargo = ?, salario = ?, data_contratacao = ? WHERE id_funcionario = ?");
        $stmt->execute([$nome, $cargo, $salario, $data_contratacao, $id_editando]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO funcionarios (nome, cargo, salario, data_contratacao) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $cargo, $salario, $data_contratacao]);
    }
    
    header("Location: administracao.php?tab=funcionarios");
    exit;
}

$func_edit = null;
if (isset($_GET['edit_func'])) {
    $id_edit = intval($_GET['edit_func']);
    $stmt = $pdo->prepare("SELECT * FROM funcionarios WHERE id_funcionario = ?");
    $stmt->execute([$id_edit]);
    $func_edit = $stmt->fetch();
}

$funcionarios = $pdo->query("SELECT * FROM funcionarios ORDER BY nome ASC")->fetchAll();
?>

<div class="coluna-painel">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        
        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <h3 style="margin-bottom: 1rem;">
                <?= $func_edit ? 'Editar Funcionário' : 'Novo Funcionário'; ?>
            </h3>
            
            <?php if (isset($erro_func)): ?>
                <div class="error-msg"><?= $erro_func; ?></div>
            <?php endif; ?>

            <form action="administracao.php?tab=funcionarios" method="POST">
                <input type="hidden" name="salvar_func" value="1"> 
                <input type="hidden" name="id_funcionario" value="<?= $func_edit['id_funcionario'] ?? ''; ?>">
                
                <div class="form-group">
                    <label>Nome Completo</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($func_edit['nome'] ?? ''); ?>" placeholder="Ex: João da Silva" required>
                </div>
                
                <div class="form-group">
                    <label>Cargo</label>
                    <select name="cargo" required>
                        <option value="">-- Selecione --</option>
                        <?php 
                            $cargos_opcoes = ["Garçom", "Cozinheiro", "Auxiliar de Cozinha", "Gerente", "Caixa", "Faxineiro"];
                            $cargo_atual = $func_edit['cargo'] ?? '';
                            foreach ($cargos_opcoes as $op) {
                                $selected = ($cargo_atual === $op) ? 'selected' : '';
                                echo "<option value=\"$op\" $selected>$op</option>";
                            }
                        ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Salário (R$)</label>
                        <input type="number" name="salario" value="<?= $func_edit['salario'] ?? ''; ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Data de Contratação</label>
                        <input type="date" name="data_contratacao" value="<?= $func_edit['data_contratacao'] ?? date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn" style="width: 100%; <?= $func_edit ? 'background-color: #2ecc71;' : ''; ?>">
                    <?= $func_edit ? 'Atualizar Funcionário' : 'Cadastrar Funcionário'; ?>
                </button>

                <?php if ($func_edit): ?>
                    <a href="administracao.php?tab=funcionarios" style="display: block; text-align: center; margin-top: 1rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 2;">
            <h3 style="margin-bottom: 1rem;">Equipe Atual</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Cargo</th>
                        <th>Salário</th>
                        <th>Admissão</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($funcionarios)): ?>
                        <tr><td colspan="6" style="text-align:center; color: #64748b;">Nenhum funcionário cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($funcionarios as $func): ?>
                            <tr>
                                <td><?= $func['id_funcionario']; ?></td>
                                <td><strong><?= htmlspecialchars($func['nome']); ?></strong></td>
                                <td><?= htmlspecialchars($func['cargo']); ?></td>
                                <td>R$ <?= number_format($func['salario'], 2, ',', '.'); ?></td>
                                <td><?= date('d/m/Y', strtotime($func['data_contratacao'])); ?></td>
                                <td>
                                    <a href="administracao.php?tab=funcionarios&edit_func=<?= $func['id_funcionario']; ?>" class="btn-acao" style="color: #f39c12;">Editar</a>
                                    |
                                    <a href="administracao.php?tab=funcionarios&delete_func=<?= $func['id_funcionario']; ?>" 
                                       class="btn-acao" 
                                       style="color: #e74c3c;"
                                       onclick="return confirm('Deseja realmente desligar/excluir este funcionário?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>