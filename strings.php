<?php
/*
 * David Bray
 * BrayWorth Pty Ltd
 * e. david@brayworth.com.au
 *
 * MIT License
 *
*/

namespace photolog;

class strings extends \strings {
	static function GoodStreetString( $street ) {
		if ( preg_match( '/The\s?Drive/i', $street ))
			return ( $street);
		if ( preg_match( '/The\s?Avenue/i', $street ))
			return ( $street);

		$find = [
			'@\sroad$@i','@\sroad,@i',
			'@\sstreet$@i','@\sstreet,@i','@\sstreet\s@i',
			'@\savenue$@i','@\savenue,@i','@\save$@i',
			'@\sparade$@i','@\spde$@i','@\sparade,@i','@\spde,@i',
			'@\sterrace$@i','@\stce$@i','@\sterrace,@i','@\stce,@i',
			'@\sdrive$@i','@\sdrive,@i',
			'@\splace$@i','@\splace,@i',
			'@\scourt$@i','@\scourt,@i',
			'@\screscent$@i','@\screscent,@i'
			];
		$replace = [
			' Rd',' Rd,',
			' St',' St,',' St, ',
			' Av',' Av,',' Av,',
			' Pd',' Pd',' Pd,',' Pd,',
			' Tc',' Tc',' Tc,',' Tc,',
			' Dr',' Dr,',
			' Pl',' Pl,',
			' Ct',' Ct,',
			' Cres',' Cres,'
			];


		return ( trim( preg_replace( $find, $replace, $street ), ', '));

	}

}