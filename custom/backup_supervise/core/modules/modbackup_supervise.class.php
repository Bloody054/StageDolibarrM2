<?php
/* Copyright ... */
/**
 * Dolibarr module descriptor for backup_supervise
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';

class modbackup_supervise extends DolibarrModules
{
    public function __construct($db)
    {
        global $langs;
        $this->db = $db;
        $this->numero = 104101;
        $this->rights_class = 'backup_supervise';
        $this->family = 'crm';
        $this->module_position = 500;
        $this->name = 'backup_supervise';
        $this->description = 'Supervision des backups S3 et génération de factures';
        $this->version = '1.0';
        $this->editor_name = 'OpenAI';
        $this->editor_url = 'https://openai.com';
        $this->const_name = 'MAIN_MODULE_BACKUP_SUPERVISE';
        $this->picto = 'backup_supervise@backup_supervise';
        $this->module_parts = array('triggers' => 1, 'css' => array('/backup_supervise/css/backup_supervise.css'), 'js' => array());
        $this->dirs = array('/backup_supervise/temp');
        $this->config_page_url = array('setup.php@backup_supervise');
        $this->hidden = false;
        $this->depends = array('modFournisseur','modSociete','modContrat','modFacture');
        $this->phpmin = array(7, 4);
        $this->langfiles = array('backup_supervise@backup_supervise');

        $this->tabs = array();

        $this->dictionaries = array();

        $this->rights = array();
        $r=0;
        $this->rights[$r][0] = 104101; // id
        $this->rights[$r][1] = 'Lire les connexions S3';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'read';
        $r++;
        $this->rights[$r][0] = 104102; // id
        $this->rights[$r][1] = 'Gérer les connexions S3';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'write';
        $r++;
        $this->rights[$r][0] = 104103; // id
        $this->rights[$r][1] = 'Créer des factures S3';
        $this->rights[$r][3] = 0;
        $this->rights[$r][4] = 'invoice';
        $r++;

        $this->menu = array();
        $this->menu[0] = array(
            'fk_menu' => 'fk_mainmenu=tools',
            'type' => 'left',
            'titre' => 'Backup supervise',
            'mainmenu' => 'backup_supervise',
            'leftmenu' => 'backup_supervise',
            'url' => '/backup_supervise/buckets.php',
            'langs' => 'backup_supervise@backup_supervise',
            'position' => 1000,
            'enabled' => '$conf->backup_supervise->enabled',
            'perms' => '$user->rights->backup_supervise->read',
            'target' => '',
            'user' => 2
        );
    }

    public function init($options = '')
    {
        $sql = array();
        $result = $this->_load_tables('/backup_supervise/sql/');
        if ($result < 0) {
            return $result;
        }
        return $this->_init($sql, $options);
    }

    public function remove($options = '')
    {
        $sql = array();
        return $this->_remove($sql, $options);
    }
}
