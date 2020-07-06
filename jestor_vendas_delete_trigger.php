<?php

// Registro deletado
    // if cobranca parcialmente ou totalmente paga
        // only deletar venda
    // if OS parcialmente ou totalmente produzida
        // only deletar venda
    // if envio parcialmente ou totalmente expedido
        // only deletar venda
    // deletar venda e todos as instancias de modulos relacionadas a ela: cobrancas, OSs, e envios
if ($registroDeletado) {
    if (!afterStatus("cobranca", "Pendente")) {
        // criar cobranca
    }
    if (!afterStatus("ordem_de_producao", "Aguardando Pagamento")) {
        // criar OS
    }
    if (!afterStatus("envio", "")) {
        // criar OS
    }
}
?>