<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace cms;

use bravedave;
use DateTime;
use NumberFormatter;

abstract class strings extends bravedave\dvc\strings {

	/**
	 * format a string as an Australian Business Number
	 */
	static public function asABN(string $str): string {

		$abn = preg_replace('@[^\d]@', '', $str);

		if (strlen($abn) != 11) return $str;
		return sprintf(
			'%s %s %s %s',
			substr($abn, 0, 2),
			substr($abn, 2, 3),
			substr($abn, 5, 3),
			substr($abn, 8)
		);
	}

	static public function asANSIDate($date) {
		if ((int)$date == $date && (int)$date > 1262268000) {	// > 2010-01-01, it's already a time
			return (date('Y-m-d', (int)$date));
		}

		if (($time = strtotime($date)) > 0) {
			return (date('Y-m-d', $time));
		}

		return false;
	}

	static public function asDate(string $date, string $default = 'today') {

		if (strtotime($date) > 0) return date('Y-m-d', strtotime($date));
		if ($default) return date('Y-m-d', strtotime($default));
		return '';
	}

	static public function asDateTime(string $date): ?DateTime {

		if (strtotime($date) > 0) return new DateTime($date);
		return null;
	}

	static public function asCurrency($amount) {

		$cf = new NumberFormatter(config::$LOCALE, NumberFormatter::CURRENCY);
		return $cf->format($amount);
	}

	static public function asLocaleNumber(float $v, int $decimalPlaces = 0) {

		$cf = new NumberFormatter(config::$LOCALE, NumberFormatter::DECIMAL);
		$cf->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimalPlaces);
		$cf->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimalPlaces);

		return $cf->format($v);
	}

	static public function asNumericAccounting($amount) {

		$cf = new NumberFormatter(config::$LOCALE, NumberFormatter::DEFAULT_STYLE);

		// set to 2 decimal places
		$cf->setAttribute(NumberFormatter::FRACTION_DIGITS, 2);

		// set to enclose with brackets
		$cf->setTextAttribute(NumberFormatter::NEGATIVE_PREFIX, '(');
		$cf->setTextAttribute(NumberFormatter::NEGATIVE_SUFFIX, ')');

		// $cf = new NumberFormatter(config::$LOCALE, NumberFormatter::CURRENCY_ACCOUNTING);
		// $cf = new NumberFormatter(config::$LOCALE, NumberFormatter::PATTERN_DECIMAL, "* #####.00 ;(* #####.00)");
		return $cf->format($amount);
	}

	static public function CleanRPDString($rpd) {
		$rpd =
			trim(preg_replace(array(
				"@^LOT\s@i",
				"@^L@i",
				"@:.*$@i",
				"@\sASHGROVE.*$@i",
				"@\sTHE\sGAP.*$@i"
			), '',  $rpd));

		$arpd = preg_split("@\s|/@", $rpd);
		if (count($arpd) > 2)
			$arpd = array($arpd[0], $arpd[1]);

		$rpd = implode(":", $arpd);

		return ($rpd);
	}

	static public function CleanNumber($word) {
		return (preg_replace('@([a-z]|,|\s|\$)@i', '', $word));
	}

	static public function DialerString($to) {
		/* Store Telephone numbers as straight string, format after via javascript email should be lowercase */
		$to = preg_replace("/ |\(|\)|-/", "", $to);	// to must not contain spaces, (, ), -

		if (substr($to, 0, 4) == "0011") $to = substr($to, 4);

		if (substr($to, 0, 1) == "+")
			$to = substr($to, 1);

		elseif (substr($to, 0, 1) == "0")
			$to = "61" . substr($to, 1);	// aussie aussie aussie

		return (sprintf('+%s', $to));
	}

	static public function EventTranslator($inEvent) {
		if ($inEvent == "Follow Inspect")
			return "Insp.FU";

		return ($inEvent);
	}

	static public function getStateFromPostcode($postcode, bool $shortForm = false) {

		if (!is_numeric($postcode) || strlen($postcode) < 1 || strlen($postcode) > 4) {
			return "Invalid postcode";
		}

		$firstDigit = (int) substr((string)$postcode, 0, 1);

		$stateMap = [
			1 => "New South Wales",
			2 => "New South Wales",
			3 => "Victoria",
			4 => "Queensland",
			5 => "South Australia",
			6 => "Western Australia",
			7 => "Tasmania or Northern Territory",
			8 => "Northern Territory or Australian Capital Territory",
			9 => "Australian Capital Territory"
		];

		if ( $shortForm ) {
			
			$stateMap = [
				1 => "NSW",
				2 => "NSW",
				3 => "VIC",
				4 => "QL",
				5 => "SA",
				6 => "WA",
				7 => "TAS or NT",
				8 => "NT or ACT",
				9 => "ACT"
			];
		}

		return $stateMap[$firstDigit] ?? "Unknown state";
	}

	static public function GoodLandString($land): string {

		if ($land == '0.0') return ('');
		if (preg_match('/[0-9]$/', $land)) $land .= ' m';
		return (string)$land;
	}

	static public function htmlSanitize($html): string {
		$debug = false;
		// $debug = true;
		// $debug = currentUser::isDavid();

		if ($_html = parent::htmlSanitize($html)) {
			$doc = new \DOMDocument;
			// ini_set ('error_reporting', "5");
			libxml_use_internal_errors(true);
			$doc->loadHTML($_html, LIBXML_NOWARNING);
			// $doc->loadHTML( $_string, LIBXML_NOWARNING);
			libxml_clear_errors();

			$found = false;
			foreach ($doc->getElementsByTagName('img') as $img) {
				if ($img->hasAttributes()) {

					$src = $img->getAttribute('src');
					if ($src) {
						if (preg_match('@^data:image@', $src)) continue;

						if ($debug) \sys::logger(sprintf('<%s> %s', $src, __METHOD__));
						$found = true;
						$img->removeAttribute('src');
						$img->setAttribute('data-safe-src', $src);
					}
				}
			}

			if ($found) {
				// $html = $doc->saveHTML();
				$tmpfile = \tempnam(\config::tempdir(), 'msg_');
				$doc->saveHTMLfile($tmpfile);
				$_html = \file_get_contents($tmpfile);
				unlink($tmpfile);
			}
		}

		return $_html;
	}

	static public function LocalisedPhone($tel = '') {

		$tel = self::CleanPhoneString(self::AsLocalPhone($tel));
		if (strlen($tel) == 8) $tel = sys::fallback_area_code() . $tel;
		return $tel;
	}

	static public function MinuteToAMPM($iMinute = 0) {

		$strAMPM = "a";
		$iHourMinute = $iMinute % 60;
		$iHour = (int) ($iMinute / 60);

		if ($iHour > 12) {
			$iHour -= 12;
			$strAMPM = "p";
		}
		return (sprintf("%02d:%02d%s", $iHour, $iHourMinute, $strAMPM));
	}

	static public function numeric_naration($i, $withTail = TRUE, $abbreviated = TRUE) {
		return (self::numeric_narration($i, $withTail));
	}

	static public function numeric_narration($i, $withTail = TRUE, $abbreviated = TRUE) {
		$debug = false;
		$debug = $i == 135000000.1;

		if ($i == '')
			return '';

		$rTail = "";
		$tail = "";

		if (preg_match("/\./", (string)$i)) {
			$x = explode('.', (string)$i);
			$rTail = array_pop($x);
			$i = implode('.', $x);
		}

		$i = (float)$i;

		if ($abbreviated) {
			if ($i >= 1000000) {
				$tail = "m";
				$i /= 1000000;
			} elseif ($i >= 1000) {
				if (config::$NUMERIC_NARRATION_TAIL)
					$tail = "k";

				$i /= 1000;
			}
		}

		if ($rTail >= 1000)
			$rTail = sprintf('.%s%s', number_format($rTail / 1000), $tail);

		elseif ($rTail > 1)
			$rTail = sprintf('.%s', number_format((int)$rTail));

		elseif ($rTail == 1)
			$rTail = ' +';

		else
			$rTail = '';


		$n = number_format($i, 3);
		while (substr($n, -1) == '0')
			$n = substr($n, 0, -1);

		if (substr($n, -1) == '.')
			$n = substr($n, 0, -1);

		if ($withTail)
			return ($n . $tail . $rTail);

		return ($n . $rTail);
	}

	static public function obfuscateBankingDetails(string $input): string {

		// Masking credit card numbers (e.g., 1234-5678-9876-5432 or 1234567898765432)
		$input = preg_replace('/\b(\d{4})(\d{8,12})(\d{4})\b/', '$1****$3', $input);

		// Masking branch codes (e.g., 123-456 or 123 456)
		$input = preg_replace('/\b(\d{3})[- ](\d{3})\b/', '$1-***', $input);

		// Masking account numbers with 6-9 digits
		$input = preg_replace('/\b(\d{2})(\d{2,5})(\d{2})\b/', '$1****$3', $input);

		return $input;
	}

	static public function SmartSentence(array $notes): string {

		if (count($notes) > 1) {

			// format the notes by joining the elements with a comma and use "and" as the last joiner
			// example: "set outcome to sold, set as property contact and set as property contact"

			$last = array_pop($notes);  	// pop the last element off the array
			$s = implode(', ', $notes); 	// join the elements with a comma
			$s .= ' and ' . $last;      	// add the last element with "and" as the last joiner
		} else {

			$s = implode(' and ', $notes);
		}

		return $s;
	}

	static public function ReStripPrice($word) {
		$aStripWords = [];
		$aReplaceWords = [];

		$aStripWords[] = "@^\.{1,}@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@(.*)auction(.*)@i";
		$aReplaceWords[] = "Auction";

		$aStripWords[] = "@(.*)Unless Sold(.*)@i";
		$aReplaceWords[] = "Auction";

		$aStripWords[] = "@(.*)Under Offer(.*)@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@onsite(.*)@i";
		$aReplaceWords[] = "Auction";

		$aStripWords[] = "@forthcoming@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@for(\s)?sale now@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@for(\s)?sale@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@contact agent@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@(sale\s)?(by\s)?negotiation@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@(sale\s)?(by\s)?neg[\.]@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@(offers over|start bid|price)@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@[\s]considered@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@(Mid to High|Mid)[\s]?@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@MAKE AN OFFER[!]?@i";
		$aReplaceWords[] = "";

		$aStripWords[] = "@\.{2,}@i";
		$aReplaceWords[] = ".";

		return trim(preg_replace($aStripWords, $aReplaceWords, $word));
	}

	static public function template(string $template, array $data): string {

		$replacements = [];
		foreach ($data as $key => $value) {
			$replacements['{' . $key . '}'] = $value;
		}

		return str_replace(array_keys($replacements), array_values($replacements), $template);
	}

	static public function truncate(string $text, int $chars = 45): string {

		if (strlen($text) <= $chars) return $text;

		$text = substr($text . ' ', 0, $chars);

		$splitLen =  min([$chars - 3, strrpos($text, ' ')]);
		if ($splitLen < 10) $splitLen = 12;
		$text = substr($text, 0, $splitLen);

		return $text . '...';
	}

	static public function validateDate($date) {
		return self::isDate($date);
	}
}
