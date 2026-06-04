<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles communication with the Laravel backend.
 */
class Musoftware_Sms_Gateway_API {

    /**
     * Create a checkout session on the Laravel backend.
     *
     * @param float  $amount
     * @param string $currency
     * @param array  $metadata Additional metadata to pass.
     * @return array|WP_Error
     */
    public static function create_checkout_session( $amount, $currency = 'EGP', $metadata = array() ) {
        $base_url   = get_option( 'musoftware_sms_gateway_base_url', 'https://musoftwares.com' );
        $secret_key = get_option( 'musoftware_sms_gateway_secret_key', '' );

        if ( empty( $base_url ) || empty( $secret_key ) ) {
            return new WP_Error( 'missing_keys', __( 'SMS Gateway API keys or Base URL are not configured.', 'musoftware-sms-gateway' ) );
        }

        $endpoint = rtrim( $base_url, '/' ) . '/api/v1/sms-gateway/checkout/sessions';

        $body = array(
            'amount'       => $amount,
            'currency'     => $currency,
            'success_url'  => home_url( '/?sms_payment_status=success&session_id={SESSION_ID}' ),
            'cancel_url'   => home_url( '/?sms_payment_status=cancel' ),
            'webhook_url'  => rest_url( 'sms-gateway/v1/webhook' ),
            'metadata'     => $metadata,
        );

        // Include current WP user details if logged in
        if ( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $body['customer_name']  = $current_user->display_name;
            $body['customer_email'] = $current_user->user_email;
            
            $body['metadata']['wp_user_id'] = $current_user->ID;
            $body['metadata']['wp_user_email'] = $current_user->user_email;
        }

        $args = array(
            'method'  => 'POST',
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( $body ),
        );

        $response = wp_remote_request( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );
        $data          = json_decode( $response_body, true );

        if ( $response_code >= 200 && $response_code < 300 && isset( $data['id'] ) ) {
            // Save initial session to DB
            Musoftware_Sms_Gateway_DB::insert_transaction( array(
                'session_id' => $data['id'],
                'wp_user_id' => is_user_logged_in() ? get_current_user_id() : null,
                'amount'     => $amount,
                'currency'   => $currency,
                'status'     => 'open',
                'payload'    => $response_body,
            ) );

            return $data; // Contains 'url', 'id', etc.
        }

        $error_message = isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'Unknown API Error', 'musoftware-sms-gateway' );
        return new WP_Error( 'api_error', $error_message, $data );
    }
}
