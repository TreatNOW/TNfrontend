<?php 

class Http404Page extends Page {

	//locals
	private $requestPath;

	function __Construct($requestPath, $dictionary) {
        parent::__Construct(get_class(), "desktop.html", $dictionary);
        $this->requestPath = $requestPath;
	}

	function getTitle() {
		return "404: Sorry, we couldn't find the page: ".HTML::Encode($this->requestPath);
	}

	function getContent() {
        $html = '';
        $html .= HTML::H1($this->getTitle());
		$html .= '<p><a href="javascript:window.history.back();">&lt; back to the previous page</a></p>';
        return parent::wrapContent($html);
	}

}

?>
