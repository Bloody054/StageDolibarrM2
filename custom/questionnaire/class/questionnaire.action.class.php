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
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/timespent.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
dol_include_once("/reponse/class/reponse.class.php");
dol_include_once("/questionnaire/lib/questionnaire.lib.php");

/**
 * Class to manage products or services
 */
class QuestionnaireAction extends CommonObject
{
    public $element = 'questionnaire_action';
    public $table_element = 'questionnaire_action';
    public $table_element_line = 'questionnaire_actiondet';

    public $fk_element = 'fk_questionnaire_action';
    public $picto = 'questionnaire@questionnaire';
    public $ismultientitymanaged = 1;    // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

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
     * Questionnaire id
     * @var string
     */
    public $fk_questionnaire = 0;

    /**
     * Type
     * @var string
     */
    public $type;

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
     * Entity
     * @var int
     */
    public $entity;

    /**
     * @var QuestionnaireActionLine[]
     */
    public $lines = array();


    const SOCIETE_TYPE = 'societe';
    const PROJECT_TYPE = 'projet';
    const TASK_TIMESPENT_TYPE = 'Tache (temps passé)';
    const STOCK_INCREMENT_TYPE = 'Incrémentation du stock';

    /**
     *  Constructor
     *
     * @param DoliDB $db Database handler
     */
    function __construct($db)
    {
        global $langs;

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

        $now = dol_now();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "questionnaire_action (";
        $sql .= " fk_questionnaire";
        $sql .= " , type";
        $sql .= " , datec";
        $sql .= " , user_author_id";
        $sql .= " , active";
        $sql .= " , entity";
        $sql .= " , tms";
        $sql .= ") VALUES (";
        $sql .= " " . (!empty($this->fk_questionnaire) ? $this->fk_questionnaire : "0");
        $sql .= ", " . (!empty($this->type) ? "'" . $this->db->escape($this->type) . "'" : "null");
        $sql .= ", " . (!empty($this->datec) ? "'" . $this->db->idate($this->datec) . "'" : "null");
        $sql .= ", " . (!empty($this->user_author_id) ? $this->user_author_id : "0");
        $sql .= ", " . (!empty($this->active) ? $this->active : "0");
        $sql .= ", " . (!empty($this->entity) ? $this->entity : "0");
        $sql .= ", '" . $this->db->idate($now) . "'";
        $sql .= ")";

        dol_syslog(get_class($this) . "::Create", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $id = $this->db->last_insert_id(MAIN_DB_PREFIX . "questionnaire_action");

            if ($id > 0) {
                $this->id = $id;
            } else {
                $error++;
                $this->error = 'ErrorFailedToGetInsertedId';
            }
        } else {
            $error++;
            $this->error = $this->db->lasterror();
        }


        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_ACTION_CREATE', $user);
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

        $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnaire_action";
        $sql .= " SET type = " . (!empty($this->type) ? "'" . $this->db->escape($this->type) . "'" : "null");
        $sql .= ", active = " . (!empty($this->active) ? $this->active : "0");
        $sql .= ", fk_questionnaire = " . (!empty($this->fk_questionnaire) ? $this->fk_questionnaire : "0");
        $sql .= ", tms = '" . $this->db->idate(dol_now()) . "'";
        $sql .= " WHERE rowid = " . $id;

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_ACTION_MODIFY', $user);
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
    function fetch($id)
    {
        global $langs, $conf;

        dol_syslog(get_class($this) . "::fetch id=" . $id);


        // Check parameters
        if (!$id) {
            $this->error = 'ErrorWrongParameters';
            //dol_print_error(get_class($this)."::fetch ".$this->error);
            return -1;
        }

        $sql = "SELECT e.rowid, e.type, e.datec, e.fk_questionnaire, e.user_author_id, e.active, e.entity ";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire_action e";
        $sql .= " WHERE e.rowid = " . $id;

        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql) > 0) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;

                $this->user_author_id = $obj->user_author_id;
                $this->type = $obj->type;
                $this->fk_questionnaire = $obj->fk_questionnaire;
                $this->datec = $this->db->jdate($obj->datec);
                $this->active = $obj->active;
                $this->entity = $obj->entity;

                $this->db->free($resql);

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

        $sqlz = "DELETE FROM " . MAIN_DB_PREFIX . "questionnaire_action";
        $sqlz .= " WHERE rowid = " . $id;
        dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if (!$resultz) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        $sqlz = "DELETE FROM " . MAIN_DB_PREFIX . "questionnaire_actiondet";
        $sqlz .= " WHERE fk_questionnaire_action = " . $id;
        dol_syslog(get_class($this) . '::delete', LOG_DEBUG);
        $resultz = $this->db->query($sqlz);

        if (!$resultz) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (!$error) {
            if (!$notrigger) {
                // Call trigger
                $result = $this->call_trigger('QUESTIONNAIRE_ACTION_DELETE', $user);
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

        $sql = "SELECT l.rowid, l.fk_questionnaire_action, l.field, l.use_for_fetch, l.fk_line, qd.code, qd.label, ";
        $sql .= " l.datec, l.user_author_id, l.tms";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire_actiondet as l";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "questionnairedet as qd ON l.fk_line = qd.rowid";
        $sql .= " WHERE l.fk_questionnaire_action = " . $this->id;

        dol_syslog(get_class($this) . "::fetch_lines", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);

            $i = 0;
            while ($i < $num) {
                $objp = $this->db->fetch_object($result);

                $line = new QuestionnaireActionLine($this->db);

                $line->rowid = $objp->rowid;
                $line->id = $objp->rowid;
                $line->fk_questionnaire_action = $objp->fk_questionnaire_action;
                $line->label = $objp->code; // $obj->label
                $line->code = $objp->code; // $obj->label
                $line->field = $objp->field;
                $line->use_for_fetch = $objp->use_for_fetch;
                $line->fk_line = $objp->fk_line;
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
    function addline($fk_line, $field, $use_for_fetch, $notrigger = 0)
    {
        global $mysoc, $conf, $langs, $user;

        dol_syslog(get_class($this) . "::addline", LOG_DEBUG);
        // Insert line
        $this->line = new QuestionnaireActionLine($this->db);
        $this->line->context = $this->context;

        $this->line->fk_questionnaire_action = $this->id;
        $this->line->fk_line = $fk_line;
        $this->line->field = $field;
        $this->line->use_for_fetch = $use_for_fetch;

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
    function updateline($rowid, $fk_line, $field, $use_for_fetch, $notrigger = 0)
    {
        global $conf, $mysoc, $langs, $user;

        dol_syslog(get_class($this) . "::updateline");

        $this->db->begin();


        //Fetch current line from the database and then clone the object and set it in $oldline property
        $line = new QuestionnaireActionLine($this->db);
        $line->fetch($rowid);

        $staticline = clone $line;

        $line->oldline = $staticline;

        $this->line = $line;
        $this->line->context = $this->context;

        $this->line->rowid = $rowid;
        $this->line->fk_line = $fk_line;
        $this->line->field = $field;
        $this->line->use_for_fetch = $use_for_fetch;
        $this->line->fk_questionnaire_action = $this->id;

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

        $line = new QuestionnaireActionLine($this->db);
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
        return '';
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
        print '<th class="linecollabel">' . $langs->trans('QuestionnaireFieldCode') . '</th>';

        // Name
        print '<th class="linecolfield">' . $langs->trans('QuestionnaireField') . '</th>';

        print '<th class="linecoluseforfetch">' . $langs->trans('QuestionnaireUseForFetch') .img_help(1,$langs->trans('QuestionnaireUseForFetch')) .'</th>';

        print '<th class="linecoledit"></th>';  // No width to allow autodim

        print '<th class="linecoldelete" width="10"></th>';

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

        $form = new Form($this->db);

        $questionnaire = new Questionnaire($this->db);
        $questionnaire->fetch($this->fk_questionnaire);


        $lines = $questionnaire->getFormLines(0,1);
        $fields = $this->getFields();

        // Ligne en mode visu
        if ($action != 'editline' || $selected != $line->id) {

            print '<tr  id="row-' . $line->id . '" class="drag drop oddeven" ' . $domData . ' >';
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center">' . ($i + 1) . '</td>';
            }
            print '<td class="linecollabel"><div id="line_' . $line->id . '"></div>';
            print $lines[$line->fk_line];
            print '</td>';

            //
            print '<td class="linecolfield nowrap">';
            if (!empty($line->field)) {
                print  isset($fields[$line->field]) ? $fields[$line->field] : '&nbsp;';
            } else {
                print '&nbsp;';
            }
            print '</td>';

            print '<td class="linecoluseforfetch nowrap">';
            print yn($line->use_for_fetch);
            print '</td>';

            if ($action != 'selectlines') {
                print '<td class="linecoledit" align="center">';
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->fk_questionnaire . '&action=editline&lineid=' . $line->id . '&token=' . newToken() . '">';
                print img_edit();
                print '</a>';
                print '</td>';

                print '<td class="linecoldelete" align="center">';
                print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $this->fk_questionnaire . '&action=ask_deleteline&lineid=' . $line->id . '&token=' . newToken() . '">';
                print img_delete();
                print '</a>';
                print '</td>';
            } else {
                print '<td colspan="2">&nbsp;</td>';
            }

            print '</tr>';
        }

        // Ligne en mode update
        if ($action == 'editline' && $selected == $line->id) {

            print '<tr ' . $bc[$var] . ' >';
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center">' . ($i + 1) . '</td>';
            }
            print '<td>';
            print '<div id="qline_' . $line->id . '"></div>';
            print '<input type="hidden" name="lineid" value="' . $line->id . '">';
                        
            //print $form->selectarray("fk_line", $lines, $line->fk_line);
            print '</td>';

            print '<td>' . "\n";
            print $form->selectarray("field", $fields, $line->field);
            print '</td>' . "\n";

            print '<td>' . "\n";
            print $form->selectyesno("use_for_fetch", $line->use_for_fetch, 1);
            print '</td>' . "\n";

            print '<td align="center" colspan="2" valign="middle">' . "\n";
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

        $form = new Form($this->db);

        $nolinesbefore = (count($this->lines) == 0);

        $questionnaire = new Questionnaire($this->db);
        $questionnaire->fetch($this->fk_questionnaire);


        $lines = $questionnaire->getFormLines(0,1);

        $fields = $this->getFields();

        if ($nolinesbefore) {

            print '<tr class="liste_titre' . ($nolinesbefore ? '' : ' liste_titre_add_') . ' nodrag nodrop">' . "\n";
            if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
                print '<td class="linecolnum" align="center"></td>' . "\n";
            }

            print '<td class="linecollabel">' . "\n";
            print '<div id="add"></div><span class="hideonsmartphone">' . $langs->trans('AddNewLine') . '</span>' . "\n";
            print '</td>' . "\n";
            print '<td class="linecoltype"><span id="title_type">' . $langs->trans('QuestionnaireField') . '</span></td>' . "\n";
            print '<td class="linecoluseforfetch">' . $langs->trans('QuestionnaireUseForFetch') . '</td>' . "\n";

            print '<td class="linecoledit" colspan="2">&nbsp;</td>' . "\n";
            print '</tr>' . "\n";
        }

        print '<tr class="pair nodrag nodrop nohoverpair' . ($nolinesbefore ? '' : ' liste_titre_create') . '">' . "\n";

        // Adds a line numbering column
        if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
            print '<td class="nobottom linecolnum" align="center" width="5"></td>' . "\n";
        }

        print '<td class="nobottom linecollabel">' . "\n";
                
        print $form->selectarray("fk_line", $lines, GETPOST('fk_line', 'int'));
        //TODO, passer en multi select et ajouter des champs de type 'séparateur' print $form->multiselectarray("fk_line", $lines, GETPOST('fk_line', 'int'));
        print '</td>' . "\n";

        print '<td class="nobottom linecolfield">' . "\n";
        print $form->selectarray("field", $fields, GETPOST('field', 'alpha'));
        print '</td>' . "\n";

        print '<td class="nobottom linecoluseforfetch">' . "\n";
        print $form->selectyesno("use_for_fetch", GETPOST('use_for_fetch', 'int'), 1);
        print '</td>' . "\n";

        print '<td class="nobottom linecoledit" align="center" valign="middle" colspan="2">' . "\n";
        print '<input type="submit" class="button" value="' . $langs->trans('Add') . '" name="addline" id="addline">' . "\n";
        print '</td>' . "\n";

        print '</tr>' . "\n";
    }

    /**
     *  Return list of questionnaires
     *
     * @return     int                    -1 if KO, array with result if OK
     */
    function liste_array($fk_questionnaire, $sortfield = 'e.rowid', $sortorder = 'DESC')
    {
        global $user;

        $actions = array();

        $sql = "SELECT e.rowid as id, e.fk_questionnaire, e.datec";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire_action as e";
        $sql .= " WHERE e.entity IN (" . getEntity('questionnaire') . ")";
        $sql .= " AND e.fk_questionnaire = " . intval($fk_questionnaire);
        $sql .= $this->db->order($sortfield, $sortorder);

        $result = $this->db->query($sql);
        if ($result) {
            $num = $this->db->num_rows($result);
            if ($num) {
                $i = 0;
                while ($i < $num) {
                    $obj = $this->db->fetch_object($result);
                    $action = new QuestionnaireAction($this->db);
                    $action->fetch($obj->id);

                    $actions[$obj->id] = $action;

                    $i++;
                }
            }

            return $actions;
        } else {
            dol_print_error($this->db);
            return -1;
        }
    }


    /**
     * Get all fields
     *
     * @return    array
     */
    function getFields($with_prefix = 0)
    {
        global $langs;

        $langs->loadLangs(array('projects', "companies", "commercial", "bills", "banks", "users","stockmouvement"));

        $fields = array();

        if ($this->type == self::SOCIETE_TYPE) {
            $object = new Societe($this->db);
            $objectFields = $object->fields;
            foreach ($objectFields as $f => $field) {
                if ($f == 'nom') {
                    $f = 'name'; // Grrr...
                }

                if ($with_prefix) {
                    $f = $this->type.".".$f;
                }

                $fields[$f] = $langs->trans($field['label']);
            }

            // Extrafields
            $extrafields = new ExtraFields($this->db);
            $extrafields->fetch_name_optionals_label($object->table_element);

            if (isset($extrafields->attributes[$object->table_element]['type']) && count($extrafields->attributes[$object->table_element]['type'])) {
                foreach ($extrafields->attributes[$object->table_element]['type'] as $key => $dummy) {
                    $f = 'options_'.$key;
                    if ($with_prefix) {
                        $f = $this->type.".".$f;
                    }
                    $fields[$f] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]);
                }
            }
        
        } else if ($this->type == self::PROJECT_TYPE) {
            $object = new Project($this->db);
            $objectFields = $object->fields;
            
            foreach ($objectFields as $f => $field) {
                if ($with_prefix) {
                    $f = $this->type.".".$f;
                }

                $fields[$f] = $langs->trans($field['label']);
            }

            // Extrafields
            $extrafields = new ExtraFields($this->db);
            $extrafields->fetch_name_optionals_label($object->table_element);

            if (isset($extrafields->attributes[$object->table_element]['type']) && count($extrafields->attributes[$object->table_element]['type'])) {
                foreach ($extrafields->attributes[$object->table_element]['type'] as $key => $dummy) {
                    $f = 'options_'.$key;
                    if ($with_prefix) {
                        $f = $this->type.".".$f;
                    }
                    $fields[$f] = $langs->trans($extrafields->attributes[$object->table_element]['label'][$key]);
                }
            }
        
        } else if ($this->type == self::TASK_TIMESPENT_TYPE) { // on utilise certains temps de la tâche, mais pas tous, de toute façon la class task n'a pas de propriété fields (en v18.0.5)
            $object = new Task($this->db);
            $fields = array(
                'id'=>'ID de la tâche',
                'timespent_date'=>'Date du temps passé',
                'timespent_duration'=>'Durée en heure',
                'field_user'=>'Qui',
                'thm'=>'Taux Horaire Moyen (THM)',
                'timespent_note'=>'Commentaire',
            );//les autres champs ne servent pas pour le moment.
            $objectFields = $object->fields;
                                    
            foreach ($objectFields as $f => $field) {
                if ($with_prefix) {
                    $f = $this->type.".".$f;
                }

                $fields[$f] = $langs->trans($field['label']);
            }
        
        } else if ($this->type == self::STOCK_INCREMENT_TYPE) {
            $object = new MouvementStock($this->db);
            $objectFields = $object->fields;
            foreach ($objectFields as $f => $field) {
                if ($f == 'nom') {
                    $f = 'name'; // Grrr...
                }

                if ($with_prefix) {
                    $f = $this->type.".".$f;
                }

                $fields[$f] = $langs->trans($field['label']);
            }
        }

        asort($fields);
        return $fields;
    }


    /**
     * Get all types
     *
     * @return    array
     */
    function getTypes()
    {
        global $langs;

        $types = array(
            self::SOCIETE_TYPE => $langs->trans('QuestionActionSociety'),
            self::PROJECT_TYPE => $langs->trans('QuestionActionProject'),
            self::TASK_TIMESPENT_TYPE => $langs->trans('QuestionActionTaskTimeSpent'),
            self::STOCK_INCREMENT_TYPE => $langs->trans('QuestionActionStockIncrement'),
        );

        return $types;
    }

    function getTypesHelp()
    {
        global $langs;

        $typeshelp = array(
            self::SOCIETE_TYPE => $langs->trans('QuestionActionSocietyHelp'),
            self::PROJECT_TYPE => $langs->trans('QuestionActionProjectHelp'),
            self::TASK_TIMESPENT_TYPE => $langs->trans('QuestionActionTaskTimeSpentHelp'),
            self::STOCK_INCREMENT_TYPE => $langs->trans('QuestionActionStockIncrementHelp'),
        );

        return $typeshelp;
    }
}


/**
 *  Class to manage questionnaire lines
 */
class QuestionnaireActionLine extends CommonObjectLine
{
    /**
     * @var string ID to identify managed object
     */
    public $element = 'questionnaire_actiondet';

    public $table_element = 'questionnaire_actiondet';

    var $oldline;

    /**
     * Id of questionnaire
     * @var int
     */
    public $fk_questionnaire_action;

    /**
     * Id of questionnaire line
     * @var int
     */
    public $fk_line = 0;

    /**
     * Use for fetch
     * @var int
     */
    public $use_for_fetch = 0;

    /**
     * Label
     * @var string
     */
    public $label;

    /**
     * Code
     * @var string
     */
    public $code;

    /**
     * Target
     * @var string
     */
    public $field;

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
        $sql = "SELECT l.rowid, l.fk_questionnaire_action, l.fk_line, l.field, l.datec, l.use_for_fetch, l.user_author_id, l.tms";
        $sql .= " FROM " . MAIN_DB_PREFIX . "questionnaire_actiondet as l";
        $sql .= " WHERE l.rowid = " . $rowid;

        dol_syslog(get_class($this) . "::fetch", LOG_DEBUG);

        $result = $this->db->query($sql);
        if ($result) {
            $objp = $this->db->fetch_object($result);

            $this->rowid = $objp->rowid;
            $this->id = $objp->rowid;
            $this->fk_line = $objp->rowid;
            $this->field = $objp->field;
            $this->fk_questionnaire_action = $objp->fk_questionnaire_action;
            $this->use_for_fetch = $objp->use_for_fetch;

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

        $sql = 'DELETE FROM ' . MAIN_DB_PREFIX . "questionnaire_actiondet WHERE rowid=" . $this->rowid;

        dol_syslog(get_class($this) . "::delete", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEQUESTIONNAIRE_ACTION_DELETE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
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
        if (empty($this->fk_line) || $this->fk_line < 0) $this->fk_line = 0;

        if ($this->fk_line == 0) {
            return -1;
        }

        dol_syslog(get_class($this) . "::insert rang=" . $this->rang);

        // Clean parameters
        if (empty($this->rang)) $this->rang = 0;

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "questionnaire_actiondet (";
        $sql .= " fk_line,";
        $sql .= " fk_questionnaire_action,";
        $sql .= " use_for_fetch,";
        $sql .= " field,";
        $sql .= " datec,";
        $sql .= " user_author_id,";
        $sql .= " tms";
        $sql .= " )";
        $sql .= " VALUES (";
        $sql .= " " . ($this->fk_line > 0 ? $this->fk_line : 0) . ",";
        $sql .= " " . ($this->fk_questionnaire_action > 0 ? $this->fk_questionnaire_action : 0) . ",";
        $sql .= " " . (!empty($this->use_for_fetch) ? 1 : 0) . ",";
        $sql .= " '" . $this->db->escape($this->field) . "',";
        $sql .= " '" . $this->db->idate(dol_now()) . "',";
        $sql .= " " . (is_object($user) ? $user->id : 0) . ",";
        $sql .= "'" . $this->db->idate(dol_now()) . "'";
        $sql .= ")";

        dol_syslog(get_class($this) . "::insert", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            $this->rowid = $this->db->last_insert_id(MAIN_DB_PREFIX . 'questionnaire_actiondet');

            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEQUESTIONNAIRE_ACTION_INSERT', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
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
        if (empty($this->fk_line) || $this->fk_line < 0) $this->fk_line = 0;

        if ($this->fk_line == 0) {
            return -1;
        }


        // Clean parameters
        $this->db->begin();

        // Mise a jour ligne en base
        $sql = "UPDATE " . MAIN_DB_PREFIX . "questionnaire_actiondet SET";
        $sql .= " fk_line = " . ($this->fk_line > 0 ? $this->fk_line : 0);
        $sql .= " , use_for_fetch = " . (!empty($this->use_for_fetch) ? 1 : 0);
        $sql .= " , field = '" . $this->db->escape($this->field) . "'";
        $sql .= " , tms = '" . $this->db->idate(dol_now()) . "'";
        $sql .= " WHERE rowid = " . $this->rowid;

        dol_syslog(get_class($this) . "::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if (!$error && !$notrigger) {
                // Call trigger
                $result = $this->call_trigger('LINEQUESTIONNAIRE_ACTION_UPDATE', $user);
                if ($result < 0) $error++;
                // End call triggers
            }

            if (!$error) {
                $this->db->commit();
                return 1;
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


