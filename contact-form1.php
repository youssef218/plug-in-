<?php
/*
Plugin Name: Contact Form
Description: Plugin to add a msg contact form to dash my site
Version: 1.0
*/
// Exit if accessed directly
function my_plugin_enqueue_scripts() {
    wp_enqueue_style('bootstrap-css', plugins_url('bootstrap.min.css', __FILE__));
    wp_enqueue_script('bootstrap-js', plugins_url('bootstrap.bundle.min.js', __FILE__), array('jquery'), false, true);
}
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');
add_action( 'admin_enqueue_scripts', 'my_plugin_enqueue_scripts' );

if (!defined('ABSPATH')) {
    exit;
}
// Create table on plugin activation
function contact_form_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        sujet varchar(50) NOT NULL,
        nom varchar(50) NOT NULL,
        prenom varchar(50) NOT NULL,
        email varchar(50) NOT NULL,
        message text NOT NULL,
        date_envoi datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'contact_form_create_table');
// Delete table on plugin deactivation
function contact_form_delete_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}
register_deactivation_hook(__FILE__, 'contact_form_delete_table');

function contact_form_shortcode()
{
    ob_start(); // Capture le contenu HTML de la fonction
?>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <div class="form-group">
            <label for="subject"><?php esc_html_e('Subject', 'contact-form'); ?></label>
            <input type="text" name="subject" id="subject" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="name"><?php esc_html_e('Name', 'contact-form'); ?></label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="firstname"><?php esc_html_e('First name', 'contact-form'); ?></label>
            <input type="text" name="firstname" id="firstname" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email"><?php esc_html_e('Email', 'contact-form'); ?></label>
            <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="message"><?php esc_html_e('Message', 'contact-form'); ?></label>
            <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
        </div>
        <input type="hidden" name="action" value="submit_contact_form">
       

        <?php wp_nonce_field('submit_contact_form', 'contact_form_nonce'); ?>
        <button type="submit" class="btn btn-primary"><?php esc_html_e('Submit', 'contact-form'); ?></button>
    </form>
<?php
    return ob_get_clean(); // Retourne le contenu HTML capturé
}
function register_contact_form_shortcode()
{
    add_shortcode('contact_form', 'contact_form_shortcode');
}
add_action('init', 'register_contact_form_shortcode');
// Créer le menu de la page des réponses
function cf_add_menu_page()
{
    add_menu_page('contact-form', 'All Forms', 'manage_options', 'cf_responses_page', 'cf_render_responses_page', 'dashicons-email-alt', 1);
    add_submenu_page('cf_responses_page', 'Contact Form ALl messages', 'All messages', 'manage_options', 'cf_Message_page', 'cf_render_Message_page');
    add_submenu_page('cf_responses_page', 'Contact Form Settings', 'Customize', 'manage_options', 'cf_settings_page', 'cf_render_settings_page');
}
function cf_render_settings_page() {
    // Add your settings page content here
    echo "hi";
}
function cf_render_Message_page() {
    // Add your settings page content here
    echo "hello";
}

add_action('admin_menu', 'cf_add_menu_page');
function contact_form_submit()
{
    global $wpdb;
    // Verify the nonce
    if (!isset($_POST['contact_form_nonce']) || !wp_verify_nonce($_POST['contact_form_nonce'], 'submit_contact_form')) {
        wp_die(esc_html__('Security check failed. Please try again.', 'contact-form'));
    }
    // Retrieve the form data
    $subject = sanitize_text_field($_POST['subject']);
    $name = sanitize_text_field($_POST['name']);
    $firstname = sanitize_text_field($_POST['firstname']);
    $email = sanitize_email($_POST['email']);
    $message = wp_kses_post($_POST['message']);
    // Insert the data into the database
    $table_name = $wpdb->prefix . 'contact_form';
    $wpdb->insert(
        $table_name,
        array(
            'sujet' => $subject,
            'nom' => $name,
            'prenom' => $firstname,
            'email' => $email,
            'message' => $message,
        ),
        array('%s', '%s', '%s', '%s', '%s')
    );
    // Redirect the user back to the contact form
    wp_redirect(home_url('/contact/'));
    exit;
}
add_action('admin_post_nopriv_submit_contact_form', 'contact_form_submit');
add_action('admin_post_submit_contact_form', 'contact_form_submit');
function cf_render_responses_page()
{
    // Vérifier que l'utilisateur est autorisé à afficher la page
    if (!current_user_can('manage_options')) {
        return;
    }
    global $wpdb;
    $table_name = $wpdb->prefix . 'contact_form';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    // Output the page content
    echo '<div class="wrap ">';
    echo '<h1>' . esc_html__('Contact Form Responses', 'contact-form') . '</h1>';
    echo '<p>' . esc_html__('View and manage responses submitted through the contact form.') . '</p>';
    // Display the table of responses
    echo '<table class="wp-list-table widefat fixed striped ">';
    echo '<thead>';
    echo '<tr>';
    echo '<th style="width: 2rem;">' . esc_html__('ID', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('Subject', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('Name', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('First name', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('Email', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('Message', 'contact-form') . '</th>';
    echo '<th>' . esc_html__('Date', 'contact-form') . '</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    foreach ($results as $row) {
        echo '<tr>';
        echo '<td>' . $row->id . '</td>';
        echo '<td>' . $row->sujet . '</td>';
        echo '<td>' . $row->nom . '</td>';
        echo '<td>' . $row->prenom . '</td>';
        echo '<td>' . $row->email . '</td>';
        echo '<td>' . $row->message . '</td>';
        echo '<td>' . $row->date_envoi . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}



?>