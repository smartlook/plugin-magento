<?php

/**
 * Smartlook integration plugin.
 *
 * @category  Extension
 * @package   Smartlook
 * @author    Smartsupp <vladimir@smartsupp.com>
 * @copyright 2018 Smartsupp.com
 * @license   http://opensource.org/licenses/gpl-license.php GPL-2.0+
 * @link      http://www.smartsupp.com
 *
 * Plugin Name:       Smartlook
 * Plugin URI:        http://www.getsmartlook.com
 * Description:       Adds Smartlook code to Magento 2.x
 * Version:           2.0.3
 * Author:            Smartsupp
 * Author URI:        http://www.smartsupp.com
 * Text Domain:       smartsupp
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

namespace Smartsupp\Smartlook\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Smartsupp\Smartlook\Auth\Client;

/**
 * Index Controller Class.
 *
 * @category Class
 * @package  Smartlook
 * @author   Smartsupp <vladimir@smartsupp.com>
 * @license  http://opensource.org/licenses/gpl-license.php GPL-2.0+
 * @link     http://www.smartsupp.com
 */
class Index extends \Magento\Backend\App\Action
{
    const DOMAIN = 'smartlook';
    const AUTH_KEY = '47a2435f1f3673ffce7385bc57bbe3e7353ab02e';
    const CONFIG_PATH = __DIR__ . '/../../../etc/config.json';

    const MSG_CACHE = 'Changes do not apply to Smartlook plugin? Refresh Magento cache.',
        MSG_CACHE_ID = 'cache_refresh',
        MSG_CACHE_GLOBAL = true; // show permanent message about cache refresh in plugin?


    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     * 
     * @param Context     $context           context
     * @param PageFactory $resultPageFactory page factory
     */
    public function __construct(Context $context, PageFactory $resultPageFactory)
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Execute method
     *
     * @return rendered html page
     */
    public function execute()
    {
        $message = null;
        $formAction = null;
                
        $slaction = $this->getRequest()->getParam('slaction');
        $email = $this->getRequest()->getParam('email');
        $password = $this->getRequest()->getParam('password');
        $project = $this->getRequest()->getParam('project');
        $termsConsent = $this->getRequest()->getParam('termsConsent');

        if (self::MSG_CACHE_GLOBAL) {
            $this->messageManager->addNotice(self::MSG_CACHE);
        }

        if (isset($slaction)) {
            switch ($slaction) {
            case 'disable':
                $this->_updateOptions(
                    array(
                    'email' => null,
                    'chatId' => null,
                    'chatKey' => null,
                    'projectId' => null,
                    )
                );
                $message = self::MSG_CACHE_ID;
                break;
            case 'login':
            case 'register':
                $api = new Client;
                $result = $slaction === 'register' ?
                $api->signUp(array('authKey' => self::AUTH_KEY, 'email' => $email, 'password' => $password, 'lang' => $this->_convertLocale($this->getLocale()), 'consentTerms' => 1)) :
                $api->signIn(array('authKey' => self::AUTH_KEY, 'email' => $email, 'password' => $password,));

                if ($result['ok']) {
                    $projectId = null;
                    $chatKey = null;
                    if ($slaction === 'register') {
                        $api->authenticate($result['account']['apiKey']);
                        $project = $api->projectsCreate(
                            array(
                            'name' => $this->getStoreName(),
                            )
                        );
                        $projectId = $project['project']['id'];
                        $chatKey = $project['project']['key'];
                    } else {
                        $api->authenticate($result['account']['apiKey']);
                    }
                    $this->_updateOptions(
                        array(
                        'email' => $result['user']['email'],
                        'chatId' => $result['account']['apiKey'],
                        'chatKey' => $chatKey,
                        'customCode' => '',
                        'projectId' => $projectId,
                        )
                    );
                    $message = self::MSG_CACHE_ID;
                } else {
                    $message = $result['error'];
                    $formAction = $slaction === 'register' ? null : 'login';
                    $data['email'] = $email;
                }

                break;
            case 'update':
                $api = new Client;
                $options = $this->_getOptions();
                $api->authenticate($options['chatId']);
                if (substr($project, 0, 1) === '_') {
                    $project = $api->projectsCreate(
                        array(
                        'name' => substr($project, 1),
                        )
                    );
                } else {
                    $project = $api->projectsGet(
                        array(
                        'id' => $project,
                        )
                    );
                }
                $this->_updateOptions(
                    array(
                    'projectId' => $project['project']['id'],
                    'chatKey' => $project['project']['key'],
                    )
                );
                break;
            }
        }

        $project = null;
        $projects = null;
        if ($chatId = $this->_getOption('chatId')) {
            $api = new Client;
            $api->authenticate($chatId);
            $projects = $api->projectsList();
            $projects = $projects['projects'];
            if (count($projects) === 1) {
                $this->_updateOptions(array('projectId' => $projects[0]['id'], 'chatKey' => $projects[0]['key']));
            }
            if ($projectId = $this->_getOption('projectId')) {
                $project = $projectId;
            }
        }

        if ($message) {
            $mapping = array(
                'invalid_param' => $formAction ? 'Email not found.' : 'Email already registered.',
                'not_found' => $formAction ? 'Email not found.' : 'Email already registered.',
                'sign:invalid_password' => 'Invalid password.',
                'sign:login_failure' => 'Login failed, please try again.',
                self::MSG_CACHE_ID => self::MSG_CACHE,
            );
            if (isset($mapping[$message])) {
                $message = $mapping[$message];
            } else {
                $message = ''; // better fail silently than display unknown message from API
            }
        }
        
        $resultPage = $this->resultPageFactory->create();
        $block = $resultPage->getLayout()->getBlock('smartlook.settings');
        if ($block) {
            $block->setDomain(self::DOMAIN);
            $block->setOptions($this->_getOptions());
            $block->setMessage((string) $message);
            $block->setEmail($email ?: $this->_getOption('email'));
            $block->setEnabled((bool) $this->_getOption('email', null));
            $block->setProjects($projects);
            $block->setProject($project);
            $block->setDisplayForm(!$project);
            $block->setTermsConsent($termsConsent);
        }
        return $resultPage;
    }
    
    /**
     * Get store name
     *
     * @return String
     */
    public function getStoreName()
    {
        return $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore()->getName();
    }


    /**
     * Get store locale
     *
     * @return String
     */
    public function getLocale()
    {
        return $this->_objectManager->get('\Magento\Framework\Locale\Resolver')->getLocale();
    }

    /**
     * Update option file
     * 
     * @param array $options name-value pairs
     * 
     * @return none
     */
    private function _updateOptions(array $options)
    {
        $current = $this->_getOptions();
        foreach ($options as $key => $option) {
            $current[$key] = $option;
        }
        file_put_contents(self::CONFIG_PATH, json_encode($current));
    }

    /**
     * Get options from file
     * 
     * @return array
     */
    private function _getOptions()
    {
        $config = @file_get_contents(self::CONFIG_PATH);
        if (!$config) {
            $config = array();
        } else {
            $config = json_decode($config, JSON_OBJECT_AS_ARRAY);
        }
        return $config;
    }

    /**
     * Get option from file
     * 
     * @param String $name    option name
     * @param String $default default value
     * 
     * @return String
     */
    private function _getOption($name, $default = null)
    {
        $options = $this->_getOptions();
        return isset($options[$name]) ? $options[$name] : $default;
    }


    /**
     * Convert locale to well-know string
     * 
     * @param String $locale locale code
     * 
     * @return String
     */
    private function _convertLocale($locale)
    {
        $available = array('en', 'cs', 'da', 'nl', 'fr', 'de', 'hu', 'it', 'ja', 'pl', 'br', 'pt', 'es', 'tr', 'eu', 'cn', 'tw', 'ro', 'ru', 'sk');
        $part = strtolower(substr($locale, 0, 2));
        $locale = strtolower(substr($locale, 0, 5));
        if (!in_array($part, $available, true)) {
            return 'en';
        }
        if ($part === 'pt') {
            if ($locale === 'pt_br') {
                $part = 'br';
            }
        } elseif ($part === 'zh') {
            if ($locale === 'zh_cn') {
                $part = 'cn';
            } elseif ($locale === 'zh_tw') {
                $part = 'tw';
            } else {
                $part = null;
            }
        } elseif ($part === 'eu') {
            $part = null;
        }
        return $part ?: 'en';
    }
}
