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
 * Bieu tuong + mau nhan theo loai noi dung.
 *
 * @return array{0: string, 1: string}
 */
function cvc_slot_kind_style( string $kind ): array {
	$map = array(
		'courses'      => array( 'fa-graduation-cap', 'text-amber-300 bg-amber-500/10 border-amber-500/30' ),
		'recruitments' => array( 'fa-briefcase', 'text-emerald-300 bg-emerald-500/10 border-emerald-500/30' ),
		'exams'        => array( 'fa-pen-to-square', 'text-cyan-300 bg-cyan-500/10 border-cyan-500/30' ),
		'legal'        => array( 'fa-scale-balanced', 'text-indigo-300 bg-indigo-500/10 border-indigo-500/30' ),
		'topics'       => array( 'fa-layer-group', 'text-rose-300 bg-rose-500/10 border-rose-500/30' ),
	);

	return $map[ $kind ] ?? $map['exams'];
}

/**
 * The 1 muc (dung chung cho moi loai).
 */
function cvc_render_slot_card( array $item ): void {
	$kind            = (string) ( $item['kind'] ?? '' );
	list( $icon, $tone ) = cvc_slot_kind_style( $kind );
	$meta            = array_filter( array_map( 'strval', (array) ( $item['meta'] ?? array() ) ) );
	?>
	<a href="<?php echo esc_url( cvc_slot_item_url( $item ) ); ?>" class="group bg-navy-950 p-5 rounded-3xl border border-slate-800 hover:border-amber-500/60 transition-colors flex flex-col justify-between gap-4 min-w-0">
		<div class="space-y-3 min-w-0">
			<span class="inline-flex items-center gap-1.5 text-[10px] font-black px-2.5 py-1 rounded-full border <?php echo esc_attr( $tone ); ?>">
				<i class="fa-solid <?php echo esc_attr( $icon ); ?>" aria-hidden="true"></i><?php echo esc_html( (string) ( $item['badge'] ?? '' ) ); ?>
			</span>
			<h3 class="font-extrabold text-sm sm:text-base text-white group-hover:text-amber-300 leading-snug line-clamp-3 break-words"><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></h3>
			<?php if ( $meta ) : ?>
				<p class="text-[11px] text-slate-400 leading-relaxed"><?php echo esc_html( implode( ' · ', $meta ) ); ?></p>
			<?php endif; ?>
		</div>
		<span class="text-xs font-black text-amber-400"><?php echo esc_html( (string) ( $item['cta_label'] ?? 'Xem' ) ); ?> &rarr;</span>
	</a>
	<?php
}

/**
 * Khu (section) day du - dung o trang chu / trang danh sach.
 *
 * @param array<string, mixed> $slot Du lieu tu cvc_content_slot().
 * @param array<string, mixed> $opts id, class, columns (3|4), heading_level.
 */
function cvc_render_content_slot_section( array $slot, array $opts = array() ): void {
	$items = array_values( array_filter( (array) ( $slot['items'] ?? array() ), 'is_array' ) );
	if ( empty( $items ) ) {
		return;
	}
	$kind    = (string) ( $slot['kind'] ?? 'exams' );
	$id      = (string) ( $opts['id'] ?? 'noi-dung-goi-y' );
	$columns = 3 === (int) ( $opts['columns'] ?? 4 ) ? 'lg:grid-cols-3' : 'lg:grid-cols-4';
	$class   = (string) ( $opts['class'] ?? 'py-20 bg-slate-900 border-t border-slate-800' );
	list( , $tone ) = cvc_slot_kind_style( $kind );
	?>
	<section id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>" data-cvc-slot="<?php echo esc_attr( (string) ( $slot['slot'] ?? '' ) ); ?>" data-cvc-slot-kind="<?php echo esc_attr( $kind ); ?>">
		<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-4">
				<div class="space-y-2">
					<span class="inline-block text-xs font-extrabold uppercase tracking-widest px-3.5 py-1.5 rounded-full border <?php echo esc_attr( $tone ); ?>"><?php echo esc_html( (string) ( $slot['eyebrow'] ?? '' ) ); ?></span>
					<h2 class="text-2xl sm:text-4xl font-extrabold text-white tracking-normal"><?php echo esc_html( (string) ( $slot['title'] ?? '' ) ); ?></h2>
					<?php if ( ! empty( $slot['subtitle'] ) ) : ?>
						<p class="text-xs sm:text-sm text-slate-400"><?php echo esc_html( (string) $slot['subtitle'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $slot['cta']['label'] ) ) : ?>
					<a href="<?php echo esc_url( cvc_slot_cta_url( (string) ( $slot['cta']['kind'] ?? $kind ) ) ); ?>" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs rounded-xl transition-all border border-slate-700 inline-flex items-center gap-2 self-start md:self-auto">
						<span><?php echo esc_html( (string) $slot['cta']['label'] ); ?></span><i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
					</a>
				<?php endif; ?>
			</div>
			<div class="grid grid-cols-1 sm:grid-cols-2 <?php echo esc_attr( $columns ); ?> gap-5">
				<?php foreach ( $items as $item ) : ?>
					<?php cvc_render_slot_card( $item ); ?>
				<?php endforeach; ?>
			</div>
		</div>
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
	<section class="bg-navy-950 border border-slate-800 p-5 rounded-3xl space-y-3 shadow-xl text-xs" aria-labelledby="<?php echo esc_attr( $hid ); ?>" data-cvc-slot-kind="<?php echo esc_attr( $kind ); ?>">
		<h2 id="<?php echo esc_attr( $hid ); ?>" class="font-extrabold text-xs text-cyan-400 uppercase tracking-wider border-b border-slate-800 pb-3"><?php echo esc_html( (string) ( $slot['eyebrow'] ?? '' ) ); ?></h2>
		<ul class="space-y-2.5">
			<?php foreach ( $items as $item ) : ?>
				<li>
					<a href="<?php echo esc_url( cvc_slot_item_url( $item ) ); ?>" class="block p-3 bg-slate-900 hover:bg-slate-800 rounded-2xl border border-slate-800 space-y-1.5">
						<span class="block font-bold text-white leading-snug line-clamp-3 break-words"><?php echo esc_html( (string) ( $item['title'] ?? '' ) ); ?></span>
						<span class="block text-slate-400"><?php echo esc_html( implode( ' · ', array_filter( array_map( 'strval', (array) ( $item['meta'] ?? array() ) ) ) ) ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $slot['cta']['label'] ) ) : ?>
			<a href="<?php echo esc_url( cvc_slot_cta_url( (string) ( $slot['cta']['kind'] ?? $kind ) ) ); ?>" class="block text-center text-cyan-300 font-bold"><?php echo esc_html( (string) $slot['cta']['label'] ); ?></a>
		<?php endif; ?>
	</section>
	<?php
}
