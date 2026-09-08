    </div>

    <footer class="site-footer">
        <div class="container site-footer__grid">
            <div class="site-footer__brand-col">
                <p class="site-footer__brand">
                    <span class="site-footer__mark" aria-hidden="true">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3 3 8v1h18V8L12 3Z"/>
                            <path d="M5 10.5V18M9 10.5V18M15 10.5V18M19 10.5V18"/>
                            <path d="M3.5 19.5h17"/>
                        </svg>
                    </span>
                    <strong><?php bloginfo( 'name' ); ?></strong>
                </p>
                <?php $footer_tagline = get_bloginfo( 'description' ); ?>
                <p class="site-footer__tagline"><?php echo esc_html( $footer_tagline ?: 'Kiến thức - Kỹ năng - Cơ hội phát triển' ); ?></p>
                <p class="site-footer__about">Nền tảng hỗ trợ công chức, viên chức học tập, ôn thi và cập nhật thông tin tuyển dụng.</p>
            </div>

            <div class="site-footer__nav-col">
                <p class="site-footer__col-title">Khám phá</p>
                <?php cvc_render_footer_nav(); ?>
            </div>
        </div>

        <div class="container site-footer__bottom">
            <p class="site-footer__copyright">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. Tất cả quyền được bảo lưu.</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
