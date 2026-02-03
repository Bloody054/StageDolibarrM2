<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
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

/**
 *  \file       htdocs/couleurdevis/class/actions_couleurdevis.class.php
 *  \ingroup    couleurdevis
 *  \brief      File of class to manage actions on propal
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/dolitour/class/dolitour.class.php';

class ActionsDoliTour
{ 
	/**
     * HOOK 1 : Injection du CSS/JS pour le guide interactif
     */
    public function addHtmlHeader($parameters, &$object, &$action, $hookmanager)
    {
        global $conf, $db, $user, $langs;

        $output = '';

        //Add js and css into page
        $output .= '<script src="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.js.iife.js"></script>';
        $output .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@latest/dist/driver.css">';
    
        // Chargement des polices de Google
        $output .= '<link rel="preconnect" href="https://fonts.googleapis.com">';
        $output .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        $output .= '<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Roboto:wght@400;500&display=swap" rel="stylesheet">';
        
        // Load and prepare each onboard
        $dolitour = new DoliTour($db);
        $items = $dolitour->liste_array();

        

        if(getDolGlobalInt('DOLITOUR_SHOW_ME_THE_CONTEXT')==1){
            echo '<pre>';
            echo 'Onboarding → Le contexte est le suivant : ';
            print_r($parameters['context']);
            echo '</pre>';
        }

        // Récupération de la config globale par défaut
        $overlaycolor=getDolGlobalString('DOLITOUR_OVERLAY_COLOR') ? getDolGlobalString('DOLITOUR_OVERLAY_COLOR') : '#ECECEC';
        $showprogress_val = 1;
        $allow_close_val = 1;
        $config_locked = false;
      
        // Filtrage
        $valid_steps = array();
        $first_step_color = ''; 
        $custom_css = ''; 

        foreach ($items as $item) {
            // Contexte
            if (!empty($item->context) && $parameters['context'] != $item->context) continue;

            // Url
            if (!empty($item->url) && strpos($_SERVER["PHP_SELF"], $item->url) === false) continue;

            // Date
            $now = dol_now();
            if (!empty($item->date_start) && $now < $db->jdate($item->date_start)) continue;
            if (!empty($item->date_end) && $now > $db->jdate($item->date_end)) continue;
            
            // Rejouabilité
            if ($item->play_once == 1 && empty($conf->global->DOLITOUR_DEBUG_FORCE_SHOW)) {
                // On interroge le registre des logs
                $sql_check = "SELECT rowid FROM ".MAIN_DB_PREFIX."dolitour_logs";
                $sql_check.= " WHERE fk_tour = ".((int)$item->id);
                $sql_check.= " AND fk_user = ".((int)$user->id);
                $sql_check.= " AND entity = ".((int)$conf->entity);
                
                $res_check = $db->query($sql_check);
                // Si on trouve une trace, on saute cette étape (continue)
                if ($res_check && $db->num_rows($res_check) > 0) {
                    continue; 
                }
            }
            // Groupe
            if ($item->fk_user_group > 0) {
                $sql_verif = "SELECT rowid FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_user = ".$user->id." AND fk_usergroup = ".((int)$item->fk_user_group);
                $res_verif = $db->query($sql_verif);
                if (!$res_verif || $db->num_rows($res_verif) == 0) continue; 
            }
            
            // On stocke l'étape
            $valid_steps[] = $item;

            if (!$config_locked) {
                if (!empty($item->color)) $first_step_color = $item->color;
                $config_locked = true;
            }

            if (empty($first_step_color) && !empty($item->color)) $first_step_color = $item->color;

            // Nettoyage des couleurs
            $background_color = $item->background_color;
            if (!empty($background_color) && strpos($background_color, '#') === false) $background_color = '#' . $background_color;

            $font_color = $item->font_color;
            if (!empty($font_color) && strpos($font_color, '#') === false) $font_color = '#' . $font_color;

            // Taille : Ajout du 'px' si c'est juste un nombre
            $font_size = $item->font_size;
            if (!empty($font_size) && is_numeric($font_size)) $font_size .= 'px';
            
            if (!empty($font_size)) {
                $valeur_numerique = (int)$font_size; 
                // Si c'est plus grand que 24px, on bloque à 24px
                if ($valeur_numerique > 24) { 
                    $font_size = '24px'; 
                }
                // Si c'est plus petit que 10px (illisible), on force à 10px
                if ($valeur_numerique < 10) { 
                    $font_size = '10px'; 
                }
            }

            $step_id = 'dolitour-step-' . $item->id; // ID unique

            // Génération du CSS

            // Le fond
            $custom_css .= "#{$step_id} {";
            if (!empty($background_color)) {
                $custom_css .= "--driver-popover-bg: {$background_color} !important;";
                $custom_css .= "background-color: {$background_color} !important;"; 
            }
            $custom_css .= "}\n";

            // Texte (Titre + Description + Progression)
            $custom_css .= "#{$step_id} .driver-popover-title, ";
            $custom_css .= "#{$step_id} .driver-popover-description, ";
            $custom_css .= "#{$step_id} .driver-popover-progress-text { ";
            
            // Couleur Texte
            if (!empty($font_color)) {
                $custom_css .= "--driver-text-color: {$font_color} !important;";
                $custom_css .= "color: {$font_color} !important;";
            }
            // Police (Font Family)
            if (!empty($item->font_family)) {
                $custom_css .= "font-family: '{$item->font_family}', sans-serif !important;";
            }
            $custom_css .= "}\n";
            
            // Taille (Uniquement sur la description)
            // On évite de changer la taille du titre pour qu'il reste plus gros que le texte
            if (!empty($font_size)) {
                 $custom_css .= "#{$step_id} .driver-popover-description { font-size: {$font_size} !important; }\n";
            }

            // Gestion de la progression 
            if ($item->show_progress != 1) {
                 $custom_css .= "#{$step_id} .driver-popover-progress-text { display: none !important; }\n";
            }

            // Gestion de la Croix par étape
            if ($item->show_cross != 1) {
                 $custom_css .= "#{$step_id} .driver-popover-close-btn { display: none !important; }\n";
            }
        }
        
        $default_overlay = !empty($first_step_color) ? $first_step_color : $overlaycolor;
        if (!empty($default_overlay) && strpos($default_overlay, '#') === false) $default_overlay = '#' . $default_overlay;
        
         // CSS
        $output .= '<style>
            .driver-popover {
                /* Fond de la bulle */
                --driver-popover-bg: #ffffff;
                /* Couleur du texte */
                --driver-text-color: #333333;
                /* Couleur du titre */
                --driver-title-color: #000000;
                
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(14, 142, 233, 0.15);
            }
            
            /* Customisation des boutons */
            .driver-popover-next-btn {
                background-color: #6c5ce7 !important; /* Couleur Dolibarr ou autre */
                color: white !important;
                border-radius: 4px;
                text-shadow: none !important;
            }
            
            .driver-popover-prev-btn {
                border-radius: 4px;
                color: #666 !important;
                background-color: #f0f0f0 !important;
                text-shadow: none !important;
                border: 1px solid #ddd !important;
            }
            /* CSS dynamique*/
            ' . $custom_css . '
        </style>';

        // SCRIPT JS
        $output .= '<script type="text/javascript">' . "\n";
        $output .= '    $(document).ready(function () {' . "\n";
        $output .= '        const debugConsole = ' . (!empty($conf->global->DOLITOUR_DEBUG_CONSOLE) ? 'true' : 'false') . ';' . "\n";
        $output .= '        const debugHighlight = ' . (!empty($conf->global->DOLITOUR_DEBUG_HIGHLIGHT_TARGET) ? 'true' : 'false') . ';' . "\n";
        $output .= '        if (debugConsole) console.log("[DoliTour] Initialisation...");' . "\n";
        $output .= '        const driver = window.driver.js.driver;' . "\n";
        $output .= '        const driverObj = driver({' . "\n";
        $output .= '            showProgress: true,' . "\n";
        $output .= '            allowClose: true,' . "\n";
        $output .= '            progressText: "{{current}} / {{total}}",' . "\n";
        $output .= '            overlayColor: "'.$default_overlay.'",' . "\n";
        $output .= '            nextBtnText: "Suivant →",' . "\n";
        $output .= '            prevBtnText: "← Retour",' . "\n";
        $output .= '            doneBtnText: "Terminer",' . "\n"; 
        
        // Gestion Id
        $output .= '            onHighlightStarted: (element, step) => {' . "\n";
        $output .= '                if (debugConsole) console.log("[DoliTour] Étape active :", step.popover.title);' . "\n";
        $output .= '                if (debugHighlight) {' . "\n";
        $output .= '                    if (element) {' . "\n";
        $output .= '                        element.style.outline = "4px solid red";' . "\n"; // Bordure rouge
        $output .= '                        element.style.outlineOffset = "2px";' . "\n"; 
        $output .= '                        if (debugConsole) console.log("[DoliTour] Cible trouvée : ", element);' . "\n";
        $output .= '                        setTimeout(() => { element.style.outline = ""; }, 1000);' . "\n"; // On enlève après 1s
        $output .= '                    } else {' . "\n";
        $output .= '                        if (debugConsole) console.warn("[DoliTour] ⚠️ CIBLE INTROUVABLE pour l\'étape : " + step.popover.title);' . "\n";
        $output .= '                    }' . "\n";
        $output .= '                }' . "\n";
        $output .= '                if (step.stepId) {' . "\n";
        $output .= '                    let attempts = 0;' . "\n";
        $output .= '                    const forceId = setInterval(() => {' . "\n";
        $output .= '                        const popover = document.querySelector(".driver-popover");' . "\n";
        $output .= '                        if (popover) {' . "\n";
        $output .= '                            popover.id = step.stepId;' . "\n";
        $output .= '                        }' . "\n";
        $output .= '                        attempts++;' . "\n";
        $output .= '                        if (attempts > 20) clearInterval(forceId);' . "\n";
        $output .= '                    }, 50);' . "\n";
        // Rejouabilité : Injection du JS dans le $output 
        if (empty($conf->global->DOLITOUR_DEBUG_FORCE_SHOW)) {
            $output .= '                        $.ajax({' . "\n";
            $output .= '                            url: "'. dol_buildpath('/dolitour/ajax.php', 1) .'?action=mark_as_read&id=" + step.tourId,' . "\n";
            $output .= '                            method: "POST",' . "\n";
            $output .= '                            data: { token: "'. newToken() .'" },' . "\n";
            $output .= '                            success: function(r) { if(debugConsole) console.log("Tour marqué comme lu (Sauvegardé en BDD) : " + step.tourId); }' . "\n";
            $output .= '                        });' . "\n";
        } else {
            $output .= '                        if(debugConsole) console.log("Mode Debug actif : Lecture NON enregistrée en base pour le tour " + step.tourId);' . "\n";
        }
        $output .= '                }' . "\n";

        
        // Gestion dynamique de l'overlay
        $output .= '                if (step.stepColor) {' . "\n";
        $output .= '                    const overlayPath = document.querySelector(".driver-overlay path");' . "\n";
        $output .= '                    if (overlayPath) {' . "\n";
        $output .= '                        overlayPath.style.fill = step.stepColor;' . "\n";
        $output .= '                        overlayPath.style.transition = "fill 0.3s ease";' . "\n"; 
        $output .= '                    }' . "\n";
        $output .= '                }' . "\n";
        $output .= '            },' . "\n";
        
        // Fermeture AJAX et confirmation
        $output .= '            onCloseClick: () => {' . "\n";
        $output .= '                if (!driverObj.hasNextStep() || confirm("Voulez-vous vraiment arrêter le tutoriel ?")) {' . "\n";
        $output .= '                    $.ajax({' . "\n";
        $output .= '                        url: "' . dol_buildpath('/dolitour/ajax.php', 1) . '",' . "\n";
        $output .= '                        method: "POST",' . "\n";
        $output .= '                        data: { action: "driver_closed" }' . "\n";
        $output .= '                    });' . "\n";
        $output .= '                    driverObj.destroy();' . "\n";
        $output .= '                }' . "\n";
        $output .= '            },' . "\n";

        $output .= '            steps: [' . "\n";

        $count_steps = 0;

        foreach ($valid_steps as $item) {
        
        $popoverClass = 'dolitour-theme-' . $item->id;

        // Positionnement et alignement
            $side = empty($item->side) ? 'left' : $item->side;
            $align = empty($item->align) ? 'start' : $item->align;
            
            // Couleur
            $step_color = !empty($item->color) ? $item->color : $default_overlay;
           
            $element_cible = !empty($item->elementtoselect) ? $item->elementtoselect : 'body';

            // Titre
            $js_title = dol_escape_js($item->title);
            
            // Image
            $img_html = '';
            if (!empty($item->image)) {
                $relative_path = dol_sanitizeFileName($item->ref) . '/' . $item->image;
                $image_url = DOL_URL_ROOT . '/viewimage.php?modulepart=dolitour&file=' . urlencode($relative_path);
                // On ajoute l'image
                $img_html = '<br><br><img src="'.$image_url.'" style="max-width:100%; height:auto; border-radius:4px; margin-top:10px;">';
            }

            //Description
            // Récupération de la description
            $temp_desc = $item->description;
            // Fonction qui permet de transformer les sauts de ligne invisibles (\n) en balises HTML <br>.
            $temp_desc = dol_nl2br($temp_desc);

            $full_content = $temp_desc . $img_html;
            $js_desc  = dol_escape_js($full_content);
            // On s'assure d'avoir l'ID généré
            $step_id = 'dolitour-step-' . $item->id;

            $output .= '{ ';
            // On utilise la variable sécurisée 'element_cible'
            $output .=      'element: "' . dol_escape_js($element_cible) . '", ';
            // On donne l'Id PHP au JS
            $output .=      'tourId: "' . $item->id . '", '; 
            $output .=      'stepColor: "' . dol_escape_js($step_color) . '", '; 
            // Envoie de l'Id au JS
            $output .=      'stepId: "' . dol_escape_js($step_id) . '", '; 
            $output .=      'popoverClass: "", ';
            $output .=      'popover: { ';
            $output .=          'title: "' . $js_title . '", ';
            $output .=          'description: "' . $js_desc . '", ';
            $output .=          'side: "' . $side . '", ';
            $output .=          'align: "' . $align . '" ';
            $output .=      '} ';
            $output .= '},' . "\n";

            $count_steps++;
        } 
        
        $output .= '            ]' . "\n"; 
        $output .= '        });' . "\n"; 

        if ($count_steps > 0) {
                $output .= '        if (debugConsole) console.log("DoliTour: Démarrage avec ' . $count_steps . ' étapes.");'; // LOG
                $output .= '        driverObj.drive();'; 
        } else {
                $output .= '        if (debugConsole) console.log("DoliTour: Aucune étape valide pour cette page.");'; // LOG
        }
        
        $output .= '    });' . "\n";
        $output .= '</script>' . "\n";

        print $output;
    }
    
    /**
    * HOOK 2 : Bouton réinitialisation utilisateur
    */
    public function addMoreActionsButtons($parameters, &$object, &$action, $hookmanager)
    {
        global $langs, $user;

        // Vérification de l'élément 'user' (Fiche utilisateur Dolibarr) et que l'ID existe
        if ($object->element == 'user' && $object->id > 0)
        {
            // Vérification droits 
            if ($user->rights->dolitour->supprimer)
            {
                // Création du lien vers la même page, mais avec une action spéciale 'dolitour_reset_user_logs'
                $link = $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=dolitour_reset_user_logs&token='.newToken();

                print '<div class="inline-block divButAction">';
                print '<a class="butAction" href="'.$link.'">Rejouer l\'aide DoliTour</a>';
                print '</div>';
            }
        }
        return 0;
    }

    /**
     * HOOK 3 : Exécution de la réinitialisation quand on appuie sur le bouton. 
     */
    public function doActions($parameters, &$object, &$action, $hookmanager)
    {
        global $db, $user, $conf;

        // Si l'action correspond à celle de notre bouton
        if ($object->element == 'user' && $action == 'dolitour_reset_user_logs')
        {
            // Vérification de sécurité
            if (!$user->rights->dolitour->supprimer) return 0;

            // Suppression des logs pour cet utilisateur et cette entité
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."dolitour_logs WHERE fk_user = ".((int)$object->id)." AND entity = ".((int)$conf->entity);
            $res = $db->query($sql);

            if ($res) {
                setEventMessages("L'historique des visites DoliTour pour cet utilisateur a été réinitialisé.", null, 'mesgs');
            } else {
                setEventMessages("Erreur lors de la réinitialisation : " . $db->lasterror(), null, 'errors');
            }
            
            // On vide l'action pour ne pas qu'elle tourne en boucle
            $action = '';
            return 1;
        }
        
        return 0;
    }
}


