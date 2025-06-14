<?php

/**
 * @link       http://patchwork2.org/
 * @author     Ignas Rudaitis <ignas.rudaitis@gmail.com>
 * @copyright  2010-2018 Ignas Rudaitis
 * @license    http://www.opensource.org/licenses/mit-license.html
 */
namespace Patchwork\CallRerouting;

class Handle {

	private $references         = array();
	private $expirationHandlers = array();
	private $silenced           = false;
	private $tags               = array();

	public function __destruct() {
		$this->expire();
	}

	public function tag( $tag ) {
		$this->tags[] = $tag;
	}

	public function hasTag( $tag ) {
		return in_array( $tag, $this->tags );
	}

	public function addReference( &$reference ) {
		$this->references[] = &$reference;
	}

	public function expire() {
		foreach ( $this->references as &$reference ) {
			$reference = null;
		}
		if ( ! $this->silenced ) {
			foreach ( $this->expirationHandlers as $expirationHandler ) {
				$expirationHandler();
			}
		}
		$this->expirationHandlers = array();
	}

	public function addExpirationHandler( callable $expirationHandler ) {
		$this->expirationHandlers[] = $expirationHandler;
	}

	public function silence() {
		$this->silenced = true;
	}

	public function unsilence() {
		$this->silenced = false;
	}
}
