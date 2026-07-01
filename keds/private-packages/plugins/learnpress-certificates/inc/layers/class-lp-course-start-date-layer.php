<?php

class LP_Certificate_Course_Start_Date_Layer extends LP_Certificate_Datetime_Layer {
	public function apply( $data ) {
		if ( ! isset( $data['user_id'] ) || ! isset( $data['course_id'] ) ) {
			return;
		}

		$user                = learn_press_get_user( $data['user_id'] );
		$course_data         = $user->get_course_data( $data['course_id'] );
		$start_time_formated = '';
		if ( $course_data ) {
			$format = get_option( 'date_format' );

			$start_time_formated = wp_date( $format, $course_data->get_start_time()->getTimestamp() );
		}

		$this->options['text'] = ! empty( $start_time_formated ) ? $start_time_formated : $this->options['text'];
	}
}
