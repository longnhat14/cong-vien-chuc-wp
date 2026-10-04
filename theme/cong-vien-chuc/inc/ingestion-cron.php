<?php
/**
 * Phase 14 - nhịp chạy dự phòng cho bộ thu thập tin tuyển dụng.
 * Hosting chưa có cron hệ thống gọi `php artisan schedule:run`, nên WP-Cron
 * gọi backend 10 phút/lần (xác thực bằng CVC_PROXY_KEY). Backend tự khoá
 * nên chạy song song với cron hệ thống (nếu sau này bật) không bị trùng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'cron_schedules',
	static function ( array $schedules ): array {
		$schedules['cvc_ten_minutes'] = array(
			'interval' => 600,
			'display'  => 'Mỗi 10 phút (Công Viên Chức)',
		);

		return $schedules;
	}
);

add_action(
	'init',
	static function (): void {
		if ( ! defined( 'CVC_PROXY_KEY' ) || '' === CVC_PROXY_KEY ) {
			return;
		}
		if ( ! wp_next_scheduled( 'cvc_ingestion_tick' ) ) {
			wp_schedule_event( time() + 120, 'cvc_ten_minutes', 'cvc_ingestion_tick' );
		}
	}
);

add_action( 'cvc_ingestion_tick', 'cvc_run_ingestion_tick' );

function cvc_run_ingestion_tick(): void {
	if ( ! defined( 'CVC_PROXY_KEY' ) || '' === CVC_PROXY_KEY ) {
		return;
	}

	$response = wp_remote_post(
		cvc_api_base_url() . '/api/internal/cron/tick',
		array(
			'timeout'  => 110,
			'blocking' => true,
			'headers'  => array(
				'Accept'         => 'application/json',
				'X-CVC-Cron-Key' => CVC_PROXY_KEY,
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		error_log( '[cvc] ingestion tick failed: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
	}
}
