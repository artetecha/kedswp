<?php
/**
 * Mint a short-lived logged_in auth cookie for the WP user whose email is
 * $args[0], so Playwright can act as that user without knowing (or
 * resetting) any password. The frontend + /wp-json/lp/v1 REST calls only
 * need the logged_in scheme; no wp-admin access is granted or used.
 */
$email = $args[0] ?? '';
$user = $email ? get_user_by('email', $email) : false;
if (!$user) {
	echo "\nJSON:" . json_encode(['error' => "no WP user for email: {$email}"]);
	exit(1);
}

$expiration = time() + 2 * HOUR_IN_SECONDS;
$cookie = wp_generate_auth_cookie($user->ID, $expiration, 'logged_in');

echo "\nJSON:" . json_encode([
	'name'    => LOGGED_IN_COOKIE,
	'value'   => $cookie,
	'expires' => $expiration,
	'user_id' => $user->ID,
	'login'   => $user->user_login,
]);
