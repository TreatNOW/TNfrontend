<?php require_once("base.inc.php"); ?>
<?php 
/*
 * ROUTING
 * =======
 * uri                               langCode  client           page
 * --------------------------------- --------- ---------------- ---------------------------------------------
 * /                                 en        $mobileDetect()  HomePage || MobilePage
 * /$langCode/$client/               $langCode $client          HomePage || MobilePage
 * /$client/                         en        $client          HomePage || MobilePage
 * /$langCode/                       $langCode $mobileDetect()  HomePage || MobilePage
 * /[$cmsSection/]$cmsPage           en        desktop          CmsPage
 * /$langCode/[$cmsSection/]$cmsPage $langCode desktop          CmsPage
 * /contact                          en        desktop          HomePage with flag for popup
 * /verify/$providerReference        en        desktop          ProviderSetupPage
 *
 * API CALLS
 * > lang can always be provided as a parameter for api calls
 * Provider in a location
 * /api/providers/?  Parameters: ll          > latlong
 *                               category    > zidmiCategoryId
 *                               open        > true / false
 *                               requestable > true / false
 *                               lang        > 2char language code
 * /api/subscribe    Used to subscribe to a mail_list
 *
 */
$response = null;

if (isset($_SERVER['REQUEST_URI'])) {
    //get request
    $elements = explode("/", Util::PageUri());
    $element0 = ''; $element1 = ''; $element2 = '';
    if (count($elements) >= 1) { $element0 = $elements[0]; }
    if (count($elements) >= 2) { $element1 = $elements[1]; }
    if (count($elements) >= 3) { $element2 = $elements[2]; }
    //always get user
    $user = Application::GetUser();
    //fork if api
    if ($element0 == 'api') {
        $langCode = Params::Get('lang', 'en');
        $dictionary = new Dictionary($langCode, 'treatnow.');
        if ($element1 == 'providers') {
            $response = new ProvidersResponse();
        }
        elseif ($element1 == 'subscribe') {
            $response = new SubscriptionResponse($user, $dictionary);
        }
        elseif ($element1 == 'subscribe-name') {
            $response = new SubscriptionNameResponse($user, $dictionary);
        }
        elseif ($element1 == 'categories') {
            $response = new CategoriesResponse($dictionary);
        }
        elseif ($element1 == 'contact') {
            $response = new ContactResponse($user, $dictionary);
        }
        elseif ($element1 == 'verify-provider') {
            $response = new VerifyProviderResponse($user, $dictionary);
        }
        elseif ($element1 == 'verify-feedback') {
            $response = new VerifyFeedbackResponse($user, $dictionary);
        }
    }
    else {
        //first element can either be client, langCode, cmsSection or cmsPage
        //- char(2) = langCode
        //- mobile/desktop = client
        //use of client and/or langCode is reductive.
        //- i.e. we tag it and then reset the uri
        //language
        $langCode = 'en';
        if ($element0 != '') {
            if (strlen($element0) == 2) {
               $langCode = $element0;
               $element0 = $element1;
               $element1 = $element2;
            }
        }
        //client
        $client = 'desktop';
        $mobileDetect = new Mobile_Detect();
        if ($mobileDetect->isMobile()) {
            $client = 'mobile';
        }
        if ($element0 != '') {
            if ($element0 == 'mobile' || $element0 == 'desktop') {
                $client = $element0;
                $element0 = $element1;
                $element1 = $element2;
            }
        }
        //setup dictionary
        $dictionary = new Dictionary($langCode, 'treatnow.');
        //if at root then load HomePage or MobilePage
        if ($element0 == '') {
            if ($client == 'mobile') {
                $response = new MobilePage($dictionary);
            }
            else {
                $response = new HomePage($dictionary);
            }
        }
        //if we have anything left in the uri it's going to be a page or section unless special case
        else {
            //special cases
            if ($element0 == 'contact') {
                $response = new HomePage($dictionary, true);
            }
            elseif ($element0 == 'sitemap') {
                $response = new SitemapResponse();
            }
            elseif ($element0 == 'verify') {
                $provider = Provider::FromVerificationCode($element1);
                if (!is_null($provider)) {
                    $response = new ProviderSetupPage($dictionary, $provider);
                }
            }
            elseif ($element0 == 'stripe-setup') {
                $provider = Provider::FromPaymentConfigCode($element1);
                if (!is_null($provider)) {
                    $response = new StripeSetupPage($dictionary, $provider);
                }
            }
            elseif ($element0 == 'stripe-return') {
                $response = new StripeReturnPage($dictionary);
            }
            elseif ($client == 'mobile' && $element0 == 'static') {
                $response = new MobileHoldingPage($dictionary);
            }
            else {
                //cmsPage
                $cmsPage = null;
                if ($element0 != '' && $element1 == '') {
                    $cmsPage = CmsPage::FromUri(Application::SITE_CODE, $element0);
                }
                //cmsPageSection + cmsPage
                elseif ($element0 != '' && $element1 != '') {
                    $cmsPage = CmsPage::FromUri(Application::SITE_CODE, $element1, $element0);
                }
                if (!is_null($cmsPage)) {
                    if ($cmsPage->getStatusCode() == CmsPageStatus::PUBLISHED) {
                        $response = new ContentPage($dictionary, $cmsPage);
                    }
                }
            }
        }
    }
	//if here we haven't found a valid page so 404
	if (is_null($response)) {
        $response = new Http404Page(Util::PageUri(), new Dictionary('en', 'treatnow.'));
	}
}
//if no page here then it's an error condition
if (is_null($response)) {
	$response = new Http500Page('http500', new Dictionary('en', 'treatnow.'));
}

header('Content-Type: '.$response->getContentType().'; charset=utf-8');

echo $response->getContent();

?>


