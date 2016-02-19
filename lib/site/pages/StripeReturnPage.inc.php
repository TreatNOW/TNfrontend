<?php 

class StripeReturnPage extends Page {

    //locals
    private $connected = false;
    private $message = null;

	function __Construct($dictionary) {
		parent::__Construct(get_class(), "desktop.html", $dictionary);

        //get input params
        $paymentConfigCode = Params::Get('state');
        $authCode = Params::Get('code');

        //load provider (verify code)
        $provider = Provider::FromPaymentConfigCode($paymentConfigCode);
        if (is_null($provider)) {
            //TODO: deal with this
        }
        else {
            //check stripe id isn't already set
            if (!is_null($provider->getStripeAccountId())) {
                //TODO: deal with this
            }
            else {

                $stripeAccount = StripeConnector::ConstructAccount($authCode);

                if (is_null($stripeAccount)) {
                    //TODO: deal with this
                }
                else {
                    //add to provider - clear code
                    $provider->setStripeAccountId($stripeAccount->getId());
                    $provider->setPaymentConfigCode(null);
                    $provider->save();
                    //notify success
                    $this->connected = true;
                }
            }
        }

	}

    function getContent() {
        //compile page array
        $pageValues = array('dictionaryCode'   => $this->dictionary->getCode(),
                            'partnerCode'      => Config::Get(Application::CONFIG_PARTNERCODE),
                            'message'          => HTML::Encode($this->message)
                           );
        //done
        if ($this->connected) {
            return Application::LoadTemplate('stripe-return.html', $pageValues);
        }
        else {
            return Application::LoadTemplate('stripe-return-error.html', $pageValues);
        }

    }

}

?>
