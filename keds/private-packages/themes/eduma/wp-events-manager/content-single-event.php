<?php defined( 'ABSPATH' ) || exit; ?>

<?php $event_id = get_the_ID(); ?>
<article id="tp_event-<?php echo esc_attr( $event_id ); ?>" <?php post_class( 'tp_single_event' ); ?>>

	<?php
	/**
	 * tp_event_before_single_event hook
	 *
	 * @hooked tp_event_show_room_sale_flash - 10
	 * @hooked tp_event_show_room_images - 20
	 */
	do_action( 'tp_event_before_single_event' );
	?>
	<div class="summary entry-summary">
		<?php
		$time_format = get_option( 'time_format' );
		$date_format = get_option( 'date_format' );

		$time_from_meta   = get_post_meta( $event_id, 'tp_event_date_start', true );
		$time_finish_meta = get_post_meta( $event_id, 'tp_event_date_end', true );

		$time_from   = $time_from_meta ? strtotime( $time_from_meta ) : time();
		$time_finish = $time_finish_meta ? strtotime( $time_finish_meta ) : time();

		$time_start = wpems_event_start( $time_format );
		$time_end   = wpems_event_end( $time_format );

		$location        = get_post_meta( $event_id, 'tp_event_location', true );
		$location_iframe = get_post_meta( $event_id, 'tp_event_iframe', true );
		$qty             = get_post_meta( $event_id, 'tp_event_qty', true );

		$event = new WPEMS_Event( $event_id );
		?>
		<div class="tp-event-content">
			<div class="tp-event-content-left">
				<div class="heading-title">
					<h4>
						<?php echo esc_html__( 'Event Information', 'eduma' ); ?>
					</h4>
				</div>
				<div class="tp-event-information">
					<ul>
						<?php if ( $event->get_price() ) : ?>
							<li class="info-price">
								<i class="thim-color edu-dollar-sign"></i>
								<?php echo esc_html__( 'Price', 'eduma' ); ?>
								<span>
									<?php
									$price = wpems_format_price( $event->get_price() );
									echo wp_kses_post( sprintf( '%s <span>%s</span>', esc_html( $price ), esc_html__( 'per participant', 'eduma' ) ) );
									?>
								</span>
							</li>
						<?php endif; ?>

						<?php if ( $location ) : ?>
							<li>
								<i aria-hidden="true" class="thim-color edu-map-marker"></i>
								<?php echo esc_html__( 'Location', 'eduma' ); ?>
								<span><?php echo esc_html( $location ); ?></span>
							</li>
						<?php endif; ?>

						<?php if ( $time_start && $time_from ) : ?>
							<li>
								<i class="thim-color edu-clock-o"></i>
								<?php esc_html_e( 'Start Time', 'eduma' ); ?>
								<span><?php echo esc_html( $time_start ) . '  ' . esc_html( date_i18n( $date_format, $time_from ) ); ?></span>
							</li>

						<?php endif; ?>
						<?php if ( $time_end && $time_finish ) : ?>
							<li>
								<i class="thim-color edu-flag-o"></i><?php esc_html_e( 'Finish Time', 'eduma' ); ?>
								<span><?php echo esc_html( $time_end ) . ' ' . esc_html( date_i18n( $date_format, $time_finish ) ); ?></span>
							</li>
						<?php endif; ?>
						<?php
						if ( $qty ) :
							?>
							<li>
								<i aria-hidden="true" class="tk tk-user"></i>
								<?php echo esc_html__( 'Capacity', 'eduma' ); ?>
								<span><?php echo sprintf( esc_html__( 'Limited to %s people', 'eduma' ), number_format_i18n( intval( $qty ) ) ); ?></span>
							</li>
						<?php endif; ?>
					</ul>
				</div>
				<?php
				/**
				 * tp_event_single_event_content hook
				 */
				do_action( 'tp_event_single_event_content' );

				?>
				<?php if ( $location_iframe ) : ?>
					<div class="tp-event-iframe">
						<div class="heading-title">
							<h4><?php echo esc_html__( 'Location', 'eduma' ); ?></h4>
						</div>
						<div>
							<?php echo wp_kses_post( $location_iframe ); ?>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( thim_plugin_active( 'thim-our-team/init.php' ) ) { ?>
					<?php $members = array_map( 'absint', (array) get_post_meta( $event_id, 'thim_event_members', true ) ); ?>
					<?php
					if ( ! empty( $members ) ) :
						$team = new WP_Query(
							array(
								'post_type'           => 'our_team',
								'post_status'         => 'publish',
								'ignore_sticky_posts' => true,
								'posts_per_page'      => - 1,
								'post__in'            => $members,
							)
						);
						?>
						<?php if ( $team->have_posts() ) : ?>
						<div class="tp-event-organizers">
							<h3 class="title"><?php esc_html_e( 'Event Participants', 'eduma' ); ?></h3>
							<div class="thim-carousel-container">
								<div class="thim-carousel-wrapper" data-visible="4" data-navigation="1">
									<?php
									while ( $team->have_posts() ) :
										$team->the_post();
										?>
										<div class="item">
											<div
												class="thumbnail"><?php echo wp_kses_post( thim_get_feature_image( get_post_thumbnail_id( get_the_ID() ), 'full', 110, 110 ) ); ?></div>
											<a class="name"
												href="<?php echo esc_url( get_the_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>

											<p class="regency"><?php echo esc_html( get_post_meta( get_the_ID(), 'regency', true ) ); ?></p>
										</div>
									<?php endwhile; ?>
									<?php wp_reset_postdata(); ?>
								</div>
							</div>
						</div>
					<?php endif; ?>

					<?php endif; // end if have posts ?>

				<?php } // end if plugin active ?>

				<div class="tp-event-single-share">
					<?php do_action( 'thim_social_share' ); ?>
				</div>
				<?php
				/**
				 * tp_event_after_single_event hook
				 */
				do_action( 'tp_event_after_single_event' );

				if ( get_theme_mod( 'thim_event_single_info_author', false ) ) {

					?>
					<div class="heading-title">
						<h4>
							<?php echo esc_html__( 'About the Instructor', 'eduma' ); ?>
						</h4>
					</div>
					<?php do_action( 'thim_about_author' ); ?>

					<?php
				}
				// If comments are open or we have at least one comment, load up the comment template
				if ( comments_open() || intval( get_comments_number() ) > 0 ) :
					comments_template();
				endif;

				?>

			</div>
		</div>
	</div><!-- .summary -->
</article>
