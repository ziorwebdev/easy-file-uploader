<div class="wrap">
    <h2><?php esc_html_e( 'WP FilePond Settings', 'filepond-wp-integration' ); ?></h2>
    <div class="wp-filepond-settings-container">
        <form method="post" action="options.php">
            <?php
            settings_fields( $args['options_group'] );
            do_settings_sections( $args['page_slug'] );
            submit_button();
            ?>
        </form>
        <?php do_action( 'wp_filepond_settings_after' ); ?>
    </div>
</div>
<style>
.wp-filepond-settings-container {
    display: flex;
    justify-content: space-between; /* Pushes form left & card right */
    align-items: flex-start; /* Aligns items at the top */
    gap: 20px; /* Adds space between elements */
}

.wp-filepond-settings-container form {
    width: auto; /* Auto width based on content */
    flex-grow: 1; /* Allows it to take available space */
}

/* Responsive Design */
@media (max-width: 1024px) { /* Tablet (portrait & smaller) */
    .wp-filepond-settings-container {
        flex-direction: column; /* Stack items vertically */
    }

    .wp-filepond-settings-container form,
    .wp-filepond-pro-card {
        width: 100%; /* Full width */
    }
}
</style>
