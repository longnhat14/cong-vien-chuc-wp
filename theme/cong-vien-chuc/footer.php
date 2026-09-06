    </div>

    <footer class="site-footer">
        <div class="container site-footer__inner">
            <p class="site-footer__brand">
                <strong><?php bloginfo( 'name' ); ?></strong>
            </p>
            <?php cvc_render_footer_nav(); ?>
            <p class="site-footer__copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>.</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
