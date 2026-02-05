<?php
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once './class/s3connection.class.php';
require_once './class/s3denvr.class.php';
require_once './lib/backup_supervise.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';

//if (empty($user->rights->backup_supervise->read)) accessforbidden();

$langs->load('backup_supervise@backup_supervise');
$action = GETPOST('action','aZ09');


$buckets = array();
$denvr = new S3Denvr($db);

$buckets = $denvr->getBuckets($denvr->apiToken);



$bucketsbybilling = array();

foreach ($buckets as $bucket) {
    $billingid = $bucket['billing_account_id'];

    if (!isset($bucketsbybilling[$billingid])) {
        $bucketsbybilling[$billingid] = [
            'total_size' => 0,
            'total_objects' => 0,
            'buckets' => []
        ];
    }

    $bucketsbybilling[$billingid]['total_size'] += $bucket['size_bytes'];
    $bucketsbybilling[$billingid]['total_objects'] += $bucket['num_objects'];
    $bucketsbybilling[$billingid]['buckets'][] = $bucket;
}

echo '<pre>';
 print_r($bucketsbybilling);
echo '</pre>';


if ($action === 'createinvoice' && !empty($_POST['toselect'])) {
    if (empty($user->rights->backup_supervise->invoice)) accessforbidden();
    $selected = array_keys($_POST['toselect']);
    foreach ($selected as $id) {
        $bucket = new BackupBucket($db);
        if (!$bucket->fetch($id)) continue;
        $conn = new S3Connection($db);
        $conn->fetch($bucket->fk_connection);

        $month = dol_time_plus_duree(dol_now(), -1, 'm');
        $monthDate = dol_print_date($month,'%Y-%m-01');
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."backup_supervise_bucket_invoice WHERE fk_bucket=".(int)$bucket['id']." AND billing_month='".$db->escape($monthDate)."'";
        $res = $db->query($sql);
        if ($res && $db->num_rows($res)) continue;

        $invoice = new Facture($db);
        $invoice->socid = $bucket['fk_thirdparty'];
        $invoice->date = $month;
        $invoice->type = Facture::TYPE_STANDARD;
        $invoice->note_public = 'Facturation stockage bucket '.$bucket['name'];
        $invoice->create($user);
        $qty = max(1, round($bucket['size_bytes'] / pow(1024,4), 2));
        $price = $bucket['price_per_tb'] > 0 ? $bucket['price_per_tb'] : 0;
        $desc = ($conn->billing_service?:'Stockage S3').' - '.$bucket['name'].' ('.$qty.' To)';
        $invoice->addline($desc, $price, $qty, 0, 0, 0, 0, 0, '', '', '', Facture::TYPE_STANDARD, -1, $bucket->fk_contract);
        if ($bucket->fk_contract) {
            $contract = new Contrat($db);
            if ($contract->fetch($bucket->fk_contract) > 0) {
                $contract->add_object_linked('facture', $invoice->id);
            }
        }
        $db->query("INSERT INTO ".MAIN_DB_PREFIX."backup_supervise_bucket_invoice (fk_bucket,fk_facture,fk_contract,billing_month) VALUES (".(int)$bucket['id'].",".(int)$invoice->id.",".($bucket->fk_contract?(int)$bucket->fk_contract:'NULL').",'".$db->escape($monthDate)."')");
        setEventMessages('Facture brouillon générée pour '.$bucket['name'], null, 'mesgs');
    }
}

llxHeader('', 'Buckets S3');
//backup_supervise_load_assets();

$title= $langs->trans("Buckets");
print load_fiche_titre($title, '', 'backup_supervise@backup_supervise');
$h = 0;
$head = array();
$head[$h][0] = DOL_URL_ROOT.'/backup_supervise/buckets.php';
$head[$h][1] = $langs->trans("Buckets");
$head[$h][2] = 'buckets';
$h++;
$head[$h][0] = DOL_URL_ROOT.'/backup_supervise/bucketsbybilling.php';
$head[$h][1] = $langs->trans("BucketsByBilling");
$head[$h][2] = 'bucketsbybilling';
$h++;

print dol_get_fiche_head($head, 'buckets', $langs->trans("Buckets"), -1);

echo '<div class="div-table-responsive">';
$html = '';
$html .= '<div class="div-table-responsive">' . "\n";
$html .= '<table class="tagtable liste">' . "\n";
$html .= '  <thead>' . "\n";
$html .= '    <tr class="liste_titre">' . "\n";
$html .= '      <th class="liste_titre left">Nom</th>' . "\n";
$html .= '      <th class="liste_titre center">Taille</th>' . "\n";
$html .= '      <th class="liste_titre right">Nombre objects</th>' . "\n";
$html .= '      <th class="liste_titre right">N° compte fac.</th>' . "\n";
$html .= '      <th class="liste_titre center">MAJ</th>' . "\n";
$html .= '    </tr>' . "\n";
$html .= '  </thead>' . "\n";
$html .= '  <tbody>' . "\n";

foreach ($buckets as $bucket) {
    $html .= '    <tr class="oddeven">' . "\n";
    $html .= '      <td class="left">'.$bucket['name'].'</td>' . "\n";
    $html .= '      <td class="center">'.backup_supervise_size_format($bucket['size_bytes']).'</td>' . "\n";
    $html .= '      <td class="right">'.(int) $bucket['num_objects'].'</td>' . "\n";
    $html .= '      <td class="right">'.$bucket['billing_account_id'].'</td>' . "\n";
    $html .= '      <td class="center">'.($bucket['modified_at']?dol_print_date($bucket['modified_at'],'dayhour'):'-').'</td>' . "\n";
    $html .= '    </tr>' . "\n";
}

$html .= '  </tbody>' . "\n";
$html .= '</table>' . "\n";
$html .= '</div>' . "\n";

echo $html;

llxFooter();