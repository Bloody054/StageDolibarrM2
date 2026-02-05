<?php
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

class S3Connection extends CommonObject
{
    public $element = 'backup_supervise_connection';
    public $table_element = 'backup_supervise_connection';
    public $picto = 'globe';

    public $id;
    public $ref;
    public $label;
    public $host;
    public $access_key;
    public $secret_key;
    public $service;
    public $billing_service;
    public $note;
    public $control_panel;
    public $color;
    public $entity;

    public function __construct($db)
    {
        $this->db = $db;
        $this->entity = $GLOBALS['conf']->entity;
    }

    public function create(User $user, $notrigger = false)
    {
        $sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element . " (ref,label,host,access_key,secret_key,service,billing_service,note,control_panel,color,entity,datec) VALUES (".
            "'".$this->db->escape($this->ref)."',".
            "'".$this->db->escape($this->label)."',".
            "'".$this->db->escape($this->host)."',".
            "'".$this->db->escape($this->access_key)."',".
            "'".$this->db->escape($this->secret_key)."',".
            "'".$this->db->escape($this->service)."',".
            "'".$this->db->escape($this->billing_service)."',".
            "'".$this->db->escape($this->note)."',".
            "'".$this->db->escape($this->control_panel)."',".
            "'".$this->db->escape($this->color ?: '#2563eb')."',".
            (int) $this->entity . ",".
            "'".$this->db->idate(dol_now())."')";
        if ($this->db->query($sql)) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
            return $this->id;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    public function fetch($id)
    {
        $sql = "SELECT rowid, ref, label, host, access_key, secret_key, service, billing_service, note, control_panel, color, entity FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=".(int) $id;
        $res = $this->db->query($sql);
        if ($res && $this->db->num_rows($res)) {
            $obj = $this->db->fetch_object($res);
            foreach ($obj as $k => $v) {
                $this->$k = $v;
            }
            $this->id = $obj->rowid;
            return 1;
        }
        $this->error = $this->db->lasterror();
        return 0;
    }

    public function fetchAll($limit = 0, $offset = 0)
    {
        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE entity=".(int) $this->entity." ORDER BY label";
        if ($limit) {
            $sql .= $this->db->plimit($limit, $offset);
        }
        $res = $this->db->query($sql);
        $list = array();
        if ($res) {
            while ($obj = $this->db->fetch_object($res)) {
                $item = new self($this->db);
                $item->fetch($obj->rowid);
                $list[] = $item;
            }
        }
        return $list;
    }

    public function update(User $user)
    {
        $sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ref='".$this->db->escape($this->ref)."', label='".$this->db->escape($this->label)."', host='".$this->db->escape($this->host)."', access_key='".$this->db->escape($this->access_key)."', secret_key='".$this->db->escape($this->secret_key)."', service='".$this->db->escape($this->service)."', billing_service='".$this->db->escape($this->billing_service)."', note='".$this->db->escape($this->note)."', control_panel='".$this->db->escape($this->control_panel)."', color='".$this->db->escape($this->color)."' WHERE rowid=".(int) $this->id;
        if ($this->db->query($sql)) {
            return 1;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }

    public function delete(User $user)
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid=".(int) $this->id;
        if ($this->db->query($sql)) {
            return 1;
        }
        $this->error = $this->db->lasterror();
        return -1;
    }


    public function connect($connectionId = null)
    {
        if ($connectionId) {
            $this->fetch($connectionId);
            echo '<pre>';
 print_r($this);
echo '</pre>';
        }

        if (!class_exists('Aws\\S3\\S3Client')) {
            return null;
        }


      $config = array(
            'version' => 'latest',
            'region' => $this->host,
            'endpoint' => $this->label,
            'use_path_style_endpoint' => true,
            'credentials' => array('key' => $this->access_key, 'secret' => $this->secret_key),
            'http' => array('verify' => false)
        );

        return new Aws\S3\S3Client($config);
    }
}
