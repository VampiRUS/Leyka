<?php
/*
Plugin Name: Leyka PayPal gateway
Plugin URI: http://leyka.te-st.ru/
Description: Gateway for Leyka donations management system which adds option for receiving donates using PayPal payment service. Can only be used for receiving donations!
Version: 1.0
Author: Lev Zvyagincev aka Ahaenor
Author URI: ahaenor@gmail.com
License: GPLv2 or later

	Copyright (C) 2012-2013 by Teplitsa of Social Technologies (http://te-st.ru).

	GNU General Public License, Free Software Foundation <http://www.gnu.org/licenses/gpl-2.0.html>

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

function leyka_paypal_plugins_loaded(){
    // Set filter for plugin's languages directory
    $plugin_lang_dir = dirname(plugin_basename(__FILE__)).'/languages/';
    $plugin_lang_dir = apply_filters('leyka_languages_directory', $plugin_lang_dir);

    // Traditional WordPress plugin locale filter
    $locale = apply_filters('plugin_locale', get_locale(), 'leyka-paypal');
    $mofile = sprintf('%1$s-%2$s.mo', 'leyka-paypal', $locale);

    // Setup paths to current locale file
    $mofile_local = $plugin_lang_dir.$mofile;
    $mofile_global = WP_LANG_DIR.'/leyka-paypal/'.$mofile;

    if(file_exists($mofile_global)) {
        // Look in global /wp-content/languages/edd folder
        load_textdomain('leyka-paypal', $mofile_global);
    } elseif(file_exists(WP_PLUGIN_DIR.'/'.$mofile_local)) {
        // Look in local /wp-content/plugins/easy-digital-donates/languages/ folder
        load_textdomain('leyka-paypal', WP_PLUGIN_DIR.'/'.$mofile_local);
    } else {
        // Load the default language files
        load_plugin_textdomain('leyka-paypal', false, $plugin_lang_dir);
    }

    // Base Leyka isn't defined, deactivate this plugin:
    if( !defined('LEYKA_VERSION') ) {
        if( !function_exists('deactivate_plugins') )
            require_once(ABSPATH.'wp-admin/includes/plugin.php');
        deactivate_plugins(__FILE__);
        echo __('<div id="message" class="error"><strong>Error:</strong> base donations plugin is missing or inactive. It is required for PayPal donations gateway module to work. PayPal donations plugin will be deactivated.</div>', 'leyka-paypal');
    }
}
add_action('plugins_loaded', 'leyka_paypal_plugins_loaded', 10);

function leyka_paypal_init(){
    /**
     * Add paypal to the gateways list by filter hook.
     */
    function leyka_paypal_gateways($options){
        $options['paypal_donates'] = array(
            'admin_label' => __('PayPal (donates only!)', 'leyka-paypal'),
            'checkout_label' => __('PayPal Donates', 'leyka-paypal')
        );
        return $options;
    }
    add_filter('edd_payment_gateways', 'leyka_paypal_gateways', 5);

    /**
     * PayPal checkout form, so user can fill gateway specific fields.
     */
//    add_action('edd_paypal_donates_cc_form', function(){
//    });

    /**
     * Do some validation on our gateway specific fields if needed.
     */
//    add_action('edd_checkout_error_checks', function($checkout_form_data){ 
//    });

    /**
     * Do the gateway's data processing: redirect, saving data in DB, etc.
     */
    function leyka_paypal_processing($payment_data){
        global $edd_options;

        if(empty($edd_options['paypal_business_id'])) {
            edd_set_error('paypal_business_id_is_missing', __('Error: donations receiver\'s PayPal business ID has not been set. Please, report it to him.', 'leyka-paypal'));
            edd_send_back_to_checkout('?payment-mode='.$payment_data['post_data']['edd-gateway']);
        } else { // Success, redirect to PayPal to donate:

            if(empty($edd_options['paypal_currency_to_usd_course'])
                || (float)$edd_options['paypal_currency_to_usd_course'] <= 0.0)
                $edd_options['paypal_currency_to_usd_course'] = 1.0;

            // PayPal accepts payments only in USD, so use donations currency rate to convert payment amount to USD: 
            $payment_data['price'] = round($payment_data['price']/$edd_options['paypal_currency_to_usd_course'], 2);

            leyka_insert_payment($payment_data); // Process the payment on our side

            header('location: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amount='.$payment_data['price'].'&business='.$edd_options['paypal_business_id'].'&item_name='.$edd_options['paypal_item_name'].'&buyer_credit_promo_code=&buyer_credit_product_category=&buyer_credit_shipping_method=&buyer_credit_user_address_change=&no_shipping=1&currency_code='.$edd_options['paypal_currency_id'].'&tax=0&lc=US&bn=PP-DonationsBF');
            flush();
        }
    }
    add_action('edd_gateway_paypal_donates', 'leyka_paypal_processing');
}
add_action('init', 'leyka_paypal_init', 1);

function leyka_paypal_admin_init(){
    // Add settings link on plugin page:
    function leyka_paypal_plugin_page_links($links){
        array_unshift(
            $links,
            '<a href="'.admin_url('edit.php?post_type=download&page=edd-settings&tab=gateways#paypal_settings').'">'.__('Settings').'</a>'
        );
        return $links;
    }
    add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'leyka_paypal_plugin_page_links');

    function leyka_paypal_options($options){
        global $edd_options;
        array_push(
            $options,
            array(
                'id' => 'paypal_donates_settings',
                'name' => '<h4 id="paypal_settings">'.__('PayPal Settings', 'leyka-paypal').'</h4>',
                'type' => 'header',
                'desc' => '',
            ),
            array(
                'id' => 'paypal_business_id',
                'name' => __('PayPal business ID', 'leyka-paypal'),
                'desc' => __('Enter your PayPal business ID or email', 'leyka-paypal'),
                'type' => 'text',
                'size' => 'regular'
            ),
            array(
                'id' => 'paypal_item_name',
                'name' => __('PayPal item name', 'leyka-paypal'),
                'desc' => __('Enter your PayPal item name', 'leyka-paypal'),
                'type' => 'text',
                'size' => 'regular'
            ),
            array(
                'id' => 'paypal_currency_id',
                'name' => __('PayPal currency ID', 'leyka-paypal'),
                'desc' => __('Enter your PayPal currency ID, if it\'s different from ED Donates currency setting', 'leyka-paypal'),
                'type' => 'text',
                'size' => 'regular'
            ),
            array(
                'id' => 'paypal_currency_to_usd_course',
                'name' => sprintf(__('Donations currency (%s) to USD course', 'leyka-paypal'), $edd_options['currency']),
                'desc' => sprintf(__('Enter the cost of 1 USD in %s (a currency you selected for donations). This cost can be either more or less than 1.0. Default is 1.0.', 'leyka-paypal'), $edd_options['currency']),
                'type' => 'text',
                'size' => 'regular',
                'std' => '1.0',
            ),
            array(
                'id' => 'paypal_desc',
                'name' => __('PayPal gateway description', 'leyka-paypal'),
                'desc' => __('Enter PayPal gateway description that will be shown to the donor when this gateway will be selected for use', 'leyka-paypal'),
                'type' => 'rich_editor',
                'std' => '<a href="https://www.paypal.com/ru/webapps/mpp/home">PayPal</a> - популярная электронная валюта, способ отправки и получения средств через Интернет физическими лицами и компаниями.'
            )
        );
        return $options;
    }
    add_filter('edd_settings_gateways', 'leyka_paypal_options');

    // Enqueue backend javascript:
//    if(file_exists(dirname(__FILE__).'/scripts/script-admin.js')) {
//        if(function_exists('plugins_url')) {
//            wp_enqueue_script(
//                'leyka-yamo-script-admin',
//                plugins_url('/scripts/script-admin.js', __FILE__),
//                array('jquery'), '1.0', TRUE
//            );
//        } else {
//            wp_enqueue_script(
//                'leyka-yamo-script-admin',
//                dirname(__FILE__).'/scripts/script-admin.js',
//                array('jquery'), '1.0', TRUE
//            );
//        }
//    }
}
add_action('admin_init', 'leyka_paypal_admin_init', 1);
