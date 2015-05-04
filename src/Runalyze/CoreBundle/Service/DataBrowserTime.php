<?php
namespace Runalyze\CoreBundle\Service;


/**
 * Timefunctions for DataBrowser
 *
 * @author Hannes Christiansen & Michael Pohl
 */
class DataBrowserTime {
    
    
    /**
     * @param int $int Timestamp of the week
     * @return array of start and end time
     */    
    static function week($time) {
        return array(Time::Weekstart($time), Time::Weekend($time));
    }
    
    /**
    * @param int $int Timestamp of the month
     * @return array of start and end time
     */    
    static function month($time) {
		return array(mktime(0, 0, 0, date("m", $time), 1, date("Y", $time)),
			mktime(23, 59, 50, date("m", $time)+1, 0, date("Y", $time)));
    }
    

    /**
     * @param int $int Timestamp of the year
     * @return array of start and end time
     */
    static function year($time) {
            return array(mktime(0, 0, 0, 1, 1, date("Y", $time)),
                    mktime(23, 59, 50, 12, 31, date("Y", $time)));
    }  
    
    /**
     * Get previous timestamps depending on current time-interval (just an alias for getNextTimestamps($start, $end, true);)
     * @param int $start Timestamp for first date in browser
     * @param int $end Timestamp for last date in browser
     * @return array Returns an array {'start', 'end'}
     */
    static function prevTimestamps($start, $end) {
            return self::nextTimestamps($start, $end, true);
    }
    
    /**
     * Get next timestamps depending on current time-interval
     * @param int $start Timestamp for first date in browser
     * @param int $end Timestamp for last date in browser
     * @param bool $getPrev optional to get previous timestamps
     * @return array Returns an array {'start', 'end'}
     */
    static function nextTimestamps($start, $end, $getPrev = false) {
            if (!is_numeric($start))
                    $start = time();
            if (!is_numeric($end))
                    $end = time();

            $date = array();
            $factor = $getPrev ? -1 : 1;
            $diff_in_days = round(($end - $start) / 86400);
            $start_month = date("m", $start);
            $start_day   = date("d", $start);
            $start_year  = date("Y", $start);
            $end_month   = date("m", $end);
            $end_day     = date("d", $end);
            $end_year    = date("Y", $end);

            if (360 < $diff_in_days && $diff_in_days < 370) {
                    $start_year  += 1*$factor;
                    $end_year    += 1*$factor;
            } elseif (28 <= $diff_in_days && $diff_in_days <= 31) {
                    $start_month += 1*$factor;
                    $end_month   += 1*$factor;

                    if ($start_day == 1 && $end_day != 0) {
                            $end_month = $start_month + 1;
                            $end_day = 0;
                    }
            } else {
                    $start_day   += $diff_in_days*$factor;
                    $end_day     += $diff_in_days*$factor;
            }

            $date['start'] = mktime(0, 0, 0, $start_month, $start_day, $start_year);
            $date['end'] = mktime(23, 59, 50, $end_month, $end_day, $end_year);

            return $date;
    }
}