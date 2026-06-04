<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the plugin settings and admin menus.
 */
class Musoftware_Sms_Gateway_Settings {

    /**
     * Initialize the settings class.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Add admin menus.
     */
    public static function add_admin_menu() {
        // Main menu
        add_menu_page(
            __( 'SMS Gateway', 'musoftware-sms-gateway' ),
            __( 'SMS Gateway', 'musoftware-sms-gateway' ),
            'manage_options',
            'musoftware-sms-gateway',
            array( __CLASS__, 'render_settings_page' ),
            'dashicons-money-alt',
            56
        );

        // Submenu for Settings
        add_submenu_page(
            'musoftware-sms-gateway',
            __( 'Settings', 'musoftware-sms-gateway' ),
            __( 'Settings', 'musoftware-sms-gateway' ),
            'manage_options',
            'musoftware-sms-gateway',
            array( __CLASS__, 'render_settings_page' )
        );

        // Submenu for Transactions
        add_submenu_page(
            'musoftware-sms-gateway',
            __( 'Transactions', 'musoftware-sms-gateway' ),
            __( 'Transactions', 'musoftware-sms-gateway' ),
            'manage_options',
            'musoftware-sms-gateway-transactions',
            array( __CLASS__, 'render_transactions_page' )
        );
    }

    /**
     * Register plugin settings.
     */
    public static function register_settings() {
        register_setting( 'musoftware_sms_gateway_settings', 'musoftware_sms_gateway_base_url' );
        register_setting( 'musoftware_sms_gateway_settings', 'musoftware_sms_gateway_secret_key' );
        register_setting( 'musoftware_sms_gateway_settings', 'musoftware_sms_gateway_publishable_key' );
        register_setting( 'musoftware_sms_gateway_settings', 'musoftware_sms_gateway_target_role' );
    }

    /**
     * Render the settings page.
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $base_url = get_option( 'musoftware_sms_gateway_base_url', 'https://musoftwares.com' );
        $secret_key = get_option( 'musoftware_sms_gateway_secret_key', '' );
        $publishable_key = get_option( 'musoftware_sms_gateway_publishable_key', '' );
        $target_role = get_option( 'musoftware_sms_gateway_target_role', '' );
        
        // Get all WP roles
        global $wp_roles;
        $roles = $wp_roles->roles;
        
        $webhook_url = rest_url( 'sms-gateway/v1/webhook' );

        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'musoftware_sms_gateway_settings' );
                do_settings_sections( 'musoftware_sms_gateway_settings' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="musoftware_sms_gateway_base_url"><?php _e( 'API Base URL', 'musoftware-sms-gateway' ); ?></label></th>
                        <td>
                            <input type="text" name="musoftware_sms_gateway_base_url" id="musoftware_sms_gateway_base_url" value="<?php echo esc_attr( $base_url ); ?>" class="regular-text" />
                            <p class="description"><?php _e( 'The base URL of the Laravel ERP (e.g. https://musoftwares.com).', 'musoftware-sms-gateway' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="musoftware_sms_gateway_secret_key"><?php _e( 'Secret Key', 'musoftware-sms-gateway' ); ?></label></th>
                        <td>
                            <input type="password" name="musoftware_sms_gateway_secret_key" id="musoftware_sms_gateway_secret_key" value="<?php echo esc_attr( $secret_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="musoftware_sms_gateway_publishable_key"><?php _e( 'Publishable Key', 'musoftware-sms-gateway' ); ?></label></th>
                        <td>
                            <input type="text" name="musoftware_sms_gateway_publishable_key" id="musoftware_sms_gateway_publishable_key" value="<?php echo esc_attr( $publishable_key ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="webhook_url"><?php _e( 'Webhook URL', 'musoftware-sms-gateway' ); ?></label></th>
                        <td>
                            <input type="text" id="webhook_url" value="<?php echo esc_attr( $webhook_url ); ?>" class="regular-text" readonly onclick="this.select();" />
                            <p class="description"><?php _e( 'Copy this URL and paste it into the Webhook settings in your Musoftwares SMS Gateway dashboard.', 'musoftware-sms-gateway' ); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="musoftware_sms_gateway_target_role"><?php _e( 'Assign Role on Payment', 'musoftware-sms-gateway' ); ?></label></th>
                        <td>
                            <select name="musoftware_sms_gateway_target_role" id="musoftware_sms_gateway_target_role">
                                <option value=""><?php _e( '-- Do not assign any role --', 'musoftware-sms-gateway' ); ?></option>
                                <?php foreach ( $roles as $role_key => $role_details ) : ?>
                                    <option value="<?php echo esc_attr( $role_key ); ?>" <?php selected( $target_role, $role_key ); ?>>
                                        <?php echo esc_html( translate_user_role( $role_details['name'] ) ); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'If a role is selected, the plugin will automatically assign this role to the logged-in user upon successful payment.', 'musoftware-sms-gateway' ); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button( __( 'Save Settings', 'musoftware-sms-gateway' ) ); ?>
            </form>
            
            <hr />
            
            <h2><?php _e( 'Shortcode Usage', 'musoftware-sms-gateway' ); ?></h2>
            <p><?php _e( 'You can use the following shortcodes in your pages or posts:', 'musoftware-sms-gateway' ); ?></p>
            <ul>
                <li><code>[sms_payment_gateway]</code> - <?php _e( 'Renders a dynamic form where the user enters the amount before paying.', 'musoftware-sms-gateway' ); ?></li>
                <li><code>[sms_payment_gateway amount="100" currency="EGP"]</code> - <?php _e( 'Renders an immediate iframe for a fixed amount.', 'musoftware-sms-gateway' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render the transactions page.
     */
    public static function render_transactions_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $transactions = Musoftware_Sms_Gateway_DB::get_recent_transactions( 50 );
        ?>
        <div class="wrap">
            <h1><?php _e( 'SMS Gateway Transactions', 'musoftware-sms-gateway' ); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'ID', 'musoftware-sms-gateway' ); ?></th>
                        <th><?php _e( 'Session ID', 'musoftware-sms-gateway' ); ?></th>
                        <th><?php _e( 'User ID', 'musoftware-sms-gateway' ); ?></th>
                        <th><?php _e( 'Amount', 'musoftware-sms-gateway' ); ?></th>
                        <th><?php _e( 'Status', 'musoftware-sms-gateway' ); ?></th>
                        <th><?php _e( 'Date', 'musoftware-sms-gateway' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $transactions ) ) : ?>
                        <tr>
                            <td colspan="6"><?php _e( 'No transactions found.', 'musoftware-sms-gateway' ); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $transactions as $txn ) : ?>
                            <tr>
                                <td><?php echo esc_html( $txn->id ); ?></td>
                                <td><?php echo esc_html( $txn->session_id ); ?></td>
                                <td><?php echo esc_html( $txn->wp_user_id ? $txn->wp_user_id : '-' ); ?></td>
                                <td><?php echo esc_html( $txn->amount . ' ' . $txn->currency ); ?></td>
                                <td>
                                    <?php 
                                        $status_color = $txn->status === 'complete' ? 'green' : ( $txn->status === 'open' ? 'orange' : 'red' );
                                        echo '<span style="color:' . esc_attr( $status_color ) . ';font-weight:bold;">' . esc_html( ucfirst( $txn->status ) ) . '</span>'; 
                                    ?>
                                </td>
                                <td><?php echo esc_html( $txn->created_at ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
