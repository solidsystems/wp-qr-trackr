<?php declare(strict_types=1);
/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPUnit\TextUI\XmlConfiguration;

use PHPUnit\Runner\TestSuiteSorter;
use PHPUnit\TextUI\Configuration\ConstantCollection;
use PHPUnit\TextUI\Configuration\DirectoryCollection;
use PHPUnit\TextUI\Configuration\ExtensionBootstrapCollection;
use PHPUnit\TextUI\Configuration\FileCollection;
use PHPUnit\TextUI\Configuration\FilterDirectoryCollection as CodeCoverageFilterDirectoryCollection;
use PHPUnit\TextUI\Configuration\GroupCollection;
use PHPUnit\TextUI\Configuration\IniSettingCollection;
use PHPUnit\TextUI\Configuration\Php;
use PHPUnit\TextUI\Configuration\Source;
use PHPUnit\TextUI\Configuration\TestSuiteCollection;
use PHPUnit\TextUI\Configuration\VariableCollection;
use PHPUnit\TextUI\XmlConfiguration\CodeCoverage\CodeCoverage;
use PHPUnit\TextUI\XmlConfiguration\Logging\Logging;

/**
 * @no-named-arguments Parameter names are not covered by the backward compatibility promise for PHPUnit
 *
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 *
 * @psalm-immutable
 */
final class DefaultConfiguration extends Configuration {

	public static function create(): self {
		return new self(
			ExtensionBootstrapCollection::fromArray( array() ),
			new Source(
				null,
				false,
				CodeCoverageFilterDirectoryCollection::fromArray( array() ),
				FileCollection::fromArray( array() ),
				CodeCoverageFilterDirectoryCollection::fromArray( array() ),
				FileCollection::fromArray( array() ),
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
			),
			new CodeCoverage(
				null,
				CodeCoverageFilterDirectoryCollection::fromArray( array() ),
				FileCollection::fromArray( array() ),
				CodeCoverageFilterDirectoryCollection::fromArray( array() ),
				FileCollection::fromArray( array() ),
				false,
				true,
				false,
				false,
				null,
				null,
				null,
				null,
				null,
				null,
				null,
			),
			new Groups(
				GroupCollection::fromArray( array() ),
				GroupCollection::fromArray( array() ),
			),
			new Logging(
				null,
				null,
				null,
				null,
			),
			new Php(
				DirectoryCollection::fromArray( array() ),
				IniSettingCollection::fromArray( array() ),
				ConstantCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
				VariableCollection::fromArray( array() ),
			),
			new PHPUnit(
				null,
				true,
				null,
				80,
				\PHPUnit\TextUI\Configuration\Configuration::COLOR_DEFAULT,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				null,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				false,
				null,
				false,
				false,
				true,
				false,
				false,
				1,
				1,
				10,
				60,
				null,
				TestSuiteSorter::ORDER_DEFAULT,
				true,
				false,
				false,
				false,
				false,
				false,
				false,
				100,
			),
			TestSuiteCollection::fromArray( array() ),
		);
	}

	public function isDefault(): bool {
		return true;
	}
}
