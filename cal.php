<link rel="stylesheet" href="cal.css" type="text/css">

<?php
# iCaltoPHPCalendar 
# by Kevin Lee
# License: GPL

$url='Your iCal URL Here';

function iCalendarToXML() {
	
	global $url;
		
	$lines = file($url);

    // Properties can be folded over 2 lines. In this case the second
    // line will be preceeded by a space or tab.
    $lines2 = array();
    foreach($lines as $line) {

        if ($line[0]==" " || $line[0]=="\t") {
            $lines2[count($lines2)-1].=substr($line,1);
            continue;
        }

        $lines2[]=$line;

    }
    $xml = '<?xml version="1.0"?>' . "\n";

    $spaces = 0;
    foreach($lines2 as $line) {

        $matches = array();
        // This matches PROPERTYNAME;ATTRIBUTES:VALUE
        if (preg_match('/^([^:^;]*)(?:;([^:]*))?:(.*)$/',$line,$matches)) {
            $propertyName = strtoupper($matches[1]);
            $attributes = $matches[2];
            $value = $matches[3];

            // If the line was in the format BEGIN:COMPONENT or END:COMPONENT, we need to special case it.
            if ($propertyName == 'BEGIN') {
                $xml.=str_repeat(" ",$spaces);
                $xml.='<' . strtoupper($value) . ">\n";
                $spaces+=2;
                continue;
            } elseif ($propertyName == 'END') {
                $spaces-=2;
                $xml.=str_repeat(" ",$spaces);
                $xml.='</' . strtoupper($value) . ">\n";
                continue;
            }

            $xml.=str_repeat(" ",$spaces);
            $xml.='<' . $propertyName;
            if ($attributes) {
                // There can be multiple attributes
                $attributes = explode(';',$attributes);
                foreach($attributes as $att) {

                    list($attName,$attValue) = explode('=',$att,2);
                    $xml.=' ' . $attName . '="' . htmlspecialchars($attValue) . '"';

                }
				
            }

            $xml.='>'. htmlspecialchars($value) . '</' . $propertyName . ">\n";

        }

    }
    return $xml;

}


// function for generating arrays of events from XML files in a directory
function get_xml_events ($month, $year) {
	$events = array();
	$xml = new SimpleXMLElement(iCalendarToXML());

	foreach($xml->VEVENT as $event)
	{
	
		//get start year,month,day and end year,month,day	
		$start = date_parse_from_format("Ynj",(string) $event->DTSTART);
		$end = date_parse_from_format("Ynj",(string) $event->DTEND);
				
		//make timestamp for start, end, and current
		$end_of_month = gmmktime(0,0,0,$month + 1,1,$year);
		$start_of_month = gmmktime(0,0,0,$month,1,$year);		
		$start_date = gmmktime(0,0,0,$start["month"],$start["day"],$start["year"]);
		$end_date = gmmktime(0,0,0,$end["month"],$end["day"],$end["year"]);

		//if current month and year are within start and end range
		if ($end_of_month > $start_date && $start_of_month < $end_date) {
			//if event start date is after first of month
			if ($start_of_month <= $start_date)
			{
				$total_days = floor(($end_date - $start_date)/86400);
				//count and mark the booked days in the month
				for ($i = 0;$i <= $total_days && $i <= gmdate('t',$start_of_month);$i++)
				{ 
					$class = 'booked';
					//special classes for first and last days
					if ($i == 0)
						$class .= ' first';
					elseif($i == $total_days)
						 $class .= ' last';
					$events[$start["day"] + $i] = $class;
				}
			}
			else //event start date is before first of month
			{
				$total_days = floor(($end_date - $start_of_month)/86400);
				
				
				for ($i = 0;$i <= $total_days && $i <= gmdate('t',$start_of_month);$i++)
				{ 
					$class = 'booked';
					//special classes for last day
					if($i == $total_days)
						$class .= ' last';
					$events[$i + 1] = $class;
				}			
			}
			
		}
	}
	
	
	
	return $events;

}



// set current time
$time = time();
// set current date
$today = date('j',$time);

// if url parameters are set, we generate some appropriate previous/next month (pn) links
if (isset($_GET['month']) && isset($_GET['year']) && isset($_GET['pn'])) {
	// set month, year & previous or next (pn)
	$month = (int)$_GET['month'];
	$year = (int)$_GET['year'];
	$pn = $_GET['pn'];
	// if the month is btw 1 and 12 we incr for next and decr for previous
	if($month > 1 && $month < 12){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month='.((int)$_GET['month']+1).'&year='.$year;
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month='.((int)$_GET['month']-1).'&year='.$year;
	} // In Dec the next month is Jan and the year is upped, the prev month is Nov and the year stays the same
	if($month == 12){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month=1&year='.($year+1);
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month=11&year='.$year;
	} // In Jan the next month is Feb and the year stays the same, the prev month is Dec and the year is decr by one
	if($month == 1){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month=2&year='.$year;
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month=12&year='.($year-1);
	}
} else {  // otherwise we generate a calendar for the current month based on the current date & some (pn) links
	// set current month & year
	$month = (int)date('n', $time);
	$year = (int)date('Y', $time);
	// same conditional crap as above
	if($month > 1 && $month < 12){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month='.($month+1).'&year='.$year;
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month='.($month-1).'&year='.$year;
	}
	if($month == 12){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month=1&year='.($year+1);
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month=11&year='.$year;
	}
	if($month == 1){
		$nxt = $_SERVER['PHP_SELF'].'?pn=next&month=2&year='.$year;
		$prv = $_SERVER['PHP_SELF'].'?pn=prev&month=12&year='.($year-1);
	}
}

// array for our previous and next links
$pn = array('<'=>$prv, '>'=>$nxt);

// actually generating the calendar
echo generate_calendar($year, $month, get_xml_events($month, $year), 3, NULL, 0, $pn);

# Generate Calendar Function
# PHP Calendar (version 2.3), written by Keith Devens
# http://keithdevens.com/software/php_calendar
# see example at http://keithdevens.com/weblog
# License: http://keithdevens.com/software/license

function generate_calendar($year, $month, $days = array(), $day_name_length = 3, $month_href = NULL, $first_day = 0, $pn = array()){
	$first_of_month = gmmktime(0,0,0,$month,1,$year);
	#remember that mktime will automatically correct if invalid dates are entered
	# for instance, mktime(0,0,0,12,32,1997) will be the date for Jan 1, 1998
	# this provides a built in "rounding" feature to generate_calendar()

	$day_names = array(); #generate all the day names according to the current locale
	for($n=0,$t=(3+$first_day)*86400; $n<7; $n++,$t+=86400) #January 4, 1970 was a Sunday
		$day_names[$n] = ucfirst(gmstrftime('%A',$t)); #%A means full textual day name

	list($month, $year, $month_name, $weekday) = explode(',',gmstrftime('%m,%Y,%B,%w',$first_of_month));
	$weekday = ($weekday + 7 - $first_day) % 7; #adjust for $first_day
	$title   = htmlentities(ucfirst($month_name)).'&nbsp;'.$year;  #note that some locales don't capitalize month and day names

	#Begin calendar. Uses a real <caption>. See http://diveintomark.org/archives/2002/07/03
	@list($p, $pl) = each($pn); @list($n, $nl) = each($pn); #previous and next links, if applicable
	if($p) $p = '<span class="calendar-prev">'.($pl ? '<a href="'.htmlspecialchars($pl).'" class="calnav">'.$p.'</a>' : $p).'</span>';
	if($n) $n = '<span class="calendar-next">'.($nl ? '<a href="'.htmlspecialchars($nl).'" class="calnav">'.$n.'</a>' : $n).'</span>';
	$calendar = '<table class="calendar">'."\n".
		'<caption class="calendar-month">'.$p.' '.$n.' '.($month_href ? '<a href="'.htmlspecialchars($month_href).'">'.$title.'</a>' : $title)."</caption>\n<tr>";

	if($day_name_length){ #if the day names should be shown ($day_name_length > 0)
		#if day_name_length is >3, the full name of the day will be printed
		foreach($day_names as $d)
			$calendar .= '<th abbr="'.htmlentities($d).'">'.htmlentities($day_name_length < 4 ? substr($d,0,$day_name_length) : $d).'</th>';
		$calendar .= "</tr>\n<tr>";
	}

	if($weekday > 0) $calendar .= '<td colspan="'.$weekday.'">&nbsp;</td>'; #initial 'empty' days
	for($day=1,$days_in_month=gmdate('t',$first_of_month); $day<=$days_in_month; $day++,$weekday++){
		if($weekday == 7){
			$weekday   = 0; #start a new week
			$calendar .= "</tr>\n<tr>";
		}
			$calendar .= '<td'.(isset($days[$day]) ? ' class="'.$days[$day].'"' : ' ' ).'style="text-align:center;">'.
							'<a>'.$day.'</a>'.
						 '</td>';
	}
	if($weekday != 7) $calendar .= '<td colspan="'.(7-$weekday).'">&nbsp;</td>'; #remaining "empty" days

	return $calendar."</tr>\n</table>\n";
}
?>