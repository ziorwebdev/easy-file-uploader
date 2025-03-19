<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
    <h2><?php esc_html_e( 'Easy DragDrop File Uploader Settings', 'easy-dragdrop-file-uploader' ); ?></h2>
    <div class="dragdrop-settings-container">
        <form method="post" action="options.php">
            <?php
            settings_fields( $args['options_group'] );
            do_settings_sections( $args['page_slug'] );
            submit_button();
            ?>
        </form>
        <?php do_action( 'easy_dragdrop_settings_after' ); ?>
    </div>
</div>
<style>
.dragdrop-settings-container {
    display: flex;
    justify-content: space-between; /* Pushes form left & card right */
    align-items: flex-start; /* Aligns items at the top */
    gap: 20px; /* Adds space between elements */
}

.dragdrop-settings-container form {
    width: auto; /* Auto width based on content */
    flex-grow: 1; /* Allows it to take available space */
}

/* Responsive Design */
@media (max-width: 1024px) { /* Tablet (portrait & smaller) */
    .dragdrop-settings-container {
        flex-direction: column; /* Stack items vertically */
    }

    .dragdrop-settings-container form,
    .dragdrop-pro-card {
        width: 100%; /* Full width */
    }
}
</style>
