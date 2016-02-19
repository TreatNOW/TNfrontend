<?php

class SubscriptionNameResponse extends JsonResponse {

	function __Construct($user, $dictionary) {
		parent::__Construct(get_class());
        $success = false;
        $message = '';
        if (HTTP::IsPost()) {
            //inputs
            $subscriptionId = Params::GetLong('subscriptionId');
            $name           = Params::Get('name');
            //load subscription
            $subscription = new Subscription($subscriptionId);
            $subscription->setName($name);
            $subscription->save();
            $success = true;
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