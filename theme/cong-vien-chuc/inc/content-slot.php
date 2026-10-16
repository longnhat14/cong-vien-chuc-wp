<?php
/**
 * Khung noi dung tu lap day: o danh cho khoa hoc / tin tuyen dung chua co du
 * lieu -> backend chon loai noi dung dau tien du dieu kien (khoa hoc -> tuyen
 * dung con han -> de thi thu mien phi -> van ban moi -> kien thuc theo chu de).
 * Khong co gi -> an ca khu (khong hien "chua co du lieu").
 *
 * @package CongVienChuc
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lay du lieu khung (cache 10 phut o WordPress).
 *
 * @param array<int, string> $exclude
 * @param array<int, int>    $exclude_exams
 * @return array<string, mixed>|null null = khong co noi dung de hien.
 */
function cvc_content_slot( string $slot, array $exclude = array(), array $exclude_exams = array(), int $limit = 4 ): ?array {
	sort( $exclude );
	$exclude_exams = array_values( array_unique( array_map( 'intval', $exclude_exams ) ) );
	sort( $exclude_exams );
	$key    = 'cvc_slot_v2_' . md5( wp_json_encode( array( $slot, $exclude, $exclude_exams, $limit ) ) );
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return empty( $cached['kind'] ) ? null : $cached;
	}

	$result = ( new CVC_Homepage_Service() )->contentSlot( $slot, $exclude, $exclude_exams, $limit );
	if ( empty( $result['ok'] ) || ! is_array( $result['data']['data'] ?? null ) ) {
		return null; // Loi API: an khu, khong cache.
	}

	$data = $result['data']['data'];
	set_transient( $key, $data, 10 * MINUTE_IN_SECONDS );

	return empty( $data['kind'] ) ? null : $data;
}

/**
 * Co khoa hoc ban duoc (da co bai giang) hay chua - cache 10 phut.
 */
function cvc_has_sellable_courses(): bool {
	$cached = get_transient( 'cvc_has_sellable_courses' );
	if ( false !== $cached ) {
		return '1' === (string) $cached;
	}
	$result = ( new CVC_Course_Service() )->list( array( 'sellable' => 1, 'per_page' => 1 ) );
	if ( empty( $result['ok'] ) ) {
		return false; // Loi API: coi nhu chua co, khong cache.
	}
	$has = (int) ( $result['data']['data']['total'] ?? 0 ) > 0;
	set_transient( 'cvc_has_sellable_courses', $has ? '1' : '0', 10 * MINUTE_IN_SECONDS );

	return $has;
}

function cvc_slot_item_url( array $item ): string {
	$slug = (string) ( $item['slug'] ?? '' );
	switch ( (string) ( $item['kind'] ?? '' ) ) {
		case 'courses':
			return cvc_course_url( $slug );
		case 'recruitments':
			return cvc_recruitment_url( $slug );
		case 'exams':
			return cvc_exam_url( $slug );
		case 'legal':
			return cvc_legal_document_url( $slug );
		case 'topics':
			return cvc_topic_url( $slug );
	}

	return home_url( '/' );
}

function cvc_slot_cta_url( string $kind ): string {
	switch ( $kind ) {
		case 'courses':
			return cvc_courses_url();
		case 'recruitments':
			return cvc_recruitments_url();
		case 'exams':
			return cvc_exams_url();
		case 'legal':
			return cvc_legal_documents_url();
		case 'topics':
			return cvc_topics_url();
	}

	return home_url( '/' );
}

/**
 * Bieu tuong + nhan theo loai noi dung (giao dien van ban 16/10/2026: chi
 * con 1 tong mau, phan biet bang chu nhan).
 *
 * @return array{0: string, 1: string}
 */
function cvc_slot_kind_style( string $kind ): array {
	$map = array(
		'courses'      => array( 'fa-graduation-cap', '' ),
		'recruitments' => array( 'fa-briefcase', '' ),
		'exams'        => array( 'fa-pen-to-square', '' ),
		'legal'        => array( 'fa-scale-balanced', '' ),
		'topics'       => array( 'fa-layer-group', '' ),
	);

	return $map[ $kind ] ?? $map['exams'];
}

/**
 * 1 muc dang dong muc luc (dung chung cho moi loai).
 */
function cvc_render_slot_card( array $item ): void {
	$meta = array_filter( array_map( 'strval', (array) ( $item['meta'] ?? array() ) ) );
	?>
	<li>
		<h3 class="dz-item-title"><a href="<?php echo esc_url( cvc_slot_item_url( $item ) ); ?>"><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></a></h3>
		<div class="dz-item-meta">
			<?php if ( ! empty( $item['badge'] ) ) : ?><span class="dz-tag"><?php echo esc_html( (string) $item['badge'] ); ?></span><?php endif; ?>
			<?php foreach ( $meta as $m ) : ?><span><?php echo esc_html( $m ); ?></span><?php endforeach; ?>
		</div>
	</li>
	<?php
}

/**
 * Khu (section) day du - dung o trang chu / trang danh sach.
 *
 * @param array<string, mixed> $slot Du lieu tu cvc_content_slot().
 * @param array<string, mixed> $opts id, class.
 */
function cvc_render_content_slot_section( array $slot, array $opts = array() ): void {
	$items = array_values( array_filter( (array) ( $slot['items'] ?? array() ), 'is_array' ) );
	if ( empty( $items ) ) {
		return;
	}
	$kind = (string) ( $slot['kind'] ?? 'exams' );
	$id   = (string) ( $opts['id'] ?? 'noi-dung-goi-y' );
	$hid  = $id . '-title';
	?>
	<section id="<?php echo esc_attr( $id ); ?>" class="dz-section <?php echo esc_attr( (string) ( $opts['class'] ?? '' ) ); ?>" aria-labelledby="<?php echo esc_attr( $hid ); ?>" data-cvc-slot="<?php echo esc_attr( (string) ( $slot['slot'] ?? '' ) ); ?>" data-cvc-slot-kind="<?php echo esc_attr( $kind ); ?>">
		<div class="dz-h2">
			<h2 id="<?php echo esc_attr( $hid ); ?>"><?php echo esc_html( (string) ( $slot['title'] ?? '' ) ); ?></h2>
			<?php if ( ! empty( $slot['cta']['label'] ) ) : ?>
				<a href="<?php echo esc_url( cvc_slot_cta_url( (string) ( $slot['cta']['kind'] ?? $kind ) ) ); ?>"><?php echo esc_html( (string) $slot['cta']['label'] ); ?> →</a>
			<?php endif; ?>
		</div>
		<?php if ( ! empty( $slot['subtitle'] ) ) : ?>
			<p class="dz-muted" style="margin:-4px 0 6px;font-style:italic"><?php echo esc_html( (string) $slot['subtitle'] ); ?></p>
		<?php endif; ?>
		<ul class="dz-list">
			<?php foreach ( $items as $item ) : ?>
				<?php cvc_render_slot_card( $item ); ?>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}

/**
 * Dang gon cho cot ben (sidebar).
 *
 * @param array<string, mixed> $slot
 */
function cvc_render_content_slot_compact( array $slot ): void {
	$items = array_values( array_filter( (array) ( $slot['items'] ?? array() ), 'is_array' ) );
	if ( empty( $items ) ) {
		return;
	}
	$kind = (string) ( $slot['kind'] ?? 'exams' );
	$hid  = 'cvc-slot-' . sanitize_html_class( (string) ( $slot['slot'] ?? 'side' ) );
	?>
	<section class="dz-box" aria-labelledby="<?php echo esc_attr( $hid ); ?>" data-cvc-slot-kind="<?php echo esc_attr( $kind ); ?>">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="dz-box-head"><?php echo esc_html( (string) ( $slot['eyebrow'] ?? ( $slot['title'] ?? '' ) ) ); ?></h2>
		<div class="dz-box-body">
			<ul class="dz-list dz-list-sm">
				<?php foreach ( $items as $item ) : ?>
					<li>
						<a class="dz-sidelink" href="<?php echo esc_url( cvc_slot_item_url( $item ) ); ?>"><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></a>
						<?php $m = array_filter( array_map( 'strval', (array) ( $item['meta'] ?? array() ) ) ); if ( $m ) : ?><div class="dz-item-meta"><?php echo esc_html( implode( ' · ', $m ) ); ?></div><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( ! empty( $slot['cta']['label'] ) ) : ?>
				<p style="margin:10px 0 0"><a class="dz-link" href="<?php echo esc_url( cvc_slot_cta_url( (string) ( $slot['cta']['kind'] ?? $kind ) ) ); ?>"><?php echo esc_html( (string) $slot['cta']['label'] ); ?> →</a></p>
			<?php endif; ?>
		</div>
	</section>
	<?php
}
