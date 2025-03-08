<div class="wrap">
    <h1><?php esc_html_e( 'Settings', 'wp-filepond' ); ?></h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( $args['options_group'] );
        do_settings_sections( $args['page_slug'] );
        submit_button();
        ?>
    </form>
</div>
