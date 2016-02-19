<?php

class ContactResponse extends JsonResponse {

	function __Construct($user, $dictionary) {
		parent::__Construct(get_class());
        $success = false;
        $message = '';

        if (HTTP::IsPost()) {
            $siteContact = new SiteContact();
            $siteContact->setUserId($user->getId());
            $siteContact->setTypeId(Params::GetLong('typeId'));
            $siteContact->setContactName(Params::Get('contactName'));
            $siteContact->setContactEmailAddress(Params::Get('contactEmailAddress'));
            $siteContact->setContactPhone(Params::Get('contactPhone'));
            $siteContact->setDictionaryCode($dictionary->getCode());
            $siteContact->setContent(Params::Get('content'));
            if ($siteContact->validate()) {
                $siteContact->save();
                $success = true;
                //notify partner by email
                $partner = new Partner(Application::PARTNER_CODE);
                $recipientAddress = $partner->getConfigValue(PartnerConfig::COMMS_NOTIFY_EMAIL);
                $content = "\n";
                $content .= "A new site contact has been submitted on treatnow.co.\n\n";
                $content .= '---------------------------------------------------------------------'."\n";
                $content .= "Type: ".$siteContact->getTypeName()."\n";
                $content .= "Contact Name: ".$siteContact->getContactName()."\n";
                $content .= "Contact Email: ".$siteContact->getContactEmailAddress()."\n";
                $content .= "Contact Phone: ".$siteContact->getContactPhone()."\n";
                $content .= '---------------------------------------------------------------------'."\n\n";
                $content .= $siteContact->getContent()."\n";
                $content .= '---------------------------------------------------------------------'."\n\n";
                $content .= "You can manage the contact here: http://manage.zidmi.com/operations/contacts/";
                Application::SendEmail('notifications@zidmi.com', 'Zidmi', null, $recipientAddress, 'Site Contact', $content);
            }
            else {
                $message = $siteContact->getValidationError();
            }
        }
        else {
            $message = "NO DATA POSTED";
        }
        //done
        $this->jsonData = array('success' => $success,
                                'message' => $message);
    }

}
?>