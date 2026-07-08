<?php
/**
 * Reset the CRM-workflow test state so the enroll -> complete journey can be
 * exercised repeatedly against the same course. MUTATING — only ever run
 * against a designated TEST environment.
 *
 * Args: 0=email, 1=course_id, 2=enrolled_tag_title, 3=completed_tag_title,
 *       4=coupon_code, 5=first_name (optional), 6=last_name (optional)
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
$first_name    = $args[5] ?? 'CRM';
$last_name     = $args[6] ?? 'E2E Test';

$done = [];
$fail = function ($msg) {
	echo "\nJSON:" . json_encode(['error' => $msg]);
	exit(1);
};

// Dedicated test student: created on first run against any environment. The
// password is random and never used — the suite logs in via a minted cookie.
$user = get_user_by('email', $email);
if (!$user) {
	$user_id = wp_insert_user([
		'user_login'   => sanitize_user(strstr($email, '@', true) . '-crm-e2e', true),
		'user_email'   => $email,
		'user_pass'    => wp_generate_password(32),
		'first_name'   => $first_name,
		'last_name'    => $last_name,
		'display_name' => trim("{$first_name} {$last_name}"),
		'role'         => 'subscriber',
	]);
	if (is_wp_error($user_id)) { $fail('could not create WP user: ' . $user_id->get_error_message()); }
	$user = get_user_by('id', $user_id);
	$done['wp_user_created'] = $user->ID;
}
if (!$course_id || get_post_type($course_id) !== 'lp_course') { $fail("post {$course_id} is not an lp_course"); }
if (!$enrolled_tag || !$completed_tag || !$coupon_code) { $fail('missing tag titles or coupon code'); }

// -- 0. Complete billing address + empty leftover cart ------------------------
// The recording's "make sure this information's up to date" step: block
// checkout refuses to place an order with an incomplete billing address, so
// fill any missing pieces deterministically. Also drop the persistent cart a
// previous (possibly aborted) run may have left for this user.
$customer = new WC_Customer($user->ID);
$billing_defaults = [
	'billing_first_name' => $user->first_name ?: 'CRM',
	'billing_last_name'  => $user->last_name ?: 'E2E Test',
	'billing_address_1'  => '1st Floor, 415 High Street',
	'billing_city'       => 'London',
	'billing_postcode'   => 'E15 4QZ',
	'billing_country'    => 'GB',
	'billing_email'      => $email,
];
foreach ($billing_defaults as $prop => $default) {
	$getter = "get_{$prop}";
	$setter = "set_{$prop}";
	if (!$customer->{$getter}()) {
		$customer->{$setter}($default);
		$done['billing_filled'][] = $prop;
	}
}
$customer->save();
delete_user_meta($user->ID, '_woocommerce_persistent_cart_' . get_current_blog_id());
// The WooCommerce session (keyed by user ID for logged-in users) caches both
// the cart and a copy of the customer address; block checkout reads THAT, so
// a stale session from an earlier run masks the user-meta fix above and
// carries a leftover cart. Drop it; the next request rebuilds it fresh.
global $wpdb;
$wpdb->delete("{$wpdb->prefix}woocommerce_sessions", ['session_key' => (string) $user->ID]);
// Sessions are additionally cached in the persistent object cache (Redis on
// Upsun) under the wc_session_id group — deleting only the DB row leaves the
// stale copy live. Bump the group prefix to invalidate it.
if (class_exists('WC_Cache_Helper')) {
	\WC_Cache_Helper::incr_cache_prefix('wc_session_id');
}
$done['wc_session_cleared'] = true;

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
if (!$contact) {
	$contact = FluentCrmApi('contacts')->createOrUpdate([
		'email'      => $email,
		'first_name' => $first_name,
		'last_name'  => $last_name,
		'status'     => 'subscribed',
		'user_id'    => $user->ID,
	]);
	$done['crm_contact_created'] = (bool) $contact;
}
if (!$contact) { $fail("no FluentCRM contact for {$email} and could not create one"); }
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
	// Tag-trigger funnels keep their tag list under settings.tags; fall back
	// to scanning the whole settings blob if that shape ever changes.
	$subscribed_tags = array_map('intval', (array) ($settings['tags'] ?? []));
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
	// The processor also skips sequences whose metric rows say "completed"
	// for this subscriber — a previous pass would silently swallow the
	// detach-Enrolled-tag action on re-entry. Clear them too.
	\FluentCrm\App\Models\FunnelMetric::where('subscriber_id', $contact->id)
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
