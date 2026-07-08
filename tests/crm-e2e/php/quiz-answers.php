<?php
/**
 * Dump the quiz of a course ($args[0] = course post ID) with the correct
 * answer for every question, so the Playwright spec can take the quiz
 * through the real UI. Read-only. This is the programmatic equivalent of
 * the manual procedure's "look the answers up under LearnPress > Quizzes".
 */
global $wpdb;
$course_id = (int) ($args[0] ?? 0);
$p = $wpdb->prefix;

$items = $wpdb->get_results($wpdb->prepare(
	"SELECT si.item_id, si.item_type
	 FROM {$p}learnpress_sections s
	 JOIN {$p}learnpress_section_items si ON si.section_id = s.section_id
	 WHERE s.section_course_id = %d
	 ORDER BY s.section_order, si.item_order",
	$course_id
), ARRAY_A);

$quiz_id = 0;
foreach ($items as $item) {
	if ($item['item_type'] === 'lp_quiz') {
		$quiz_id = (int) $item['item_id'];
	}
}
if (!$quiz_id) {
	echo "\nJSON:" . json_encode(['error' => "no lp_quiz item found in course {$course_id}"]);
	exit(1);
}

$question_ids = $wpdb->get_col($wpdb->prepare(
	"SELECT question_id FROM {$p}learnpress_quiz_questions WHERE quiz_id = %d ORDER BY question_order",
	$quiz_id
));

$questions = [];
foreach ($question_ids as $qid) {
	$type = get_post_meta($qid, '_lp_type', true) ?: get_post_meta($qid, '_lp_question_type', true);
	$rows = $wpdb->get_results($wpdb->prepare(
		"SELECT question_answer_id, title, value, `order`, is_true
		 FROM {$p}learnpress_question_answers WHERE question_id = %d ORDER BY `order`",
		$qid
	), ARRAY_A);

	$q = [
		'id'      => (int) $qid,
		'title'   => html_entity_decode(wp_strip_all_tags(get_the_title($qid)), ENT_QUOTES),
		'type'    => $type,
		'correct' => [],
		'blanks'  => [],
	];

	if ($type === 'fill_in_blanks') {
		// Blank fills live inline in the answer row content as
		// [fib fill="word" id="hash" ] shortcodes (see LearnPress
		// QuestionPostFIBModel::convert_content_from_editor_to_db). The
		// frontend renders one input per shortcode, keyed by data-id.
		foreach ($rows as $row) {
			if (preg_match_all('/\[fib\s+fill="([^"]*)"\s+id="([^"]+)"\s*\]/', $row['title'], $m, PREG_SET_ORDER)) {
				foreach ($m as $match) {
					$q['blanks'][] = ['id' => $match[2], 'fill' => html_entity_decode($match[1], ENT_QUOTES)];
				}
			}
		}
	} else {
		foreach ($rows as $row) {
			if ($row['is_true'] === 'yes') {
				$q['correct'][] = html_entity_decode(wp_strip_all_tags($row['title']), ENT_QUOTES);
			}
		}
	}

	$questions[] = $q;
}

echo "\nJSON:" . json_encode([
	'quiz_id'       => $quiz_id,
	'quiz_title'    => get_the_title($quiz_id),
	'passing_grade' => get_post_meta($quiz_id, '_lp_passing_grade', true),
	'questions'     => $questions,
]);
