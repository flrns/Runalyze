<?php
/**
 * This file contains the class::Weather for handling weather-types
 */
/**
 * Class: Weather
 * 
 * @author Hannes Christiansen <mail@laufhannes.de>
 * @version 1.0
 * @uses class::Error
 * @uses class::Mysql
 */
class Weather {
	/**
	* Array containing all rows from database
	* @var array
	*/
	static private $fullArray = null;

	/**
	 * ID for unknown weather in database
	 * @var int
	 */
	static public $UNKNOWN_ID = 1;

	/**
	 * ID for loading weather from API
	 * @var int
	 */
	static public $FORECAST_ID = -1;

	/**
	 * Internal ID
	 * @var int
	 */
	private $id;

	/**
	 * Temperature in degree celsius, is optional
	 * @var int
	 */
	private $temperature;

	/**
	 * Array from database
	 * @var array
	 */
	private $data;

	/**
	 * Constructor
	 */
	public function __construct($weather_id, $temperature = null) {
		$this->id = $weather_id;
		$this->temperature = $temperature;

		if ($this->isForecast())
			$this->loadForecast();
		else
			$this->setData();
	}

	/**
	 * Destructor
	 */
	public function __destruct() {}

	/**
	 * Get object for forecast
	 * @return Weather
	 */
	static public function Forecaster() {
		return new Weather(self::$FORECAST_ID);
	}

	/**
	 * Set array from database as internal data-array
	 */
	private function setData() {
		$array = self::getFullArray();

		if (isset($array[$this->id]))
			$this->data = $array[$this->id];
	}

	/**
	 * Returns the img-Tag for a weather-symbol
	 * @return string img-tag
	 */
	public function icon() {
		return Icon::getWeatherIcon($this->id);
	}

	/**
	 * Returns the name
	 * @return string name for this weather
	 */
	public function name() {
		return $this->data['name'];
	}

	/**
	 * Get as string with icon and temperature if set
	 * @return string
	 */
	public function asString() {
		$string = '';

		if (!$this->isUnknown())
			$string = $this->icon().' ';
		if (!is_null($this->temperature))
			$string .= $this->temperatureString();

		return $string;
	}

	/**
	 * Get string for temperature with unit
	 * @return string
	 */
	public function temperatureString() {
		return Helper::Unknown($this->temperature).' &#176;C';
	}

	/**
	 * Get string with icon, name and temperature
	 * @return string
	 */
	public function fullString() {
		return $this->icon().' '.$this->name().' bei '.$this->temperatureString();
	}

	/**
	 * Is the weather-data empty?
	 * @return bool
	 */
	public function isEmpty() {
		return ($this->isUnknown() && is_null($this->temperature));
	}

	/**
	 * Boolean flag: Is this object a forecast?
	 * @return bool
	 */
	private function isForecast() {
		return ($this->id == self::$FORECAST_ID);
	}

	/**
	 * Is this the ID for unknown weather?
	 * @return bool
	 */
	public function isUnknown() {
		return ($this->id == self::$UNKNOWN_ID);
	}

	/**
	 * Set internal data to post-array if not set
	 */
	public function setPostDataIfEmpty() {
		if (!isset($_POST['weatherid']))
			$_POST['weatherid'] = $this->id;
		if (!isset($_POST['temperature']))
			$_POST['temperature'] = $this->temperature;
	}

	/**
	 * Set default data for internal id/temperature
	 */
	private function setDefaultVars() {
		$this->id = self::$UNKNOWN_ID;
		$this->temperature = NULL;

		$this->setData();
	}

	/**
	 * Load current conditions from API and set as internal data
	 */
	private function loadForecast() {
		if (CONF_PLZ == 0) {
			$this->setDefaultVars();
		} else {
			$Array = $this->loadWeatherAsArrayFromAPI();
			$Current = $Array['xml_api_reply']['weather']['current_conditions'];

			if (!isset($Current['temp_c']) || !isset($Current['condition'])) {
				$this->setDefaultVars();
			} else {
				$this->temperature = (int)$Current['temp_c']['attr']['data'];
				$this->id = $this->getIdFromAPICondition($Current['condition']['attr']['data']);
			}
		}
	}

	/**
	 * Load API-file and parse XML
	 * @return array
	 */
	private function loadWeatherAsArrayFromAPI() {
		require_once 'tcx/class.XmlParser.php';

		$Xml = @file_get_contents('http://www.google.de/ig/api?weather='.CONF_PLZ.'&hl=de');
		$Parser = new XmlParser($Xml);

		return $Parser->getContentAsArray();
	}

	/**
	 * Translate condition-data from API to internal ID
	 * @param string $condition
	 * @return int
	 */
	private function getIdFromAPICondition($condition) {
		$name = $this->translateGoogleConditionToInternalName($condition);
		$data = Mysql::getInstance()->fetchSingle('SELECT `id` FROM `'.PREFIX.'weather` WHERE `name`="'.$name.'"');

		return $data['id'];
	}

	/**
	 * Translate google string for condition to database-string
	 * @param string $string
	 * @return string
	 */
	private function translateGoogleConditionToInternalName($string) {
		switch ($string) {
			case 'Meist sonnig':
			case 'Klar':
				return 'sonnig';
			case 'Teils sonnig':
				return 'heiter';
			case 'Bedeckt':
			case 'Meistens bew�lkt':
			case 'Bew�lkt':
			case 'Nebel':
				return 'bew&ouml;lkt';
			case 'Vereinzelt st�rmisch':
			case 'Vereinzelte Schauer':
			case 'Vereinzelt Regen':
			case 'Leichter Regen':
			case 'Nieselregen':
			case 'Dunst':
				return 'wechselhaft';
			case 'Regen':
			case 'Gewitterschauer':
				return 'regnerisch';
			case 'Schnee':
				return 'Schnee';
			default:
				Error::getInstance()->addNotice('Unknown condition from GoogleWeatherAPI: "'.$string.'"');
				return 'unbekannt';
		}
	}

	/**
	 * Get all rows from database
	 * @return array
	 */
	static public function getFullArray() {
		if (is_null(self::$fullArray)) {
			$array = Mysql::getInstance()->fetchAsArray('SELECT * FROM `'.PREFIX.'weather` ORDER BY `id` ASC');
			foreach ($array as $data)
				self::$fullArray[$data['id']] = $data;
		}

		return self::$fullArray;
	}

	/**
	 * Get all rows except the one for unknown weather
	 * @return array
	 */
	static public function getArrayWithoutUnknown() {
		$array = self::getFullArray();
		unset($array[self::$UNKNOWN_ID]);

		return $array;
	}

	/**
	 * Get select-box for all weather-ids
	 * @param mixed $selected [optional] Value to be selected
	 * @return string
	 */
	static public function getSelectBox($selected = -1) {
		if ($selected == -1 && isset($_POST['weatherid']))
			$selected = $_POST['weatherid'];

		$weather = self::getFullArray();
		foreach ($weather as $id => $data)
			$weather[$id] = $data['name'];

		return HTML::selectBox('weatherid', $weather, $selected);
	}
}
?>