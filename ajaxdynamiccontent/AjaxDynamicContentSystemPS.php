<?php
/**
 * Created by PhpStorm.
 * User: Ben
 * Date: 28/09/2016
 * Time: 11:02
 */

class AjaxDynamicContentSystemPS {
    /** @var bool $dropBefore */
    private $dropBefore;
    /** @var AjaxDynamicContentSelectorPS[] $selectorList */
    private $selectorList;

    public function __construct($dropBefore=false)
    {
        $this->selectorList = array();
        if (is_numeric($dropBefore)) {
            $dropBefore = (int) $dropBefore;
            $this->dropBefore = $dropBefore == 1;
        }
        else if (is_bool($dropBefore)) {
            $this->dropBefore = $dropBefore;
        }
        else {
            $this->dropBefore = false;
        }
    }

    /**
     * @param AjaxDynamicContentSelectorPS $selector
     * @return bool
     */
    public function addSelector($selector) {
        if (is_object($selector) && is_a($selector, 'AjaxDynamicContentSelectorPS')) {
            $this->selectorList[] = $selector;

            return true;
        }
        return false;
    }

    /**
     * @param string $url
     * @return bool
     */
    public static function isValidURL($url) {
        return !filter_var($url, FILTER_VALIDATE_URL) === false;
    }

    /**
     * @param string $ip
     * @return bool
     */
    public static function isValidIpAddress($ip) {
        return !filter_var($ip, FILTER_VALIDATE_IP) === false;
    }

    /**
     * @return bool
     */
    public function generateJSON()
    {
        $content = $this->getJsonContent();
        if ($content !== false) {
            if (file_put_contents(self::getJsonFilename(), $content) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return bool|string
     */
    public function getJsonContent() {
        if (count($this->selectorList) > 0) {
            $jsonArray = array();
            $tmpArray = array();

            foreach ($this->selectorList as $currentSelector) {
                if (!array_key_exists($currentSelector->getUrl(), $tmpArray)) {
                    $tmpArray[$currentSelector->getUrl()] = array();
                }
                $tmpArray[$currentSelector->getUrl()][] = $currentSelector->getSelector();
            }

            foreach ($tmpArray as $currentURL=>$selectorList) {
                $jsonArray[] = array(
                    'url' => $currentURL,
                    'selectors' => $selectorList
                );
            }

            return json_encode(array(
                'drop' => $this->dropBefore,
                'selectorList' => $jsonArray
            ), JSON_PRETTY_PRINT);
        }
        return false;
    }

    /**
     * @return string
     */
    private static function getJsonFilename() {
        return dirname(__FILE__).'/views/js/ajaxDynamicContent.json';
    }
}