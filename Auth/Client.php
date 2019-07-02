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

namespace Smartsupp\Smartlook\Auth;

/**
 * SmartlookBlock Template Class.
 *
 * @category Class
 * @package  Smartlook
 * @author   Smartsupp <vladimir@smartsupp.com>
 * @license  http://opensource.org/licenses/gpl-license.php GPL-2.0+
 * @link     http://www.smartsupp.com
 */
class Client
{

    /**
     * API Key
     * 
     * @var string
     */
    public $apiKey = null;

    /**
     * API Url
     * 
     * @var string
     */
    public $apiUrl = 'https://www.smartlook.com/api';

    /**
     * Header
     * 
     * @var array
     */
    private $_headers = array();


    /**
     * Constructor
     * 
     * @param String $apiKey API-key
     * 
     * @return none
     */
    public function __construct($apiKey = null)
    {
        $this->apiKey = $apiKey;
    }


    /**
     * Authenticate method
     * 
     * @param String $apiKey API-key
     * 
     * @return this
     */
    public function authenticate($apiKey = null)
    {
        $this->setHeader('apiKey', $apiKey);
        return $this;
    }


    /**
     * Set header
     * 
     * @param String $name  name parameter
     * @param String $value name value
     * 
     * @return this
     */
    public function setHeader($name, $value)
    {
        if ($value === null) {
            unset($this->_headers[$name]);
        } else {
            $this->_headers[$name] = $value;
        }
        return $this;
    }


    /**
     * Call method via API
     * 
     * @param String $method method name
     * @param array  $params method parameters
     * 
     * @return array
     */
    public function call($method, array $params = null)
    {
        $curl = curl_init($this->apiUrl . '/' . $method);

        $headers = array();
        $headers[] = 'Content-Type: application/json';
        foreach ($this->_headers as $name => $value) {
            $headers[] = "$name: $value";
        }

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params ? $params : array()));

        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        $error = curl_error($curl);
        curl_close($curl);

        $code = $info['http_code'];
        if ($code != 200) {
            return array(
                'ok' => false,
                'error' => 'request_failure',
                'message' => $error,
                'request' => $info
            );
        } else {
            $values = $result ? json_decode($result, true) : array('ok' => true);
            $values = $values === null ? array('ok' => true) : $values;
            $values['ok'] = (bool)$values['ok'];
            return $values;
        }
    }

    /**
     * Call method
     * 
     * @param String $name      method name
     * @param array  $arguments method arguments
     * 
     * @return array
     */
    public function __call($name, $arguments)
    {
        $method = $this->formatMethod($name);
        $params = isset($arguments[0]) ? $arguments[0] : array();
        return $this->call($method, $params);
    }

    /**
     * Format method name
     * 
     * @param String $name method name
     * 
     * @return String
     */
    public function formatMethod($name)
    {
        return preg_replace_callback(
            '/^([a-z]+)([A-Z][a-zA-Z]+)/',
            function ($matches) {
                return $matches[1] . '.' . lcfirst($matches[2]);
            },
            $name
        );
    }
}
