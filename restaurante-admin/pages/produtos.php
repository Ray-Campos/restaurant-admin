<?php

if (isset($_GET['delete_produto'])) {
    $id_del = intval($_GET['delete_produto']);
    try {
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id_produto = ?");
        $stmt->execute([$id_del]);
    } catch (PDOException $e) {
        $erro_produto = "Erro ao deletar: O produto pode estar vinculado a um pedido existente.";
    }
    
    if (!isset($erro_produto)) {
        header("Location: administracao.php?tab=produtos");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_produto'])) {
    $nome = trim($_POST['nome']);
    $descricao = trim($_POST['descricao']);
    $preco = floatval($_POST['preco']);
    $estoque = intval($_POST['estoque']);
    $id_categoria = !empty($_POST['id_categoria']) ? intval($_POST['id_categoria']) : null;
    $id_editando = !empty($_POST['id_produto']) ? intval($_POST['id_produto']) : null;

    if ($id_editando) {
        $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, descricao = ?, preco = ?, estoque = ?, id_categoria = ? WHERE id_produto = ?");
        $stmt->execute([$nome, $descricao, $preco, $estoque, $id_categoria, $id_editando]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, estoque, id_categoria) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $descricao, $preco, $estoque, $id_categoria]);
    }
    
    header("Location: administracao.php?tab=produtos");
    exit;
}

$prod_edit = null;
if (isset($_GET['edit_produto'])) {
    $id_edit = intval($_GET['edit_produto']);
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id_produto = ?");
    $stmt->execute([$id_edit]);
    $prod_edit = $stmt->fetch();
}

$categorias = $pdo->query("SELECT * FROM categorias ORDER BY nome ASC")->fetchAll();

$query_produtos = "
    SELECT p.*, c.nome AS nome_categoria 
    FROM produtos p 
    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
    ORDER BY p.nome ASC
";
$produtos = $pdo->query($query_produtos)->fetchAll();
?>

<div class="coluna-painel">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
        
        <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
            <h3 style="margin-bottom: 1rem;">
                <?= $prod_edit ? 'Editar Produto' : 'Novo Produto'; ?>
            </h3>
            
            <?php if (isset($erro_produto)): ?>
                <div class="error-msg"><?= $erro_produto; ?></div>
            <?php endif; ?>

            <form action="administracao.php?tab=produtos" method="POST">
                <input type="hidden" name="salvar_produto" value="1"> 
                <input type="hidden" name="id_produto" value="<?= $prod_edit['id_produto'] ?? ''; ?>">
                
                <div class="form-group">
                    <label>Nome do Produto</label>
                    <input type="text" name="nome" value="<?= htmlspecialchars($prod_edit['nome'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Categoria</label>
                    <select name="id_categoria">
                        <option value="">-- Selecione (Opcional) --</option>
                        <?php foreach ($categorias as $cat): ?>
                            <?php $selected = (isset($prod_edit['id_categoria']) && $prod_edit['id_categoria'] == $cat['id_categoria']) ? 'selected' : ''; ?>
                            <option value="<?= $cat['id_categoria']; ?>" <?= $selected; ?>>
                                <?= htmlspecialchars($cat['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Preço (R$)</label>
                        <input type="number" name="preco" value="<?= $prod_edit['preco'] ?? ''; ?>" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label>Estoque Inicial</label>
                        <input type="number" name="estoque" value="<?= $prod_edit['estoque'] ?? '0'; ?>" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Descrição Curta</label>
                    <textarea name="descricao" rows="2"><?= htmlspecialchars($prod_edit['descricao'] ?? ''); ?></textarea>
                </div>

                <button type="submit" class="btn" style="width: 100%; <?= $prod_edit ? 'background-color: #2ecc71;' : ''; ?>">
                    <?= $prod_edit ? 'Atualizar Produto' : 'Cadastrar Produto'; ?>
                </button>

                <?php if ($prod_edit): ?>
                    <a href="administracao.php?tab=produtos" style="display: block; text-align: center; margin-top: 1rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem;">Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="flex: 2;">
            <h3 style="margin-bottom: 1rem;">Estoque Atual</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Produto</th>
                        <th>Categoria</th>
                        <th>Preço</th>
                        <th>Qtd.</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($produtos)): ?>
                        <tr><td colspan="6" style="text-align:center;">Nenhum produto cadastrado.</td></tr>
                    <?php else: ?>
                        <?php foreach ($produtos as $prod): ?>
                            <tr>
                                <td><?= $prod['id_produto']; ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($prod['nome']); ?></strong><br>
                                    <small style="color: #94a3b8;"><?= htmlspecialchars($prod['descricao']); ?></small>
                                </td>
                                <td><?= htmlspecialchars($prod['nome_categoria'] ?? 'Sem categoria'); ?></td>
                                <td>R$ <?= number_format($prod['preco'], 2, ',', '.'); ?></td>
                                <td><?= $prod['estoque']; ?></td>
                                <td>
                                    <a href="administracao.php?tab=produtos&edit_produto=<?= $prod['id_produto']; ?>" class="btn-acao" style="color: #f39c12;">Editar</a>
                                    |
                                    <a href="administracao.php?tab=produtos&delete_produto=<?= $prod['id_produto']; ?>" 
                                       class="btn-acao" 
                                       style="color: #e74c3c;"
                                       onclick="return confirm('Deseja realmente excluir este produto?');">Excluir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>