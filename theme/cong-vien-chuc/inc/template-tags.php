<?php
/**
 * Helper render các state dùng chung khi hiển thị dữ liệu từ Laravel API:
 * loading, empty, error. Các trang domain (Courses, Topics, ...) nên dùng
 * lại các hàm này thay vì tự viết markup riêng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_render_loading_state( string $message = 'Đang tải dữ liệu…' ): void {
	printf(
		'<div class="cvc-state cvc-state--loading">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_empty_state( string $message = 'Chưa có dữ liệu.' ): void {
	printf(
		'<div class="cvc-state cvc-state--empty">%s</div>',
		esc_html( $message )
	);
}

function cvc_render_error_state( string $message = 'Không thể tải dữ liệu, vui lòng thử lại sau.' ): void {
	printf(
		'<div class="cvc-state cvc-state--error">%s</div>',
		esc_html( $message )
	);
}
