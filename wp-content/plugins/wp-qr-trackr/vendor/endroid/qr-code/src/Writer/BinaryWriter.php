<?php

declare(strict_types=1);

namespace Endroid\QrCode\Writer;

use Endroid\QrCode\Bacon\MatrixFactory;
use Endroid\QrCode\Label\LabelInterface;
use Endroid\QrCode\Logo\LogoInterface;
use Endroid\QrCode\QrCodeInterface;
use Endroid\QrCode\Writer\Result\BinaryResult;
use Endroid\QrCode\Writer\Result\ResultInterface;

final class BinaryWriter implements WriterInterface {

	public function write( QrCodeInterface $qrCode, LogoInterface $logo = null, LabelInterface $label = null, array $options = array() ): ResultInterface {
		$matrixFactory = new MatrixFactory();
		$matrix        = $matrixFactory->create( $qrCode );

		return new BinaryResult( $matrix );
	}
}
