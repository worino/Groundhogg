<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Plugin;
use Groundhogg\Step;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Delay Timer
 *
 * This allows the adition of an event which "does nothing" but runs at the specified time according to the time provided.
 * Essentially delaying proceeding events.
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
class Delay_Timer extends Action
{

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Delay Timer', 'action_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'delay_timer';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Pause for the specified amount of time.', 'element_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/delay-timer.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $html = Plugin::$instance->utils->html;

        $html->start_form_table();

        $html->start_row();

        $html->th( __( 'Wait at least:', 'groundhogg' ) );

        $run_date_args = [
            'class'         => 'input',
            'name'          => $this->setting_name_prefix( 'run_date' ),
            'id'            => $this->setting_id_prefix( 'run_date' ),
            'value'         => $this->get_setting( 'run_date', date( 'Y-m-d', strtotime( '+3 days' ) ) ),
            'placeholder'   => 'yyy-mm-dd',
        ];

        $run_date = $html->date_picker( $run_date_args );

        $run_time_args = [
            'type'  => 'time',
            'class' => 'input',
            'name'  => $this->setting_name_prefix( 'run_time' ),
            'id'    => $this->setting_id_prefix(   'run_time' ),
            'value' => $this->get_setting( 'run_time', "09:00:00" ),
        ];

        $run_time = $html->input( $run_time_args );

        $local_time_args = [
            'label'         => _x( "Run in the contact's local time.", 'action', 'groundhogg' ),
            'name'          => $this->setting_name_prefix( 'send_in_timezone' ),
            'id'            => $this->setting_id_prefix(   'send_in_timezone' ),
            'value'         => '1',
            'checked'       => $step->get_meta( 'send_in_timezone' ),
            'title'         => __( "Run in the contact's local time.", 'groundhogg' ),
            'required'      => false,
        ];

        $local_time = $html->wrap( $html->checkbox( $local_time_args ), 'div', [ 'id' => $this->setting_id_prefix( 'local_time_div' ) ] );

        $td_content = $run_date . $run_time . $local_time;

        $html->td( $td_content );

        $html->end_row();

        $html->end_form_table();

        ?>

        <table class="form-table">
            <tbody>
            <tr>
                <th><?php echo esc_html__( 'Wait at least:', 'groundhogg' ); ?></th>
                <td>
                    <?php $args = array(
                        'name'  => $step->prefix( 'delay_amount' ),
                        'id'    => $step->prefix( 'delay_amount' ),
                        'class' => 'input',
                        'value' => $amount,
                        'min'   => 0,
                        'max'   => 9999,
                    );

                    echo WPGH()->html->number( $args );

                    $delay_types = array(
                        'minutes'   => __( 'Minutes' ),
                        'hours'     => __( 'Hours' ),
                        'days'      => __( 'Days' ),
                        'weeks'     => __( 'Weeks' ),
                        'months'    => __( 'Months' ),
                    );

                    $args = array(
                        'name'          => $step->prefix( 'delay_type' ),
                        'id'            => $step->prefix( 'delay_type' ),
                        'options'       => $delay_types,
                        'selected'      => $type,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args ); ?>
                </td>
            </tr>
            <tr>
                <th><?php echo esc_html__( 'And run:', 'groundhogg' ); ?></th>
                <td>
                    <?php

                    $when_types = array(
                        'now'   => __( 'Immediately', 'groundhogg' ),
                        'later' => __( 'At time of day...', 'groundhogg' ),
                    );

                    $args = array(
                        'name'          => $step->prefix( 'run_when' ),
                        'id'            => $step->prefix( 'run_when' ),
                        'options'       => $when_types,
                        'selected'      => $run_when,
                        'option_none'   => false,
                    );

                    echo WPGH()->html->dropdown( $args );

                    $args = array(
                        'type'  => 'time',
                        'class' => ( 'now' === $run_when ) ? 'input hidden' : 'input',
                        'name'  => $step->prefix( 'run_time' ),
                        'id'    => $step->prefix( 'run_time' ),
                        'value' => $run_time,
                    );

                    echo WPGH()->html->input( $args );

                    ?><div id="<?php echo $step->prefix( 'local_time' );?>" class="<?php echo ( 'now' === $run_when ) ? 'hidden' : ''; ?>"><?php
                    echo WPGH()->html->checkbox( array(
                        'label'         => _x( 'Run in the contact\'s local time.', 'action', 'groundhogg' ),
                        'name'          => $step->prefix( 'send_in_timezone' ),
                        'id'            => $step->prefix( 'send_in_timezone' ),
                        'class'         => '',
                        'value'         => '1',
                        'checked'       => $step->get_meta( 'send_in_timezone' ),
                        'title'         => __( 'Run in the contact\'s local time.', 'groundhogg' ),
                        'attributes'    => '',
                        'required'      => false,) );
                        ?></div>

                    <script>
                        jQuery( "#<?php echo $step->prefix( 'run_when' ); ?>" ).change(function(){
                            jQuery( "#<?php echo $step->prefix( 'run_time' ); ?>" ).toggleClass( 'hidden' );
                            jQuery( "#<?php echo $step->prefix( 'local_time' ); ?>" ).toggleClass( 'hidden' );
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <th>
                    <?php echo esc_html__( 'Disable Temporarily:', 'groundhogg' ); ?>
                </th>
                <td><?php
                    $args = array(
//                    'type'  => 'time',
//                    'class' => 'input',
                        'name'  => $step->prefix( 'disable' ),
                        'id'    => $step->prefix( 'disable' ),
                        'value' => 1,
                        'checked' => $checked,
                        'label' => __( 'Disable', 'groundhogg' )
                    );

                    echo WPGH()->html->checkbox( $args ); ?>
                </td>
            </tr>
            </tbody>
        </table>

        <?php
    }

    /**
     * Save the step settings
     *
     * @param $step WPGH_Step
     */
    public function save( $step )
    {

        $amount = intval( $_POST[ $step->prefix('delay_amount' ) ] );
        $step->update_meta( 'delay_amount', $amount );

        $type = sanitize_text_field( $_POST[ $step->prefix( 'delay_type' ) ] );
        $step->update_meta( 'delay_type', $type );

        $run_time = sanitize_text_field( $_POST[ $step->prefix( 'run_when' ) ] );
        $step->update_meta( 'run_when', $run_time );

        $run_time = sanitize_text_field( $_POST[ $step->prefix( 'run_time' ) ] );
        $step->update_meta( 'run_time', $run_time );

        if ( isset( $_POST[ $step->prefix( 'disable' ) ] ) ){
            $step->update_meta( 'disable', 1 );
        } else {
            $step->delete_meta( 'disable' );
        }

        if ( isset( $_POST[ $step->prefix( 'send_in_timezone' ) ] ) ){
            $step->update_meta( 'send_in_timezone', 1 );
        } else {
            $step->delete_meta( 'send_in_timezone' );
        }


    }

    /**
     * Override the parent and set the run time of this function to the settings
     *
     * @param WPGH_Step $step
     * @return int
     */
    public function enqueue( $step )
    {

        if ( $step->get_meta( 'disable' ) ){
            return parent::enqueue( $step );
        }

        $amount     = $step->get_meta( 'delay_amount' );
        $type       = $step->get_meta( 'delay_type' );
        $run_when   = $step->get_meta( 'run_when' );
        $run_time   = $step->get_meta( 'run_time' );
        $send_in_timezone = $step->get_meta( 'send_in_timezone' );

        if ( $run_when == 'now' ){
            $time_string = '+ ' . $amount . ' ' . $type;
            $final_time = strtotime( $time_string );
        } else {
            $time_string = '+ ' . $amount . ' ' . $type;
            $base_time = strtotime( $time_string );
            $formatted_date = date( 'Y-m-d', $base_time );
            $time_string = $formatted_date . ' ' . $run_time;
            if ( strtotime( $time_string ) < time() ){
                $formatted_date = date( 'Y-m-d', strtotime( 'tomorrow' ) );
                $time_string = $formatted_date . ' ' . $run_time;
            }

            /* convert to utc */
            $final_time = wpgh_convert_to_utc_0( strtotime( $time_string ) );

            /* Modify according to the contacts timezone */
            if ( $send_in_timezone && WPGH()->event_queue->is_processing()  ){
                $final_time = WPGH()->event_queue->cur_event->contact->get_local_time_in_utc_0( $final_time );
                if ( $final_time < time() ){
                    $final_time+=DAY_IN_SECONDS;
                }
            }
        }

        return $final_time;
    }
    /**
     * Process the apply tag step...
     *
     * @param $contact WPGH_Contact
     * @param $event WPGH_Event
     *
     * @return true
     */
    public function run( $contact, $event )
    {
        //do nothing
        return true;
    }
}