<?php

class VerifyFeedbackResponse extends JsonResponse {

	function __Construct($user, $dictionary) {
		parent::__Construct(get_class());
        $success = false;
        $message = '';
        if (HTTP::IsPost()) {
            $verificationCode = Params::Get('verificationCode');
            $verifierName     = Params::Get('verifierName');
            $feedback         = Params::Get('feedback');
            if (!is_null($verificationCode) && !is_null($verifierName) && !is_null($feedback)) {
                $provider = Provider::FromVerificationCode($verificationCode);
                if (!is_null($provider)) {
                    //add provider event
                    $reference = 'UserId:'.$user->getId();
                    $notes = "Submitted by:".$verifierName."\n".$feedback;
                    $provider->addEvent(ProviderEventType::VERIFICATION_FEEDBACK, $reference, $notes);
                    $success = true;
                    //notify email
                    $partner = new Partner(Application::PARTNER_CODE);
                    $recipientAddress = $partner->getConfigValue(PartnerConfig::COMMS_NOTIFY_EMAIL);
                    $content = "\n";
                    $content .= "Some feedback has been submitted regarding provider data.\n\n";
                    $content .= '---------------------------------------------------------------------'."\n";
                    $content .= "Provider: ".$provider->getName()."\n";
                    $content .= "Submitted by: ".$verifierName."\n";
                    $content .= "Feedback: \n";
                    $content .= $feedback."\n";
                    $content .= '---------------------------------------------------------------------'."\n\n";
                    Application::SendEmail('notifications@zidmi.com', 'Zidmi', null, $recipientAddress, 'Provider Verification Feedback', $content);
                }
            }
        }
        else {
            $message = "NO DATA POSTED";
        }
        //done
        $this->jsonData = array('success'        => $success,
                                'message'        => $message);
    }

}
?>