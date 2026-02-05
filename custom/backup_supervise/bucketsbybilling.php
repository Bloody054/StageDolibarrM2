<?php
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once './class/s3connection.class.php';
require_once './class/s3denvr.class.php';
require_once './lib/backup_supervise.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

//if (empty($user->rights->backup_supervise->read)) accessforbidden();

$langs->load('backup_supervise@backup_supervise');
$action = GETPOST('action','aZ09');
$soc = new Societe($db);

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
            'socid'=>'',
            'name'=>'',
            'buckets' => [],
        ];
    }

    $bucketsbybilling[$billingid]['total_size'] += $bucket['size_bytes'];
    $bucketsbybilling[$billingid]['total_objects'] += $bucket['num_objects'];
    $bucketsbybilling[$billingid]['buckets'][] = $bucket;
    $bucketsbybilling[$billingid]['socid']= getObjectIdsFromExtrafield($db, 'societe', 'billing_id', $billingid);
    if(empty($bucketsbybilling[$billingid]['socid'])){
        $bucketsbybilling[$billingid]['name'] = 'CUSTOMER NOT FOUND ! CPTE FACTURATION '.$billingid;
    }else{
        $soc->fetch($bucketsbybilling[$billingid]['socid']);
        $bucketsbybilling[$billingid]['name'] = $soc->name;
    }

    
}




// GENERER LES FACTURES
if ($action === 'createinvoice' && !empty(GETPOST('billing_ids','array'))) {
    if (empty($user->rights->backup_supervise->invoice)) accessforbidden();
    $selected = GETPOST('billing_ids','array');
    foreach ($selected as $bid) {

    //recherche la société qui a ce compte de facturation
    $socid= getObjectIdsFromExtrafield($db, 'societe', 'billing_id', $bid);
    if(!empty($socid)){
        $soc->fetch($socid);
        $month = dol_time_plus_duree(dol_now(), -1, 'm');
        $monthDate = dol_print_date($month,'%Y-%m');
        
        //création de la facture
        $invoice = new Facture($db);
        $invoice->socid = $socid;
        $invoice->date = $month;
        $invoice->type = Facture::TYPE_STANDARD;
        $invoice->ref_customer = 'Stockage / sauvegarde '.$monthDate;
        $invoice->ref_client = 'Stockage / sauvegarde '.$monthDate;
        
        $invoice->cond_reglement_id = 1;
        $invoice->mode_reglement_id = 2;

        $invoice->create($user);
        $tva_tx = 20;
        $qty_totale = 0;
        $pu_ht = !empty($soc->array_options['options_price_per_tb']) ? round($soc->array_options['options_price_per_tb'],0) : 12;
        $desc='';
        $ldesc='';

        //Detail de chaque bucket facturé
        foreach($buckets as $bucket){
            
            if($bucket['billing_account_id']==$bid){
                $qty = backup_supervise_size_format($bucket['size_bytes']);
                $qty_totale+=$bucket['size_bytes'];
                $nb_objects = number_format((int) $bucket['num_objects'], 0, ',', ' ');
                
                $ldesc.= '<br><b>• '.$bucket['name'].'</b>';
                $ldesc.= '<br>- Volume occupé : '.$qty;
                $ldesc.= '<br>- Nombre d`\'objets stockés : '.$nb_objects;
                //l$desc.= '<br>- Dernière modification : '.dol_print_date(strtotime($bucket['modified_at']), 'dayhour');;
            }                
        }


        $desc ='Volume de stockage pour la période : <b>'. $monthDate.'</b>';
        $desc .='<br>Prix en euro HT par To : '. $pu_ht;
        $desc .='<br>Quantité totale exprimée en To<sup>*</sup> : <b>'.backup_supervise_size_format($qty_totale,'To').'</b>';
        $desc .='<br>';
        $desc.=$ldesc;
        $desc .='<br><br><sup>*</sup> 1To = 1024 Go';
        
        $invoice->addline(
                $desc,
                $pu_ht,
                backup_supervise_size_format($qty_totale,'To'),
                $tva_tx,
                0, //$localtax1_tx = 0,
                0, //$localtax2_tx = 0,
                0, //$fk_product = 0,
                0, //$remise_percent = 0,
                '',//$date_start = '',
                '',//$date_end = '',
                0,//$ventil = 0,
                0,//$info_bits = 0,
                0,//$fk_remise_except = 0,
                'HT',//$price_base_type = 'HT',
                0,//$pu_ttc = 0,
                1,//$type = 0,
                -1,//$rang = -1,
                0,//$special_code = 0,
                0,//$fk_parent_line = 0,
                null,//$fk_fournprice = null,
                '5.99',//$pa_ht = 0,
                //$label = '',
                //$array_options = [],
                //$situation_percent = 100,
                //$fk_prev_id = 0,
                //$fk_unit = null,
                //$origin = '',
                //$origin_id = 0
                );
            
            //TODO AJOUTER CODE COMPTABLE
            // AJOUTER PRIX DE REVIENT DU TO pour calcul marge
            //AJOUTER le tarif du TO au client
            //Tenir une table pour suivre ce qui a été éditée de ce qui ne l'a pas été ?
            // ajouter une catégorie à la facture en plus de l'extrafield ?


        //mise à jour des extrafields de la facture
        $invoice->array_options['options_volume'] = backup_supervise_size_format($qty_totale,'To');
        $invoice->array_options['options_periode_y'] = $monthDate = dol_print_date($month,'%Y');
        $invoice->array_options['options_periode_m'] = $monthDate = dol_print_date($month,'%m');
        $invoice->array_options['options_catfac'] = 's3';
        $invoice->update($user);

        setEventMessages('Facture '.$invoice->ref.' générée pour '.$soc->name, null, 'mesgs');
    }else{
        echo 'Client introuvable cpte facturation n°'.$bid;
        exit;
    }


        
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

print dol_get_fiche_head($head, 'bucketsbybilling', $langs->trans("Buckets"), -1);

$html = '';
//formulaire
$html .= '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">'."\n";
$html .= '<input type="hidden" name="token" value="'.newToken().'">'."\n";
$html .= '<input type="hidden" name="action" value="createinvoice">'."\n";
$html .= '<div class="center">'."\n";
$html .= '  <input type="submit" class="butAction" value="'.$langs->trans('CreateInvoiceFromSelection').'">'."\n";
$html .= '</div>'."\n";


$html .= '<div class="div-table-responsive">';
$html .= '<div class="div-table-responsive">' . "\n";
$html .= '<table class="tagtable liste">' . "\n";
$html .= '  <thead>' . "\n";
$html .= '    <tr class="liste_titre">' . "\n";
$html .= '      <th class="liste_titre left"></th>' . "\n";
$html .= '      <th class="liste_titre left">Nom</th>' . "\n";
$html .= '      <th class="liste_titre center">Taille</th>' . "\n";
$html .= '      <th class="liste_titre right">Nombre objects</th>' . "\n";
$html .= '      <th class="liste_titre right">Dernière Fac.</th>' . "\n";
$html .= '      <th class="liste_titre right">Suspendu ?</th>' . "\n";
$html .= '    </tr>' . "\n";
$html .= '  </thead>' . "\n";
$html .= '  <tbody>' . "\n";

foreach ($bucketsbybilling as $bid => $bucketbb) {
    $buckets = $bucketbb['buckets'];
    
    $html .= '    <tr class="oddeven">' . "\n";
    $html .= '      <td class="left"><input type="checkbox" name="billing_ids[]" value="'.$bid.'"></td>' . "\n";
    $html .= '      <td class="left bold">'.$bucketbb['name'].'</td>' . "\n";
    $html .= '      <td class="right bold">'.backup_supervise_size_format($bucketbb['total_size']).'</td>' . "\n";
    $html .= '      <td class="right bold">'.number_format((int) $bucketbb['total_objects'], 0, ',', ' ').'</td>' . "\n";
    $html .= '      <td class="right"></td>' . "\n";
    $html .= '      <td class="right"></td>' . "\n";
    $html .= '    </tr>' . "\n";

        //Détail des buckets
        foreach($buckets as $b){
        $html .= '    <tr class="oddeven">' . "\n";
        $html .= '      <td class="left"></td>' . "\n";
        $html .= '      <td class="left">'.$b['name'].'</td>' . "\n";
        $html .= '      <td class="right">'.backup_supervise_size_format($b['size_bytes']).'</td>' . "\n";
        $html .= '      <td class="right">'.(int) $b['num_objects'].'</td>' . "\n";
        $html .= '      <td class="right"></td>' . "\n";
        $html .= '      <td class="right">'.$b['is_suspended'].'</td>' . "\n";
        $html .= '    </tr>' . "\n";

    }
}

$html .= '  </tbody>' . "\n";
$html .= '</table>' . "\n";
$html .= '</div>' . "\n";

$html .= '</form>'."\n";

echo $html;

llxFooter();