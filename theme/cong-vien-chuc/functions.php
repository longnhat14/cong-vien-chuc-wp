<?php
/**
 * Bootstrap theme Công Viên Chức.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/inc/api/class-cvc-api-client.php';
require_once __DIR__ . '/inc/api/class-cvc-api-service.php';
require_once __DIR__ . '/inc/services/class-cvc-course-service.php';
require_once __DIR__ . '/inc/services/class-cvc-topic-service.php';
require_once __DIR__ . '/inc/services/class-cvc-knowledge-service.php';
require_once __DIR__ . '/inc/services/class-cvc-recruitment-service.php';
require_once __DIR__ . '/inc/services/class-cvc-exam-service.php';
require_once __DIR__ . '/inc/services/class-cvc-legal-document-service.php';
require_once __DIR__ . '/inc/services/class-cvc-search-service.php';
require_once __DIR__ . '/inc/services/class-cvc-goal-service.php';
require_once __DIR__ . '/inc/services/class-cvc-bookmark-service.php';
require_once __DIR__ . '/inc/services/class-cvc-profile-service.php';
require_once __DIR__ . '/inc/services/class-cvc-learning-path-service.php';
require_once __DIR__ . '/inc/services/class-cvc-recommendation-service.php';
require_once __DIR__ . '/inc/services/class-cvc-recruitment-match-service.php';
require_once __DIR__ . '/inc/services/class-cvc-notification-service.php';
require_once __DIR__ . '/inc/services/class-cvc-exam-attempt-service.php';
require_once __DIR__ . '/inc/template-tags.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/actions.php';
require_once __DIR__ . '/inc/exam.php';
require_once __DIR__ . '/inc/engagement.php';
require_once __DIR__ . '/inc/routes.php';
require_once __DIR__ . '/inc/dev-tools.php';

add_action( 'wp_enqueue_scripts', 'cvc_enqueue_assets' );

function cvc_enqueue_assets(): void {
	wp_enqueue_style(
		'cvc-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_script(
		'cvc-nav',
		get_theme_file_uri( '/assets/js/nav.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_enqueue_script(
		'cvc-engagement',
		get_theme_file_uri( '/assets/js/engagement.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		true
	);

	wp_localize_script(
		'cvc-engagement',
		'cvcEngagement',
		array(
			'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'cvc_track_event' ),
			'loggedIn'   => cvc_is_logged_in(),
		)
	);
}

add_action( 'after_setup_theme', 'cvc_theme_setup' );

function cvc_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption' ) );
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'cong-vien-chuc' ),
		)
	);
}
