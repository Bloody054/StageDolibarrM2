<?php
/*
 * Classe de gestion des composants HTML spécifiques au module DoliTour
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';

class FormDoliTour extends Form
{
    /**
     * Constructeur
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Retourne la liste des côtés disponibles
     */
    public function getSideList()
    {
        global $langs;
        return array(
            'left'   => $langs->trans("Gauche"),
            'right'  => $langs->trans("Droite"),
            'top'    => $langs->trans("Haut"),
            'bottom' => $langs->trans("Bas")
        );
    }

    /**
     * Retourne la liste des alignements disponibles
     */
    public function getAlignList()
    {
        global $langs;
        return array(
            'start'  => $langs->trans("Début"),
            'center' => $langs->trans("Centre"),
            'end'    => $langs->trans("Fin")
        );
    }

    /**
     * Retourne la liste des polices disponibles
     */
    public function getFontList()
    {
        return array(
            'Arial' => 'Arial',
            'Helvetica' => 'Helvetica',
            'Verdana' => 'Verdana',
            'Times New Roman' => 'Times New Roman',
            'Courier New' => 'Courier New',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Tahoma' => 'Tahoma',
            'Georgia' => 'Georgia',
            'Trebuchet MS' => 'Trebuchet MS'
        );
    }

    /**
     * Affiche le selecteur de Côté
     */
    public function selectSide($selected = '', $htmlname = 'side', $showempty = 0)
    {
        
        $options = $this->getSideList(); 
        
        //  fonction native de Dolibarr pour créer le select
        return $this->selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', '', true);
    }

    /**
     * Affiche le selecteur d'Alignement
     */
    public function selectAlign($selected = '', $htmlname = 'align', $showempty = 0)
    {
        $options = $this->getAlignList();
        return $this->selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', '', true);
    }

    /**
     * Affiche le selecteur de Police
     */
    public function selectFontFamily($selected = '', $htmlname = 'font_family', $showempty = 0)
    {
        $options = $this->getFontList();
        return $this->selectarray($htmlname, $options, $selected, $showempty, 0, 0, '', 0, 0, 0, '', '', true);
    }

    
    public function selectShowProgress($selected = '', $htmlname = 'show_progress', $showempty = 1)
    {
        global $langs;
        // On construit manuellement le tableau pour être sûr d'avoir le choix "Vide"
        $options = array(
            '-1' => '&nbsp;',       // La valeur -1 sert à dire "Tout afficher"
            '1'  => $langs->trans("Yes"),
            '0'  => $langs->trans("No")
        );
        
        // On utilise selectarray qui est plus souple que selectyesno
        return $this->selectarray($htmlname, $options, $selected, 0, 0, 0, '', 0, 0, 0, '', '', true);
    }

    /**
     * Affiche le selecteur de Croix avec option Vide forcée
     */
    public function selectShowCross($selected = '', $htmlname = 'show_cross', $showempty = 1)
    {
        global $langs;
        $options = array(
            '-1' => '&nbsp;',       // La valeur -1 sert à dire "Tout afficher"
            '1'  => $langs->trans("Yes"),
            '0'  => $langs->trans("No")
        );
        
        return $this->selectarray($htmlname, $options, $selected, 0, 0, 0, '', 0, 0, 0, '', '', true);
    }
}
?>