<?php

namespace Groundhogg\Queue;

use Groundhogg\DB\Events;
use function Groundhogg\get_db;

/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2019-06-04
 * Time: 8:12 AM
 */
class Event_Store {

	/**
	 * @return Events
	 */
	public function db() {
		return get_db( 'events' );
	}

	/**
	 * Get a number of queued events.
	 *
	 * @param $count
	 *
	 * @return array
	 */
	public function get_queued_event_ids( $count=100 ) {
		global $wpdb;

		$now = time();

		$SQL = "SELECT ID FROM {$this->db()->get_table_name()}
		WHERE `status` = 'waiting' AND `time` <= {$now} AND `claim` = ''
		ORDER BY `priority` ASC, `time` ASC
		LIMIT {$count}";

		$queued_events = $wpdb->get_results( $SQL );

		return wp_parse_id_list( wp_list_pluck( $queued_events, 'ID' ) );
	}

	/**
	 * Get a number of events by a claim ID
	 *
	 * @param $claim
	 *
	 * @return int[]
	 */
	public function get_events_by_claim( $claim ) {
		$queued_events = $this->db()->advanced_query( [
			'where'   => [
				'relationship' => 'AND',
				[ 'col' => 'claim', 'val' => $claim, 'compare' => '=' ],
			],
			'orderby' => 'time',
		], false );

		$ids = wp_parse_id_list( wp_list_pluck( $queued_events, 'ID' ) );

		return $ids;
	}

	/**
	 * Stake a claim in the DB
	 *
	 * @param int $count
	 *
	 * @return bool|string
	 */
	public function stake_claim( $count = 100 ) {
		$claim  = $this->generate_claim_id();
		$events = $this->get_queued_event_ids( $count );
		$this->claim_events( $events, $claim );

		return $claim;
	}

	/**
	 * @param $event_ids
	 * @param $claim
	 *
	 * @return bool
	 */
	public function claim_events( $event_ids, $claim ) {
		global $wpdb;

		$ids = implode( ',', $event_ids );

		if ( empty( $ids ) ) {
			return false;
		}

		$result = $wpdb->query( $wpdb->prepare( "UPDATE {$this->db()->get_table_name()} SET claim = %s WHERE ID IN ( $ids )", $claim ) );

		return $result;
	}

	/**
	 * Generate a claim ID.
	 *
	 * @return bool|string
	 */
	public function generate_claim_id() {
		$claim_id = md5( microtime( true ) . rand( 0, 1000 ) );

		return substr( $claim_id, 0, 20 ); // to fit in db field with 20 char limit
	}

	/**
	 * Remove the claim from the event store.
	 *
	 * @param $claim
	 *
	 * @return bool
	 */
	public function release_events( $claim ) {
		return $this->db()->mass_update(
			[
				'claim' => ''
			],
			[
				'claim' => $claim
			]
		);
	}


}