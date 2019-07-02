<?php

/**
 * Smartlook integration plugin.
 *
 * @category  Extension
 * @package   Smartlook
 * @author    Smartsupp <vladimir@smartsupp.com>
 * @copyright 2018 - 2019 Smartsupp.com
 * @license   http://opensource.org/licenses/gpl-license.php GPL-2.0+
 * @link      http://www.smartsupp.com
 *
 * Plugin Name:       Smartlook
 * Plugin URI:        https://www.smartlook.com
 * Description:       Adds Smartlook code to Magento 2.x
 * Version:           2.0.3
 * Author:            Smartsupp
 * Author URI:        http://www.smartsupp.com
 * Text Domain:       smartsupp
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

\Magento\Framework\Component\ComponentRegistrar::register(
    \Magento\Framework\Component\ComponentRegistrar::MODULE,
    'Smartsupp_Smartlook',
    __DIR__
);
