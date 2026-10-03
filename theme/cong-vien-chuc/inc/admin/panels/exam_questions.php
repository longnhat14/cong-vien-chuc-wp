<?php
/**
 * Panel: danh sách câu hỏi trong đề thi (PUT /api/admin/exams/{id}/questions).
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$item      = (array) ( $args['item'] ?? array() );
$questions = (array) ( $item['questions'] ?? array() );
$ids       = array_map( static fn ( $q ) => (int) ( $q['id'] ?? 0 ), $questions );
?>
<section class="bg-[#0A192F] border border-slate-800 rounded-2xl p-5 space-y-3" aria-labelledby="cvc-exam-questions">
	<div class="flex items-center justify-between gap-3">
		<h2 id="cvc-exam-questions" class="text-base font-black text-white">Câu hỏi trong đề <span class="text-slate-400 font-bold text-sm">(<?php echo esc_html( number_format_i18n( count( $questions ) ) ); ?>)</span></h2>
		<a class="text-xs text-cyan-300 font-bold" href="<?php echo esc_url( cvc_admin_url( 'questions' ) ); ?>" target="_blank" rel="noopener">Tìm câu hỏi trong ngân hàng ↗</a>
	</div>

	<?php if ( ! empty( $questions ) ) : ?>
		<ol class="max-h-80 overflow-y-auto divide-y divide-slate-800 text-sm list-decimal list-inside">
			<?php foreach ( $questions as $q ) : ?>
				<li class="py-1.5 text-slate-200">
					<a class="hover:text-cyan-300" href="<?php echo esc_url( cvc_admin_url( 'questions', (int) $q['id'] ) ); ?>">#<?php echo (int) $q['id']; ?> · <?php echo esc_html( wp_trim_words( (string) ( $q['question_text'] ?? '' ), 16 ) ); ?></a>
					<?php if ( ! empty( $q['exam_subject']['name'] ) ) : ?>
						<span class="text-xs text-slate-500">— <?php echo esc_html( (string) $q['exam_subject']['name'] ); ?></span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>

	<?php if ( cvc_admin_can( 'exam.update' ) ) : ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="space-y-2">
			<?php wp_nonce_field( 'cvc_admin_exam_questions' ); ?>
			<input type="hidden" name="action" value="cvc_admin_exam_questions">
			<input type="hidden" name="id" value="<?php echo (int) ( $item['id'] ?? 0 ); ?>">
			<label for="cvc-exam-question-ids" class="block text-xs font-bold text-slate-300">ID câu hỏi theo thứ tự trong đề</label>
			<textarea id="cvc-exam-question-ids" name="question_ids" rows="4" class="<?php echo esc_attr( cvc_admin_input_class() ); ?> font-mono text-xs"><?php echo esc_textarea( implode( ', ', $ids ) ); ?></textarea>
			<p class="text-[11px] text-slate-500">Ngăn cách bằng dấu phẩy hoặc xuống dòng. Danh sách này thay thế toàn bộ câu hỏi hiện có của đề.</p>
			<button type="submit" class="px-4 py-2 bg-cyan-500 hover:bg-cyan-400 text-navy-950 font-black text-sm rounded-xl">Cập nhật câu hỏi của đề</button>
		</form>
	<?php endif; ?>
</section>
