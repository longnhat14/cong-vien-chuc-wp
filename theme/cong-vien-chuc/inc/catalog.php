<?php
/**
 * Danh mục Ôn thi (18/10/2026) - /thi-trac-nghiem/, /chu-de/, /kien-thuc/ theo thiết kế exam-catalog-v1.
 *
 *  - Dữ liệu thật từ /api/catalog/{stats,exams,topics,knowledge} (Bearer tùy chọn: đã lưu, đã làm, độ chính xác, gợi ý theo mục tiêu).
 *  - Trang đầu render phía server (SEO, chạy được khi tắt JS: bộ lọc là form GET).
 *  - AJAX `cvc_cat_view` (công khai) trả HTML danh sách khi lọc / sắp xếp / phân trang.
 *  - AJAX `cvc_cat_save` (nonce + đăng nhập) lưu / bỏ lưu đề thi, chủ đề, mục kiến thức.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const CVC_CAT_KINDS  = array( 'exams', 'topics', 'knowledge' );
const CVC_CAT_GROUPS = array( 'general', 'admin', 'education', 'health', 'agri_forest', 'finance', 'justice', 'culture', 'it', 'science', 'other' );

function cvc_cat_config( string $kind ): array {
	$all = array(
		'exams'     => array(
			'url'       => 'cvc_exams_url',
			'crumb'     => 'Đề thi thử miễn phí',
			'eyebrow'   => 'Trung tâm luyện thi thông minh',
			'h1'        => 'Chinh phục kỳ thi của bạn',
			'lead'      => 'Khám phá bộ đề theo nhóm kiến thức, theo dõi tiến độ và chọn đúng nội dung cần luyện tập tiếp theo.',
			'find'      => 'Tìm đề thi phù hợp',
			'saved'     => 'Đề thi đã lưu',
			'title'     => 'Danh sách đề thi',
			'unit'      => 'đề',
			'filter_h'  => 'Bộ lọc đề thi',
			'search_l'  => 'Tìm tên đề, văn bản, mã đề',
			'search_p'  => 'VD: Nghị định 170/2025…',
			'sorts'     => array( 'recommended' => 'Đề xuất', 'newest' => 'Mới cập nhật', 'title' => 'Tên đề A–Z', 'duration' => 'Thời lượng tăng dần', 'questions' => 'Số câu nhiều nhất' ),
			'save_type' => 'exam',
			'empty'     => 'Chưa tìm thấy đề thi phù hợp',
		),
		'topics'    => array(
			'url'       => 'cvc_topics_url',
			'crumb'     => 'Chủ đề ôn thi',
			'eyebrow'   => 'Ôn thi theo chủ đề',
			'h1'        => 'Ôn chắc từng chủ đề',
			'lead'      => 'Mỗi chủ đề gom mục kiến thức trọng tâm và câu hỏi luyện tập từ cùng một văn bản hoặc môn thi, để bạn ôn trọn một mảng trước khi chuyển sang mảng khác.',
			'find'      => 'Tìm chủ đề phù hợp',
			'saved'     => 'Chủ đề đã lưu',
			'title'     => 'Danh sách chủ đề',
			'unit'      => 'chủ đề',
			'filter_h'  => 'Bộ lọc chủ đề',
			'search_l'  => 'Tìm tên chủ đề, số hiệu văn bản',
			'search_p'  => 'VD: Luật Viên chức…',
			'sorts'     => array( 'recommended' => 'Đề xuất', 'name' => 'Tên chủ đề A–Z', 'knowledge' => 'Nhiều mục kiến thức', 'questions' => 'Nhiều câu hỏi' ),
			'save_type' => 'topic',
			'empty'     => 'Chưa tìm thấy chủ đề phù hợp',
		),
		'knowledge' => array(
			'url'       => 'cvc_knowledge_url',
			'crumb'     => 'Kiến thức trọng tâm',
			'eyebrow'   => 'Kiến thức trọng tâm',
			'h1'        => 'Nắm chắc điều khoản cần thi',
			'lead'      => 'Tóm tắt từng điều, khoản quan trọng của văn bản pháp luật gốc, kèm câu hỏi luyện tập để kiểm tra lại ngay sau khi đọc.',
			'find'      => 'Tìm kiến thức',
			'saved'     => 'Mục đã lưu',
			'title'     => 'Danh sách kiến thức trọng tâm',
			'unit'      => 'mục',
			'filter_h'  => 'Bộ lọc kiến thức',
			'search_l'  => 'Tìm điều khoản, số hiệu, tên văn bản',
			'search_p'  => 'VD: Điều 1 Nghị định 93/2026…',
			'sorts'     => array( 'recommended' => 'Đề xuất', 'newest' => 'Văn bản mới nhất', 'title' => 'Tiêu đề A–Z' ),
			'save_type' => 'knowledge_item',
			'empty'     => 'Chưa tìm thấy mục kiến thức phù hợp',
		),
	);

	return $all[ $kind ] ?? $all['exams'];
}

function cvc_cat_exam_types(): array {
	return array(
		'practice' => 'Luyện tập theo văn bản',
		'mock'     => 'Đề thi thử theo vị trí',
		'official' => 'Bộ đề ôn thi tổng hợp',
	);
}

function cvc_cat_doc_kinds(): array {
	return array(
		'luat'       => 'Luật, Hiến pháp, Nghị quyết QH',
		'nghi_dinh'  => 'Nghị định',
		'thong_tu'   => 'Thông tư',
		'quyet_dinh' => 'Quyết định',
		'nghi_quyet' => 'Nghị quyết khác',
	);
}

/* ------------------------------------------------------------------
 * Bộ lọc
 * ------------------------------------------------------------------ */
function cvc_cat_filters( string $kind, array $src ): array {
	$s   = static fn ( string $k ): string => isset( $src[ $k ] ) && ! is_array( $src[ $k ] ) ? sanitize_text_field( wp_unslash( (string) $src[ $k ] ) ) : '';
	$b   = static fn ( string $k ): bool => in_array( $s( $k ), array( '1', 'true', 'on' ), true );
	$cfg = cvc_cat_config( $kind );
	$f   = array(
		'q'     => mb_substr( trim( $s( 'q' ) ), 0, 100 ),
		'group' => in_array( $s( 'group' ), CVC_CAT_GROUPS, true ) ? $s( 'group' ) : '',
		'sort'  => isset( $cfg['sorts'][ $s( 'sort' ) ] ) ? $s( 'sort' ) : 'recommended',
		'saved' => $b( 'saved' ),
	);
	if ( 'exams' === $kind ) {
		$types = isset( $src['type'] ) ? (array) wp_unslash( $src['type'] ) : array();
		$types = array_values( array_intersect( array_keys( cvc_cat_exam_types() ), array_map( 'strval', $types ) ) );
		$f    += array(
			'type'        => $types,
			'max_minutes' => in_array( (int) $s( 'max_minutes' ), array( 30, 40, 60, 90 ), true ) ? (int) $s( 'max_minutes' ) : 0,
			'not_done'    => $b( 'not_done' ),
		);
	} elseif ( 'topics' === $kind ) {
		$f += array(
			'subject_id' => absint( $s( 'subject_id' ) ),
			'weak'       => $b( 'weak' ),
		);
	} else {
		$f += array(
			'doc_kind' => isset( cvc_cat_doc_kinds()[ $s( 'doc_kind' ) ] ) ? $s( 'doc_kind' ) : '',
			'practice' => $b( 'practice' ),
		);
	}

	return $f;
}

/** Tham số URL / API (bỏ giá trị mặc định). */
function cvc_cat_args( array $f ): array {
	$out = array();
	foreach ( $f as $k => $v ) {
		if ( 'sort' === $k && 'recommended' === $v ) {
			continue;
		}
		if ( is_bool( $v ) ) {
			if ( $v ) {
				$out[ $k ] = 1;
			}
		} elseif ( is_array( $v ) ) {
			if ( $v ) {
				$out[ $k ] = array_values( $v );
			}
		} elseif ( '' !== (string) $v && 0 !== $v ) {
			$out[ $k ] = $v;
		}
	}

	return $out;
}

function cvc_cat_filter_count( array $f ): int {
	$n = 0;
	foreach ( cvc_cat_args( $f ) as $k => $v ) {
		if ( 'sort' !== $k ) {
			$n += is_array( $v ) ? count( $v ) : 1;
		}
	}

	return $n;
}

function cvc_cat_url( string $kind, array $f, int $page = 1 ): string {
	$base = call_user_func( cvc_cat_config( $kind )['url'], max( 1, $page ) );
	$args = cvc_cat_args( $f );

	return $args ? $base . '?' . http_build_query( $args, '', '&' ) : $base;
}

function cvc_cat_fetch( string $kind, array $f, int $page, ?string $token ): array {
	$q = cvc_cat_args( $f ) + array( 'page' => $page, 'per_page' => 10 );

	return cvc_ci_api( '/api/catalog/' . $kind, $q, $token, 15, 120 );
}

/* ------------------------------------------------------------------
 * Tiện ích hiển thị
 * ------------------------------------------------------------------ */
function cvc_cat_n( $v ): string {
	return null === $v ? '—' : number_format( (int) $v, 0, ',', '.' );
}

function cvc_cat_date( ?string $iso ): string {
	$t = $iso ? strtotime( $iso ) : false;

	return $t ? gmdate( 'd/m/Y', $t ) : '';
}

function cvc_cat_legal_line( ?array $d ): string {
	if ( ! $d || empty( $d['url'] ) ) {
		return '';
	}
	$label = (string) ( $d['number'] ?? '' ) ?: wp_trim_words( (string) ( $d['title'] ?? '' ), 8 );
	$cls   = 'in_force' === ( $d['effect'] ?? '' ) ? 'is-ok' : ( in_array( $d['effect'] ?? '', array( 'expired', 'replaced' ), true ) ? 'is-warn' : '' );

	return '<a class="ec-doc ' . esc_attr( $cls ) . '" href="' . esc_url( home_url( (string) $d['url'] ) ) . '">Căn cứ: ' . esc_html( $label ) . '</a>'
		. ( ! empty( $d['effect_label'] ) ? ' <span class="ec-effect">' . esc_html( (string) $d['effect_label'] ) . '</span>' : '' );
}

function cvc_cat_save_btn( string $type, int $id, bool $saved, string $unit ): string {
	return sprintf(
		'<button type="button" class="ec-save%1$s" data-ec-save="%2$d" data-type="%3$s" aria-pressed="%4$s" title="%5$s">%6$s</button>',
		$saved ? ' is-saved' : '',
		$id,
		esc_attr( $type ),
		$saved ? 'true' : 'false',
		esc_attr( $saved ? 'Bỏ lưu ' . $unit : 'Lưu ' . $unit . ' vào tài khoản' ),
		$saved ? '♥ Đã lưu' : '♡ Lưu ' . esc_html( $unit )
	);
}

/* ------------------------------------------------------------------
 * Thẻ nội dung
 * ------------------------------------------------------------------ */
function cvc_cat_card_exam( array $e, int $no ): void {
	$a     = is_array( $e['attempt'] ?? null ) ? $e['attempt'] : null;
	$done  = $a && (int) $a['done'] > 0;
	$open  = $a && ! empty( $a['in_progress'] );
	$url   = cvc_exam_url( (string) ( $e['slug'] ?? '' ) );
	$label = $open ? 'Làm tiếp' : ( $done ? 'Làm lại' : 'Làm bài' );
	$desc  = trim( (string) ( $e['description'] ?? '' ) );
	$doc   = cvc_cat_legal_line( is_array( $e['legal_document'] ?? null ) ? $e['legal_document'] : null );
	?>
	<article class="ec-item<?php echo $done ? ' is-done' : ''; ?>">
		<div class="ec-num"><?php echo esc_html( str_pad( (string) $no, 2, '0', STR_PAD_LEFT ) ); ?></div>
		<div class="ec-body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) ( $e['title'] ?? '' ) ); ?></a></h3>
			<div class="ec-badges">
				<?php if ( ! empty( $e['type_label'] ) ) : ?><span class="ec-badge is-t"><?php echo esc_html( (string) $e['type_label'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $e['job_family_label'] ) ) : ?><span class="ec-badge"><?php echo esc_html( (string) $e['job_family_label'] ); ?></span><?php endif; ?>
				<?php if ( $open ) : ?><span class="ec-badge is-b">Đang làm dở</span><?php elseif ( $done ) : ?><span class="ec-badge is-g">Đã làm</span><?php endif; ?>
			</div>
			<div class="ec-meta">
				<?php if ( ! empty( $e['code'] ) ) : ?><span>⌁ Mã đề: <?php echo esc_html( (string) $e['code'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $e['total_questions'] ) ) : ?><span>▤ <?php echo (int) $e['total_questions']; ?> câu</span><?php endif; ?>
				<?php if ( ! empty( $e['duration_minutes'] ) ) : ?><span>◷ <?php echo (int) $e['duration_minutes']; ?> phút</span><?php endif; ?>
				<?php if ( $a && null !== ( $a['best'] ?? null ) ) : ?><span class="is-score">✓ Điểm cao nhất: <?php echo (int) $a['best']; ?>%</span><?php endif; ?>
			</div>
			<?php if ( '' !== $desc ) : ?><p class="ec-desc"><?php echo esc_html( wp_trim_words( $desc, 32 ) ); ?></p><?php endif; ?>
			<div class="ec-bottom">
				<span><?php echo '' !== $doc ? $doc : esc_html( 'Chấm điểm ngay khi nộp · xem giải thích từng câu' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span><?php echo $done ? esc_html( 'Đã hoàn thành ' . (int) $a['done'] . ' lần' ) : ( $open ? 'Có bài làm chưa nộp' : 'Sẵn sàng bắt đầu' ); ?></span>
			</div>
		</div>
		<div class="ec-actions">
			<a class="ec-btn is-primary" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php echo cvc_cat_save_btn( 'exam', (int) $e['id'], ! empty( $e['saved'] ), 'đề' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<button type="button" class="ec-attach" data-ec-attach="<?php echo (int) $e['id']; ?>" title="Thêm đề này vào lộ trình của Hồ sơ chinh phục kỳ thi">⊕ Gắn mục tiêu</button>
		</div>
	</article>
	<?php
}

function cvc_cat_card_topic( array $t, int $no ): void {
	$p    = is_array( $t['progress'] ?? null ) ? $t['progress'] : null;
	$weak = $p && ! empty( $p['weak'] );
	$url  = cvc_topic_url( (string) ( $t['slug'] ?? '' ) );
	$pe   = is_array( $t['practice_exam'] ?? null ) ? $t['practice_exam'] : null;
	$desc = trim( (string) ( $t['description'] ?? '' ) );
	$doc  = cvc_cat_legal_line( is_array( $t['legal_document'] ?? null ) ? $t['legal_document'] : null );
	?>
	<article class="ec-item<?php echo $weak ? ' is-weak' : ''; ?>">
		<div class="ec-num"><?php echo esc_html( str_pad( (string) $no, 2, '0', STR_PAD_LEFT ) ); ?></div>
		<div class="ec-body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) ( $t['name'] ?? '' ) ); ?></a></h3>
			<div class="ec-badges">
				<?php if ( ! empty( $t['group_label'] ) ) : ?><span class="ec-badge is-t"><?php echo esc_html( (string) $t['group_label'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $t['subject']['name'] ) ) : ?><span class="ec-badge"><?php echo esc_html( (string) $t['subject']['name'] ); ?></span><?php endif; ?>
				<?php if ( $weak ) : ?><span class="ec-badge is-r">Cần ôn lại</span><?php elseif ( $p ) : ?><span class="ec-badge is-g">Đã luyện</span><?php endif; ?>
			</div>
			<div class="ec-meta">
				<span>☰ <?php echo (int) ( $t['knowledge_count'] ?? 0 ); ?> mục kiến thức</span>
				<span>▤ <?php echo (int) ( $t['question_count'] ?? 0 ); ?> câu hỏi</span>
				<?php if ( $pe ) : ?><span>◷ Đề luyện <?php echo (int) $pe['total_questions']; ?> câu<?php echo ! empty( $pe['duration_minutes'] ) ? ' · ' . (int) $pe['duration_minutes'] . ' phút' : ''; ?></span><?php endif; ?>
				<?php if ( $p ) : ?><span class="is-score">✓ Đúng <?php echo (int) $p['accuracy']; ?>% · <?php echo (int) $p['answered']; ?> lượt trả lời</span><?php endif; ?>
			</div>
			<?php if ( '' !== $desc ) : ?><p class="ec-desc"><?php echo esc_html( wp_trim_words( $desc, 32 ) ); ?></p><?php endif; ?>
			<?php if ( $p ) : ?><div class="ec-acc" aria-label="Tỷ lệ đúng <?php echo (int) $p['accuracy']; ?>%"><span style="width:<?php echo (int) max( 2, min( 100, $p['accuracy'] ) ); ?>%"></span></div><?php endif; ?>
			<div class="ec-bottom">
				<span><?php echo '' !== $doc ? $doc : esc_html( 'Ôn theo môn thi' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<?php if ( $pe ) : ?><a href="<?php echo esc_url( cvc_exam_url( (string) $pe['slug'] ) ); ?>">Làm đề luyện tập →</a><?php endif; ?>
			</div>
		</div>
		<div class="ec-actions">
			<a class="ec-btn is-primary" href="<?php echo esc_url( $url ); ?>"><?php echo $p ? 'Ôn tiếp' : 'Ôn chủ đề'; ?></a>
			<?php echo cvc_cat_save_btn( 'topic', (int) $t['id'], ! empty( $t['saved'] ), 'chủ đề' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</article>
	<?php
}

function cvc_cat_card_knowledge( array $k, int $no ): void {
	$url  = cvc_knowledge_item_url( (string) ( $k['slug'] ?? '' ) );
	$pe   = is_array( $k['practice_exam'] ?? null ) ? $k['practice_exam'] : null;
	$sum  = trim( (string) ( $k['summary'] ?? '' ) );
	$d    = is_array( $k['legal_document'] ?? null ) ? $k['legal_document'] : null;
	$doc  = cvc_cat_legal_line( $d );
	$tp   = is_array( $k['topic'] ?? null ) ? $k['topic'] : null;
	?>
	<article class="ec-item">
		<div class="ec-num"><?php echo esc_html( str_pad( (string) $no, 2, '0', STR_PAD_LEFT ) ); ?></div>
		<div class="ec-body">
			<h3><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) ( $k['title'] ?? '' ) ); ?><?php echo $d && ! empty( $d['number'] ) ? ' <small>· ' . esc_html( (string) $d['number'] ) . '</small>' : ''; ?></a></h3>
			<div class="ec-badges">
				<?php if ( ! empty( $k['group_label'] ) ) : ?><span class="ec-badge is-t"><?php echo esc_html( (string) $k['group_label'] ); ?></span><?php endif; ?>
				<?php if ( $tp ) : ?><a class="ec-badge" href="<?php echo esc_url( cvc_topic_url( (string) $tp['slug'] ) ); ?>"><?php echo esc_html( wp_trim_words( (string) $tp['name'], 9 ) ); ?></a><?php endif; ?>
			</div>
			<div class="ec-meta">
				<?php if ( ! empty( $k['question_count'] ) ) : ?><span>▤ <?php echo (int) $k['question_count']; ?> câu hỏi gắn với mục này</span><?php endif; ?>
				<?php if ( $d && ! empty( $d['issued_date'] ) ) : ?><span>◷ Ban hành <?php echo esc_html( cvc_cat_date( (string) $d['issued_date'] ) ); ?></span><?php endif; ?>
				<?php if ( $pe ) : ?><span>✓ Có đề luyện <?php echo (int) $pe['total_questions']; ?> câu</span><?php endif; ?>
			</div>
			<?php if ( '' !== $sum ) : ?><p class="ec-desc"><?php echo esc_html( wp_trim_words( $sum, 40 ) ); ?></p><?php endif; ?>
			<div class="ec-bottom">
				<span><?php echo '' !== $doc ? $doc : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<?php if ( $pe ) : ?><a href="<?php echo esc_url( cvc_exam_url( (string) $pe['slug'] ) ); ?>">Luyện đề văn bản này →</a><?php endif; ?>
			</div>
		</div>
		<div class="ec-actions">
			<a class="ec-btn is-primary" href="<?php echo esc_url( $url ); ?>">Đọc &amp; ôn</a>
			<?php echo cvc_cat_save_btn( 'knowledge_item', (int) $k['id'], ! empty( $k['saved'] ), 'mục' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</article>
	<?php
}

/* ------------------------------------------------------------------
 * Vùng kết quả (render lại bằng AJAX)
 * ------------------------------------------------------------------ */
function cvc_cat_chips( string $kind, array $f, array $st ): array {
	$chips = array();
	if ( '' !== $f['q'] ) {
		$chips[] = array( 'q', '⌕ “' . $f['q'] . '”' );
	}
	if ( 'exams' === $kind ) {
		foreach ( $f['type'] as $t ) {
			$chips[] = array( 'type:' . $t, cvc_cat_exam_types()[ $t ] ?? $t );
		}
		if ( $f['max_minutes'] ) {
			$chips[] = array( 'max_minutes', '◷ Tối đa ' . $f['max_minutes'] . ' phút' );
		}
		if ( $f['not_done'] ) {
			$chips[] = array( 'not_done', 'Chưa làm lần nào' );
		}
	}
	if ( '' !== $f['group'] ) {
		$lbl = 'general' === $f['group'] ? 'Kiến thức chung' : ( cvc_ci_families()[ $f['group'] ] ?? $f['group'] );
		$chips[] = array( 'group', $lbl );
	}
	if ( 'topics' === $kind ) {
		if ( $f['subject_id'] ) {
			$name = 'Môn #' . $f['subject_id'];
			foreach ( (array) ( $st['subjects'] ?? array() ) as $s ) {
				if ( (int) $s['id'] === $f['subject_id'] ) {
					$name = (string) $s['name'];
				}
			}
			$chips[] = array( 'subject_id', $name );
		}
		if ( $f['weak'] ) {
			$chips[] = array( 'weak', 'Chủ đề cần ôn lại' );
		}
	}
	if ( 'knowledge' === $kind ) {
		if ( '' !== $f['doc_kind'] ) {
			$chips[] = array( 'doc_kind', cvc_cat_doc_kinds()[ $f['doc_kind'] ] );
		}
		if ( $f['practice'] ) {
			$chips[] = array( 'practice', 'Có đề luyện tập' );
		}
	}
	if ( $f['saved'] ) {
		$chips[] = array( 'saved', '♥ Đã lưu' );
	}

	return $chips;
}

function cvc_cat_pages( int $cur, int $last ): array {
	$out = array();
	for ( $i = 1; $i <= $last; $i++ ) {
		if ( 1 === $i || $last === $i || abs( $i - $cur ) <= 1 ) {
			$out[] = $i;
		} elseif ( end( $out ) !== 0 ) {
			$out[] = 0;
		}
	}

	return $out;
}

function cvc_cat_render_results( string $kind, array $res, array $f, int $page, array $st, bool $logged_in ): void {
	$cfg   = cvc_cat_config( $kind );
	$data  = $res['ok'] ? (array) ( $res['data']['data'] ?? array() ) : array();
	$items = (array) ( $data['items'] ?? array() );
	$pg    = (array) ( $data['pagination'] ?? array() );
	$total = (int) ( $pg['total'] ?? 0 );
	$last  = max( 1, (int) ( $pg['last_page'] ?? 1 ) );
	$cur   = min( max( 1, (int) ( $pg['page'] ?? $page ) ), $last );
	$from  = (int) ( $pg['from'] ?? 0 );
	$to    = (int) ( $pg['to'] ?? 0 );
	$chips = cvc_cat_chips( $kind, $f, $st );
	?>
	<div class="ec-toolbar">
		<div>
			<h2 class="ec-title"><?php echo '' !== $f['q'] ? 'Kết quả cho “' . esc_html( $f['q'] ) . '”' : esc_html( $cfg['title'] ); ?></h2>
			<div class="ec-sub" data-ec-info><?php
			if ( $res['ok'] ) {
				echo $total > 0 ? esc_html( sprintf( 'Hiển thị %s–%s trong %s %s phù hợp', cvc_cat_n( $from ), cvc_cat_n( $to ), cvc_cat_n( $total ), $cfg['unit'] ) ) : esc_html( 'Không có ' . $cfg['unit'] . ' nào khớp bộ lọc' );
			}
			?></div>
		</div>
		<div class="ec-tools">
			<label class="ec-sr" for="ec-sort">Sắp xếp</label>
			<select id="ec-sort" name="sort" form="ec-form" data-ec-sort>
				<?php foreach ( $cfg['sorts'] as $k => $lbl ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"<?php selected( $f['sort'], $k ); ?>>Sắp xếp: <?php echo esc_html( $lbl ); ?></option>
				<?php endforeach; ?>
			</select>
			<div class="ec-views" role="group" aria-label="Kiểu hiển thị">
				<button type="button" data-view="list" class="on" title="Danh sách" aria-label="Danh sách">☷</button>
				<button type="button" data-view="compact" title="Gọn" aria-label="Gọn">☰</button>
				<button type="button" data-view="grid" title="Thẻ" aria-label="Thẻ">▦</button>
			</div>
		</div>
	</div>
	<div class="ec-chips">
		<?php if ( $chips ) : ?>
			<?php foreach ( $chips as $c ) : ?>
				<button type="button" class="ec-chip is-on" data-ec-clear="<?php echo esc_attr( $c[0] ); ?>" title="Bỏ bộ lọc này"><?php echo esc_html( $c[1] ); ?> <b aria-hidden="true">×</b></button>
			<?php endforeach; ?>
			<button type="button" class="ec-chip is-reset" data-ec-reset>↺ Xóa tất cả</button>
		<?php elseif ( 'exams' === $kind ) : ?>
			<span class="ec-chip">✓ <b>Chấm điểm ngay khi nộp</b></span><span class="ec-chip">◷ Có đồng hồ đếm giờ</span><span class="ec-chip">↗ Phân tích điểm yếu sau bài thi</span>
		<?php elseif ( 'topics' === $kind ) : ?>
			<span class="ec-chip">✓ <b>Gắn văn bản gốc</b></span><span class="ec-chip">☰ Kiến thức + câu hỏi cùng chỗ</span><span class="ec-chip">↗ Theo dõi tỷ lệ đúng</span>
		<?php else : ?>
			<span class="ec-chip">✓ <b>Trích từ văn bản gốc</b></span><span class="ec-chip">◷ Đọc nhanh từng điều</span><span class="ec-chip">▤ Có câu hỏi kiểm tra</span>
		<?php endif; ?>
	</div>
	<?php if ( ! $res['ok'] ) : ?>
		<?php if ( 401 === (int) ( $res['status'] ?? 0 ) ) : ?>
			<div class="ec-empty"><span aria-hidden="true">♙</span><b>Đăng nhập để dùng bộ lọc này</b>Bộ lọc “đã lưu / chưa làm / cần ôn lại” dựa trên tài khoản của bạn.<br><a class="ec-btn is-primary" href="<?php echo esc_url( cvc_login_url( cvc_cat_url( $kind, $f ) ) ); ?>">Đăng nhập</a> <button type="button" class="ec-btn" data-ec-reset>Xóa bộ lọc</button></div>
		<?php else : ?>
			<div class="ec-empty"><span aria-hidden="true">!</span><b>Không tải được danh sách</b>Máy chủ đang bận, vui lòng thử lại sau ít phút.<br><a class="ec-btn" href="<?php echo esc_url( cvc_cat_url( $kind, $f, $page ) ); ?>">Tải lại</a></div>
		<?php endif; ?>
		<?php
		return;
	endif;
	?>
	<?php if ( ! $items ) : ?>
		<div class="ec-empty"><span aria-hidden="true">⌕</span><b><?php echo esc_html( $cfg['empty'] ); ?></b>Thử đổi từ khóa hoặc xóa bớt bộ lọc.<br><button type="button" class="ec-btn" data-ec-reset>Xóa bộ lọc</button></div>
	<?php else : ?>
		<div class="ec-list" data-ec-list>
			<?php
			foreach ( array_values( $items ) as $i => $it ) {
				$no = ( $from ?: 1 ) + $i;
				if ( 'exams' === $kind ) {
					cvc_cat_card_exam( (array) $it, $no );
				} elseif ( 'topics' === $kind ) {
					cvc_cat_card_topic( (array) $it, $no );
				} else {
					cvc_cat_card_knowledge( (array) $it, $no );
				}
			}
			?>
		</div>
		<nav class="ec-pager" aria-label="Phân trang">
			<span>Trang <?php echo (int) $cur; ?>/<?php echo (int) $last; ?></span>
			<?php if ( $last > 1 ) : ?>
				<div>
					<?php if ( $cur > 1 ) : ?><a href="<?php echo esc_url( cvc_cat_url( $kind, $f, $cur - 1 ) ); ?>" data-page="<?php echo (int) ( $cur - 1 ); ?>" rel="prev" aria-label="Trang trước">‹</a><?php else : ?><span class="is-off" aria-hidden="true">‹</span><?php endif; ?>
					<?php foreach ( cvc_cat_pages( $cur, $last ) as $n ) : ?>
						<?php if ( 0 === $n ) : ?><span class="is-gap">…</span><?php elseif ( $n === $cur ) : ?><span class="is-cur" aria-current="page"><?php echo (int) $n; ?></span><?php else : ?><a href="<?php echo esc_url( cvc_cat_url( $kind, $f, $n ) ); ?>" data-page="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></a><?php endif; ?>
					<?php endforeach; ?>
					<?php if ( $cur < $last ) : ?><a href="<?php echo esc_url( cvc_cat_url( $kind, $f, $cur + 1 ) ); ?>" data-page="<?php echo (int) ( $cur + 1 ); ?>" rel="next" aria-label="Trang sau">›</a><?php else : ?><span class="is-off" aria-hidden="true">›</span><?php endif; ?>
				</div>
			<?php endif; ?>
		</nav>
	<?php endif; ?>
	<?php
}

/* ------------------------------------------------------------------
 * Trang đầy đủ
 * ------------------------------------------------------------------ */
function cvc_cat_select( string $id, string $name, string $empty, array $opts, string $cur ): void {
	echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '"><option value="">' . esc_html( $empty ) . '</option>';
	foreach ( $opts as $v => $lbl ) {
		echo '<option value="' . esc_attr( (string) $v ) . '"' . selected( $cur, (string) $v, false ) . '>' . esc_html( $lbl ) . '</option>';
	}
	echo '</select>';
}

function cvc_cat_check( string $name, string $label, bool $on, bool $auth, bool $logged_in, string $value = '1' ): void {
	printf(
		'<label class="ec-check%5$s"><input type="checkbox" name="%1$s" value="%2$s"%3$s%4$s>%6$s</label>',
		esc_attr( $name ),
		esc_attr( $value ),
		checked( $on, true, false ),
		$auth ? ' data-ec-auth="1"' : '',
		$auth && ! $logged_in ? ' is-locked' : '',
		esc_html( $label ) . ( $auth && ! $logged_in ? ' <small>· cần đăng nhập</small>' : '' )
	);
}

function cvc_cat_render_page( string $kind ): void {
	$kind      = in_array( $kind, CVC_CAT_KINDS, true ) ? $kind : 'exams';
	$cfg       = cvc_cat_config( $kind );
	$token     = cvc_auth_token();
	$logged_in = null !== $token;
	$page      = max( 1, absint( get_query_var( 'cvc_paged' ) ) );
	$f         = cvc_cat_filters( $kind, $_GET ); // phpcs:ignore WordPress.Security.NonceVerification
	$sr        = cvc_ci_api( '/api/catalog/stats', array( 'kind' => $kind ), $token, 12, 600 );
	$st        = $sr['ok'] ? (array) ( $sr['data']['data'] ?? array() ) : array();
	$mine      = is_array( $st['mine'] ?? null ) ? $st['mine'] : null;
	$res       = cvc_cat_fetch( $kind, $f, $page, $token );
	$groups    = array();
	foreach ( (array) ( $st['groups'] ?? array() ) as $g ) {
		$groups[ (string) $g['code'] ] = $g['label'] . ' (' . cvc_cat_n( $g['count'] ) . ')';
	}
	$base      = call_user_func( $cfg['url'], 1 );
	$saved_n   = $mine ? (int) ( $mine['saved'] ?? 0 ) : null;

	// Ô số liệu: [biểu tượng, giá trị, nhãn]
	if ( 'exams' === $kind ) {
		$p25   = $st['duration_p25'] ?? null;
		$p75   = $st['duration_p75'] ?? null;
		$hero  = array( 'NGÂN HÀNG ĐỀ THI', cvc_cat_n( $st['total'] ?? null ), cvc_cat_n( $st['questions_total'] ?? null ) . ' câu hỏi có chấm điểm' );
		$cards = array(
			array( '▤', cvc_cat_n( $st['total'] ?? null ), 'Đề thi đang mở' ),
			array( '✓', (string) count( $groups ), 'Nhóm kiến thức' ),
			array( '◷', null !== $p25 && null !== $p75 ? ( $p25 === $p75 ? $p25 . "'" : $p25 . '–' . $p75 . "'" ) : '—', 'Thời lượng phổ biến' ),
			$mine
				? ( null !== ( $mine['avg_last5'] ?? null ) ? array( '↗', (int) $mine['avg_last5'] . '%', 'Điểm TB 5 bài gần nhất' ) : array( '↗', cvc_cat_n( $mine['done'] ?? 0 ), 'Đề bạn đã hoàn thành' ) )
				: array( '↗', 'Cá nhân hóa', 'Gợi ý theo kết quả sau khi đăng nhập' ),
		);
	} elseif ( 'topics' === $kind ) {
		$hero  = array( 'NGÂN HÀNG CHỦ ĐỀ', cvc_cat_n( $st['total'] ?? null ), cvc_cat_n( $st['knowledge_total'] ?? null ) . ' mục kiến thức liên kết' );
		$cards = array(
			array( '▤', cvc_cat_n( $st['total'] ?? null ), 'Chủ đề ôn thi' ),
			array( '✓', cvc_cat_n( $st['knowledge_total'] ?? null ), 'Mục kiến thức trọng tâm' ),
			array( '◷', cvc_cat_n( $st['questions_total'] ?? null ), 'Câu hỏi luyện tập' ),
			$mine ? array( '↗', cvc_cat_n( $mine['weak'] ?? 0 ), 'Chủ đề bạn cần ôn lại' ) : array( '↗', (string) count( $groups ), 'Nhóm kiến thức' ),
		);
	} else {
		$hero  = array( 'KIẾN THỨC TRỌNG TÂM', cvc_cat_n( $st['total'] ?? null ), 'Trích từ ' . cvc_cat_n( $st['documents_total'] ?? null ) . ' văn bản gốc' );
		$cards = array(
			array( '▤', cvc_cat_n( $st['total'] ?? null ), 'Mục kiến thức trọng tâm' ),
			array( '✓', cvc_cat_n( $st['documents_total'] ?? null ), 'Văn bản pháp luật gốc' ),
			array( '◷', cvc_cat_n( $st['with_practice'] ?? null ), 'Mục có đề luyện tập' ),
			$mine ? array( '↗', cvc_cat_n( $mine['saved'] ?? 0 ), 'Mục bạn đã lưu' ) : array( '↗', (string) count( $groups ), 'Nhóm kiến thức' ),
		);
	}
	$nav     = function_exists( 'cvc_main_nav_items' ) ? cvc_main_nav_items() : array();
	$study   = array();
	foreach ( $nav as $n ) {
		if ( 'exams' === $n['key'] ) {
			$study = (array) ( $n['children'] ?? array() );
		}
	}
	?>
	<main id="main" class="ec" data-kind="<?php echo esc_attr( $kind ); ?>">
		<section class="ec-hero">
			<div class="ec-wrap">
				<?php
				cvc_render_breadcrumbs(
					array(
						array( 'label' => 'Trang chủ', 'url' => home_url( '/' ) ),
						array( 'label' => 'Ôn thi', 'url' => cvc_exams_url() ),
						array( 'label' => $cfg['crumb'] ),
					)
				);
				?>
				<div class="ec-hero-row">
					<div>
						<div class="ec-eyebrow"><i class="ec-dot"></i><?php echo esc_html( $cfg['eyebrow'] ); ?></div>
						<h1><?php echo esc_html( $cfg['h1'] ); ?></h1>
						<p><?php echo esc_html( $cfg['lead'] ); ?></p>
						<div class="ec-hero-actions">
							<a class="ec-btn is-primary" href="#ec-q" data-ec-focus>⌕ <?php echo esc_html( $cfg['find'] ); ?></a>
							<button type="button" class="ec-btn<?php echo $f['saved'] ? ' is-on' : ''; ?>" data-ec-toggle="saved">♡ <?php echo esc_html( $cfg['saved'] ); ?><?php if ( null !== $saved_n ) : ?> <span class="ec-count" data-ec-saved-count><?php echo (int) $saved_n; ?></span><?php endif; ?></button>
						</div>
					</div>
					<div class="ec-hero-stat"><small><?php echo esc_html( $hero[0] ); ?></small><strong><?php echo esc_html( $hero[1] ); ?></strong><span><?php echo esc_html( $hero[2] ); ?></span></div>
				</div>
			</div>
		</section>

		<div class="ec-wrap ec-main">
			<section class="ec-stats" aria-label="Số liệu">
				<?php foreach ( $cards as $c ) : ?>
					<div class="ec-stat"><div class="ec-stat-icon" aria-hidden="true"><?php echo esc_html( $c[0] ); ?></div><div><strong><?php echo esc_html( (string) $c[1] ); ?></strong><small><?php echo esc_html( $c[2] ); ?></small></div></div>
				<?php endforeach; ?>
			</section>

			<div class="ec-layout">
				<aside class="ec-side">
					<form class="ec-panel" id="ec-form" method="get" action="<?php echo esc_url( $base ); ?>" data-ec-form role="search">
						<div class="ec-panel-head"><h2><?php echo esc_html( $cfg['filter_h'] ); ?></h2><span class="ec-fcount" data-ec-fcount><?php echo (int) cvc_cat_filter_count( $f ); ?> bộ lọc</span><button type="button" class="ec-ftoggle" data-ec-ftoggle aria-expanded="false" aria-controls="ec-form">Mở bộ lọc ▾</button></div>
						<div class="ec-panel-body">
							<div class="ec-search-area">
								<label class="ec-label" for="ec-q"><?php echo esc_html( $cfg['search_l'] ); ?></label>
								<div class="ec-search"><input id="ec-q" name="q" type="search" value="<?php echo esc_attr( $f['q'] ); ?>" placeholder="<?php echo esc_attr( $cfg['search_p'] ); ?>" maxlength="100" autocomplete="off"><button class="ec-btn is-primary" type="submit">Tìm</button></div>
							</div>
							<?php if ( 'exams' === $kind ) : ?>
								<fieldset class="ec-filter"><legend class="ec-ftitle">Loại đề thi</legend><div class="ec-checks">
									<?php
									foreach ( cvc_cat_exam_types() as $t => $lbl ) {
										$cnt = $st['types'][ $t ] ?? null;
										cvc_cat_check( 'type[]', $lbl . ( null !== $cnt ? ' (' . cvc_cat_n( $cnt ) . ')' : '' ), in_array( $t, $f['type'], true ), false, $logged_in, $t );
									}
									?>
								</div></fieldset>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-group">Nhóm kiến thức</label><?php cvc_cat_select( 'ec-group', 'group', 'Tất cả nhóm', $groups, $f['group'] ); ?></div>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-dur">Thời lượng</label><?php cvc_cat_select( 'ec-dur', 'max_minutes', 'Tất cả thời lượng', array( 30 => 'Tối đa 30 phút', 40 => 'Tối đa 40 phút', 60 => 'Tối đa 60 phút', 90 => 'Tối đa 90 phút' ), $f['max_minutes'] ? (string) $f['max_minutes'] : '' ); ?></div>
								<fieldset class="ec-filter"><legend class="ec-ftitle">Tùy chọn</legend><div class="ec-checks">
									<?php cvc_cat_check( 'saved', 'Chỉ xem đề đã lưu', $f['saved'], true, $logged_in ); ?>
									<?php cvc_cat_check( 'not_done', 'Chưa làm lần nào', $f['not_done'], true, $logged_in ); ?>
								</div></fieldset>
							<?php elseif ( 'topics' === $kind ) : ?>
								<?php
								$subs = array();
								foreach ( (array) ( $st['subjects'] ?? array() ) as $s ) {
									$subs[ (int) $s['id'] ] = $s['name'] . ' (' . cvc_cat_n( $s['count'] ) . ')';
								}
								?>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-subject">Môn thi</label><?php cvc_cat_select( 'ec-subject', 'subject_id', 'Tất cả môn', $subs, $f['subject_id'] ? (string) $f['subject_id'] : '' ); ?></div>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-group">Nhóm kiến thức</label><?php cvc_cat_select( 'ec-group', 'group', 'Tất cả nhóm', $groups, $f['group'] ); ?></div>
								<fieldset class="ec-filter"><legend class="ec-ftitle">Tùy chọn</legend><div class="ec-checks">
									<?php cvc_cat_check( 'saved', 'Chỉ xem chủ đề đã lưu', $f['saved'], true, $logged_in ); ?>
									<?php cvc_cat_check( 'weak', 'Chủ đề tôi cần ôn lại', $f['weak'], true, $logged_in ); ?>
								</div></fieldset>
							<?php else : ?>
								<?php
								$kinds = array();
								foreach ( (array) ( $st['doc_kinds'] ?? array() ) as $d ) {
									if ( (int) $d['count'] > 0 ) {
										$kinds[ (string) $d['code'] ] = $d['label'] . ' (' . cvc_cat_n( $d['count'] ) . ')';
									}
								}
								?>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-kind">Loại văn bản</label><?php cvc_cat_select( 'ec-kind', 'doc_kind', 'Tất cả loại văn bản', $kinds ?: cvc_cat_doc_kinds(), $f['doc_kind'] ); ?></div>
								<div class="ec-filter"><label class="ec-ftitle" for="ec-group">Nhóm kiến thức</label><?php cvc_cat_select( 'ec-group', 'group', 'Tất cả nhóm', $groups, $f['group'] ); ?></div>
								<fieldset class="ec-filter"><legend class="ec-ftitle">Tùy chọn</legend><div class="ec-checks">
									<?php cvc_cat_check( 'practice', 'Có đề luyện tập', $f['practice'], false, $logged_in ); ?>
									<?php cvc_cat_check( 'saved', 'Chỉ xem mục đã lưu', $f['saved'], true, $logged_in ); ?>
								</div></fieldset>
							<?php endif; ?>
							<noscript><button class="ec-btn is-primary ec-reset" type="submit">Áp dụng bộ lọc</button></noscript>
							<a class="ec-btn ec-reset" href="<?php echo esc_url( $base ); ?>" data-ec-reset>↺ Xóa bộ lọc</a>
						</div>
					</form>

					<section class="ec-panel ec-progress">
						<div class="ec-panel-head"><h2>Tiến độ của bạn</h2><span class="ec-fcount"><?php echo $logged_in ? 'Đã đồng bộ' : 'Chưa đăng nhập'; ?></span></div>
						<div class="ec-panel-body">
							<?php if ( ! $mine ) : ?>
								<p class="ec-note">Đăng nhập để đồng bộ <?php echo 'exams' === $kind ? 'kết quả làm bài' : ( 'topics' === $kind ? 'tỷ lệ đúng theo chủ đề' : 'mục kiến thức đã lưu' ); ?> và nhận gợi ý ôn tập.</p>
								<a class="ec-btn is-primary ec-reset" href="<?php echo esc_url( cvc_login_url( cvc_cat_url( $kind, $f, $page ) ) ); ?>">♙ Đăng nhập</a>
							<?php elseif ( 'exams' === $kind ) : ?>
								<?php $done = (int) ( $mine['done'] ?? 0 ); $started = (int) ( $mine['started'] ?? 0 ); ?>
								<div class="ec-prow"><span>Đề đã hoàn thành</span><b><?php echo (int) $done; ?>/<?php echo (int) max( $done, $started ); ?></b></div>
								<div class="ec-pbar"><span style="width:<?php echo (int) ( $started ? round( $done / max( $done, $started ) * 100 ) : 0 ); ?>%"></span></div>
								<p class="ec-note"><?php echo $started ? 'Số đề bạn đã mở: ' . (int) $started . '.' : 'Bạn chưa làm đề nào. Chọn một đề bên cạnh để bắt đầu.'; ?><?php echo null !== ( $mine['avg_last5'] ?? null ) ? ' Điểm trung bình 5 bài gần nhất: ' . (int) $mine['avg_last5'] . '%.' : ''; ?></p>
								<a class="ec-link" href="<?php echo esc_url( cvc_account_url( 'exam-history' ) ); ?>">Xem lịch sử làm bài →</a>
							<?php elseif ( 'topics' === $kind ) : ?>
								<?php $pr = (int) ( $mine['practiced'] ?? 0 ); $tt = (int) ( $st['total'] ?? 0 ); ?>
								<div class="ec-prow"><span>Chủ đề đã luyện</span><b><?php echo (int) $pr; ?>/<?php echo esc_html( cvc_cat_n( $tt ) ); ?></b></div>
								<div class="ec-pbar"><span style="width:<?php echo (int) ( $tt ? max( $pr ? 2 : 0, round( $pr / $tt * 100 ) ) : 0 ); ?>%"></span></div>
								<?php if ( (int) ( $mine['weak'] ?? 0 ) > 0 ) : ?>
									<button type="button" class="ec-link" data-ec-toggle="weak"><?php echo (int) $mine['weak']; ?> chủ đề cần ôn lại →</button>
								<?php else : ?>
									<p class="ec-note">Chưa phát hiện chủ đề yếu. Làm thêm câu hỏi để hệ thống đánh giá chính xác hơn.</p>
								<?php endif; ?>
							<?php else : ?>
								<div class="ec-prow"><span>Mục kiến thức đã lưu</span><b data-ec-saved-count><?php echo (int) ( $mine['saved'] ?? 0 ); ?></b></div>
								<p class="ec-note">Lưu các điều khoản quan trọng để ôn lại nhanh trước ngày thi.</p>
								<button type="button" class="ec-link" data-ec-toggle="saved">Xem mục đã lưu →</button>
							<?php endif; ?>
						</div>
					</section>
				</aside>

				<section class="ec-center" id="ec-results" aria-live="polite">
					<?php cvc_cat_render_results( $kind, $res, $f, $page, $st, $logged_in ); ?>
				</section>

				<aside class="ec-right">
					<?php if ( ! empty( $st['latest_legal'] ) ) : ?>
						<section class="ec-panel">
							<div class="ec-panel-head"><h2>Văn bản mới</h2><a class="ec-link" href="<?php echo esc_url( cvc_legal_documents_url() ); ?>">Xem tất cả →</a></div>
							<div class="ec-panel-body">
								<?php foreach ( (array) $st['latest_legal'] as $d ) : ?>
									<a class="ec-news" href="<?php echo esc_url( home_url( (string) ( $d['url'] ?? '/' ) ) ); ?>"><b><?php echo esc_html( wp_trim_words( (string) ( $d['title'] ?? '' ), 18 ) ); ?></b><small><?php echo esc_html( implode( ' · ', array_filter( array( ! empty( $d['issued_date'] ) ? 'Ban hành ' . cvc_cat_date( (string) $d['issued_date'] ) : '', (string) ( $d['effect_label'] ?? '' ) ) ) ) ); ?></small></a>
								<?php endforeach; ?>
							</div>
						</section>
					<?php endif; ?>
					<section class="ec-promo">
						<div class="ec-eyebrow"><i class="ec-dot"></i>Luyện thi thông minh</div>
						<h3>Biết mình yếu đâu. Ôn đúng phần đó.</h3>
						<p><?php echo $logged_in ? 'Lộ trình cá nhân dựa trên kết quả làm bài và vị trí dự tuyển bạn chọn.' : 'Làm vài đề sau khi đăng nhập, hệ thống sẽ chỉ ra chủ đề yếu và gợi ý tài liệu, đề luyện phù hợp.'; ?></p>
						<a class="ec-btn" href="<?php echo esc_url( add_query_arg( 'tab', 'path', cvc_recruitments_url() ) ); ?>">Khám phá lộ trình →</a>
					</section>
					<section class="ec-panel">
						<div class="ec-panel-head"><h2>Luyện đề miễn phí</h2></div>
						<div class="ec-panel-body">
							<p class="ec-note">Làm đề có chấm điểm và xem giải thích từng câu sau khi nộp.</p>
							<nav class="ec-study" aria-label="Ôn thi">
								<?php foreach ( $study as $c ) : $is = untrailingslashit( (string) $c['url'] ) === untrailingslashit( $base ); ?>
									<a href="<?php echo esc_url( (string) $c['url'] ); ?>"<?php echo $is ? ' class="is-cur" aria-current="page"' : ''; ?>><span><?php echo esc_html( (string) $c['label'] ); ?></span><?php if ( ! empty( $c['badge'] ) ) : ?><small><?php echo esc_html( (string) $c['badge'] ); ?></small><?php elseif ( $is ) : ?><small class="is-t">Đang xem</small><?php else : ?><em aria-hidden="true">→</em><?php endif; ?></a>
								<?php endforeach; ?>
							</nav>
						</div>
					</section>
				</aside>
			</div>
		</div>
		<div class="ec-toast" role="status" aria-live="polite" data-ec-toast></div>
	</main>
	<?php
}

/* ------------------------------------------------------------------
 * Tài nguyên + AJAX
 * ------------------------------------------------------------------ */
add_action( 'wp_enqueue_scripts', 'cvc_cat_assets', 60 );

function cvc_cat_assets(): void {
	$page = (string) get_query_var( 'cvc_page' );
	if ( ! in_array( $page, CVC_CAT_KINDS, true ) ) {
		return;
	}
	wp_enqueue_style( 'cvc-catalog', get_theme_file_uri( '/assets/css/catalog.css' ), array(), (string) filemtime( get_theme_file_path( '/assets/css/catalog.css' ) ) );
	wp_enqueue_script( 'cvc-catalog', get_theme_file_uri( '/assets/js/catalog.js' ), array(), (string) filemtime( get_theme_file_path( '/assets/js/catalog.js' ) ), true );
	wp_add_inline_script(
		'cvc-catalog',
		'window.CVC_CAT=' . wp_json_encode(
			array(
				'ajax'     => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'cvc_cat' ),
				'kind'     => $page,
				'loggedIn' => null !== cvc_auth_token(),
				'login'    => cvc_login_url( call_user_func( cvc_cat_config( $page )['url'], 1 ) ),
				'base'     => call_user_func( cvc_cat_config( $page )['url'], 1 ),
			)
		) . ';',
		'before'
	);
}

add_action( 'wp_ajax_cvc_cat_view', 'cvc_cat_ajax_view' );
add_action( 'wp_ajax_nopriv_cvc_cat_view', 'cvc_cat_ajax_view' );

function cvc_cat_ajax_view(): void {
	$kind = sanitize_key( wp_unslash( $_REQUEST['kind'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! in_array( $kind, CVC_CAT_KINDS, true ) ) {
		wp_send_json_error( array( 'message' => 'Không hợp lệ.' ), 400 );
	}
	$f     = cvc_cat_filters( $kind, $_REQUEST ); // phpcs:ignore WordPress.Security.NonceVerification
	$page  = max( 1, min( 500, absint( $_REQUEST['page'] ?? 1 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$token = cvc_auth_token();
	$res   = cvc_cat_fetch( $kind, $f, $page, $token );
	$st    = array();
	if ( 'topics' === $kind && $f['subject_id'] ) {
		$sr = cvc_ci_api( '/api/catalog/stats', array( 'kind' => $kind ), null, 12, 600 );
		$st = $sr['ok'] ? (array) ( $sr['data']['data'] ?? array() ) : array();
	}
	ob_start();
	cvc_cat_render_results( $kind, $res, $f, $page, $st, null !== $token );
	$html = (string) ob_get_clean();
	nocache_headers();
	wp_send_json_success(
		array(
			'html'   => $html,
			'url'    => cvc_cat_url( $kind, $f, $page ),
			'fcount' => cvc_cat_filter_count( $f ),
		)
	);
}

add_action( 'wp_ajax_cvc_cat_attach', 'cvc_cat_ajax_attach' );
add_action( 'wp_ajax_nopriv_cvc_cat_attach', 'cvc_cat_ajax_attach' );

/** "Gắn đề thi với mục tiêu của tôi" -> lộ trình của hồ sơ chinh phục chính. */
function cvc_cat_ajax_attach(): void {
	check_ajax_referer( 'cvc_cat', 'nonce' );
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Đăng nhập để gắn đề vào hồ sơ chinh phục.', 'login' => true ), 401 );
	}
	$res = ( new CVC_Api_Client( null, 30 ) )->post( '/api/me/conquest/attach-exam', array( 'exam_id' => absint( $_POST['id'] ?? 0 ) ), $token );
	$url = function_exists( 'cvc_conquest_url' ) ? cvc_conquest_url( (int) ( $res['data']['data']['goal_id'] ?? 0 ) ) : cvc_account_url();
	if ( ! $res['ok'] ) {
		$msg = (string) ( $res['data']['message'] ?? '' );
		wp_send_json_error( array( 'message' => '' !== $msg && in_array( (int) $res['status'], array( 404, 422 ), true ) ? $msg : cvc_api_error_message( $res ), 'profile' => ! empty( $res['data']['need_profile'] ) ? cvc_conquest_url() : null ), (int) ( $res['status'] ?? 0 ) ?: 500 );
	}
	wp_send_json_success( array( 'message' => (string) ( $res['data']['message'] ?? 'Đã gắn đề vào hồ sơ.' ), 'url' => $url ) );
}

add_action( 'wp_ajax_cvc_cat_save', 'cvc_cat_ajax_save' );
add_action( 'wp_ajax_nopriv_cvc_cat_save', 'cvc_cat_ajax_save' );

function cvc_cat_ajax_save(): void {
	check_ajax_referer( 'cvc_cat', 'nonce' );
	$token = cvc_auth_token();
	if ( null === $token ) {
		wp_send_json_error( array( 'message' => 'Vui lòng đăng nhập để lưu vào tài khoản.', 'login' => true ), 401 );
	}
	$type = sanitize_key( wp_unslash( $_POST['type'] ?? '' ) );
	if ( ! in_array( $type, array( 'exam', 'topic', 'knowledge_item' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Loại nội dung không hợp lệ.' ), 400 );
	}
	$saved = in_array( (string) ( $_POST['saved'] ?? '' ), array( '1', 'true' ), true );
	$res   = ( new CVC_Api_Client( null, 20 ) )->post( '/api/me/career/save', array( 'type' => $type, 'id' => absint( $_POST['id'] ?? 0 ), 'saved' => $saved ), $token );
	if ( ! $res['ok'] ) {
		$msg = (string) ( $res['data']['message'] ?? '' );
		wp_send_json_error( array( 'message' => '' !== $msg && in_array( (int) $res['status'], array( 404, 422 ), true ) ? $msg : cvc_api_error_message( $res ), 'login' => 401 === (int) $res['status'] ), (int) ( $res['status'] ?? 0 ) ?: 500 );
	}
	wp_send_json_success( array( 'message' => (string) ( $res['data']['message'] ?? ( $saved ? 'Đã lưu vào tài khoản.' : 'Đã bỏ lưu.' ) ), 'saved' => $saved ) );
}
