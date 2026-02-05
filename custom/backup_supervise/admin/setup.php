<?php
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once '../class/s3connection.class.php';
require_once '../lib/backup_supervise.lib.php';

if (!$user->admin) accessforbidden();

$langs->load('backup_supervise@backup_supervise');
$action = GETPOST('action','aZ09');

$connection = new S3Connection($db);

if ($action === 'save') {
    $id = GETPOST('id','int');
    $connection->id = $id;
    $connection->ref = GETPOST('ref','alpha');
    $connection->label = GETPOST('label','alpha');
    $connection->host = GETPOST('host','alpha');
    $connection->access_key = GETPOST('access_key','alpha');
    $connection->secret_key = GETPOST('secret_key','alpha');
    $connection->service = GETPOST('service','alpha');
    $connection->billing_service = GETPOST('billing_service','alpha');
    $connection->note = GETPOST('note','restricthtml');
    $connection->control_panel = GETPOST('control_panel','alpha');
    $connection->color = GETPOST('color','alpha');
    if ($id > 0) {
        $connection->update($user);
    } else {
        $connection->create($user);
    }
}
if ($action === 'delete') {
    $id = GETPOST('id','int');
    if ($connection->fetch($id)) {
        $connection->delete($user);
    }
}

$sql = 'SELECT * FROM '.MAIN_DB_PREFIX.'backup_supervise_connection WHERE entity='.(int) $conf->entity;
$resql = $db->query($sql);
$connections = array();
if ($resql) {
    while ($obj = $db->fetch_object($resql)) {
        $connections[] = $obj;
    }
}

$connection->ref = '';
$connection->label='';
$connection->host='';
$connection->access_key='';
$connection->secret_key='';
$connection->service='';
$connection->billing_service='';
$connection->control_panel='';
$connection->color='#2563eb';
$connection->note='';
if ($action === 'edit') {
    $connection->fetch(GETPOST('id','int'));
}

llxHeader('', 'Backup supervise - Configuration');
backup_supervise_load_assets();

print load_fiche_titre('Backup supervise - Connexions S3');
print '<div class="section">';
print '<div class="columns">';
print '<div class="column is-two-thirds">';
print '<table class="table is-fullwidth is-striped">';
print '<thead><tr><th>Nom</th><th>Endpoint</th><th>Service</th><th>Couleur</th><th></th></tr></thead><tbody>';
foreach ($connections as $conn) {
    print '<tr>';
    print '<td>'.dol_escape_htmltag($conn->label).'</td>';
    print '<td>'.dol_escape_htmltag($conn->host).'</td>';
    print '<td>'.dol_escape_htmltag($conn->billing_service).'</td>';
    print '<td><span class="tag" style="background:'.dol_escape_htmltag($conn->color).';color:#fff">'.dol_escape_htmltag($conn->color).'</span></td>';
    print '<td class="has-text-right">';
    print '<a class="button is-small" href="?action=edit&id='.$conn->rowid.'">Modifier</a> ';
    print '<a class="button is-small is-danger" href="?action=delete&id='.$conn->rowid.'">Supprimer</a>';
    print '</td></tr>';
}
print '</tbody></table>';
print '</div>';
print '<div class="column">';
print '<div class="box">';
print '<h3 class="title is-5">'.($action==='edit'?'Modifier':'Nouvelle').' connexion</h3>';
print '<form method="POST">';
print '<input type="hidden" name="action" value="save">';
print '<input type="hidden" name="id" value="'.(int)$connection->id.'">';
print '<div class="field"><label class="label">Référence</label><div class="control"><input class="input" name="ref" value="'.dol_escape_htmltag($connection->ref).'" required></div></div>';
print '<div class="field"><label class="label">Label</label><div class="control"><input class="input" name="label" value="'.dol_escape_htmltag($connection->label).'" required></div></div>';
print '<div class="field"><label class="label">Endpoint</label><div class="control"><input class="input" name="host" value="'.dol_escape_htmltag($connection->host).'" required></div></div>';
print '<div class="field"><label class="label">Access key ID</label><div class="control"><input class="input" name="access_key" value="'.dol_escape_htmltag($connection->access_key).'" required></div></div>';
print '<div class="field"><label class="label">Secret access key</label><div class="control"><input class="input" type="password" name="secret_key" value="'.dol_escape_htmltag($connection->secret_key).'" required></div></div>';
print '<div class="field"><label class="label">Nom du service</label><div class="control"><input class="input" name="service" value="'.dol_escape_htmltag($connection->service).'" placeholder="s3"></div></div>';
print '<div class="field"><label class="label">Service pour facturation</label><div class="control"><input class="input" name="billing_service" value="'.dol_escape_htmltag($connection->billing_service).'" placeholder="PRESTATION-S3"></div></div>';
print '<div class="field"><label class="label">Panneau de contrôle</label><div class="control"><input class="input" name="control_panel" value="'.dol_escape_htmltag($connection->control_panel).'" placeholder="https://console.example"></div></div>';
print '<div class="field"><label class="label">Note</label><div class="control"><textarea class="textarea" name="note">'.dol_escape_htmltag($connection->note).'</textarea></div></div>';
print '<div class="field"><label class="label">Couleur</label><div class="control"><input class="input" type="color" name="color" value="'.dol_escape_htmltag($connection->color ?: '#2563eb').'"></div></div>';
print '<div class="field"><div class="control"><button class="button is-primary" type="submit">Enregistrer</button></div></div>';
print '</form>';
print '</div>';
print '</div>';
print '</div>';
print '</div>';

llxFooter();
