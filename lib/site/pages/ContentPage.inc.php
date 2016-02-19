<?php 

class ContentPage extends Page {

    private $cmsPage;

	function __Construct($dictionary, $cmsPage) {
		parent::__Construct(get_class(), "desktop.html", $dictionary);
        $this->cmsPage = $cmsPage;
	}

	function getContent() {
        return parent::wrapContent(HTML::Div($this->cmsPage->getContent(), null, 'content'));
    }

}

?>
