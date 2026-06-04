<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles the shortcode rendering and AJAX endpoints.
 */
class Musoftware_Sms_Gateway_Shortcode {

    /**
     * Initialize shortcode and AJAX.
     */
    public static function init() {
        add_shortcode( 'sms_payment_gateway', array( __CLASS__, 'render_shortcode' ) );
        
        // AJAX handlers for dynamic amount checkout
        add_action( 'wp_ajax_musoftware_sms_create_session', array( __CLASS__, 'ajax_create_session' ) );
        add_action( 'wp_ajax_nopriv_musoftware_sms_create_session', array( __CLASS__, 'ajax_create_session' ) );
    }

    /**
     * Render the [sms_payment_gateway] shortcode.
     */
    public static function render_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'amount'   => '',
            'currency' => 'EGP',
            'product'  => '',
        ), $atts, 'sms_payment_gateway' );

        ob_start();

        // Check if there is a success/cancel query param in the URL
        if ( isset( $_GET['sms_payment_status'] ) ) {
            $status = sanitize_text_field( $_GET['sms_payment_status'] );
            if ( $status === 'success' ) {
                echo '<div class="sms-gateway-alert sms-gateway-success"><p>' . __( 'Payment completed successfully. Thank you!', 'musoftware-sms-gateway' ) . '</p></div>';
            } elseif ( $status === 'cancel' ) {
                echo '<div class="sms-gateway-alert sms-gateway-error"><p>' . __( 'Payment was cancelled.', 'musoftware-sms-gateway' ) . '</p></div>';
            }
        }

        if ( ! empty( $atts['amount'] ) && is_numeric( $atts['amount'] ) ) {
            // Mode A: Fixed Amount - Create Session Immediately
            $metadata = array(
                'product' => $atts['product'],
            );
            
            $session = Musoftware_Sms_Gateway_API::create_checkout_session( $atts['amount'], $atts['currency'], $metadata );
            
            if ( is_wp_error( $session ) ) {
                echo '<div class="sms-gateway-error">' . esc_html( $session->get_error_message() ) . '</div>';
            } else {
                $checkout_url = $session['url'];
                self::render_iframe( $checkout_url );
            }
        } else {
            // Mode B: Dynamic Amount - Render Form
            self::render_dynamic_form( $atts['currency'], $atts['product'] );
        }

        return ob_get_clean();
    }

    /**
     * Render the iframe.
     */
    private static function render_iframe( $url ) {
        ?>
        <div class="sms-gateway-iframe-container" style="width: 100%; max-width: 500px; margin: 0 auto;">
            <iframe src="<?php echo esc_url( $url ); ?>" width="100%" height="700px" frameborder="0" allow="clipboard-write" style="border: 1px solid #e2e8f0; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);"></iframe>
        </div>
        <?php
    }

    /**
     * Render the dynamic amount form.
     */
    private static function render_dynamic_form( $currency, $product ) {
        wp_enqueue_script( 'jquery' );
        $form_id = 'sms-gateway-form-' . uniqid();
        ?>
        <div id="<?php echo esc_attr( $form_id ); ?>-wrapper" class="sms-gateway-form-wrapper" style="max-width: 400px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <form id="<?php echo esc_attr( $form_id ); ?>" class="sms-gateway-dynamic-form">
                <input type="hidden" name="action" value="musoftware_sms_create_session">
                <input type="hidden" name="currency" value="<?php echo esc_attr( $currency ); ?>">
                <input type="hidden" name="product" value="<?php echo esc_attr( $product ); ?>">
                <?php wp_nonce_field( 'sms_gateway_checkout_nonce', 'security' ); ?>
                
                <div style="margin-bottom: 15px;">
                    <label for="sms_amount_<?php echo esc_attr( $form_id ); ?>" style="display: block; margin-bottom: 5px; font-weight: bold;"><?php _e( 'Enter Amount', 'musoftware-sms-gateway' ); ?> (<?php echo esc_html( $currency ); ?>)</label>
                    <input type="number" id="sms_amount_<?php echo esc_attr( $form_id ); ?>" name="amount" min="1" step="0.01" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>
                
                <button type="submit" class="sms-gateway-submit-btn" style="width: 100%; padding: 10px; background-color: #0f172a; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    <?php _e( 'Pay Now', 'musoftware-sms-gateway' ); ?>
                </button>
                <div class="sms-gateway-loader" style="display: none; text-align: center; margin-top: 10px;"><?php _e( 'Loading...', 'musoftware-sms-gateway' ); ?></div>
                <div class="sms-gateway-error-msg" style="color: red; margin-top: 10px; display: none;"></div>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js( $form_id ); ?>').on('submit', function(e) {
                e.preventDefault();
                
                var $form = $(this);
                var $wrapper = $('#<?php echo esc_js( $form_id ); ?>-wrapper');
                var $submitBtn = $form.find('.sms-gateway-submit-btn');
                var $loader = $form.find('.sms-gateway-loader');
                var $errorMsg = $form.find('.sms-gateway-error-msg');
                
                $submitBtn.prop('disabled', true);
                $loader.show();
                $errorMsg.hide();
                
                $.ajax({
                    url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response.success && response.data.url) {
                            // Replace form with iframe
                            var iframeHtml = '<div class="sms-gateway-iframe-container" style="width: 100%;"><iframe src="' + response.data.url + '" width="100%" height="700px" frameborder="0" allow="clipboard-write" style="border: none;"></iframe></div>';
                            $wrapper.html(iframeHtml);
                            $wrapper.css({padding: 0, border: 'none'});
                        } else {
                            $errorMsg.text(response.data || '<?php _e( 'An error occurred.', 'musoftware-sms-gateway' ); ?>').show();
                            $submitBtn.prop('disabled', false);
                            $loader.hide();
                        }
                    },
                    error: function() {
                        $errorMsg.text('<?php _e( 'A network error occurred. Please try again.', 'musoftware-sms-gateway' ); ?>').show();
                        $submitBtn.prop('disabled', false);
                        $loader.hide();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX handler to create session.
     */
    public static function ajax_create_session() {
        check_ajax_referer( 'sms_gateway_checkout_nonce', 'security' );

        $amount   = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $currency = isset( $_POST['currency'] ) ? sanitize_text_field( $_POST['currency'] ) : 'EGP';
        $product  = isset( $_POST['product'] ) ? sanitize_text_field( $_POST['product'] ) : '';

        if ( $amount <= 0 ) {
            wp_send_json_error( __( 'Invalid amount.', 'musoftware-sms-gateway' ) );
        }

        $metadata = array(
            'product' => $product,
        );

        $session = Musoftware_Sms_Gateway_API::create_checkout_session( $amount, $currency, $metadata );

        if ( is_wp_error( $session ) ) {
            wp_send_json_error( $session->get_error_message() );
        }

        wp_send_json_success( array( 'url' => $session['url'] ) );
    }
}
