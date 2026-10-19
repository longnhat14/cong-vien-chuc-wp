<?php
/**
 * Quản trị đề cá nhân hóa theo tin tuyển dụng (20/10/2026):
 * công tắc từng tin (Theo mặc định / Luôn bật / Luôn tắt), phạm vi chung theo loại tin, chạy dựng đề ngay.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_post_cvc_admin_personal_exams', 'cvc_admin_handle_personal_exams' );
add_action( 'admin_post_nopriv_cvc_admin_personal_exams', 'cvc_admin_handle_personal_exams' );

function cvc_admin_handle_personal_exams(): void {
	check_admin_referer( 'cvc_admin_personal_exams' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_POST['id'] ?? 0 );
	$op    = sanitize_key( (string) ( $_POST['op'] ?? '' ) );
	$back  = $id ? cvc_admin_url( 'recruitments', $id ) . '#de-ca-nhan-hoa' : cvc_admin_url( 'recruitments' );
	$api   = new CVC_Api_Client( null, 110 );

	if ( 'settings' === $op ) {
		if ( ! cvc_admin_can( 'settings.update' ) ) {
			cvc_admin_redirect( $back, 'error', 'Bạn không có quyền đổi cấu hình chung.' );
			return;
		}
		$scope  = array_values( array_intersect( array( 'public_employee', 'civil_servant', 'other' ), array_map( 'sanitize_key', (array) wp_unslash( $_POST['scope'] ?? array() ) ) ) );
		$budget = isset( $_POST['budget'] ) && '' !== $_POST['budget'] ? absint( $_POST['budget'] ) : null;
		$result = $api->put( '/api/admin/personal-exams/settings', array( 'scope' => $scope, 'budget' => $budget ), $token );
		if ( ! $result['ok'] ) {
			cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
			return;
		}
		cvc_admin_redirect( $back, 'success', 'Đã lưu phạm vi tự tạo đề. Hệ thống áp dụng ở lượt chạy kế tiếp (mỗi giờ).' );
		return;
	}

	if ( ! $id || ! cvc_admin_can( 'recruitment.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền sửa tin tuyển dụng.' );
		return;
	}

	if ( 'mode' === $op ) {
		$mode   = sanitize_key( (string) ( $_POST['mode'] ?? 'inherit' ) );
		$result = $api->put( '/api/admin/recruitments/' . $id . '/personal-exams', array( 'mode' => $mode ), $token );
	} else {
		$result = $api->post( '/api/admin/recruitments/' . $id . '/personal-exams/run', array( 'force' => ! empty( $_POST['force'] ) ), $token );
	}
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}
	$lines = array();
	foreach ( (array) ( $result['data']['data']['report'] ?? array() ) as $row ) {
		$lines[] = trim( ( '-' !== ( $row['group'] ?? '-' ) ? ( $row['label'] ?? $row['group'] ) . ': ' : '' ) . ( $row['message'] ?? $row['status'] ?? '' ) );
	}
	cvc_admin_redirect( $back, 'success', ( 'mode' === $op ? 'Đã đổi công tắc. ' : 'Đã chạy dựng đề. ' ) . implode( ' · ', array_slice( $lines, 0, 4 ) ) );
}
