<?php
namespace Groundhogg\Bulk_Jobs;

use Groundhogg\Contact_Query;
use function Groundhogg\get_contactdata;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

class Add_Contacts_To_Funnel extends Bulk_Job
{

    /**
     * @var Step
     */
    protected $step = null;

    /**
     * Get the action reference.
     *
     * @return string
     */
    function get_action(){
        return 'add_contacts_to_funnel';
    }

    /**
     * Get an array of items someway somehow
     *
     * @param $items array
     * @return array
     */
    public function query( $items )
    {
        if ( ! current_user_can( 'edit_contacts' ) ){
            return $items;
        }

        set_transient( 'gh_step_id', absint( get_request_var( 'step_id' ) ), HOUR_IN_SECONDS );

        $query = new Contact_Query();
        $args = [
            'tags_include' => wp_parse_id_list( get_request_var( 'include_tags' ) ),
            'tags_exclude' => wp_parse_id_list( get_request_var( 'exclude_tags' ) )
        ];

        $contacts = $query->query( $args );
        $ids = wp_list_pluck( $contacts, 'ID' );

        return $ids;
    }

    /**
     * Get the maximum number of items which can be processed at a time.
     *
     * @param $max int
     * @param $items array
     * @return int
     */
    public function max_items($max, $items)
    {
        if ( ! current_user_can( 'edit_contacts' ) ){
            return $max;
        }

        return min( 100, intval( ini_get( 'max_input_vars' ) ) ) ;
    }

    /**
     * Process an item
     *
     * @param $item mixed
     * @return void
     */
    protected function process_item( $item )
    {
        $this->step->enqueue( get_contactdata( absint( $item )) );
    }

    /**
     * Do stuff before the loop
     *
     * @return void
     */
    protected function pre_loop(){
        $step_id = absint( get_transient( 'gh_step_id' ) );
        $this->step = new Step( $step_id );

        if ( ! $this->step->exists() ){
            wp_send_json_error();
        }
    }

    /**
     * do stuff after the loop
     *
     * @return void
     */
    protected function post_loop(){}

    /**
     * Cleanup any options/transients/notices after the bulk job has been processed.
     *
     * @return void
     */
    protected function clean_up()
    {
        delete_transient( 'gh_step_id' );
    }

    /**
     * Get the return URL
     *
     * @return string
     */
    protected function get_return_url()
    {
        return add_query_arg( [
            'page' => 'gh_funnels',
            'action' => 'edit',
            'funnel' => $this->step->get_funnel_id(),
        ], admin_url( 'admin.php' ) );
    }
}