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

require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

dol_include_once("/reponse/class/reponse.class.php");

 if (!empty($conf->questionnaire->enabled)) {
	dol_include_once("/questionnaire/class/html.form.questionnaire.class.php");
 }
/**
 * API class for reponses
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */

$user = null;

class ReponseApi extends DolibarrApi
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
     * Get properties of a reponse object
     *
     * Return an array with reponse informations
     *
     * @param 	int 	$id ID of reponse
     * @return 	array|mixed data without useless information
     *
     * @throws 	RestException
     */

    function get($id)
    {
        global $conf, $langs, $user;

        $langs->load('reponse@reponse');


        if(! DolibarrApiAccess::$user->rights->reponse->lire) {
            throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
        }

        $user = DolibarrApiAccess::$user;

        $reponse = new Reponse($this->db);
        $result = $reponse->fetch($id);
        if (! $result) {
            throw new RestException(404, $langs->transnoentities('ReponseNotFound'));
        }

        return $this->_cleanObjectDatas($reponse);
    }


    /**
     * Create reponse object
     *
     * @return  array|mixed data without useless information
     */
    function post()
    {
		global $langs, $user;

		$langs->load('reponse@reponse');

        if(! DolibarrApiAccess::$user->rights->reponse->creer) {
	        throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
		}

        $user = DolibarrApiAccess::$user;
        
        $object = new Reponse($this->db);
        $object->fk_questionnaire = GETPOST('fk_questionnaire', 'int');
        $object->fk_soc = GETPOST('fk_soc', 'int');

        if ($object->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, $langs->transnoentities('ErrorWhileCreatingReponse'));
        }
        else
        {
            $object->fill(DolibarrApiAccess::$user);
            $res = $object->fetch($object->id);
        }

        return array(
			'error' => false,
			'message' => $langs->transnoentities('ReponseHasBeenCreated', $object->ref),
			'id' => intval($object->id),
			'ref' => $object->ref,
 		);
    }

	/**
	 * List Reponses
	 *
	 * Get a list of reponses
	 *
	 * @param string	$sortfield	Sort field
	 * @param string	$sortorder	Sort order
	 * @param int		$limit		Limit for list
	 * @param int		$page		Page number
	 * @param string   	$user_ids   Reponse ids filter field. Example: '1' or '1,2,3'          {@pattern /^[0-9,]*$/i}
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
	 * @return  array               Array of Reponses objects
	 */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $reponse_ids = 0, $sqlfilters = '', $datestart = '', $dateend = '')
    {
	    global $db, $conf, $langs, $user;

	    $obj_ret = array();

		if(!DolibarrApiAccess::$user->rights->reponse->lire) {
	        throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
	    }

        if (DolibarrApiAccess::$user->id == $conf->global->CONNECT_AGENT_ID) {
            return $obj_ret;
        }

		$user = DolibarrApiAccess::$user;

	    $sql = "SELECT t.rowid";
	    $sql.= " FROM ".MAIN_DB_PREFIX."reponse as t";
		$sql.= ' WHERE t.is_draft = 0 AND t.entity IN ('.getEntity('reponse').')';
		if (!DolibarrApiAccess::$user->rights->user->user->lire) {
			$sql.= ' AND t.user_author_id = '.DolibarrApiAccess::$user->id;
		}

		if (!empty($datestart) && !empty($dateend)) {
            $sql.= " AND t.datec BETWEEN '".$db->idate($datestart)."' AND '".$db->idate($dateend)."'";
		} else if (!empty($datestart)) {
            $sql.= " AND t.datec >= '".$db->idate($datestart)."'";
        } else if (!empty($dateend)) {
            $sql.= " AND t.datec <= '".$db->idate($dateend)."'";
        }

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
			$i = 0;
	        while ($i < $min)
	        {
				$obj = $db->fetch_object($result);

	            $reponse_static = new Reponse($db);
	            if($reponse_static->fetch($obj->rowid)) {
	                $reponse_static->fetchObjectLinked();
	                $r = $this->_cleanObjectDatas($reponse_static);
	                if ($r) {
                        $obj_ret[] = $r;
                    }
	            }
	            $i++;
	        }
	    }
	    else {
	        throw new RestException(503, $langs->transnoentities('ErrorWhileRetrievingReponseList'));
	    }
	    /*if( ! count($obj_ret)) {
	        throw new RestException(404, 'No Reponse found');
	    }*/
	    return $obj_ret;
	}

    /**
     * Get top contributors
     *
     * @param int $num  Number of contributors
     *
     * @throws RestException
     *
     * @url GET /contributorstats/{$num}
     */
    function contributorstats($num)
    {
        global $langs, $conf;

        $langs->load('reponse@reponse');

        if(! DolibarrApiAccess::$user->rights->reponse->stats) {
            throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
        }

        $contributors = array();

        $agent_id = $conf->global->CONNECT_AGENT_ID ? intval($conf->global->CONNECT_AGENT_ID) : 0;

        $sql = "SELECT COUNT(t.rowid) as total, u.lastname, u.firstname";
        $sql.= " FROM ".MAIN_DB_PREFIX."reponse as t";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = t.user_author_id";
        $sql.= " WHERE t.entity IN (".getEntity('reponse').") AND t.user_author_id <> ".$agent_id;
        $sql.= " AND u.lastname <> '' AND u.firstname <> ''";
        $sql.= " GROUP BY t.user_author_id";
        $sql.= " ORDER BY total DESC";
        $sql.= " LIMIT ".intval($num);

        $resql = $this->db->query($sql);
        if ( $resql )
        {
            if ($this->db->num_rows($resql) > 0)
            {
                while ($obj = $this->db->fetch_object($resql)) {
                    $contributors[] = array(
                        'lastname' => ucfirst(strtolower($obj->lastname)),
                        'firstname' => ucfirst(strtolower($obj->firstname)),
                        'total' => $obj->total
                    );
                }

                $this->db->free($resql);
            }
        }

        return $contributors;
    }

    /**
     * Get total stats
     *
     * @throws RestException
     *
     * @url GET /totalstats
     */
    function totalstats()
    {
        global $langs, $conf;

        $langs->load('reponse@reponse');

        if(! DolibarrApiAccess::$user->rights->reponse->stats) {
            throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
        }

        $total = 0;

        $sql = "SELECT COUNT(t.rowid) as total";
        $sql.= " FROM ".MAIN_DB_PREFIX."reponse as t";
        $sql.= " WHERE t.entity IN (".getEntity('reponse').")";

        $resql = $this->db->query($sql);
        if ( $resql )
        {
            if ($this->db->num_rows($resql) > 0)
            {
                if ($obj = $this->db->fetch_object($resql)) {
                    $total = $obj->total;
                }

                $this->db->free($resql);
            }
        }

        return $total;

    }

    /**
     * Get total stats for form
     *
     * @throws RestException
     *
     * @url POST /formstats
     */
    function formstats()
    {
        global $langs, $conf;

        $langs->load('reponse@reponse');

        if(! DolibarrApiAccess::$user->rights->reponse->stats) {
            throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
        }

        $total = 0;
        $ref = GETPOST('ref');

        $sql = "SELECT COUNT(t.rowid) as total";
        $sql.= " FROM ".MAIN_DB_PREFIX."reponse as t";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."questionnaire as f ON f.rowid = t.fk_questionnaire";
        $sql.= " WHERE t.entity IN (".getEntity('reponse').")";
        $sql.= " AND f.ref LIKE '".$this->db->escape($ref)."'";

        $resql = $this->db->query($sql);
        if ( $resql )
        {
            if ($this->db->num_rows($resql) > 0)
            {
                if ($obj = $this->db->fetch_object($resql)) {
                    $total = $obj->total;
                }

                $this->db->free($resql);
            }
        }

        return $total;
    }

    /**
     * Get user stats
     *
     * @throws RestException
     *
     * @url GET /userstats
     */
    function userstats()
    {
        global $langs, $conf;

        $langs->load('reponse@reponse');

        if(! DolibarrApiAccess::$user->rights->reponse->stats) {
            throw new RestException(401, $langs->transnoentities('AccessNotAllowed'));
        }

        $total = 0;

        $agent_id = $conf->global->CONNECT_AGENT_ID ? intval($conf->global->CONNECT_AGENT_ID) : 0;
        $group_id = $conf->global->CONNECT_USERS_GROUP_ID ? intval($conf->global->CONNECT_USERS_GROUP_ID) : 0;

        $sql = "SELECT COUNT(ug.fk_user) as total";
        $sql .= " FROM ".MAIN_DB_PREFIX."usergroup_user as ug";
        $sql .= " WHERE ug.fk_user <> ".$agent_id;
        $sql .= " AND ug.entity IN (".getEntity('user').")";
        $sql .= " AND ug.fk_usergroup = ".$group_id;

        $resql = $this->db->query($sql);
        if ( $resql )
        {
            $total = $this->db->num_rows($resql);

            if ($this->db->num_rows($resql) > 0)
            {
                if ($obj = $this->db->fetch_object($resql)) {
                    $total = $obj->total;
                }

                $this->db->free($resql);
            }
        }

        return $total;
    }
}
