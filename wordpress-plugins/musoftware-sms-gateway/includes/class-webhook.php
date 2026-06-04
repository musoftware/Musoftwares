<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles Webhook callbacks from the Laravel backend.
 */
class Musoftware_Sms_Gateway_Webhook {

    /**
     * Initialize the Webhook class.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
    }

    /**
     * Register the REST route for the webhook.
     */
    public static function register_rest_route() {
        register_rest_route( 'sms-gateway/v1', '/webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'handle_webhook' ),
            'permission_callback' => '__return_true', // Publicly accessible, we rely on the session_id or signature validation if any
        ) );
    }

    /**
     * Handle the incoming webhook.
     */
    public static function handle_webhook( WP_REST_Request $request ) {
        $raw_body = $request->get_body();
        $secret   = get_option( 'musoftware_sms_gateway_webhook_secret', '' );

        // Require signature validation if a secret key is configured.
        if ( ! empty( $secret ) ) {
            // WordPress converts HTTP header names: 'X-Musoftware-Signature' becomes 'x_musoftware_signature'
            $signature = $request->get_header( 'x_musoftware_signature' );
            
            if ( empty( $signature ) ) {
                return new WP_REST_Response( array( 'error' => 'Missing Signature Header' ), 401 );
            }

            $expected_signature = hash_hmac( 'sha256', $raw_body, $secret );

            if ( ! hash_equals( $expected_signature, $signature ) ) {
                return new WP_REST_Response( array( 'error' => 'Invalid Signature' ), 401 );
            }
        }

        $payload = $request->get_json_params();

        // The webhook payload from the Laravel backend needs to contain the session_id
        // Usually, the payload might look like:
        // { "event": "checkout.session.completed", "data": { "session_id": "cs_live_...", "status": "complete", "transaction_reference": "..." } }
        
        // Let's defensively parse it based on expected structures
        $event      = isset( $payload['event'] ) ? $payload['event'] : '';
        $session_id = isset( $payload['data']['id'] ) ? $payload['data']['id'] : ( isset( $payload['session_id'] ) ? $payload['session_id'] : '' );
        $status     = isset( $payload['data']['status'] ) ? $payload['data']['status'] : ( isset( $payload['status'] ) ? $payload['status'] : '' );
        
        if ( empty( $session_id ) ) {
            // Fallback: Check if it's directly in the root
            $session_id = isset( $payload['id'] ) ? $payload['id'] : '';
        }

        if ( empty( $session_id ) ) {
            return new WP_REST_Response( array( 'error' => 'Missing session ID' ), 400 );
        }

        // Only process successful events
        if ( $status === 'complete' || $event === 'checkout.session.completed' ) {
            $transaction = Musoftware_Sms_Gateway_DB::get_transaction_by_session( $session_id );
            
            if ( ! $transaction ) {
                // If it doesn't exist (e.g. created somewhere else, or missed), create it
                $wp_user_id = isset( $payload['data']['metadata']['wp_user_id'] ) ? $payload['data']['metadata']['wp_user_id'] : null;
                $amount     = isset( $payload['data']['amount'] ) ? $payload['data']['amount'] : 0;
                $currency   = isset( $payload['data']['currency'] ) ? $payload['data']['currency'] : 'EGP';

                Musoftware_Sms_Gateway_DB::insert_transaction( array(
                    'session_id' => $session_id,
                    'wp_user_id' => $wp_user_id,
                    'amount'     => $amount,
                    'currency'   => $currency,
                    'status'     => 'complete',
                    'payload'    => wp_json_encode( $payload ),
                ) );
                
                $transaction = Musoftware_Sms_Gateway_DB::get_transaction_by_session( $session_id );
            } else {
                // Update existing
                Musoftware_Sms_Gateway_DB::update_transaction_by_session( $session_id, array(
                    'status'  => 'complete',
                    'payload' => wp_json_encode( $payload ),
                ) );
            }

            // Assign User Role if configured
            $target_role = get_option( 'musoftware_sms_gateway_target_role', '' );
            $wp_user_id  = $transaction->wp_user_id;
            
            if ( ! empty( $target_role ) && $wp_user_id ) {
                $user = get_userdata( $wp_user_id );
                if ( $user ) {
                    $user->add_role( $target_role );
                }
            }

            // Trigger global hook for developers
            do_action( 'sms_gateway_payment_success', $payload, $transaction );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
