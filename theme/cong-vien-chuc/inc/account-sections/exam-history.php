<?php
/**
 * Lịch sử làm bài (Phase 10) - GET /api/my-exam-attempts thật. Pass/fail
 * CHỈ hiện khi `passed` khác null (Phần XVIII - "không tự suy diễn đậu/
 * rớt nếu backend không cung cấp", vd đề practice có thể không tính pass).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page   = isset( $_GET['epage'] ) ? max( 1, absint( $_GET['epage'] ) ) : 1;
$result = ( new CVC_Exam_Attempt_Service() )->history( array( 'per_page' => 15, 'page' => $page ), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$pagination = $result['data']['data'] ?? array();
$attempts   = $pagination['data'] ?? array();

$status_labels = array(
	'in_progress' => 'Đang làm',
	'submitted'   => 'Đã nộp',
	'expired'     => 'Hết giờ',
	'abandoned'   => 'Đã hủy',
);
?>

<section class="cvc-account-section">
	<h2>Các lần làm bài</h2>

	<?php if ( empty( $attempts ) ) : ?>
		<?php cvc_render_empty_state( 'Bạn chưa làm bài thi nào. Hãy chọn 1 đề thi trắc nghiệm để bắt đầu luyện tập.' ); ?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_exams_url() ); ?>">Xem đề thi trắc nghiệm</a></p>
	<?php else : ?>
		<div class="cvc-table-wrap">
			<table class="cvc-table">
				<thead>
					<tr>
						<th scope="col">Đề thi</th>
						<th scope="col">Trạng thái</th>
						<th scope="col">Kết quả</th>
						<th scope="col">Thời gian</th>
						<th scope="col"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $attempts as $attempt ) : ?>
						<?php
						$status     = (string) ( $attempt['status'] ?? '' );
						$percentage = $attempt['percentage'] ?? null;
						$passed     = $attempt['passed'] ?? null;
						$exam       = is_array( $attempt['exam'] ?? null ) ? $attempt['exam'] : array();
						?>
						<tr>
							<td>
								<?php if ( ! empty( $exam['slug'] ) ) : ?>
									<a href="<?php echo esc_url( cvc_exam_url( $exam['slug'] ) ); ?>"><?php echo esc_html( $exam['title'] ?? '' ); ?></a>
								<?php else : ?>
									<?php echo esc_html( $exam['title'] ?? '' ); ?>
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( $status_labels[ $status ] ?? $status ); ?></td>
							<td>
								<?php if ( null !== $percentage ) : ?>
									<?php echo esc_html( sprintf( '%s%%', rtrim( rtrim( number_format( (float) $percentage, 1 ), '0' ), '.' ) ) ); ?>
									<?php if ( null !== $passed ) : ?>
										<?php echo $passed ? '· Đạt' : '· Chưa đạt'; ?>
									<?php endif; ?>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
							<td><?php echo esc_html( cvc_format_date_vn( $attempt['submitted_at'] ?? $attempt['started_at'] ?? null ) ); ?></td>
							<td>
								<?php if ( 'in_progress' === $status ) : ?>
									<a class="cvc-btn cvc-btn--small cvc-btn--primary" href="<?php echo esc_url( cvc_exam_attempt_url( $attempt['id'] ) ); ?>">Tiếp tục</a>
								<?php else : ?>
									<a class="cvc-btn cvc-btn--small cvc-btn--secondary" href="<?php echo esc_url( cvc_exam_attempt_url( $attempt['id'] ) ); ?>">Xem kết quả</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php
		cvc_render_pagination(
			(int) ( $pagination['current_page'] ?? $page ),
			(int) ( $pagination['last_page'] ?? 1 ),
			fn( int $target_page ): string => add_query_arg( 'epage', $target_page, cvc_account_url( 'exam-history' ) )
		);
		?>
	<?php endif; ?>
</section>
