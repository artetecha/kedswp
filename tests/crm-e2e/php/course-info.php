<?php
/**
 * Resolve the course under test ($args[0] = course post ID) to its public
 * URL and title. Read-only.
 */
$course_id = (int) ($args[0] ?? 0);
if (get_post_type($course_id) !== 'lp_course') {
	echo "\nJSON:" . json_encode(['error' => "post {$course_id} is not an lp_course"]);
	exit(1);
}

echo "\nJSON:" . json_encode([
	'url'   => get_permalink($course_id),
	'title' => get_the_title($course_id),
]);
