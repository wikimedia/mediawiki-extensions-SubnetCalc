<?php

namespace SubnetCalc\Hook\ParserFirstCallInit;

use MediaWiki\Hook\ParserFirstCallInitHook;
use SubnetCalc\SubnetCalc;

class SetParserHook implements ParserFirstCallInitHook {

	/**
	 * @inheritDoc
	 */
	public function onParserFirstCallInit( $parser ) {
		$subnetCalc = new SubnetCalc();

		$parser->setHook( 'subnet', [ $subnetCalc, 'calculateSubnet' ] );
	}
}
