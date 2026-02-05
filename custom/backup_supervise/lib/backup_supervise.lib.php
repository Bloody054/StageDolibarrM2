<?php
function backup_supervise_load_assets()
{
    print '<link rel="stylesheet" href="/backup_supervise/css/backup_supervise.css">';
}

function backup_supervise_size_format($bytes, $forceUnit = null, $thousandsSep = ' ', $decimalSep = ',')
{
    if ($bytes <= 0) return '0';
    $units = ['o', 'Ko', 'Mo', 'Go', 'To', 'Po'];

    // Forcer une unité si demandée
    if ($forceUnit !== null) {
        $power = array_search($forceUnit, $units, true);
        if ($power === false) {
            return 'Unité invalide';
        }
    } else {
        $power = floor(log($bytes, 1024));
    }

    $value = $bytes / pow(1024, $power);

    return number_format($value, 2, $decimalSep, $thousandsSep).' '.$units[$power];
}

//Retourne la clé de l'object depuis la valeur d'un ectrafield
function getObjectIdsFromExtrafield($db, $element, $extrafield, $value)
{
    $table = MAIN_DB_PREFIX.$element.'_extrafields';

    $sql = "SELECT fk_object
            FROM ".$table."
            WHERE ".$extrafield." = '".$db->escape($value)."'";

    $resql = $db->query($sql);

    $ids = [];
    if ($resql) {
        while ($obj = $db->fetch_object($resql)) {
            $ids[] = $obj->fk_object;
        }
    }
    if (count($ids)==1){
        return $ids[0];
    }else{
        return $ids;
    }
    
}