<?php
$osWithVendas = Jestor.loadData("producao", array(
    'where' => array(
        'venda_vinculada' => $objectNew['id_vendas']
    )
));
$crWithVendas = Jestor.loadData("contas_a_receber_cr", array(
    'where' => array(
        'venda_vinculada' => $objectNew['id_vendas']
    )
));
if (count($osWithVendas) == 0 && count($crWithVendas) == 0) {
    if ($objectNew['status'] == "Fechado") {
        $contaObject = Jestor.loadData('contas_de_bancos', array(
            'where' => array(
                'name' => "Michigan Comercio")));
        $listaOrcamentosVinculados = Jestor.loadData('orcamentos_de_vendas', array(
            'where' => array(
                'venda_vinculada' => $objectNew['id_vendas'])));
        $resultsCount = count($listaOrcamentosVinculados);
        for ($i = 0; $i < count($listaOrcamentosVinculados); $i++) {
            if ($listaOrcamentosVinculados[$i]['fechado'] == 1) {
                $orcamentoFechado = $listaOrcamentosVinculados[$i];
                $orcamentoName = $orcamentoFechado['name'];
                $valorOrcamento = $orcamentoFechado['valor_do_orcamento'];
                $formaPagamento = $orcamentoFechado['forma_de_pagamento'];
                $prazoPagamento = $orcamentoFechado['prazo_de_pagamento'];
                $iss = $orcamentoFechado['iss'];
                $isv = $orcamentoFechado['isv'];
                $icms = $orcamentoFechado['icms'];
                $ipi = $orcamentoFechado['ipi'];
                $somaAliquotasImpostos = $iss + $isv + $icms + $ipi;
                $tempoCompensacao = 1;
                switch ($formaPagamento) {
                    case 'Boleto':
                        $tempoCompensacao = 1;
                        break;
                    case 'Transferência Bancária':
                        $tempoCompensacao = 1;
                        break;
                    case 'Cartão de Crédito':
                        $tempoCompensacao = 14;
                        break;
                    case 'Cartão de Débito':
                        $tempoCompensacao = 14;
                        break;
                    case 'Cf. Contrato':
                        $tempoCompensacao = 3;
                        break;
                    case 'Cheque':
                        $tempoCompensacao = 30;
                        break;
                    case 'Dinheiro':
                        $tempoCompensacao = 1;
                        break;
                    case 'Permuta':
                        $tempoCompensacao = 30;
                        break;
                    default:
                        $tempoCompensacao = 3;
                        break;
                }
                $intervaloPagamento = 0;
                $totalParcelas = 1;
                switch ($prazoPagamento) {
                    case '5 dias':
                        $totalParcelas = 1;
                        $primeiroPagamento = 5;
                        break;
                    case '30 dias':
                        $totalParcelas = 1;
                        $primeiroPagamento = 30;
                        break;
                    case '5/35 dias':
                        $totalParcelas = 2;
                        $intervaloPagamento = 30;
                        $primeiroPagamento = 5;
                        break;
                    case '30/60 dias':
                        $totalParcelas = 2;
                        $intervaloPagamento = 30;
                        $primeiroPagamento = 30;
                        break;
                    case '5/25/45 dias':
                        $totalParcelas = 3;
                        $intervaloPagamento = 20;
                        $primeiroPagamento = 5;
                        break;
                    case '5/25/45/65 dias':
                        $totalParcelas = 4;
                        $intervaloPagamento = 20;
                        $primeiroPagamento = 5;
                        break;
                    case '5/35/65/95 dias':
                        $totalParcelas = 4;
                        $intervaloPagamento = 30;
                        $primeiroPagamento = 5;
                        break;
                    default:
                        $totalParcelas = 2;
                        $prazoPagamento = "5/35 dias";
                        $intervaloPagamento = 30;
                        $primeiroPagamento = 5;
                        break;
                }
                $valorParcela = $valorOrcamento / $totalParcelas;
                
                // If Produto de Estoque(Supreme, Mifare Branco, Protetores Faciais)
                
                $produtoOrcamento = $orcamentoFechado['produto'];
                $tipoDeServico = Jestor.loadData('tipos_de_servico', array(
                    'where' => array(
                        'id_tipos_de_servico' => $orcamentoFechado['tipo_de_servico']),
                    'limit' => 1));
                if ($tipoDeServico[0]['name'] == "Produto de Estoque") {
                    $pedObjects = Jestor.loadData("pedidos", array(
                        'sort' => array("name desc")
                    ));
                    $nextPedName = strval(19000 + count($pedObjects));
                    $pedCreated = Jestor.create("pedidos", array(
                        'name' => $nextPedName,
                        'data_do_pedido' => $objectNew['data_de_fechamento'],
                        'quantidade_do_pedido' => $orcamentoFechado['quantidade'],
                        'status_do_pedido' => 'Aguardando Pagamento',
                        'venda_vinculada' => $objectNew['id_vendas'],
                        'cliente' => $objectNew['cliente'],
                        'orcamento_de_venda' => $orcamentoFechado['id_orcamentos_de_vendas'],
                        'produto_vinculado' => $produtoOrcamento,
                    ));
                    $orcamentoFechado['pedido'] = $pedCreated['id_pedidos'];
                    $pedObject = $pedCreated['id_pedidos'];
                    $osObject = null;
                } else {

                    // If not Produto de Estoque

                    $dataInicioProducao =  date('d-m-Y', strtotime($dataFechamento. " + 5 days"));
                    $dataTerminoProducao =  date('d-m-Y', strtotime($dataFechamento. " + 25 days"));
                    $osObjects = Jestor.loadData("producao", array(
                        'sort' => array("name desc")
                    ));
                    $nextOSName = strval(19000 + count($osObjects));
                    if ($orcamentoFechado['liberar_producao'] == 1) {
                        $statusProducao = "Produção Liberada - Negociação Especial";
                    } else {
                        $statusProducao = "Aguardando";
                    }
                    $osCreated = Jestor.create("producao", array(
                        'name' => $nextOSName,
                        'previsao_de_inicio' => $dataInicioProducao,
                        'previsao_de_termino' => $dataTerminoProducao,
                        'quantidade_de_producao' => $orcamentoFechado['quantidade'],
                        'quantidade_baixada' => 0,
                        'quantidade_em_producao' => 0,
                        'status_de_producao' => $statusProducao,
                        'venda_vinculada' => $objectNew['id_vendas'],
                        'cliente' => $objectNew['cliente'],
                        'orcamento_de_venda' => $orcamentoFechado['id_orcamentos_de_vendas'],
                        'produto_vinculado' => $orcamentoFechado['produto'],
                        'tipo_de_servico' => $orcamentoFechado['tipo_de_servico']
                    ));
                    $orcamentoFechado['ordem_de_servico_os'] = $osCreated['id_producao'];
                    $osObject = $osCreated['id_producao'];
                    // em produto, criar campo com tipo de produto
                    // criar teste com orc 1 -> OS, orc 2 -> PED
                    $pedObject = null;
                }
                foreach (range(1, $totalParcelas) as $parcela) {
                    // create CR
                    $diasAteVencimento = ($parcela - 1) * $intervaloPagamento + $primeiroPagamento;
                    $dataFechamento = date('d-m-Y', $objectNew['data_de_fechamento']);
                    $dataVencimento =  date('d-m-Y', strtotime($dataFechamento. " + $diasAteVencimento days"));
                    $comissaoVendedor = ($orcamentoFechado['comissao_vendedor_'] / 100) * $valorParcela;
                    $comissaoAgencia = ($orcamentoFechado['comissao_agencia_'] / 100) * $valorParcela;
                    $comissaoEspecial = ($orcamentoFechado['comissao_especial'] / 100) * $valorParcela;
                    $impostoRetido = ($somaAliquotasImpostos / 100) * $valorParcela;
                    $valorReceita = $valorParcela - $impostoRetido - ($comissaoVendedor + $comissaoAgencia + $comissaoEspecial);
                    Jestor.create("contas_a_receber_cr", array(
                        'name' => "Pagamento $parcela de $totalParcelas - $orcamentoName",
                        'valor_da_receita' => $valorReceita,
                        'contas_de_bancos' => $contaObject,
                        'cliente' => $objectNew['cliente'],
                        'vendedor' => $objectNew['vendedor'],
                        'comissionado_especial' => $objectNew['comissionado_especial'],
                        'agencia' => $objectNew['agencia'],
                        'data_do_vencimento_original' => $dataVencimento,
                        'data_do_vencimento' => $dataVencimento,
                        'data_da_venda' => $objectNew['data_de_fechamento'],
                        'prazo_de_pagamento' => $prazoPagamento,
                        'tipo_de_cobranca' => "Banco Simples",
                        'forma_de_pagamento' => $formaPagamento,
                        'numero_da_parcela' => $parcela,
                        'total_de_parcelas' => $totalParcelas,
                        'status' => "À Vencer",
                        'valor_original' => $valorParcela,
                        'descontos' => 0,
                        'juros' => 0,
                        'imposto_retido' => $impostoRetido,
                        'comissao_vendedor_r' => $comissaoVendedor,
                        'comissao_agencia_r' => $comissaoAgencia,
                        'comissao_especial_r' => $comissaoEspecial,
                        'tempo_de_compensacao' => $tempoCompensacao,
                        'venda_vinculada' => $objectNew['id_vendas'],
                        'orcamento_relacionado' => $orcamentoFechado['id_orcamentos_de_vendas'],
                        'ordem_de_servico_os' => $osObject,
                        'pedido' => $pedObject,
                        'fluxo_automatico' => 1
                        ));
                }
            }
        
        }
    }
}
?>