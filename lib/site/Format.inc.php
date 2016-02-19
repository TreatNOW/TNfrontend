<?php

class Format {

	static function DateTime($value) {
		$display = null;
		if (!is_null($value)) {
			$display = $value->format('Y-m-d H:i');
		}
		return $display;
	}

    static function Date($value) {
        $display = null;
        if (!is_null($value)) {
            $display = $value->format('Y-m-d');
        }
        return $display;
    }

    static function Time($value) {
        $display = null;
        if (!is_null($value)) {
            if (is_object($value)) {
                $display = $value->format('H:i');
            }
            elseif (is_string($value)) {
                if (strlen($value) == 4) {
                    $display = substr($value, 0, 2).':'.substr($value, 2, 2);
                }
            }
        }
        return $display;
    }

    static function Time4($value) {
        $display = null;
        if (!is_null($value)) {
            $display = $value->format('Hi');
        }
        return $display;
    }

    static function Duration($duration) {
		$seconds = 0;
		$hours = 0;
		$minutes = 0;
		//seconds
		$seconds = $duration % 60;
		$duration = $duration - $seconds;
		//minutes
		if ($duration > 60) {
			$duration = $duration / 60;
			$minutes = $duration % 60;
			$duration = $duration - $minutes;
		}
		//hours
		if ($duration > 0) {
			$hours = $duration / 60;
		}
		//format
		return $hours.':'.Util::DoubleString($minutes).':'.Util::DoubleString($seconds);
	}

    static function Minutes($duration) {
        $minutes = $duration % 60;
        $hours = ($duration - $minutes) / 60;
        $display = '';
        if ($duration >= 60) {
            $display .= $hours."h ";
        }
        if ($minutes > 0) {
            $display .= " ".$minutes."min";
        }
        return $display;
    }

	static function Distance($metres) {
		if ($metres % 1000 == 0) {
			$km = ($metres / 1000).'.0';
		}
		else {
			$km = (string)($metres / 1000);
		}
		return $km;
	}

    static function Money($value, $currencyCode = null) {
        $display = null;
        if (!is_null($value)) {
            if (!is_null($currencyCode)) {
                switch ($currencyCode) {
                    case 'GBP':
                        $display = '£';
                        break;
                    case 'USD':
                        $display = '$';
                        break;
                    case 'EUR':
                        $display = '€';
                        break;
                    default:
                        $display = $currencyCode;
                }

            }
            $display .= number_format((float)$value, 2, '.', '');
        }
        return $display;
    }

    static function HoursPeriod($from, $to) {
        if (is_null($from) && is_null($to)) {
            return 'Closed';
        }
        else {
            return substr($from, 0, 2).':'.substr($from, 2, 2).
                   ' - '.
                   substr($to, 0, 2).':'.substr($to, 2, 2);
        }
    }

    static function Phone($idc, $number) {
        if (is_null($idc) && is_null($number)) {
            return null;
        }
        elseif (is_null($idc)) {
            return HTML::Encode($number);
        }
        else {
            return '+'.$idc.HTML::Encode($number);
        }
    }

}

?>
