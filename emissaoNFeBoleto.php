<?php // URL:  https://michiganbrasil.jestor.com.br/development/trigger/contas_a_receber_cr/emissaoNFeBoleto

$customerRequestURL = "https://sandbox.asaas.com/api/v3/customers";
$paymentRequestURL = "https://sandbox.asaas.com/api/v3/payments";
$accessToken = "7711338deff452aab3195adc68e184baa198fa9f0320ce576cff1691f93644fe";
$crObject = $objectNew['id_contas_a_receber_cr'];
$clienteVinculado = $objectNew['cliente'];
$clienteArray = Jestor.loadData("cliente", array(
'where' => array(
    'id_cliente' => $clienteVinculado),
'limit' => 1));
$asaasId = $clienteArray[0]['id_do_cliente']; // check if we have a customer id from asaas
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
$totalDeParcelas = $objectNew['total_de_parcelas'];
$dataVencimento = date('Y-m-d', $objectNew['data_do_vencimento']);
$valor = $objectNew['valor_original'];
$crPedido = $objectNew['pedido'];
$crProducao = $objectNew['ordem_de_servico_os'];
if ($crPedido != null) {
    $pedidoObject = Jestor.loadData("pedidos", array(
        'where' => array(
            'id_pedidos' => $crPedido
        )
    ));
    $pedidoName = $pedidoObject[0]['name'];
    $description =  "Pedido: $pedidoName";
} else if ($crProducao != null) {
    $osObject = Jestor.loadData("producao", array(
        'where' => array(
            'id_producao' => $crProducao
        )
    ));
    $osName = $osObject[0]['name'];
    $description =  "Ordem de Serviço: $osName";
} else {
    Jestor.error("Erro ao emitir boleto. Este contas a receber possui uma OS e um PED, o que não deveria acontecer. Exclua um dos dois, e tente novamente");    
}
if ($objectNew['gerar_boleto'] == 1) {
    $pagamentoObject = Jestor.loadData("formas_de_pagamento", array(
        'where' => array(
            'id_formas_de_pagamento' => $objectNew['forma_de_pagamento_2']
        )
    ));
    $formaPagamento = $pagamentoObject[0]['name'];
    if ($formaPagamento == "Boleto") {
        if ($objectNew['url_do_boleto'] == null) {
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
                    \"dueDate\": \"$dataVencimento\",
                    \"value\": $valor,
                    \"description\": \"$description\",
                    \"externalReference\": \"$crObject\",
                    \"postalService\": false
                    }"));
            $cobrancaArray = json_decode($cobrancaJson, true);
            $errorMessage = $cobrancaArray['errors'][0]['description'];
            if ($errorMessage == null) {
                $cobrancaUrl = $cobrancaArray['bankSlipUrl'];
                $objectNew['url_do_boleto'] = $cobrancaUrl;
            } else {
                // Jestor.error("Chamada para a API do Asaas retornou o problema: $errorMessage");
            }
        }
    }
}
        // Emissao de NF
        // if primeira parcela
            // if not related to another NF
                // call asaas API for creation of NF
        // if ($objectNew['gerar_nf'] == 1) {

        // }  



        // EDGE CASES
        // issue NF or Boleto twice
        // if boleto cancelled for some reason later, and we save the CR again, how do we ensure boleto or nf not issued again erroneously?
        // should this be triggered only when the CR is created? 

        // FIXED

?>