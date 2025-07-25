<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\GdResult;
use Endroid\QrCode\Writer\Result\GifResult;
use Endroid\QrCode\Writer\Result\ResultInterface;

final class GifWriter extends AbstractGdWriter {

	public function write( QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = array() ): ResultInterface {
		/** @var GdResult $gdResult */
		$gdResult = parent::write( $qrCode, $logo, $label, $options );

		return new GifResult( $gdResult->getMatrix(), $gdResult->getImage() );
	}
}
