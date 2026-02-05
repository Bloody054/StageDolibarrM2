<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class S3Denvr extends CommonObject
{
    public $apiToken = 'mNV7MLeclfKWkj9oPlCT6wygDrydHEvs';
    public $cost_price_gb_month = 0.01;

    public function __construct($db)
    {
        $this->db = $db;
    }

    function getBuckets($apiToken, $billingAccountId = null) {
    $url = 'https://api.denv-r.com/v1/storage/bucket/list';
    if ($billingAccountId !== null) {
        $url .= '?billing_account_id=' . urlencode($billingAccountId);
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $apiToken,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        // Erreur réseau / cURL
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error when calling DENV-R API: {$err}");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            if ($data === null) {
                throw new Exception("Unable to decode JSON response from DENV-R: {$response}");
            }
            return $data; // tableau de buckets
        } else {
            // Gérer l’erreur renvoyée par l’API
            throw new Exception("DENV-R API returned HTTP code {$httpCode} with body: {$response}");
        }

    }

    public function getMonthlyAverageUsageByName(string $bucketName, ?int $year = null, ?int $month = null): ?float
    {
    $db = $this->db ?? $GLOBALS['db'];

    // Valeurs par défaut → mois courant
    if ($year === null)  $year  = (int)date('Y');
    if ($month === null) $month = (int)date('m');

    // Bornes du mois
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', strtotime($firstDay));

    // Requête SQL
    $sql = "SELECT AVG(size_bytes) as avg_size
            FROM ".MAIN_DB_PREFIX."backup_supervise_usage
            WHERE bucket_name = '".$db->escape($bucketName)."'
            AND date_measure BETWEEN '".$db->escape($firstDay)."'
                                 AND '".$db->escape($lastDay)."'";

    $res = $db->query($sql);
    if (!$res) {
        dol_syslog(__METHOD__." SQL ERROR: ".$db->lasterror(), LOG_ERR);
        return null;
    }

    $obj = $db->fetch_object($res);
    if (!$obj) return null;

    return (float)$obj->avg_size;
    }

    public function convert($bytes, string $unit, int $decimals = 2)
    {
        switch (strtolower($unit)) {
            case 'go':
                $value = $bytes / (1024 ** 3);
                return round($value, $decimals).' Go';

            case 'to':
                $value = $bytes / (1024 ** 4);
                return round($value, $decimals).' To';

            default:
                throw new InvalidArgumentException("Unité inconnue : $unit");
        }
    }

    public function getMonthlyMaxUsageByName(string $bucketName,string $return = 'size',?int $year = null,
    ?int $month = null)
    {
    global $db;

    // Valeurs par défaut : mois courant
    if ($year === null)  $year  = (int)date('Y');
    if ($month === null) $month = (int)date('m');

    // Bornes du mois
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', strtotime($firstDay));

    // Requête SQL pour récupérer le pic
    $sql = "SELECT size_bytes, date_measure
            FROM ".MAIN_DB_PREFIX."backup_supervise_usage
            WHERE bucket_name = '".$db->escape($bucketName)."'
            AND date_measure BETWEEN '".$db->escape($firstDay)."'
                                 AND '".$db->escape($lastDay)."'
            ORDER BY size_bytes DESC
            LIMIT 1";

    $res = $db->query($sql);
    if (!$res) {
        dol_syslog(__METHOD__." SQL ERROR: ".$db->lasterror(), LOG_ERR);
        return null;
    }

    $obj = $db->fetch_object($res);
    if (!$obj) return null;

    // Interprétation du paramètre $return
        switch (strtolower($return)) {

            case 'size':
                return (float) $obj->size_bytes;

            case 'date':
                return $obj->date_measure;

            case 'both':
                return [
                    'size_bytes'   => (float) $obj->size_bytes,
                    'date_measure' => $obj->date_measure
                ];

            default:
                throw new InvalidArgumentException("Valeur du paramètre 'return' invalide : $return");
        }
    }

    public function getMonthlyUsageSeriesByName(string $bucketName,?int $year = null,?int $month = null): array
    {
    $db = $this->db ?? $GLOBALS['db'];

    // Par défaut : mois & année courants
    if ($year === null)  $year  = (int)date('Y');
    if ($month === null) $month = (int)date('m');

    // Bornes du mois
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay  = date('Y-m-t', strtotime($firstDay));

    // Requête SQL pour récupérer toutes les mesures du mois
    $sql = "SELECT date_measure, size_bytes
            FROM ".MAIN_DB_PREFIX."backup_supervise_usage
            WHERE bucket_name = '".$db->escape($bucketName)."'
              AND date_measure BETWEEN '".$db->escape($firstDay)."'
                                   AND '".$db->escape($lastDay)."'
            ORDER BY date_measure ASC";

    $res = $db->query($sql);
    if (!$res) {
        dol_syslog(__METHOD__." SQL ERROR : ".$db->lasterror(), LOG_ERR);
        return [];
    }

    $series = [];

    while ($obj = $db->fetch_object($res)) {
        $series[] = [
            'date'       => $obj->date_measure,
            'size_bytes' => (float)$obj->size_bytes
        ];
    }

    return $series;  // tableau des points journaliers
    }

    function getBillingRessources($apiToken,$billingAccountId) {
    $url = 'https://api.denv-r.com/v1/user-resource/billing_resources';
    if ($billingAccountId !== null) {
        $url .= '?id=' . urlencode($billingAccountId);
    }
    echo $url;
    echo '&apikey='.$apiToken;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'apikey: ' . $apiToken,
        'Accept: application/json'
    ]);
    //curl_setopt($ch, CURLOPT_VERBOSE, true);
    //curl_setopt($ch, CURLOPT_STDERR, fopen('php://output', 'w'));

    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        // Erreur réseau / cURL
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL error when calling DENV-R API: {$err}");
    }
        

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            if ($data === null) {
                throw new Exception("Unable to decode JSON response from DENV-R: {$response}");
            }
            return $data;
        } else {
            // Gérer l’erreur renvoyée par l’API
            throw new Exception("DENV-R API returned HTTP code {$httpCode} with body: {$response}");
        }

    }

}