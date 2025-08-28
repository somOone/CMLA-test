<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * SendGrid Helper Functions
 * Provides functions to get SendGrid configuration from multiple sources
 */

if (!function_exists('get_sendgrid_api_key')) {
    /**
     * Get SendGrid API key from config or environment variable
     * @param string $key_type - 'primary' or 'alt'
     * @return string
     */
    function get_sendgrid_api_key($key_type = 'primary') {
        $CI =& get_instance();
        
        // Try to get from CodeIgniter config first
        if ($key_type === 'alt') {
            $api_key = $CI->config->item('sendgrid_api_key_alt');
        } else {
            $api_key = $CI->config->item('sendgrid_api_key');
        }
        
        // If not found in config, try environment variable
        if (empty($api_key)) {
            if ($key_type === 'alt') {
                $api_key = getenv('SENDGRID_API_KEY_ALT');
            } else {
                $api_key = getenv('SENDGRID_API_KEY');
            }
        }
        
        return $api_key;
    }
}

if (!function_exists('get_sendgrid_config')) {
    /**
     * Get SendGrid configuration value
     * @param string $config_key
     * @param mixed $default
     * @return mixed
     */
    function get_sendgrid_config($config_key, $default = null) {
        $CI =& get_instance();
        
        // Try to get from CodeIgniter config first
        $value = $CI->config->item($config_key);
        
        // If not found in config, try environment variable
        if (empty($value)) {
            $env_key = strtoupper($config_key);
            $value = getenv($env_key);
        }
        
        return $value ?: $default;
    }
}

if (!function_exists('get_sendgrid_from_email')) {
    /**
     * Get SendGrid from email
     * @return string
     */
    function get_sendgrid_from_email() {
        return get_sendgrid_config('sendgrid_from_email', 'info@chinmayala.com');
    }
}

if (!function_exists('get_sendgrid_from_name')) {
    /**
     * Get SendGrid from name
     * @return string
     */
    function get_sendgrid_from_name() {
        return get_sendgrid_config('sendgrid_from_name', 'CMLA BV System Admin');
    }
}

if (!function_exists('get_sendgrid_bcc_emails')) {
    /**
     * Get SendGrid BCC emails
     * @return string
     */
    function get_sendgrid_bcc_emails() {
        return get_sendgrid_config('sendgrid_bcc_emails', 'cmla.community@gmail.com,sivagurunath77@gmail.com');
    }
}
?>
