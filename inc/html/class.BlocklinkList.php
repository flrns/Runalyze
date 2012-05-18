<?php
/**
 * Class for a list with links as block/big button 
 */
class BlocklinkList {
	/**
	 * Internal array with all links
	 * @var array
	 */
	private $links = array();

	/**
	 * Constructor 
	 */
	public function __construct() {
		
	}

	/**
	 * Destructor 
	 */
	public function __destruct() {
		
	}

	/**
	 * Add a complete link
	 * @param string $link 
	 */
	public function addCompleteLink($link) {
		$this->links[] = $link;
	}

	/**
	 * Add a new link to this list
	 * @param string $href
	 * @param string $title
	 * @param string $description [optional]
	 * @param string $size [optional]
	 */
	public function addStandardLink($href, $title, $description = '', $size = '') {
		$this->links[] = $this->getStandardLinkFor($href, $title, $description, $size);
	}

	/**
	 * Get standard link
	 * @param string $href
	 * @param string $title
	 * @param string $description [optional]
	 * @param string $size [optional]
	 * @return string
	 */
	private function getStandardLinkFor($href, $title, $description = '', $size = '') {
		return Ajax::window('<a href="'.$href.'" title="'.$title.'"><strong>'.$title.'</strong><br /><small>'.$description.'</small></a>', $size);
	}

	/**
	 * Get code for list
	 * @return string 
	 */
	public function getCode() {
		$code  = '<ul class="blocklist">';

		foreach ($this->links as $link)
			$code .= '<li>'.$link.'</li>';

		$code .= '</ul>';

		return $code;
	}

	/**
	 * Display this list 
	 */
	public function display() {
		echo $this->getCode();
	}
}