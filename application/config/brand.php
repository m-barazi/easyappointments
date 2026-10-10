<?php defined('BASEPATH') or exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Branding / White-Label Configuration
|--------------------------------------------------------------------------
|
| Central place to customise the product branding that is shown in the user
| interface and the e-mails (product name, tagline, link and the optional
| "Powered by" / vendor promo). Change these values to white-label the
| application instead of editing individual templates.
|
| NOTE: These settings only control the configurable product branding. They
| do NOT remove and must not be used to remove the GPL-3.0 license notice or
| any copyright notice (see the LICENSE file and the file headers), which have
| to remain intact when distributing a modified version.
|
*/

// Product name shown in page titles and footers (e.g. "| <brand_name>").
$config['brand_name'] = 'Easy!Appointments';

// Short tagline shown next to the product name (e.g. in the backend header).
$config['brand_tagline'] = 'Online Appointment Scheduler';

// Link target used by the branding footer. Leave empty for plain text.
$config['brand_url'] = 'https://easyappointments.org';

// Show the "Powered by <brand_name>" line in the footers. Set to false to hide it.
$config['brand_show_powered_by'] = true;

// Show vendor promo elements (the author logo/link and the "Premium" link).
// Set to false for a fully white-labelled interface.
$config['brand_show_vendor_links'] = true;
