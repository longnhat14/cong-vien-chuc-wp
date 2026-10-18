<?php
/**
 * Phase 16 (18/10/2026) — Đợt tuyển dụng độc lập + phân loại tuyển dụng.
 *
 *  - /tuyen-dung/dot/{slug}/  trang đợt (kể cả đợt chỉ có lịch thi / danh sách / kết quả,
 *    chưa thu thập được thông báo mở đăng ký) — API /api/recruitment-rounds/{slug}.
 *  - Dòng thời gian sự kiện của đợt (dùng chung trang tin và trang đợt).
 *  - Danh sách "Đang tổ chức thi / Có kết quả" trên /tuyen-dung/.
 *  - Bảng tra loại tuyển dụng × phương thức (bộ lọc danh sách tin).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function cvc_round_url( string $slug ): string {
	return home_url( '/tuyen-dung/dot/' . rawurlencode( $slug ) . '/' );
}

/**
 * Loại tuyển dụng (employment_types.code) — khớp bảng tra backend.
 *
 * @return array<string, string>
 */
function cvc_employment_type_options(): array {
	return array(
		'civil_servant'        => 'Công chức',
		'public_employee'      => 'Viên chức',
		'contract_admin'       => 'Hợp đồng (cơ quan hành chính)',
		'contract_public_unit' => 'Hợp đồng (đơn vị sự nghiệp)',
	);
}

/**
 * Phương thức tuyển (recruitment_methods.code).
 *
 * @return array<string, string>
 */
function cvc_recruitment_method_options(): array {
	return array(
		'examination'        => 'Thi tuyển',
		'selection'          => 'Xét tuyển',
		'acceptance'         => 'Tiếp nhận',
		'contract_selection' => 'Lựa chọn ký hợp đồng',
	);
}

/** Nhãn + lớp màu của giai đoạn đợt tuyển. */
function cvc_round_stage_tag( string $stage, string $label = '' ): string {
	$map = array(
		'accepting' => array( 'Đang nhận hồ sơ', 'is-ok' ),
		'announced' => array( 'Đã có thông báo', '' ),
		'examining' => array( 'Đang tổ chức thi', 'is-warn' ),
		'result'    => array( 'Đã có kết quả', 'is-warn' ),
		'closed'    => array( 'Đã kết thúc', 'is-off' ),
	);
	$m = $map[ $stage ] ?? array( '' !== $label ? $label : 'Đợt tuyển dụng', '' );

	return '<span class="dz-tag ' . esc_attr( $m[1] ) . '">' . esc_html( '' !== $label ? $label : $m[0] ) . '</span>';
}

/**
 * Dòng thời gian sự kiện của đợt (kế hoạch → thông báo → điều chỉnh → danh sách → lịch thi → kết quả).
 * Chỉ link tới địa chỉ chính thức (API đã lọc `source_url`).
 *
 * @param array<int, array<string, mixed>> $events
 * @param array<int, array<string, mixed>> $legal_basis
 */
function cvc_render_round_timeline( array $events, array $legal_basis = array(), string $heading = 'Văn bản của đợt tuyển', string $anchor = 'van-ban-dot' ): void {
	if ( empty( $events ) ) {
		return;
	}
	usort(
		$events,
		static function ( $a, $b ) {
			$da = (string) ( $a['issued_date'] ?? '' );
			$db = (string) ( $b['issued_date'] ?? '' );
			if ( $da === $db ) {
				return (int) ( $a['id'] ?? 0 ) <=> (int) ( $b['id'] ?? 0 );
			}
			if ( '' === $da ) {
				return 1;
			}
			if ( '' === $db ) {
				return -1;
			}

			return strcmp( $da, $db );
		}
	);
	$phase_labels = array(
		'registration' => 'Đăng ký',
		'exam'         => 'Tổ chức thi',
		'result'       => 'Kết quả',
		'other'        => 'Khác',
	);
	?>
	<section id="<?php echo esc_attr( $anchor ); ?>" class="dz-panel space-y-4">
		<h2 class="dz-sech2"><?php echo esc_html( $heading ); ?></h2>
		<ol class="dz-timeline">
			<?php foreach ( $events as $ev ) : ?>
				<?php
				$type  = (string) ( $ev['type'] ?? 'other' );
				$phase = (string) ( $ev['phase'] ?? 'other' );
				$hot   = in_array( $type, array( 'amendment', 'extension', 'correction', 'cancellation' ), true );
				?>
				<li class="dz-timeline-item is-<?php echo esc_attr( $phase ); ?><?php echo $hot ? ' is-hot' : ''; ?>">
					<div class="dz-timeline-meta">
						<span class="dz-timeline-type"><?php echo esc_html( (string) ( $ev['type_label'] ?? 'Văn bản' ) ); ?></span>
						<?php if ( isset( $phase_labels[ $phase ] ) && 'other' !== $phase ) : ?><span class="dz-muted">· <?php echo esc_html( $phase_labels[ $phase ] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $ev['doc_number'] ) ) : ?><span class="dz-timeline-num">Số <?php echo esc_html( (string) $ev['doc_number'] ); ?></span><?php endif; ?>
						<?php if ( ! empty( $ev['issued_date'] ) ) : ?><span class="dz-muted"><?php echo esc_html( cvc_format_date_vn( (string) $ev['issued_date'] ) ); ?></span><?php endif; ?>
					</div>
					<p class="dz-timeline-title"><?php echo esc_html( (string) ( $ev['title'] ?? '' ) ); ?></p>
					<?php
					$files = (array) ( $ev['files'] ?? array() );
					if ( ! empty( $files ) || ! empty( $ev['source_url'] ) ) :
						?>
						<div class="dz-timeline-links">
							<?php foreach ( $files as $f ) : ?>
								<a href="<?php echo esc_url( (string) ( $f['download_url'] ?? '#' ) ); ?>"><i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i> <?php echo esc_html( wp_trim_words( (string) ( $f['filename'] ?? $f['title'] ?? 'Tệp' ), 8 ) ); ?> <span class="dz-muted"><?php echo esc_html( strtoupper( (string) ( $f['extension'] ?? '' ) ) ); ?></span></a>
							<?php endforeach; ?>
							<?php if ( ! empty( $ev['source_url'] ) ) : ?>
								<a href="<?php echo esc_url( (string) $ev['source_url'] ); ?>" target="_blank" rel="noopener nofollow"><i class="fa-solid fa-up-right-from-square" aria-hidden="true"></i> Trang gốc</a>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
		<?php
		if ( ! empty( $legal_basis ) && function_exists( 'cvc_render_recruitment_legal_basis' ) ) {
			cvc_render_recruitment_legal_basis( $legal_basis );
		}
		?>
	</section>
	<?php
}

/**
 * Danh sách đợt (thẻ ngắn) — dùng trên /tuyen-dung/.
 *
 * @param array<int, array<string, mixed>> $rounds
 */
function cvc_render_round_list( array $rounds ): void {
	if ( empty( $rounds ) ) {
		return;
	}
	?>
	<ul class="dz-list">
		<?php foreach ( $rounds as $r ) : ?>
			<?php
			$last   = (array) ( $r['last_event'] ?? array() );
			$rec    = (array) ( $r['recruitment'] ?? array() );
			$href   = ! empty( $rec['slug'] ) ? cvc_recruitment_url( (string) $rec['slug'] ) : cvc_round_url( (string) ( $r['slug'] ?? '' ) );
			$agency = (string) ( $r['agency']['name'] ?? '' );
			?>
			<li>
				<h3 class="dz-item-title"><a href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( (string) ( $r['title'] ?? '' ) ); ?></a></h3>
				<div class="dz-item-meta">
					<?php echo cvc_round_stage_tag( (string) ( $r['stage'] ?? '' ), (string) ( $r['stage_label'] ?? '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php if ( '' !== $agency ) : ?><span><?php echo esc_html( $agency ); ?></span><?php endif; ?>
					<?php if ( ! empty( $r['province']['name'] ) ) : ?><span><?php echo esc_html( (string) $r['province']['name'] ); ?></span><?php endif; ?>
				</div>
				<?php if ( ! empty( $last ) ) : ?>
					<p class="dz-item-sum">Mới nhất: <strong><?php echo esc_html( (string) ( $last['type_label'] ?? '' ) ); ?></strong><?php echo ! empty( $last['doc_number'] ) ? esc_html( ' số ' . $last['doc_number'] ) : ''; ?><?php echo ! empty( $last['issued_date'] ) ? esc_html( ' ngày ' . cvc_format_date_vn( (string) $last['issued_date'] ) ) : ''; ?> — <?php echo esc_html( wp_trim_words( (string) ( $last['title'] ?? '' ), 22 ) ); ?></p>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Đợt đang tổ chức thi / có kết quả (cache 10 phút).
 *
 * @return array<int, array<string, mixed>>
 */
function cvc_recruitment_rounds_in_progress( int $limit = 10, int $province_id = 0 ): array {
	$key    = 'cvc_rounds_prog_' . $limit . '_' . $province_id;
	$cached = get_transient( $key );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$query = array(
		'stages'   => 'examining,result',
		'per_page' => $limit,
	);
	if ( $province_id > 0 ) {
		$query['province_id'] = $province_id;
	}
	$res  = ( new CVC_Api_Client( null, 10 ) )->get( '/api/recruitment-rounds', $query );
	$rows = $res['ok'] ? (array) ( $res['data']['data']['data'] ?? array() ) : array();
	if ( $res['ok'] ) {
		set_transient( $key, $rows, 10 * MINUTE_IN_SECONDS );
	}

	return $rows;
}

/** Chip "Loại · Phương thức" của 1 vị trí. */
function cvc_position_taxonomy_chip( array $pos ): string {
	$parts = array_filter( array( (string) ( $pos['employment_type_label'] ?? '' ), (string) ( $pos['recruitment_method_label'] ?? '' ) ) );
	if ( empty( $parts ) ) {
		return '';
	}

	return '<span class="dz-tag">' . esc_html( implode( ' · ', $parts ) ) . '</span>';
}
