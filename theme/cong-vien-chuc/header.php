<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
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
            <nav class="site-nav" aria-label="<?php esc_attr_e( 'Primary', 'cong-vien-chuc' ); ?>">
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
