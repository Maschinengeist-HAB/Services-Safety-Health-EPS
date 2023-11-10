<?php
namespace Maschinengeist\Services\Safety\Health\EPS;
class Config {

    /**
     * Version of this service
     * @return string Current service version
     */
    public static function getVersion() : string {
        return '1.0.0';
    }

    /**
     * Name or IP address pointing to the MQTT broker
     * Can be set via MQTT_HOST environment variable
     * @return string defaults to message-broker
     */
    public static function getMqttHost() : string {
        return ($_ENV['MQTT_HOST'] ?? 'message-broker');
    }

    /**
     * Port number to the MQTT broker
     * Can be set via MQTT_PORT environment variable
     * @return int defaults to 1883
     * @throws \TypeError if MQTT_PORT environment variable does not look like an int
     * @throws \OutOfRangeException if MQTT_PORT environment variable fits not into the port range
     */
    public static function getMqttPort() : int {
        # value not set, so it's from here and trustworthy
        if (false === isset($_ENV['MQTT_PORT'])) {
            return 1883;
        }

        $port = $_ENV['MQTT_PORT'];

        $filter_options = array(
            'options' => array(
                'default'   => null,
            ),
        );

        if ( TRUE === is_null(filter_var($port, FILTER_VALIDATE_INT, $filter_options))) {
            throw new \TypeError("MQTT_PORT '$port' is not an integer");
        }

        $filter_options = array(
            'options' => array(
                'default'   => null,
                'min_range' => 1,
                'max_range' => 65535,
            ),
        );

        if ( TRUE === is_null(filter_var($port, FILTER_VALIDATE_INT, $filter_options))) {
            throw new \OutOfRangeException("MQTT PORT '$port' is not between 1 and 65535 and thus no valid port");
        }

        return $port;
    }

    /**
     * Set username for the MQTT_BROKER
     * Can be set via MQTT_USERNAME environment variable
     * @return string Empty string
     */
    public static function getMqttUsername() : string {
        return ($_ENV['MQTT_USERNAME'] ?? '');
    }

    /**
     * Set password for the MQTT_BROKER
     * Can be set via MQTT_PASSWORD environment variable
     * @return string Empty string
     */
    public static function getMqttPassword() : string {
        return ($_ENV['MQTT_PASSWORD'] ?? '');
    }

    /**
     * MQTT keep alive flag
     * Can be set via MQTT_KEEP_ALIVE
     * This setting tries hard to guess what you want (i.e. on, off, TRUE, False, 1, 0) will be mostly doing what
     * you expect, but in the end, it will be defaulting to true, if not guessing what to do.
     * @return bool defaults to TRUE
     */
    public static function getMqttKeepAlive() : bool {
        $flag = ($_ENV['MQTT_KEEP_ALIVE'] ?? TRUE);
        $flag = filter_var($flag, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        return (is_null($flag)) ? TRUE : $flag;
    }

    /**
     * MQTT base topic - every other topic will expand on this
     * Can be set via MQTT_BASE_TOPIC
     * @return string defaults to maschinengeist/services/safety/health/eps/data
     */
    public static function getMqttBaseTopic() : string {
        return ($_ENV['MQTT_BASE_TOPIC'] ?? 'maschinengeist/services/safety/health/eps');
    }

    /**
     * MQTT LWT topic
     * Can be set via MQTT_LWT_TOPIC
     * @return string defaults to maschinengeist/services/safety/health/eps/lwt
     */
    public static function getMqttLwtTopic() : string {
        return ($_ENV['MQTT_LWT_TOPIC'] ?? self::getMqttBaseTopic() . '/lwt');
    }

    /**
     * MQTT topic for result data, but not for errors
     * @return string defaults to maschinengeist/services/safety/health/eps/data
     */
    public static function getMqttDataTopic() : string {
        return self::getMqttBaseTopic() . '/data';
    }

    /**
     * MQTT error topic - errors are reported to this topic and to STDERR
     * Can be set via environment variable MQTT_ERROR_TOPIC
     * @return string defaults to maschinengeist/services/safety/health/eps/errors
     */
    public static function getMqttErrorTopic() : string {
        return ($_ENV['MQTT_ERROR_TOPIC'] ?? self::getMqttBaseTopic() . '/errors');
    }

    /**
     * MQTT command topic
     * @return string defaults to maschinengeist/services/safety/health/eps/command
     */
    public static function getMqttCommandTopic() : string {
        return self::getMqttBaseTopic() . '/command';
    }

    /**
     * Base URI to the german EPS info
     * Can be set via EPS_URI environment variable
     * @return string defaults to https://www.aponet.de
     * @throws \ErrorException if EPS_URI is set but empty
     * @throws \ErrorException if EPS_URI is set but does not look like a URI
     */
    public static function getEpsBaseUri() : string {

        if (FALSE === isset($_ENV['EPS_URI'])) {
            return 'https://www.aponet.de';
        }

        $uri = filter_input(INPUT_ENV, 'EPS_URI', FILTER_VALIDATE_URL,
            FILTER_NULL_ON_FAILURE);

        if (FALSE === $uri) {
            throw new \ErrorException("EPS_URI is given, but not set");
        }

        if (NULL === $uri) {
            throw new \ErrorException("EPS_URI is set, but does not look like an valid URI");
        }

        return $uri;
    }


    /**
     * @return string URI path to the EPS search endpoint
     * @throws \ErrorException if EPS base uri is empty or not a valid URI
     */
    public static function getEpsSearchUri() : string {
        return self::getEpsBaseUri() . '/apotheke/notdienstsuche';
    }

    /**
     * @return string Regex for extract the file name containing the token
     */
    public static function getTokenFileRegEx(): string {
        return '!/typo3temp\/assets\/compressed\/pharmacymap-\w+?\.js!';
    }

    /**
     * @return string Regex for extract the current token from the token containing file
     */
    public static function getTokenRegEx() : string {
        return (string) "!randomToken\s*=\s*'(\w+)'!";
    }


    /**
     * Default latitude for the getting the nearest EPS pharmacy
     * Can be set via DEFAULT_LATITUDE environment variable
     * @return float 51.9117
     * @throws \ErrorException if DEFAULT_LATITUDE is set but empty
     * @throws \ErrorException if DEFAULT_LATITUDE is set but does not look like a URI
     */
    public static function getDefaultLatitude() : float {

        if (FALSE === isset($_ENV['DEFAULT_LATITUDE'])) {
            return 51.9117;
        }

        $filter_options = array(
            'options' => array(
                'default'   => null,
                'min_range' => -180,
                'max_range' => 180,
            ),
        );

        $latitude = filter_input(
            INPUT_ENV,
            'DEFAULT_LATITUDE',
            FILTER_VALIDATE_FLOAT,
            $filter_options
        );

        if (FALSE === $latitude) {
            throw new \ErrorException("DEFAULT_LATITUDE is given, but not set");
        }

        if (NULL === $latitude) {
            throw new \ErrorException("DEFAULT_LATITUDE is set, but does not look like a float");
        }

        return $latitude;
    }

    /**
     * Default longitude for the getting the nearest EPS pharmacy
     *  Can be set via DEFAULT_LONGITUDE environment variable
     * @return float 8.8394
     */
    public static function getDefaultLongitude() : float {

        if (FALSE === isset($_ENV['DEFAULT_LONGITUDE'])) {
            return 8.8394;
        }

        $filter_options = array(
            'options' => array(
                'default'   => null,
                'min_range' => -90,
                'max_range' => 90,
            ),
        );

        $longitude = filter_input(
            INPUT_ENV,
            'DEFAULT_LONGITUDE',
            FILTER_VALIDATE_FLOAT,
            $filter_options
        );

        if (FALSE === $longitude) {
            throw new \ErrorException("DEFAULT_LONGITUDE is given, but not set");
        }

        if (NULL === $longitude) {
            throw new \ErrorException("DEFAULT_LONGITUDE is set, but does not look like a float");
        }

        return $longitude;

    }

    /**
     * Set verbose message output
     * Can be set via VERBOSE environment variable
     * @return bool FALSE, disable verbose output
     * @TODO Maybe named better debug?
     */
    public static function getVerbose() : bool {
        return (filter_var($_ENV['VERBOSE'] ?? FALSE , FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Return all the configuration
     * @return array[] Array with current configuration
     * @throws \ErrorException
     */
    public static function getCurrentConfig(): array {
        return array(
            'mqtt topics'       => array(
                'base'          => self::getMqttBaseTopic(),
                'result'        => self::getMqttDataTopic(),
                'command'       => self::getMqttCommandTopic(),
                'lwt'           => self::getMqttLwtTopic(),
                'error'         => self::getMqttErrorTopic(),
            ),
            'connection data' => array(
                'host'          => self::getMqttHost(),
                'port'          => self::getMqttPort(),
                'user'          => self::getMqttUsername(),
                'password'      => str_repeat('*', random_int(10,16)),
                'keep alive'    => self::getMqttKeepAlive(),
            ),
            'application config' => array(
                'Verbose' => self::getVerbose(),

                'EPS Online Service' => array(
                    'base uri' => self::getEpsBaseUri(),
                    'search uri' => self::getEpsSearchUri(),
                ),
                'Scraper' => array(
                    'token file regex' => self::getTokenFileRegEx(),
                    'token regex' => self::getTokenRegEx(),
                ),
                'Request default values' => array(
                    'latitude' => self::getDefaultLatitude(),
                    'longitude' => self::getDefaultLongitude(),
                )
            ),
        );
    }
}