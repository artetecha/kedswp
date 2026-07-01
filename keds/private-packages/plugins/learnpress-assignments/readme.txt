=== LearnPress - Assignments ===
Contributors: Thimpress
Donate link:
Tags: learnpress, lms, assignment
Tested up to: 6.8
Stable tag: 4.1.7

== Changelog ==

= 4.2.0 (2026-04-11) =
~ Fixed: minor bugs.

= 4.1.9 (2025-10-30) =
~ Fixed: time of sending mail evaluated.

= 4.1.8 (2025-10-08) =
~ Fixed: minor bugs.

= 4.1.7 (2025-09-22) =
~ Tweak send mail hooks for compatible with LP v4.2.9.2.
~ Deprecated some shortcode old. Not run correct.
~ Added AssignmentAjax class.

= 4.1.6 (2025-09-04) =
~ Fixed: display iframe, audio on the description.
~ Fixed: text domain.
~ Clean code.

= 4.1.5 (2025-06-30) =
~ Added: hook 'learn-press/assignment/user/can-retake', 'learn-press/assignment/instructor-re-evaluated', 'learn-press/assignment/layout/user-submitted'.
~ Tweak: query_profile_assignments method.
~ Fixed: minor bugs.

= 4.1.4 (2025-03-04) =
~ Fixed: minor bugs.

= 4.1.3 (2024-12-18) =
~ Fixed: error save introduction.
~ Fixed: alert confirm send submission.
~ Tweak: display retake.

= 4.1.2 (2024-12-11) =
~ Refactor, optimize code.
~ Tweak all layout on the Frontend.
~ Tweak display on the Backend.

= 4.1.1 (2023-08-12) =
~ Fixed: item not detect completed with Content Drip.
~ Fixed: minor bugs.
~ Fixed: error teacher can't evaluate mark point type decimal.
~ Fixed: error user sent Assignment completed will not count time.

= 4.1.0 (2023-03-09) =
~ Fixed: evaluate button translate will be can't submit.
~ Fixed: slug assignment incorrect with setting on Permalinks Course.
~ Added: slug rewrite rule, hook LP v4.2.2.2.
~ Fixed: translate text domain.

= 4.0.9 (2023-01-10) =
~ Fixed: mobile app API.
~ Fixed: time remaining in assignment.
~ Fixed: wrong passing condition of course by final Assignment.

= 4.0.8 (2022-11-22) =
~ Compatible PHP 8.1
~ Replace call array key ['items'] to get_items of LP_Query_List_Table.
~ Deprecated: __get on the LP_Assignment class.
~ Remove implement ArrayAccess on the LP_Assignment class.
~ Modified: learn_press_assignment_get_uploaded_files.
~ Added: Assignment API for Frontend Editor.

= 4.0.7 (2022-09-28) =
~ Fixed: error with PHP version lower 7.2.

= 4.0.6 (2022-09-28) =
~ Compatible with addon Frontend Editor 4.0.2.
~ Remove "learn_press_get_user_item_id" function.
~ Check $user->get_course_data, $course_data->get_item.
~ Remove some files use for old Frontend Editor (lower 4.0.2).
~ Fixed: error not send mail when student submit answer.
~ Fixed: error display one line on the content of answer.

= 4.0.5 (2022-09-15) =
~ Fix: error with LP 4.1.7.
~ Fix: error with api course jwt have quiz and assignment.
~ Update: assignment meta-box for FE.

= 4.0.4 (2022-08-09) =
~ Replace: all LP_Global::course to learn_press_get_course()
~ Fixed : translated item assignment with Addon LP WPML.
~ Fixed: upload limit.

= 4.0.3 =
~ Fixed: Compatible with LP v4.1.5 - Curriculum.

= 4.0.2 =
~ Fixed: error "Evaluate via results of the final assignment".
~ Fixed: error display wrong duration.

= 4.0.1 =
~ Add API.
~ Fixed: value of meta key '_lp_assignment_instructor_note' > 255 will be lost string, move to save extra_value.
~ Fixed: send emails.

= 4.0.0 =
~ Fix compatible with LP4.
~ Add send email for instructor.
~ New evaluate metabox in course.
~ Fix JS compatible with jQuery > 3.0.
~ Optimize database for query in LP4.
~ Fix WordPress Coding Standard error, warming.
