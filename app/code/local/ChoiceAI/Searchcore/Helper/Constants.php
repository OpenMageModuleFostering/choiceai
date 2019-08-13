<?php

/**
 * @category ChoiceAI
 * @copyright   Copyright (c) MineWhat
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class ChoiceAI_Searchcore_Helper_Constants extends Mage_Core_Helper_Abstract {

    const SITE_KEY = "site_key";

    const API_KEY = "api_key";

    const SECRET_KEY = "secret_key";

    const USERNAME = "username";

    const NEED_FEATURE_FIELD_UPDATION = "need_feature_field_updation";

    const NEED_FEATURE_FIELD_UPDATION_FALSE = '0';

    const NEED_FEATURE_FIELD_UPDATION_TRUE = '1';

    const IS_CRON_ENABLED = "cron_enabled";

    const SUBJECT = 'subject';

    const CONTENT = 'content';

    const CC = 'cc';

    const FILTER = 'filter';

    const FILTER_RANGE_DELIMITER = "|`";

    const FEATURE_FIELD_PRICE = 'price';

    const FEATURE_FIELD_IMAGE_URL = 'imageUrl';

    const FEATURE_FIELD_PRODUCT_URL = 'productUrl';

    const FEATURE_FIELD_CATEGORY = 'category';

    const FEATURE_FIELD_BRAND = 'brand';

    const FEATURE_FIELD_TITLE = 'title';

    const AUTOSUGGEST_STATUS = 'autosuggest_status';

    const AUTOSUGGEST_SKIN = 'autosuggest_skin';

    const AUTOSUGGEST_TEMPLATE = 'autosuggest_template';

    const AUTOSUGGEST_MAX_SUGGESTION = 'autosuggest_max_suggestion';

    const AUTOSUGGEST_TOP_QUERIES_STATUS = 'autosuggest_top_queries_status';

    const AUTOSUGGEST_KEYWORD_STATUS = 'autosuggest_keyword_status';

    const AUTOSUGGEST_SEARCH_SCOPE_STATUS = 'autosuggest_search_scope_status';

    const AUTOSUGGEST_MAX_PRODUCTS = 'autosuggest_max_products';

    const AUTOSUGGEST_POP_PRODUCT_HEADER = 'autosuggest_pop_product_header';

    const AUTOSUGGEST_KEYWORD_HEADER = 'autosuggest_keyword_header';

    const AUTOSUGGEST_TOPQUERY_HEADER = 'autosuggest_topquery_header';

    const AUTOSUGGEST_SEARCH_SCOPE_HEADER = 'autosuggest_search_scope_header';

    const AUTOSUGGEST_SHOW_CART = 'autosuggest_show_cart';

    const AUTOSUGGEST_TEMPLATE_1COLUMN = '1column';

    const AUTOSUGGEST_TEMPLATE_2COLUMN = '2column';

    const AUTOSUGGEST_TEMPLATE_2COLUMN_LEFT = '2column-left';

    const AUTOSUGGEST_TEMPLATE_2COLUMN_RIGHT = '2column-right';

    const AUTOSUGGEST_TEMPLATE_1COLUMN_ADD_TO_CART = '1column-addToCart';

    const AUTOSUGGEST_SIDECONTENT = 'autosuggest_sidecontent';

    const AUTOSUGGEST_SIDECONTENT_RIGHT = 'right';

    const AUTOSUGGEST_SIDECONTENT_LEFT = 'left';

    const AUTOSUGGEST_INQUERY = "inFields";

    const AUTOSUGGEST_KEYWORDSUGGESTION = "keywordSuggestions";

    const AUTOSUGGEST_TOP_QUERY = "topQueries";

    const AUTOSUGGEST_POP_PRODUCTS = "popularProducts";

    const AUTOSUGGEST_MAIN_TEMPLATE = 'mainTpl';

    const AUTOSUGGET_SIDE_TEMPLATE = 'sideTpl';

    static $AUTOSUGGEST_SIDECONTENTS = array(self::AUTOSUGGEST_SIDECONTENT_RIGHT, self::AUTOSUGGEST_SIDECONTENT_LEFT);

    static $AUTOSUGGEST_TEMPLATES = array(self::AUTOSUGGEST_TEMPLATE_1COLUMN, self::AUTOSUGGEST_TEMPLATE_2COLUMN);

    const SEARCH_MOD_STATUS = 'search_mod_status';

    const SEARCH_MOD_POWER = 'search_mod_power';

    const SEARCH_HOSTED_STATUS = 'search_hosted_status';

    const SEARCH_HOSTED_INT_STATUS = 'search_hosted_int_status';

    const SEARCH_HOSTED_INT_COMPLETE = 'complete';

    const SEARCH_HOSTED_INT_PROCESSING = 'processing';

    const SEARCH_HOSTED_REDIRECT_URL = 'search_hosted_redirect_url';

    const HOSTED_SEARCH_STATUS = 'hosted_search_status';

    const CHOICEAI_CONFIG_PREFIX = 'choiceai/general';

    const CONFIG_SEPARATOR = '/';

    const SEARCH_POWER_LABEL = 'search';

    const NAVIGATION_POWER_LABEL = 'navigation';

    const ALL_POWER_LABEL = 'all';

    const TRUE = "true";

    const FALSE = "false";

    const FIELD_NAME = 'field_name';

    const FIELD_TYPE = 'field_type';

    const FIELD_TYPE_STRING = 'string';

    const FIELD_TYPE_IMAGE = 'image';

    const FIELD_TYPE_NUMBER = 'number';

    const FIELD_TYPE_DATE = 'date';

    const CHOICEAI_DATATYPE_TEXT = "text";

    const CHOICEAI_DATATYPE_LONGTEXT = "longText";

    const CHOICEAI_DATATYPE_LINK = "link";

    const CHOICEAI_DATATYPE_NUMBER = "number";

    const CHOICEAI_DATATYPE_DECIMAL = "decimal";

    const CHOICEAI_DATATYPE_DATE = "date";

    const CHOICEAI_DATATYPE_BOOL = "bool";

    const CHOICEAI_DATATYPE_SKU = "sku";

    const FIELD_CONF = 'field_conf';

    const INCLUDE_OUT_OF_STOCK = 'include_out_of_stock';

    const INCLUDE_CHILD_PRODUCT = 'include_child_product';

    const INCLUDE_ENABLED_CHILD_PRODUCT = 'include_only_enabled_child_product';

    const INCLUDE_OUT_OF_STOCK_CHILD_PRODUCT = 'include_out_of_stock_child_product';

    const INCLUDE_OUT_OF_STOCK_FROM_NON_CACHE = "include_out_of_stock_from_non_cache";

    const INCLUDE_TAXONOMY_NODES = "include_taxonomy_nodes";

    const INCLUDE_TAXONOMY_MAPPING = "include_taxonomy_mapping";

    const FEED_STATUS_UPLOADING = 'UPLOADING';

    const FEED_STATUS_UPLOADED_SUCCESSFULLY = 'UPLOADED SUCCESSFULLY';

    const FEED_STATUS_UPLOADED_FAILED = 'FAILED';

    const EXCLUDE_CATEGORY = 'exclude_category';

    const AUTH_TOKEN = 'auth_token';

    const AUTH_REQUEST_PARAM = 'auth';

    const LAST_UPLOAD_TIME = 'lastUpload';
	
}
