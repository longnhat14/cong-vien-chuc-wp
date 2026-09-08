<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>document.documentElement.classList.add('js');</script>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<a class="cvc-skip-link" href="#main"><?php esc_html_e( 'Bỏ qua, tới nội dung chính', 'cong-vien-chuc' ); ?></a>
<div class="site">
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <span class="site-title__mark" aria-hidden="true">
                    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3 3 8v1h18V8L12 3Z"/>
                        <path d="M5 10.5V18M9 10.5V18M15 10.5V18M19 10.5V18"/>
                        <path d="M3.5 19.5h17"/>
                        <path d="M12 6.2a.6.6 0 1 0 0 1.2.6.6 0 0 0 0-1.2Z" fill="currentColor" stroke="none"/>
                    </svg>
                </span>
                <span class="site-title__text">
                    <?php bloginfo( 'name' ); ?>
                    <?php $tagline = get_bloginfo( 'description' ); ?>
                    <span class="site-title__tagline"><?php echo esc_html( $tagline ?: 'Kiến thức - Kỹ năng - Cơ hội phát triển' ); ?></span>
                </span>
            </a>
            <a class="site-search-link" href="<?php echo esc_url( cvc_search_url() ); ?>" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'cong-vien-chuc' ); ?>">
                <?php cvc_render_icon( 'search', 18 ); ?>
            </a>
            <button type="button" class="site-nav-toggle" aria-expanded="false" aria-controls="site-nav-menu">
                <span class="site-nav-toggle__box" aria-hidden="true"></span>
                <?php esc_html_e( 'Menu', 'cong-vien-chuc' ); ?>
            </button>
            <nav class="site-nav" id="site-nav-menu" aria-label="<?php esc_attr_e( 'Primary', 'cong-vien-chuc' ); ?>">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'fallback_cb'    => 'cvc_default_nav_fallback',
                    )
                );
                ?>
                <?php cvc_render_header_auth_area(); ?>
            </nav>
        </div>
    </header>

    <div class="site-content">
