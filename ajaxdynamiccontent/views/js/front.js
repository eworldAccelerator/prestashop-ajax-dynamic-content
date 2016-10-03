/**
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

if (typeof jQuery == 'undefined') {
    var jq = document.createElement('script'); jq.type = 'text/javascript';
    // Path to jquery.js file, eg. Google hosted version
    jq.src = 'https://code.jquery.com/jquery-2.2.3.min.js';
    document.getElementsByTagName('head')[0].appendChild(jq);
    //console.log('loading jquery');
}
else {
    //console.log('jquery ok');
}

/* Function from Sam Deering, on https://www.sitepoint.com/url-parameters-jquery/ */
function adcUrlParam (name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
        return null;
    }
    else{
        return results[1] || 0;
    }
}

var adcHtmlContent = null;
/* If eacc_adc_json not defined because jsDef does not exists yet (< 1.6) */
if (typeof eacc_adc_json == 'undefined') {
    /* If Prestashop has defined a baseDir JS variable */
    if (typeof baseDir != 'undefined') {
        var eacc_adc_json = baseDir + '/modules/ajaxdynamiccontent/views/js/ajaxDynamicContent.json';
    }
}
jQuery(document).ready(function() {
    /* If we are not in called URL by ajax */
    if (parseInt(adcUrlParam('nocache')) != 1 && (adcUrlParam('adctime') == null || adcUrlParam('adctime') == 0)) {
        if (typeof eacc_adc_json != 'undefined') {
            jQuery.getJSON(eacc_adc_json, function (data) {
                jQuery.each(data.selectorList, function (key, currentSource) {
                    var selectorExistsInPage = false;
                    jQuery.each(currentSource.selectors, function (key2, currentSelector) {
                        if (jQuery(currentSelector).length) {
                            if (data.drop) {
                                jQuery(currentSelector).html('');
                            }
                            selectorExistsInPage = true;
                        }
                    });
                    if (selectorExistsInPage) {
                        jQuery.ajax({
                            url: currentSource.url,
                            data: "nocache=1&adctime=" + jQuery.now(),
                            success: function (data) {
                                /* Convert <body> in HTML cause jQuery remove <body> tag when parsing
                                 * and adds 2 <div> elements because find() needs it (kind of weird) */
                                data = data.replace(/<body/, "<body><div id='adc'><div").replace(/<\/body>/, "</div></div></body>");
                                adcHtmlContent = jQuery(data);
                                jQuery.each(currentSource.selectors, function (key2, currentSelector) {
                                    if (adcHtmlContent.find(currentSelector).length) {
                                        jQuery(currentSelector).html(adcHtmlContent.find(currentSelector).html());
                                        jQuery(currentSelector).addClass('ajaxDynamicContentReplaced');
                                    }
                                    else {
                                        console.log('ADC can\'t find selector "' + currentSelector + '" on submitted page');
                                    }
                                });
                            },
                            dataType: 'html'
                        });
                    }
                });
            });
        }
        else {
            console.log('ADC can\'t get configured JSON URL');
        }
    }
    else {
        console.log('ADC disabled on source content URL');
    }
});