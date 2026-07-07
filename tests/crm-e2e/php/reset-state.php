<?php
/**
 * Reset the CRM-workflow test state so the enroll -> complete journey can be
 * exercised repeatedly against the same course. MUTATING — only ever run
 * against a designated TEST environment.
 *
 * Args: 0=email, 1=course_id, 2=enrolled_tag_title, 3=completed_tag_title,
 *       4=coupon_code
 *
 * Steps:
 *  1. Cancel previous test orders: WooCommerce orders for this billing email
 *     whose only line item is this course AND whose total is 0 (the manual
 *     procedure's 100%-coupon orders match too, by design).
 *  2. Cancel LearnPress orders for this user + course.
 *  3. Delete the user's LearnPress enrollment + progress for the course.
 *  4. Detach the Enrolled/Completed tags from the FluentCRM contact.
 *  5. Delete the contact's funnel-subscriber rows for published funnels
 *     triggered by either tag, so once-per-contact automations (e.g.
 *     "KYB 3 - Completion and Advancement", which removes the Enrolled tag)
 *     can re-enter on the next run.
 *  6. Upsert the dedicated 100% coupon, restricted to this course.
 */

$email         = $args[0] ?? '';
$course_id     = (int) ($args[1] ?? 0);
$enrolled_tag  = $args[2] ?? '';
$completed_tag = $args[3] ?? '';
$coupon_code   = $args[4] ?? '';

$done = [];
$fail = function ($msg) {
	echo "\nJSON:" . json_encode(['error' => $msg]);
	exit(1);
};

$user = get_user_by('email', $email);
if (!$user) { $fail("no WP user for {$email}"); }
if (!$course_id || get_post_type($course_id) !== 'lp_course') { $fail("post {$course_id} is not an lp_course"); }
if (!$enrolled_tag || !$completed_tag || !$coupon_code) { $fail('missing tag titles or coupon code'); }

// -- 1. Cancel previous £0 test orders for this course ----------------------
$orders = wc_get_orders([
	'billing_email' => $email,
	'limit'         => -1,
	'status'        => ['pending', 'processing', 'on-hold', 'completed'],
]);
foreach ($orders as $order) {
	$items = $order->get_items();
	if (count($items) !== 1) { continue; }
	$item = reset($items);
	if ((int) $item->get_product_id() !== $course_id) { continue; }
	if ((float) $order->get_total() > 0) { continue; }
	$order->update_status('cancelled', 'crm-e2e: reset before automated CRM workflow test');
	$done['wc_orders_cancelled'][] = $order->get_id();
}

// -- 2. Cancel LearnPress orders for this user + course ----------------------
$lp_order_ids = get_posts([
	'post_type'      => 'lp_order',
	'post_status'    => 'any',
	'fields'         => 'ids',
	'posts_per_page' => -1,
	'meta_query'     => [[ 'key' => '_user_id', 'value' => $user->ID ]],
]);
foreach ($lp_order_ids as $lp_order_id) {
	$lp_order = learn_press_get_order($lp_order_id);
	if (!$lp_order || $lp_order->get_status() === 'cancelled') { continue; }
	$has_course = false;
	foreach ((array) $lp_order->get_items() as $item) {
		$cid = is_array($item) ? (int) ($item['course_id'] ?? $item['item_id'] ?? 0) : 0;
		if ($cid === $course_id) { $has_course = true; }
	}
	if ($has_course) {
		$lp_order->update_status('cancelled');
		$done['lp_orders_cancelled'][] = $lp_order_id;
	}
}

// -- 3. Remove enrollment + progress -----------------------------------------
\LP_User_Items_DB::getInstance()->delete_user_items_old($user->ID, $course_id);
$done['lp_user_items_deleted'] = true;

// -- 4. Detach tags from the contact -----------------------------------------
$contact = FluentCrmApi('contacts')->getContact($email);
if (!$contact) { $fail("no FluentCRM contact for {$email}"); }
$tags = \FluentCrm\App\Models\Tag::whereIn('title', [$enrolled_tag, $completed_tag])->get();
if ($tags->count() !== 2) { $fail("expected 2 tags ({$enrolled_tag}, {$completed_tag}), found {$tags->count()}"); }
$tag_ids = $tags->pluck('id')->all();
$contact->detachTags($tag_ids);
$done['tags_detached'] = $tags->pluck('title')->all();

// -- 5. Allow re-entry into the tag-triggered funnels -------------------------
$funnel_ids = [];
$funnels = \FluentCrm\App\Models\Funnel::where('status', 'published')
	->where('trigger_name', 'fluentcrm_contact_added_to_tags')->get();
foreach ($funnels as $funnel) {
	$settings = is_string($funnel->settings) ? json_decode($funnel->settings, true) : $funnel->settings;
	// Tag-trigger funnels keep their tag list under settings.subscribes; fall
	// back to scanning the whole settings blob if that shape ever changes.
	$subscribed_tags = array_map('intval', (array) ($settings['subscribes'] ?? []));
	if (!$subscribed_tags && preg_match_all('/\d+/', wp_json_encode($settings), $m)) {
		$subscribed_tags = array_map('intval', $m[0]);
	}
	if (array_intersect($subscribed_tags, array_map('intval', $tag_ids))) {
		$funnel_ids[] = $funnel->id;
		$done['funnels_reset'][] = $funnel->title;
	}
}
if ($funnel_ids) {
	\FluentCrm\App\Models\FunnelSubscriber::where('subscriber_id', $contact->id)
		->whereIn('funnel_id', $funnel_ids)->delete();
}

// -- 6. Upsert the dedicated coupon -------------------------------------------
$coupon = new WC_Coupon($coupon_code);
$coupon->set_code($coupon_code);
$coupon->set_discount_type('percent');
$coupon->set_amount(100);
$coupon->set_date_expires(null);
$coupon->set_usage_limit(0);
// Only ever discounts the course under test; harmless if the code leaks.
$coupon->set_product_ids([$course_id]);
$coupon->set_description('Managed by tests/crm-e2e (CRM workflow E2E). Do not edit; recreated on every run.');
$coupon->save();
$done['coupon_id'] = $coupon->get_id();

$done['baseline_tags'] = FluentCrmApi('contacts')->getContact($email)->tags->pluck('title')->values()->all();
echo "\nJSON:" . json_encode($done);
