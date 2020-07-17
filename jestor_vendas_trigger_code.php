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

// ------------------  customer id
        $customerRequestURL = "https://sandbox.asaas.com/api/v3/customers";
        $paymentRequestURL = "https://sandbox.asaas.com/api/v3/payments";
        $accessToken = "7711338deff452aab3195adc68e184baa198fa9f0320ce576cff1691f93644fe";
        $clienteVinculado = $objectNew['cliente'];
        $clienteArray = Jestor.loadData("cliente", array(
        'where' => array(
            'id_cliente' => $clienteVinculado),
        'limit' => 1));
        $asaasId = $clienteArray[0]['id_do_cliente'];
        $customerName = $clienteArray[0]['name'];
        $customerEmail = $clienteArray[0]['email_principal'];
        $customerPhone = $clienteArray[0]['telefone_comercial'];
        $customerMobile = $clienteArray[0]['telefone_celular'];
        $customerCEP = $clienteArray[0]['cep'];
        $customerAddress = $clienteArray[0]['endereco'];
        $customerNumero = $clienteArray[0]['numero'];
        $customerComplement = $clienteArray[0]['complemento'];
        $customerNeighborhood = $clienteArray[0]['bairro'];
        $jestorCustomerId = $clienteArray[0]['id_cliente'];
        $municipalInscription = $clienteArray[0]['inscricao_municipal'];
        $stateInscription = $clienteArray[0]['inscricao_estadual'];
        $customerCnpjCpf = $clienteArray[0]['cnpj_cpf']; 
        if ($asaasId == null) {
        $customerJson = Jestor.curlCall("$customerRequestURL?cpfCnpj=$customerCnpjCpf", array( // GET - check if they have it
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => array(
                "access_token: $accessToken")
        ));
        $customerDecodedJson = json_decode($customerJson, true);
        if ($customerDecodedJson['totalCount'] > 0) {
            $asaasId = $customerDecodedJson['data'][0]['id'];
            if ($asaasId != null) {
                Jestor.update("cliente", array(
                    'id_cliente' => $clienteArray[0]['id_cliente'],
                    'id_do_cliente' => $asaasId
                ));
            }
        }
        }
        if ($asaasId == null) { // POST - create if they don't
            $json = Jestor.curlCall($customerRequestURL, array( 
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_HEADER => 0,
                    CURLOPT_POST => 1,
                    CURLOPT_HTTPHEADER => array(
                        "Content-Type: application/json", 
                        "access_token: $accessToken"),
                    CURLOPT_POSTFIELDS => "{
                            \"name\": \"$customerName\",
                            \"email\": \"$customerEmail\",
                            \"phone\": \"$customerPhone\",
                            \"mobilePhone\": \"$customerMobile\",
                            \"cpfCnpj\": \"$customerCnpjCpf\",
                            \"postalCode\": \"$customerCEP\",
                            \"address\": \"$customerAddress\",
                            \"addressNumber\": \"$customerNumero\",
                            \"complement\": \"$customerComplement\",
                            \"province\": \"$customerNeighborhood\",
                            \"externalReference\": \"$jestorCustomerId\",
                            \"notificationDisabled\": true,
                            \"municipalInscription\": \"$municipalInscription\",
                            \"stateInscription\": \"$stateInscription\"
                        }"));
            $jsonArray = json_decode($json, true);
            $asaasId = $jsonArray['id'];
            if ($asaasId != null) {
                Jestor.update("cliente", array(
                    'id_cliente' => $clienteArray[0]['id_cliente'],
                    'id_do_cliente' => $asaasId
                ));
            } else {
                $errorMessage = $jsonArray['errors'][0]['description'];
                Jestor.error("Erro ao criar registro do cliente com Asaas, erro: $errorMessage");
            }
        }

// ends -------- customer id

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
                $iss = $orcamentoFechado['iss'];
                $isv = $orcamentoFechado['isv'];
                $icms = $orcamentoFechado['icms'];
                $ipi = $orcamentoFechado['ipi'];
                $somaAliquotasImpostos = $iss + $isv + $icms + $ipi;
                $pagamentoObject = Jestor.loadData("formas_de_pagamento", array(
                    'where' => array(
                        'id_formas_de_pagamento' => $orcamentoFechado['forma_de_pagamento_2']
                        )
                ));
                $tempoCompensacao = intval($pagamentoObject[0]['tempo_de_compensacao']);
                $prazoObject = Jestor.loadData("prazos_de_pagamento", array(
                    'where' => array(
                        'id_prazos_de_pagamento' => $orcamentoFechado['prazo_de_pagamento_2']
                        )
                ));
                $intervaloPagamento = intval($prazoObject[0]['intervalo_de_pagamentos']);
                $totalParcelas = $prazoObject[0]['total_de_parcelas'];
                $primeiroPagamento = $prazoObject[0]['dias_ate_primeiro_pagamento'];
                $valorParcela = $valorOrcamento / $totalParcelas;
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
                    $dataFechamento = date('d-m-Y', $objectNew['data_de_fechamento']);
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
                    $pedObject = null;
                }
                if ($pedObject != null && $osObject == null) {
                    $description =  "Pedido: $nextPedName";
                } else if ($osObject != null && $pedObject == null) {
                    $description =  "Ordem de Serviço: $nextOSName";
                } else {
                    Jestor.error("Erro ao emitir boleto. Este contas a receber possui uma OS e um PED, o que não deveria acontecer. Exclua um dos dois, e tente novamente");    
                }
                foreach (range(1, $totalParcelas) as $parcela) {
                    // Create Boleto
                    $diasAteVencimento = ($parcela - 1) * $intervaloPagamento + $primeiroPagamento;
                    $dataFechamento = date('d-m-Y', $objectNew['data_de_fechamento']);
                    $dataVencimento =  date('d-m-Y', strtotime($dataFechamento. " + $diasAteVencimento days"));
                    $boletoURL = null;
                    $formaPagamento = $pagamentoObject[0]['name'];
                    if ($formaPagamento == "Boleto") {
                        $vencimentoBoleto = date('Y-m-d', strtotime($dataFechamento. " + $diasAteVencimento days"));
                        $cobrancaJson = Jestor.curlCall($paymentRequestURL, array( // POST - create Boleto
                            CURLOPT_RETURNTRANSFER => 1,
                            CURLOPT_HEADER => 0,
                            CURLOPT_POST => 1,
                            CURLOPT_HTTPHEADER => array(
                                'Content-Type: application/json', 
                                "access_token: $accessToken"),
                            CURLOPT_POSTFIELDS => "{
                                \"customer\": \"$asaasId\",
                                \"billingType\": \"BOLETO\",
                                \"dueDate\": \"$vencimentoBoleto\",
                                \"value\": $valorParcela,
                                \"description\": \"$description\",
                                \"externalReference\": \"$\",
                                \"postalService\": false
                                }"));
                        $cobrancaArray = json_decode($cobrancaJson, true);
                        $boletoURL = $cobrancaArray['bankSlipUrl'];
                        $errorMessage = $cobrancaArray['errors'][0]['description'];
                        if ($errorMessage != null) {
                            Jestor.error("Chamada para a API do Asaas retornou o problema: $errorMessage");
                        }
                    }
                    // create CR
                    $comissaoVendedor = ($orcamentoFechado['comissao_vendedor_'] / 100) * $valorParcela;
                    $comissaoAgencia = ($orcamentoFechado['comissao_agencia_'] / 100) * $valorParcela;
                    $comissaoEspecial = ($orcamentoFechado['comissao_especial'] / 100) * $valorParcela;
                    $impostoRetido = ($somaAliquotasImpostos / 100) * $valorParcela;
                    $valorReceita = $valorParcela - $impostoRetido - ($comissaoVendedor + $comissaoAgencia + $comissaoEspecial);
                    Jestor.create("contas_a_receber_cr", array(
                        'name' => "Pagamento $parcela de $totalParcelas - $orcamentoName",
                        'valor_da_receita' => $valorReceita,
                        'valor_original' => $valorParcela,
                        'contas_de_bancos' => $contaObject[0],
                        'cliente' => $objectNew['cliente'],
                        'vendedor' => $objectNew['vendedor'],
                        'comissionado_especial' => $objectNew['comissionado_especial'],
                        'agencia' => $objectNew['agencia'],
                        'prazo_de_pagamento_2' => $prazoObject[0],
                        'data_do_vencimento_original' => $dataVencimento,
                        'data_do_vencimento' => $dataVencimento,
                        'data_da_venda' => $objectNew['data_de_fechamento'],
                        'tipo_de_cobranca' => "Banco Simples",
                        'forma_de_pagamento_2' => $pagamentoObject[0],
                        'numero_da_parcela' => $parcela,
                        'total_de_parcelas' => $totalParcelas,
                        'status' => "À Vencer",
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
                        'fluxo_automatico' => 1,
                        'gerar_boleto' => 1,
                        'gerar_nf' => 1,
                        'url_do_boleto' => $boletoURL
                        ));
                    }
                }
            }
        
        }
}
?>