<?php 

class MobileHoldingPage extends Page {

	function __Construct($dictionary) {
		parent::__Construct(get_class(), "mobile-holding.html", $dictionary);
	}

	function getContent() {
        $pageValues = $this->getDictionaryItems();
        return parent::wrapContent(Application::LoadTemplate('caravan.html', $pageValues));
    }

    private function getDictionaryItems() {
        return $this->dictionary->getList('treatnow.desktop.', true);
    }

}

?>
