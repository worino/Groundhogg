<?php
/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


class WPGH_Emails_Page
{
    /**
     * @var WPGH_Notices
     */
    public $notices;

    /**
     * WPGH_Emails_Page constructor.
     */
    function __construct()
    {
        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_emails' ){

            $this->notices = WPGH()->notices;

            add_action( 'init' , array( $this, 'process_action' )  );

        }
    }

    /**
     * Get affected emails
     *
     * @return array|bool
     */
    function get_emails()
    {
        $emails = isset( $_REQUEST['email'] ) ? $_REQUEST['email'] : null;

        if ( ! $emails )
            return false;

        return is_array( $emails )? array_map( 'intval', $emails ) : array( intval( $emails ) );
    }

    /**
     * Get the action
     *
     * @return bool|string
     */
    function get_action()
    {
        if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) )
            return false;

        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    /**
     * Get the last completed action
     *
     * @return mixed
     */
    function get_previous_action()
    {
        $action = get_transient( 'gh_last_action' );

        delete_transient( 'gh_last_action' );

        return $action;
    }

    /**
     * Get the title of the current page
     */
    function get_title()
    {
        switch ( $this->get_action() ){
            case 'add':
                _e( 'Add Email' , 'groundhogg' );
                break;
            case 'edit':
                _e( 'Edit Email' , 'groundhogg' );
                break;
            default:
                _e( 'Emails', 'groundhogg' );
        }
    }

    /**
     * Process the current action based on the admin view and any post variables
     */
    function process_action()
    {
        if ( ! $this->get_action() || ! $this->verify_action() || ! current_user_can( 'gh_manage_emails' ) )
            return;

        $base_url = remove_query_arg( array( '_wpnonce', 'action' ), wp_get_referer() );

        switch ( $this->get_action() )
        {
            case 'add':

                if ( ! empty( $_POST ) ) {

                    $this->add_email();

                }

                break;

            case 'edit':

                if ( ! empty( $_POST ) ){

                    $this->update_email();

                }

                break;

            case 'trash':

                foreach ( $this->get_emails() as $id ) {

                    $args = array( 'status' => 'trash' );

                    WPGH()->emails->update( $id, $args );

                }

	            $this->notices->add(
		            esc_attr( 'trashed' ),
		            sprintf( "%s %d %s",
			            __( 'Trashed' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_trash_emails' );

                break;

            case 'delete':

                foreach ( $this->get_emails() as $id ){
                    WPGH()->emails->delete( $id );
                }

                $this->notices->add(
		            esc_attr( 'deleted' ),
		            sprintf( "%s %d %s",
			            __( 'Deleted' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_delete_emails' );

                break;

            case 'restore':

                foreach ( $this->get_emails() as $id )
                {
                    $args = array( 'status' => 'draft' );

                    WPGH()->emails->update( $id, $args );                }

                $this->notices->add(
		            esc_attr( 'restored' ),
		            sprintf( "%s %d %s",
			            __( 'Restored' ),
			            count( $this->get_emails() ),
			            __( 'Emails', 'groundhogg' ) ),
		            'success'
	            );

                do_action( 'wpgh_restore_emails' );

                break;

        }

        set_transient( 'gh_last_action', $this->get_action(), 30 );

        if ( $this->get_action() === 'edit' || $this->get_action() === 'add' )
            return;

        $base_url = add_query_arg( 'ids', urlencode( implode( ',', $this->get_emails() ) ), $base_url );

        wp_redirect( $base_url );
        die();
    }

    /**
     * Create an email and then redirect to the edit page
     */
    private function add_email()
    {
        if ( isset( $_POST[ 'email_template' ] ) ){

            include_once WPGH_PLUGIN_DIR . '/templates/email-templates.php';

            /**
             * @var $email_templates array
             * @see /templates/email-templates.php
             */
            $email_content = $email_templates[ $_POST[ 'email_template' ] ][ 'content' ];

        } else if ( isset( $_POST[ 'email_id' ] ) ) {

            $email = WPGH()->emails->get( intval( $_POST['email_id'] ) );
            $email_content = $email->content;

        } else {

            $this->notices->add( 'ooops', __( 'Could not create email.', 'groundhogg' ), 'error' );
            return;

        }

        $email = array(
            'content'   => $email_content,
            'status'    => 'draft',
            'author'    => get_current_user_id(),
            'from_user' => get_current_user_id(),
        );

        $email_id = WPGH()->emails->add( $email );

        if ( ! $email_id ){

            $this->notices->add( 'ooops', __( 'Could not create email.', 'groundhogg' ), 'error' );
            return;

        }

        $return_path = admin_url( 'admin.php?page=gh_emails&action=edit&email=' .  $email_id );

        if ( isset( $_GET['step'] ) ){

            /* Make it easy to return back to the funnel editing screen */
            $step_id = intval( $_GET['step'] );
            $step = new WPGH_Step( $step_id );
            $return_path .= sprintf( "&return_funnel=%s&return_step=%s", $step->funnel_id, $step->ID );

        }

        do_action( 'wpgh_add_email', $email_id );

        wp_redirect( $return_path );

        die();
    }

    /**
     * Update the current email
     */
    private function update_email()
    {

        $id = intval( $_GET[ 'email' ] );

        do_action( 'wpgh_email_update_before', $id );

        $args = array();

        $status = ( isset( $_POST['status'] ) )? sanitize_text_field( trim( stripslashes( $_POST['status'] ) ) ): 'draft';
        $args[ 'status' ] = $status;

        if ( $status === 'draft' ) {
            $this->notices->add( 'email-in-draft-mode', __( 'This email will not be sent while in DRAFT mode.', 'groundhogg' ), 'info' );
        }

        $from_user =  ( isset( $_POST['from_user'] ) )? intval( $_POST['from_user'] ): -1;
        $args[ 'from_user' ] = $from_user;

        $subject =  ( isset( $_POST['subject'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['subject'] ) ) ) ): '';
        $args[ 'subject' ] = $subject;

        $pre_header =  ( isset( $_POST['pre_header'] ) )? wp_strip_all_tags( sanitize_text_field( trim( stripslashes( $_POST['pre_header'] ) ) ) ): '';
        $args[ 'pre_header' ] = $pre_header;

        $content =  ( isset( $_POST['content'] ) )? apply_filters( 'wpgh_sanitize_email_content', wpgh_minify_html( trim( stripslashes( $_POST['content'] ) ) ) ): '';
        $args[ 'content' ] = $content;

        $args[ 'last_updated' ] = current_time( 'mysql' );

        WPGH()->emails->update( $id, $args );

        $alignment =  ( isset( $_POST['email_alignment'] ) )? sanitize_text_field( trim( stripslashes( $_POST['email_alignment'] ) ) ): '';
        WPGH()->email_meta->update_meta( $id, 'alignment', $alignment );

        $browser_view =  ( isset( $_POST['browser_view'] ) )? 1 : false;
        WPGH()->email_meta->update_meta( $id, 'browser_view', $browser_view );

        do_action( 'wpgh_email_update_after', $id );

        $this->notices->add( 'email-updated', __( 'Email Updated.', 'groundhogg' ), 'success' );

        if ( isset( $_POST['send_test'] ) ){

            do_action( 'wpgh_before_send_test_email', $id );

            $test_email_uid =  ( isset( $_POST['test_email'] ) )? intval( $_POST['test_email'] ): '';
            WPGH()->email_meta->update_meta( $id, 'test_email', $test_email_uid );

            $email = new WPGH_Email( $id );

            $email->enable_test_mode();

            $user = get_userdata( $test_email_uid );

            $contact = new WPGH_Contact( $user->user_email );

            $sent = $contact->exists() ? $email->send( $contact ) : false;

            if ( ! $sent ){
                wp_die( 'Could not send test.' );
            }

            $this->notices->add(
                esc_attr( 'sent-test' ),
                sprintf( "%s %s",
                    __( 'Sent test email to', 'groundhogg' ),
                    get_userdata( $test_email_uid )->user_email ),
                'success'
            );

            do_action( 'wpgh_after_send_test_email', $id );
        }

    }

    function verify_action()
    {
        if ( ! isset( $_REQUEST['_wpnonce'] ) )
            return false;

        return wp_verify_nonce( $_REQUEST[ '_wpnonce' ] ) || wp_verify_nonce( $_REQUEST[ '_wpnonce' ], $this->get_action() )|| wp_verify_nonce( $_REQUEST[ '_wpnonce' ], 'bulk-emails' );
    }

    function table()
    {
        if ( ! class_exists( 'WPGH_Emails_Table' ) ){
            include dirname(__FILE__) . '/class-wpgh-emails-table.php';
        }

        $emails_table = new WPGH_Emails_Table();

        $emails_table->views(); ?>
        <form method="post" class="search-form wp-clearfix" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input"><?php _e( 'Search Emails ', 'groundhogg'); ?>:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="<?php _e( 'Search Emails ', 'groundhogg'); ?>">
            </p>
            <?php $emails_table->prepare_items(); ?>
            <?php $emails_table->display(); ?>
        </form>

        <?php
    }

    function edit()
    {
        include dirname( __FILE__ ) . '/email-editor.php';

    }

    function add()
    {
        include dirname( __FILE__ ) . '/add-email.php';
    }

    function page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php $this->get_title(); ?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
            <?php $this->notices->notices(); ?>
            <hr class="wp-header-end">
            <?php switch ( $this->get_action() ){
                case 'add':
                    $this->add();
                    break;
                case 'edit':
                    $this->edit();
                    break;
                default:
                    $this->table();
            } ?>
        </div>
        <?php
    }
}