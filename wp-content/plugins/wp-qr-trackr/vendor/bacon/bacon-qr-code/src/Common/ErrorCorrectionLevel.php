<?php
declare(strict_types = 1);

namespace BaconQrCode\Common;

use BaconQrCode\Exception\OutOfBoundsException;
use DASPRiD\Enum\AbstractEnum;

/**
 * Enum representing the four error correction levels.
 *
 * @method static self L() ~7% correction
 * @method static self M() ~15% correction
 * @method static self Q() ~25% correction
 * @method static self H() ~30% correction
 */
final class ErrorCorrectionLevel extends AbstractEnum {

	protected const L = array( 0x01 );
	protected const M = array( 0x00 );
	protected const Q = array( 0x03 );
	protected const H = array( 0x02 );

	protected function __construct( private readonly int $bits ) {
	}

	/**
	 * @throws OutOfBoundsException if number of bits is invalid
	 */
	public static function forBits( int $bits ): self {
		switch ( $bits ) {
			case 0:
				return self::M();

			case 1:
				return self::L();

			case 2:
				return self::H();

			case 3:
				return self::Q();
		}

		throw new OutOfBoundsException( 'Invalid number of bits' );
	}

	/**
	 * Returns the two bits used to encode this error correction level.
	 */
	public function getBits(): int {
		return $this->bits;
	}
}
