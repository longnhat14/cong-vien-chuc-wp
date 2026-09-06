<?php
/**
 * Chi tiết văn bản pháp luật - /van-ban-phap-luat/{slug}/
 *
 * Backend đã makeHidden(['file_path', 'file_hash']) ở
 * LegalDocumentController - response không có 2 field này. Trang này
 * không cố lấy thêm field nào ngoài response, không tự tạo download URL:
 * chỉ dùng source_url nếu API thực sự trả field đó.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$slug = sanitize_text_field( (string) get_query_var( 'cvc_legal_document_slug' ) );

$service = new CVC_Legal_Document_Service();
$result  = $service->find( $slug );

$document = null;
$is_found = false;

if ( $result['ok'] ) {
	$data     = $result['data']['data'] ?? null;
	$document = is_array( $data ) ? $data : null;
	$is_found = null !== $document;
}

if ( ! $is_found ) {
	if ( 404 === (int) $result['status'] ) {
		status_header( 404 );
		cvc_seo_set_noindex();
	} else {
		status_header( 503 );
	}
}

cvc_seo_set_title( $is_found ? (string) $document['title'] : 'Không tìm thấy văn bản pháp luật' );

if ( $is_found ) {
	if ( ! empty( $document['summary'] ) ) {
		cvc_seo_set_description( (string) $document['summary'] );
	}
	cvc_seo_set_canonical( cvc_legal_document_url( $slug ) );
}

get_header();
?>

<main id="main" class="container cvc-page">
	<?php
	cvc_render_breadcrumbs(
		array(
			array(
				'label' => 'Trang chủ',
				'url'   => home_url( '/' ),
			),
			array(
				'label' => 'Văn bản pháp luật',
				'url'   => cvc_legal_documents_url(),
			),
			array( 'label' => $is_found ? (string) $document['title'] : 'Không tìm thấy' ),
		)
	);
	?>

	<?php if ( ! $is_found ) : ?>
		<h1><?php echo esc_html( 404 === (int) $result['status'] ? 'Không tìm thấy văn bản pháp luật' : 'Đã có lỗi xảy ra' ); ?></h1>
		<?php
		if ( 404 === (int) $result['status'] ) {
			cvc_render_notfound_state( 'Văn bản bạn tìm không tồn tại hoặc đã bị gỡ bỏ.' );
		} else {
			cvc_render_error_state();
		}
		?>
		<p><a class="cvc-btn cvc-btn--secondary" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">&larr; Xem tất cả văn bản</a></p>
	<?php else : ?>
		<?php
		$replacedBy        = is_array( $document['replaced_by'] ?? null ) ? $document['replaced_by'] : null;
		$replacedDocuments = is_array( $document['replaced_documents'] ?? null ) ? $document['replaced_documents'] : array();
		?>
		<header class="cvc-page-header">
			<?php if ( ! empty( $document['document_type'] ) ) : ?>
				<span class="cvc-badge cvc-badge--subject"><?php echo esc_html( $document['document_type'] ); ?></span>
			<?php endif; ?>
			<h1><?php echo esc_html( $document['title'] ); ?></h1>
			<p class="cvc-page-header__meta">
				<?php
				$meta_parts = array();
				if ( ! empty( $document['document_number'] ) ) {
					$meta_parts[] = $document['document_number'];
				}
				if ( ! empty( $document['issuing_agency'] ) ) {
					$meta_parts[] = $document['issuing_agency'];
				}
				if ( ! empty( $document['effective_date'] ) ) {
					$meta_parts[] = sprintf( 'Hiệu lực từ %s', cvc_format_date_vn( $document['effective_date'] ) );
				}
				echo esc_html( implode( ' · ', $meta_parts ) );
				?>
			</p>
		</header>

		<?php if ( $replacedBy && ! empty( $replacedBy['slug'] ) ) : ?>
			<div class="cvc-state cvc-state--notfound">
				Văn bản này đã được thay thế bởi
				<a href="<?php echo esc_url( cvc_legal_document_url( $replacedBy['slug'] ) ); ?>"><?php echo esc_html( $replacedBy['title'] ?? '' ); ?></a>.
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $document['summary'] ) ) : ?>
			<div class="cvc-prose"><?php echo nl2br( esc_html( $document['summary'] ) ); ?></div>
		<?php endif; ?>

		<?php if ( ! empty( $document['issued_date'] ) || ! empty( $document['expiry_date'] ) ) : ?>
			<ul class="cvc-related-list">
				<?php if ( ! empty( $document['issued_date'] ) ) : ?>
					<li><?php echo esc_html( sprintf( 'Ngày ban hành: %s', cvc_format_date_vn( $document['issued_date'] ) ) ); ?></li>
				<?php endif; ?>
				<?php if ( ! empty( $document['expiry_date'] ) ) : ?>
					<li><?php echo esc_html( sprintf( 'Hết hiệu lực: %s', cvc_format_date_vn( $document['expiry_date'] ) ) ); ?></li>
				<?php endif; ?>
			</ul>
		<?php endif; ?>

		<?php if ( ! empty( $document['source_url'] ) ) : ?>
			<p><a class="cvc-btn cvc-btn--primary" href="<?php echo esc_url( $document['source_url'] ); ?>" target="_blank" rel="noopener noreferrer">Xem văn bản gốc</a></p>
		<?php endif; ?>

		<?php if ( ! empty( $replacedDocuments ) ) : ?>
			<section class="cvc-related-section">
				<h2>Văn bản bị thay thế</h2>
				<ul class="cvc-related-list">
					<?php foreach ( $replacedDocuments as $old_doc ) : ?>
						<?php if ( empty( $old_doc['slug'] ) ) : continue; endif; ?>
						<li><a href="<?php echo esc_url( cvc_legal_document_url( $old_doc['slug'] ) ); ?>"><?php echo esc_html( $old_doc['title'] ?? '' ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</main>

<?php get_footer(); ?>
