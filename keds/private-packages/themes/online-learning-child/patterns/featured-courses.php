<?php
/**
 * Title: KEDS Featured Courses
 * Slug: online-learning-child/featured-courses
 * Categories: featured, posts, query
 * Description: A three-up grid of LearnPress courses, driven by the live LMS.
 *
 * @package online-learning-child
 */
?>
<!-- wp:group {"align":"full","backgroundColor":"surface","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-surface-background-color has-background" style="padding-top:var(--wp--preset--spacing--80);padding-bottom:var(--wp--preset--spacing--80)"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">Start with a course</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}}} -->
<p class="has-text-align-center" style="margin-bottom:var(--wp--preset--spacing--60)">Follow a clear pathway with an expert guide — and earn recognised qualifications as you go.</p>
<!-- /wp:paragraph -->

<!-- wp:learnpress/list-courses {"courseQuery":{"limit":3,"order_by":"post_date","pagination":false,"related":false,"term_id":"","tag_id":"","load_ajax":false,"load_ajax_after":false},"align":"wide"} -->
<div class="wp-block-learnpress-list-courses alignwide"><!-- wp:learnpress/course-item-template {"layout":"grid"} -->
<div class="wp-block-learnpress-course-item-template"><!-- wp:group {"metadata":{"name":"Item Inner"},"className":"wp-block-learnpress-course-item-template-inner","style":{"border":{"radius":"8px","width":"1px","color":"var:preset|color|border-color"},"color":{"background":"#ffffff"},"spacing":{"blockGap":"0","padding":{"right":"var:preset|spacing|20","left":"var:preset|spacing|20","top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group wp-block-learnpress-course-item-template-inner has-background" style="border-color:var(--wp--preset--color--border-color);border-width:1px;border-radius:8px;background-color:#ffffff;padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:learnpress/course-image {"style":{"border":{"radius":{"topLeft":"8px","topRight":"8px","bottomLeft":"8px","bottomRight":"8px"}}}} /-->

<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"},"blockGap":"12px"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--20)"><!-- wp:learnpress/star-info {"rated":true} /-->

<!-- wp:learnpress/course-title {"tag":"h6","isLink":true} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
<div class="wp-block-group"><!-- wp:learnpress/course-student {"showLabel":false} /-->

<!-- wp:learnpress/course-duration {"showLabel":false} /--></div>
<!-- /wp:group -->

<!-- wp:learnpress/course-price /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:learnpress/course-item-template --></div>
<!-- /wp:learnpress/list-courses -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--60)"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/course-overview/">View all courses</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
