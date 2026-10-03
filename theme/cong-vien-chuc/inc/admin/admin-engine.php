<?php
/**
 * Khu quản trị /quan-tri/ (Phase 13) - lõi xử lý: quyền, gọi admin API,
 * dựng trường form, thu payload, các handler admin-post và AJAX.
 *
 * Nguyên tắc: Laravel vẫn là nơi kiểm tra quyền + validate cuối cùng. WP
 * chỉ ẩn/hiện theo danh sách permission trả về từ GET /api/auth/me và
 * chuyển nguyên thông báo lỗi của backend về cho người dùng.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Quyền                                                               */
/* ------------------------------------------------------------------ */

function cvc_admin_is_staff(): bool {
	$user = cvc_current_user();

	return is_array( $user ) && ! empty( $user['is_staff'] );
}

function cvc_admin_can( string $permission ): bool {
	$user = cvc_current_user();
	if ( ! is_array( $user ) ) {
		return false;
	}
	if ( in_array( 'SUPER_ADMIN', (array) ( $user['roles'] ?? array() ), true ) ) {
		return true;
	}

	return in_array( $permission, (array) ( $user['permissions'] ?? array() ), true );
}

function cvc_admin_can_resource( array $res, string $action ): bool {
	if ( ! empty( $res['read_only'] ) && in_array( $action, array( 'create', 'update', 'delete' ), true ) ) {
		return false;
	}

	return cvc_admin_can( $res['module'] . '.' . $action );
}

/**
 * Chặn truy cập handler quản trị: phải đăng nhập + là nhân sự vận hành.
 */
function cvc_admin_require_staff(): string {
	$token = cvc_require_token_or_die();
	if ( ! cvc_admin_is_staff() ) {
		wp_die( esc_html__( 'Bạn không có quyền truy cập khu quản trị.', 'cvc' ), '', array( 'response' => 403 ) );
	}

	return $token;
}

/* ------------------------------------------------------------------ */
/* URL                                                                 */
/* ------------------------------------------------------------------ */

function cvc_admin_url( string $resource = '', $id = null, array $args = array() ): string {
	$path = '/quan-tri/';
	if ( '' !== $resource ) {
		$path .= $resource . '/';
		if ( 'new' === $id ) {
			$path .= 'moi/';
		} elseif ( null !== $id ) {
			$path .= (int) $id . '/';
		}
	}
	$url = home_url( $path );

	return empty( $args ) ? $url : add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $args ) ), $url );
}

/* ------------------------------------------------------------------ */
/* Gọi API                                                             */
/* ------------------------------------------------------------------ */

function cvc_admin_client(): CVC_Api_Client {
	static $client = null;
	if ( null === $client ) {
		$client = new CVC_Api_Client( null, 25 );
	}

	return $client;
}

/**
 * @param array<string, mixed> $query
 */
function cvc_admin_api_get( string $path, array $query = array() ): array {
	$query = array_filter( $query, static fn ( $v ) => null !== $v && '' !== $v );
	$query = array_map( static fn ( $v ) => is_string( $v ) ? rawurlencode( $v ) : $v, $query );

	return cvc_admin_client()->get( $path, $query, cvc_auth_token() );
}

/**
 * Thông báo lỗi đầy đủ (gộp mọi lỗi validate) từ kết quả API.
 */
function cvc_admin_error_message( array $result ): string {
	$data = $result['data'] ?? null;
	if ( 422 === (int) ( $result['status'] ?? 0 ) && is_array( $data ) && ! empty( $data['errors'] ) && is_array( $data['errors'] ) ) {
		$messages = array();
		foreach ( $data['errors'] as $list ) {
			foreach ( (array) $list as $msg ) {
				$messages[] = (string) $msg;
			}
		}
		if ( ! empty( $messages ) ) {
			return implode( ' ', array_slice( array_unique( $messages ), 0, 6 ) );
		}
	}
	if ( 500 === (int) ( $result['status'] ?? 0 ) ) {
		return 'Máy chủ gặp lỗi khi xử lý (mã 500). Kiểm tra lại các trường đã nhập; nếu vẫn lỗi, báo bộ phận kỹ thuật kèm thời điểm thao tác.';
	}
	if ( in_array( (int) ( $result['status'] ?? 0 ), array( 403, 409, 422 ), true ) && is_array( $data ) && ! empty( $data['message'] ) ) {
		return (string) $data['message'];
	}

	return cvc_api_error_message( $result );
}

/**
 * Lựa chọn cho ô chọn quan hệ: id => nhãn. Lấy tối đa 5 trang × 100 bản
 * ghi, cache 5 phút (chỉ id + nhãn, không chứa dữ liệu nhạy cảm).
 *
 * @return array<int, string>
 */
function cvc_admin_relation_options( string $resource_key ): array {
	static $memo = array();
	if ( isset( $memo[ $resource_key ] ) ) {
		return $memo[ $resource_key ];
	}

	$cache_key = 'cvc_admin_opts_' . md5( $resource_key );
	$cached    = get_transient( $cache_key );
	if ( is_array( $cached ) ) {
		return $memo[ $resource_key ] = $cached;
	}

	$res = cvc_admin_resource( $resource_key );
	if ( ! $res ) {
		return array();
	}

	$options = array();
	for ( $page = 1; $page <= 5; $page++ ) {
		$result = cvc_admin_api_get( $res['endpoint'], array( 'per_page' => 100, 'page' => $page ) );
		if ( ! $result['ok'] ) {
			break;
		}
		$pagination = $result['data']['data'] ?? array();
		foreach ( (array) ( $pagination['data'] ?? array() ) as $row ) {
			if ( isset( $row['id'] ) ) {
				$options[ (int) $row['id'] ] = cvc_admin_option_label( $resource_key, $row );
			}
		}
		if ( (int) ( $pagination['last_page'] ?? 1 ) <= $page ) {
			break;
		}
	}

	asort( $options, SORT_NATURAL | SORT_FLAG_CASE );
	set_transient( $cache_key, $options, 5 * MINUTE_IN_SECONDS );

	return $memo[ $resource_key ] = $options;
}

function cvc_admin_option_label( string $resource_key, array $row ): string {
	switch ( $resource_key ) {
		case 'legal-documents':
			return trim( ( $row['document_number'] ?? '' ) . ' — ' . wp_trim_words( (string) ( $row['title'] ?? '' ), 10 ), ' —' );
		case 'recruitments':
			return trim( ( $row['code'] ?? '' ) . ' — ' . wp_trim_words( (string) ( $row['title'] ?? '' ), 12 ), ' —' );
		case 'topics':
			$subject = $row['exam_subject']['name'] ?? '';
			return (string) ( $row['name'] ?? '' ) . ( '' !== $subject ? ' (' . $subject . ')' : '' );
		case 'exams':
			return trim( ( $row['code'] ?? '' ) . ' — ' . wp_trim_words( (string) ( $row['title'] ?? '' ), 10 ), ' —' );
		case 'agencies':
			return (string) ( $row['name'] ?? '' ) . ( ! empty( $row['province']['name'] ) ? ' (' . $row['province']['name'] . ')' : '' );
		case 'course-lessons':
			return (string) ( $row['title'] ?? '' ) . ( ! empty( $row['course']['title'] ) ? ' — ' . wp_trim_words( (string) $row['course']['title'], 6 ) : '' );
		default:
			return (string) ( $row['name'] ?? $row['title'] ?? ( '#' . $row['id'] ) );
	}
}

function cvc_admin_flush_relation_cache( string $resource_key ): void {
	delete_transient( 'cvc_admin_opts_' . md5( $resource_key ) );
}

/**
 * @return array<int, string>
 */
function cvc_admin_provinces(): array {
	$cached = get_transient( 'cvc_admin_provinces_v1' );
	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return $cached;
	}
	$result  = cvc_admin_api_get( '/api/admin/lookups/provinces' );
	$options = array();
	foreach ( (array) ( $result['data']['data'] ?? array() ) as $row ) {
		$options[ (int) $row['id'] ] = (string) $row['name'];
	}
	if ( ! empty( $options ) ) {
		set_transient( 'cvc_admin_provinces_v1', $options, DAY_IN_SECONDS );
	}

	return $options;
}

/**
 * @return array<int, string>
 */
function cvc_admin_admin_units( int $province_id ): array {
	if ( $province_id <= 0 ) {
		return array();
	}
	$result  = cvc_admin_api_get( '/api/admin/lookups/admin-units', array( 'province_id' => $province_id ) );
	$options = array();
	foreach ( (array) ( $result['data']['data'] ?? array() ) as $row ) {
		$options[ (int) $row['id'] ] = (string) $row['name'];
	}

	return $options;
}

/* ------------------------------------------------------------------ */
/* Tiện ích hiển thị                                                   */
/* ------------------------------------------------------------------ */

/**
 * Đọc giá trị theo đường dẫn "a.b.c".
 *
 * @return mixed
 */
function cvc_admin_get( array $item, string $path ) {
	$value = $item;
	foreach ( explode( '.', $path ) as $segment ) {
		if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
			return null;
		}
		$value = $value[ $segment ];
	}

	return $value;
}

function cvc_admin_format( $value, string $format = '' ): string {
	if ( null === $value || '' === $value ) {
		return '—';
	}
	switch ( $format ) {
		case 'date':
			return cvc_format_date_vn( (string) $value ) ?: '—';
		case 'datetime':
			$ts = strtotime( (string) $value );
			return $ts ? wp_date( 'd/m/Y H:i', $ts ) : '—';
		case 'money':
			return number_format( (float) $value, 0, ',', '.' ) . 'đ';
		case 'bool':
			return $value ? 'Có' : 'Không';
		case 'status':
			$labels = cvc_admin_status_labels();
			$key    = is_bool( $value ) ? ( $value ? '1' : '0' ) : (string) $value;
			return $labels[ $key ] ?? $key;
		case 'difficulty':
			$map = array( 'easy' => 'Dễ', 'medium' => 'Trung bình', 'hard' => 'Khó' );
			return $map[ (string) $value ] ?? (string) $value;
		case 'list':
			return is_array( $value ) ? implode( ', ', array_map( 'strval', $value ) ) : (string) $value;
		default:
			return is_scalar( $value ) ? (string) $value : '—';
	}
}

function cvc_admin_status_class( $value ): string {
	$key = is_bool( $value ) ? ( $value ? '1' : '0' ) : (string) $value;
	$map = array(
		'published' => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40',
		'active'    => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40',
		'paid'      => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40',
		'1'         => 'bg-emerald-500/15 text-emerald-300 border-emerald-500/40',
		'review'    => 'bg-amber-500/15 text-amber-300 border-amber-500/40',
		'pending'   => 'bg-amber-500/15 text-amber-300 border-amber-500/40',
		'draft'     => 'bg-slate-700/60 text-slate-300 border-slate-600',
		'0'         => 'bg-slate-700/60 text-slate-300 border-slate-600',
		'expired'   => 'bg-rose-500/15 text-rose-300 border-rose-500/40',
		'failed'    => 'bg-rose-500/15 text-rose-300 border-rose-500/40',
		'cancelled' => 'bg-rose-500/15 text-rose-300 border-rose-500/40',
	);

	return $map[ $key ] ?? 'bg-slate-700/60 text-slate-300 border-slate-600';
}

function cvc_admin_item_title( array $res, array $item ): string {
	$title = (string) ( $item[ $res['title'] ] ?? '' );

	return '' !== $title ? wp_trim_words( $title, 14 ) : ( 'Bản ghi #' . (int) ( $item['id'] ?? 0 ) );
}

function cvc_admin_public_url( array $res, array $item ): string {
	if ( empty( $res['public']['fn'] ) || ! function_exists( $res['public']['fn'] ) ) {
		return '';
	}
	$value = (string) ( $item[ $res['public']['key'] ] ?? '' );
	if ( '' === $value ) {
		return '';
	}
	$statuses = $res['public']['statuses'] ?? null;
	if ( is_array( $statuses ) && ! in_array( (string) ( $item['status'] ?? '' ), $statuses, true ) ) {
		return '';
	}

	return (string) call_user_func( $res['public']['fn'], $value );
}

/* ------------------------------------------------------------------ */
/* Dữ liệu nhập lại khi lỗi (giữ form)                                 */
/* ------------------------------------------------------------------ */

function cvc_admin_old_key(): string {
	return 'cvc_admin_old_' . md5( (string) cvc_auth_token() );
}

function cvc_admin_remember_input( array $input ): void {
	set_transient( cvc_admin_old_key(), $input, 5 * MINUTE_IN_SECONDS );
}

function cvc_admin_take_old_input(): ?array {
	$old = get_transient( cvc_admin_old_key() );
	if ( is_array( $old ) ) {
		delete_transient( cvc_admin_old_key() );
		return $old;
	}

	return null;
}

/* ------------------------------------------------------------------ */
/* Giá trị trường khi sửa                                              */
/* ------------------------------------------------------------------ */

/**
 * @return mixed
 */
function cvc_admin_field_value( array $field, array $item ) {
	$name = $field['name'];
	switch ( $field['type'] ) {
		case 'relation_multi':
			$ids = array();
			foreach ( (array) ( $item[ $field['from'] ?? $name ] ?? array() ) as $related ) {
				if ( is_array( $related ) && isset( $related['id'] ) ) {
					$ids[] = (int) $related['id'];
				} elseif ( is_numeric( $related ) ) {
					$ids[] = (int) $related;
				}
			}
			return $ids;
		case 'options':
			$rows = array();
			foreach ( (array) ( $item['options'] ?? array() ) as $opt ) {
				$rows[] = array(
					'option_text' => (string) ( $opt['option_text'] ?? '' ),
					'is_correct'  => ! empty( $opt['is_correct'] ),
					'explanation' => (string) ( $opt['explanation'] ?? '' ),
				);
			}
			return $rows;
		case 'date':
			$value = (string) ( $item[ $name ] ?? '' );
			return '' !== $value ? substr( $value, 0, 10 ) : '';
		case 'checkbox':
			$value = $item[ $name ] ?? null;
			return null === $value ? null : ( (bool) $value && '0' !== $value );
		case 'textarea':
			$value = $item[ $name ] ?? '';
			return is_array( $value ) ? implode( "\n", array_map( 'strval', $value ) ) : (string) $value;
		default:
			$value = $item[ $name ] ?? '';
			return is_scalar( $value ) ? (string) $value : '';
	}
}

/* ------------------------------------------------------------------ */
/* Dựng trường form                                                    */
/* ------------------------------------------------------------------ */

function cvc_admin_input_class(): string {
	return 'w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-700 text-sm text-white placeholder-slate-500 focus:border-cyan-400 focus:outline-none';
}

/**
 * @param mixed $value
 */
function cvc_admin_render_field( array $field, $value, bool $is_create, array $item = array() ): void {
	$name     = $field['name'];
	$id       = 'f-' . $name;
	$required = ! empty( $field['required'] ) || ( $is_create && ! empty( $field['required_on_create'] ) );
	$class    = cvc_admin_input_class();
	$wide     = ! empty( $field['wide'] ) || in_array( $field['type'], array( 'html', 'options', 'relation_multi' ), true );
	?>
	<div class="space-y-1.5 <?php echo $wide ? 'md:col-span-2' : ''; ?>">
		<?php if ( 'checkbox' !== $field['type'] ) : ?>
			<label for="<?php echo esc_attr( $id ); ?>" class="block text-xs font-bold text-slate-300">
				<?php echo esc_html( $field['label'] ); ?><?php echo $required ? ' <span class="text-rose-400" aria-hidden="true">*</span>' : ''; ?>
			</label>
		<?php endif; ?>
		<?php
		switch ( $field['type'] ) {
			case 'textarea':
			case 'html':
				printf(
					'<textarea id="%1$s" name="f[%2$s]" rows="%3$d" class="%4$s" %5$s>%6$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					'html' === $field['type'] ? 12 : 4,
					esc_attr( $class . ( 'html' === $field['type'] ? ' font-mono text-xs' : '' ) ),
					$required ? 'required' : '',
					esc_textarea( (string) $value )
				);
				if ( 'html' === $field['type'] ) {
					echo '<p class="text-[11px] text-slate-500">Cho phép HTML cơ bản (đoạn văn, danh sách, in đậm, liên kết). Xuống dòng sẽ được giữ nguyên.</p>';
				}
				break;

			case 'number':
			case 'decimal':
				printf(
					'<input type="number" id="%1$s" name="f[%2$s]" value="%3$s" step="%4$s" min="0" class="%5$s" %6$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					'decimal' === $field['type'] ? 'any' : '1',
					esc_attr( $class ),
					$required ? 'required' : ''
				);
				break;

			case 'date':
				printf( '<input type="date" id="%1$s" name="f[%2$s]" value="%3$s" class="%4$s" %5$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $class ), $required ? 'required' : '' );
				break;

			case 'checkbox':
				$checked = null === $value ? ! empty( $field['default'] ) || 'status' === $name : (bool) $value;
				?>
				<input type="hidden" name="f[<?php echo esc_attr( $name ); ?>]" value="0">
				<label class="inline-flex items-center gap-2 text-sm text-slate-200 pt-6">
					<input type="checkbox" id="<?php echo esc_attr( $id ); ?>" name="f[<?php echo esc_attr( $name ); ?>]" value="1" class="w-4 h-4 accent-amber-500" <?php checked( $checked ); ?>>
					<?php echo esc_html( $field['label'] ); ?>
				</label>
				<?php
				break;

			case 'select':
				echo '<select id="' . esc_attr( $id ) . '" name="f[' . esc_attr( $name ) . ']" class="' . esc_attr( $class ) . '" ' . ( $required ? 'required' : '' ) . '>';
				$options = (array) $field['options'];
				if ( ! empty( $field['nullable'] ) && ! isset( $options[''] ) ) {
					echo '<option value="">— Không chọn —</option>';
				}
				if ( '' !== (string) $value && ! isset( $options[ (string) $value ] ) ) {
					$options = array( (string) $value => (string) $value . ' (giá trị hiện có)' ) + $options;
				}
				foreach ( $options as $opt_value => $opt_label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( (string) $opt_value ), selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
				break;

			case 'relation':
			case 'province':
			case 'admin_unit':
				if ( 'province' === $field['type'] ) {
					$options = cvc_admin_provinces();
					$extra   = 'data-cvc-province="1"';
				} elseif ( 'admin_unit' === $field['type'] ) {
					$options = cvc_admin_admin_units( (int) ( $item['province_id'] ?? 0 ) );
					$extra   = 'data-cvc-admin-unit="1"';
				} else {
					$options = cvc_admin_relation_options( $field['resource'] );
					$extra   = count( $options ) > 15 ? 'data-cvc-filterable="1"' : '';
				}
				if ( '' !== (string) $value && ! isset( $options[ (int) $value ] ) ) {
					$options = array( (int) $value => '#' . (int) $value ) + $options;
				}
				echo '<select id="' . esc_attr( $id ) . '" name="f[' . esc_attr( $name ) . ']" class="' . esc_attr( $class ) . '" ' . ( $required ? 'required' : '' ) . ' ' . $extra . '>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $extra là chuỗi cố định.
				echo '<option value="">— Chọn —</option>';
				foreach ( $options as $opt_value => $opt_label ) {
					printf( '<option value="%1$d" %2$s>%3$s</option>', (int) $opt_value, selected( (string) $value, (string) $opt_value, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
				break;

			case 'relation_multi':
				$options  = cvc_admin_relation_options( $field['resource'] );
				$selected = array_map( 'intval', (array) $value );
				?>
				<input type="hidden" name="f[<?php echo esc_attr( $name ); ?>][]" value="">
				<?php if ( count( $options ) > 12 ) : ?>
					<input type="search" placeholder="Lọc danh sách..." class="<?php echo esc_attr( $class ); ?> mb-2" data-cvc-multi-filter="<?php echo esc_attr( $id ); ?>" aria-label="<?php echo esc_attr( 'Lọc ' . $field['label'] ); ?>">
				<?php endif; ?>
				<div id="<?php echo esc_attr( $id ); ?>" class="max-h-56 overflow-y-auto grid grid-cols-1 sm:grid-cols-2 gap-1 p-2 rounded-xl bg-slate-950 border border-slate-700">
					<?php if ( empty( $options ) ) : ?>
						<p class="text-xs text-slate-500">Chưa có dữ liệu để chọn.</p>
					<?php endif; ?>
					<?php foreach ( $options as $opt_value => $opt_label ) : ?>
						<label class="flex items-start gap-2 text-xs text-slate-200 px-2 py-1 rounded-lg hover:bg-slate-800" data-cvc-multi-item>
							<input type="checkbox" name="f[<?php echo esc_attr( $name ); ?>][]" value="<?php echo (int) $opt_value; ?>" class="mt-0.5 accent-amber-500" <?php checked( in_array( (int) $opt_value, $selected, true ) ); ?>>
							<span><?php echo esc_html( $opt_label ); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
				<?php
				break;

			case 'options':
				$rows = is_array( $value ) && ! empty( $value ) ? $value : array();
				while ( count( $rows ) < 4 ) {
					$rows[] = array( 'option_text' => '', 'is_correct' => false, 'explanation' => '' );
				}
				?>
				<div class="space-y-2" data-cvc-options>
					<?php foreach ( array_values( $rows ) as $i => $row ) : ?>
						<div class="grid grid-cols-[2rem_1fr_auto] gap-2 items-start p-2 rounded-xl bg-slate-950 border border-slate-700" data-cvc-option-row>
							<span class="w-8 h-8 rounded-lg bg-slate-800 text-cyan-300 font-black flex items-center justify-center text-xs" data-cvc-option-key><?php echo esc_html( chr( 65 + $i ) ); ?></span>
							<div class="space-y-1">
								<input type="text" name="f[options][<?php echo (int) $i; ?>][option_text]" value="<?php echo esc_attr( (string) $row['option_text'] ); ?>" placeholder="Nội dung phương án" class="<?php echo esc_attr( $class ); ?>" aria-label="<?php echo esc_attr( 'Phương án ' . chr( 65 + $i ) ); ?>">
								<input type="text" name="f[options][<?php echo (int) $i; ?>][explanation]" value="<?php echo esc_attr( (string) $row['explanation'] ); ?>" placeholder="Giải thích riêng cho phương án (không bắt buộc)" class="<?php echo esc_attr( $class ); ?> text-xs" aria-label="<?php echo esc_attr( 'Giải thích phương án ' . chr( 65 + $i ) ); ?>">
							</div>
							<label class="flex items-center gap-1.5 text-xs text-emerald-300 font-bold pt-2 whitespace-nowrap">
								<input type="checkbox" name="f[options][<?php echo (int) $i; ?>][is_correct]" value="1" class="accent-emerald-500" <?php checked( ! empty( $row['is_correct'] ) ); ?>> Đúng
							</label>
						</div>
					<?php endforeach; ?>
				</div>
				<button type="button" class="text-xs font-bold text-cyan-300" data-cvc-add-option>+ Thêm phương án</button>
				<p class="text-[11px] text-slate-500">Bỏ trống nội dung để xóa phương án. Đánh dấu ít nhất 1 phương án đúng.</p>
				<?php
				break;

			case 'file':
				printf( '<input type="file" id="%1$s" name="cvc_file_%2$s" accept=".pdf,.doc,.docx,.xls,.xlsx" class="block text-sm text-slate-300 file:mr-3 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:bg-cyan-500 file:text-navy-950 file:font-bold" %3$s>', esc_attr( $id ), esc_attr( $name ), $required ? 'required' : '' );
				if ( ! $is_create && ! empty( $item['file_path'] ) ) {
					echo '<p class="text-[11px] text-slate-400">Tệp hiện tại: ' . esc_html( basename( (string) $item['file_path'] ) ) . ( ! empty( $item['file_size'] ) ? ' (' . esc_html( size_format( (int) $item['file_size'] ) ) . ')' : '' ) . '. Chọn tệp mới để thay thế.</p>';
				}
				break;

			case 'slug':
				printf(
					'<input type="text" id="%1$s" name="f[%2$s]" value="%3$s" class="%4$s font-mono text-xs" pattern="[a-z0-9\-]+" title="Chỉ chữ thường không dấu, số và dấu gạch ngang" data-cvc-slug-from="f-%5$s" %6$s>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value ),
					esc_attr( $class ),
					esc_attr( (string) ( $field['from'] ?? 'title' ) ),
					$required ? 'required' : ''
				);
				echo '<p class="text-[11px] text-slate-500">Tự tạo từ tiêu đề khi thêm mới; đổi slug sẽ đổi đường dẫn công khai.</p>';
				break;

			default:
				printf( '<input type="text" id="%1$s" name="f[%2$s]" value="%3$s" class="%4$s" %5$s>', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), esc_attr( $class ), $required ? 'required' : '' );
		}
		?>
		<?php if ( ! empty( $field['help'] ) ) : ?>
			<p class="text-[11px] text-slate-500"><?php echo esc_html( $field['help'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php
}

/* ------------------------------------------------------------------ */
/* Thu payload từ form                                                 */
/* ------------------------------------------------------------------ */

/**
 * @return array<string, mixed>
 */
function cvc_admin_collect_payload( array $res, bool $is_create ): array {
	$input   = isset( $_POST['f'] ) && is_array( $_POST['f'] ) ? wp_unslash( $_POST['f'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- làm sạch theo từng kiểu bên dưới.
	$payload = array();

	foreach ( $res['fields'] as $field ) {
		$name = $field['name'];
		$type = $field['type'];
		if ( 'file' === $type ) {
			continue;
		}
		$raw = $input[ $name ] ?? null;

		switch ( $type ) {
			case 'number':
				$payload[ $name ] = ( null === $raw || '' === $raw ) ? null : (int) $raw;
				break;
			case 'decimal':
				$payload[ $name ] = ( null === $raw || '' === $raw ) ? null : (float) str_replace( ',', '.', (string) $raw );
				break;
			case 'checkbox':
				$payload[ $name ] = is_array( $raw ) ? in_array( '1', $raw, true ) : '1' === (string) $raw;
				break;
			case 'relation':
			case 'province':
			case 'admin_unit':
				$payload[ $name ] = ( null === $raw || '' === $raw ) ? null : (int) $raw;
				break;
			case 'relation_multi':
				$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $raw ) ) ) );
				if ( 'objects' === ( $field['shape'] ?? '' ) ) {
					$ids = array_map( static fn ( $id, $i ) => array( 'id' => $id, 'sort_order' => $i ), $ids, array_keys( $ids ) );
				}
				$payload[ $name ] = $ids;
				break;
			case 'options':
				$rows = array();
				foreach ( (array) $raw as $row ) {
					$text = trim( sanitize_textarea_field( (string) ( $row['option_text'] ?? '' ) ) );
					if ( '' === $text ) {
						continue;
					}
					$rows[] = array(
						'option_key'  => chr( 65 + count( $rows ) ),
						'option_text' => $text,
						'is_correct'  => ! empty( $row['is_correct'] ),
						'explanation' => ( '' !== trim( (string) ( $row['explanation'] ?? '' ) ) ) ? sanitize_textarea_field( (string) $row['explanation'] ) : null,
						'sort_order'  => count( $rows ),
					);
				}
				$payload['options'] = $rows;
				break;
			case 'html':
				$value            = trim( wp_kses_post( (string) $raw ) );
				$payload[ $name ] = '' === $value ? null : $value;
				break;
			case 'textarea':
				$value            = trim( sanitize_textarea_field( (string) $raw ) );
				$payload[ $name ] = '' === $value ? null : $value;
				break;
			case 'slug':
				$value = sanitize_title( (string) $raw );
				if ( '' === $value ) {
					$value = sanitize_title( (string) ( $input[ $field['from'] ?? 'title' ] ?? '' ) );
				}
				$payload[ $name ] = $value;
				break;
			default:
				$value            = trim( sanitize_text_field( (string) $raw ) );
				$payload[ $name ] = ( '' === $value && empty( $field['required'] ) ) ? null : $value;
		}
	}

	/*
	 * Không gửi null cho cột có giá trị mặc định ở DB (nhiều cột NOT NULL
	 * nhưng validate "nullable"): khi tạo mới bỏ mọi giá trị null; khi sửa
	 * chỉ giữ null cho trường chữ/quan hệ (để xóa được nội dung, gỡ liên kết).
	 */
	$types = wp_list_pluck( $res['fields'], 'type', 'name' );
	foreach ( $payload as $key => $value ) {
		if ( null !== $value ) {
			continue;
		}
		$type = $types[ $key ] ?? 'text';
		if ( $is_create || in_array( $type, array( 'number', 'decimal', 'select', 'checkbox' ), true ) ) {
			unset( $payload[ $key ] );
		}
	}

	return $payload;
}

/* ------------------------------------------------------------------ */
/* Handlers                                                            */
/* ------------------------------------------------------------------ */

function cvc_admin_handler_resource(): array {
	$key = sanitize_key( wp_unslash( $_POST['resource'] ?? '' ) );
	$res = cvc_admin_resource( $key );
	if ( ! $res ) {
		wp_die( esc_html__( 'Loại dữ liệu không hợp lệ.', 'cvc' ), '', array( 'response' => 400 ) );
	}

	return array( $key, $res );
}

function cvc_admin_redirect( string $url, string $type, string $message ): void {
	cvc_redirect_with_notice( $url, $type, $message, null );
}

add_action( 'admin_post_cvc_admin_save', 'cvc_admin_handle_save' );
add_action( 'admin_post_nopriv_cvc_admin_save', 'cvc_admin_handle_save' );

function cvc_admin_handle_save(): void {
	check_admin_referer( 'cvc_admin_save' );
	$token          = cvc_admin_require_staff();
	list( $key, $res ) = cvc_admin_handler_resource();
	$id             = absint( $_POST['id'] ?? 0 );
	$is_create      = 0 === $id;
	$back           = cvc_admin_url( $key, $is_create ? 'new' : $id );

	if ( ! cvc_admin_can_resource( $res, $is_create ? 'create' : 'update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền thực hiện thao tác này.' );
		return;
	}

	$payload = cvc_admin_collect_payload( $res, $is_create );

	// Không gửi trường trạng thái do quy trình quản lý (tin tuyển dụng, khóa học).
	if ( ! empty( $res['actions'] ) ) {
		unset( $payload['status'] );
	}

	if ( ! empty( $res['multipart'] ) ) {
		$files = array();
		foreach ( $res['fields'] as $field ) {
			if ( 'file' !== $field['type'] ) {
				continue;
			}
			$upload = $_FILES[ 'cvc_file_' . $field['name'] ] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			if ( is_array( $upload ) && UPLOAD_ERR_OK === (int) ( $upload['error'] ?? UPLOAD_ERR_NO_FILE ) && is_uploaded_file( (string) $upload['tmp_name'] ) ) {
				$check = wp_check_filetype( (string) $upload['name'] );
				$files[ $field['name'] ] = array( (string) $upload['tmp_name'], sanitize_file_name( (string) $upload['name'] ), $check['type'] ?: 'application/octet-stream' );
			}
		}
		$fields = array();
		foreach ( $payload as $name => $value ) {
			if ( is_bool( $value ) ) {
				$fields[ $name ] = $value ? '1' : '0';
			} elseif ( null === $value ) {
				$fields[ $name ] = '';
			} else {
				$fields[ $name ] = $value;
			}
		}
		if ( ! $is_create ) {
			$fields['_method'] = 'PUT';
		}
		$path   = $res['endpoint'] . ( $is_create ? '' : '/' . $id );
		$result = cvc_admin_client()->post_multipart( $path, $fields, $files, $token );
	} elseif ( $is_create ) {
		$result = cvc_admin_client()->post( $res['endpoint'], $payload, $token );
	} else {
		$result = cvc_admin_client()->put( $res['endpoint'] . '/' . $id, $payload, $token );
	}

	if ( ! $result['ok'] ) {
		cvc_admin_remember_input( isset( $_POST['f'] ) ? (array) wp_unslash( $_POST['f'] ) : array() ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- chỉ để hiển thị lại, luôn escape khi in.
		cvc_admin_redirect( $back . ( $is_create && ! empty( $_POST['prefill'] ) ? '?' . sanitize_text_field( wp_unslash( $_POST['prefill'] ) ) : '' ), 'error', 'Chưa lưu được: ' . cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_flush_relation_cache( $key );
	$new_id = (int) ( $result['data']['data']['id'] ?? $result['data']['id'] ?? $id );
	cvc_admin_redirect( cvc_admin_url( $key, $new_id ?: null ), 'success', $is_create ? 'Đã tạo ' . $res['singular'] . '.' : 'Đã lưu thay đổi.' );
}

add_action( 'admin_post_cvc_admin_delete', 'cvc_admin_handle_delete' );
add_action( 'admin_post_nopriv_cvc_admin_delete', 'cvc_admin_handle_delete' );

function cvc_admin_handle_delete(): void {
	check_admin_referer( 'cvc_admin_delete' );
	$token          = cvc_admin_require_staff();
	list( $key, $res ) = cvc_admin_handler_resource();
	$id             = absint( $_POST['id'] ?? 0 );

	if ( ! $id || ! cvc_admin_can_resource( $res, 'delete' ) ) {
		cvc_admin_redirect( cvc_admin_url( $key, $id ?: null ), 'error', 'Bạn không có quyền xóa bản ghi này.' );
		return;
	}

	$result = cvc_admin_client()->delete( $res['endpoint'] . '/' . $id, $token );
	if ( ! $result['ok'] ) {
		$message = 500 === (int) $result['status']
			? 'Không xóa được - bản ghi đang được dữ liệu khác sử dụng (ví dụ câu hỏi, đề thi, bài kiến thức). Hãy gỡ liên kết hoặc chuyển sang Nháp.'
			: cvc_admin_error_message( $result );
		cvc_admin_redirect( cvc_admin_url( $key, $id ), 'error', $message );
		return;
	}

	cvc_admin_flush_relation_cache( $key );
	cvc_admin_redirect( cvc_admin_url( $key ), 'success', 'Đã xóa ' . $res['singular'] . '.' );
}

add_action( 'admin_post_cvc_admin_action', 'cvc_admin_handle_action' );
add_action( 'admin_post_nopriv_cvc_admin_action', 'cvc_admin_handle_action' );

function cvc_admin_handle_action(): void {
	check_admin_referer( 'cvc_admin_action' );
	$token          = cvc_admin_require_staff();
	list( $key, $res ) = cvc_admin_handler_resource();
	$id             = absint( $_POST['id'] ?? 0 );
	$action         = sanitize_key( wp_unslash( $_POST['workflow'] ?? '' ) );
	$allowed        = wp_list_pluck( (array) ( $res['actions'] ?? array() ), 'perm', 'action' );

	if ( ! $id || ! isset( $allowed[ $action ] ) || ! cvc_admin_can( $allowed[ $action ] ) ) {
		cvc_admin_redirect( cvc_admin_url( $key, $id ?: null ), 'error', 'Thao tác không hợp lệ hoặc bạn không có quyền.' );
		return;
	}

	$result = cvc_admin_client()->post( $res['endpoint'] . '/' . $id . '/' . $action, array(), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( cvc_admin_url( $key, $id ), 'error', cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_redirect( cvc_admin_url( $key, $id ), 'success', (string) ( $result['data']['message'] ?? 'Đã cập nhật trạng thái.' ) );
}

add_action( 'admin_post_cvc_admin_exam_questions', 'cvc_admin_handle_exam_questions' );
add_action( 'admin_post_nopriv_cvc_admin_exam_questions', 'cvc_admin_handle_exam_questions' );

function cvc_admin_handle_exam_questions(): void {
	check_admin_referer( 'cvc_admin_exam_questions' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = cvc_admin_url( 'exams', $id );

	if ( ! $id || ! cvc_admin_can( 'exam.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền sửa đề thi.' );
		return;
	}

	$raw = (string) wp_unslash( $_POST['question_ids'] ?? '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- chỉ lấy số bên dưới.
	preg_match_all( '/\d+/', $raw, $m );
	$ids = array_values( array_unique( array_map( 'intval', $m[0] ) ) );

	$questions = array();
	foreach ( $ids as $i => $qid ) {
		$questions[] = array( 'question_id' => $qid, 'sort_order' => $i + 1 );
	}

	$result = cvc_admin_client()->put( '/api/admin/exams/' . $id . '/questions', array( 'questions' => $questions ), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_redirect( $back, 'success', sprintf( 'Đã cập nhật đề với %d câu hỏi.', count( $questions ) ) );
}

add_action( 'admin_post_cvc_admin_position_subjects', 'cvc_admin_handle_position_subjects' );
add_action( 'admin_post_nopriv_cvc_admin_position_subjects', 'cvc_admin_handle_position_subjects' );

function cvc_admin_handle_position_subjects(): void {
	check_admin_referer( 'cvc_admin_position_subjects' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = cvc_admin_url( 'positions', $id );

	if ( ! $id || ! cvc_admin_can( 'recruitment.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền sửa vị trí.' );
		return;
	}

	$selected = array_values( array_unique( array_filter( array_map( 'intval', (array) ( $_POST['subjects'] ?? array() ) ) ) ) );
	$optional = array_map( 'intval', (array) ( $_POST['optional'] ?? array() ) );
	$subjects = array();
	foreach ( $selected as $i => $sid ) {
		$subjects[] = array(
			'exam_subject_id' => $sid,
			'is_required'     => ! in_array( $sid, $optional, true ),
			'sort_order'      => $i,
		);
	}

	$result = cvc_admin_client()->put( '/api/admin/positions/' . $id . '/exam-subjects', array( 'subjects' => $subjects ), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_redirect( $back, 'success', 'Đã cập nhật môn thi của vị trí.' );
}

add_action( 'admin_post_cvc_admin_user_roles', 'cvc_admin_handle_user_roles' );
add_action( 'admin_post_nopriv_cvc_admin_user_roles', 'cvc_admin_handle_user_roles' );

function cvc_admin_handle_user_roles(): void {
	check_admin_referer( 'cvc_admin_user_roles' );
	$token = cvc_admin_require_staff();
	$id    = absint( $_POST['id'] ?? 0 );
	$back  = cvc_admin_url( 'users', $id );

	if ( ! $id || ! cvc_admin_can( 'user.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền phân vai trò.' );
		return;
	}

	$roles  = array_values( array_filter( array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['roles'] ?? array() ) ) ) );
	$result = cvc_admin_client()->put( '/api/admin/users/' . $id . '/roles', array( 'roles' => $roles ), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_redirect( $back, 'success', 'Đã cập nhật vai trò.' );
}

add_action( 'admin_post_cvc_admin_payment_settings', 'cvc_admin_handle_payment_settings' );
add_action( 'admin_post_nopriv_cvc_admin_payment_settings', 'cvc_admin_handle_payment_settings' );

/**
 * Lưu cấu hình VNPay/MoMo. Ô bí mật để trống = giữ nguyên giá trị đã lưu
 * (backend cũng bỏ qua giá trị đã che dạng "abcd...wxyz").
 */
function cvc_admin_handle_payment_settings(): void {
	check_admin_referer( 'cvc_admin_payment_settings' );
	$token = cvc_admin_require_staff();
	$back  = cvc_admin_url( 'thanh-toan' );
	$group = sanitize_key( wp_unslash( $_POST['group'] ?? '' ) );

	$keys = array(
		'vnpay' => array( 'vnpay.mode', 'vnpay.tmn_code', 'vnpay.hash_secret' ),
		'momo'  => array( 'momo.partner_code', 'momo.access_key', 'momo.secret_key' ),
	);
	$secret = array( 'vnpay.hash_secret', 'momo.access_key', 'momo.secret_key' );

	if ( ! isset( $keys[ $group ] ) || ! cvc_admin_can( 'settings.update' ) ) {
		cvc_admin_redirect( $back, 'error', 'Bạn không có quyền sửa cấu hình thanh toán.' );
		return;
	}

	$values = array();
	foreach ( $keys[ $group ] as $setting_key ) {
		$field = str_replace( '.', '__', $setting_key );
		$value = trim( sanitize_text_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
		if ( in_array( $setting_key, $secret, true ) && '' === $value ) {
			continue;
		}
		$values[ $setting_key ] = $value;
	}

	$result = cvc_admin_client()->put( '/api/admin/settings', array( 'group' => $group, 'values' => $values ), $token );
	if ( ! $result['ok'] ) {
		cvc_admin_redirect( $back, 'error', cvc_admin_error_message( $result ) );
		return;
	}

	cvc_admin_redirect( $back, 'success', 'Đã lưu cấu hình ' . strtoupper( $group ) . '.' );
}

add_action( 'wp_ajax_cvc_admin_units', 'cvc_admin_ajax_units' );
add_action( 'wp_ajax_nopriv_cvc_admin_units', 'cvc_admin_ajax_units' );

function cvc_admin_ajax_units(): void {
	check_ajax_referer( 'cvc_admin_ajax', 'nonce' );
	if ( ! cvc_admin_is_staff() ) {
		wp_send_json_error( array( 'message' => 'Không có quyền.' ), 403 );
	}
	$units = cvc_admin_admin_units( absint( $_GET['province_id'] ?? 0 ) );
	$out   = array();
	foreach ( $units as $uid => $name ) {
		$out[] = array( 'id' => $uid, 'name' => $name );
	}
	wp_send_json_success( $out );
}
