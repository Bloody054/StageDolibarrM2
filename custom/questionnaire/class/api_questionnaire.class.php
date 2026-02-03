<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 *
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

 use Luracast\Restler\RestException;

 dol_include_once("/questionnaire/class/questionnaire.class.php");

/**
 * API class for questionnaires
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class QuestionnaireApi extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array();

    /**
     * Constructor
     */
    function __construct()
    {
		  global $db, $conf;
		  $this->db = $db;
    }

    /**
     * Get properties of a questionnaire object
     *
     * Return an array with questionnaire questionnaires
     *
     * @param 	int 	$id ID of questionnaire
     * @return 	array|mixed data without useless questionnaire
     *
     * @throws 	RestException
     */
	
	function get($id)
	{
		global $conf, $langs;

		$langs->load('questionnaire@questionnaire');

		if(! DolibarrApiAccess::$user->rights->questionnaire->lire) {
			throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
		}

        $questionnaire = new Questionnaire($this->db);
		$result = $questionnaire->fetch($id);
		if (! $result) {
			throw new RestException(404, $langs->transnoentities('FormNotFound'));
		}

		return $this->_cleanObjectDatas($questionnaire);
    }
	
	/**
	 * List Questionnaires
	 *
	 * Get a list of questionnaires
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of Questionnaires objects
	 */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
	    global $db, $conf;

	    $obj_ret = array();

		if(! DolibarrApiAccess::$user->rights->questionnaire->lire) {
			throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
	    }

	    // case of external user, $societe param is ignored and replaced by user's socid
	    //$socid = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $societe;

	    $sql = "SELECT t.rowid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."questionnaire as t";
	    $sql.= ' WHERE t.entity IN ('.getEntity('questionnaire').') AND t.active = 1';

		// Add sql filters
        /*if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }*/

	    $sql.= $db->order($sortfield, $sortorder);
	    if ($limit)	{
	        if ($page < 0)
	        {
	            $page = 0;
	        }
	        $offset = $limit * $page;

	        $sql.= $db->plimit($limit + 1, $offset);
	    }

	    $result = $db->query($sql);

	    if ($result)
	    {
	        $num = $db->num_rows($result);
	        $min = min($num, ($limit <= 0 ? $num : $limit));
	        while ($i < $min)
	        {
				$obj = $db->fetch_object($result);
				
	            $questionnaire_static = new Questionnaire($db);
	            if($questionnaire_static->fetch($obj->rowid)) {
	                $obj_ret[] = $this->_cleanObjectDatas($questionnaire_static);
	            }
	            $i++;
	        }
	    }
	    else {
			throw new RestException(503, $langs->transnoentities('ErrorWhileRetrievingFormList'));
		}
		/*
	    if( ! count($obj_ret)) {
	        throw new RestException(404, 'No Questionnaire found');
	    }*/
	    return $obj_ret;
	}

    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {

        //$object = parent::_cleanObjectDatas($object);

		$ret = new stdClass();
		$ret->id = intval($object->id);
		$ret->ref = $object->ref;
		$ret->title = $object->title;
		$ret->selected = boolval($object->selected);
        $ret->active = boolval($object->active);
        $ret->fk_email 		= intval($object->fk_email);
        $ret->fk_name 		= intval($object->fk_name);
        $ret->fk_date 		= intval($object->fk_date);
        $ret->fk_location 		= intval($object->fk_location);

		$ret->items = array();

		if (count($object->lines)) {
			foreach ($object->lines as $l) {

				$line = new stdClass();
				$line->id               = intval($l->id);
				$line->rank             = intval($l->rang);
				$line->questionnaire_id          = intval($l->fk_questionnaire);
				$line->code             = $l->code;
				$line->fk_cond          = intval($l->fk_cond);
				$line->fk_op_cond       = intval($l->fk_op_cond);
				$line->val_cond         = $l->val_cond;
				$line->prefill          = $l->prefill;
				$line->label            = $l->label;
				$line->type             = $l->type;
				$line->param     		= $l->param;
                $line->visibility        = boolval($l->visibility);
                $line->inapp        = boolval($l->inapp);
				$line->mandatory        = boolval($l->mandatory);
				$line->help             = $l->help;

				$ret->items[] = $line;
			}
		}

		return $ret;
    }
}
