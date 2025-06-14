<?php
/**
 * Tests that unclosed docblocks during live coding are handled correctly.
 *
 * @author    Juliette Reinders Folmer <phpcs_nospam@adviesenzo.nl>
 * @copyright 2024 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 */

namespace PHP_CodeSniffer\Tests\Core\Tokenizers\Comment;

/**
 * Tests that unclosed docblocks during live coding are handled correctly.
 *
 * @covers PHP_CodeSniffer\Tokenizers\Comment
 */
final class LiveCoding1Test extends CommentTestCase {



	/**
	 * Data provider.
	 *
	 * @see testDocblockOpenerCloser()
	 *
	 * @return array<string, array<string, string|int|array<int>>>
	 */
	public static function dataDocblockOpenerCloser() {
		return array(
			'live coding: unclosed docblock, no blank line at end of file' => array(
				'marker'       => '/* testLiveCoding */',
				'closerOffset' => 8,
				'expectedTags' => array(),
			),
		);
	}//end dataDocblockOpenerCloser()


	/**
	 * Verify tokenization of the DocBlock.
	 *
     * @phpcs:disable Squiz.Arrays.ArrayDeclaration.SpaceBeforeDoubleArrow -- Readability is better with alignment.
	 *
	 * @return void
	 */
	public function testLiveCoding() {
		$expectedSequence = array(
			array( T_DOC_COMMENT_OPEN_TAG => '/**' ),
			array( T_DOC_COMMENT_WHITESPACE => "\n" ),
			array( T_DOC_COMMENT_WHITESPACE => ' ' ),
			array( T_DOC_COMMENT_STAR => '*' ),
			array( T_DOC_COMMENT_WHITESPACE => ' ' ),
			array( T_DOC_COMMENT_STRING => 'Unclosed docblock, live coding.... with no blank line at end of file.' ),
			array( T_DOC_COMMENT_WHITESPACE => "\n" ),
			array( T_DOC_COMMENT_WHITESPACE => ' ' ),
			array( T_DOC_COMMENT_CLOSE_TAG => '*' ),
		);

		$target = $this->getTargetToken( '/* ' . __FUNCTION__ . ' */', T_DOC_COMMENT_OPEN_TAG );

		$this->checkTokenSequence( $target, $expectedSequence );
	}//end testLiveCoding()
}//end class
