<?php

class SubscriptionResponse extends JsonResponse {

    //config
    const DEFAULT_SOURCE = 'TreatNOW WebApp';

	function __Construct($user, $dictionary) {
		parent::__Construct(get_class());
        $success = false;
        $message = '';
        $subscriptionId = null;

        if (HTTP::IsPost()) {
            $emailAddress  = Params::Get('emailAddress');
            $listReference = Params::Get('listReference');
            $source        = Params::Get('source', self::DEFAULT_SOURCE);
            if (!Util::ValidateEmail($emailAddress)) {
                $message = 'INVALID EMAIL ADDRESS';
            }
            else {
                //get list
                $emailList = EmailList::FromReference(Application::PARTNER_CODE, $listReference);
                if (is_null($emailList)) {
                    $message = "INVALID EMAIL LIST [".$listReference."]";
                }
                else {
                    $success = $emailList->subscribe($source, $emailAddress, $user->getId(), $dictionary->getCode(), $message);
                }
                //send welcome email
                if ($success) {
                    //load subscription back up for id
                    $subscription = Subscription::FromEmail($emailList->getId(), $emailAddress);
                    $subscriptionId = $subscription->getId();
                    //load template
                    if ($listReference == 'consumer') {
                        $templateRef = 'subscribe-consumer';
                    }
                    else {
                        $templateRef = 'subscribe-provider';
                    }
                    //TODO: support multiple language emails (just need to deal with inheritance)
                    $template = EmsTemplate::FromReference(Application::PARTNER_CODE, $templateRef, 'EN');
                    //send email
                    Application::SendEmail($template->getFromEmailAddress(),
                                           $template->getFromName(),
                                           $template->getReplyToEmailAddress(),
                                           $emailAddress,
                                           $template->getSubject(),
                                           $template->getContent(),
                                           ContentType::HTML);
                }
            }
        }
        else {
            $message = "NO DATA POSTED";
        }
        //done
        $this->jsonData = array('success'        => $success,
                                'subscriptionId' => $subscriptionId,
                                'message'        => $message);
    }

}
?>