<?php
/**
 * Cloudflare Turnstile Module for PrestaShop
 *
 * @author    blauwfruit
 * @copyright 2025 blauwfruit
 * @license   MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Turnstile extends Module
{
    public function __construct()
    {
        $this->name = 'turnstile';
        $this->tab = 'front_office_features';
        $this->version = '1.0.1';
        $this->author = 'blauwfruit';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.7.1.0',
            'max' => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Cloudflare Turnstile');
        $this->description = $this->l('Use a user-friendly Cloudflare Turnstile to protect your forms.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Cloudflare Turnstile?');
    }
    
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionFrontControllerInit')
            && $this->registerHook('actionAuthentication')
            && $this->registerHook('actionFrontControllerSetMedia');
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $enabled = (int)Tools::getValue('TURNSTILE_ENABLED');
            $siteKey = Tools::getValue('TURNSTILE_SITE_KEY');
            $secretKey = Tools::getValue('TURNSTILE_SECRET_KEY');

            Configuration::updateValue('TURNSTILE_ENABLED', $enabled);
            Configuration::updateValue('TURNSTILE_SITE_KEY', $siteKey);
            Configuration::updateValue('TURNSTILE_SECRET_KEY', $secretKey);

            $output .= $this->displayConfirmation($this->l('Settings updated successfully.'));
        }
        
        return $output . $this->displayForm();
    }

    public function displayForm()
    {
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fieldsForm = [
            'form' => [
                'legend' => [
                    'title' => $this->l('Cloudflare Turnstile Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->l('Enable Turnstile'),
                        'name' => 'TURNSTILE_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->l('Enabled')
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->l('Disabled')
                            ]
                        ]
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Site Key'),
                        'name' => 'TURNSTILE_SITE_KEY',
                        'size' => 64,
                        'required' => true,
                        'desc' => $this->l('Get your site key from Cloudflare dashboard')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Secret Key'),
                        'name' => 'TURNSTILE_SECRET_KEY',
                        'size' => 64,
                        'required' => true,
                        'desc' => $this->l('Get your secret key from Cloudflare dashboard')
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                    'class' => 'btn btn-default pull-right'
                ]
            ]
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name;
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['TURNSTILE_ENABLED'] = Configuration::get('TURNSTILE_ENABLED');
        $helper->fields_value['TURNSTILE_SITE_KEY'] = Configuration::get('TURNSTILE_SITE_KEY');
        $helper->fields_value['TURNSTILE_SECRET_KEY'] = Configuration::get('TURNSTILE_SECRET_KEY');

        return $helper->generateForm([$fieldsForm]);
    }

    public function hookDisplayHeader($params = [])
    {
        if (Tools::getValue('turnstile-failure') == '1') {
            $this->context->controller->errors[] = $this->l('Captcha verification failed. Please try again.');
        }

        $siteKey = Configuration::get('TURNSTILE_SITE_KEY');
        
        // Pass site key to JavaScript
        Media::addJsDef([
            'turnstileSiteKey' => $siteKey
        ]);
        
        // Register Cloudflare Turnstile API script
        $this->context->controller->registerJavascript(
            'turnstile-api',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [
                'server' => 'remote',
                'attributes' => 'async defer'
            ]
        );
        
        // Register our custom front.js
        $this->context->controller->registerJavascript(
            'module-turnstile-front',
            'modules/' . $this->name . '/views/js/front.js',
            [
                'position' => 'bottom',
                'priority' => 200
            ]
        );
    }

    public function hookActionFrontControllerAfterInit($params)
    {
        $this->validateTurnstile();
    }

    protected function validateTurnstile()
    {
        if (!Configuration::get('TURNSTILE_ENABLED')) {
            return '';
        }

        // Check if any submit* field is posted (indicating a form submission)
        $hasSubmitField = false;
        $submitFields = [];
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'submit') === 0) {
                $hasSubmitField = true;
                $submitFields[] = $key;
            }
        }

        // Also check for login form (email + password)
        $isLoginForm = false;
        if (!$hasSubmitField && !empty($_POST)) {
            if (isset($_POST['email']) && isset($_POST['passwd'])) {
                $hasSubmitField = true;
                $isLoginForm = true;
            }
        }
        
        // If no form submission detected, no validation needed
        if (!$hasSubmitField) {
            return true;
        }

        
        // Validate the Turnstile token
        $token = Tools::getValue('cf-turnstile-response');

        
        if (empty($token)) {

            $this->handleValidationFailure($isLoginForm);
            return false;
        }

        $secretKey = Configuration::get('TURNSTILE_SECRET_KEY');
        $remoteIp = Tools::getRemoteAddr();

        $data = [
            'secret' => $secretKey,
            'response' => $token,
            'remoteip' => $remoteIp
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data),
                'timeout' => 10
            ]
        ];

        $context = stream_context_create($options);
        $result = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $context);

        if ($result === false) {
            $this->handleValidationFailure($isLoginForm);
            return false;
        }

        $resultJson = json_decode($result, true);
        
        $isValid = isset($resultJson['success']) && $resultJson['success'] === true;
        
        if (!$isValid) {
            $this->handleValidationFailure($isLoginForm);
            return false;
        }
        
        return true;
    }
    
    protected function handleValidationFailure($isLoginForm = false)
    {
        $errorMessage = $this->l('Captcha verification failed. Please try again.');
        
        // Add error to controller
        if (isset($this->context->controller->errors)) {
            $this->context->controller->errors[] = $errorMessage;
        }

        $controllerName = $this->context->controller->php_self;
        $currentUrl = $this->context->link->getPageLink($controllerName, null, null, ['turnstile-failure' => 1]);
        Tools::redirect($currentUrl);
        
        // Check if it's an AJAX request
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        
        if ($isAjax) {
            // Return JSON error for AJAX requests
            header('Content-Type: application/json');
            http_response_code(400);
            die(json_encode([
                'hasError' => true,
                'errors' => [$errorMessage],
                'nw_error' => true,
                'msg' => $errorMessage
            ]));
        }
        
        // For login forms, we need to prevent authentication
        // Clear critical POST fields to prevent login
        if ($isLoginForm || (isset($_POST['email']) && isset($_POST['passwd']))) {
            unset($_POST['email']);
            unset($_POST['passwd']);
            unset($_POST['submitLogin']);
            unset($_REQUEST['email']);
            unset($_REQUEST['passwd']);
            unset($_REQUEST['submitLogin']);
        } else {
            // For other forms, clear all POST data
            $_POST = [];
            $_REQUEST = [];
        }
        
        // Store error message in cookie for display after redirect
        $this->context->cookie->turnstile_error = $errorMessage;
        
        // Redirect back and stop execution immediately
        $redirectUrl = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $this->context->link->getPageLink('index');
        
        // Send headers and redirect
        header('Location: ' . $redirectUrl);
        http_response_code(302);
        exit();
    }
}
