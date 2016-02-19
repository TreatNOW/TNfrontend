<?php

/**
 * Quick and dirty SiteMap
 */
class SiteMapResponse extends XmlResponse {

    //internals
    private $db;

    function __Construct() {
        parent::__Construct(get_class());
    }

    function getContent() {
        $xml = '';
        $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        //root
        $xml .= $this->buildUrlTag('/');
        //cms pages
        $pages = CmsPage::GetAll(array('partnerCode' => Application::PARTNER_CODE,
                                       'statusCode'  => CmsPageStatus::PUBLISHED));
        foreach ($pages as $page) {
            $xml .= $this->buildUrlTag($page->getPath(), $page->getUpdated());
        }
        //done
        $xml .= '</urlset>';
        return $xml;
    }

    private function buildUrlTag($path, $updated = null, $changeFrequency = 'daily') {
        $loc = Config::Get(Application::CONFIG_DOMAIN).$path;
        if (is_null($updated)) {
            $updated = new DateTime();
        }
        $lastMod = Format::DateTime($updated);
        return HTML::Tag('url', HTML::Tag('loc', $loc).
                                HTML::Tag('lastmod', $lastMod).
                                HTML::Tag('changefreq', $changeFrequency));
    }

}
?>