<?php
/**
 * GD4 - On thi theo dot tuyen dung (danh muc tai lieu on tap cua Hoi dong) + can cu phap ly.
 * Du lieu tu API /recruitments/{slug}: study_plan, legal_basis.
 *
 * @package CongVienChuc
 */

defined( 'ABSPATH' ) || exit;

/**
 * Nhan vong thi cua de gan voi dot (exam_recruitment.exam_stage).
 */
function cvc_exam_stage_label( string $stage ): string {
	$map = array(
		'stage_1'     => 'Vòng 1',
		'knowledge'   => 'Vòng 1',
		'stage_2'     => 'Vòng 2',
		'specialized' => 'Vòng 2',
	);
	return $map[ $stage ] ?? ( '' !== $stage ? $stage : '' );
}

/**
 * Chip trang thai on tap cua 1 van ban trong danh muc.
 */
function cvc_study_doc_status( array $doc ): array {
	$q = (int) ( $doc['questions'] ?? 0 );
	switch ( (string) ( $doc['status'] ?? '' ) ) {
		case 'ready':
			return array( 'Đủ câu luyện (' . $q . ')', 'text-emerald-300 border-emerald-400/40' );
		case 'partial':
			return array( 'Có ' . $q . ' câu', 'text-cyan-300 border-cyan-400/40' );
		case 'preparing':
			return array( 'Đang soạn câu hỏi', 'text-amber-300 border-amber-400/40' );
		default:
			return array( 'Chưa có toàn văn', 'text-slate-400 border-slate-600' );
	}
}

/**
 * Danh sach van ban cua 1 vong / 1 vi tri.
 */
function cvc_render_study_docs( array $docs ): void {
	if ( empty( $docs ) ) {
		return;
	}
	echo '<ul class="divide-y divide-slate-800 border border-slate-800 rounded-2xl overflow-hidden">';
	foreach ( $docs as $doc ) {
		list( $label, $tone ) = cvc_study_doc_status( $doc );
		$number = (string) ( $doc['number'] ?? '' );
		$title  = (string) ( $doc['title'] ?? '' );
		echo '<li class="px-4 py-3 bg-slate-900/60 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">';
		echo '<div class="flex-1 min-w-0">';
		if ( ! empty( $doc['slug'] ) ) {
			echo '<a class="text-sm font-semibold text-white hover:text-emerald-300 leading-snug" href="' . esc_url( cvc_legal_document_url( (string) $doc['slug'] ) ) . '">' . esc_html( $title ) . '</a>';
		} else {
			echo '<span class="text-sm font-semibold text-slate-200 leading-snug">' . esc_html( $title ) . '</span>';
		}
		echo '<span class="block text-[11px] font-mono text-amber-300/90 mt-0.5">' . esc_html( $number ) . ( ! empty( $doc['expired'] ) ? ' <span class="text-rose-300 font-sans">· hết hiệu lực</span>' : '' ) . '</span>';
		echo '</div>';
		echo '<div class="flex flex-wrap items-center gap-2 shrink-0">';
		echo '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full border ' . esc_attr( $tone ) . '">' . esc_html( $label ) . '</span>';
		if ( ! empty( $doc['practice_slug'] ) ) {
			echo '<a class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-cyan-500/10 border border-cyan-400/40 text-cyan-200 hover:bg-cyan-500/20" href="' . esc_url( cvc_exam_url( (string) $doc['practice_slug'] ) ) . '">Luyện tập</a>';
		}
		echo '</div></li>';
	}
	echo '</ul>';
}

/**
 * The de thi thu.
 */
function cvc_render_study_exam_link( array $exam, string $accent = 'emerald' ): void {
	$tones = array(
		'emerald' => 'border-emerald-400/50 hover:border-emerald-300 text-emerald-200',
		'amber'   => 'border-amber-400/50 hover:border-amber-300 text-amber-200',
	);
	echo '<a href="' . esc_url( cvc_exam_url( (string) ( $exam['slug'] ?? '' ) ) ) . '" class="block p-3.5 rounded-2xl bg-navy-950 border ' . esc_attr( $tones[ $accent ] ?? $tones['emerald'] ) . '">';
	// Ten day du co hau to " · <ten dot>" - trong muc lo trinh cua chinh dot do thi bo di cho gon.
	$label = preg_replace( '/\s·\s[^·]*$/u', '', (string) ( $exam['title'] ?? '' ) );
	echo '<span class="block text-sm font-bold leading-snug">' . esc_html( (string) $label ) . '</span>';
	echo '<span class="block text-[11px] text-slate-400 mt-1">' . esc_html( (int) ( $exam['total_questions'] ?? 0 ) . ' câu · ' . (int) ( $exam['duration_minutes'] ?? 0 ) . ' phút' ) . '</span>';
	echo '</a>';
}

/**
 * Muc "On thi theo danh muc cua Hoi dong" tren trang tin tuyen dung.
 */
function cvc_render_recruitment_study_plan( array $plan ): void {
	$syllabus = (array) ( $plan['syllabus'] ?? array() );
	$progress = (array) ( $plan['progress'] ?? array() );
	$round1   = (array) ( $plan['round_1'] ?? array() );
	$round2   = (array) ( $plan['round_2'] ?? array() );
	$rules    = (array) ( $plan['rules'] ?? array() );
	$total    = max( 1, (int) ( $progress['documents'] ?? 0 ) );
	$withq    = (int) ( $progress['with_questions'] ?? 0 );
	$pct      = (int) round( 100 * $withq / $total );
	?>
	<section id="lo-trinh-on" class="bg-navy-950 p-6 sm:p-8 rounded-3xl border border-emerald-500/30 space-y-6 shadow-xl">
		<div class="border-b border-slate-800 pb-4 space-y-2">
			<p class="text-[11px] font-black uppercase tracking-wider text-emerald-400">Ôn đúng danh mục của Hội đồng tuyển dụng</p>
			<h2 class="text-lg sm:text-xl font-extrabold text-white leading-snug">Lộ trình ôn thi cho đợt này</h2>
			<p class="text-xs text-slate-400">
				Theo Danh mục tài liệu ôn tập <?php echo esc_html( (string) ( $syllabus['notice_number'] ?? '' ) ); ?>
				<?php if ( ! empty( $syllabus['issued_date'] ) ) : ?>
					ngày <?php echo esc_html( cvc_format_date_vn( (string) $syllabus['issued_date'] ) ); ?>
				<?php endif; ?>
				<?php if ( ! empty( $syllabus['source_url'] ) ) : ?>
					· <a class="text-azure-300 hover:underline" href="<?php echo esc_url( (string) $syllabus['source_url'] ); ?>" target="_blank" rel="noopener nofollow">văn bản gốc</a>
				<?php endif; ?>
			</p>
			<div class="flex items-center gap-3 pt-1">
				<div class="flex-1 h-2 rounded-full bg-slate-800 overflow-hidden" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( (string) $pct ); ?>">
					<div class="h-full bg-emerald-400" style="width: <?php echo esc_attr( (string) $pct ); ?>%"></div>
				</div>
				<span class="text-[11px] text-slate-300 tabular-nums whitespace-nowrap"><?php echo esc_html( $withq . '/' . (int) ( $progress['documents'] ?? 0 ) ); ?> văn bản đã có câu luyện</span>
			</div>
		</div>

		<?php if ( ! empty( $round1['documents'] ) ) : ?>
			<div class="space-y-3">
				<div class="flex flex-wrap items-baseline justify-between gap-2">
					<h3 class="text-base font-extrabold text-white">Vòng 1 · Kiến thức, năng lực chung <span class="text-xs font-semibold text-slate-400">(mọi thí sinh)</span></h3>
					<span class="text-[11px] text-slate-400"><?php echo esc_html( count( $round1['documents'] ) ); ?> văn bản</span>
				</div>
				<?php if ( ! empty( $rules['round_1'] ) ) : ?>
					<p class="text-xs text-slate-400"><?php echo esc_html( (string) $rules['round_1'] ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $round1['exams'] ) ) : ?>
					<div class="grid sm:grid-cols-3 gap-3">
						<?php foreach ( $round1['exams'] as $exam ) { cvc_render_study_exam_link( (array) $exam, 'emerald' ); } ?>
					</div>
				<?php endif; ?>
				<?php cvc_render_study_docs( (array) $round1['documents'] ); ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $round2 ) ) : ?>
			<div class="space-y-3" data-cvc-study-round2>
				<div class="flex flex-wrap items-baseline justify-between gap-2">
					<h3 class="text-base font-extrabold text-white">Vòng 2 · Nghiệp vụ chuyên ngành <span class="text-xs font-semibold text-slate-400">(theo vị trí dự tuyển)</span></h3>
					<span class="text-[11px] text-slate-400"><?php echo esc_html( count( $round2 ) ); ?> vị trí</span>
				</div>
				<?php if ( ! empty( $rules['round_2'] ) ) : ?>
					<p class="text-xs text-slate-400"><?php echo esc_html( (string) $rules['round_2'] ); ?></p>
				<?php endif; ?>
				<label for="cvc-study-position" class="block text-xs font-bold text-slate-300">Chọn vị trí bạn dự tuyển</label>
				<select id="cvc-study-position" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-emerald-400">
					<?php foreach ( $round2 as $i => $sec ) : ?>
						<option value="<?php echo esc_attr( (string) ( $sec['id'] ?? $i ) ); ?>"><?php echo esc_html( trim( ( ! empty( $sec['code'] ) ? $sec['code'] . '. ' : '' ) . wp_trim_words( (string) ( $sec['title'] ?? '' ), 26 ) ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php foreach ( $round2 as $i => $sec ) : ?>
					<div class="space-y-3" data-cvc-study-section="<?php echo esc_attr( (string) ( $sec['id'] ?? $i ) ); ?>" <?php echo 0 === $i ? '' : 'hidden'; ?>>
						<p class="text-sm text-slate-200 font-semibold leading-snug"><?php echo esc_html( (string) ( $sec['title'] ?? '' ) ); ?></p>
						<?php if ( ! empty( $sec['exam'] ) ) : ?>
							<div class="grid sm:grid-cols-2 gap-3"><?php cvc_render_study_exam_link( (array) $sec['exam'], 'amber' ); ?></div>
						<?php else : ?>
							<p class="text-xs text-amber-200/90 bg-amber-500/10 border border-amber-400/30 rounded-xl px-3 py-2">Đề thi thử cho vị trí này đang được chuẩn bị: cần ít nhất 60 câu hỏi đã kiểm định từ đúng các văn bản dưới đây. Trong lúc chờ, bạn có thể luyện theo từng văn bản đã có câu hỏi.</p>
						<?php endif; ?>
						<?php cvc_render_study_docs( (array) ( $sec['documents'] ?? array() ) ); ?>
					</div>
				<?php endforeach; ?>
				<script>
				(function () {
					var sel = document.getElementById('cvc-study-position');
					if (!sel) return;
					var panels = document.querySelectorAll('[data-cvc-study-section]');
					var show = function (id) { panels.forEach(function (p) { p.hidden = p.getAttribute('data-cvc-study-section') !== id; }); };
					var saved = null;
					try { saved = window.localStorage.getItem('cvc-study-pos-' + location.pathname); } catch (e) {}
					if (saved && sel.querySelector('option[value="' + saved + '"]')) { sel.value = saved; show(saved); }
					sel.addEventListener('change', function () {
						show(sel.value);
						try { window.localStorage.setItem('cvc-study-pos-' + location.pathname, sel.value); } catch (e) {}
					});
				})();
				</script>
			</div>
		<?php endif; ?>

		<p class="text-[11px] text-slate-500">Câu hỏi được soạn từ toàn văn chính thức, mỗi câu trích dẫn Điều cụ thể và đã qua kiểm tra độc lập. Danh mục chính thức là văn bản của Hội đồng tuyển dụng - hãy đối chiếu khi ôn.</p>
	</section>
	<?php
}

/**
 * "Can cu phap ly" cua dot tuyen dung.
 */
function cvc_render_recruitment_legal_basis( array $basis ): void {
	if ( empty( $basis ) ) {
		return;
	}
	?>
	<div class="border-t border-slate-800 pt-4 space-y-2" id="can-cu-phap-ly">
		<h3 class="text-sm font-bold text-white">Căn cứ pháp lý của đợt tuyển</h3>
		<ul class="space-y-1.5 text-xs">
			<?php foreach ( $basis as $b ) : ?>
				<li class="flex flex-wrap items-baseline gap-x-2">
					<span class="font-mono text-amber-300 shrink-0"><?php echo esc_html( (string) ( $b['number'] ?? '' ) ); ?></span>
					<?php if ( ! empty( $b['slug'] ) ) : ?>
						<a class="text-slate-200 hover:text-emerald-300" href="<?php echo esc_url( cvc_legal_document_url( (string) $b['slug'] ) ); ?>"><?php echo esc_html( (string) ( $b['title'] ?? '' ) ); ?></a>
					<?php else : ?>
						<span class="text-slate-300"><?php echo esc_html( (string) ( $b['title'] ?? '' ) ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
