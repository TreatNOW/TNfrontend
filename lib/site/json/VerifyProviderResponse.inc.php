<?php

class VerifyProviderResponse extends JsonResponse {

	function __Construct($user, $dictionary) {
		parent::__Construct(get_class());
        $success = false;
        $message = '';
        if (HTTP::IsPost()) {
            $verificationCode = Params::Get('verificationCode');
            $verifierName     = Params::Get('verifierName');
            if (!is_null($verificationCode) && !is_null($verifierName)) {
                $provider = Provider::FromVerificationCode($verificationCode);
                if (!is_null($provider)) {
                    $reference = 'UserId:'.$user->getId();
                    $provider->verify($verifierName, $reference);
                    $success = true;
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