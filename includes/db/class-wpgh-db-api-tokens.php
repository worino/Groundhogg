<?php
/**
 * API tokens DB
 *
 * Store API Tokens
 *
 * @package     Includes
 * @subpackage  includes/DB
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_DB_Contacts Class
 *
 * @since 2.1
 */
class WPGH_DB_API_Tokens extends WPGH_DB  {

    /**
     * The name of the cache group.
     *
     * @access public
     * @since  2.8
     * @var string
     */
    public $cache_group = 'tokens';

    /**
     * Get things started
     *
     * @access  public
     * @since   2.1
     */
    public function __construct() {

        global $wpdb;

        if ( wpgh_should_if_multisite() ){
            $this->table_name  = $wpdb->prefix . 'gh_api_tokens';
        } else {
            $this->table_name  = $wpdb->base_prefix . 'gh_api_tokens';
        }

        $this->primary_key = 'ID';
        $this->version     = '1.0';
    }

    /**
     * Get columns and formats
     *
     * @access  public
     * @since   2.1
     */
    public function get_columns() {
        return array(
            'ID'            => '%d',
            'token'         => '%s',
            'user_id'       => '%d',
            'domain'        => '%s',
        );
    }

    /**
     * Get default column values
     *
     * @access  public
     * @since   2.1
     */
    public function get_column_defaults() {
        return array(
            'ID'            => 0,
            'token'         => wp_generate_password( 16 ),
            'user_id'       => 0,
            'domain'        => ''
        );
    }

    /**
     * Add a activity
     *
     * @access  public
     * @since   2.1
     */
    public function add( $data = array() ) {

        $args = wp_parse_args(
            $data,
            $this->get_column_defaults()
        );

        if( empty( $args['domain'] ) ) {
            return false;
        }

        if ( strlen( $args[ 'token' ] ) > 16 ){
            return false;
        }

        return $this->insert( $args, 'token' );
    }

    /**
     * Insert a new activity
     *
     * @access  public
     * @since   2.1
     * @return  int
     */
    public function insert( $data, $type = '' ) {
        $result = parent::insert( $data, $type );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Update activity
     *
     * @access  public
     * @since   2.1
     * @return  bool
     */
    public function update( $row_id, $data = array(), $where = '' ) {
        $result = parent::update( $row_id, $data, $where );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Delete activity
     *
     * @access  public
     * @since   2.3.1
     */
    public function delete( $id = false ) {
        $result = parent::delete( $id );

        if ( $result ) {
            $this->set_last_changed();
        }

        return $result;
    }

    /**
     * Retrieve activity like the given args
     *
     * array(
        'ID' => 1234
     * )
     *
     * array(
        'token' => 'abcd'
     * )
     *
     * array(
        'user_id' => 1234
     * )
     *
     * array(
        'domain' => 'abdc.com'
     * )
     *
     * @access  public
     * @since   2.1
     */
    public function get_tokens( $data = array(), $order = 'ID' ) {

        global  $wpdb;

        if ( ! is_array( $data ) )
            return false;

        // Initialise column format array
        $column_formats = $this->get_columns();

        // Force fields to lower case
        $data = array_change_key_case( $data );

        // White list columns
        $data = array_intersect_key( $data, $column_formats );

        $where = $this->generate_where( $data );

        if ( empty( $where ) ){

            $where = "1=1";

        }

        $results = $wpdb->get_results( "SELECT * FROM $this->table_name WHERE $where ORDER BY $order DESC" );

        return $results;

    }

    /**
     * Count the number of rows
     *
     * @param array $args
     * @return int
     */
    public function count( $args = array() )
    {

        return count( $this->get_tokens( $args ) );

    }


    /**
     * Check to see if activity like the object supplied exists
     *
     * @access  public
     * @since   2.1
     */
    public function token_exists( $data = array() ) {

        $results = $this->get_tokens( $data );

        return ! empty( $results );

    }

    /**
     * Sets the last_changed cache key for activitys.
     *
     * @access public
     * @since  2.8
     */
    public function set_last_changed() {
        wp_cache_set( 'last_changed', microtime(), $this->cache_group );
    }

    /**
     * Retrieves the value of the last_changed cache key for activitys.
     *
     * @access public
     * @since  2.8
     */
    public function get_last_changed() {
        if ( function_exists( 'wp_cache_get_last_changed' ) ) {
            return wp_cache_get_last_changed( $this->cache_group );
        }

        $last_changed = wp_cache_get( 'last_changed', $this->cache_group );
        if ( ! $last_changed ) {
            $last_changed = microtime();
            wp_cache_set( 'last_changed', $last_changed, $this->cache_group );
        }

        return $last_changed;
    }

    /**
     * Create the table
     *
     * @access  public
     * @since   2.1
     */
    public function create_table() {

        global $wpdb;

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        $sql = "CREATE TABLE " . $this->table_name . " (
        ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        token varchar(16) NOT NULL,
        domain mediumtext NOT NULL,
        PRIMARY KEY (ID),
        KEY token (token)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

        dbDelta( $sql );

        update_option( $this->table_name . '_db_version', $this->version );
    }

}