<?php 

class ProviderSetupPage extends Page {

    private $provider;

	function __Construct($dictionary, $provider) {
		parent::__Construct(get_class(), "desktop.html", $dictionary);
        $this->provider = $provider;
	}

    function getContent() {

//        $this->provider = new Provider();

        //CORE DETAILS
        //spoken languages
        $spokenLanguages = '';
        foreach ($this->provider->getSpokenLanguages() as $language) {
            if ($spokenLanguages != '') { $spokenLanguages .= ', '; };
            $spokenLanguages .= HTML::Encode($language->getDictionaryName());
        }

        //IMAGES
        $image = $this->provider->getImage(ProviderImageType::PRIMARY);
        if (is_null($image)) {
            $primaryImage = "We don't have a primary image from you yet, please email us your preferred image, if not we will try to do a screen grab from your website.";
        }
        else {
            $primaryImage = HTML::Img(Application::GetCdnUri($image, 200, 133));
        }
        $image = $this->provider->getImage(ProviderImageType::SERVICE_LIST);
        if (is_null($image)) {
            $serviceListImage = "You don't have one set so we'll use the primary image.";
        }
        else {
            $serviceListImage = HTML::Img(Application::GetCdnUri($image, 200, 40));
        }

        //FULFILLMENT
        //hours
        $requestHours = "We'll send you appointment requests ";
        if ($this->provider->getUseRequestOpenRules()) {
            $requestHours .= "using the following schedule:";
            $requestHours .= $this->getHoursTable($this->provider->getRequestHours());
        }
        else {
            $requestHours .= "whenever you're open using your standard open hours (see above).";
        }
        //sms/email config
        $emailAddress = HTML::Span(HTML::Encode($this->provider->getEmailAddress()), null, 'highlight');
        $smsNumber = HTML::Span(Format::Phone($this->provider->getSmsIdc(), $this->provider->getSmsNumber()), null, 'highlight');
        if ($this->provider->getEmailRequests() && !$this->provider->getSmsRequests()) {
            $requestAction = 'When we receive a request we\'ll send an email to '.$emailAddress.' containing the details. '.
                             'The email will contain a link to set your availability.';
        }
        elseif (!$this->provider->getEmailRequests() && $this->provider->getSmsRequests()) {
            $requestAction = 'When we receive a request we\'ll send a text message to '.$smsNumber.
                             'containing the details. You will be able to respond to the message or use the link provided to set your availability.';
        }
        elseif ($this->provider->getEmailRequests() && $this->provider->getSmsRequests()) {
            $requestAction = 'When we receive a request we\'ll send an email to '.$emailAddress.' and a text message to '.$smsNumber.'. '.
                             'Both will contain a link to set your availability but you can reply directly to the text.';
        }
        else {
            $requestAction = 'Currently your profile is not configured to send emails or text messages. '.
                             'Until you let us know how you want to receive requests we won\'t be able to put you live in the app.';
        }
        //slot config
        $slotConfig = 'When a customer requests an appointment we will show you ';
        $slotLength = $this->provider->getSlotLength(true);
        $slotStart = $this->provider->getSlotStart(true);

        $slotConfig .= $slotLength;
        $slotConfig .= ' minute slots starting ';
        $slotConfig .= ($slotStart == 0) ? 'on the hour.' : 'at '.$slotStart.' past the hour.';
        $slotConfig .= HTML::Br().'Example: ';
        $slotTime = new DateTime('1974-05-22 13:00');
        $slotTime->modify('+'.$slotStart.' minutes');
        $periodEnd = new DateTime('1974-05-22 14:00');
        while ($slotTime < $periodEnd) {
            $slotConfig .= HTML::Button(Format::Time($slotTime), null, 'slotButton').' ';
            $slotTime->modify('+'.$slotLength.' minutes');
        }
        //confirmation emails
        if ($this->provider->getSendConfirmationEmail()) {
            $confEmails = 'When a booking is completed by a customer we will send a email to '.$emailAddress.'.';
        }
        else {
            $confEmails = 'We will not send you an email when a booking is confirmed.';
        }

        //compile page array
        $pageValues = array_merge(array('dictionaryCode'            => $this->dictionary->getCode(),
                                        'partnerCode'               => Config::Get(Application::CONFIG_PARTNERCODE),
                                        'verificationCode'          => $this->provider->getVerificationCode(),
                                        'phoneNumber'               => Format::Phone($this->provider->getPhoneIdc(), $this->provider->getPhoneNumber()),
                                        'openHours'                 => $this->getHoursTable($this->provider->getOpenHours()),
                                        'spokenLanguages'           => $spokenLanguages,
                                        'primaryImage'              => $primaryImage,
                                        'serviceListImage'          => $serviceListImage,
                                        'fulfillment.requestHours'  => $requestHours,
                                        'fulfillment.requestAction' => $requestAction,
                                        'fulfillment.slotConfig'    => $slotConfig,
                                        'fulfillment.confEmails'    => $confEmails,
                                        'services'                  => $this->getServices()
                                       ),
                                  Util::PrependArray('provider', $this->provider->toArray()));
        //done
        return Application::LoadTemplate('provider-setup.html', $pageValues);
    }

    function getHoursTable($hours) {
        return HTML::Table($this->getHoursTableRow('Monday:', $hours->getMondayOpen1(),    $hours->getMondayClose1(),    $hours->getMondayOpen2(),    $hours->getMondayClose1()).
                           $this->getHoursTableRow('Tuesday:', $hours->getTuesdayOpen1(),   $hours->getTuesdayClose1(),   $hours->getTuesdayOpen2(),   $hours->getTuesdayClose1()).
                           $this->getHoursTableRow('Wednesday:', $hours->getWednesdayOpen1(), $hours->getWednesdayClose1(), $hours->getWednesdayOpen2(), $hours->getWednesdayClose1()).
                           $this->getHoursTableRow('Thursday:', $hours->getThursdayOpen1(),  $hours->getThursdayClose1(),  $hours->getThursdayOpen2(),  $hours->getThursdayClose1()).
                           $this->getHoursTableRow('Friday:', $hours->getFridayOpen1(),    $hours->getFridayClose1(),    $hours->getFridayOpen2(),    $hours->getFridayClose1()).
                           $this->getHoursTableRow('Saturday:', $hours->getSaturdayOpen1(),  $hours->getSaturdayClose1(),  $hours->getSaturdayOpen2(),  $hours->getSaturdayClose1()).
                           $this->getHoursTableRow('Sunday:', $hours->getSundayOpen1(),    $hours->getSundayClose1(),    $hours->getSundayOpen2(),    $hours->getSundayClose1()));
    }
    function getHoursTableRow($caption, $openTime1, $closeTime1, $openTime2, $closeTime2) {
        $html = '';
        $html .= HTML::Td($caption, array('style' => 'vertical-align:top'));
        if (is_null($openTime2)) {
            $html .= HTML::Td(Format::HoursPeriod($openTime1, $closeTime1));
        }
        else {
            $html .= HTML::Td(Format::HoursPeriod($openTime1, $closeTime1).
                HTML::Br().
                Format::HoursPeriod($openTime2, $closeTime2));
        }
        return HTML::Tr($html);
    }

    function getServices() {
        $html = '';
        $categories = ServiceCategory::GetAll(array('partnerCode' => Application::PARTNER_CODE));
        foreach ($categories as $category) {
            if ($category->getLevel() == 3) {
                $services = $this->provider->getServices(true, $category->getId());
                if (count($services) > 0) {
                    $html .= HTML::Tr(HTML::Th(HTML::Encode($category->getFullName()), array('colspan' => 3)));
                    foreach ($services as $service) {
                        $html .= HTML::Tr(HTML::Td(HTML::Encode($service->getName())).
                                          HTML::Td(Format::Money($service->getSalePrice(), $service->getCurrencyCode())).
                                          HTML::Td(Format::Minutes($service->getDuration())));
                    }
                }
            }
        }
        return HTML::Table($html, 'servicesTable');
    }

}

?>
