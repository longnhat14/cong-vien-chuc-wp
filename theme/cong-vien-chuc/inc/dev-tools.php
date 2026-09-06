<?php
/**
 * Dev-only route "/cvc-api-status/" để chứng minh API client + domain
 * service + WordPress rendering pipeline hoạt động đúng.
 *
 * Chỉ bật khi hằng số CVC_DEV_TOOLS = true (khai báo ở wp-config.php cho
 * môi trường local) - không chạy trên production nếu không cấu hình.
 * Không tạo WP Page/nội dung thật.
 *
 * Dùng query string (?cvc-api-status=1) thay vì pretty URL vì route này
 * không phụ thuộc permalink structure hiện tại của site (Apache sẽ 404
 * trước khi tới WordPress nếu dùng path đẹp mà .htaccess/mod_rewrite
 * chưa được flush) - đã xác nhận bằng test thực tế.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'template_redirect', 'cvc_maybe_render_api_status_page' );

function cvc_maybe_render_api_status_page(): void {
	if ( ! defined( 'CVC_DEV_TOOLS' ) || ! CVC_DEV_TOOLS ) {
		return;
	}

	if ( ! isset( $_GET['cvc-api-status'] ) ) {
		return;
	}

	$domains = array(
		'Courses'          => new CVC_Course_Service(),
		'Topics'           => new CVC_Topic_Service(),
		'Knowledge Items'  => new CVC_Knowledge_Service(),
		'Recruitments'     => new CVC_Recruitment_Service(),
		'Exams'            => new CVC_Exam_Service(),
		'Legal Documents'  => new CVC_Legal_Document_Service(),
	);

	$rows = array();

	foreach ( $domains as $label => $service ) {
		$result       = $service->list( array( 'per_page' => 1 ) );
		$rows[ $label ] = array(
			'ok'    => $result['ok'],
			'count' => $result['ok'] ? ( $result['data']['data']['total'] ?? null ) : null,
			'error' => $result['error'],
		);
	}

	add_filter( 'pre_get_document_title', fn() => 'CVC API Status' );

	header( 'Content-Type: text/html; charset=utf-8' );
	?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex, nofollow">
	<?php wp_head(); ?>
</head>
<body class="cvc-dev-tools">
	<main class="container">
		<h1>API Connection Status</h1>
		<p>API base URL: <code><?php echo esc_html( cvc_api_base_url() ); ?></code></p>

		<table class="cvc-status-table">
			<thead>
				<tr>
					<th>Domain</th>
					<th>Status</th>
					<th>Count</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $label => $row ) : ?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td>
						<?php if ( $row['ok'] ) : ?>
							<span class="cvc-status-ok">OK</span>
						<?php else : ?>
							<span class="cvc-status-error">ERROR<?php echo $row['error'] ? ': ' . esc_html( $row['error'] ) : ''; ?></span>
						<?php endif; ?>
					</td>
					<td><?php echo null !== $row['count'] ? esc_html( (string) $row['count'] ) : '—'; ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
	<?php
	exit;
}
