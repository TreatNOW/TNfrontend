<?php

/**
 * Class CategoriesResponse
 * This is currently running a direct search against the database
 * However it should be a proxy api call against zidmi app. To be refactored at some point...
 */
class CategoriesResponse extends JsonResponse {

	function __Construct($dictionary) {
		parent::__Construct(get_class());
        $categories = array();
        $list = ServiceCategory::GetAll(array('partnerCode' => Application::PARTNER_CODE,
                                              'parentId'    => null),
                                        null,
                                        $dictionary->getCode());
        foreach ($list as $category) {
            $categories[] = $category->toArray(true);
        }
        $this->jsonData = array('categories' => $categories);
    }

}
?>