<?php
namespace LearnPress\Certificate\TemplateHooks\CourseBuilder;


defined( 'ABSPATH' ) || exit;

use LearnPress\Certificate\Models\CertificatePostModel;
use LearnPress\CourseBuilder\CourseBuilder;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\CourseBuilder\CourseBuilderTemplate;
use LP_Certificate;
use LP_Certificate_DB;
use LP_Certificate_Filter;
use LP_Database;
use Throwable;

/**
 * Class CBListCertificatesTemplate
 *
 * Template for certificate list display on Course Builder screen
 */
class CBListCertificatesTemplate {
	use Singleton;

	public function init() {}

	public function layout( array $data = [] ): string {
		$tab = [
			'wrapper'      => '<div class="cb-tab-certificate">',
			'header'       => $this->html_header(),
			'filter_bar'   => $this->html_filter_bar(),
			'certificates' => $this->tab_list_certificates(),
			'wrapper_end'  => '</div>',
		];

		return Template::combine_components( $tab );
	}

	public function html_header(): string {
		$section = [
			'wrapper'     => '<div class="cb-tab-header">',
			'title'       => sprintf( '<h2 class="lp-cb-tab__title">%s</h2>', esc_html__( 'Certificates', 'learnpress-certificates' ) ),
			'actions'     => sprintf(
				'<div class="cb-tab-header-actions" style="display:flex;align-items:center;gap:8px;">%s</div>',
				$this->html_btn_add_new()
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_filter_bar(): string {
		$section = [
			'wrapper'     => '<div class="cb-tab-certificate__action">',
			'search'      => $this->html_search(),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	public function html_search(): string {
		$args     = lp_archive_skeleton_get_args();
		$link_tab = CourseBuilder::get_link_course_builder( CBCertificateTemplate::MENU_CERTIFICATES );

		$search = [
			'wrapper'     => sprintf( '<form class="cb-search-form" method="get" action="%s">', $link_tab ),
			'search_btn'  => '<button class="cb-search-btn" type="submit"><i class="lp-icon-search"></i></button>',
			'input'       => sprintf(
				'<input class="cb-input-search-lesson" type="search" placeholder="%s" name="c_search" value="%s">',
				esc_attr__( 'Search', 'learnpress-certificates' ),
				esc_attr( $args['c_search'] ?? '' )
			),
			'wrapper_end' => '</form>',
		];

		return Template::combine_components( $search );
	}

	public function html_btn_add_new(): string {
		$link = '';

		if ( class_exists( CourseBuilder::class ) && method_exists( CourseBuilder::class, 'get_link_add_new' ) ) {
			$link = CourseBuilder::get_link_add_new( 'certificates' );
		} elseif ( class_exists( CourseBuilder::class ) && method_exists( CourseBuilder::class, 'get_link_course_builder' ) ) {
			$link = CourseBuilder::get_link_course_builder( 'certificates/create' );
		}

		return sprintf(
			'<a href="%s" class="lp-button cb-btn-add-new">%s</a>',
			esc_url( $link ),
			esc_html__( 'Add New Certificate', 'learnpress-certificates' )
		);
	}

	public function tab_list_certificates(): string {
		$content = '';

		try {
			$param = lp_archive_skeleton_get_args();

			$filter              = new LP_Certificate_Filter();
			$filter->limit       = 10;
			$filter->page        = $GLOBALS['wp_query']->get( 'paged', 1 ) ?: 1;
			$filter->only_fields = [ 'ID', 'post_title', 'post_date', 'post_status' ];
			$filter->order_by    = 'cer.post_date';

			if ( ! empty( $param['c_search'] ) ) {
				$search          = sanitize_text_field( $param['c_search'] );
				$filter->where[] = LP_Certificate_DB::getInstance()->wpdb->prepare(
					'AND cer.post_title LIKE %s',
					'%' . LP_Certificate_DB::getInstance()->wpdb->esc_like( $search ) . '%'
				);
			}

			if ( ! current_user_can( 'manage_options' ) ) {
				$filter->where[] = LP_Certificate_DB::getInstance()->wpdb->prepare(
					'AND cer.post_author = %d',
					get_current_user_id()
				);
			}

			$override_status = function ( $f ) {
				$f->where   = array_filter(
					$f->where,
					function ( $w ) {
						return strpos( $w, 'post_status' ) === false;
					}
				);
				$f->where[] = "AND cer.post_status IN ('publish', 'draft', 'pending', 'private', 'trash')";

				return $f;
			};
			add_filter( 'lp/certificate/query/filter', $override_status );

			$total_rows   = 0;
			$certificates = LP_Certificate::query_certificates( $filter, $total_rows );

			remove_filter( 'lp/certificate/query/filter', $override_status );

			if ( empty( $certificates ) ) {
				$content = Template::print_message(
					__( 'No certificates found', 'learnpress-certificates' ),
					'info',
					false
				);
			} else {
				$items_html = '';

				foreach ( $certificates as $cert ) {
					$items_html .= $this->html_certificate_item( $cert );
				}

				$header  = '<div class="cb-list-table-header">';
				$header .= sprintf( '<span>%s</span>', __( 'Certificate Title', 'learnpress-certificates' ) );
				$header .= sprintf( '<span>%s</span>', __( 'Courses', 'learnpress-certificates' ) );
				$header .= sprintf( '<span>%s</span>', __( 'Create Date', 'learnpress-certificates' ) );
				$header .= sprintf( '<span>%s</span>', __( 'Status', 'learnpress-certificates' ) );
				$header .= sprintf( '<span>%s</span>', __( 'Actions', 'learnpress-certificates' ) );
				$header .= '</div>';

				$sections = [
					'wrapper'     => '<div class="courses-builder__lesson-tab learn-press-certificates">',
					'header'      => $header,
					'list_wrap'   => '<ul class="cb-list-lesson">',
					'list'        => $items_html,
					'list_end'    => '</ul>',
					'wrapper_end' => '</div>',
				];

				$content = Template::combine_components( $sections );

				$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );
				if ( $total_pages > 1 ) {
					$link_tab = CourseBuilder::get_link_course_builder( CBCertificateTemplate::MENU_CERTIFICATES );
					$base_url = trailingslashit( $link_tab ) . 'page/%#%';

					$data_pagination = [
						'total'    => $total_pages,
						'current'  => max( 1, $filter->page ),
						'base'     => $base_url,
						'format'   => '',
						'per_page' => $filter->limit,
					];

					ob_start();
					Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
					$content .= ob_get_clean();
				}
			}
		} catch ( Throwable $e ) {
			$content = sprintf( '<p class="cb-error">%s</p>', esc_html( $e->getMessage() ) );
		}

		return $content;
	}

	public function html_certificate_item( object $cert, bool $is_course_builder = false ): string {
		$cert_id   = $cert->ID;
		$certModel = CertificatePostModel::find( $cert_id, true );
		$edit_link = $certModel ? $certModel->get_edit_link( $is_course_builder ) : admin_url( 'post.php?post=' . $cert_id . '&action=edit' );

		$html_content = [
			'title'   => sprintf(
				'<h3 class="wap-lesson-title"><a href="%s">%s</a></h3>',
				esc_url( $edit_link ),
				esc_html( $cert->post_title )
			),
			'courses' => sprintf( '<div class="lesson-assigned-courses">%s</div>', $this->get_assigned_courses( $cert_id ) ),
			'date'    => sprintf( '<span class="lesson__date">%s</span>', date( 'm/d/Y', strtotime( $cert->post_date ) ) ),
			'status'  => sprintf( '<span class="lesson-status %1$s">%1$s</span>', esc_attr( $cert->post_status ) ),
		];

		$html_action = [
			'wrapper'                     => '<div class="lesson-action">',
			'edit'                        => sprintf(
				'<div class="lesson-action-editor"><a href="%s" class="btn-edit-certificate lesson-edit-permalink">%s</a></div>',
				esc_url( $edit_link ),
				'<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>'
			),
			'action_expanded_button'      => '<div class="certificate-action-expanded"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg></div>',
			'action_expanded_wrapper'     => '<div style="display:none;" class="certificate-action-expanded__items">',
			'action_expanded_edit'        => sprintf(
				'<a href="%s" class="certificate-action-expanded__edit">%s</a>',
				esc_url( $edit_link ),
				__( 'Edit', 'learnpress-certificates' )
			),
			'action_expanded_duplicate'   => sprintf(
				'<span class="certificate-action-expanded__duplicate" data-popup-title="%s" data-popup-text="%s" data-popup-confirm="%s" data-popup-cancel="%s">%s</span>',
				esc_attr__( 'Are you sure?', 'learnpress-certificates' ),
				esc_attr__( 'Are you sure you want to duplicate this certificate?', 'learnpress-certificates' ),
				esc_attr__( 'Yes', 'learnpress-certificates' ),
				esc_attr__( 'Cancel', 'learnpress-certificates' ),
				esc_html__( 'Duplicate', 'learnpress-certificates' )
			),
			'action_expanded_publish'     => sprintf( '<span class="certificate-action-expanded__publish">%s</span>', __( 'Publish', 'learnpress-certificates' ) ),
			'action_expanded_trash'       => sprintf(
				'<span class="certificate-action-expanded__trash" data-popup-title="%s" data-popup-text="%s" data-popup-confirm="%s" data-popup-cancel="%s">%s</span>',
				esc_attr__( 'Are you sure?', 'learnpress-certificates' ),
				esc_attr__( 'Move this certificate to trash?', 'learnpress-certificates' ),
				esc_attr__( 'Yes', 'learnpress-certificates' ),
				esc_attr__( 'Cancel', 'learnpress-certificates' ),
				esc_html__( 'Trash', 'learnpress-certificates' )
			),
			'action_expanded_delete'      => sprintf(
				'<span class="certificate-action-expanded__delete" data-popup-title="%s" data-popup-text="%s" data-popup-confirm="%s" data-popup-cancel="%s">%s</span>',
				esc_attr__( 'Are you sure?', 'learnpress-certificates' ),
				esc_attr__( 'Are you sure you want to delete this certificate? This action cannot be undone.', 'learnpress-certificates' ),
				esc_attr__( 'Yes', 'learnpress-certificates' ),
				esc_attr__( 'Cancel', 'learnpress-certificates' ),
				esc_html__( 'Delete', 'learnpress-certificates' )
			),
			'action_expanded_wrapper_end' => '</div>',
			'wrapper_end'                 => '</div>',
		];

		$section = [
			'wrapper_li'         => '<li class="lesson">',
			'wrapper_div'        => sprintf( '<div class="lesson-item" data-certificate-id="%s">', $cert_id ),
			'certificate_info'   => Template::combine_components( $html_content ),
			'certificate_action' => Template::combine_components( $html_action ),
			'wrapper_div_end'    => '</div>',
			'wrapper_li_end'     => '</li>',
		];

		return Template::combine_components( $section );
	}

	private function get_assigned_courses( $cert_id ): string {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT p.ID, p.post_title
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE pm.meta_value = %d
			AND p.post_status IN ('publish', 'draft', 'pending', 'private')
			",
			'_lp_cert',
			$cert_id
		);

		$courses = $wpdb->get_results( $query );

		if ( empty( $courses ) ) {
			return sprintf(
				'<span class="lesson-no-courses">%s</span>',
				esc_html__( '--', 'learnpress-certificates' )
			);
		}

		$links = [];
		foreach ( $courses as $course ) {
			$link = CourseBuilder::get_link_course_builder( CourseBuilderTemplate::MENU_COURSES . '/' . $course->ID );

			$links[] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $link ),
				esc_html( $course->post_title )
			);
		}

		return implode( ' | ', $links );
	}
}
