<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Handles database operations for the plugin.
 */
class Musoftware_Sms_Gateway_DB {

    /**
     * Install the database tables.
     */
    public static function install() {
        global $wpdb;

        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        // Database Schema
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            session_id varchar(255) NOT NULL,
            wp_user_id bigint(20) NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL,
            status varchar(50) NOT NULL,
            transaction_reference varchar(255) NULL,
            payload text NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY session_id (session_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Get the table name with prefix.
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'smsgateway_transactions';
    }

    /**
     * Insert a new transaction record.
     */
    public static function insert_transaction( $data ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        $wpdb->insert(
            $table_name,
            $data
        );
        
        return $wpdb->insert_id;
    }

    /**
     * Update a transaction record by session_id.
     */
    public static function update_transaction_by_session( $session_id, $data ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        return $wpdb->update(
            $table_name,
            $data,
            array( 'session_id' => $session_id )
        );
    }

    /**
     * Get a transaction by session_id.
     */
    public static function get_transaction_by_session( $session_id ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE session_id = %s", $session_id ) );
    }

    /**
     * Get recent transactions for the admin page.
     */
    public static function get_recent_transactions( $limit = 50, $offset = 0 ) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset ) );
    }

    /**
     * Get the total count of transactions.
     */
    public static function get_total_transactions() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        
        return $wpdb->get_var( "SELECT COUNT(id) FROM $table_name" );
    }
}
