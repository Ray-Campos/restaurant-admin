<?php

$id_gerenciar = isset($_GET['gerenciar']) ? intval($_GET['gerenciar']) : null;
$mensagem_erro = null;
$erro_estoque = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['abrir_pedido'])) {
    $id_mesa = !empty($_POST['id_mesa']) ? intval($_POST['id_mesa']) : null;
    $id_cliente = !empty($_POST['id_cliente']) ? intval($_POST['id_cliente']) : null;
    $id_funcionario = !empty($_POST['id_funcionario']) ? intval($_POST['id_funcionario']) : null;
    
    $stmt = $pdo->prepare("INSERT INTO pedidos (id_cliente, id_mesa, id_funcionario, status, forma_de_pagamento) VALUES (?, ?, ?, 'aberto', 'DINHEIRO')");
    $stmt->execute([$id_cliente, $id_mesa, $id_funcionario]);
    
    $novo_id = $pdo->lastInsertId();

    if ($id_mesa) {
        $pdo->prepare("UPDATE mesas SET status = 'ocupada' WHERE id_mesa = ?")->execute([$id_mesa]);
    }
    
    header("Location: caixa.php?tab=pedidos&gerenciar=" . $novo_id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item']) && $id_gerenciar) {
    $id_produto = intval($_POST['id_produto']);
    $quantidade = intval($_POST['quantidade']);

    $stmt_preco = $pdo->prepare("SELECT preco FROM produtos WHERE id_produto = ?");
    $stmt_preco->execute([$id_produto]);
    $preco_unitario = $stmt_preco->fetchColumn();

    try {
        $stmt = $pdo->prepare("INSERT INTO itens_pedido (id_pedido, id_produto, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmt->execute([$id_gerenciar, $id_produto, $quantidade, $preco_unitario]);
        
        header("Location: caixa.php?tab=pedidos&gerenciar=" . $id_gerenciar);
        exit;
    } catch (PDOException $e) {
        $mensagem_erro = "Não foi possível lançar: " . $e->getMessage();
    }
}

if (isset($_GET['delete_item']) && $id_gerenciar) {
    $id_item = intval($_GET['delete_item']);
    
    $pdo->prepare("DELETE FROM itens_pedido WHERE id_item = ?")->execute([$id_item]);
    
    header("Location: caixa.php?tab=pedidos&gerenciar=" . $id_gerenciar);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fechar_pedido']) && $id_gerenciar) {
    $forma_pagamento = $_POST['forma_pagamento'];
    $id_mesa_liberar = !empty($_POST['id_mesa_liberar']) ? intval($_POST['id_mesa_liberar']) : null;

    $stmt = $pdo->prepare("UPDATE pedidos SET status = 'fechado', forma_de_pagamento = ? WHERE id_pedido = ?");
    $stmt->execute([$forma_pagamento, $id_gerenciar]);

    if ($id_mesa_liberar) {
        $pdo->prepare("UPDATE mesas SET status = 'livre' WHERE id_mesa = ?")->execute([$id_mesa_liberar]);
    }
    
    header("Location: caixa.php?tab=pedidos");
    exit;
}

if ($id_gerenciar): 
    
    $stmt = $pdo->prepare("
        SELECT p.*, c.nome AS nome_cliente, m.numero AS numero_mesa, f.nome AS nome_garcom 
        FROM pedidos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
        LEFT JOIN funcionarios f ON p.id_funcionario = f.id_funcionario
        WHERE p.id_pedido = ?
    ");
    $stmt->execute([$id_gerenciar]);
    $pedido_atual = $stmt->fetch();

    if (!$pedido_atual) die("Pedido não encontrado.");

    $stmt_itens = $pdo->prepare("
        SELECT i.*, pr.nome AS nome_produto 
        FROM itens_pedido i
        JOIN produtos pr ON i.id_produto = pr.id_produto
        WHERE i.id_pedido = ?
    ");
    $stmt_itens->execute([$id_gerenciar]);
    $itens = $stmt_itens->fetchAll();

    $total_pedido = 0;
    foreach ($itens as $item) {
        $total_pedido += ($item['quantidade'] * $item['preco_unitario']);
    }

    $produtos_disponiveis = $pdo->query("SELECT * FROM produtos ORDER BY nome ASC")->fetchAll();
?>

    <div style="margin-bottom: 1rem;">
        <a href="caixa.php?tab=pedidos" style="text-decoration: none; color: #64748b; font-weight: bold;">&larr; Voltar para Visão Geral</a>
    </div>

    <div class="coluna-painel" style="margin-bottom: 1.5rem; background-color: #f8fafc; border-left: 5px solid #3498db;">
        <h3 style="margin-bottom: 0.5rem;">Pedido #<?= $pedido_atual['id_pedido']; ?></h3>
        <p style="font-size: 0.95rem; color: #475569;">
            <strong>Mesa:</strong> <?= $pedido_atual['numero_mesa'] ?? 'Avulso/Balcão'; ?> | 
            <strong>Cliente:</strong> <?= htmlspecialchars($pedido_atual['nome_cliente'] ?? 'Não informado'); ?> | 
            <strong>Atendente:</strong> <?= htmlspecialchars($pedido_atual['nome_garcom'] ?? 'Não informado'); ?> |
            <strong>Abertura:</strong> <?= date('d/m/Y H:i', strtotime($pedido_atual['data_pedido'])); ?>
        </p>
    </div>

    <div class="coluna-painel">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
            
            <div style="flex: 1;">
                <h3 style="margin-bottom: 1rem;">Adicionar Produto</h3>
                
                <?php if ($erro_estoque): ?>
                    <div class="error-msg"><?= $erro_estoque; ?></div>
                <?php endif; ?>

                <form action="caixa.php?tab=pedidos&gerenciar=<?= $id_gerenciar; ?>" method="POST">
                    <input type="hidden" name="add_item" value="1">
                    
                    <div class="form-group">
                        <label>Produto</label>
                        <select name="id_produto" required>
                            <option value="">-- Selecione o Produto --</option>
                            <?php foreach ($produtos_disponiveis as $prod): ?>
                                <?php $esgotado = $prod['estoque'] <= 0 ? 'disabled' : ''; ?>
                                <option value="<?= $prod['id_produto']; ?>" <?= $esgotado; ?>>
                                    <?= htmlspecialchars($prod['nome']); ?> - R$ <?= number_format($prod['preco'], 2, ',', '.'); ?> (Estoque: <?= $prod['estoque']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Quantidade</label>
                        <input type="number" name="quantidade" value="1" min="1" required>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">+ Lançar Item</button>
                </form>

                <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #e2e8f0;">

                <h3 style="margin-bottom: 1rem; color: #2ecc71;">Fechamento de Conta</h3>
                <form action="caixa.php?tab=pedidos&gerenciar=<?= $id_gerenciar; ?>" method="POST">
                    <input type="hidden" name="fechar_pedido" value="1">
                    <input type="hidden" name="id_mesa_liberar" value="<?= $pedido_atual['id_mesa']; ?>">
                    
                    <div class="form-group">
                        <label>Forma de Pagamento</label>
                        <select name="forma_pagamento" required>
                            <option value="DINHEIRO">Dinheiro</option>
                            <option value="PIX">PIX</option>
                            <option value="CARTAO">Cartão (Débito/Crédito)</option>
                        </select>
                    </div>
                    <?php if (empty($itens)): ?>
                        <button type="button" class="btn" style="width: 100%; background-color: #94a3b8; cursor: not-allowed;">Comanda Vazia</button>
                    <?php else: ?>
                        <button type="submit" class="btn" style="width: 100%; background-color: #2ecc71;" onclick="return confirm('Confirmar fechamento da conta e liberação da mesa?');">Finalizar Pedido (R$ <?= number_format($total_pedido, 2, ',', '.'); ?>)</button>
                    <?php endif; ?>
                </form>
            </div>

            <div style="flex: 2;">
                <h3 style="margin-bottom: 1rem;">Comanda (Itens Lançados)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th>Qtd.</th>
                            <th>Valor Unit.</th>
                            <th>Subtotal</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($itens)): ?>
                            <tr><td colspan="5" style="text-align:center;">Nenhum item lançado ainda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($itens as $item): 
                                $subtotal = $item['quantidade'] * $item['preco_unitario'];
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($item['nome_produto']); ?></strong></td>
                                    <td><?= $item['quantidade']; ?></td>
                                    <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.'); ?></td>
                                    <td>R$ <?= number_format($subtotal, 2, ',', '.'); ?></td>
                                    <td>
                                        <a href="caixa.php?tab=pedidos&gerenciar=<?= $id_gerenciar; ?>&delete_item=<?= $item['id_item']; ?>" 
                                           class="btn-acao" style="color: #e74c3c;">Remover</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f1f5f9;">
                            <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.1rem;">TOTAL:</td>
                            <td colspan="2" style="font-weight: bold; font-size: 1.1rem; color: #2ecc71;">R$ <?= number_format($total_pedido, 2, ',', '.'); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        </div>
    </div>

<?php else: 
    
    $clientes_lista = $pdo->query("SELECT id_cliente, nome FROM clientes ORDER BY nome ASC")->fetchAll();
    $mesas_livres = $pdo->query("SELECT id_mesa, numero FROM mesas WHERE status = 'livre' ORDER BY numero ASC")->fetchAll();
    $query_func = "SELECT id_funcionario, nome 
                FROM funcionarios 
                WHERE cargo LIKE '%Garçom%' 
                    OR cargo LIKE '%Garcom%' 
                    OR cargo LIKE '%Atendente%' 
                ORDER BY nome ASC";

    $funcionarios_lista = $pdo->query($query_func)->fetchAll();
    
    $query_abertos = "
        SELECT p.*, c.nome AS nome_cliente, m.numero AS numero_mesa 
        FROM pedidos p
        LEFT JOIN clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN mesas m ON p.id_mesa = m.id_mesa
        WHERE p.status = 'aberto'
        ORDER BY p.data_pedido DESC
    ";
    $pedidos_abertos = $pdo->query($query_abertos)->fetchAll();
?>

    <div class="coluna-painel">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 2rem;">
            
            <div style="flex: 1; background: #f8fafc; padding: 1.5rem; border-radius: 6px; border: 1px solid #e2e8f0; position: sticky; top: 20px;">
                <h3 style="margin-bottom: 1rem;">Abrir Novo Pedido</h3>
                <form action="caixa.php?tab=pedidos" method="POST">
                    <input type="hidden" name="abrir_pedido" value="1"> 
                    
                    <div class="form-group">
                        <label>Mesa</label>
                        <select name="id_mesa">
                            <option value="">-- Balcão / Viagem --</option>
                            <?php foreach ($mesas_livres as $ml): ?>
                                <option value="<?= $ml['id_mesa']; ?>">Mesa <?= $ml['numero']; ?> (Livre)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cliente (Opcional)</label>
                        <select name="id_cliente">
                            <option value="">-- Consumidor Final --</option>
                            <?php foreach ($clientes_lista as $cl): ?>
                                <option value="<?= $cl['id_cliente']; ?>"><?= htmlspecialchars($cl['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Atendente / Garçom</label>
                        <select name="id_funcionario" required>
                            <option value="">-- Selecione o Responsável --</option>
                            <?php foreach ($funcionarios_lista as $fl): ?>
                                <option value="<?= $fl['id_funcionario']; ?>"><?= htmlspecialchars($fl['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn" style="width: 100%;">Criar Pedido</button>
                </form>
            </div>

            <div style="flex: 2;">
                <h3 style="margin-bottom: 1rem;">Comandas em Aberto</h3>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mesa</th>
                            <th>Cliente</th>
                            <th>Abertura</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pedidos_abertos)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8;">Nenhum pedido aberto no momento.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pedidos_abertos as $ped): ?>
                            <tr>
                                <td><strong>#<?= $ped['id_pedido']; ?></strong></td>
                                <td><?= $ped['numero_mesa'] ? 'Mesa ' . $ped['numero_mesa'] : 'Balcão'; ?></td>
                                <td><?= htmlspecialchars($ped['nome_cliente'] ?? 'Consumidor Final'); ?></td>
                                <td><?= date('H:i - d/m', strtotime($ped['data_pedido'])); ?></td>
                                <td>
                                    <a class="btn" href="caixa.php?tab=pedidos&gerenciar=<?= $ped['id_pedido']; ?>" style="padding: 0.3rem 0.6rem; font-size: 0.85rem; background-color: #f39c12;">Ver Comanda &rarr;</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

<?php endif; ?>