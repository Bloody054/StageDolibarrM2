<?php
// Nécessaire pour inclure l'environnement Dolibarr
$res = @include '../../main.inc.php';
if (! $res) $res = @include '../../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
dol_include_once('/dolitour/class/dolitour.class.php'); 

// Vérifie que l'utilisateur est connecté
if (empty($user->id)) {
    http_response_code(401);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

$action = '';
if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

// Gestion du Drag & Drop 
if ($action == 'update_rank') {
    $rows = GETPOST('row', 'array'); 

    if (is_array($rows) && count($rows) > 0) {
        $db->begin();
        $error = 0;
        
        foreach ($rows as $rank => $id) {
            $new_rank = $rank + 1;
            $sql = "UPDATE ".MAIN_DB_PREFIX."dolitour SET rank = ".$new_rank." WHERE rowid = ".((int)$id);
            if (!$db->query($sql)) $error++;
        }

        if (!$error) {
            $db->commit();
            echo "OK";
        } else {
            $db->rollback();
            echo "KO";
        }
    }
    exit; 
}

// Gestion fermeture Driver 
if ($action === 'driver_closed') {
    $extrafield_name = 'driver_closed';
    $u = new User($db);
    if ($u->fetch($user->id) > 0) {
        $u->array_options['options_' . $extrafield_name] = 1;
        $result = $u->insertExtraFields();

        if ($result > 0) echo json_encode(['success' => true]);
        else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update extrafield']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
    }
    exit; 
}

// Gestion Traçabilité 
if ($action == 'mark_as_read') {
    // On récupère l'ID de la même façon manuelle pour être sûr
    $id = 0;
    if (isset($_REQUEST['id'])) $id = (int)$_REQUEST['id'];
    
    if ($id > 0) {
        $object = new DoliTour($db);
        $object->id = $id;
        
        $result = $object->markAsRead($user->id);

        if ($result > 0) {
            echo "OK";
        } else {
            error_log("DoliTour MarkAsRead Error: " . $object->error); 
            echo "KO " . $object->error;
        }
    } else {
        echo "KO ID Missing";
    }
    exit; 
}


// Si on arrive ici, on affiche TOUT ce que PHP voit pour comprendre
http_response_code(400);
echo json_encode([
    'error' => 'Invalid action', 
    'received_action' => $action, 
    'debug_GET' => $_GET,       // Voir ce qu'il y a dans l'URL
    'debug_POST' => $_POST,     // Voir ce qu'il y a dans le corps
    'debug_REQUEST' => $_REQUEST // Voir la fusion des deux
]);
?>