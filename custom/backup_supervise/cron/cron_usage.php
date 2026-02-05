<?php

require '../../main.inc.php';

global $db, $conf;

require_once DOL_DOCUMENT_ROOT.'/backup_supervise/class/s3denvr.class.php';

$denvr = new S3Denvr($db);
$apiToken = $denvr->apiToken;

if (empty($apiToken)) {
    dol_syslog("backup_supervise CRON : API token manquant", LOG_ERR);
    return -1;
}


try {
    $buckets = $denvr->getBuckets($apiToken);

} catch (Exception $e) {
    dol_syslog("backup_supervise CRON : erreur getBuckets : " . $e->getMessage(), LOG_ERR);
    return -1;
}

foreach ($buckets as $bucket) {

    $bucketName = $bucket['name'] ?? '';
    $sizeBytes  = $bucket['size_bytes'] ?? null;

    if ($sizeBytes === null) {
        dol_syslog("backup_supervise : bucket $bucketName sans size_bytes", LOG_WARNING);
        continue;
    }

    $today = date("Y-m-d");
    $now = date("Y-m-d H:i:s");

    $sql = "INSERT IGNORE INTO ".MAIN_DB_PREFIX."backup_supervise_usage
            (bucket_name, size_bytes, date_measure, date_creation)
            VALUES (
                '".$db->escape($bucketName)."',
                ".$sizeBytes.",
                '".$db->escape($today)."',
                '".$db->escape($now)."'
            )";

    if (!$db->query($sql)) {
        dol_syslog("backup_supervise SQL ERROR : ".$db->lasterror(), LOG_ERR);
    } else {
        dol_syslog("backup_supervise : bucket $bucketName ($bucketName) = $sizeBytes bytes", LOG_INFO);
    }
}

return 0;
