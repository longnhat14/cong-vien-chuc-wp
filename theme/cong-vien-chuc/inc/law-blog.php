<?php
/**
 * Trang Điều luật (/kien-thuc/) → bài viết blog đúng ngữ cảnh (12/11/2026, claude/ke-hoach-dieu-luat-dan-blog.md).
 *
 *  - cvc_law_cluster_of(): văn bản → cụm luật / chuyên ngành (theo tên + cơ quan ban hành).
 *  - cvc_kt_blog_pick(): chọn bài theo thứ tự (1) bài cầu nối của cụm (meta cvc_law_cluster) (2) bài trích đúng số hiệu
 *    (3) bài cùng chuyên ngành (4) bài trụ cột; trong mỗi tầng xếp theo mã băm của trang → mỗi trang cố định 1 bộ bài,
 *    nhưng 10 nghìn trang chia đều link cho mọi bài trong nhóm (không dồn vào 3 bài).
 *  - Dòng gợi ý trong nội dung (sau "Giải thích"), khối 3 bài cột phải, luyện nhanh 5 câu theo Điều.
 *  - Mọi link gắn data-cvc-go="<vị trí>" → track.js đếm lượt bấm (bảng link_clicks_daily, xem ở Quản trị → Tình trạng index).
 *  - Shortcode [cvc_dieu_hay_tra docs="146,148" so="8"] cho bài cầu nối: các Điều được tìm đọc nhiều nhất (tự cập nhật).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** @return array<string, array{label:string, prompt:string, doc:array<int,string>, blog:array<int,string>, pillars:array<int,string>}> */
function cvc_law_clusters(): array {
	return array(
		'y-te'      => array(
			'label'   => 'Y tế – Dược',
			'prompt'  => 'Đang ôn thi viên chức ngành y tế?',
			'doc'     => array( 'kham benh', 'chua benh', 'duoc', 'y te', 'byt', 'benh vien', 'bao hiem y te', 'an toan thuc pham', 'dan so', 'phong chong benh', 'hanh nghe' ),
			'blog'    => array( 'y te', 'dieu duong', 'benh vien', 'bac si', 'trung tam y te' ),
			'pillars' => array( 'on-thi-vien-chuc-nganh-y-te', 'tuyen-vien-chuc-y-te', 'on-thi-vien-chuc' ),
		),
		'khcn'      => array(
			'label'   => 'Khoa học, công nghệ',
			'prompt'  => 'Ôn luật khoa học, công nghệ cho kỳ thi hoặc cuộc thi tìm hiểu pháp luật?',
			'doc'     => array( 'khoa hoc', 'cong nghe', 'doi moi sang tao', 'bkhcn', 'so huu tri tue', 'chuyen doi so', 'giao dich dien tu', 'an ninh mang', 'vien thong', 'buu chinh', 'du lieu', 'tieu chuan', 'do luong', 'nang luong nguyen tu', 'an toan buc xa' ),
			'blog'    => array( 'dai hoc', 'kien thuc chung', 'vi tri viec lam' ),
			'pillars' => array( 'kien-thuc-chung-thi-vien-chuc', 'on-thi-vien-chuc', 'tuyen-dung-vien-chuc-dai-hoc-2026' ),
		),
		'giao-duc'  => array(
			'label'   => 'Giáo dục',
			'prompt'  => 'Đang ôn thi viên chức giáo dục?',
			'doc'     => array( 'nha giao', 'giao duc', 'giao vien', 'bgddt', 'hoc sinh', 'dai hoc', 'mam non', 'pho thong', 'giao duc nghe nghiep' ),
			'blog'    => array( 'giao vien', 'giao duc', 'mam non', 'tieu hoc', 'thcs', 'thpt' ),
			'pillars' => array( 'on-thi-vien-chuc-giao-duc', 'tuyen-vien-chuc-giao-vien-2026', 'on-thi-vien-chuc' ),
		),
		'xay-dung'  => array(
			'label'   => 'Xây dựng – Quy hoạch – Đất đai',
			'prompt'  => 'Ôn thi công chức địa chính – xây dựng?',
			'doc'     => array( 'xay dung', 'quy hoach', 'bxd', 'nha o', 'do thi', 'kien truc', 'dat dai', 'tai nguyen', 'moi truong', 'dia chinh', 'giao thong', 'kinh doanh bat dong san' ),
			'blog'    => array( 'dia chinh', 'xay dung', 'cap xa' ),
			'pillars' => array( 'on-thi-cong-chuc-dia-chinh-xay-dung', 'tuyen-dung-cong-chuc-dia-chinh-xay-dung-cap-xa', 'on-thi-cong-chuc' ),
		),
		'ngan-sach' => array(
			'label'   => 'Tài chính – Ngân sách',
			'prompt'  => 'Ôn thi công chức tài chính – ngân sách (thuế, kho bạc, kế toán)?',
			'doc'     => array( 'ngan sach', 'dau tu cong', 'dau thau', 'thue', 'ke toan', 'kiem toan', 'tai chinh', 'btc', 'tai san cong', 'phi va le phi', 'kho bac', 'hai quan', 'quan ly no' ),
			'blog'    => array( 'thue', 'ke toan', 'hai quan', 'tai chinh', 'kho bac' ),
			'pillars' => array( 'on-thi-cong-chuc-ke-toan', 'tuyen-dung-cong-chuc-bo-tai-chinh-2026', 'on-thi-cong-chuc' ),
		),
		'tu-phap'   => array(
			'label'   => 'Tư pháp – Hộ tịch',
			'prompt'  => 'Ôn thi công chức tư pháp – hộ tịch?',
			'doc'     => array( 'hoa giai', 'ho tich', 'tu phap', 'chung thuc', 'dan su', 'hinh su', 'to tung', 'thi hanh an', 'btp', 'luat su', 'cong chung', 'xu ly vi pham hanh chinh', 'tro giup phap ly' ),
			'blog'    => array( 'tu phap', 'ho tich', 'vien kiem sat' ),
			'pillars' => array( 'on-thi-cong-chuc-tu-phap-ho-tich', 'tuyen-dung-cong-chuc-tu-phap-ho-tich-cap-xa', 'on-thi-cong-chuc' ),
		),
		'cong-vu'   => array(
			'label'   => 'Công vụ – Tuyển dụng',
			'prompt'  => 'Đang ôn thi công chức, viên chức?',
			'doc'     => array(),
			'blog'    => array( 'luat', 'kien thuc chung', 'noi vu', 'vi tri viec lam', 'chuyen vien', 'van phong' ),
			'pillars' => array( 'on-thi-cong-chuc', 'tuyen-cong-chuc', 'on-thi-vien-chuc', 'tuyen-vien-chuc' ),
		),
	);
}

/** Cụm của văn bản (mặc định công vụ). */
function cvc_law_cluster_of( ?array $doc ): string {
	if ( ! $doc ) {
		return 'cong-vu';
	}
	$t = ' ' . cvc_blog_ascii( (string) ( $doc['title'] ?? '' ) . ' ' . (string) ( $doc['issuing_agency'] ?? '' ) . ' ' . str_replace( array( '/', '-' ), ' ', (string) ( $doc['document_number'] ?? '' ) ) ) . ' ';
	// Văn bản công vụ đặc thù (Luật CBCC, Viên chức, tuyển dụng…) đi thẳng vào cụm công vụ.
	foreach ( array( 'can bo cong chuc', 'vien chuc', 'tuyen dung', 'bnv', 'bo noi vu' ) as $k ) {
		if ( str_contains( $t, ' ' . $k . ' ' ) ) {
			return 'cong-vu';
		}
	}
	foreach ( cvc_law_clusters() as $key => $c ) {
		foreach ( $c['doc'] as $k ) {
			if ( str_contains( $t, ' ' . $k . ' ' ) ) {
				return $key;
			}
		}
	}

	return 'cong-vu';
}

/** Xếp ổn định theo trang: cùng trang luôn ra cùng thứ tự, trang khác ra thứ tự khác. */
function cvc_kt_shuffle( array $posts, string $seed ): array {
	usort( $posts, static fn ( $a, $b ) => crc32( $seed . '|' . $a['id'] ) <=> crc32( $seed . '|' . $b['id'] ) );

	return $posts;
}

/**
 * Chọn bài cho trang Điều luật.
 *
 * @return array{cluster:string, inline:?array, side:array<int,array>}
 */
function cvc_kt_blog_pick( ?array $doc, string $seed, int $n = 3 ): array {
	$key   = cvc_law_cluster_of( $doc );
	$conf  = cvc_law_clusters()[ $key ];
	$posts = function_exists( 'cvc_blog_posts_index' ) ? cvc_blog_posts_index() : array();
	$num   = mb_strtoupper( trim( (string) ( $doc['document_number'] ?? '' ) ), 'UTF-8' );
	$tiers = array( array(), array(), array(), array() );
	$slug  = static fn ( $p ) => basename( rtrim( (string) $p['url'], '/' ) );
	foreach ( $posts as $p ) {
		if ( $key === ( $p['bridge'] ?? '' ) ) {
			$tiers[0][] = $p;
		} elseif ( '' !== $num && in_array( $num, (array) ( $p['laws'] ?? array() ), true ) ) {
			$tiers[1][] = $p;
		} else {
			$txt = ' ' . $p['text'] . ' ';
			foreach ( $conf['blog'] as $k ) {
				if ( str_contains( $txt, ' ' . $k . ' ' ) ) {
					$tiers[2][] = $p;
					break;
				}
			}
		}
	}
	foreach ( $conf['pillars'] as $ps ) {
		foreach ( $posts as $p ) {
			if ( $slug( $p ) === $ps ) {
				$tiers[3][] = $p;
			}
		}
	}
	$list = array();
	$seen = array();
	foreach ( $tiers as $i => $t ) {
		foreach ( 3 === $i ? $t : cvc_kt_shuffle( $t, $seed ) as $p ) {
			if ( ! isset( $seen[ $p['id'] ] ) ) {
				$seen[ $p['id'] ] = true;
				$list[]           = $p;
			}
		}
	}

	return array( 'cluster' => $key, 'inline' => $list[0] ?? null, 'side' => array_slice( $list, 1, $n ) );
}

/** Dòng gợi ý trong nội dung chính. */
function cvc_kt_render_inline( array $pick ): void {
	if ( empty( $pick['inline'] ) ) {
		return;
	}
	$conf = cvc_law_clusters()[ $pick['cluster'] ];
	$p    = $pick['inline'];
	?>
	<p class="cvc-kt-next"><span><?php echo esc_html( $conf['prompt'] ); ?></span> <a href="<?php echo esc_url( $p['url'] ); ?>" data-cvc-go="kt-inline"><?php echo esc_html( $p['title'] ); ?> →</a></p>
	<?php
}

/** Khối 3 bài ở cột phải. */
function cvc_kt_render_side( array $pick ): void {
	if ( empty( $pick['side'] ) ) {
		return;
	}
	cvc_blog_css();
	?>
	<section class="cvc-blog-rel" aria-label="Bài hướng dẫn liên quan">
		<p class="cvc-blog-rel-h">Bài hướng dẫn liên quan</p>
		<ul>
			<?php foreach ( $pick['side'] as $p ) : ?>
				<li><a href="<?php echo esc_url( $p['url'] ); ?>" data-cvc-go="kt-side"><?php echo esc_html( $p['title'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
		<a class="cvc-blog-rel-all" href="<?php echo esc_url( home_url( '/bai-viet/' ) ); ?>" data-cvc-go="kt-side">Tất cả bài hướng dẫn →</a>
	</section>
	<?php
}

/** Luyện nhanh 5 câu theo Điều (bù câu cùng văn bản) - dùng chung bộ chấm mini quiz. */
function cvc_kt_render_quiz( int $ki, int $ld, string $article_label, ?array $practice, array $pick ): void {
	if ( $ki <= 0 ) {
		return;
	}
	cvc_kt_quiz_assets();
	$after = $pick['inline'] ?? null;
	?>
	<section id="luyen-nhanh" class="dz-panel scroll-mt-24 space-y-3" aria-labelledby="luyen-nhanh-h">
		<h2 id="luyen-nhanh-h" class="text-base font-black text-ink">Luyện nhanh 5 câu<?php echo '' !== $article_label ? ' về ' . esc_html( $article_label ) : ''; ?></h2>
		<div class="dz-quiz" data-cvc-mini-quiz data-ki="<?php echo esc_attr( (string) $ki ); ?>" data-ld="<?php echo esc_attr( (string) $ld ); ?>">
			<div class="dz-quiz-top"><strong data-quiz-progress>Đang tải câu hỏi…</strong><span class="dz-muted" data-quiz-score></span></div>
			<div data-quiz-body class="dz-quiz-body" aria-live="polite"><noscript><p class="dz-muted">Cần bật JavaScript để làm thử.</p></noscript></div>
			<div class="dz-quiz-actions">
				<button type="button" data-quiz-next class="hidden dz-btn dz-btn-primary">Câu tiếp theo →</button>
				<button type="button" data-quiz-reload class="hidden dz-btn">Làm bộ khác</button>
			</div>
			<div data-quiz-after hidden class="cvc-kt-after">
				<?php if ( $practice && ! empty( $practice['slug'] ) ) : ?>
					<a href="<?php echo esc_url( cvc_exam_url( (string) $practice['slug'] ) ); ?>" data-cvc-go="kt-quiz">Làm đề <?php echo (int) ( $practice['total_questions'] ?? 0 ); ?> câu theo cả văn bản →</a>
				<?php endif; ?>
				<?php if ( $after ) : ?>
					<a href="<?php echo esc_url( $after['url'] ); ?>" data-cvc-go="kt-quiz">Đọc tiếp: <?php echo esc_html( $after['title'] ); ?> →</a>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
}

function cvc_kt_quiz_assets(): void {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	$f    = get_theme_file_path( '/assets/js/homepage-nextgen.js' );
	wp_enqueue_script( 'cvc-homepage-nextgen', get_theme_file_uri( '/assets/js/homepage-nextgen.js' ), array(), (string) filemtime( $f ), true );
	wp_localize_script( 'cvc-homepage-nextgen', 'cvcNextGen', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'cvc_mini_quiz' ), 'homeUrl' => home_url( '/' ) ) );
}

add_action(
	'wp_head',
	static function (): void {
		if ( '' === (string) get_query_var( 'cvc_knowledge_slug' ) ) {
			return;
		}
		echo '<style>.cvc-kt-next{margin:0;padding:12px 16px;border:1px solid #cfe0f1;border-left:4px solid #1767aa;border-radius:12px;background:#f5f9fd;font-size:15px;line-height:1.5}.cvc-kt-next span{color:#14263b;font-weight:700;margin-right:4px}.cvc-kt-next a{color:#1767aa;font-weight:700;text-decoration:underline;text-underline-offset:2px}'
			. '.cvc-kt-after{margin-top:12px;display:flex;flex-direction:column;gap:6px;font-size:15px}.cvc-kt-after a{color:#1767aa;font-weight:700}#luyen-nhanh .dz-quiz{border-radius:12px;background:#fff}#luyen-nhanh .dz-quiz-q{font-size:16px}#luyen-nhanh .dz-quiz-opt{font-size:15px}#luyen-nhanh .dz-btn{padding:8px 14px;border:1px solid #c9d4e0;border-radius:10px;background:#fff;font-weight:700}#luyen-nhanh .dz-btn-primary{background:#1767aa;border-color:#1767aa;color:#fff}</style>';
	}
);

/* ---------- Shortcode cho bài cầu nối: các Điều được tìm đọc nhiều nhất (theo lượt hiển thị Google 28 ngày) ---------- */
add_shortcode( 'cvc_dieu_hay_tra', 'cvc_sc_dieu_hay_tra' );

function cvc_sc_dieu_hay_tra( $atts ): string {
	$a    = shortcode_atts( array( 'docs' => '', 'so' => 8, 'tieu_de' => '' ), (array) $atts );
	$docs = implode( ',', array_slice( array_filter( array_map( 'absint', explode( ',', (string) $a['docs'] ) ) ), 0, 12 ) );
	if ( '' === $docs ) {
		return '';
	}
	$n   = max( 3, min( 15, (int) $a['so'] ) );
	$key = 'cvc_lawtop_' . md5( $docs . ':' . $n );
	$rows = get_transient( $key );
	if ( ! is_array( $rows ) ) {
		$res  = ( new CVC_Api_Client( null, 10 ) )->get( '/api/reports/law-top', array( 'docs' => $docs, 'limit' => $n ) );
		$rows = ! empty( $res['ok'] ) && is_array( $res['data']['data'] ?? null ) ? $res['data']['data'] : array();
		set_transient( $key, $rows, $rows ? 6 * HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS );
	}
	if ( ! $rows ) {
		return '';
	}
	$h = '<div class="cvc-lawtop">';
	if ( '' !== (string) $a['tieu_de'] ) {
		$h .= '<p class="cvc-lawtop-h">' . esc_html( (string) $a['tieu_de'] ) . '</p>';
	}
	$h .= '<ol>';
	foreach ( $rows as $r ) {
		$h .= '<li><a href="' . esc_url( cvc_knowledge_item_url( (string) $r['slug'] ) ) . '"><strong>' . esc_html( (string) $r['title'] ) . '</strong></a> <small>(' . esc_html( (string) $r['document_number'] ) . ')</small>'
			. ( '' !== (string) $r['summary'] ? '<br><span>' . esc_html( (string) $r['summary'] ) . '</span>' : '' ) . '</li>';
	}
	$h .= '</ol><p class="cvc-lawtop-note">Danh sách tự cập nhật theo số lượt người tìm đọc trên Google 28 ngày gần nhất.</p></div>';
	static $css = false;
	if ( ! $css ) {
		$css = true;
		$h  .= '<style>.cvc-lawtop{margin:16px 0;padding:14px 16px;border:1px solid #dfe4ea;border-radius:12px;background:#fff}.cvc-lawtop-h{margin:0 0 8px;font-weight:800;color:#123a63}.cvc-lawtop ol{margin:0;padding-left:22px;display:grid;gap:10px}.cvc-lawtop li{line-height:1.5}.cvc-lawtop li span{color:#4a5563;font-size:.95em}.cvc-lawtop small{color:#5c5c5c}.cvc-lawtop-note{margin:10px 0 0;font-size:13px;color:#5c5c5c}</style>';
	}

	return $h;
}
