<?php
/*
TODO

- incluir propriedades necessarias em Vendas
- Dynamic naming of OS
*/

// we have one trigger for creation and update of instaces, and another for deletion



function mudou($property) {
    if ($objectOld[$property] !== $objectNew[$property]) {
        return True;
    }
    return False;
}

function afterStatus($desiredObject, $desiredStatus, $desiredObjectId) {
    $objectOfVenda = Jestor.getData($desiredObject, array(
        "where" => array(
            $desiredObject => $desiredObjectId
        )
        ));
    if ($objectOfVenda["status"] !== $desiredStatus) {
        return True;
    }
    return False;
}

function linkedObjectExists($desiredObject) {
    $objectOfVenda = Jestor.getObject($desiredObject);
    if (!empty($objectOfVenda)) {
        return True;
    }
    return False;
}

// PSEUDOCODE - Registro Criado ou Atualizado
    // if fluxo_automatico == "sim"
        // if venda.status == aprovado
            // if cobranca ou OS have not been generated yet
                // if venda.neg_especial == "nao"
                    // criar cobranca
                    // criar OS
                // else
                    // criar cobranca
                    // criar OS 
            // else
                // numero de parcelas mudou
                // data da primeira parcela mudou
                // else
                    // do nothing
        // else
            // do nothing
    // else
        // show message: "Deseja criar Cobrancas e OSs de forma automatica?(Recomendado)"
        if ($objectNew["fluxo_automatico"] == "Sim") {
            if ($objectNew["status"] == "Fechado") {
                // if OS or cobranca never created before
                if (!linkedObjectExists("contas_a_receber") && !linkedObjectExists("producao")) {
                    Jestor.create("contas_a_receber", [
                        // make sure to populate at least all required fields
                            "name" => $objectNew['name'],
                            "valor_receita" => $objectNew['valor_da_venda']
                                ]);
                    Jestor.create("producao", [
                        // make sure to populate at least all required fields
                        /*
                        BOTOES(pensar em nomes melhores, provavelmente consegue fundir parar/apontamentos)
                        - Baixa 
                        - Parar/Retomar
                        - Apontamento
                            */
                            "name" => "OS 4",
                            "status_de_producao" => "Aguardando Pagamento"
                                ]);
                }
                // in which case would only one of producao or contas a receber be created already?
                // if linked records have been created, but the update affects them
                // if linked stuff not affected
        }
    


    if ($objectNew["fluxo_automatico"] == "Sim") {
        if ($objectNew["status"] == "Fechado") {
            if (!afterStatus("cobranca", "Pendente")) {
                Jestor.create("contas_a_receber", [
                    // make sure to populate at least all required fields
                    "name" => "Pagamento 1 de 1 de: " + $objectNew['name'],
                    "valor_receita" => $objectNew['valor_da_venda']
                ]);
            }
            if (!afterStatus("ordem_de_servico", "Aguardando Pagamento")) {
                Jestor.create("producao", [
                    // make sure to populate at least all required fields
                    "name" => "OS 4",
                    "status_de_producao" => "Aguardando Pagamento"
                ]);
            }
            /*
            if (mudou("quantidade_parcelas")) {
                // ainda ha possibilidade de cobranca nao existir?
                // atualizar cobrancas relacionadas
            }
            if (mudou("data_primeira_parcela")) {
                // ainda ha possibilidade de cobranca nao existir?
                // atualizar primeira cobranca
            }
            */
            // oque poderia ser alterado aqui que teria um impacto em OSs relacionadas
        }
    }
}


// Codigo para ser inserido no trigger do Jestor
// 5 dias
// 30 dias
// 5/35 dias
// 30/60 dias
// 5/25/45 dias
// 5/25/45/65 dias
// 5/35/65/95 dias
// definir numero de parcelas a partir de prazo de pagamento
$contaObject = Jestor.loadData('contas_de_bancos', array(
    'where' => array(
        'name' => "Michigan Comercio")));
// for each orcamento in lista de orc vinculados
    // create CR com info do orcamento


    // campos de orcamentos para popular CR
    //impostos
    // valor da venda
$listaOrcamentosVinculados = Jestor.loadData('orcamentos', array(
    'where' => array(
        'venda_vinculada' => $objectNew),
    ));
$listaOrcamentosFechados = array();
foreach ($listaOrcamentosVinculados as $orcamentoVinculado) {
    if ($orcamentoVinculado['fechado'] == 1) {
        array_push($listaOrcamentosFechados, $orcamentoVinculado);
    }
}
if ($objectNew['fluxo_automatico'] == 1) {
    if ($objectNew['status'] == "Fechado") {
        if ($objectNew['faturada'] != 1) {
            foreach ($listaOrcamentosFechados as $orcamentoFechado) {
                $orcamentoName = $orcamentoFechado['name'];
                $valorOrcamento = $orcamentoFechado['valor_do_orcamento'];
                $formaPagamento = $orcamentoFechado['forma_de_pagamento'];
                $prazoPagamento = $orcamentoFechado['prazo_de_pagamento'];
                $iss = $orcamentoFechado['ISS'];
                $isv = $orcamentoFechado['ISV'];
                $icms = $orcamentoFechado['ICMS'];
                $ipi = $orcamentoFechado['IPI'];
                $somaAliquotasImpostos = $iss + $isv + $icms + $ipi;
                $tempoCompensacao = 1;
                switch ($formaPagamento) {
                    case 'Boleto':
                        $tempoCompensacao = 1;
                        break;
                    case 'Transferência':
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
                    default:
                        $tempoCompensacao = 1;
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
                    Jestor.create("contas_a_receber_cr", array(
                        'name' => "Pagamento $parcela de $totalParcelas - $orcamentoName",
                        'valor_da_receita' => $valorParcela,
                        'conta_vinculada' => $contaObject,
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
                        'imposto_retido' => $somaAliquotasImpostos * $valorOrcamento,
                        'tempo_de_compensacao' => $tempoCompensacao,
                        'venda_vinculada' => $objectNew,
                        'orcamento_vinculado' => $orcamentoFechado
                        ));
                    // at this points issue NF and boleto
                }
                $objectNew['faturada'] = 1;
                // createOS();
                // updateVendasCR();
                // updateVendasOS();
            }
        }   
    }
}

function createOS() {
    Jestor.create("producao", [
        "name" => "OS 4",
        "status_de_producao" => "Aguardando Pagamento"
        ]);
}

?>

if ($objectNew['fluxo_automatico'] == 1) {
    if ($objectNew['status'] == "Fechado") {
        $contaObject = Jestor.loadData('contas_de_bancos', array(
            'where' => array(
                'name' => "Michigan Comercio")));
        $listaOrcamentosVinculados = Jestor.loadData('orcamentos_de_vendas', array(
            'where' => array(
                'venda_vinculada' => $objectOld)));
        $listaOrcamentosFechados = array();
        foreach ($listaOrcamentosVinculados as $orcamentoVinculado) {
            if ($orcamentoVinculado['fechado'] == 1) {
                array_push($listaOrcamentosFechados, $orcamentoVinculado);
            }
        }
        if ($objectNew['faturada'] != 1) {
            foreach ($listaOrcamentosFechados as $orcamentoFechado) {
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
                    case 'Transferência':
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
                    default:
                        $tempoCompensacao = 1;
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
                    Jestor.create("contas_a_receber_cr", array(
                        'name' => "Pagamento $parcela de $totalParcelas - $orcamentoName",
                        'valor_da_receita' => $valorParcela,
                        'conta_vinculada' => $contaObject,
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
                        'imposto_retido' => $somaAliquotasImpostos * $valorOrcamento,
                        'tempo_de_compensacao' => $tempoCompensacao,
                        'venda_vinculada' => $objectNew,
                        'orcamento_vinculado' => $orcamentoFechado
                        ));
                }
                $objectNew['faturada'] = 1;
            }
        }  
    }
}