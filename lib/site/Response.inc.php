<?php

abstract class Response {

	protected $name;

	function __Construct($name) {
		$this->name = $name;
    }
	
    function getName() {
        return $this->name;
    }

	function getContent() {
		return null;
	}

    function getContentType() {
        return 'text/plain';
    }

}

abstract class Page extends Response {

    protected $title;
    protected $baseTemplate;
    protected $dictionary;

    function __Construct($name, $baseTemplate, $dictionary, $title = 'TreatNOW') {
        parent::__Construct($name);
        $this->title = $title;
        $this->baseTemplate = $baseTemplate;
        $this->dictionary = $dictionary;
    }

    function getTitle() {
        return $this->title;
    }

    function wrapContent($content) {
        $headers = '';
        //include the page stylesheet if exists
        if (file_exists('css/'.$this->name.'.css')) {
            $headers .= '<link rel="stylesheet" type="text/css" href="/css/'.$this->name.'.css" />'."\n";
        }
        //include the page script if exists
        if (file_exists('scripts/'.$this->name.'.js')) {
            $headers .= '<script type="text/javascript" src="/scripts/'.$this->name.'.js"></script>'."\n";
        }
        //values
        $pageValues = array('title'   => HTML::Encode($this->getTitle()),
                            'headers' => $headers,
                            'content' => $content);
        $pageValues = array_merge($pageValues, $this->getDictionaryItems());
        //return template
        return Application::LoadTemplate($this->baseTemplate, $pageValues);
    }

    function getContentType() {
        return 'text/html';
    }

    //TODO: currently this supports null so 404 page loads
    private function getDictionaryItems() {
        if (is_null($this->dictionary)) {
            return array();
        }
        else {
            return $this->dictionary->getList('treatnow.template.', true);
        }

    }

}

abstract class JsonResponse extends Response {

    protected $jsonData = '';

    function __Construct($name) {
        parent::__Construct($name);

    }

    function getContent() {
        return json_encode($this->jsonData);
    }

    function getContentType() {
        return 'application/json';
    }

}

abstract class XmlResponse extends Response {

    function __Construct($name) {
        parent::__Construct($name);

    }

    function getContentType() {
        return 'text/xml';
    }

}

?>