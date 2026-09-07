<?php
/**
 * Gợi ý cho bạn (Phase 10) - GET /api/recommendations thật
 * (RecommendationService, Phase 8, KHÔNG AI - deterministic dựa trên tín
 * hiệu thật: mục tiêu/khóa học đang học/chủ đề yếu). Mỗi item có cùng
 * shape với kết quả tìm kiếm (transformPublicEntity) - TÁI DÙNG NGUYÊN
 * cvc_render_search_result() thay vì viết renderer riêng (Phần Design
 * System "audit và extend, không duplicate").
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result = ( new CVC_Recommendation_Service() )->list( array(), $token );

if ( ! $result['ok'] ) {
	cvc_render_error_state( cvc_api_error_message( $result ) );
	return;
}

$cold_start = ! empty( $result['data']['cold_start'] );
$sections   = $result['data']['data'] ?? array();
?>

<?php if ( $cold_start ) : ?>
	<p class="cvc-page-header__lead">
		Bạn chưa có nhiều hoạt động trên hệ thống, đây là gợi ý khởi đầu dành cho người mới.
	</p>
<?php endif; ?>

<?php if ( empty( $sections ) ) : ?>
	<?php cvc_render_empty_state( 'Chưa có gợi ý nào dành cho bạn. Hãy khám phá khóa học, đề thi hoặc đặt mục tiêu ôn thi để nhận gợi ý phù hợp hơn.' ); ?>
<?php else : ?>
	<?php foreach ( $sections as $section_data ) : ?>
		<section class="cvc-account-section">
			<h2><?php echo esc_html( $section_data['title'] ?? '' ); ?></h2>
			<div class="cvc-card-grid">
				<?php foreach ( ( $section_data['items'] ?? array() ) as $item ) : ?>
					<div class="cvc-recommendation-item" data-cvc-click-track="recommendation_open" data-cvc-target-type="<?php echo esc_attr( $item['type'] ?? '' ); ?>" data-cvc-target-id="<?php echo esc_attr( $item['id'] ?? '' ); ?>">
						<?php if ( ! empty( $item['reason'] ) ) : ?>
							<p class="cvc-recommendation-item__reason"><?php echo esc_html( $item['reason'] ); ?></p>
						<?php endif; ?>
						<?php cvc_render_search_result( $item, 3 ); ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
<?php endif; ?>
