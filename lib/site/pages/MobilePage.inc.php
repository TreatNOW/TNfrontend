<?php 

class MobilePage extends Page {

	function __Construct($dictionary) {
		parent::__Construct(get_class(), 'mobile.html', $dictionary);
	}

	function getContent() {
        $pageValues = array_merge(array('langList'       => $this->getLangList(),
                                        'dictionaryCode' => $this->dictionary->getCode(),
                                        'partnerCode'    => Config::Get(Application::CONFIG_PARTNERCODE),
                                        'zidmiAppUri'    => Config::Get(Application::CONFIG_ZIDMIURI),
                                        'backUri'        => Application::GetFullUri('/mobile'),
                                        'refererUri'     => array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : '',
                                        'landingUri'     => array_key_exists('REQUEST_URI', $_SERVER) ? $_SERVER['REQUEST_URI'] : ''),
                                  $this->getDictionaryItems());
        return Application::LoadTemplate('mobile.html', $pageValues);
    }

    private function getDictionaryItems() {
        return $this->dictionary->getList('treatnow.mobile.', true);
    }

    private function getLangList() {
        $html = '';
        $buttonClass = 'langCode langButton'.strtoupper($this->dictionary->getCode());
        $html .= HTML::Button("", 'langCode', $buttonClass, 'selectLang();');
        $html .= HTML::Div($this->getLangDiv('en').
                           $this->getLangDiv('fr').
                           $this->getLangDiv('de').
                           $this->getLangDiv('es').
                           $this->getLangDiv('it')
                           , "langList", null, 'display:none;');
        return $html;
    }
    private function getLangDiv($langCode) {
        $html = '';
        if ($langCode != $this->dictionary->getCode()) {
            $id      = 'lang'.strtoupper($langCode);
            $class   = $id.' langListItem';
            $onclick = "Site.setLanguage('".strtolower($langCode)."');";
            $content = $this->dictionary->get('treatnow.mobile.languageName.'.$langCode);
            $html = HTML::Div($content, $id, $class, null, array('onclick' => $onclick));
        }
        return $html;
    }


}

?>