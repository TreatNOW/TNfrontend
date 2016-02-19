<?php 

class Http500Page extends Page {

    function __Construct($requestPath, $dictionary) {
        parent::__Construct(get_class(), "desktop.html", $dictionary);
    }

	function getContent() {
		$html = '';
		$html .= '<p>HTTP 500</p>';
		$html .= '<p>Unable to process request!</p>';
        return parent::wrapContent($html);
	}

}

?>