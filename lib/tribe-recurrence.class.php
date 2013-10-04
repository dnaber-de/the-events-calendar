<?php
/**
 * Given a start date, series end (end date or number of occurrences), and rules engine; find me all the dates in a recurrence
 * @author John Gadbois
 */
class TribeRecurrence {
	private $start_date;
	private $end;
	/** @var DateSeriesRules */
	private $series_rules;
	private $by_occurrence_count;
	private $event;
	private $minDate = 0;
	private $maxDate = PHP_INT_MAX;
	private $last_request_constrained = FALSE;

	public function  __construct($start_date, $end, $series_rules, $by_occurrence_count = false, $event = null) {
		$this->start_date = $start_date;
		$this->end = $end;
		$this->series_rules = $series_rules;
		$this->by_occurrence_count = $by_occurrence_count;
		$this->event = $event;
	}

	public function setMinDate( $timestamp ) {
		$this->minDate = (int)$timestamp;
	}

	public function setMaxDate( $timestamp ) {
		$this->maxDate = (int)$timestamp;
	}

	/**
	 * Using the rules engine, find all dates in the series 
	 *
	 * @param bool $all_events Return ALL instances?
	 * @param array $old_start_dates The old start dates for an event.
	 * @return array An array of all dates in the series
	 */
	public function getDates( $all_events = true, $old_start_dates = null ) {
		$this->last_request_constrained = FALSE;
		if( $this->series_rules ) {
			$dates = array();
			$cur_date = $this->start_date;

			$i = 0;
			while ( $cur_date = $this->getNextDate($cur_date) ) {
				$i++;
				if ( $cur_date > $this->maxDate ) {
					$this->last_request_constrained = $cur_date;
					break; // no more dates will be in range. stop here
				}
				if ( $cur_date < $this->minDate ) {
					continue; // move forward until we find a date within range
				}
				if ( $this->afterSeries( $this->by_occurrence_count ? $i : $cur_date ) ) {
					break; // end of the series
				}

				$dates[] = $cur_date;
			}

			if ( !$all_events && $old_start_dates && $this->event ) {
				$existing_dates = array();
				foreach ( $old_start_dates as $index => $date ) {
					$date_obj = date_create( $date );
					$existing_dates[] = $date_obj->format( 'U' );
				}
				foreach ( $dates as $index => $date ) {
					if ( !in_array( $date, $existing_dates ) ) {
						unset( $dates[$index] );
					}
				}
			}

			return $dates;
		}
		return array();
	}

	/**
	 * Flag indicating if the last getDates() request was constrained
	 * by the max date setting
	 * @return bool
	 */
	public function constrainedByMaxDate() {
		return $this->last_request_constrained;
	}

	private function getNextDate( $current_date ) {
		$current_date = $this->series_rules->getNextDate($current_date);
		// Makes sure to assign the proper hours to the date.
		$current_date = mktime (date("H", $this->start_date), date("i", $this->start_date), date("s", $this->start_date), date('n', $current_date),  date('j', $current_date), date('Y', $current_date));
		return $current_date;
	}

	private function afterSeries( $instance ) {
		if ( $this->by_occurrence_count ) {
			return $instance > $this->end;
		} else {
			return $instance > $this->end;
		}
	}
}