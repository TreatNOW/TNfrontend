<?php 

class HomePage extends Page {

    private $showContactPopup;

	function __Construct($dictionary, $showContactPopup = false) {
		parent::__Construct(get_class(), "desktop.html", $dictionary);
        $this->showContactPopup = $showContactPopup;
	}

	function getContent() {
        $contactPopup = '';
        if ($this->showContactPopup) {
            $contactPopup = HTML::Tag('script', '$(function() { Site.Contact(); });');
        }
        $pageValues = array_merge(array('contactPopup' => $contactPopup),
                                  $this->getDictionaryItems());
        return parent::wrapContent(Application::LoadTemplate('home.html', $pageValues));
    }

    private function getDictionaryItems() {
        return $this->dictionary->getList('treatnow.desktop.', true);
    }

}

?>
