<?php

// if producao status == finalizada
    // if no envio already attached
        // if quantity produced <= orcamento quantity
            // status = envio autorizado
        // else 
            // status = aguardando liberacao

if ($objectNew['fluxo_automatico'] == 1) {
    $osStatus = $objectNew['status_de_producao'];
    if ($osStatus == "Finalizado") {
        $envioRelacionado = Jestor.loadData("expedicao", array(
            'where' => array(
                'ordem_de_servico' => $objectNew['id_producao']),
            'limit' => 1));
        if (count($envioRelacionado) == 0) {
            $quantidadeExcedente = $objectNew['quantidade_de_excedentesextras'];
            if ($quantidadeExcedente <= 0) {
                $statusEnvio = "Expedição Liberada";
                
            } else {
                $statusEnvio = "Aguardando Liberação";
            }
            $envioObjects = Jestor.loadData("expedicao", array(
                'sort' => array("name desc")
            ));
            $nextEnvioName = strval(3000 + count($envioObjects));
            $newEnvio = Jestor.create("expedicao", array(
                'name' => $nextEnvioName,
                'status_de_expedicao' => $statusEnvio,
                'venda' => $objectNew['venda_vinculada'],
                'ordem_de_servico' => $objectNew['id_producao'],
            ));
        }    
    }
}




// EDGE CASES

// esqueceram de atualizar status da OS
// 

// FIXED
// envio manual criado
    // se linkado com nossa OS, sem problema, se nao, 

?>