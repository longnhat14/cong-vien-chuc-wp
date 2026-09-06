    </div>

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <div>
                <p class="site-footer__brand">
                    <strong><?php bloginfo( 'name' ); ?></strong>
                </p>
                <?php $footer_tagline = get_bloginfo( 'description' ); ?>
                <?php if ( $footer_tagline ) : ?>
                    <p class="site-footer__tagline"><?php echo esc_html( $footer_tagline ); ?></p>
                <?php endif; ?>
            </div>
            <?php cvc_render_footer_nav(); ?>
            <p class="site-footer__copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
