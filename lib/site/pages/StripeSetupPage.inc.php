<?php 

class StripeSetupPage extends Page {

    private $provider;

	function __Construct($dictionary, $provider) {
		parent::__Construct(get_class(), "desktop.html", $dictionary);
        $this->provider = $provider;
	}

    function getContent() {
        $partner = new Partner(Application::PARTNER_CODE);
        //compile page array
        $pageValues = array('dictionaryCode'            => $this->dictionary->getCode(),
                            'paymentConfigCode'         => $this->provider->getPaymentConfigCode(),
                            'stripeClientId'            => $partner->getConfigValue(PartnerConfig::STRIPE_CLIENTID)
                           );
        //done
        return Application::LoadTemplate('stripe-setup.html', $pageValues);
    }

}

?>
