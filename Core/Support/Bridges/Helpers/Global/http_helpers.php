<?php

if (!function_exists('old')) {
    /**
     * Get previous input from session (Laravel-style)
     */
    function old(string $key, $default = null)
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('response')) {
    /**
     * Get the HTTP response engine instance ⚓🛡️
     * 
     * @return \Rbn\Framework\Core\Http\Response
     */
    function response(): \Rbn\Framework\Core\Http\Response
    {
        return new \Rbn\Framework\Core\Http\Response();
    }
}

if (!function_exists('request')) {
    /**
     * Get the HTTP request engine instance ⚓🛡️
     * 
     * @return \Rbn\Framework\Core\Http\Request
     */
    function request(): \Rbn\Framework\Core\Http\Request
    {
        return \Rbn\Framework\Core\Http\Request::capture();
    }
}

if (!function_exists('alert')) {
    /**
     * Get the Notification engine instance 🔔🛡️
     * 
     * @return \Rbn\Framework\Core\Http\AlertService
     */
    function alert(): \Rbn\Framework\Core\Http\AlertService
    {
        return \Rbn\Framework\Core\Base\Services\BaseService::get()->service('alert');
    }
}
