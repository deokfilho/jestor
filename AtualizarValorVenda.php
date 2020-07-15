<?php
$currObject = $objectNew['fechado'];
$vendaVinculada = $objectNew['venda_vinculada'];
$listaOrcamentos = Jestor.loadData('orcamentos_de_vendas', array(
    'where' => array(
        'venda_vinculada' => $vendaVinculada)));
$totalFechado = 0;
$totalAberto = 0;
for ($i = 0; $i < count($listaOrcamentos); $i++) {
    if ($listaOrcamentos[$i]['fechado'] == 1) {
        $totalFechado += $listaOrcamentos[$i]['valor_do_orcamento'];
    } else {
        $totalAberto += $listaOrcamentos[$i]['valor_do_orcamento'];
    }
}

if ($totalFechado != 0) {
    $totalOrcamento = $totalFechado;
} else {
    $totalOrcamento = $totalAberto;
}

Jestor.update("vendas", array(
    'id_vendas' => $vendaVinculada,
    'valor_da_venda' => $totalOrcamento
));

?>