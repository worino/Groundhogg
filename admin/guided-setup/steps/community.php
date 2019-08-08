<?php
namespace Groundhogg\Admin\Guided_Setup\Steps;

use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\Plugin;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class Community extends Step
{

    public function get_title()
    {
        return _x( 'Community', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'community';
    }

    public function get_description()
    {
        return _x( 'Join our online community and be a part of our global movement to democratize digital marketing & sales.', 'guided_setup', 'groundhogg' );
    }

    public function get_content()
    {

        echo html()->e( 'style', [], '.button .dashicons { vertical-align: middle;}' );
        echo html()->e( 'h2', [], 'Support Group' );
        echo html()->e( 'p', [], 'Our support group is where you can crowd-source support from our awesome user community!' );
        echo html()->e( 'a', [ 'href' => 'https://www.facebook.com/groups/groundhoggwp/', 'class' => 'button button-secondary', 'target' => '_blank' ], '<span class="dashicons dashicons-facebook"></span> Join the group now!' );

        echo html()->e( 'h2', [], 'Facebook' );
        echo html()->e( 'p', [], 'Get inspiration from our Facebook page as we share podcasts, tutorials, and how to guides.' );
        echo html()->e( 'a', [ 'href' => 'https://www.facebook.com/groundhoggwp/', 'class' => 'button button-secondary', 'target' => '_blank'], '<span class="dashicons dashicons-facebook-alt"></span> Like us on Facebook!' );

        echo html()->e( 'h2', [], 'Twitter' );
        echo html()->e( 'p', [], 'Get promotions, important news, and general updates by staying up to date with us on Twitter.' );
        echo html()->e( 'a', [ 'href' => 'https://twitter.com/Groundhoggwp', 'class' => 'button button-secondary', 'target' => '_blank' ], '<span class="dashicons dashicons-twitter"></span> Follow us on Twitter!' );

        echo html()->e( 'h2', [], 'Youtube' );
        echo html()->e( 'p', [], 'Watch tutorials, how to\'s, guides, and podcasts on our official Youtube channel.' );
        echo html()->e( 'a', [ 'href' => 'https://www.youtube.com/channel/UChHW8I3wPv-KUhQYX-eUp6g', 'class' => 'button button-secondary', 'target' => '_blank' ], '<span class="dashicons dashicons-playlist-video"></span> Subscribe to us on YouTube!' );


    }

    public function save()
    {

        if ( get_request_var( 'enable_tracking' ) ){
            Plugin::$instance->stats_collection->stats_tracking_optin();
        }

        return true;
    }

}