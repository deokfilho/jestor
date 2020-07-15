<?php
// check if customer already created in ASAAS(field id cliente asaas)
// if not
    // create customer using API
    // store customer ID in Cliente

// $vendaVinculada = $objectNew['venda_vinculada'];
// $vendaArray = Jestor.loadData("vendas", array(
//     'where' => array(
//         'id_vendas' => $vendaVinculada),
//     'limit' => 1));

// URL:  https://michiganbrasil.jestor.com.br/development/trigger/contas_a_receber_cr/emissaoNFeBoleto

$clienteVinculado = $objectNew['cliente'];
$clienteArray = Jestor.loadData("cliente", array(
'where' => array(
    'id_cliente' => $clienteVinculado),
'limit' => 1));
$customerId = $clienteArray[0]['id_do_cliente'];
if ($customerId == null) {
    $customerName = $clienteArray[0]['name'];
    $customerEmail = $clienteArray[0]['email_principal'];
    $customerPhone = $clienteArray[0]['telefone_comercial'];
    $customerMobile = $clienteArray[0]['telefone_celular'];
    $customerCnpjCpf = $clienteArray[0]['cnpj_cpf'];
    $customerCEP = $clienteArray[0]['cep'];
    $customerAddress = $clienteArray[0]['endereco'];
    $customerNumero = $clienteArray[0]['numero'];
    $customerComplement = $clienteArray[0]['complemento'];
    $customerNeighborhood = $clienteArray[0]['bairro'];
    $jestorCustomerId = $clienteArray[0]['id_cliente'];
    $municipalInscription = $clienteArray[0]['inscricao_municipal'];
    $stateInscription = $clienteArray[0]['inscricao_estadual'];
    $result = Jestor.curlCall("https://www.asaas.com/api/v3/customers", array( 
            'CURLOPT_RETURNTRANSFER' => 1,
            'CURLOPT_HEADER' => 0,
            'CURLOPT_HTTPHEADER' => array(
                'Content-Type: application/json', 
                'access_token: 5288b0c6ff744e18d65a9d3d4ce9398d226af014f5acbadd7e75ba93e1884a67'),
            'CURLOPT_POSTFIELDS' => http_build_query(array(
                'name' => $customerName,
                'email' => $customerEmail,
                'phone'=> $customerPhone,
                'mobilePhone' => $customerMobile,
                'cpfCnpj' => $customerCnpjCpf,
                'postalCode' => $customerCEP,
                'address' => $customerAddress,
                'addressNumber' => $customerNumero,
                'complement' =>  $customerComplement,
                'province' =>$customerNeighborhood,
                'externalReference' => $jestorCustomerId,
                'notificationDisabled' => false,
                'municipalInscription' => $municipalInscription,
                'stateInscription' => $stateInscription
                ))));
    Jestor.error("The response was: $result");
}


// Emissao de Boleto
// for parcela in parcelas
    // if numero de parcelas == 1
        // call asaas API for creation of cobranca(boleto)
    // else
        // call asaasa API for creation of installments


// Emissao de NF
// if primeira parcela
    // if not related to another NF
        // call asaas API for creation of NF



?>