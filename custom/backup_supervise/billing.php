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


$billressources = array();
$denvr = new S3Denvr($db);

$billressources = $denvr->getBillingRessources($denvr->apiToken, 129391); //129391 id billing STV

echo '<pre>';
 print_r($billressources);
echo '</pre>';

llxHeader('', 'Buckets S3');


llxFooter();
