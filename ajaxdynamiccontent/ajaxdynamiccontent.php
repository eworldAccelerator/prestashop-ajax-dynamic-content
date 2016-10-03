<?php
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2015 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
	exit;
}

class AjaxDynamicContent extends Module {
	protected $config_form = false;

	public function __construct() {

		$this->name = 'ajaxdynamiccontent';
		$this->tab = 'administration';
		$this->version = '0.2.0';
		$this->author = 'eworld Accelerator';
		$this->need_instance = 0;

		/**
		 * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
		 */
		$this->bootstrap = true;

		parent::__construct();

		$this->displayName = $this->l('Ajax Dynamic Content');
		$this->description = $this->l('Load dynamic content (cart, wishlist, my account, etc.) via Ajax for Prestashop websites with cache');
	}

	/**
	 * Don't forget to create update methods if needed:
	 * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
	 */
	public function install() {
		Configuration::updateValue('EACC_ADC_SELECTORS', '');
		Configuration::updateValue('EACC_ADC_TEST_IP', '');
		Configuration::updateValue('EACC_ADC_ACTIVE', 0);
		Configuration::updateValue('EACC_ADC_DROP_BEFORE', 0);

		return parent::install() &&
		$this->registerHook('header') &&
		$this->registerHook('displayHeader');
	}

	public function uninstall() {
		Configuration::deleteByName('EACC_ADC_SELECTORS');
		Configuration::deleteByName('EACC_ADC_TEST_IP');
		Configuration::deleteByName('EACC_ADC_DROP_BEFORE');
		Configuration::deleteByName('EACC_ADC_ACTIVE');

		return parent::uninstall();
	}

	/**
	 * Load the configuration form
	 */
	public function getContent() {
		/**
		 * If values have been submitted in the form, process.
		 */
		if (((bool)Tools::isSubmit('submitAjax-dynamic-contentModule')) == true) {
			$this->postProcess();
		}

		$this->context->smarty->assign('module_dir', $this->_path);

		$output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        if (isset($this->errors) && is_array($this->errors) && count($this->errors) > 0) {
            $output .= join("\r\n", $this->errors);
        }

		return $output . $this->renderForm();
	}

	/**
	 * Create the form that will be displayed in the configuration of your module.
	 */
	protected function renderForm() {
		$helper = new HelperForm();

		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$helper->module = $this;
		$helper->default_form_language = $this->context->language->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submitAjax-dynamic-contentModule';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
			. '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');

		$helper->tpl_vars = array(
			'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id,
		);

		return $helper->generateForm(array($this->getConfigForm()));
	}

	/**
	 * Create the structure of your form.
	 */
	protected function getConfigForm() {
		return array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Settings'),
					'icon' => 'icon-cogs',
				),
				'input' => array(
					array(
						'type' => 'radio',
						'label' => $this->l('Enable'),
						'name' => 'EACC_ADC_ACTIVE',
						'class' => 't',
						'required' => true,
						'is_bool' => false,
						'values' => array(
							array(
								'id' => 'active_on',
								'value' => 1,
								'label' => $this->l('Enabled')
							),
							array(
								'id' => 'active_off',
								'value' => 2,
								'label' => $this->l('Disabled')
							),
							array(
								'id' => 'active_test',
								'value' => 3,
								'label' => $this->l('Test mode only (you must specify IP)')
							)
						),
					),
					array(
						'type' => 'text',
						'prefix' => '<i class="icon icon-lock"></i>',
						'desc' => $this->l('This module will be active only for this public IP. Your IP is ' . $_SERVER['REMOTE_ADDR']),
						'name' => 'EACC_ADC_TEST_IP',
						'label' => $this->l('Test mode IP'),
					),
                    array(
                        'type' => 'radio',
                        'label' => $this->l('Drop content before getting URL source'),
                        'name' => 'EACC_ADC_DROP_BEFORE',
                        'class' => 't',
                        'required' => true,
                        'is_bool' => false,
                        'values' => array(
                            array(
                                'id' => 'drop_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'drop_off',
                                'value' => 2,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
					array(
						'type' => 'textarea',
						'label' => $this->l('Selector(s) and URL(s)'),
						'name' => 'EACC_ADC_SELECTORS',
						'desc' => 'Selector(s) are jQuery like selector describing element to be replaced.<br />
URL are page where we can find the specified element to replace.<br />
We recommend (for speed optimization) to have only one URL for all selectors defined.<br />
For multilanguage stores, you can use lang_xx class added to <body> tag by Prestashop<br />
1 selector &amp; 1 URL per line, separated by ;.<br />
Example : ".lang_en #exampleID .exampleClass span;http://'.$_SERVER['HTTP_HOST'].'/dir/page-adc.html"',
						'required' => true
					)
				),
				'submit' => array(
					'title' => $this->l('Save'),
				),
			),
		);
	}

	/**
	 * Set values for the inputs.
	 */
	protected function getConfigFormValues() {
		// Definied values
		$enabled = (int) trim(Configuration::get('EACC_ADC_ACTIVE'));
		if ($enabled == 1 || $enabled == 2 || $enabled == 3) {
			return array(
				'EACC_ADC_ACTIVE' => $enabled,
				'EACC_ADC_TEST_IP' => Configuration::get('EACC_ADC_TEST_IP'),
				'EACC_ADC_SELECTORS' => Configuration::get('EACC_ADC_SELECTORS'),
				'EACC_ADC_DROP_BEFORE' => (int) Configuration::get('EACC_ADC_DROP_BEFORE'),
			);
		}
		// Default values
		else {
			return array(
				'EACC_ADC_ACTIVE' => 1,
				'EACC_ADC_TEST_IP' => '',
				'EACC_ADC_SELECTORS' => '',
				'EACC_ADC_DROP_BEFORE' => 0,
			);
		}
	}

	/**
	 * Save form data.
	 */
	protected function postProcess() {
		$enabled = (int) trim(Tools::getValue('EACC_ADC_ACTIVE'));
		$testIpAddress = trim(Tools::getValue('EACC_ADC_TEST_IP'));
		$selectors = trim(Tools::getValue('EACC_ADC_SELECTORS'));
		$dropBefore = trim(Tools::getValue('EACC_ADC_DROP_BEFORE'));

		if ($enabled != 1 && $enabled != 2 && $enabled != 3) {
			$this->errors[] = $this->displayError($this->l('Enabled value is not recognized'));
		} else {
			$oldValue = Configuration::get('EACC_ADC_ACTIVE');
			Configuration::updateValue('EACC_ADC_ACTIVE', $enabled);

			if ($enabled == 1 && $oldValue != 1) {
				$this->errors[] = $this->displayConfirmation($this->l('System enabled'));
			} else if ($enabled == 2 && $oldValue != 2) {
				$this->errors[] = $this->displayConfirmation($this->l('System disabled'));
			} else if ($enabled == 3 && $oldValue != 3) {
				$this->errors[] = $this->displayConfirmation($this->l('Test mode enabled'));
			}
		}

		if ($testIpAddress != '') {
            Configuration::updateValue('EACC_ADC_TEST_IP', $testIpAddress);
            if (!AjaxDynamicContentSystemPS::isValidIpAddress($testIpAddress)) {
                $this->errors[] = $this->displayError($this->l('IP address is not valid'));
            }
        }
        else if ($enabled == 3) {
            $this->errors[] = $this->displayError($this->l('Test mode is active and IP address is empty'));
        }

        if ($dropBefore != 1 && $dropBefore != 2) {
            $this->errors[] = $this->displayError($this->l('"Drop before" value is not recognized'));
        }
        else {
            Configuration::updateValue('EACC_ADC_DROP_BEFORE', $dropBefore);
        }

        if ($selectors == '') {
            $this->errors[] = $this->displayError($this->l('Selector list is empty. You must set at least 1 Selector/URL'));
        }
        else {
            $selectorsArray = explode(PHP_EOL, $selectors);
            $ajaxDynamicContentSystem = new AjaxDynamicContentSystemPS($dropBefore);
            if (count($selectorsArray) > 0) {
                Configuration::updateValue('EACC_ADC_SELECTORS', trim($selectors));
                foreach ($selectorsArray as $lineNumber=>$lineValue) {
                    if (strpos($lineValue, ';') !== false ) {
                        list($currentSelector, $currentURL) = explode(';', $lineValue);
                        $currentSelector = trim($currentSelector);
                        $currentURL = trim($currentURL);

                        if (!AjaxDynamicContentSystemPS::isValidURL($currentURL)) {
                            $this->errors[] = $this->displayError($this->l('URL is not valid at line '.$lineNumber.' : '.$currentURL));
                        }
                        else {
                            $ajaxDynamicContentSystem->addSelector(new AjaxDynamicContentSelectorPS($currentSelector, $currentURL));
                        }
                    }
                    else {
                        $this->errors[] = $this->displayError($this->l('; missing at line '.$lineNumber.' in selector(s)'));
                    }
                }
                // Generate JSON file
                if ($ajaxDynamicContentSystem->generateJSON()) {
                    $this->errors[] = $this->displayConfirmation($this->l('JSON file generated'));
                }
                else {
                    $this->errors[] = $this->displayError($this->l('Can\'t create JSON file'));
                }
            }
        }
	}

	/**
	 * Add the CSS & JavaScript files you want to be added on the FO.
	 */
	public function hookHeader() {
        $enabled = (int) trim(Configuration::get('EACC_ADC_ACTIVE'));
        // enabled or restricted to configured IP
        if ($enabled == 1 || $enabled == 3 && $_SERVER['REMOTE_ADDR'] == trim(Configuration::get('EACC_ADC_TEST_IP'))) {
            $this->context->controller->addJS($this->_path . '/views/js/front.js');
            /* Media::addJsDef exists from Prestashop 1.6 */
            if (method_exists('Media', 'addJsDef')) {
                Media::addJsDef(array('eacc_adc_json' => _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/ajaxdynamiccontent/views/js/ajaxDynamicContent.json'));
            }
        }
	}

	public function hookDisplayHeader() {
        $enabled = (int) trim(Configuration::get('EACC_ADC_ACTIVE'));
        // enabled or restricted to configured IP
        if ($enabled == 1 || $enabled == 3 && $_SERVER['REMOTE_ADDR'] == trim(Configuration::get('EACC_ADC_TEST_IP'))) {
            $this->context->controller->addJS($this->_path . '/views/js/front.js');
            /* Media::addJsDef exists from Prestashop 1.6 */
            if (method_exists('Media', 'addJsDef')) {
                Media::addJsDef(array('eacc_adc_json' => _PS_BASE_URL_ . __PS_BASE_URI__ . 'modules/ajaxdynamiccontent/views/js/ajaxDynamicContent.json'));
            }
        }
	}
}

require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AjaxDynamicContentSystemPS.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'AjaxDynamicContentSelectorPS.php';
