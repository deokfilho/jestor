<?php
if ($objectNew['fluxo_automatico'] == 1) {
    if ($objectNew['status'] == "Fechado") {
        $contaObject = Jestor.loadData('contas_de_bancos', array(
            'where' => array(
                'name' => "Michigan Comercio")));
        $listaOrcamentosVinculados = Jestor.loadData('orcamentos_de_vendas', array(
            'where' => array(
                'venda_vinculada' => $objectNew['id_vendas'])));
        $resultsCount = count($listaOrcamentosVinculados);
        if ($objectNew['faturada'] != 1) {
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
                    foreach (range(1, $totalParcelas) as $parcela) {
                        // create CR
                        $diasAteVencimento = ($parcela - 1) * $intervaloPagamento + $primeiroPagamento;
                        $dataPagamento = date('d-m-Y', $objectNew['data_de_fechamento']);
                        $dataVencimento =  date('d-m-Y', strtotime($dataPagamento. " + $diasAteVencimento days"));
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
                            'orcamento_relacionado' => $orcamentoFechado['id_orcamentos_de_vendas']
                            ));
                    }
                    $objectNew['faturada'] = 1;
            }
        }  
    }
}
}
?>