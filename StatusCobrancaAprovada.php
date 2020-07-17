<?php
// URL:  https://michiganbrasil.jestor.com.br/development/trigger/contas_a_receber_cr/StatusCobrancaAprovada


if ($objectNew['numero_da_parcela'] == 1) {
    if ($objectNew['fluxo_automatico'] == 1) {
        $crStatus = $objectNew['status'];
        if ($crStatus == "Pago") {
            $osVinculada = $objectNew['ordem_de_servico_os'];
            $osObject = Jestor.loadData("producao", array(
                'where' => array(
                    'id_producao' => $osVinculada),
                'limit' => 1));
            $osStatus = $osObject[0]['status_de_producao'];
            if ($osStatus == "Aguardando") {
                Jestor.update("producao", array(
                    'id_producao' => $osVinculada,
                    'status_de_producao' => "Liberado"
                ));
            }
        }
    }
}

// EDGE CASES
// What about when there is no OS, only PED

// FIXED
// if pago by mistake(message showing automation should inform user that OS was updated automatically)
    // create fluxo_automatico selection box, default is yes
// if already vencido
    // still update OS automatically, for this it doesn't matter that its vencido
// if edited multiple times with Pago for status
    // not a problem since it will only update OS, if the OS status is Aguardando, once its changed to Liberado, this trigger won't have an impact
// if produção already ahead of liberado
    // status check for OS takes care of it
// if pago but shouldn't produce
    // fluxo automatico checkbox takes care of it

?>