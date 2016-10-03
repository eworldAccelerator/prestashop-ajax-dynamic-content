<?php
/**
 * Created by PhpStorm.
 * User: Ben
 * Date: 28/09/2016
 * Time: 11:03
 */

class AjaxDynamicContentSelectorPS {
    /** @var string $selector */
    private $selector;
    /** @var string $url */
    private $url;

    public function __construct($selector, $url)
    {
        $this->selector = $selector;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getSelector()
    {
        return $this->selector;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }
}