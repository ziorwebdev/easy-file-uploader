<div class="wrap">
    <h2><?php esc_html_e( 'WP FilePond Settings', 'filepond-wp-integration' ); ?></h2>
    <form method="post" action="options.php">
        <?php
        settings_fields( $args['options_group'] );
        do_settings_sections( $args['page_slug'] );
        submit_button();
        ?>
    </form>
    <?php do_action( 'wp_filepond_settings_after' ); ?>
</div>
