<?php

/**
 * Shared-hosting front-controller shim.
 *
 * On most shared hosts (cPanel/Plesk) the buyer extracts this package and points
 * the domain's document root at THIS folder rather than /public. The .htaccess in
 * this directory serves real assets out of public/ and rewrites every other request
 * to this file, so we simply hand off to Laravel's real entrypoint.
 *
 * __DIR__ inside public/index.php still resolves to the /public directory, so the
 * autoloader and bootstrap paths there stay correct. If the host's document root is
 * instead pointed directly at /public (the cleaner setup), this file is never hit and
 * everything still works.
 */
require __DIR__.'/public/index.php';
