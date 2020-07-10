<?php
// only set date if not already populated
if ($objectNew['status'] == "Fechado") {
    if ($objectNew['data_de_fechamento'] == null) {
        $dataHoje =  date('d-m-Y', strtotime("Tomorrow"));
        $objectNew['data_de_fechamento'] = $dataHoje;   
    }
}

?>