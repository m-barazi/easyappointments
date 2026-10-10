<?php defined('BASEPATH') or exit('No direct script access allowed');

/* ----------------------------------------------------------------------------
 * Easy!Appointments - Online Appointment Scheduler
 *
 * @package     EasyAppointments
 * @author      A.Tselegidis <alextselegidis@gmail.com>
 * @copyright   Copyright (c) Alex Tselegidis
 * @license     https://opensource.org/licenses/GPL-3.0 - GPLv3
 * @link        https://easyappointments.org
 * @since       v1.6.0
 * ---------------------------------------------------------------------------- */

/**
 * Branding helper.
 *
 * Provides a single source of truth for the configurable product branding
 * (see application/config/brand.php) so the product name, tagline and the
 * optional "Powered by" / vendor promo are not hard-coded across templates.
 */

if (!function_exists('brand_name')) {
    /**
     * Get the configurable product name.
     */
    function brand_name(): string
    {
        $value = config('brand_name');

        return $value !== null && $value !== '' ? (string) $value : 'Easy!Appointments';
    }
}

if (!function_exists('brand_tagline')) {
    /**
     * Get the configurable product tagline.
     */
    function brand_tagline(): string
    {
        return (string) (config('brand_tagline') ?? '');
    }
}

if (!function_exists('brand_url')) {
    /**
     * Get the configurable branding link (may be empty).
     */
    function brand_url(): string
    {
        return (string) (config('brand_url') ?? '');
    }
}

if (!function_exists('brand_show_powered_by')) {
    /**
     * Whether the "Powered by" line should be rendered.
     */
    function brand_show_powered_by(): bool
    {
        $value = config('brand_show_powered_by');

        return $value === null ? true : (bool) $value;
    }
}

if (!function_exists('brand_show_vendor_links')) {
    /**
     * Whether vendor promo elements (author logo/link, Premium link) should be rendered.
     */
    function brand_show_vendor_links(): bool
    {
        $value = config('brand_show_vendor_links');

        return $value === null ? true : (bool) $value;
    }
}

if (!function_exists('brand_powered_by_html')) {
    /**
     * Build the "Powered by <brand>" snippet, or an empty string when disabled.
     *
     * The brand name and URL are HTML-escaped. This is branding only and is
     * independent of the GPL/copyright notices, which are rendered separately.
     */
    function brand_powered_by_html(): string
    {
        if (!brand_show_powered_by()) {
            return '';
        }

        $name = htmlspecialchars(brand_name(), ENT_QUOTES, 'UTF-8');
        $url = brand_url();

        if ($url !== '') {
            $safe_url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
            $label = '<a href="' . $safe_url . '" target="_blank" rel="noopener">' . $name . '</a>';
        } else {
            $label = $name;
        }

        return 'Powered by ' . $label;
    }
}
