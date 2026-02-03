<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2024 Julien Marchand <julien.marchand@iouston.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more detaile.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/questionnaire/class/questionnaire.class.php
 *  \ingroup    questionnaire
 *  \brief      File of class to manage questionnaires
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/security.lib.php';

dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/questionnaire/lib/questionnaire.lib.php");
dol_include_once("/questionnaire/class/questionnaire.action.class.php");

/**
 * Class to manage products or services
 */
class Questionnaire extends CommonObject
{
    public $element = 'questionnaire';
    public $table_element = 'questionnaire';
    public $table_element_line = 'questionnairedet';

    public $fk_element = 'fk_questionnaire';
    public $picto = 'questionnaire@questionnaire';
    public $ismultientitymanaged = 1;    // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $geocoder = 'https://nominatim.openstreetmap.org/reverse?format=json&zoom=%d&addressdetails=1';
    var $geodecoder = 'https://nominatim.openstreetmap.org/search?format=json&addressdetails=1';
    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'rowid';


    /**
     * Gestion id
     * @var int
     */
    public $id = 0;

    /**
     * Reference
     * @var string
     */
    public $ref;

    /**
     * Titre
     * @var string
     */
    public $title;

    /**
     * Creation date
     * @var int
     */
    public $datec;

    /**
     * Author id
     * @var int
     */
    public $user_author_id = 0;

    /**
     * Active
     * @var int
     */
    public $active = 1;

    /**
     * Need To Be Connected
     * @var int
     */
    public $needtobeconnected = 1;

    /**
     * Default
     * @var int
     */
    public $selected = 0;

    /**
     * Email line id
     * @var int
     */
    public $fk_email = 0;

    /**
     * Name line id
     * @var int
     */
    public $fk_name = 0;

    /**
     * Email model id
     * @var int
     */
    public $fk_confirmation_email_model = 0;

    /**
     * Email model id
     * @var int
     */
    public $fk_notification_email_model = 0;

    /**
     * Usergroup id
     * @var int
     */
    public $fk_notification_usergroup = 0;

    /**
     * Date line id
     * @var int
     */
    public $fk_date = 0;

    /**
     * Location line id
     * @var int
     */
    public $fk_location = 0;

    /**
     * Email line label
     * @var string
     */
    public $email_label = null;

    /**
     * Date line label
     * @var string
     */
    public $date_label = null;

    /**
     * Name line label
     * @var string
     */
    public $name_label = null;

    /**
     * Location line label
     * @var string
     */
    public $location_label = null;


    public $nb_answer = 0;

    /**
     * Entity
     * @var int
     */
    public $entity;

    /**
     * Progressbar
     * @var int
     */
    public $progressbar = 1;

    /**
     * Background
     * @var string
     */
    public $background = '';

    /**
     * Color accent 
     * @var string
     */
    public $coloraccent = '';

    /**
     * Button Background 
     * @var string
     */
    public $buttonbackground = '';

    /**
     * Button Background Hover 
     * @var string
     */
    public $buttonbackgroundhover = '';

    /**
     * Footer description 
     * @var string
     */
    public $footerdescription = '';

    /**
     * Custom Css 
     * @var string
     */
    public $customcss = '';

    /**
     * ProgressBar Duration 
     * @var int
     */
    public $progressbar_duration = '';

    /**
     * AfterSubmission 
     * @var string
     */
    public $aftersubmission = 'home';

    /**
     * AfterSubmissionCustomPage
     * @var string
     */
    public $aftersubmissioncustompage = '';

    /**
     * CustomConfirmMessage
     * @var string
     */
    public $customconfirmmessage = '';

    /**
     * Icon
     * @var string
     */
    public $icon = '';

    /**
     * After submission choices 
     * @var string static
     * defined in __construct()
     */
    public static $after_submission_choices;

    /**
     * @var QuestionnaireLine[]
     */
    public $lines = array();

    const CONDITION_LESS = 1;
    const CONDITION_LESS_OR_EQUAL = 2;
    const CONDITION_EQUAL = 3;
    const CONDITION_GREATER = 4;
    const CONDITION_GREATER_OR_EQUAL = 5;
    const CONDITION_DIFFERENT = 6;
    const CONDITION_EMPTY = 7;
    const CONDITION_NOT_EMPTY = 8;
    const CONDITION_ALWAYS = 9;
    const CONDITION_NOT_EMPTY_DISPLAYED = 10;
    const CONDITION_EMPTY_DISPLAYED = 11;
    const HELP_LINK = 'https://www.iouston.com/nos-prestations/modules-dolibarr/documentation-technique/questionnaire-reponse/';
    
    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    function __construct($db)
    {
        global $langs;
        $this->after_submission_choices=[
                                'home'=>$langs->trans('GoToHome'),
                                'sameform'=>$langs->trans('GoToSameForm'),
                                'custompage'=>$langs->trans('GoToCustomPage'),
                                    ];
        $this->db = $db;
    }

    /**
     *    Insert form into database
     *
     * @param User $user User making insert
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int                        Id of gestion if OK, < 0 if KO
     */
    function create($user, $notrigger = 0)
    {
        global $conf, $langs, $mysoc;

        $error = 0;

        dol_syslog(get_class($this) . "::create", LOG_DEBUG);

        $this->db->begin();

        $this->datec = dol_now();
        $this->entity = $conf->entity;
        $this->user_author_id = $user->id;
        $this->ref = $this->getNextNumRef($mysoc);

        $now = dol_now();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "questionnaire (";
        $sql .= " ref";
        $sql .= " , title";
        $sql .= " , datec";
        $sql .= " , user_author_id";
        $sql .= " , active";
        $sql .= " , needtobeconnected";
        $sql .= " , selected";
        $sql .= " , fk_email";
        $sql .= " , fk_confirmation_email_model";
        $sql .= " , fk_notification_email_model";
        $sql .= " , fk_notification_usergroup";
        $sql .= " , progressbar";
        $sql .= " , progressbar_duration";
        $sql .= " , background";
        $sql .= " , coloraccent";
        $sql .= " , buttonbackground";
        $sql .= " , buttonbackgroundhover";
        $sql .= " , footerdescription";
        $sql .= " , customcss";
        $sql .= " , fk_name";
        $sql .= " , fk_date";
        $sql .= " , fk_location";
        $sql .= " , entity";
        $sql .= " , tms";
        $sql .= " , aftersubmission";
        $sql .= " , aftersubmissioncustompage";
        $sql .= " , customconfirmmessage";
        $sql .= " , icon";
        $sql .= ") VALUES (";
        $sql .= " " . (!empty($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : "null");
        $sql .= ", " . (!empty($this->title) ? "'" . $this->db->escape($this->title) . "'" : "null");
        $sql .= ", " . (!empty($this->datec) ? "'" . $this->db->idate($this->datec) . "'" : "null");
        $sql .= ", " . (!empty($this->user_author_id) ? $this->user_author_id : "0");
        $sql .= ", " . (!empty($this->active) ? $this->active : "0");
        $sql .= ", " . (!empty($this->needtobeconnected) ? $this->needtobeconnected : "1");
        $sql .= ", " . (!empty($this->selected) ? $this->selected : "0");
        $sql .= ", " . (!empty($this->fk_email) ? $this->fk_email : "0");
        $sql .= ", " . (!empty($this->fk_confirmation_email_model) ? $this->fk_confirmation_email_model : "0");
        $sql .= ", " . (!empty($this->fk_notification_email_model) ? $this->fk_notification_email_model : "0");
        $sql .= ", " . (!empty($this->fk_notification_usergroup) ? $this->fk_notification_usergroup : "0");
        $sql .= ", " . (!empty($this->progressbar) ? $this->progressbar : "1");
        $sql .= ", " . (!empty($this->progressbar_duration) ? $this->progressbar_duration : "1");
        $sql .= ", " . (!empty($this->background) ? $this->db->escape($this->background) : "null");
        $sql .= ", " . (!empty($this->coloraccent) ? $this->db->escape($this->coloraccent) : "null");
        $sql .= ", " . (!empty($this->buttonbackground) ? $this->db->escape($this->buttonbackground) : "null");
        $sql .= ", " . (!empty($this->buttonbackgroundhover) ? $this->db->escape($this->buttonbackgroundhover) : "null");
        $sql .= ", " . (!empty($this->footerdescription) ? $this->db->escape($this->footerdescription) : "null");
        $sql .= ", " . (!empty($this->customcss) ? $this->db->escape($this->customcss) : "null");
        $sql .= ", " . (!empty($this->fk_date) ? $this->fk_date : "0");
        $sql .= ", " . (!empty($this->fk_name) ? $this->fk_name : "0");
        $sql .= ", " . (!empty($this->fk_location) ? $this->fk_location : "0");
        $sql .= ", " . (!empty($this->entity) ? $this->entity : "0");
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ", " . (!empty($this->aftersubmission) ? "'".$this->aftersubmission."'" : "'home'");
        $sql .= ", " . (!empty($this->aftersubmissionpage) ? $this->db->escape($this->aftersubmissionpage) : "null");
        $sql .= ", " . (!empty($this->customconfirmmessage) ? $this->db->escape($this->customconfirmmessage) : "null");
        $sql .= ", " . (!empty($this->icon) ? $this->db->escape($this->icon) : "null");
        $sql .= ")";

        dol_syslog(get_class($this) . "::Create", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX . "questionnaire");

            if ($id > 0) {
                $this->id = $id;

                $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "questionnaireval".$this->id." (`rowid` int(11)  AUTO_INCREMENT, `fk_reponse` int(11) DEFAULT 0,`tms` timestamp NOT NULL, PRIMARY KEY (`rowid`))ENGINE=innodb DEFAULT CHARSET=utf8;";
                $resql = $this->db->query($sql);

                $sql = "CREATE TABLE IF NOT EXISTS " . MAIN_DB_PREFIX . "questionnairefval".$this->id." (`rowid` int(11)  AUTO_INCREMENT, `fk_reponse` int(11) DEFAULT 0,`tms` timestamp NOT NULL, PRIMARY KEY (`rowid`))ENGINE=innodb DEFAULT CHARSET=utf8;";
                $resql = $this->db->query($sql);

            } else {
                $error++;
                $this->error = 'ErrorFailedToGetInsertedId';
            }
        } else {
            $error++;
            $this->error = $this->db->lasterror();
        }

        if (!$error) {
            $result = $this->insertExtraFields();
            if ($result < 0) $error++;
        }


        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_CREATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if (!$error) {
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -$error;
        }

    }

    /**
     *    Insert form into database
     *
     * @param User $user User making insert
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     *
     * @return int                        Id of gestion if OK, < 0 if KO
     */
    function clone($user, $notrigger = 0)
    {
        global $conf, $langs, $mysoc;

        $error = 0;

        dol_syslog(get_class($this) . "::clone", LOG_DEBUG);

        $result = $this->create($user, $notrigger);

        if ($result < 0) {
            $error++;
        }


        $repl_ids = array();

        if (!$error) {
            $lines = $this->lines;

            foreach ($lines as $line) {
                $old_id = intval($line->rowid);

                $line->fk_questionnaire = $this->id;


                $result = $line->clone($user, $notrigger);
                if ($result < 0) {
                    $error++;
                } else {
                    $repl_ids[$old_id] = $line->rowid;
                }
            }

            if (!$error) {
                $this->update($user, 1);

                $this->fetch_lines();

                if (count($this->lines) > 0) {
                    foreach ($this->lines as $line) {
                        $staticline = clone $line;

                        $line->oldline = $staticline;

                        $fk_cond = intval($line->fk_cond);
                        $line->fk_cond = isset($repl_ids[$fk_cond]) ? $repl_ids[$fk_cond] : 0;

                        $res = $line->update($user, 1);
                    }
                }
            }
        }

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_CLONE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if (!$error) {
            return $this->id;
        } else {
            return -$error;
        }
    }

    /**
     *    Update a record into database.
     *
     * @param User $user Object user making update
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return int                1 if OK, -1 if ref already exists, -2 if other error
     */
    function update($user, $notrigger = 0)
    {
        global $langs, $conf, $hookmanager;

        $error = 0;


        // Clean parameters
        $id = $this->id;

        // Check parameters
        if (empty($id)) {
            $this->error = "Object must be fetched before calling update";
            return -1;
        }


        $this->db->begin();

        $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnaire";
        $sql .= " SET ref = " . (!empty($this->ref) ? "'" . $this->db->escape($this->ref) . "'" : "null");
        $sql .= ", title = " . (!empty($this->title) ? "'" . $this->db->escape($this->title) . "'" : "null");
        $sql .= ", active = " . (!empty($this->active) ? $this->active : "0");
        $sql .= ", needtobeconnected = " . ($this->needtobeconnected==0 ? $this->needtobeconnected : "1");
        $sql .= ", selected = " . (!empty($this->selected) ? $this->selected : "0");
        $sql .= ", fk_email = " . (!empty($this->fk_email) ? $this->fk_email : "0");
        $sql .= ", fk_confirmation_email_model = " . (!empty($this->fk_confirmation_email_model) ? $this->fk_confirmation_email_model : "0");
        $sql .= ", fk_notification_email_model = " . (!empty($this->fk_notification_email_model) ? $this->fk_notification_email_model : "0");
        $sql .= ", fk_notification_usergroup = " . (!empty($this->fk_notification_usergroup) ? $this->fk_notification_usergroup : "0");
        $sql .= ", progressbar = " . (!empty($this->progressbar) ? $this->progressbar : "0");
        $sql .= ", progressbar_duration = " . (!empty($this->progressbar_duration) ? "'" . $this->db->escape($this->progressbar_duration)."'" : "1");
        $sql .= ", background = " . (!empty($this->background) ? "'" .$this->db->escape($this->background)."'" : "null");
        $sql .= ", coloraccent = " . (!empty($this->coloraccent) ? "'" .$this->db->escape($this->coloraccent)."'" : "null");
        $sql .= ", buttonbackground = " . (!empty($this->buttonbackground) ? "'" .$this->db->escape($this->buttonbackground)."'" : "null");
        $sql .= ", buttonbackgroundhover = " . (!empty($this->buttonbackgroundhover) ? "'" .$this->db->escape($this->buttonbackgroundhover)."'" : "null");
        $sql .= ", footerdescription = " . (!empty($this->footerdescription) ? "'" .$this->db->escape($this->footerdescription)."'" : "null");
        $sql .= ", customcss = " . (!empty($this->customcss) ? "'" .$this->db->escape($this->customcss)."'" : "null");
        $sql .= ", fk_date = " . (!empty($this->fk_date) ? $this->fk_date : "0");
        $sql .= ", fk_name = " . (!empty($this->fk_name) ? $this->fk_name : "0");
        $sql .= ", fk_location = " . (!empty($this->fk_location) ? $this->fk_location : "0");
        $sql .= ", tms = '" . $this->db->idate(dol_now()) . "'";
        $sql .= ", aftersubmission = " . (!empty($this->aftersubmission) ? "'" .$this->db->escape($this->aftersubmission)."'" : "home");
        $sql .= ", aftersubmissioncustompage = " . (!empty($this->aftersubmissioncustompage) ? "'" .$this->db->escape($this->aftersubmissioncustompage)."'" : "null");
        $sql .= ", customconfirmmessage = " . (!empty($this->customconfirmmessage) ? "'" .$this->db->escape($this->customconfirmmessage)."'" : "null");
        $sql .= ", icon = " . (!empty($this->icon) ? "'" .$this->db->escape($this->icon)."'" : "null");
        $sql .= " WHERE rowid = " . $id;
        
        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
                
        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_MODIFY', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            $this->db->commit();
            return 1;
        } else {
            $this->error = $langs->trans("Error") . " : " . $this->db->error() . " - " . $sql;
            $this->errors[] = $this->error;
            $this->db->rollback();

            return -1;
        }
    }

    /**
     *  Load a form in memory from database
     *
     * @param int $id Id of slide
     * @return int                        <0 if KO, 0 if not found, >0 if OK
     */
    function fetch($id, $ref = '')
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::fetch id=" . $id);


        // Check parameters
        if (!$id && !$ref) {
            $this->error = 'ErrorWrongParameters';
            //dol_print_error(get_class($this)."::fetch ".$this->error);
            return -1;
        }

        $sql = "SELECT e.rowid, e.ref, e.datec, e.title, e.user_author_id, e.active, e.needtobeconnected, e.selected, e.entity, ";
        $sql .= " e.fk_email, e.fk_name, e.fk_date, e.fk_location, e.fk_notification_email_model, e.fk_confirmation_email_model, e.fk_notification_usergroup, e.progressbar, e.progressbar_duration, e.background, e.coloraccent, e.buttonbackground, e.buttonbackgroundhover, e.footerdescription, e.customcss, e.aftersubmission, e.aftersubmissioncustompage, e.customconfirmmessage, e.icon,";
        $sql .= " qde.label as email_label, qdd.label as date_label, qdn.label as name_label, qdl.label as location_label ";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire e";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnairedet as qde ON e.fk_email = qde.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnairedet as qdd ON e.fk_date = qdd.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnairedet as qdn ON e.fk_name = qdn.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnairedet as qdl ON e.fk_location = qdl.rowid";

        if ($id) $sql .= " WHERE e.rowid = " . $id;
        else $sql .= " WHERE e.entity IN (" . getEntity('questionnaire') . ")"; // Dont't use entity if you use rowid
        if ($ref) $sql .= " AND e.ref='" . $this->db->escape($ref) . "'";

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->user_author_id = $obj->user_author_id;
                $this->ref = $obj->ref;
                $this->datec = $this->db->jdate($obj->datec);
                $this->title = $obj->title;
                $this->active = $obj->active;
                $this->needtobeconnected = $obj->needtobeconnected;
                $this->selected = $obj->selected;
                $this->progressbar = $obj->progressbar;
                $this->progressbar_duration = $obj->progressbar_duration;
                $this->background = $obj->background;
                $this->buttonbackground = $obj->buttonbackground;
                $this->coloraccent = $obj->coloraccent;
                $this->buttonbackgroundhover = $obj->buttonbackgroundhover;
                $this->footerdescription = $obj->footerdescription;
                $this->customcss = $obj->customcss;

                $this->aftersubmission = $obj->aftersubmission;
                $this->aftersubmissioncustompage = $obj->aftersubmissioncustompage;
                $this->customconfirmmessage = $obj->customconfirmmessage;
                $this->icon = $obj->icon;

                $this->fk_notification_email_model = $obj->fk_notification_email_model;
                $this->fk_confirmation_email_model = $obj->fk_confirmation_email_model;
                $this->fk_notification_usergroup = $obj->fk_notification_usergroup;

                $this->fk_email = $obj->fk_email;
                $this->fk_date = $obj->fk_date;
                $this->fk_name = $obj->fk_name;
                $this->fk_location = $obj->fk_location;

                $this->entity = $obj->entity;

                $this->email_label = $obj->email_label;
                $this->name_label = $obj->name_label;
                $this->date_label = $obj->date_label;
                $this->location_label = $obj->location_label;

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                $this->db->free($resql);

                // Add total answers of this form
                $sql = "SELECT COUNT(r.rowid) as nb_answer";
                $sql .= " FROM " . MAIN_DB_PREFIX . "reponse r";
                $sql .= " WHERE fk_questionnaire=".$this->id;
                $resql = $this->db->query($sql);
                if ($resql) {
                    if ($this->db->num_rows($resql) > 0) {
                        $obj = $this->db->fetch_object($resql);
                        $this->nb_answer = $obj->nb_answer;
                    }
                }

                /*
				 * Lines
				 */
                $result = $this->fetch_lines();

                if ($result < 0) {
                    return -3;
                }

                return 1;

            } else {
                return 0;
            }
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    /**
     *  Delete a form from database (if not used)
     *
     * @param User $user
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return        int                    < 0 if KO, 0 = Not possible, > 0 if OK
     */
    function delete(User $user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        // Clean parameters
        $id = $this->id;

        // Check parameters
        if (empty($id)) {
            $this->error = "Object must be fetched before calling delete";
            return -1;
        }

        $this->db->begin();

        $sqlz = "DELETE FROM " . MAIN_DB_PREFIX . "questionnaire";
        $sqlz .= " WHERE rowid = " . $id;
        dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if (!$resultz) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        $sqlz = "DELETE FROM " . MAIN_DB_PREFIX . "questionnairedet";
        $sqlz .= " WHERE fk_questionnaire = " . $id;
        dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if (!$resultz) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_DELETE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }
        }

        if (!$error) {
            $this->db->commit();
            return 1;
        } else {
            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -$error;
        }

    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps

    /**
     *    Load array lines
     *
     * @return        int                        <0 if KO, >0 if OK
     */
    function fetch_lines()
    {
        // phpcs:enable
        $this->lines = array();

        $sql = "SELECT l.rowid, l.fk_questionnaire, l.code, l.label, l.type, l.postfill, l.prefill, l.rang, l.param, l.visibility,  l.mandatory, l.crypted, l.inapp, l.help, ";
        $sql .= " l.fk_cond, l.fk_op_cond, l.val_cond, l.datec, l.user_author_id, l.tms";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnairedet as l";
        $sql .= " WHERE l.fk_questionnaire = " . $this->id;
        $sql .= " ORDER BY l.rang";

        dol_syslog(get_class($this) . "::fetch_lines", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                $line = new QuestionnaireLine($this->db);

                $line->rowid = $objp->rowid;
                $line->id = $objp->rowid;
                $line->fk_questionnaire = $objp->fk_questionnaire;
                $line->code = $objp->code;
                $line->label = $objp->label;
                $line->type = $objp->type;
                $line->postfill = $objp->postfill;
                $line->prefill = $objp->prefill;

                $line->rang = $objp->rang;
                $line->param = $objp->param;
                $line->visibility = $objp->visibility;
                $line->mandatory = $objp->mandatory;
                $line->crypted = $objp->crypted;
                $line->inapp = $objp->inapp;

                $line->help = $objp->help;

                $line->fk_cond = $objp->fk_cond;
                $line->fk_op_cond = $objp->fk_op_cond;
                $line->val_cond = $objp->val_cond;

                $line->user_author_id = $objp->user_author_id;
                $line->datec = $this->db->jdate($objp->datec);
                $line->tms = $this->db->jdate($objp->tms);

                $this->lines[$i] = $line;

                $i++;
            }

            $this->db->free($result);

            return 1;
        } else {
            $this->error = $this->db->error();
            return -3;
        }
    }


    /**
     *    Add a line into database
     *
     * @param string $label Label
     * @param string $code Code
     * @param string $type Type
     * @param string $param Parameters
     * @param string $help Help
     *
     * @return     int                                >0 if OK, <0 if KO
     *
     */
    function addline($label, $code, $type, $postfill = '', $prefill = '', $param = '', $crypted = 0, $inapp = 0, $mandatory = 0, $help = '', $fk_cond = 0, $fk_op_cond = '', $val_cond = '', $visibility = 0, $notrigger = 0)
    {
        global $mysoc, $conf, $langs, $user;

        dol_syslog(get_class($this) . "::addline", LOG_DEBUG);

        $rang = 0;

        if (count($this->lines)) {
            foreach ($this->lines as $line) {
                $rang = max($rang, $line->rang);
            }
            $rang++;
        }

        // Insert line
        $this->line = new QuestionnaireLine($this->db);
        $this->line->context = $this->context;


        $this->line->code = $code;
        $this->line->type = $type;
        $this->line->param = $param;
        $this->line->crypted = $crypted;

        $this->line->fk_questionnaire = $this->id;
        $this->line->label = $label;
        $this->line->postfill = $postfill;
        $this->line->prefill = $prefill;
        $this->line->help = $help;
        $this->line->inapp = $inapp;
        $this->line->visibility = $visibility;
        $this->line->mandatory = $mandatory;
        $this->line->rang = $rang;

        $this->line->fk_cond = $fk_cond;
        $this->line->fk_op_cond = $fk_op_cond;
        $this->line->val_cond = $val_cond;

        $result = $this->line->insert($user, $notrigger);
        if ($result > 0) {
            $this->db->commit();
            return $this->line->rowid;
        } else {
            $this->error = $this->line->error;
            dol_syslog(get_class($this) . "::addline error=" . $this->error, LOG_ERR);
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *  Update a line in database
     *
     * @param int $rowid Id of line to update
     * @param string $label Label
     * @param string $code Code
     * @param string $type Type
     * @param string $param Parameters
     * @param string $help Help
     * @return    int                                < 0 if KO, > 0 if OK
     */
    function updateline($rowid, $label, $code, $type, $postfill = '', $prefill = '', $param = '', $crypted = 0, $inapp = 0, $mandatory = 0, $help = '', $fk_cond = 0, $fk_op_cond = '', $val_cond = '', $visibility = 0, $notrigger = 0)
    {
        

        global $conf, $mysoc, $langs, $user;

        dol_syslog(get_class($this) . "::updateline");

        $this->db->begin();


        //Fetch current line from the database and then clone the object and set it in $oldline property
        $line = new QuestionnaireLine($this->db);
        $line->fetch($rowid);

        $staticline = clone $line;

        $line->oldline = $staticline;

        $this->line = $line;
        $this->line->context = $this->context;

        $this->line->rowid = $rowid;

        $this->line->code = $code;
        $this->line->type = $type;
        $this->line->param = $param;
        $this->line->crypted = $crypted;

        $this->line->label = $label;
        $this->line->postfill = $postfill;
        $this->line->prefill = $prefill;
        $this->line->mandatory = $mandatory;
        $this->line->help = $help;
        $this->line->inapp = $inapp;
        $this->line->visibility = $visibility;

        $this->line->fk_cond = $fk_cond;
        $this->line->fk_op_cond = $fk_op_cond;
        $this->line->val_cond = $val_cond;

        $result = $this->line->update($user, $notrigger);

        if ($result > 0) {
            $this->db->commit();
            return $result;
        } else {
            $this->error = $this->line->error;

            $this->db->rollback();
            return -1;
        }

    }

    /**
     *  Delete a line
     *
     * @param User $user User object
     * @param int $lineid Id of line to delete
     * @return     int                    >0 if OK, 0 if nothing to do, <0 if KO
     */
    function deleteline($user = null, $lineid = 0, $notrigger = 0)
    {
        global $conf, $mysoc, $langs, $user;

        dol_syslog(get_class($this) . "::deleteline");

        $this->db->begin();

        $line = new QuestionnaireLine($this->db);
        $result = $line->fetch($lineid);

        if ($result > 0) {
            if ($line->delete($user, $notrigger) > 0) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                $this->error = $line->error;
                return -1;
            }
        } else {
            $this->db->rollback();
            $this->error = $this->db->lasterror();
            return -1;
        }

    }

    /**
     *    Create an array of form lines
     *
     * @return int        >0 if OK, <0 if KO
     */
    function getLinesArray()
    {
        return $this->fetch_lines();
    }

    /**
     *      \brief Return next reference of confirmation not already used (or last reference)
     * @param soc $soc objet company
     * @param mode $mode 'next' for next value or 'last' for last value
     * @return    string                  free ref or last ref
     */
    function getNextNumRef($soc, $mode = 'next')
    {
        global $conf, $langs;

        $langs->load("questionnaire@questionnaire");

        // Clean parameters (if not defined or using deprecated value)
        if (empty($conf->global->QUESTIONNAIRE_ADDON)) {
            $conf->global->QUESTIONNAIRE_ADDON = 'mod_questionnaire_vittel';
        } else if ($conf->global->QUESTIONNAIRE_ADDON == 'vittel') {
            $conf->global->QUESTIONNAIRE_ADDON = 'mod_questionnaire_vittel';
        } else if ($conf->global->QUESTIONNAIRE_ADDON == 'evian') {
            $conf->global->QUESTIONNAIRE_ADDON = 'mod_questionnaire_evian';
        }

        $included = false;

        $classname = $conf->global->QUESTIONNAIRE_ADDON;
        $file = $classname . '.php';

        // Include file with class
        $dir = '/questionnaire/core/modules/questionnaire/';
        $included = dol_include_once($dir . $file);

        if (!$included) {
            $this->error = $langs->trans('FailedToIncludeNumberingFile');
            return -1;
        }

        $obj = new $classname();

        $numref = "";
        $numref = $obj->getNumRef($soc, $this, $mode);

        if ($numref != "") {
            return $numref;
        } else {
            return -1;
        }
    }

    /**
     *    Return HTML table for object lines
     *    TODO Move this into an output class file (htmlline.class.php)
     *    If lines are into a template, title must also be into a template
     *    But for the moment we don't know if it's possible as we keep a method available on overloaded objects.
     *
     * @param string $action Action code
     * @param string $seller Object of seller third party
     * @param string $buyer Object of buyer third party
     * @param int $selected Object line selected
     * @param int $dateSelector 1=Show also date range input fields
     * @return    void
     */
    function printObjectLines($action, $seller, $buyer, $selected = 0, $dateSelector = 0, $defaulttpldir = '/core/tpl')
    {
        global $conf, $hookmanager, $langs, $user;

        $num = count($this->lines);

        // Title line
        print "<thead id=\"tabhead\">\n";

        print '<tr class="liste_titre nodrag nodrop">';

        // Adds a line numbering column
        if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) print '<th class="linecolnum" align="center" width="5">&nbsp;</td>';

        // Label
        print '<th class="linecollabel">' . $langs->trans('QuestionnaireLabel') . img_help(1,$langs->trans('HelpLabel')).'</th>';

        // Name
        print '<th class="linecolcode">' . $langs->trans('QuestionnaireCode') . img_help(1,$langs->trans('HelpCode')).'</th>';

        // Type
        print '<th class="linecoltype">' . $langs->trans('QuestionnaireType') . img_help(1,$langs->trans('HelpType')).'</th>';

        // Field Prefill
        print '<th class="linecolpostfill">' . $langs->trans('QuestionnairePostFill') . img_help(1,$langs->trans('HelpPostFill')).'</th>';

        // Prefill
        print '<th class="linecolprefill">' . $langs->trans('QuestionnairePreFill') . img_help(1,$langs->trans('HelpPreFill')).'</th>';

        // Condition
        print '<th class="linecolcond">' . $langs->trans('QuestionnaireCondition') . img_help(1,$langs->trans('HelpCondition')).'</th>';

        // Parameters
        print '<th class="linecolparam">' . $langs->trans('QuestionnaireParam') . img_help(1,$langs->trans('HelpParam')).'</th>';

        // Crypted
        print '<th class="linecolcrypted">' . $langs->trans('QuestionnaireCrypted') . img_help(1,$langs->trans('HelpCrypted')).'</th>';

        // Visiblity
        print '<th class="linecolvisibility">' . $langs->trans('QuestionnaireVisibility') . img_help(1,$langs->trans('HelpVisibility')).'</th>';

        // Inapp
        print '<th class="linecolinapp">'.$langs->trans('QuestionnaireInApp'). img_help(1,$langs->trans('HelpInApp')).'</th>';

        // Obligatoire
        print '<th class="linecolmandatory">' . $langs->trans('QuestionnaireMandatory') . img_help(1,$langs->trans('HelpMandatory')).'</th>';


        // Help
        print '<th class="linecolhelp">' . $langs->trans('QuestionnaireHelp') . img_help(1,$langs->trans('HelpHelp')).'</th>';

        print '<th class="linecoledit"></th>';  // No width to allow autodim

        print '<th class="linecoldelete" width="10"></th>';

        print '<th class="linecolmove" width="10"></th>';

        print "</tr>\n";
        print "</thead>\n";

        $var = true;
        $i = 0;

        print "<tbody>\n";
        foreach ($this->lines as $line) {
            $this->printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected, '', $defaulttpldir);
            $i++;
        }
        print "</tbody>\n";
    }

    /**
     *    Return HTML content of a detail line
     *    TODO Move this into an output class file (htmlline.class.php)
     *
     * @param string $action GET/POST action
     * @param CommonObjectLine $line Selected object line to output
     * @param string $var Is it a an odd line (true)
     * @param int $num Number of line (0)
     * @param int $i I
     * @param int $dateSelector 1=Show also date range input fields
     * @param string $seller Object of seller third party
     * @param string $buyer Object of buyer third party
     * @param int $selected Object line selected
     * @param int $extrafieldsline Object of extrafield line attribute
     * @return    void
     */
    function printObjectLine($action, $line, $var, $num, $i, $dateSelector, $seller, $buyer, $selected = 0, $extrafieldsline = 0, $defaulttpldir = '/core/tpl')
    {
        global $conf, $langs, $user, $object, $hookmanager, $bc;

        $domData = ' data-element="' . $line->element . '"';
        $domData .= ' data-id="' . $line->id . '"';

        $questionnaireform = new QuestionnaireForm($this->db);
        $form = new Form($this->db);


        // Ligne en mode visu
        if ($action != 'editline' || $selected != $line->id) {
            $types = $this->getTypes();
            $postfills = $this->getPostFills();
            $prefills = $this->getPreFills();
            $operators = $this->getOperators();
            $lines = $this->getFormLines();

            print '<tr  id="row-' . $line->id . '" class="drag drop oddeven" ' . $domData . ' >';
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center">' . ($i + 1) . '</td>';
            }

            print '<td class="linecollabel"><div id="line_' . $line->id . '"></div>';
            print $line->label;
            print '</td>';

            print '<td class="linecolcode nowrap">' . $line->code . '</td>';

            print '<td class="linecoltype nowrap">';
            print isset($types[$line->type]) ? $types[$line->type] : '&nbsp;';
            print '</td>';

            //Field prefill
            print '<td class="linecolpostfill nowrap">';
            if (!empty($line->postfill)) {
                $group = explode('.', $line->postfill);
                $group = count($group) > 0 ? $group[0] : '';

                print  isset($postfills[$group]['postfills'][$line->postfill]) ? $postfills[$group]['label'] . ' : ' . $postfills[$group]['postfills'][$line->postfill] : $line->postfill;
            } else {
                print '&nbsp;';
            }
            print '</td>';

            // Forced prefill
            print '<td class="linecolprefill nowrap">';
            if (!empty($line->prefill)) {
                $group = explode('.', $line->prefill);
                $group = count($group) > 0 ? $group[0] : '';
                print  isset($prefills[$group]['prefills'][$line->prefill]) ? $prefills[$group]['label'] . ' : ' . $prefills[$group]['prefills'][$line->prefill] : $line->prefill;
            } else {
                print '&nbsp;';
            }
            print '</td>';

            print '<td class="linecolcond nowrap">';
            $condline = null;
            $condop = null;
            if ($line->fk_cond > 0 || $line->fk_op_cond > 0) {
                $condline = isset($lines[$line->fk_cond]) ? $lines[$line->fk_cond] : null;
                $condop = isset($operators[$line->fk_op_cond]) ? $operators[$line->fk_op_cond] : null;
            }
            if ($condline || $condop) {
                print trim($condline . ' ' . $condop . ' ' . $line->val_cond);
            } else {
                print '&nbsp;';
            }
            print '</td>';

            print '<td class="linecolparam nowrap">' . nl2br($line->param) . '</td>';

            print '<td class="linecolcrypted nowrap">';
            if (empty($line->crypted)) {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=enable&field=crypted&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=disable&field=crypted&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }

            print '</td>';

            print '<td class="linecolvisibility nowrap">';
            if (empty($line->visibility)) {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=enable&field=visibility&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=disable&field=visibility&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';

            print '<td class="linecolinapp nowrap">';
            if (empty($line->inapp)) {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=enable&field=inapp&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=disable&field=inapp&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';

            print '<td class="linecolmandatory nowrap">';
            if (empty($line->mandatory)) {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=enable&field=mandatory&token='.newToken().'">';
                print img_picto($langs->trans("Disabled"), 'switch_off');
                print '</a>';
            } else {
                print '<a class="reposition" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $line->id . '&action=disable&field=mandatory&token='.newToken().'">';
                print img_picto($langs->trans("Activated"), 'switch_on');
                print '</a>';
            }
            print '</td>';

            print '<td class="linecolhelp">' . nl2br($line->help) . '</td>';

            if ($action != 'selectlines') {
                print '<td class="linecoledit" align="center">';
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=editline&lineid=' . $line->id . '&token=' . newToken() . '">';
                print img_edit();
                print '</a>';
                print '</td>';

                print '<td class="linecoldelete" align="center">';
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=ask_deleteline&lineid=' . $line->id . '&token=' . newToken() . '">';
                print img_delete();
                print '</a>';
                print '</td>';

                if ($num > 1) {
                    print '<td align="center" class="linecolmove tdlineupdown">';
                    if ($i > 0) {
                        print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=up&rowid=' . $line->id . '&token=' . newToken() . '">';
                        print img_up('default', 0, 'imgupforline');
                        print '</a>';
                    }
                    if ($i < $num - 1) {
                        print '<a class="lineupdown" href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->id . '&action=down&rowid=' . $line->id . '&token=' . newToken() . '">';
                        print img_down('default', 0, 'imgdownforline');
                        print '</a>';
                    }
                    print '</td>';
                } else {
                    print '<td align="center" class="linecolmove tdlineupdown"></td>';
                }
            } else {
                print '<td colspan="3">&nbsp;</td>';
            }

            if ($action == 'selectlines') {
                print '<td class="linecolcheck" align="center"><input type="checkbox" class="linecheckbox" name="line_checkbox[' . ($i + 1) . ']" value="' . $line->id . '" ></td>';
            }

            print '</tr>';
        }

        // Ligne en mode update
        if ($action == 'editline' && $selected == $line->id) {
            $lines = $this->getFormLines($line->id);


            $label = (!empty($line->label) ? $line->label : (($line->fk_product > 0) ? $line->product_label : ''));
            $placeholder = ' placeholder="' . $langs->trans("Label") . '"';

            print '<tr ' . $bc[$var] . ' >';
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center">' . ($i + 1) . '</td>';
            }
            print '<td>';
            print '<div id="qline_' . $line->id . '"></div>';
            print '<input type="hidden" name="lineid" value="' . $line->id . '">'.$langs->trans('QuestionnaireLabel');
            print '<input id="label" name="label" class="flat" value="' . $line->label . '">';
            print '</td>';

            print '<td>'.$langs->trans('QuestionnaireCode'). "\n";
            print '<input id="code" name="code" size="40" class="flat" value="' .  $line->code . '" placeholder="' . $langs->trans('QuestionnaireCodePlaceHolder') . '" />' . "\n";
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnaireType'). "\n";
            print $questionnaireform->select_type($line->type, 'type', '', true);
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnairePostFill'). "\n";
            print $questionnaireform->select_postfill($line->postfill, 'postfill', '', true);
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnairePreFill'). "\n";
            print $questionnaireform->select_prefill($line->prefill, 'prefill', '', true);
            print '</td>' . "\n";

            print '<td class="linecolcond">'.$langs->trans('QuestionnaireCondition'). "\n";
            print $form->selectarray('fk_cond', $lines, $line->fk_cond, 1);//select_line(GETPOST('fk_cond'), 'fk_cond', '', true);
            print '&nbsp;';
            print $questionnaireform->select_operator($line->fk_op_cond, 'fk_op_cond', '', true);
            print '&nbsp;';
            print '<input id="val_cond" name="val_cond" class="flat" value="' . $line->val_cond . '">';
            print '</td>';

            print '<td>'.$langs->trans('QuestionnaireParam');
            print ' <a href="'.self::HELP_LINK.'#'.$line->type.'" target="_blank">'.img_help(1,$langs->trans('QuestionnaireParamHelp')).'</a>';
            
            $doleditor = new DolEditor('param', $line->param, '', 100, 'dolibarr_notes', '', false, true, 0, ROWS_2, '98%');
            $doleditor->Create();
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnaireCrypted'). "\n";
            print $form->selectyesno('crypted', $line->crypted, 1);
            print '</td>' . "\n";

            print '<td>' . "\n";
            print '<td>'.$langs->trans('QuestionnaireVisibility'). "\n";
            print $form->selectyesno('visibility', $line->visibility, 1);
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnaireInApp'). "\n";
            print $form->selectyesno('inapp', $line->inapp, 1);
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnaireMandatory'). "\n";
            print $form->selectyesno('mandatory', $line->mandatory, 1);
            print '</td>' . "\n";

            print '<td>'.$langs->trans('QuestionnaireHelp'). "\n";
            print '<input id="help" name="help" class="flat" value="' . $line->help . '">' . "\n";
            print '</td>' . "\n";

            print '<td align="center" colspan="3" valign="middle">' . "\n";
            print '	<input type="submit" class="button" id="savelinebutton" name="save" value="' . $langs->trans("Save") . '"><br>' . "\n";
            print '	<input type="submit" class="button" id="cancellinebutton" name="cancel" value="' . $langs->trans("Cancel") . '">' . "\n";
            print '</td>' . "\n";

            print '</tr>' . "\n";
        }
    }

    /**
     *    Show add free and predefined products/services form
     *
     * @param int $dateSelector 1=Show also date range input fields
     * @param Societe $seller Object thirdparty who sell
     * @param Societe $buyer Object thirdparty who buy
     * @return    void
     */
    function formAddObjectLine($dateSelector, $seller, $buyer, $defaulttpldir = '/core/tpl')
    {
        global $conf, $user, $langs, $object, $hookmanager;

        $questionnaireform = new QuestionnaireForm($this->db);
        $form = new Form($this->db);

        $nolinesbefore = (count($this->lines) == 0);

        $lines = $this->getFormLines();

        if ($nolinesbefore) {

            print '<tr class="liste_titre' . ($nolinesbefore ? '' : ' liste_titre_add_') . ' nodrag nodrop">' . "\n";
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center"></td>' . "\n";
            }

            print '<td class="linecollabel">' . "\n";
            print '<div id="add"></div><span class="hideonsmartphone">' . $langs->trans('AddNewLine') . '</span>' . "\n";
            print '</td>' . "\n";

            print '<td class="linecolcode"><span id="title_code">' . $langs->trans('QuestionnaireCode') . '</span></td>' . "\n";
            print '<td class="linecoltype"><span id="title_type">' . $langs->trans('QuestionnaireType') . '</span></td>' . "\n";
            print '<td class="linecolpostfill"><span id="title_postfill">' . $langs->trans('QuestionnairePostFill') . '</span></td>' . "\n";
            print '<td class="linecolprefill"><span id="title_prefill">' . $langs->trans('QuestionnairePreFill') . '</span></td>' . "\n";
            print '<td class="linecolcond"><span id="title_cond">' . $langs->trans('QuestionnaireCondition') . '</span></td>' . "\n";
            print '<td class="linecolparam"><span id="title_param">' . $langs->trans('QuestionnaireParam') . '</span></td>' . "\n";
            print '<td class="linecolcrypted"><span id="title_crypted">' . $langs->trans('QuestionnaireCrypted') . '</span></td>' . "\n";
            print '<td class="linecolvisibility"><span id="title_visiblity">' . $langs->trans('QuestionnaireVisibility') . '</span></td>';
            print '<td class="linecolinapp"><span id="title_inapp">' . $langs->trans('QuestionnaireInApp') . '</span></td>';
            print '<td class="linecolmandatory"><span id="title_mandatory">' . $langs->trans('QuestionnaireMandatory') . '</span></td>';
            print '<td class="linecolhelp"><span id="title_help">' . $langs->trans('QuestionnaireHelp') . '</span></td>' . "\n";

            print '<td class="linecoledit" colspan="3">&nbsp;</td>' . "\n";
            print '</tr>' . "\n";
        }

        print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore ? '' : ' liste_titre_create') . '">' . "\n";

        // Adds a line numbering column
        if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
            print '<td class="nobottom linecolnum" align="center" width="5"></td>' . "\n";
        }

        print '<td class="nobottom linecollabel">';
        print $langs->trans('QuestionnaireLabel');
        print '<input id="label" name="label" class="flat" value="' . GETPOST('label', 'alpha') . '">' . "\n";
        print '</td>' . "\n";

        print '<td class="nobottom linecolcode">'. "\n";
        print $langs->trans('QuestionnaireCode');
        print '<br />';
        print '<input id="code" name="code" size="40" class="flat" value="' . GETPOST('code') . '" placeholder="' . $langs->trans('QuestionnaireCodePlaceHolder') . '" />' . "\n";
        print '</td>' . "\n";

        print '<td class="nobottom linecoltype">'."\n";
        print $langs->trans('QuestionnaireType');
        print $questionnaireform->select_type(GETPOST('type', 'alpha'), 'type', '', true);
        print '</td>' . "\n";
        
        print '<td class="nobottom linecolpostfill">'."\n";
        print $langs->trans('QuestionnairePostFill');
        print $questionnaireform->select_postfill(GETPOST('postfill'), 'postfill', '', true);
        print '</td>' . "\n";

        print '<td class="nobottom linecolprefill">'."\n";
        print $langs->trans('QuestionnairePreFill');
        print $questionnaireform->select_prefill(GETPOST('prefill'), 'prefill', '', true);
        print '</td>' . "\n";

        print '<td class="linecolcond">'."\n";
        print $langs->trans('QuestionnaireCondition');
        print $form->selectarray('fk_cond', $lines, GETPOST('fk_cond'), 1);//select_line(GETPOST('fk_cond'), 'fk_cond', '', true);
        print '&nbsp;';
        print $questionnaireform->select_operator(GETPOST('fk_op_cond', 'int'), 'fk_op_cond', '', true);
        print '&nbsp;';
        print '<input id="val_cond" size="10" name="val_cond" class="flat" value="' . GETPOST('val_cond', 'alpha') . '">';
        print '</td>';

        print '<td class="nobottom linecolparam">'."\n";
        print $langs->trans('QuestionnaireParam');
        $doleditor = new DolEditor('param', GETPOST('param', 'alpha'), '', 100, 'dolibarr_notes', '', false, true, 0, ROWS_2, '98%');
        $doleditor->Create();
        print '</td>' . "\n";

        print '<td class="nobottom linecolcrypted">'."\n";
        print $langs->trans('QuestionnaireCrypted');
        print $form->selectyesno('crypted', GETPOST('crypted', 'int'), 1);
        print '</td>' . "\n";

        print '<td class="nobottom linecolvisibility">'."\n";
        print $langs->trans('QuestionnaireVisibility');
        print $form->selectyesno('visibility', GETPOST('visibility', 'int'), 1);
        print '</td>' . "\n";

        if(!empty(GETPOST('inapp', 'int'))){
            $inapp = GETPOST('inapp', 'int');
        }else{
            $inapp = 1;
        }
        print '<td class="nobottom linecolinapp">'. "\n";
        print $langs->trans('QuestionnaireInApp');
        print $form->selectyesno('inapp',$inapp, 1);
        print '</td>' . "\n";

        if(!empty(GETPOST('mandatory', 'int'))){
            $mandatory = GETPOST('mandatory', 'int');
        }else{
            $mandatory = 1;
        }
        print '<td class="nobottom linecolmandatory">'."\n";
        print $langs->trans('QuestionnaireMandatory');
        print $form->selectyesno('mandatory', $mandatory, 1);
        print '</td>' . "\n";

        print '<td class="nobottom linecolhelp">'."\n";
        print $langs->trans('QuestionnaireHelp');
        print '<input id="help" name="help" class="flat" value="' . GETPOST('help', 'alpha') . '">' . "\n";
        print '</td>' . "\n";

        print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="3">'. "\n";
        print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">' . "\n";
        print '</td>' . "\n";

        print '</tr>' . "\n";
    }

    /**
     *    Charge les informations d'ordre info dans l'objet commande
     *
     * @param int $id Id of order
     * @return    void
     */
    function info($id)
    {
        $sql = 'SELECT e.rowid, e.datec as datec, e.tms as datem,';
        $sql .= ' e.user_author_id as fk_user_author';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'questionnaire as e';
        $sql .= ' WHERE e.rowid = ' . $id;
        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result)) {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->rowid;
                if ($obj->fk_user_author) {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation = $cuser;
                }

                $this->date_creation = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
            }

            $this->db->free($result);
        } else {
            dol_print_error($this->db);
        }
    }

    /**
     *    Return clicable link of object (with eventually picto)
     *
     * @param int $withpicto Add picto into link
     * @param int $max Max length to show
     * @param int $short ???
     * @param int $notooltip 1=Disable tooltip
     * @return     string                              String with URL
     */
    function getNomUrl($withpicto = 0, $option = '', $max = 0, $short = 0, $notooltip = 0)
    {
        global $conf, $langs, $user;

        if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1;   // Force disable tooltips

        $result = '';

        $url = dol_buildpath('/questionnaire/card.php', 1) . '?id=' . $this->id;

        if ($short) return $url;

        $picto = 'questionnaire@questionnaire';
        $label = '';

        if ($user->rights->questionnaire->lire) {
            $label .= '<b>' . $langs->trans('Title') . ':</b> ' . $this->title;
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
            //$label .= '<br><b>' . $langs->trans('NBAnswer') . ':</b> ' . $this->nb_answer;

        }

        $linkclose = '';
        if (empty($notooltip) && $user->rights->questionnaire->lire) {
            if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
                $label = $langs->trans("ShowQuestionnaire");
                $linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
            }
            $linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
            $linkclose .= ' class="classfortooltip"';
        }

        $linkstart = '<a href="' . $url . '"';
        $linkstart .= $linkclose . '>';
        $linkend = '</a>';

        $title = $this->ref.' - '.$this->title ;

        if ($withpicto) $result .= ($linkstart . img_object(($notooltip ? '' : $label), $picto, ($notooltip ? '' : 'class="classfortooltip" width=20'), 0, 0, $notooltip ? 0 : 1) . $linkend);
        if ($withpicto && $withpicto != 2) $result .= ' ';
        $result .= $linkstart . $title . $linkend;

        return $result;
    }

    /**
     *    Return status label of Questionnaire
     *
     * @param int $mode 0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     * @return     string            Libelle
     */
    function getLibStatut($mode)
    {
        return '';
    }

    /**
     *  Return list of questionnaires
     *
     * @return     int                    -1 if KO, array with result if OK
     */
    function liste_array($active = -1, $sortfield = 'e.ref', $sortorder = 'DESC', $withref=1)
    {
        global $user;

        $questionnaires = array();

        $sql = "SELECT e.rowid as id, e.ref, e.title, e.datec";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire as e";
        $sql .= " WHERE e.entity IN (" . getEntity('questionnaire') . ")";
        $sql .= $active >= 0 ? " AND e.active = " . intval($active) : "";
        $sql .= $this->db->order($sortfield, $sortorder);

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);
                    if($withref==1){
                        $questionnaires[$obj->id] = $obj->ref.' - '.$obj->title;
                    }else{
                        $questionnaires[$obj->id] = $obj->title;
                    }
                    

                    $i++;
                }
            }

            return $questionnaires;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }

    function get_link($id){
        
    }

    /**
     *  Return default and active form
     *
     * @return     int                    -1 if KO, array with result if OK
     */
    function get_default()
    {
        global $user;

        $sql = "SELECT e.rowid as id";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire as e";
        $sql .= " WHERE e.entity IN (" . getEntity('questionnaire') . ")";
        $sql .= " AND e.active = 1 AND e.selected = 1";

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $obj = $this->db->fetch_object($result);
                return $obj->id;
            }
        }

        return 0;
    }

    /**
     *  Load operators
     *
     * @return array
     */
    function getOperators()
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getOperators");

        $operators = array(
            self::CONDITION_LESS => html_entity_decode($langs->trans('OperatorLess')),
            self::CONDITION_LESS_OR_EQUAL => html_entity_decode($langs->trans('OperatorLessOrEqual')),
            self::CONDITION_EQUAL => html_entity_decode($langs->trans('OperatorEqual')),
            self::CONDITION_GREATER => html_entity_decode($langs->trans('OperatorGreater')),
            self::CONDITION_GREATER_OR_EQUAL => html_entity_decode($langs->trans('OperatorGreaterOrEqual')),
            self::CONDITION_DIFFERENT => html_entity_decode($langs->trans('OperatorDifferent')),
            self::CONDITION_EMPTY => $langs->trans('OperatorEmpty'),
            self::CONDITION_NOT_EMPTY => $langs->trans('OperatorNotEmpty'),
            self::CONDITION_ALWAYS => $langs->trans('OperatorAlways'),
            self::CONDITION_NOT_EMPTY_DISPLAYED => $langs->trans('OperatorNotEmptyDisplayed'),
            self::CONDITION_EMPTY_DISPLAYED => $langs->trans('OperatorEmptyDisplayed'),

        );

        return $operators;
    }

    /**
     *  Load types
     *
     * @return array
     */
    function getTypes()
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getTypes");

        $types = array(
            'string' => $langs->trans('TypeString'),
            'int' => $langs->trans('TypeInt'),
            'numeric' => $langs->trans('TypeNumeric'),
            'text' => $langs->trans('TypeText'),
            'file' => $langs->trans('TypeFile'),
            'map' => $langs->trans('TypeMap'),
            'date' => $langs->trans('TypeDate'),
            'datetime' => $langs->trans('TypeDateTime'),
            'list' => $langs->trans('TypeList'),
            'table' => $langs->trans('TypeTable'),
            'radio' => $langs->trans('TypeRadio'),
            'checkbox' => $langs->trans('TypeCheckbox'),
            'siret' => $langs->trans('TypeSiret'),
            'societe' => $langs->trans('TypeCompany'),
            'projet' => $langs->trans('TypeProject'),
            'commande' => $langs->trans('TypeCommande'),
            'product' => $langs->trans('TypeProduct'),
            'user' => $langs->trans('TypeUser'),
            'task' => $langs->trans('TypeTask'),
            'warehouse' => $langs->trans('TypeWarehouse'),
            'sign' => $langs->trans('TypeSign'),
            'productmultiple' => $langs->trans('TypeProductMultiple'),
        );

        return $types;
    }

    /**
     *  Load prefill
     *
     * @return array
     */
    function getPreFills()
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getPreFills");

        $prefills = array(
            'user' => array(
                'label' => $langs->trans('LoggedInUser'),
                'prefills' => array(
                    'user.firstname' => $langs->trans('UserFirstName'),
                    'user.lastname' => $langs->trans('UserLastName'),
                    'user.fullname' => $langs->trans('UserFullName'),
                    'user.email' => $langs->trans('UserEmail'),
                ),
            ),
            'date' => array(
                'label' => $langs->trans('Date'),
                'prefills' => array(
                    'date.now' => $langs->trans('DateNow')
                ),
            ),
            'app' => array(
                'label' => $langs->trans('App'),
                'prefills' => array(
                    'app.os' => $langs->trans('AppOperatingSystem')
                ),
            ),
        );


        return $prefills;
    }

    /**
     *  Load prefill for fields since an other field value (like siret field)
     *
     * @return array
     */
    function getPostFills()
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getPreFills");

        $postfills = array(
            'siret' => array(
                'label' => $langs->trans('Siret'),
                'postfills' => array(
                    'siret.nom_complet' => $langs->trans('SiretNomComplet'),
                    'siret.adresse' => $langs->trans('SiretAdresse'),
                    'siret.code_postal' => $langs->trans('SiretCodePostal'),
                    'siret.libelle_commune' => $langs->trans('SiretLibelleCommune'),
                    'siret.code_ape' => $langs->trans('SiretCodeAPE'),
                    'siret.code_siren' => $langs->trans('SiretCodeSIREN'),
                    'siret.dirigeant_nom' => $langs->trans('SiretDirigeantNom'),
                    'siret.dirigeant_prenom' => $langs->trans('SiretDirigeantPrenom'),
                    'siret.est_association' => $langs->trans('SiretEstAssociation'),
                    'siret.est_bio' => $langs->trans('SiretEstBio'),
                    'siret.est_entrepreneur_individuel' => $langs->trans('SiretEstEntrepreneurIndividuel'),
                    'siret.est_rge' => $langs->trans('SiretEstRGE'),
                    'siret.identifiant_association' => $langs->trans('SiretIdentifiantAssociation'),
                    'siret.latlong' => $langs->trans('SiretLatLong')
                ),
            ),
        );

        // Complete from action
        $questionnaireaction = new QuestionnaireAction($this->db);
        $questionnaireaction->type = QuestionnaireAction::SOCIETE_TYPE;
        $fields = $questionnaireaction->getFields(1);

        $postfills['societe'] = array(
            'label' => $langs->trans('Company'),
            'postfills' => $fields
        );

        $questionnaireaction = new QuestionnaireAction($this->db);
        $questionnaireaction->type = QuestionnaireAction::PROJECT_TYPE;
        $fields = $questionnaireaction->getFields(1);

        $postfills['projet'] = array(
            'label' => $langs->trans('Project'),
            'postfills' => $fields
        );

        return $postfills;
    }


    /**
     *  Load types
     *
     * @return array
     */
    function getFormLines($excl_id = 0,$withlabel=0)
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getLines");

        $fields = $this->lines;
        $lines = array();
        
        if (count($fields)) {
            foreach ($fields as $field) {    
                
                if ($field->id != $excl_id) {
                    if($withlabel==1){
                        $lines[$field->id] = $field->code.' - '.$field->label;
                    }else{
                        $lines[$field->id] = $field->code;
                    }
                    
                }
            }
        }

        asort($lines);
        return $lines;
    }

    /**
     *  Load types
     *
     * @return array
     */
    function getLines()
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::getLines");

        $fields = $this->getAllFields();
        $lines = array();

        if (count($fields)) {
            foreach ($fields as $field) {
                $lines[$field->id] = $field->questionnaire_ref . '::' . $field->code;
            }
        }

        asort($lines);

        return $lines;
    }

    /**
     *  Reverse geocoding
     *
     * @return array
     */
    function reverse($lat, $lon)
    {
        global $langs, $user, $conf;

        $url = sprintf($this->geocoder, 10) . '&lat=' . $lat . '&lon=' . $lon;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, DOL_URL_ROOT);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $result = curl_exec($ch);

        $country = null;
        $region = null;
        $state = null;
        $city = null;
        $zip = null;

        if ($result) {
            $result = json_decode($result);

            $address = $result->address ? $result->address : null;

            if ($address) {
                if (isset($address->county) && empty($state)) {
                    $state = $address->county;
                }

                if (isset($address->state) && empty($region)) {
                    $region = $address->state;
                }

                if (isset($address->country) && empty($country)) {
                    $country = $address->country;
                }
            }
        }


        // Specific request for city and postal code
        $url = sprintf($this->geocoder, 20) . '&lat=' . $lat . '&lon=' . $lon;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, DOL_URL_ROOT);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $result = curl_exec($ch);

        if ($result) {
            $result = json_decode($result);

            $address = $result->address ? $result->address : null;

            if ($address) {
                if (isset($address->village) && empty($city)) {
                    $city = $address->village;
                }

                if (isset($address->city) && empty($city)) {
                    $city = $address->city;
                }

                if (isset($address->city_district) && empty($city)) {
                    $city = $address->city_district;
                }

                if (isset($address->town) && empty($city)) {
                    $city = $address->town;
                }

                if (isset($address->municipality) && empty($city)) {
                    $city = $address->municipality;
                }

                if (isset($address->county) && empty($state)) {
                    $state = $address->county;
                }

                if (isset($address->state) && empty($region)) {
                    $region = $address->state;
                }

                if (isset($address->postcode) && empty($zip)) {
                    $zip = $address->postcode;
                }

                if (isset($address->country) && empty($country)) {
                    $country = $address->country;
                }
            }
        }

        return array($city, $region, $state, $country, $zip);
    }

    /**
     *  Geocoding
     *
     * @return array
     */
    function geocoder($query)
    {
        global $langs, $user, $conf;

        $url = $this->geodecoder . '&q=' . urlencode($query);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, DOL_URL_ROOT);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');

        $result = curl_exec($ch);

        if ($result) {
            $result = json_decode($result);
        }


        $lat = 0;
        $lon = 0;
        $state = null;
        $region = null;

        if (count($result)) {
            $result = $result[0];

            $lat = $result->lat;
            $lon = $result->lon;

            if ($result->address) {
                $state = $result->address->county;
                $region = $result->address->state;
            }
        }

        return array($lat, $lon, $state, $region);
    }

    /**
     *    Process line.
     *
     */
    function processline($line)
    {
        global $langs, $conf, $hookmanager, $user;

        $value = '';

        switch ($line->type) {

            case 'date':
            case 'datetime':
                $hour = GETPOST($line->code . 'hour', 'int');
                $minute = GETPOST($line->code . 'min', 'int');
                $day = GETPOST($line->code . 'day', 'int');
                $month = GETPOST($line->code . 'month', 'int');
                $year = GETPOST($line->code . 'year', 'int');

                $value = dol_mktime($hour, $minute, 0, $month, $day, $year);

                $value = $this->db->idate($value);
                break;

            case 'file':
                $values = !empty($line->value) ? explode(',', $line->value) : array();

                $object = new Reponse($this->db);
                $object->fetch($line->fk_reponse);
                $ref = dol_sanitizeFileName($object->ref);

                $dir = $conf->reponse->dir_output . '/' . $ref;
                dol_mkdir($dir);

                if (@is_dir($dir)) {

                    if (isset($_FILES[$line->code])) {
                        $nFiles = is_array($_FILES[$line->code]['name']) ? count($_FILES[$line->code]['name']) : 0;
                        for ($i = 0; $i < $nFiles; $i++) {
                            $tmp = $_FILES[$line->code]['tmp_name'][$i];
                            $name = $_FILES[$line->code]['name'][$i];
                            $err = $_FILES[$line->code]['error'][$i];

                            $file_OK = is_uploaded_file($tmp);
                            $name = $ref . '_' . $name;
                            //dol_syslog(get_class($this)."::fill tmp=".$tmp." name=".$name." file_OK=".$file_OK);

                            if ($file_OK) {
                                $newfile = $dir . '/' . dol_sanitizeFileName($name);
                                if (!dol_move_uploaded_file($tmp, $newfile, 1, 0, $err) > 0) {
                                    setEventMessages($langs->trans("ErrorFailedToSaveFile"), null, 'errors');
                                } else {
                                    // Create thumbs
                                    $this->addThumbs($newfile);
                                    $values[] = dol_sanitizeFileName($name);

                                }
                            }
                        }
                    }
                }

                $value = count($values) ? implode(',', $values) : '';

                break;

            case 'checkbox':
                $values = GETPOST($line->code, 'array');
                $value = count($values) ? implode(',', array_values($values)) : '';
                break;


            case 'numeric':
                $value = GETPOST($line->code, 'alpha');
                $value = price2num($value);
                break;
            case 'list':
            case 'table':
            case 'radio':
            default:
                $value = GETPOST($line->code, 'alpha');
                break;
        }

        $uncrypted_value = $value;

        if ($line->crypted) {
            $num_bytes = !empty($conf->global->QUESTIONNAIRE_NUM_BYTES) ? intval($conf->global->QUESTIONNAIRE_NUM_BYTES) : 10;

            $key = bin2hex(random_bytes($num_bytes));

            $value = mb_dol_encode($value, $key);
            $value = $key . ':' . $value;
        }

        return array($value, $uncrypted_value);
    }

    /**
     * Get all fields
     *
     * @return    int                        <0 if KO, >0 if OK
     */
    function getAllFields()
    {
        $fields = array();

        $sql = "SELECT l.rowid as id, l.code, l.label, l.type, l.param, l.visibility, l.mandatory, l.crypted, l.inapp, f.ref, f.title";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnairedet as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnaire as f ON f.rowid = l.fk_questionnaire";
        $sql .= " WHERE f.entity IN (" . getEntity('questionnaire') . ")"; // Dont't use entity if you use rowid
        $sql .= " ORDER BY l.rang";

        dol_syslog(get_class($this) . "::getAllFields", LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                //$alias = chr(97 + mt_rand(0, 25));
                //$alias.= chr(97 + mt_rand(0, 25));
                //$alias.= chr(97 + mt_rand(0, 25));

                $field = new stdClass();

                $field->label = $objp->label;
                $field->type = $objp->type;
                $field->code = $objp->code;
                $field->param = $objp->param;
                $field->crypted = $objp->crypted;
                $field->inapp = $objp->inapp;

                $field->visibility = $objp->visibility;
                $field->mandatory = $objp->mandatory;
                $field->id = $objp->id;

                $field->questionnaire_ref = $objp->ref;
                $field->questionnaire_title = $objp->title;

                //$field->alias = $alias;


                $fields['fv.' . $field->code] = $field;

                $i++;
            }

            $this->db->free($result);
        }

        return $fields;
    }

    function addFormCSS($questionnaire){
        echo '<style>';
        echo '.headroom--not-top.navbar-theme-primary, .bg-primary{background-color:'.$questionnaire->background.'!important; border-color:'.$questionnaire->background.'!important;}';
        echo '.btn-success, .btn-outline-success, .bg-success{background-color:'.$questionnaire->buttonbackground.'!important; border-color:'.$questionnaire->buttonbackground.'!important;}';
        echo '.btn-success:hover, .btn-outline-success:hover{background-color:'.$questionnaire->buttonbackgroundhover.'!important; border-color:'.$questionnaire->buttonbackgroundhover.'!important;}';
        echo '.lead{color:'.$questionnaire->coloraccent.'!important;}';
        echo '.progress-bar{animation: '.$questionnaire->progressbar_duration.'s animate-positive!important;}';
        echo $questionnaire->customcss;
        echo '</style>';
    }

    /**
     *  Load icons in memory from directory listing
     *
     *  @return array
     */
    function getIcons()
    {
        global $langs, $conf;
        dol_syslog(get_class($this)."::getIcons");
        $icons = array();

        $path = '/questionnaire/icons';
        $upload_dir = dol_buildpath($path, 0);
        $filearray = dol_dir_list($upload_dir, "files",0, '', '@2x|@3x', 'name', SORT_ASC, 1);
        
        foreach ($filearray as $key => $file)
        {
            $icon = new stdClass();
            $icon->name = $file['name'];
            $icon->image = dol_buildpath($path.'/'.$file['name'], 2);
            $icon->fullpath = dol_buildpath($path.'/'.$file['name'], 0);
            $icons[$icon->name] = $icon;                    
        }

        return $icons;
    }



}


/**
 *  Class to manage questionnaire lines
 */
class QuestionnaireLine extends CommonObjectLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'questionnairedet';

    public $table_element = 'questionnairedet';

    var $oldline;

    /**
     * Id of questionnaire
     * @var int
     */
    public $fk_questionnaire;

    /**
     * Name or code
     * @var string
     */
    public $code;

    /**
     * Label
     * @var string
     */
    public $label;

    /**
     * Type
     * @var string
     */
    public $type;

    /**
     * Rang
     * @var int
     */
    public $rang;

    /**
     * Field prefill
     * @var string
     */
    public $postfill;

    /**
     * Forced value
     * @var string
     */
    public $prefill;

    /**
     * Display condition
     * @var int
     */
    public $fk_cond = 0;

    /**
     * Operator condition
     * @var int
     */
    public $fk_op_cond = 0;

    /**
     * Condition value
     * @var string
     */
    public $val_cond = null;

    /**
     * Param
     * @var text
     */
    public $param;

    /**
     * Help
     * @var text
     */
    public $help;

    /**
     * Crypted
     * @var int
     */
    public $crypted = 0;

    /**
     * In app
     * @var int
     */
    public $inapp = 0;

    /**
     * Visible
     * @var int
     */
    public $visibility = 1;

    /**
     * Mandatory in app
     * @var int
     */
    public $mandatory = 0;

    /**
     * Creation date
     * @var int
     */
    public $datec;

    /**
     * Author id
     * @var int
     */
    public $user_author_id = 0;


    /**
     *      Constructor
     *
     * @param DoliDB $db handler d'acces base de donnee
     */
    function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Load line questionnaire
     *
     * @param int $rowid Id line order
     * @return    int                        <0 if KO, >0 if OK
     */
    function fetch($rowid)
    {
        $sql = "SELECT l.rowid, l.fk_questionnaire, l.code, l.label, l.type, l.rang, l.prefill, l.param, l.help, l.visibility, l.mandatory, l.crypted, l.inapp, l.datec,";
        $sql .= " l.fk_cond, l.fk_op_cond, l.val_cond, l.user_author_id, l.tms";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnairedet as l";
        $sql .= " WHERE l.rowid = " . $rowid;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);

            $this->rowid = $objp->rowid;
            $this->id = $objp->rowid;
            $this->fk_questionnairedet = $objp->rowid;
            $this->fk_questionnaire = $objp->fk_questionnaire;
            $this->code = $objp->code;
            $this->label = $objp->label;
            $this->type = $objp->type;
            $this->rang = $objp->rang;
            $this->prefill = $objp->prefill;
            $this->fk_cond = $objp->fk_cond;
            $this->fk_op_cond = $objp->fk_op_cond;
            $this->val_cond = $objp->val_cond;
            $this->param = $objp->param;
            $this->help = $objp->help;
            $this->visibility = $objp->visibility;
            $this->mandatory = $objp->mandatory;
            $this->crypted = $objp->crypted;
            $this->inapp = $objp->inapp;

            $this->user_author_id = $objp->user_author_id;
            $this->datec = $this->db->jdate($objp->datec);
            $this->tms = $this->db->jdate($objp->tms);

            $this->db->free($result);

            return 1;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *  Check line questionnaire
     *
     * @return    int                        <0 if KO, >0 if OK
     */
    function check($rowid = 0, $fk_questionnaire = 0)
    {
        $sql = "SELECT l.*";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnairedet as l";
        $sql .= " WHERE l.code = '" . $this->db->escape($this->code) . "'";
        $sql .= $rowid > 0 ? " AND l.rowid <> " . $rowid : "";
        $sql .= $fk_questionnaire > 0 ? " AND l.fk_questionnaire <> " . $fk_questionnaire : "";

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            if ($this->db->num_rows($result) > 0) {
                $this->error = 'CodeAlreadyExists';
                return -1;
            } else {
                return 1;
            }
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *    Delete line in database
     *
     * @param User $user User that modify
     * @param int $notrigger 0=launch triggers after, 1=disable triggers
     * @return     int  <0 si ko, >0 si ok
     */
    function delete($user = null, $notrigger = 0)
    {
        global $conf, $user, $langs;

        $error = 0;

        $this->db->begin();

        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "questionnairedet WHERE rowid=" . $this->rowid;

        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql) {

            // Check if no more lines use this code before delete
            // TODO
            if ($this->check($this->rowid, $this->fk_questionnaire) > 0) {
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." DROP " . $this->code;
                dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
                $resql = $this->db->query($sql);

                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnairefval".$this->fk_questionnaire." DROP " . $this->code;
                dol_syslog(get_class($this) . "::delete", LOG_DEBUG);
                $resql = $this->db->query($sql);
            }


            if ($resql) {


                if (!$error && !$notrigger) {
                    // Call trigger
                    $result = $this->call_trigger('LINEQUESTIONNAIRE_DELETE', $user);
                    if ($result < 0) $error++;
                    // End call triggers
                }

                if (!$error) {
                    $this->db->commit();
                    return 1;
                }

            } else {
                $this->error = $this->db->lasterror();
                return -1;
            }


            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }

            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }


    /**
     *    Clone line into database
     *
     * @param User $user User that modify
     * @param int $notrigger 1 = disable triggers
     * @return        int                        <0 if KO, >0 if OK
     */
    function clone($user = null, $notrigger = 0)
    {
        global $langs, $conf;

        $error = 0;


        dol_syslog(get_class($this) . "::clone rang=" . $this->rang);

        // Clean parameters
        if (empty($this->rang)) $this->rang = 0;
        if (empty($this->fk_type) || $this->fk_type < 0) $this->fk_type = 0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "questionnairedet (";
        $sql .= " fk_questionnaire,";
        $sql .= " code,";
        $sql .= " label,";
        $sql .= " type,";
        $sql .= " rang,";
        $sql .= " prefill,";
        $sql .= " fk_cond,";
        $sql .= " fk_op_cond,";
        $sql .= " val_cond,";
        $sql .= " param,";
        $sql .= " help,";
        $sql .= " datec,";
        $sql .= " crypted,";
        $sql .= " visibility,";
        $sql .= " inapp,";
        $sql .= " mandatory,";
        $sql .= " user_author_id,";
        $sql .= " tms";
        $sql .= " )";
        $sql .= " VALUES (";
        $sql .= " " . $this->fk_questionnaire . ",";
        $sql .= " '" . $this->db->escape($this->code) . "',";
        $sql .= " '" . $this->db->escape($this->label) . "',";
        $sql .= " '" . $this->db->escape($this->type) . "',";
        $sql .= " " . $this->rang . ",";
        $sql .= " '" . $this->db->escape($this->prefill) . "',";
        $sql .= " " . ($this->fk_cond > 0 ? $this->fk_cond : 0) . ",";
        $sql .= " " . ($this->fk_op_cond > 0 ? $this->fk_op_cond : 0) . ",";
        $sql .= " '" . $this->db->escape($this->val_cond) . "',";
        $sql .= " '" . $this->db->escape($this->param) . "',";
        $sql .= " '" . $this->db->escape($this->help) . "',";
        $sql .= " '" . $this->db->idate(dol_now()) . "',";
        $sql .= " " . $this->crypted . ",";
        $sql .= " " . $this->visibility . ",";
        $sql .= " " . $this->inapp . ",";
        $sql .= " " . $this->mandatory . ",";
        $sql .= " " . (is_object($user) ? $user->id : 0) . ",";
        $sql .= "'" . $this->db->idate(dol_now()) . "'";
        $sql .= ")";

        dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . 'questionnairedet');


            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEQUESTIONNAIRE_CLONE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
            } else {
                $this->db->rollback();
                return -1 * $error;
            }
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }


    /**
     *    Insert line into database
     *
     * @param User $user User that modify
     * @param int $notrigger 1 = disable triggers
     * @return        int                        <0 if KO, >0 if OK
     */
    function insert($user = null, $notrigger = 0)
    {
        global $langs, $conf;

        $error = 0;

        if ($this->check() < 0) {
            return -1;
        }

        dol_syslog(get_class($this) . "::insert rang=" . $this->rang);

        // Clean parameters
        if (empty($this->rang)) $this->rang = 0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "questionnairedet (";
        $sql .= " fk_questionnaire,";
        $sql .= " code,";
        $sql .= " label,";
        $sql .= " type,";
        $sql .= " rang,";
        $sql .= " postfill,";
        $sql .= " prefill,";
        $sql .= " fk_cond,";
        $sql .= " fk_op_cond,";
        $sql .= " val_cond,";
        $sql .= " param,";
        $sql .= " help,";
        $sql .= " datec,";
        $sql .= " crypted,";
        $sql .= " visibility,";
        $sql .= " inapp,";
        $sql .= " mandatory,";
        $sql .= " user_author_id,";
        $sql .= " tms";
        $sql .= " )";
        $sql .= " VALUES (";
        $sql .= " " . $this->fk_questionnaire . ",";
        $sql .= " '" . $this->db->escape($this->code) . "',";
        $sql .= " '" . $this->db->escape($this->label) . "',";
        $sql .= " '" . $this->db->escape($this->type) . "',";
        $sql .= " " . $this->rang . ",";
        $sql .= " '" . $this->db->escape($this->postfill) . "',";
        $sql .= " '" . $this->db->escape($this->prefill) . "',";
        $sql .= " " . ($this->fk_cond > 0 ? $this->fk_cond : 0) . ",";
        $sql .= " " . ($this->fk_op_cond > 0 ? $this->fk_op_cond : 0) . ",";
        $sql .= " '" . $this->db->escape($this->val_cond) . "',";
        $sql .= " '" . $this->db->escape($this->param) . "',";
        $sql .= " '" . $this->db->escape($this->help) . "',";
        $sql .= " '" . $this->db->idate(dol_now()) . "',";
        $sql .= " " . $this->crypted . ",";
        $sql .= " " . $this->visibility . ",";
        $sql .= " " . $this->inapp . ",";
        $sql .= " " . $this->mandatory . ",";
        $sql .= " " . (is_object($user) ? $user->id : 0) . ",";
        $sql .= "'" . $this->db->idate(dol_now()) . "'";
        $sql .= ")";

        dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . 'questionnairedet');

            $type2 = "TEXT";

            if ($this->type == 'int') {
                $type = "INT(11)";
            } else if ($this->type == 'numeric') {
                $type = "DOUBLE";
            } else if ($this->type == 'date') {
                $type = "DATE";
            } else if ($this->type == 'datetime') {
                $type = "DATETIME";
            } else if ($this->type == 'radio' || $this->type == 'list' || $this->type == 'string' || $this->type == 'table' || $this->type == 'map' || $this->type == 'checkbox') {
                $type = "VARCHAR(255)";
            } else {
                $type = "TEXT"; // Default
            }

            if ($this->crypted) {
                $type = 'TEXT'; // Default
            }

            $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." ADD COLUMN `" . $this->code . "` $type NULL";
            $resql = $this->db->query($sql);

            $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnairefval".$this->fk_questionnaire." ADD COLUMN `" . $this->code . "` $type2 NULL";
            $resql = $this->db->query($sql);

            if ($resql) {
                if (!$error && !$notrigger) {
                    // Call trigger
                    $result = $this->call_trigger('LINEQUESTIONNAIRE_INSERT', $user);
                    if ($result < 0) $error++;
                    // End call triggers
                }

                if (!$error) {
                    $this->db->commit();
                    return 1;
                }
            } else {
                $this->error = $this->db->error();
                $this->db->rollback();
                return -2;
            }


            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -2;
        }
    }

    /**
     *    Uncrypt existing values
     *
     */
    function uncrypt_values()
    {
        global $conf, $langs;

        $error = 0;

        //$this->fk_questionnaire
        $sql = "SELECT rowid, " . $this->code . " as val FROM " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." WHERE 1";

        $values = array();

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);

                    $value = $obj->val;

                    if (strpos($value, ':') !== false) {
                        list($key, $value) = explode(':', $value);
                        $value = mb_dol_decode($value, $key);
                    }

                    $values[$obj->rowid] = $value;

                    $i++;
                }
            }
            return $values;
        } else {
            dol_print_error($this->db);
        }

        return -1;
    }

    /**
     *    Crypt existing values
     *
     */
    function crypt_values()
    {
        global $conf, $langs;

        $error = 0;

        $num_bytes = !empty($conf->global->QUESTIONNAIRE_NUM_BYTES) ? intval($conf->global->QUESTIONNAIRE_NUM_BYTES) : 10;

        $sql = "SELECT rowid, " . $this->code . " as val FROM " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." WHERE 1";

        $values = array();

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);

                    $key = bin2hex(random_bytes($num_bytes));

                    $value = mb_dol_encode($obj->val, $key);
                    $value = $key . ':' . $value;

                    $values[$obj->rowid] = $value;

                    $i++;
                }
            }
            return $values;
        } else {
            dol_print_error($this->db);
        }

        return -1;
    }

    /**
     *    Crypt existing values
     *
     */
    function update_values($values)
    {
        global $conf, $langs;

        $error = 0;

        if (count($values) > 0) {
            foreach ($values as $rowid => $value) {
                $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." SET " . $this->code . " = '" . $this->db->escape($value) . "' WHERE rowid = " . $rowid;
                $result = $this->db->query($sql);

                $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnairefval".$this->fk_questionnaire." SET " . $this->code . " = '" . $this->db->escape($value) . "' WHERE rowid = " . $rowid;
                $result = $this->db->query($sql);
                if ($result) {
                    // More to do ?
                } else {
                    $error++;
                }
            }
        }

        return $error > 0 ? -$error : 1;
    }

    /**
     *    Update the line object into db
     *
     * @param User $user User that modify
     * @param int $notrigger 1 = disable triggers
     * @return        int        <0 si ko, >0 si ok
     */
    function update(User $user, $notrigger = 0)
    {
        global $conf, $langs;

        $error = 0;

        if (!empty($this->oldline) && $this->code != $this->oldline->code) {
            if ($this->check($this->rowid) < 0) {
                return -1;
            }
        }


        // Clean parameters
        if (empty($this->rang)) $this->rang = 0;

        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnairedet SET";
        $sql .= " fk_questionnaire = " . $this->fk_questionnaire;
        $sql .= " , code = '" . $this->db->escape($this->code) . "'";
        $sql .= " , label = " . (!empty($this->label) ? "'" . $this->db->escape($this->label) . "'" : "null");
        $sql .= " , type = '" . $this->db->escape($this->type) . "'";
        $sql .= " , rang = " . $this->rang;
        $sql .= " , postfill = '" . $this->db->escape($this->postfill) . "'";
        $sql .= " , prefill = '" . $this->db->escape($this->prefill) . "'";
        $sql .= " , fk_cond = " . ($this->fk_cond > 0 ? $this->fk_cond : 0);
        $sql .= " , fk_op_cond = " . ($this->fk_op_cond > 0 ? $this->fk_op_cond : 0);
        $sql .= " , val_cond = '" . $this->db->escape($this->val_cond) . "'";
        $sql .= " , param = '" . $this->db->escape($this->param) . "'";
        $sql .= " , help = '" . $this->db->escape($this->help) . "'";
        $sql .= " , crypted = " . $this->crypted;
        $sql .= " , visibility = " . $this->visibility;
        $sql .= " , inapp = " . $this->inapp;
        $sql .= " , mandatory = " . $this->mandatory;
        $sql .= " , tms = '" . $this->db->idate(dol_now()) . "'";
        $sql .= " WHERE rowid = " . $this->rowid;

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $values = array();

            if ($this->type == 'int') {
                $type = "INT(11)";
            } else if ($this->type == 'numeric') {
                $type = "DOUBLE";
            } else if ($this->type == 'date') {
                $type = "DATE";
            } else if ($this->type == 'datetime') {
                $type = "DATETIME";
            } else if ($this->type == 'list' || $this->type == 'radio' || $this->type == 'string' || $this->type == 'table' || $this->type == 'map' || $this->type == 'checkbox') {
                $type = "VARCHAR(255)";
            } else {
                $type = 'TEXT'; // Default
            }

            if ($this->oldline->crypted && !$this->crypted) {
                $values = $this->uncrypt_values();
            }

            if (!$this->oldline->crypted && $this->crypted) {
                $values = $this->crypt_values();
            }

            if ($this->crypted) {
                $type = 'TEXT'; // Default
            }

            $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnaireval".$this->fk_questionnaire." CHANGE `" . $this->oldline->code . "` `" . $this->code . "` $type NULL";
            $resql = $this->db->query($sql);

            $sql = "ALTER TABLE " . MAIN_DB_PREFIX . "questionnairefval".$this->fk_questionnaire." CHANGE `" . $this->oldline->code . "` `" . $this->code . "` TEXT NULL";
            $resql = $this->db->query($sql);

            if ($resql) {
                if (!$error && !$notrigger) {
                    if (count($values) > 0) {
                        $result = $this->update_values($values);
                        if ($result < 0) {
                            $error++;
                        } else {
                            $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnairedet SET";
                            $sql .= " crypted = " . $this->crypted;
                            $sql .= " WHERE code = '" . $this->db->escape($this->code) . "'";

                            $resql = $this->db->query($sql);
                        }
                    }

                    // Call trigger
                    $result = $this->call_trigger('LINEQUESTIONNAIRE_UPDATE', $user);
                    if ($result < 0) $error++;
                    // End call triggers
                }

                if (!$error) {
                    $this->db->commit();
                    return 1;
                }
            } else {
                $this->error = $this->db->error();
                $this->db->rollback();
                return -2;
            }

            foreach ($this->errors as $errmsg) {
                dol_syslog(get_class($this) . "::update " . $errmsg, LOG_ERR);
                $this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
            }
            $this->db->rollback();
            return -1 * $error;
        } else {
            $this->error = $this->db->error();
            $this->db->rollback();
            return -3;
        }
    }
}


