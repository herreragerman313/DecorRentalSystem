<?php
// Site settings. On AWS, set these as environment variables instead of
// editing this file, so real passwords never end up on GitHub.

define('SITE_NAME', getenv('SITE_NAME') ?: 'Zero Waste Event Design');

// Business rules
define('ITEM_SETUP_FEE', 75.00);   // setup fee when someone rents single items (packages have their own)
define('LOYALTY_PERCENT', 10);     // returning customers save this % on rentals after their first return

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'decor_rental');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

// Set to true while building, false when the site is live
define('DEBUG', (getenv('APP_DEBUG') ?: 'true') === 'true');

date_default_timezone_set('America/Los_Angeles');
