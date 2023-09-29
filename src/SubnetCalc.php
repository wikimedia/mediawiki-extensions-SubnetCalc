<?php

namespace SubnetCalc;

# SubnetCalc is mostly based on the code of open source PHP Subnet Calculator
# Source of PHP Subnet Calculator can be found at http://sourceforge.net/projects/subntcalc/

# To use this extension, create a file named SubnetCalc.php inside extensions directory
# Paste this code inside the file
# Insert require_once("extensions/SubnetCalc.php") to LocalSettings.php

# [[Wikipedia:User:Oblivious]]
# http://meta.wikimedia.org/wiki/User:Oblivious/SubnetCalc
# January 29, 2006

class SubnetCalc {

	/**
	 * The callback function for converting the input text to HTML output
	 *
	 * @param string $input
	 * @param array $argv
	 * @return string
	 */
	public function calculateSubnet( $input, $argv ) {
		$my_net_info = trim( $input );
		$align = $argv['align'];
		$allhosts = $argv['allhosts'];

		# #Validate input
		if ( !preg_match(
			'^([0-9]{1,3}\.){3}[0-9]{1,3}(( ([0-9]{1,3}\.){3}[0-9]{1,3})|(/[0-9]{1,2}))$',
			$my_net_info
		) ) {
			//phpcs:ignore Generic.Files.LineLength.TooLong
			$error = "<pre><font color=red>Invalid Input.<br> <b>Use</b> IP & CIDR Netmask:  10.0.0.1/22 <br> Or IP & Netmask: 10.0.0.1 255.255.252.0 <br> Or IP & Wildcard Mask: 10.0.0.1 0.0.3.255</font>< /pre>";
			return $error;
		}

		# Determine the input type
		if ( preg_match( "/", $my_net_info ) ) {
			// if cidr type mask
			$dq_host = strtok( "$my_net_info", "/" );
			$cdr_nmask = strtok( "/" );
			if ( !( $cdr_nmask >= 0 && $cdr_nmask <= 32 ) ) {
				return ( "<pre><font color=red>Invalid CIDR value. Try an integer 0 - 32.</font>< /pre>" );
			}
			$bin_nmask = $this->cdrtobin( $cdr_nmask );
			// Dotted quad mask?
			$bin_wmask = $this->binnmtowm( $bin_nmask );
		} else {
			$dqs = explode( " ", $my_net_info );
			$dq_host = $dqs[0];
			$bin_nmask = $this->dqtobin( $dqs[1] );
			$bin_wmask = $this->binnmtowm( $bin_nmask );
			if ( preg_match( "0", rtrim( $bin_nmask, "0" ) ) ) {
				// Wildcard mask then? hmm?
				$bin_wmask = $this->dqtobin( $dqs[1] );
				$bin_nmask = $this->binwmtonm( $bin_wmask );
				if ( preg_match( "0", rtrim( $bin_nmask, "0" ) ) ) {
					// If it's not wcard, whussup?
					return ( "<pre><font color=red>Invalid Netmask.</font>< /pre>" );
				}
			}
			$cdr_nmask = $this->bintocdr( $bin_nmask );
		}

		// Check for valid $dq_host
		if ( !preg_match( '^0.', $dq_host ) ) {
			foreach ( explode( ".", $dq_host ) as $octet ) {
				if ( $octet > 255 ) {
					return ( "<pre><font color=red>Invalid IP Address</font>< /pre>" );
				}

			}
		}

		$bin_host = $this->dqtobin( $dq_host );
		$bin_bcast = ( str_pad( substr( $bin_host, 0, $cdr_nmask ), 32, 1 ) );
		$bin_net = ( str_pad( substr( $bin_host, 0, $cdr_nmask ), 32, 0 ) );
		$bin_first = ( str_pad( substr( $bin_net, 0, 31 ), 32, 1 ) );
		$bin_last = ( str_pad( substr( $bin_bcast, 0, 31 ), 32, 0 ) );
		$host_total = ( bindec( str_pad( "", ( 32 - $cdr_nmask ), 1 ) ) - 1 );

		if ( $host_total <= 0 ) {
			// Takes care of 31 and 32 bit masks.
			$bin_first = "N/A";
			$bin_last = "N/A";
			$host_total = "N/A";
			if ( $bin_net === $bin_bcast ) { $bin_bcast = "N/A";
			}
		}

		// Determine Class
		if ( preg_match( '^0', $bin_net ) ) {
			$class = "A";
			$dotbin_net = "<font color=\"Green\">0</font>" . substr( $this->dotbin( $bin_net, $cdr_nmask ), 1 );
		} elseif ( preg_match( '^10', $bin_net ) ) {
			$class = "B";
			$dotbin_net = "<font color=\"Green\">10</font>" . substr( $this->dotbin( $bin_net, $cdr_nmask ), 2 );
		} elseif ( preg_match( '^110', $bin_net ) ) {
			$class = "C";
			$dotbin_net = "<font color=\"Green\">110</font>" . substr( $this->dotbin( $bin_net, $cdr_nmask ), 3 );
		} elseif ( preg_match( '^1110', $bin_net ) ) {
			$class = "D";
			$dotbin_net = "<font color=\"Green\">1110</font>" . substr( $this->dotbin( $bin_net, $cdr_nmask ), 4 );
			$special = "<font color=\"Green\">Class D = Multicast Address Space.</font>";
		} else {
			$class = "E";
			$dotbin_net = "<font color=\"Green\">1111</font>" . substr( $this->dotbin( $bin_net, $cdr_nmask ), 4 );
			$special = "<font color=\"Green\">Class E = Experimental Address Space.</font>";
		}

		if ( preg_match( '^(00001010)|(101011000001)|(1100000010101000)', $bin_net ) ) {
			$special = '(<a href="http://www.ietf.org/rfc/rfc1918.txt">RFC-1918 Private Internet Address</a>)';
		}

		$address = $dq_host;
		$addressBin = $this->dotbin( $bin_host, $cdr_nmask );
		$netmask = $this->bintodq( $bin_nmask );
		$netmaskBin = $this->dotbin( $bin_nmask, $cdr_nmask );
		$wildcard = $this->bintodq( $bin_wmask );
		$wildcardBin = $this->dotbin( $bin_wmask, $cdr_nmask );
		$network = $this->bintodq( $bin_net );
		$networkBin = $dotbin_net;
		$networkClass = $class;
		$broadcast = $this->bintodq( $bin_bcast );
		$broadcastBin = $this->dotbin( $bin_bcast, $cdr_nmask );
		$hostmin = $this->bintodq( $bin_first );
		$hostminBin = $this->dotbin( $bin_first, $cdr_nmask );
		$hostmax = $this->bintodq( $bin_last );
		$hostmaxBin = $this->dotbin( $bin_last, $cdr_nmask );
		$hostsNet = $host_total;
		// just for the sake of doing it :)
		$special = "<br>" . $special;
		$starthost = ip2long( $hostmin );

		if ( $allhosts ) {
			$allhosts = $this->generatehosts( $host_total, $starthost );
			$allhosts = implode( '<br> ', $allhosts );
		}

		$output = "<table class=\"wikitable\" align=\"$align\">
                <tr>
                <th colspan=\"3\"> <b>Subnet info for $my_net_info</b>
                </th></tr>
                <tr>
                <th> Address
                </th><td> <font color=\"blue\">$address</font> </td><td> <font color=\"brown\">$addressBin</font>
                </td></tr>
                <tr>
                <th>Netmask
                </th><td><font color=\"blue\">$netmask</font></td><td> <font color=\"red\">$netmaskBin</font>
                </td></tr>
                <tr>
                <th>Wildcard
                </th><td><font color=\"blue\">$wildcard</font></td><td> <font color=\"brown\">$wildcardBin</font>
                </td></tr>
                <tr>
                <th>Network
                </th><td><font color=\"blue\">$network</font></td><td> <font color=\"brown\">$networkBin</font>
                </td></tr>
                <tr>
                <th>Broadcast
                </th><td><font color=\"blue\">$broadcast</font></td><td> <font color=\"brown\">$broadcastBin</font>
                </td></tr>
                <tr>
                <th>HostMin
                </th><td><font color=\"blue\">$hostmin</font></td><td><font color=\"brown\">$hostminBin</font>
                </td></tr>
                <tr>
                <th>HostMax
                </th><td><font color=\"blue\">$hostmax</font></td><td><font color=\"brown\">$hostmaxBin</font>
                </td></tr>
                <tr>
                <th>Hosts/Net
                </th>
                <td><font color=\"blue\"><center>$hostsNet</center></font> </td>
                <td><font color=\"green\">Class $networkClass network</font> $special $allhosts</td>
                </tr></table>";

		return $output;
	}

	/**
	 * Calculation-specific funtions
	 *
	 * @param string $binin
	 *
	 * @return string
	 */
	private function binnmtowm( $binin ) {
		$binin = rtrim( $binin, "0" );
		if ( !preg_match( "0", $binin ) ) {
			return str_pad( str_replace( "1", "0", $binin ), 32, "1" );
		} else { return "1010101010101010101010101010101010101010";
		}
	}

	/**
	 * @param string $binin
	 *
	 * @return int
	 */
	private function bintocdr( $binin ) {
		return strlen( rtrim( $binin, "0" ) );
	}

	/**
	 * @param string $binin
	 *
	 * @return mixed|string
	 */
	private function bintodq( $binin ) {
		if ( $binin == "N/A" ) { return $binin;
		}
		$binin = explode( ".", chunk_split( $binin, 8, "." ) );
		for ( $i = 0; $i < 4; $i++ ) {
			$dq[$i] = bindec( $binin[$i] );
		}
		return implode( ".", $dq );
	}

	/**
	 * @param string $binin
	 *
	 * @return float|int
	 */
	private function bintoint( $binin ) {
		return bindec( $binin );
	}

	/**
	 * @param string $binin
	 *
	 * @return string
	 */
	private function binwmtonm( $binin ) {
		$binin = rtrim( $binin, "1" );
		if ( !preg_match( "1", $binin ) ) {
			return str_pad( str_replace( "0", "1", $binin ), 32, "0" );
		} else { return "1010101010101010101010101010101010101010";
		}
	}

	/**
	 * @param string $cdrin
	 *
	 * @return string
	 */
	private function cdrtobin( $cdrin ) {
		return str_pad( str_pad( "", $cdrin, "1" ), 32, "0" );
	}

	/**
	 * @param string $binin
	 * @param int $cdr_nmask
	 *
	 * @return mixed|string
	 */
	private function dotbin( $binin, $cdr_nmask ) {
		// splits 32 bit bin into dotted bin octets
		if ( $binin == "N/A" ) { return $binin;
		}
		$oct = rtrim( chunk_split( $binin, 8, "." ), "." );
		if ( $cdr_nmask > 0 ) {
			$offset = sprintf( "%u", $cdr_nmask / 8 ) + $cdr_nmask;
			return substr( $oct, 0, $offset ) . "   " . substr( $oct, $offset );
		} else {
			return $oct;
		}
	}

	/**
	 * @param string $dqin
	 *
	 * @return string
	 */
	private function dqtobin( $dqin ) {
		$dq = explode( ".", $dqin );
		for ( $i = 0; $i < 4; $i++ ) {
			$bin[$i] = str_pad( decbin( $dq[$i] ), 8, "0", STR_PAD_LEFT );
		}
		return implode( "", $bin );
	}

	/**
	 * @param int $intin
	 *
	 * @return string
	 */
	private function inttobin( $intin ) {
		return str_pad( decbin( $intin ), 32, "0", STR_PAD_LEFT );
	}

	/**
	 * @param mixed $host_total
	 * @param mixed $starthost
	 *
	 * @return mixed
	 */
	private function generatehosts( $host_total, $starthost ) {
		for ( $i = 0; $i <= $host_total - 1; $i++ ) {
			$ip_range[] = long2ip( $starthost + $i );
		}
		return $ip_range;
	}

}
