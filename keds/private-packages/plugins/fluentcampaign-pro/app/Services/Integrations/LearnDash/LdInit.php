<?php

namespace FluentCampaign\App\Services\Integrations\LearnDash;

use FluentCrm\App\Models\Tag;
use FluentCrm\App\Services\Html\TableBuilder;
use FluentCrm\Framework\Support\Arr;

class LdInit
{
    public function init()
    {
        new \FluentCampaign\App\Services\Integrations\LearnDash\CourseEnrollTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\CourseLeaveTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\LessonCompletedTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\TopicCompletedTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\CourseCompletedTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\GroupEnrollTrigger();
        new \FluentCampaign\App\Services\Integrations\LearnDash\LearnDashImporter();

        // Course Actions
        new \FluentCampaign\App\Services\Integrations\LearnDash\AddToCourseAction();
        new \FluentCampaign\App\Services\Integrations\LearnDash\RemoveFromCourseAction();
        new \FluentCampaign\App\Services\Integrations\LearnDash\AddToGroupAction();
        new \FluentCampaign\App\Services\Integrations\LearnDash\RemoveFromGroupAction();

        // push profile section
        add_filter('fluentcrm_profile_sections', array($this, 'pushCoursesOnProfile'));

        add_filter('fluencrm_profile_section_ld_profile_courses', array($this, 'pushCoursesContent'), 10, 2);

        if (!apply_filters('fluentcrm_disable_integration_metaboxes', false, 'learndash')) {
            add_filter('learndash_settings_fields', array($this, 'addCourseGroupsFields'), 10, 2);

            add_action('save_post_sfwd-courses', array($this, 'saveCourseMetaBox'));

            add_action('learndash_update_course_access', array($this, 'maybeCourseEnrolledTags'), 20, 4);
            add_action('learndash_course_completed', array($this, 'maybeCourseCompletedTags'), 20);

            /*
             * Groups specific actions
             */
            add_action('save_post_groups', array($this, 'saveGroupMetaBox'));
            add_action('ld_added_group_access', array($this, 'maybeGroupEnrolledTags'), 10, 2);
            add_action('ld_removed_group_access', array($this, 'maybeGroupLeaveTagRemove'), 10, 2);
        }

        (new DeepIntegration())->init();
        (new LdSmartCodes())->init();

        add_action('learndash_update_course_access', function ($user_id, $course_id, $course_access_list, $remove) {
            if ($remove) {
                do_action('simulated_learndash_update_course_removed', $user_id, $course_id, $course_access_list, $remove);
            } else {
                do_action('simulated_learndash_update_course_added', $user_id, $course_id, $course_access_list, $remove);
            }
        }, 10, 4);

        add_filter('fluent_crm/subscriber_info_widgets', array($this, 'pushSubscriberInfoWidget'), 10, 2);
    }

    public function pushCoursesOnProfile($sections)
    {
        $sections['ld_profile_courses'] = [
            'name'    => 'fluentcrm_profile_extended',
            'title'   => __('Courses', 'fluentcampaign-pro'),
            'handler' => 'route',
            'query'   => [
                'handler' => 'ld_profile_courses'
            ]
        ];

        return $sections;
    }

    public function pushCoursesContent($content, $subscriber)
    {
        $content['heading'] = __('LearnDash Courses', 'fluentcampaign-pro');

        $userId = $subscriber->user_id;

        if (!$userId) {
            $content['content_html'] = '<p>' . __('No enrolled courses found for this contact', 'fluentcampaign-pro') . '</p>';
            return $content;
        }


        $courses = learndash_user_get_enrolled_courses($userId);


        if (empty($courses)) {
            $content['content_html'] = '<p>' . __('No enrolled courses found for this contact', 'fluentcampaign-pro') . '</p>';
            return $content;
        }

        $enrolledCourses = get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'sfwd-courses',
            'posts_per_page' => 100,
            'post__in'       => $courses,
        ]);

        $tableBuilder = new TableBuilder();
        foreach ($enrolledCourses as $course) {
            $completedAt = get_user_meta($userId, 'course_completed_' . $course->ID, true);
            $startAt = get_user_meta($userId, 'course_' . $course->ID . '_access_from', true);
            $completedSteps = '2';
            $tableBuilder->addRow([
                'id'           => $course->ID,
                'title'        => $course->post_title,
                'status'       => learndash_course_status($course->ID, $userId, false),
                'completed_at' => ($completedAt) ? gmdate('Y-m-d H:i', $completedAt) : '',
                'started_at'   => ($startAt) ? gmdate('Y-m-d H:i', $startAt) : ''
            ]);
        }

        $tableBuilder->setHeader([
            'id'           => __('ID', 'fluentcampaign-pro'),
            'title'        => __('Course Name', 'fluentcampaign-pro'),
            'started_at'   => __('Started At', 'fluentcampaign-pro'),
            'status'       => __('Status', 'fluentcampaign-pro'),
            'completed_at' => __('Completed At', 'fluentcampaign-pro')
        ]);

        $content['content_html'] = $tableBuilder->getHtml();
        return $content;
    }

    public function addCourseGroupsFields($fields, $metabox_key)
    {
        if ($metabox_key == 'learndash-course-access-settings') {
            global $post;

            if (empty($post) || empty($post->ID)) {
                return $fields;
            }

            $tagSettings = wp_parse_args(get_post_meta($post->ID, '_fluentcrm_settings', true), [
                'enrolled_tags'  => [],
                'completed_tags' => []
            ]);

            $formattedTags = [];
            foreach (Tag::get() as $tag) {
                $formattedTags[$tag->id . ' '] = $tag->title; //  WE NEED A SPACE not sure why they could not handle integer as value
            }

            $fields['fcrm_enrolled_tags'] = [
                'name'      => 'fcrm_enrolled_tags',
                'label'     => __('[FluentCRM] Apply Tags on course enrollment', 'fluentcampaign-pro'),
                'type'      => 'multiselect',
                'multiple'  => true,
                'help_text' => __('Selected tags will be applied to the contact on course enrollment', 'fluentcampaign-pro'),
                'options'   => $formattedTags,
                'value'     => (array)$tagSettings['enrolled_tags'],
                'default'   => [],
            ];

            $fields['fcrm_completed_tags'] = [
                'name'          => 'fcrm_completed_tags',
                'label'         => __('[FluentCRM] Apply Tags on course completion', 'fluentcampaign-pro'),
                'type'          => 'multiselect',
                'multiple'      => true,
                'select_option' => __('Select Tags', 'fluentcampaign-pro'),
                'help_text'     => __('Selected tags will be applied to the contact on course completion', 'fluentcampaign-pro'),
                'options'       => $formattedTags,
                'value'         => (array)$tagSettings['completed_tags'],
                'default'       => [],
            ];

        } else if ($metabox_key == 'learndash-group-access-settings') {
            global $post;

            if (empty($post) || empty($post->ID)) {
                return $fields;
            }

            $tagSettings = wp_parse_args(get_post_meta($post->ID, '_fluentcrm_settings', true), [
                'fcrm_enrolled_tags'   => [],
                'fcrm_remove_on_leave' => 'no'
            ]);

            $formattedTags = [];
            foreach (Tag::get() as $tag) {
                $formattedTags[$tag->id . ' '] = $tag->title; //  WE NEED A SPACE not sure why they could not handle integer as value
            }

            $fields['fcrm_enrolled_tags'] = [
                'name'      => 'fcrm_enrolled_tags',
                'label'     => __('[FluentCRM] Apply Tags on group enrollment', 'fluentcampaign-pro'),
                'type'      => 'multiselect',
                'multiple'  => true,
                'help_text' => __('Selected tags will be applied to the contact on group enrollment', 'fluentcampaign-pro'),
                'options'   => $formattedTags,
                'value'     => (array)$tagSettings['fcrm_enrolled_tags'],
                'default'   => [],
            ];

            $fields['fcrm_remove_on_leave'] = [
                'name'      => 'fcrm_remove_on_leave',
                'label'     => __('[FluentCRM] Remove Tags on group leave', 'fluentcampaign-pro'),
                'type'      => 'checkbox-switch',
                'options'   => array(
                    'yes' => __('selected contact tags will be removed when user leave this group', 'fluentcampaign-pro'),
                    'no'  => '',
                ),
                'help_text' => __('selected contact tags (defined in previous field) will be removed when user leave this group', 'fluentcampaign-pro'),
                'value'     => $tagSettings['fcrm_remove_on_leave'],
                'default'   => '',
            ];
        }

        return $fields;

    }

    public function saveCourseMetaBox($postId)
    {
        if (empty($_POST['post_ID']) || $_POST['post_ID'] != $postId || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)) {
            return;
        }

        if (!empty($_POST['learndash-course-access-settings'])) {
            $data = [
                'enrolled_tags'  => [],
                'completed_tags' => []
            ];

            if (!empty($_POST['learndash-course-access-settings']['fcrm_enrolled_tags'])) {
                $data['enrolled_tags'] = $_POST['learndash-course-access-settings']['fcrm_enrolled_tags'];
                unset($_POST['learndash-course-access-settings']['fcrm_enrolled_tags']);
            }

            if (!empty($_POST['learndash-course-access-settings']['fcrm_completed_tags'])) {
                $data['completed_tags'] = $_POST['learndash-course-access-settings']['fcrm_completed_tags'];
                unset($_POST['learndash-course-access-settings']['fcrm_completed_tags']);
            }

            update_post_meta($postId, '_fluentcrm_settings', $data);
        }

    }

    public function saveGroupMetaBox($postId)
    {
        if (empty($_POST['post_ID']) || !isset($_POST['post_ID']) || $_POST['post_ID'] != $postId || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !isset($_POST['post_type']) || $_POST['post_type'] != 'groups') {
            return;
        }

        if (!empty($_POST['learndash-group-access-settings'])) {

            $settings = Arr::only($_POST['learndash-group-access-settings'], ['fcrm_enrolled_tags', 'fcrm_remove_on_leave']);

            $settings = wp_parse_args($settings, [
                'fcrm_enrolled_tags'   => [],
                'fcrm_remove_on_leave' => ''
            ]);

            if (empty($settings['fcrm_enrolled_tags'])) {
                $settings['fcrm_enrolled_tags'] = [];
            }

            update_post_meta($postId, '_fluentcrm_settings', $settings);
        }
    }

    public function maybeCourseEnrolledTags($userId, $courseId, $accessList = [], $isRemoved = false)
    {

        $settings = get_post_meta($courseId, '_fluentcrm_settings', true);
        if (!$settings || empty($settings['enrolled_tags']) || !is_array($settings['enrolled_tags'])) {
            return false;
        }

        $tags = array_map(function ($tagId) {
            return intval($tagId);
        }, $settings['enrolled_tags']);

        $tags = array_filter($tags);
        if (!$tags) {
            return false;
        }

        Helper::createContactFromLd($userId, $tags);
        return true;
    }

    public function maybeGroupEnrolledTags($userId, $groupId)
    {

        $settings = get_post_meta($groupId, '_fluentcrm_settings', true);
        if (!$settings || empty($settings['fcrm_enrolled_tags']) || !is_array($settings['fcrm_enrolled_tags'])) {
            return false;
        }

        $tags = array_map(function ($tagId) {
            return intval($tagId);
        }, $settings['fcrm_enrolled_tags']);

        $tags = array_filter($tags);
        if (!$tags) {
            return false;
        }

        Helper::createContactFromLd($userId, $tags);
        return true;
    }

    public function maybeGroupLeaveTagRemove($userId, $groupId)
    {
        $settings = get_post_meta($groupId, '_fluentcrm_settings', true);
        if (!$settings || empty($settings['fcrm_enrolled_tags']) || !is_array($settings['fcrm_enrolled_tags']) || Arr::get($settings, 'fcrm_remove_on_leave') != 'yes') {
            return false;
        }

        $tagsToRemove = array_map(function ($tagId) {
            return (int)$tagId;
        }, $settings['fcrm_enrolled_tags']);

        $tagsToRemove = array_filter($tagsToRemove);
        if (!$tagsToRemove) {
            return false;
        }

        $contact = FluentCrmApi('contacts')->getContactByUserRef($userId);

        if ($contact) {
            $contact->detachTags($tagsToRemove);
            return true;
        }

        return false;
    }

    public function maybeCourseCompletedTags($data)
    {
        $settings = get_post_meta($data['course']->ID, '_fluentcrm_settings', true);
        if (!$settings || empty($settings['completed_tags']) || !is_array($settings['completed_tags'])) {
            return false;
        }

        $tags = array_map(function ($tagId) {
            return intval($tagId);
        }, $settings['completed_tags']);

        $tags = array_filter($tags);
        if (!$tags) {
            return false;
        }

        Helper::createContactFromLd($data['user'], $settings['completed_tags']);
        return true;
    }

    public function pushSubscriberInfoWidget($widgets, $subscriber)
    {
        if (!$subscriber->user_id) {
            return $widgets;
        }

        $userId  = $subscriber->user_id;
        $courses = learndash_user_get_enrolled_courses($userId);

        if (empty($courses)) {
            return $widgets;
        }

        $enrolledCourses = get_posts([
            'post_status'    => 'publish',
            'post_type'      => 'sfwd-courses',
            'posts_per_page' => 100,
            'post__in'       => $courses,
        ]);

        if (!$enrolledCourses) {
            return $widgets;
        }

        $items = [];

        foreach ($enrolledCourses as $course) {
            $rawStatus    = learndash_course_status($course->ID, $userId, false);
            $status       = $rawStatus ?: __('In Progress', 'fluentcampaign-pro');
            $statusClass  = (strtolower((string) $rawStatus) === 'completed') ? 'success' : 'pending';
            $durationSecs = $this->getCourseTotalDuration($course->ID);
            $duration     = $this->formatDuration($durationSecs);

            $item  = '<li class="fcrm_course_item">';
            $item .= '<div class="fcrm_course__title">';
            $item .= '<a target="_blank" rel="noopener" href="' . esc_url(get_permalink($course->ID)) . '">' . esc_html($course->post_title) . '</a>';

            $item .= '<span class="fcrm_badge fcrm_badge_'. $statusClass .'">' . esc_html($status) . '</span>';

            $item .= '</div>';

            if ($duration) {
                $item .= '<div class="fcrm_course__duration">';
                $item .= '<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 14C4.6862 14 2 11.3138 2 8C2 4.6862 4.6862 2 8 2C11.3138 2 14 4.6862 14 8C14 11.3138 11.3138 14 8 14ZM8 12.8C9.27304 12.8 10.4939 12.2943 11.3941 11.3941C12.2943 10.4939 12.8 9.27304 12.8 8C12.8 6.72696 12.2943 5.50606 11.3941 4.60589C10.4939 3.70571 9.27304 3.2 8 3.2C6.72696 3.2 5.50606 3.70571 4.60589 4.60589C3.70571 5.50606 3.2 6.72696 3.2 8C3.2 9.27304 3.70571 10.4939 4.60589 11.3941C5.50606 12.2943 6.72696 12.8 8 12.8ZM8.6 8H11V9.2H7.4V5H8.6V8Z" fill="currentColor"/></svg>';
                $item .= esc_html($duration);
                $item .= '</div>';
            }

            $item  .= '</li>';
            $items[] = $item;
        }

        $widgets[] = [
            'title'   => __('Course Enrollments', 'fluentcampaign-pro'),
            'content' => '<ul class="fc_full_listed">' . implode('', $items) . '</ul>',
        ];

        return $widgets;
    }

    /**
     * Sums the Forced Lesson Timer values across all lessons in a course and returns total seconds.
     * Only lessons with the timer explicitly enabled contribute to the total.
     */
    private function getCourseTotalDuration($courseId)
    {
        $cacheKey = 'fcrm_ld_course_duration_' . $courseId;
        $cachedDuration = get_transient($cacheKey);

        if ($cachedDuration !== false) {
            return (int) $cachedDuration;
        }

        $lessonIds = learndash_course_get_steps_by_type($courseId, 'sfwd-lessons');

        if (empty($lessonIds)) {
            set_transient($cacheKey, 0, HOUR_IN_SECONDS);
            return 0;
        }

        $totalSeconds = 0;
        foreach ($lessonIds as $lessonId) {
            $rawTime = learndash_forced_lesson_time($lessonId);
            if (!empty($rawTime)) {
                $totalSeconds += (int) learndash_convert_lesson_time_time($rawTime);
            }
        }

        set_transient($cacheKey, $totalSeconds, HOUR_IN_SECONDS);

        return $totalSeconds;
    }

    /**
     * Formats a duration in seconds to a compact "Xh Ym" string for display.
     */
    private function formatDuration($totalSeconds)
    {
        if ($totalSeconds <= 0) {
            return '';
        }

        $hours   = (int) floor($totalSeconds / 3600);
        $minutes = (int) floor(($totalSeconds % 3600) / 60);

        $seconds = (int) ($totalSeconds % 60);

        if ($hours > 0) {
            return $hours . 'h ' . $minutes . 'm ' . $seconds . 's';
        }

        if ($minutes > 0) {
            return $minutes . 'm ' . $seconds . 's';
        }

        return $seconds . 's';
    }
}
