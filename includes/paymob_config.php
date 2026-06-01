<?php
/**
 * paymob_config.php
 * Paymob Payment Gateway Configuration File
 *
 * Configure your Paymob developer account details here.
 * Set PAYMOB_MODE to 'live' for real payments (or sandbox test keys),
 * or keep it as 'simulation' for safe offline testing and presentations.
 */

// ── Paymob Merchant Credentials ───────────────────────────────────────────
// Get these from your Paymob Dashboard -> Settings
define('PAYMOB_API_KEY', 'ZXlKaGJHY2lPaUpJVXpVeE1pSXNJblI1Y0NJNklrcFhWQ0o5LmV5SmpiR0Z6Y3lJNklrMWxjbU5vWVc1MElpd2ljSEp2Wm1sc1pWOXdheUk2TVRFM01URTFNQ3dpYm1GdFpTSTZJbWx1YVhScFlXd2lmUS5sV3VXOHozUHNCdVJSRkU2QWNXVjFlc09GQ2UwLW1aeWRaclpoU2dnZEY0OXBMb0Z3VTFSRFpVMV8xQWtKbXlPLXRBQUdNRi00eE9pR3k0RmxYT19hUQ=='); 

// Get this from Settings -> Integrations -> Transaction Channels (Card Integration)
define('PAYMOB_INTEGRATION_ID', '5692956'); 

// Get this from Settings -> Iframes
define('PAYMOB_IFRAME_ID', '1048600'); 

// Get this from Settings -> HMAC Secret (optional, used for security signature checking)
define('PAYMOB_HMAC_SECRET', '7E883C100E52BBDCE568A7C18D5FF731'); 

// ── Mode Toggle ────────────────────────────────────────────────────────────
// Options: 'simulation' or 'live'
// 'simulation' -> Beautiful fake Paymob interface. Perfect for offline presentation!
// 'live'       -> Real Paymob gateway integration via APIs and redirect.
define('PAYMOB_MODE', 'live'); 

// ── Base URL of your website ───────────────────────────────────────────────
// Required for Paymob redirect callbacks. Change if your path is different.
define('SITE_BASE_URL', 'http://localhost/Graduation-Project'); 
?>
