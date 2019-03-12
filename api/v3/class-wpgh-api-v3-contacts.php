<?php
/**
 * Groundhogg API Contacts
 *
 * This class provides a front-facing JSON API that makes it possible to
 * query data from the other application application.
 *
 * @package     WPGH
 * @subpackage  Classes/API
 *
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WPGH_API_V2_CONTACTS Class
 *
 * Renders API returns as a JSON
 *
 * @since  1.5
 */
class WPGH_API_V3_CONTACTS extends WPGH_API_V3_BASE
{

    public function register_routes()
    {
        register_rest_route('gh/v3', '/contacts', [
            [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [ $this, 'get_contacts' ],
                'permission_callback' => [ $this, 'auth' ],
                'args'=> [
                    'query' => [
                        'required' => false,
                        'description' => _x( 'An array of query args. See WPGH_Contact_Query for acceptable arguments.', 'api', 'groundhogg' ),
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [ $this, 'create_contact' ],
                'permission_callback' => [ $this, 'auth' ],
                'args'=> [
                    'contact' => [
                        'required'    => true,
                        'description' => _x( 'Contains list of contact arguments. Please visit www.groundhogg.io for full list of accepted arguments.', 'api', 'groundhogg' )
                    ]
                ]
            ],
            [
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => [ $this, 'update_contact' ],
                'permission_callback' => [ $this, 'auth' ],
                'args'=> [
                    'id_or_email' => [
                        'required'    => true,
                        'description' => _x('The ID or email of the contact you want to delete.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                    'contact'    => [
                        'required'    => true,
                        'description' => _x('Array of updated contact details.', 'api','groundhogg')
                    ],
                ]
            ],
            [
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => [ $this, 'delete_contact' ],
                'permission_callback' => [ $this, 'auth' ],
                'args'=> [
                    'id_or_email' => [
                        'required'    => false,
                        'description' => _x('The ID or email of the contact you want to delete.','api','groundhogg'),
                    ],
                    'by_user_id' => [
                        'required'    => false,
                        'description' => _x( 'Search using the user ID.', 'api', 'groundhogg' ),
                    ],
                ]
            ],
        ] );

    }

    /**
     * Get a contact which will look good in a JSON response.
     *
     * @param $id
     * @return object
     */
    public function get_contact_for_rest_response( $id )
    {
        $raw_contact = WPGH()->contacts->get( $id );
        $contact_meta = WPGH()->contact_meta->get_meta($id);
        foreach ( $contact_meta as $key => $value ){
            $contact_meta[ $key ] = array_pop( $value );
        }
        $raw_contact->meta = $contact_meta;
        return $raw_contact;
    }

    /**
     * Takes a single parameter 'query' or empty to return a list of contacts.
     *
     * @param WP_REST_Request $request
     * @return WP_Error|WP_REST_Response
     */
    public function get_contacts(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'view_contacts' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        /* CHECK IF SINGLE FIRST */
        $contact = self::get_contact_from_request( $request );

        /* Is single */
        if ( $contact ){
            if ( is_wp_error( $contact ) ){
                return $contact;
            }

            return rest_ensure_response( $this->get_contact_for_rest_response( $contact->ID ) );
        }

        $query = $request->get_param( 'query' ) ? (array) $request->get_param( 'query' ) : [];
        $contact_query = new WPGH_Contact_Query();
        $contacts = $contact_query->query( $query );

        $response = [];

        foreach ( $contacts as $contact ) {
            $response[ $contact->ID ] = $this->get_contact_for_rest_response( $contact->ID );
        }

        return rest_ensure_response( $response );
    }

    /**
     * Update a contact given their whatever...
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function create_contact(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'add_contacts' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $args = (array) $request->get_param( 'contact' );

        $meta = null;

        if ( $args[ 'meta' ] ) {
            $meta = $args['meta'];
            unset( $args['meta'] );
        }

        if( ! isset( $args['email'] ) || ! is_email($args['email'] ) ) {
            return self::ERROR_400('invalid_email', _x( 'Please provide a valid email address.', 'api', 'groundhogg' ) );
        }

        $args = array_map('sanitize_text_field', $args );

        $contact_id = WPGH()->contacts->add( $args );

        if ( $meta ) {
            foreach( $meta as $key => $value ) {
                WPGH()->contact_meta->update_meta( $contact_id, sanitize_key( $key ), sanitize_text_field( $value ) );
            }
        }

        $contact = $this->get_contact_for_rest_response( $contact_id->ID );

        return rest_ensure_response(array(
            'status'  => 'success',
            'message' => _x('Contact added successfully.', 'api', 'groundhogg'),
            'contact' => $contact
        ));

    }

	/**
	 * Updates a contact given a contact array
	 * Can also apply & remove tags
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_Error|WP_REST_Response
	 */
    public function update_contact(WP_REST_Request $request)
    {
        if ( ! current_user_can( 'edit_contacts' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if ( is_wp_error( $contact ) ){
            return $contact;
        }

        $args = (array) $request->get_param( 'contact' );

        /* UPDATE CONTACT */
        $meta = null;

        if ( $args[ 'meta' ] ) {
            $meta = $args['meta'];
            unset( $args['meta'] );
        }

        if( isset( $args['email'] ) && ! is_email($args['email'] ) ) {
            return self::ERROR_400('invalid_email', _x( 'Please provide a valid email address.', 'api', 'groundhogg' ) );
        }

        if ( isset( $args[ 'email' ] ) && $args[ 'email' ] !== $contact->email && WPGH()->contacts->exists( $args[ 'email' ] ) ){
            return self::ERROR_400('email_in_use', _x( 'This email address already belongs to another contact.', 'api', 'groundhogg' ) );
        }

        if ( isset( $args[ 'optin_status' ] ) && intval( $args[ 'optin_status' ] ) !== $contact->optin_status ){
            $contact->change_marketing_preference( intval( $args[ 'optin_status' ] ) );
        }

        //  ---------------  Insert operation --------
        $args = array_map('sanitize_text_field', $args );

        //adding data in contact table
        $contact->update( $args );

        // insert data in contact meta table if users send meta data
        if ( $meta ) {
            foreach( $meta as $key => $value ) {
                $contact->update_meta( sanitize_key( $key ), sanitize_text_field( $value ) );
            }
        }

        $contact = $this->get_contact_for_rest_response( $contact->ID );

        return rest_ensure_response(array(
            'status'  => 'success',
            'message' => _x('Contact updated successfully.', 'api', 'groundhogg'),
            'contact' => $contact
        ));
    }

    /**
     * Delete contacts
     *
     * @param WP_REST_Request $request
     * @return mixed|WP_Error|WP_REST_Response
     */
    public function delete_contact( WP_REST_Request $request )
    {
        if ( ! current_user_can( 'delete_contacts' ) ){
            return self::ERROR_INVALID_PERMISSIONS();
        }

        $contact = self::get_contact_from_request( $request );

        if ( is_wp_error( $contact ) ){
            return $contact;
        }

        $yes = WPGH()->contacts->delete( $contact->ID );

        if ( ! $yes ){
            return self::ERROR_UNKNOWN();
        }

        return rest_ensure_response( [
            'status'  => 'success',
            'message' => _x('Contact deleted successfully.', 'api', 'groundhogg'),
        ] );

    }

}