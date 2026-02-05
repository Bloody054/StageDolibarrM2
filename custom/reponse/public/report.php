<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 Julien Marchand <julien.marchand@iouston.com>
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     	\file       htdocs/reponse/public/index.php
 *		\ingroup    core
 */

define('NOREQUIREMENU', 1);
define('NOLOGIN', 1);

$res=@include("../../main.inc.php");                   // For root directory
if (! $res) $res=@include("../../../main.inc.php");    // For "custom" directory

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

dol_include_once("/reponse/class/site.class.php");
dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/questionnaire/class/questionnaire.class.php");
dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
dol_include_once("/questionnaire/lib/questionnaire.lib.php");
dol_include_once("/questionnaire/class/questionnaire.action.class.php");

$langs->loadLangs(array('main', 'errors'));
$langs->load('reponse@reponse');
$langs->load("other");

$id = GETPOST('id', 'int') ? GETPOST('id', 'int') : 0;
$current = GETPOST('current', 'alpha');

$error = 0;

$questionnaire = new Questionnaire($db);
$questionnaireform = new QuestionnaireForm($db);

$site = new Site($db);
$site->start($user);

$reponse = new Reponse($db);

if ($id > 0 && $reponse->fetch($id) < 0)
{
    $error++;
    $site->addError($langs->trans('ReponseNotFound'));
}

if (!$error && $id > 0 && $reponse->user_author_id != $user->id)
{
    $error++;
    $site->addError($langs->trans('AccessNotAllowedUserID'));
}



if (GETPOST('action') == 'create')
{
    $ref_form = GETPOST('form-ref', 'alpha');
    $fk_questionnaire = 0;

    if (empty($ref_form)) {
        // Get default form
        $fk_questionnaire = $questionnaire->get_default();
    }

    if ($questionnaire->fetch($fk_questionnaire, $ref_form) > 0)
    {
        
        //Le questionnaire est-il activé ?
        if($questionnaire->active==0){
            $error++;
            $site->addError($langs->trans('FormIsDisabled'));
        }

        //Le questionnaire n'est pas en accès libre ?
        if($questionnaire->needtobeconnected==1 && empty($user->rights->reponse->lire)){
            $error++;
            $site->addError($langs->trans('AccessNeedToBeConnected'));
            $site->addError($langs->trans('AccessNeedReadRights'));

        }

        
                
        //apply css of form
        $questionnaire->addFormCSS($questionnaire);

        $reponse->fk_soc = $user->socid;
		$reponse->fk_questionnaire = $questionnaire->id;
        $reponse->is_draft = 1;
        $reponse->origin = GETPOST('origin', 'alpha');
        $reponse->origin_id = GETPOST('origin_id', 'int');

        if ($reponse->create($user) < 0) {
            $error++;
            $site->addError($langs->trans('ErrorWhileCreatingReponse'));
        } else {
            $reponse->fetch($reponse->id);

            $_SESSION['values'] = array();

            $lines = $reponse->lines;

            if (count($lines))
            {
                foreach ($lines as $code => $line)
                {
                    if (empty($line->inapp))
                    {
                        continue;
                    }

                    $prefill = $line->prefill;
                    $value = $questionnaireform->getPrefillValue($prefill, $user);

                    $values = isset($_SESSION['values']) ? $_SESSION['values'] : array();                    
                    $values[$line->code] = $value;
                    $_SESSION['values'] = $values;

                    if (!empty($value)) {
                        switch ($line->type) {

                            case 'date':
                            case 'datetime':        
                                $value = $db->idate($value);
                            break;
                            
                            case 'table':
                                $value = intval($value);
                            break;
                        }
    
                        $uncrypted_value = $value;
    
                        if ($line->crypted > 0)
                        {
                            $num_bytes = !empty($conf->global->FORMULAIRE_NUM_BYTES) ? intval($conf->global->FORMULAIRE_NUM_BYTES) : 10;
                
                            $key = bin2hex(random_bytes($num_bytes));
                
                            $value = mb_dol_encode($value, $key);
                            $value = $key.':'.$value;
                        }
    
                        $l = new ReponseLine($db);
                        $l->fk_reponse   = $reponse->id;
                        $l->code 			 = $line->code;
                        $l->value            = $value;
    
                        $result = $l->update($user);
                    }
                }

                $reponse->fetch($reponse->id);

                // Notifications
                $reponse->call_trigger('REPONSE_FILL_WEB', $user);
            }
        }
            
    }
    else
    {
        $error++;
        $site->addError($langs->trans('ReponseNoFormFound'));
    }
}


// Compute pages
$pages = array();

$next = GETPOSTISSET('next');
$previous = GETPOSTISSET('previous');

$direction = $previous ? -1 : 1;

if (!$error) 
{
    $lines = $reponse->lines;


    if (GETPOST('action') == 'deletefile') 
    {
        $next = false;
        $direction = 0;

		$urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);				// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
		$upload_dir = $conf->reponse->dir_output . '/' . $reponse->ref;

		$file = $upload_dir . '/' . $urlfile;
        $line = isset($reponse->lines[$current]) ? $reponse->lines[$current] : null;

		if ($urlfile && $line)
		{
            $values = $line->value ? explode(',', $line->value) : array();

            foreach ($values as $k => $val) {
                if ($val == $urlfile) {
                    unset($values[$k]);
                }
            }

            $value = count($values) ? implode(',', $values) : '';
            $line->value = $value;
            $result = $reponse->updateline($line);
            
            $reponse->fetch_lines();
            $lines = $reponse->lines;

            $ret = dol_delete_file($file, 0, 0, 0, $reponse);

            if ($ret) {
                $site->addMessage($langs->trans("FileWasRemoved", $urlfile));

                $values = isset($_SESSION['values']) ? $_SESSION['values'] : array();
                $values[$line->code] = $value;
                $_SESSION['values'] = $values;

            } else {
                $site->addError($langs->trans("ErrorFailToDeleteFile", $urlfile));
            }
		}
    }

    if ($next)
    {        
        $line = $lines[$current] ?? null;
        
        if ($line) 
        {
            // Process value
            $values = $_SESSION['values'] ?? array();

            $value = '';

            if ($line->type == 'file') {
                list($value, $uncrypted_value) = $questionnaire->processline($line);

                $l = new ReponseLine($db);
                $l->fk_reponse   = $reponse->id;
                $l->code 			 = $line->code;
                $l->value            = $value;
                $result = $l->update($user);
            } else {

                switch ($line->type) {

                    case 'date':
                    case 'datetime':
                        $hour = GETPOST($line->code.'hour', 'int');
                        $minute = GETPOST($line->code.'min', 'int');
                        $day = GETPOST($line->code.'day', 'int');
                        $month = GETPOST($line->code.'month', 'int');
                        $year = GETPOST($line->code.'year', 'int');

                        $values[$line->code.'hour'] = $hour;
                        $values[$line->code.'min'] = $minute;
                        $values[$line->code.'day'] = $day;
                        $values[$line->code.'month'] = $month;
                        $values[$line->code.'year'] = $year;

                        $value = dol_mktime($hour, $minute, 0, $month, $day, $year);
                    break;
    
                    case 'checkbox':
                        $value = GETPOST($line->code, 'array');
                    break;

                    case 'table':
                    case 'int':
                        $value = GETPOST($line->code, 'int');
                    break;

                    case 'numeric':
                        $value = GETPOST($line->code, 'alpha');
                        $value = price2num($value);
                        break;
                    
                    case 'siret':
                        
                        $value = GETPOST($line->code, 'alpha');
                        $value = preg_replace('/\s+/', '', $value); //nettoyage de tous les espaces éventuels.
                        $dummy = $reponse->sendSiretRequest($value,'q');

                        if (is_array($dummy) && count($dummy)) {
                            foreach ($dummy as $key => $val) {
                                $newkey = $line->type.'.'.$key;
                                $values[$newkey] = $val;
                            }
                        }

                        break;
                    case QuestionnaireAction::SOCIETE_TYPE:
                    case QuestionnaireAction::PROJECT_TYPE:

                        $value = GETPOST($line->code, 'alpha');
                        $value = preg_replace('/\s+/', '', $value); //nettoyage de tous les espaces éventuels.

                        if (!empty($value)) {
                            if ($line->type == QuestionnaireAction::SOCIETE_TYPE) {
                                $obj = new Societe($db);
                            } else {
                                $obj = new Project($db);
                            }

                            $field = preg_replace('/\s+/', '', $line->param); //nettoyage de tous les espaces éventuels.
                            if(empty($field)){$field='rowid';}           
                            
                            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$obj->table_element." WHERE ".$field." LIKE '".$db->escape($value)."'";

                            $result = $db->query($sql);
                            if ($result) {
                                $num = $db->num_rows($result);
                                


                                if ($num) {
                                    $objp = $db->fetch_object($result);
                                    $id = $objp->rowid;
                                    $obj->fetch($id);

                                    $questionnaireaction = new QuestionnaireAction($db);
                                    $questionnaireaction->type = $line->type;
                                    $fields = $questionnaireaction->getFields();                    
                                                                        
                                    foreach ($fields as $key => $field) {
                                        $newkey = $line->type.'.'.$key;
                                        $values[$newkey] = $obj->{$key};
                                        
                                        //Forcer rowid = id
                                        if($key=='rowid'){
                                            $values[$line->type.'.rowid']= $obj->id;
                                        }
                                    }

                                }
                            }
                        }

                        break;

                    case 'radio':
                    case 'list':
                    default:
                        $value = GETPOST($line->code, 'alpha');
                    break;
                    case 'sign':
                        $data = GETPOST('signature_png');
                        if (preg_match('/^data:image\/png;base64,/', $data)) {
                            $data = substr($data, strpos($data, ',') + 1);
                            $data = base64_decode($data);
                            $filename = strtolower($reponse->ref).'_'.$line->code.'.png';
                            $upload_dir = $conf->reponse->dir_output . '/' . $reponse->ref;
                            $path=$upload_dir.'/'.$filename;
                            if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true) && !is_dir($upload_dir)) {
                                throw new RuntimeException('Impossible de créer le dossier : ' . $upload_dir);
                            }
                            file_put_contents($path, $data);
                            $value=$filename;
                            
                        }
                    break;
                    case 'productmultiple':
                    //On réassemble chaque couple produit/qty dans un tableau
                    $productmultiple=array();
                    foreach ($_POST as $key => $value) {
                        if (preg_match('/^' . preg_quote($line->code, '/') . '_(\d+)$/', $key, $m)) {
                            $idx  = (int) $m[1];

                            $prod = trim(GETPOST($line->code . '_' . $idx, 'int'));
                            $qty  = trim(GETPOST('qty_' . $idx, 'int'));

                            if ($prod && $qty) { // suffit, car int 0 est false
                                if (empty($productmultiple[$prod])) {
                                    $productmultiple[$prod] = 0;
                                }
                                $productmultiple[$prod] += $qty;
                            }
                        }
                    }
                    
                        $value = json_encode($productmultiple);
                                                                                                                                                
                    break;
                }

            }


            $notfilled = false;
            if ($line->mandatory > 0) {
                switch ($line->type) {
                    case 'table':
                        $notfilled = $value < 1;
                    break;
    
                    case 'date':
                    case 'datetime':
                        $notfilled = empty($value);
                    break;
    
                    default:
                        $notfilled = $value === "";
                    break;
                }
            }
                        

            if ($notfilled) {
                $site->addError($langs->trans('ReponseFieldIsMandatory'));
                $direction = 0;
            } else {
                $values[$line->code] = $value;
            }
            
            $_SESSION['values'] = $values;
        }
    }

    if (count($lines))
    {

        foreach ($lines as $code => $line)
        {
            if (empty($line->inapp))
            {
                continue;
            }
            // Process value
            $prefill = $line->prefill;
            $value = $questionnaireform->getPrefillValue($prefill, $user);
                       
            $addpage = empty($value);

            if ($line->fk_op_cond > 0 && $line->fk_op_cond == Questionnaire::CONDITION_ALWAYS) {
                $addpage = true;
            }

            if ($addpage) 
            {
                $pages[$code] = $line;
            }
        }
    }

}

$tpl = 'tpl/form/empty.tpl.php';

$displayPreviousButton = false;
$line = null;
$values = $_SESSION['values'] ?? array();

$progress = 0;
if (count($pages) > 0) {
    
    $codes = array_keys($pages);
    
    if (empty($current)) {
        $idx = -1;
    } else {
        $idx = array_search($current, $codes) !== false ? array_search($current, $codes) : -1;
    }
    
    $conditionSatisfied = false;

    $submit = false;

    while (!$conditionSatisfied) {
        $idx += $direction;

        if ($idx < 0) {
            $idx = 0;
            break;
        } else if ($idx >= count($codes)) {
            $submit = true;
            break;    
        } else {
            $current = $codes[$idx];
            $line = $pages[$current];

            $conditionSatisfied = $questionnaireform->isConditionSatisfied($reponse, $line, $values);
        }
    }


    if ($submit) {
        // Submit form

        if (count($values) > 0) {
            foreach ($values as $code => $value) {
                $_POST[$code] = $value;
            }
        }

        $result = $reponse->fill($user);

        if ($result > 0) {

            $reponse->is_draft = 0;
            $reponse->update($user);

            if(!empty($reponse->questionnaire->customconfirmmessage)){
                $message = $reponse->questionnaire->customconfirmmessage;
                $message .= '<br>'.$langs->trans('ReponseYourRef').' '.$reponse->ref;
            }else{
                $message = $langs->trans('ReponseHasBeenCreated', $reponse->ref);
            }
            
            $site->addMessage(str_replace('\n', "<br />", $message));

            $reportsIds = isset($_SESSION['dol_reports']) ? $_SESSION['dol_reports'] : array();
            $reportsIds[] = $reponse->id;
            $_SESSION['dol_reports'] = $reportsIds;

        } else {
            $errors = $reponse->errors;
            if (count($errors) > 0) {
                $site->addError(implode('<br />', $errors));
            }
        }
        
        //Redirection après soumission du fomulaire
        $aftersubmission = (!empty($reponse->questionnaire->aftersubmission) ? $reponse->questionnaire->aftersubmission : 'home');
        
        if($aftersubmission=='home'){
            $url = $site->makeUrl('index.php');
        }else if($aftersubmission=='sameform'){
            $url = $site->makeUrl('report.php?entity='.$reponse->entity.'&action=create&form-ref='.$reponse->questionnaire->ref);
        }else if($aftersubmission=='custompage'){
            $url=$reponse->questionnaire->aftersubmissioncustompage;
        }
        header("Location: ".$url);
        exit;

    } else {
        if (isset($codes[$idx])) {

            $progress = array_search($idx, array_keys($codes)) !== false ? array_search($idx, array_keys($codes)) : 0;
            $progress = round(100 * $progress / count($codes));

            $displayPreviousButton = $idx > 0;

            $https://github.com/Properdol/connectcurrent = $codes[$idx];
            $line = $pages[$current];
            $line->value = $values[$line->code] ?? GETPOST($line->code, 'nohtml');

            if ($line->postfill) {
                $line->value = $questionnaireform->getPostFillValue($line->postfill, $user, $values);
            }

            $tpl = 'tpl/form/'.strtolower($line->type).'.tpl.php';
        }
    } 
}

?>
<?php $reponse->include_once('tpl/layouts/header.tpl.php'); ?>
<?php $reponse->include_once('tpl/layouts/error.tpl.php'); ?>

<?php 
if($reponse->fk_questionnaire>0){
    $questionnaire=$reponse->questionnaire;
    //apply css of form
    $questionnaire->addFormCSS($questionnaire);

}
$reponse->include_once($tpl, array(
    'line' => $line,
    'reponse' => $reponse,
    'current' => $current,
    'displayPreviousButton' => $displayPreviousButton,
    'progress' => $progress,
)); 

?>

<?php $reponse->include_once('tpl/layouts/footer.tpl.php',array('questionnaire'=>$questionnaire)); ?>