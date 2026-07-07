<?php
/**
 * Print the FluentCRM tag titles of the contact given by email ($args[0]).
 * Run via: wp eval-file - <email>   (see helpers/wp.ts)
 */
$email = $args[0] ?? '';
if (!$email) {
	echo "\nJSON:" . json_encode(['error' => 'missing email argument']);
	exit(1);
}

$contact = FluentCrmApi('contacts')->getContact($email);
echo "\nJSON:" . json_encode([
	'tags' => $contact ? $contact->tags->pluck('title')->values()->all() : null,
]);
