<?php

class StripeConnector {


    //used as part of the connection process.
    //> takes the authcode for an integration in process and returns a stripe account object
    static function ConstructAccount($authCode) {

        //get client secret
        $partner = new Partner(Application::PARTNER_CODE);
        $clientSecret = $partner->getConfigValue(PartnerConfig::STRIPE_CLIENTSECRET);

        //call stripe to get auth tokens
        $request = curl_init('https://connect.stripe.com/oauth/token');
        $request_params = array(
            'client_secret' => $clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $authCode
        );
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($request, CURLOPT_POST, true );
        curl_setopt($request, CURLOPT_POSTFIELDS, http_build_query($request_params));

        // TODO: Additional error handling
        $respCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
        $response = json_decode(curl_exec($request), true);
        curl_close($request);

        //create stripe account
        $account = new StripeAccount();
        $account->setAccessToken($response['access_token']);
        $account->setRefreshToken($response['refresh_token']);
        $account->setPublishableKey($response['stripe_publishable_key']);
        $account->setUserId($response['stripe_user_id']);
        $account->save();

        //done
        return $account;



    }


}

?>