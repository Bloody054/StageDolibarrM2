<?php
/* Copyright (C) 2017 Mikael Carlavan <contact@mika-carl.fr>
 * Copyright (C) 2024 Julien Marchand <julien.marchand@iouston.com>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file    core/triggers/interface_99_modReponse_ReponseTriggers.class.php
 * \ingroup reponse
 * \brief   Example trigger.
 *
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/html.formmail.class.php";
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once  DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';

dol_include_once("/reponse/class/reponse.class.php");

/**
 *  Class of triggers for Reponse module
 */
class InterfaceReponseTriggers extends DolibarrTriggers
{
	/**
	 * @var DoliDB Database handler
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "crm";
		$this->description = "Reponse triggers.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = '1.0.0';
		$this->picto = 'reponse@reponse';
	}

	/**
	 * Trigger name
	 *
	 * @return string Name of trigger file
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * Trigger description
	 *
	 * @return string Description of trigger file
	 */
	public function getDesc()
	{
		return $this->description;
	}


	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file
	 * is inside directory core/triggers
	 *
	 * @param string 		$action 	Event action code
	 * @param CommonObject 	$object 	Object
	 * @param User 			$user 		Object user
	 * @param Translate 	$langs 		Object langs
	 * @param Conf 			$conf 		Object conf
	 * @return int              		<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{

        if (empty($conf->reponse->enabled)) return 0;     // Module not active, we do nothing

	    // Put here code you want to execute when a Dolibarr business events occurs.
		// Data and type of action are stored into $object and $action

        $langs->load("other");
        $langs->load("reponse@reponse");

        switch ($action) {

		    case 'REPONSE_SENTBYMAIL':
		     
		        dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

		        $object->confirm($user);	

		    break;


            case 'REPONSE_FILL':

                dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

                $object->fetch($object->id);

                if ($object->questionnaire && $object->questionnaire->fk_confirmation_email_model > 0) {
                    $formmail = new FormMail($this->db);
                    $template = $formmail->getEMailTemplate($this->db, 'questionnaire_send', $user, $langs, $object->questionnaire->fk_confirmation_email_model);


                    $sendto = $object->email;

                    //dol_syslog("Template ".json_encode($template)." to send to $sendto id=".$object->id);

                    if ($template && !empty($sendto)) {
                        $langs->load("commercial");

                        $from = dol_string_nospecial($conf->global->MAIN_MAIL_EMAIL_FROM, ' ', array(",")) .' <'.$conf->global->MAIN_MAIL_EMAIL_FROM.'>';

                        $message = $template->content;
                        $subject = $template->topic;

                        $message = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $message);

                        $sendtobcc= '';
                        $sendtocc = '';
                        $autocopy = 'MAIN_MAIL_AUTOCOPY_REPONSE_TO';		// used to know the automatic BCC to add

                        if (! empty($autocopy)) {
                            $sendtobcc .= (empty($conf->global->$autocopy) ? '' : (($sendtobcc?", ":"").$conf->global->$autocopy));
                        }

                        $deliveryreceipt = 0;
                        $trackid = 'rep'.$object->id;

                        // Create form object
                        $formmail = new FormMail($this->db);
                        $formmail->trackid = $trackid;      // $trackid must be defined

                        $attachedfiles = $formmail->get_attached_files();
                        $filepath = $attachedfiles['paths'];
                        $filename = $attachedfiles['names'];
                        $mimetype = $attachedfiles['mimes'];

                        // Make substitution in email content
                        $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
                        $substitutionarray['__EMAIL__'] = $sendto;
                        $substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty))?'<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>':'';

                        $parameters = array('mode'=>'formemail');
                        complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

                        $subject=make_substitutions($subject, $substitutionarray);
                        $message=make_substitutions($message, $substitutionarray);

                        if (method_exists($object, 'makeSubstitution'))
                        {
                            $subject = $object->makeSubstitution($subject);
                            $message = $object->makeSubstitution($message);
                        }

                        // Send mail (substitutionarray must be done just before this)
                        if (empty($sendcontext)) $sendcontext = 'standard';
                        $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

                        if (!$mailfile->error) {
                            $mailfile->sendfile();

                            $object->envoi_ar = 1;
                            $object->update($user);

                            $now = dol_now();

                            $text = $langs->transnoentities("ReponseAutoSendAR");
                            $code = 'AC_AR_SENT';

                            $contactforaction = new Contact($this->db);
                            $societeforaction = new Societe($this->db);

                            $actioncomm = new ActionComm($this->db);
                            $actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                            $actioncomm->code        = $code;
                            $actioncomm->label       = $text;
                            $actioncomm->note        = $text;
                            $actioncomm->fk_project  = '';
                            $actioncomm->datep       = $now;
                            $actioncomm->datef       = $now;
                            $actioncomm->durationp   = 0;
                            $actioncomm->punctual    = 1;
                            $actioncomm->percentage  = -1;   // Not applicable
                            $actioncomm->societe     = $societeforaction;
                            $actioncomm->contact     = $contactforaction;
                            $actioncomm->socid       = $societeforaction->id;
                            $actioncomm->contactid   = $contactforaction->id;
                            $actioncomm->authorid    = $user->id;   // User saving action
                            $actioncomm->userownerid = $user->id;	// Owner of action
                            $actioncomm->fk_element  = $object->id;
                            $actioncomm->elementtype = $object->element;

                            $ret = $actioncomm->create($user);       // User creating action

                        } else {
                            //dol_syslog("Mail error ".$mailfile->error." to send to $sendto id=".$object->id);
                        }
                    }
                }

                if ($object->questionnaire && $object->questionnaire->fk_notification_email_model > 0) {
                    $formmail = new FormMail($this->db);
                    $template = $formmail->getEMailTemplate($this->db, 'questionnaire_send', $user, $langs, $object->questionnaire->fk_notification_email_model);

                    $usergroup = new UserGroup($this->db);
                    //$usergroup->fetch($object->questionnaire->fk_notification_email_mode);
                    $usergroup->fetch($object->questionnaire->fk_notification_usergroup);

                    $users = $usergroup->listUsersForGroup();

                    if (count($users)) {
                        foreach ($users as $u) {
                            $sendto = $u->email;

                            if ($template && !empty($sendto)) {
                                $langs->load("commercial");

                                $from = dol_string_nospecial($conf->global->MAIN_MAIL_EMAIL_FROM, ' ', array(",")) .' <'.$conf->global->MAIN_MAIL_EMAIL_FROM.'>';

                                $message = $template->content;
                                $subject = $template->topic;

                                $message = preg_replace('/(<img.*src=")[^\"]*viewimage\.php([^\"]*)modulepart=medias([^\"]*)file=([^\"]*)("[^\/]*\/>)/', '\1'.$urlwithroot.'/viewimage.php\2modulepart=medias\3file=\4\5', $message);

                                $sendtobcc= '';
                                $sendtocc = '';
                                $autocopy = 'MAIN_MAIL_AUTOCOPY_REPONSE_TO';		// used to know the automatic BCC to add

                                if (! empty($autocopy)) {
                                    $sendtobcc .= (empty($conf->global->$autocopy) ? '' : (($sendtobcc?", ":"").$conf->global->$autocopy));
                                }

                                $deliveryreceipt = 0;
                                $trackid = 'rep'.$object->id;

                                // Create form object
                                $formmail = new FormMail($this->db);
                                $formmail->trackid = $trackid;      // $trackid must be defined

                                $attachedfiles = $formmail->get_attached_files();
                                $filepath = $attachedfiles['paths'];
                                $filename = $attachedfiles['names'];
                                $mimetype = $attachedfiles['mimes'];

                                // Make substitution in email content
                                $substitutionarray = getCommonSubstitutionArray($langs, 0, null, $object);
                                $substitutionarray['__EMAIL__'] = $sendto;
                                $substitutionarray['__CHECK_READ__'] = (is_object($object) && is_object($object->thirdparty))?'<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$object->thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>':'';

                                $parameters = array('mode'=>'formemail');
                                complete_substitutions_array($substitutionarray, $langs, $object, $parameters);

                                $subject=make_substitutions($subject, $substitutionarray);
                                $message=make_substitutions($message, $substitutionarray);

                                if (method_exists($object, 'makeSubstitution'))
                                {
                                    $subject = $object->makeSubstitution($subject);
                                    $message = $object->makeSubstitution($message);
                                }

                                // Send mail (substitutionarray must be done just before this)
                                if (empty($sendcontext)) $sendcontext = 'standard';
                                $mailfile = new CMailFile($subject, $sendto, $from, $message, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);

                                if (!$mailfile->error) {
                                    $mailfile->sendfile();

                                    $now = dol_now();

                                    $text = $langs->transnoentities("ReponseAutoSendAR");
                                    $code = 'AC_AR_SENT';

                                    $contactforaction = new Contact($this->db);
                                    $societeforaction = new Societe($this->db);

                                    $actioncomm = new ActionComm($this->db);
                                    $actioncomm->type_code   = 'AC_OTH_AUTO';		// Type of event ('AC_OTH', 'AC_OTH_AUTO', 'AC_XXX'...)
                                    $actioncomm->code        = $code;
                                    $actioncomm->label       = $text;
                                    $actioncomm->note        = $text;
                                    $actioncomm->fk_project  = '';
                                    $actioncomm->datep       = $now;
                                    $actioncomm->datef       = $now;
                                    $actioncomm->durationp   = 0;
                                    $actioncomm->punctual    = 1;
                                    $actioncomm->percentage  = -1;   // Not applicable
                                    $actioncomm->societe     = $societeforaction;
                                    $actioncomm->contact     = $contactforaction;
                                    $actioncomm->socid       = $societeforaction->id;
                                    $actioncomm->contactid   = $contactforaction->id;
                                    $actioncomm->authorid    = $user->id;   // User saving action
                                    $actioncomm->userownerid = $user->id;	// Owner of action
                                    $actioncomm->fk_element  = $object->id;
                                    $actioncomm->elementtype = $object->element;

                                    $ret = $actioncomm->create($user);       // User creating action
                                }
                            }
                        }
                    }
                }

                break;
		}

		return 0;
	}
}
