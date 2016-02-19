<?php
/*******************************************************/
/* THIS IS A GROUP INCLUDE FOR ALL SITE SPECIFIC FILES */
/*******************************************************/
//framework
require_once('site/Application.inc.php');
require_once('site/Dictionary.inc.php');
require_once('site/Format.inc.php');
require_once('site/Response.inc.php');
//status pages
require_once('site/statusPages/Http404Page.inc.php');
require_once('site/statusPages/Http500Page.inc.php');
//pages
require_once('site/pages/HomePage.inc.php');
require_once('site/pages/ContentPage.inc.php');
require_once('site/pages/MobilePage.inc.php');
require_once('site/pages/MobileHoldingPage.inc.php');
require_once('site/pages/ProviderSetupPage.inc.php');
require_once('site/pages/StripeSetupPage.inc.php');
require_once('site/pages/StripeReturnPage.inc.php');
//api
require_once('site/json/CategoriesResponse.inc.php');
require_once('site/json/ContactResponse.inc.php');
require_once('site/json/ProvidersResponse.inc.php');
require_once('site/json/SubscriptionNameResponse.inc.php');
require_once('site/json/SubscriptionResponse.inc.php');
require_once('site/json/VerifyFeedbackResponse.inc.php');
require_once('site/json/VerifyProviderResponse.inc.php');
//xml
require_once('site/xml/SiteMapResponse.inc.php');
//connectors
require_once('site/connectors/StripeConnector.inc.php');
?>