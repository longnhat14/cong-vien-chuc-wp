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
                <?php bloginfo( 'name' ); ?>
                <?php $tagline = get_bloginfo( 'description' ); ?>
                <?php if ( $tagline ) : ?>
                    <span class="site-title__tagline"><?php echo esc_html( $tagline ); ?></span>
                <?php endif; ?>
            </a>
            <a class="site-search-link" href="<?php echo esc_url( cvc_search_url() ); ?>" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'cong-vien-chuc' ); ?>">
                <span aria-hidden="true">&#128269;</span>
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
            </nav>
        </div>
    </header>

    <div class="site-content">
