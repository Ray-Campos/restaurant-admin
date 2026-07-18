<?php

require_once '../config/config.php';
include '../includes/header.php';

$data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$data_fim = $_GET['data_fim'] ?? date('Y-m-d');

$dt_sql_inicio = $data_inicio . ' 00:00:00';
$dt_sql_fim = $data_fim . ' 23:59:59';

$filtro_periodo = " AND p.data_pedido BETWEEN '$dt_sql_inicio' AND '$dt_sql_fim' ";

try {
    $folha_pagamento = $pdo->query("SELECT SUM(salario) FROM funcionarios")->fetchColumn() ?: 0;

    $query_despesas = "SELECT SUM(valor) FROM despesas WHERE data_despesa BETWEEN '$data_inicio' AND '$data_fim'";
    $despesas_gerais = $pdo->query($query_despesas)->fetchColumn() ?: 0;
    
    $saidas_totais = $folha_pagamento + $despesas_gerais;
    
    $query_bruto = "
        SELECT SUM(i.quantidade * i.preco_unitario) 
        FROM itens_pedido i
        JOIN pedidos p ON i.id_pedido = p.id_pedido
        WHERE p.status = 'fechado' $filtro_periodo
    ";
    $lucro_bruto = $pdo->query($query_bruto)->fetchColumn() ?: 0;
    
    $lucro_liquido = $lucro_bruto - $saidas_totais;

    $query_top_clientes = "
        SELECT 
            c.nome, 
            COUNT(DISTINCT p.id_pedido) AS num_pedidos, 
            SUM(i.quantidade * i.preco_unitario) AS total_gasto
        FROM clientes c
        JOIN pedidos p ON c.id_cliente = p.id_cliente
        JOIN itens_pedido i ON p.id_pedido = i.id_pedido
        WHERE p.status = 'fechado' $filtro_periodo
        GROUP BY c.id_cliente
        ORDER BY total_gasto DESC
        LIMIT 10
    ";
    $top_clientes = $pdo->query($query_top_clientes)->fetchAll();

    $query_tamanho = "
        SELECT 
            SUM(CASE WHEN valor_total < 100 THEN 1 ELSE 0 END) AS pequeno,
            SUM(CASE WHEN valor_total >= 100 AND valor_total <= 399 THEN 1 ELSE 0 END) AS medio,
            SUM(CASE WHEN valor_total >= 400 THEN 1 ELSE 0 END) AS grande
        FROM (
            SELECT p.id_pedido, SUM(i.quantidade * i.preco_unitario) AS valor_total
            FROM pedidos p
            JOIN itens_pedido i ON p.id_pedido = i.id_pedido
            WHERE p.status = 'fechado' $filtro_periodo
            GROUP BY p.id_pedido
        ) AS totais_pedidos
    ";
    $tamanhos = $pdo->query($query_tamanho)->fetch();

    $query_mais_vendidos = "
        SELECT 
            pr.nome, 
            SUM(i.quantidade) AS total_vendido, 
            SUM(i.quantidade * i.preco_unitario) AS total_arrecadado
        FROM produtos pr
        JOIN itens_pedido i ON pr.id_produto = i.id_produto
        JOIN pedidos p ON i.id_pedido = p.id_pedido
        WHERE p.status = 'fechado' $filtro_periodo
        GROUP BY pr.id_produto
        ORDER BY total_vendido DESC
        LIMIT 5
    ";
    $mais_vendidos = $pdo->query($query_mais_vendidos)->fetchAll();

    $query_menos_vendidos = "
        SELECT 
            pr.nome, 
            COALESCE(vendas.qtd_vendida, 0) AS total_vendido, 
            COALESCE(vendas.valor_total, 0) AS total_arrecadado
        FROM produtos pr
        LEFT JOIN (
            SELECT 
                i.id_produto, 
                SUM(i.quantidade) AS qtd_vendida, 
                SUM(i.quantidade * i.preco_unitario) AS valor_total
            FROM itens_pedido i
            JOIN pedidos p ON i.id_pedido = p.id_pedido
            WHERE p.status = 'fechado' $filtro_periodo
            GROUP BY i.id_produto
        ) AS vendas ON pr.id_produto = vendas.id_produto
        ORDER BY total_vendido ASC, pr.nome ASC
        LIMIT 5
    ";
    $menos_vendidos = $pdo->query($query_menos_vendidos)->fetchAll();

    $query_melhores_func = "
        SELECT 
            f.nome,
            f.cargo,
            SUM(i.quantidade * i.preco_unitario) AS total_vendido
        FROM funcionarios f
        JOIN pedidos p ON f.id_funcionario = p.id_funcionario
        JOIN itens_pedido i ON p.id_pedido = i.id_pedido
        WHERE p.status = 'fechado' $filtro_periodo
        GROUP BY f.id_funcionario
        ORDER BY total_vendido DESC
        LIMIT 5
    ";
    $melhores_func = $pdo->query($query_melhores_func)->fetchAll();

    $query_piores_func = "
        SELECT 
            f.nome,
            f.cargo,
            COALESCE(vendas.valor_total, 0) AS total_vendido
        FROM funcionarios f
        LEFT JOIN (
            SELECT 
                p.id_funcionario, 
                SUM(i.quantidade * i.preco_unitario) AS valor_total
            FROM pedidos p
            JOIN itens_pedido i ON p.id_pedido = i.id_pedido
            WHERE p.status = 'fechado' $filtro_periodo
            GROUP BY p.id_funcionario
        ) AS vendas ON f.id_funcionario = vendas.id_funcionario
        ORDER BY total_vendido ASC, f.nome ASC
        LIMIT 5
    ";
    $piores_func = $pdo->query($query_piores_func)->fetchAll();

} catch (PDOException $e) {
    die("Erro ao gerar relatórios: " . $e->getMessage());
}
?>

<h2>Módulo de Análise e Relatórios</h2>
<p style="color: #666; margin-bottom: 1.5rem;">Visão gerencial consolidada a partir dos dados do MariaDB.</p>

<div class="coluna-painel" style="margin-bottom: 2rem; background: #fff; padding: 1.5rem; border-radius: 8px; border: 1px solid #e2e8f0;">
    <form method="GET" action="relatorios.php" style="display: flex; gap: 1rem; align-items: flex-end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.85rem; color: #64748b; font-weight: bold;">Data Inicial:</label>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio); ?>" required style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
            <label style="font-size: 0.85rem; color: #64748b; font-weight: bold;">Data Final:</label>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim); ?>" required style="padding: 0.5rem; border: 1px solid #cbd5e1; border-radius: 4px;">
        </div>
        <button type="submit" class="btn" style="padding: 0.6rem 1.5rem;">Filtrar Período</button>
        <a href="relatorios.php" style="margin-left: 1rem; color: #3498db; text-decoration: none; font-size: 0.9rem;">Resetar (30 Dias)</a>
    </form>
</div>

<div class="kpi-container" style="margin-bottom: 2rem;">
    <div class="kpi-card" style="border-left-color: #f39c12;">
        <h4>Lucro Bruto (Faturamento)</h4>
        <p>R$ <?= number_format($lucro_bruto, 2, ',', '.'); ?></p>
    </div>
    
    <div class="kpi-card" style="border-left-color: #e74c3c;">
        <h4>Saídas Totais</h4>
        <p>R$ <?= number_format($saidas_totais, 2, ',', '.'); ?></p>
        <div style="margin-top: 0.8rem; font-size: 0.8rem; color: #64748b; border-top: 1px solid #f1f5f9; padding-top: 0.5rem;">
            <strong>Folha:</strong> R$ <?= number_format($folha_pagamento, 2, ',', '.'); ?><br>
            <strong>Despesas (Período):</strong> R$ <?= number_format($despesas_gerais, 2, ',', '.'); ?>
        </div>
    </div>
    
    <div class="kpi-card <?= $lucro_liquido >= 0 ? 'green' : ''; ?>" <?= $lucro_liquido < 0 ? 'style="border-left-color: #c0392b;"' : ''; ?>>
        <h4>Lucro Líquido</h4>
        <p>R$ <?= number_format($lucro_liquido, 2, ',', '.'); ?></p>
    </div>
</div>

<div class="grid-operacoes">
    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Volume por Ticket Médio</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Distribuição de pedidos fechados baseados no valor final.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Classificação</th>
                    <th>Faixa de Valor</th>
                    <th>Qtd. Pedidos</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Pequeno</strong></td>
                    <td>Até R$ 99,99</td>
                    <td style="font-size: 1.2rem; font-weight: bold;"><?= $tamanhos['pequeno'] ?? 0; ?></td>
                </tr>
                <tr>
                    <td><strong>Médio</strong></td>
                    <td>De R$ 100,00 a R$ 399,99</td>
                    <td style="font-size: 1.2rem; font-weight: bold;"><?= $tamanhos['medio'] ?? 0; ?></td>
                </tr>
                <tr>
                    <td><strong>Grande</strong></td>
                    <td>Acima de R$ 400,00</td>
                    <td style="font-size: 1.2rem; font-weight: bold;"><?= $tamanhos['grande'] ?? 0; ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Top 10 Melhores Clientes</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Clientes ranqueados pelo volume total gasto em pedidos fechados.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Posição</th>
                    <th>Cliente</th>
                    <th>Nº Pedidos</th>
                    <th>Total Gasto</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($top_clientes)): ?>
                    <tr><td colspan="4" style="text-align:center;">Nenhum histórico de pedidos fechados com clientes.</td></tr>
                <?php else: ?>
                    <?php $posicao = 1; foreach ($top_clientes as $cli): ?>
                        <tr>
                            <td><?= $posicao++; ?>º</td>
                            <td><strong><?= htmlspecialchars($cli['nome']); ?></strong></td>
                            <td><?= $cli['num_pedidos']; ?></td>
                            <td style="color: #2ecc71; font-weight: bold;">R$ <?= number_format($cli['total_gasto'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-operacoes" style="margin-top: 2rem;">
    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Top 5 Mais Vendidos</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Os produtos com maior volume de saída.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd. Vendida</th>
                    <th>Receita Gerada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mais_vendidos)): ?>
                    <tr><td colspan="3" style="text-align:center;">Nenhum produto vendido ainda.</td></tr>
                <?php else: ?>
                    <?php foreach ($mais_vendidos as $prod): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prod['nome']); ?></strong></td>
                            <td style="font-weight: bold;"><?= $prod['total_vendido']; ?> un.</td>
                            <td style="color: #2ecc71;">R$ <?= number_format($prod['total_arrecadado'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Top 5 Menos Vendidos</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Produtos com pouca ou nenhuma saída registrada.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Qtd. Vendida</th>
                    <th>Receita Gerada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($menos_vendidos)): ?>
                    <tr><td colspan="3" style="text-align:center;">Sem dados suficientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($menos_vendidos as $prod): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($prod['nome']); ?></strong></td>
                            <td style="color: #e74c3c; font-weight: bold;"><?= $prod['total_vendido']; ?> un.</td>
                            <td style="color: #e74c3c;">R$ <?= number_format($prod['total_arrecadado'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="grid-operacoes" style="margin-top: 2rem;">
    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Top 5 Atendentes (Mais Vendas)</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Funcionários com maior volume financeiro em pedidos fechados.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Cargo</th>
                    <th>Valor Vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($melhores_func)): ?>
                    <tr><td colspan="3" style="text-align:center;">Sem dados de vendas.</td></tr>
                <?php else: ?>
                    <?php foreach ($melhores_func as $func): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($func['nome']); ?></strong></td>
                            <td><small><?= htmlspecialchars($func['cargo']); ?></small></td>
                            <td style="color: #2ecc71; font-weight: bold;">R$ <?= number_format($func['total_vendido'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="coluna-painel">
        <h3 style="margin-bottom: 1rem;">Top 5 Atendentes (Menos Vendas)</h3>
        <p style="font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem;">Inclui funcionários que ainda não realizaram fechamento de pedidos.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Funcionário</th>
                    <th>Cargo</th>
                    <th>Valor Vendido</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($piores_func)): ?>
                    <tr><td colspan="3" style="text-align:center;">Sem dados de equipe.</td></tr>
                <?php else: ?>
                    <?php foreach ($piores_func as $func): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($func['nome']); ?></strong></td>
                            <td><small><?= htmlspecialchars($func['cargo']); ?></small></td>
                            <td style="color: #e74c3c; font-weight: bold;">R$ <?= number_format($func['total_vendido'], 2, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../includes/footer.php'; ?>