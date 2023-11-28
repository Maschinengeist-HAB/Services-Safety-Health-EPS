#! /usr/bin/env php
<?php
# ------------------------------------------------------------------------------------------ global
namespace Maschinengeist\Services\Safety\Health\EPS;

/** @noinspection PhpMultipleClassDeclarationsInspection */

use ErrorException;
use Exception;
use PhpMqtt\Client\{Exceptions\DataTransferException, Exceptions\InvalidMessageException, Exceptions\MqttClientException,
    Exceptions\ProtocolViolationException,
    Exceptions\RepositoryException,
    MqttClient,
    ConnectionSettings};
use JsonException;

error_reporting(E_ALL);
date_default_timezone_set($_ENV['TZ'] ?? 'Europe/Berlin');
define('SERVICE_NAME', 'maschinengeist-services-safety-health-eps');
# ------------------------------------------------------------------------------------------ resolve dependencies
require_once __DIR__ . '/vendor/autoload.php';

spl_autoload_register(
/**
 * @param $class_name
 * @return void
 */
    function ($class_name) {
        $class_name = str_replace('\\', DIRECTORY_SEPARATOR, $class_name);
        require '/opt/Library/' . $class_name . '.php';
    }
);

set_error_handler(
/**
 * @throws ErrorException
 */
function ($severity, $message, $file, $line) {
        throw new ErrorException($message, $severity, $severity, $file, $line);
    }
);

# ------------------------------------------------------------------------------------------ configuration
require_once 'Config.php';

# ------------------------------------------------------------------------------------------ banner
error_log(sprintf('Welcome to the %s service, version %s ', SERVICE_NAME, Config::getVersion()));
error_log("Current configuration:");
error_log(print_r(Config::getCurrentConfig(), true));

# ------------------------------------------------------------------------------------------ main
$mqttClient = null;


$mqttConnectionSettings = (new ConnectionSettings)
    ->setKeepAliveInterval(Config::getMqttKeepAlive())
    ->setLastWillTopic(Config::getMqttLwtTopic())
    ->setRetainLastWill(true)
    ->setLastWillMessage('offline');

if (Config::getMqttUsername()) {
    $return = $mqttConnectionSettings->setUsername(Config::getMqttUsername());
}

if (Config::getMqttPassword()) {
    $return = $mqttConnectionSettings->setPassword(Config::getMqttPassword());
}

try {
    $mqttClient = new MqttClient(Config::getMqttHost(), Config::getMqttPort(), SERVICE_NAME);
} catch (Exception $exception) {
    trigger_error('Cannot create mqtt object: ' . $exception->getMessage());
}

if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGINT, function () use ($mqttClient) {
        $mqttClient->interrupt();
    });
}

try {
    $mqttClient->connect($mqttConnectionSettings);

    if (Config::getVerbose()) {
        error_log('Connected to ' . Config::getMqttHost() );
    }
} catch (Exception $e) {
    error_log(
        sprintf(
            "Can't connect to %s (%s): %s. Aborting.", Config::getMqttHost(), Config::getMqttPort(), $e->getMessage()
        )
    );
    exit(107);
}

try {
    $mqttClient->publish(Config::getMqttLwtTopic(), 'online');
    if (Config::getVerbose()) {
        error_log("Published LWT message to " . Config::getMqttLwtTopic());
    }
} catch (DataTransferException|RepositoryException $e) {
    error_log('Publishing first LWT message was not possible: ' . $e->getMessage());
}

function log_errors(string $error_msg, MqttClient $mqttClient, string $topic = 'error/' . SERVICE_NAME): void {
    error_log($error_msg);
    try {
        $mqttClient->publish($topic, $error_msg, 1);
    } catch (DataTransferException|RepositoryException $e) {
        error_log('Publishing the error message via MQTT was not possible: ' . $e->getMessage());
    }
}

try {
    $mqttClient->subscribe(
    /**
     * @param $topic
     * @param $message
     * @return void
     * @throws DataTransferException
     * @throws RepositoryException
     */
        Config::getMqttCommandTopic(), function ($topic, $message) use ($mqttClient) {

        if ($message) {

            try {
                $message_data = json_decode($message, true, 512, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                log_errors($e->getMessage(), $mqttClient, Config::getMqttErrorTopic());
                return;
            }

            switch ($message_data['command']) {

                case 'update':
                    if (Config::getVerbose()) {
                        error_log("Got update request via $topic:");
                        error_log(print_r($message_data, true));
                    }

                    try {
                        $result = get_current_eps($message_data['vars']);
                        $result = json_encode($result);
                        $mqttClient->publish(Config::getMqttDataTopic(), $result);
                    } catch (ErrorException|Exception $e) {
                        log_errors($e->getMessage(), $mqttClient, Config::getMqttErrorTopic());
                    }

                    break;

                default:
                    $error = json_encode(array(
                        'error' => 'Command with empty or unknown action received',
                        'message' => $message_data,
                    ));

                    log_errors($error, $mqttClient, Config::getMqttErrorTopic());
                    return;
            }

        }
    }, 0);
} catch (DataTransferException|RepositoryException $e) {
    log_errors($e->getMessage(), $mqttClient, Config::getMqttErrorTopic());
}


/**
 * @throws ErrorException
 * @throws JsonException
 * @noinspection PhpMultipleClassDeclarationsInspection
 */
function get_current_eps(Array $parameters) : array|string {

    $eps_file = file_get_contents(Config::getEpsSearchUri());

    if (FALSE === $eps_file) {
        throw new ErrorException(
                "Could not get the content for location "
            . Config::getEpsSearchUri()
        );
    }

    preg_match(Config::getTokenFileRegEx(), $eps_file, $token_file_matches);

    if (sizeof($token_file_matches) !== 1) {
        throw new ErrorException("Could not get the name of the token file from "
            . Config::getEpsSearchUri()
        );
    }

    $token_file = file_get_contents(Config::getEpsBaseUri() . $token_file_matches[0]);

    if (FALSE === $token_file) {
        throw new ErrorException(
                "Can't get the content for location "
                . Config::getEpsBaseUri() . $token_file_matches[0]);
    }

    preg_match(Config::getTokenRegEx(), $token_file, $token_matches);

    if (sizeof($token_matches) != 2) {
        throw new ErrorException(
        'Not enough matches in the token containing file '
            . Config::getEpsBaseUri() . $token_file_matches[0]
        );
    }

    $url = sprintf(
        '%s'
        . '?tx_aponetpharmacy_search[action]=result'
        . '&tx_aponetpharmacy_search[controller]=Search&'
        . '&tx_aponetpharmacy_search[search][lat]=%s'
        . '&tx_aponetpharmacy_search[search][lng]=%s'
        . '&tx_aponetpharmacy_search[search][radius]=0'
        . '&tx_aponetpharmacy_search[token]=%s'
        . '&type=1981',
        Config::getEpsSearchUri(),
        ($parameters['latitude']  ?? Config::getDefaultLatitude()),
        ($parameters['longitude'] ?? Config::getDefaultLongitude()),
        urlencode($token_matches[1]),
    );

    if (Config::getVerbose()) {
        error_log("Requested URI: $url");
    }

    $http_options = array(
        'http'=>array(
            'method'=>  "GET",
            'header'=>  "Accept: application/json\r\n" . "Accept-Charset: utf-8, iso-8859-1\r\n"
        )
    );
    $context = stream_context_create($http_options);

    $result = json_decode(
        file_get_contents($url, false, $context),
        flags: JSON_OBJECT_AS_ARRAY|JSON_THROW_ON_ERROR
    );

    if ($result['results']['statistik']['anzahl'] === 0) {
        $result = array('results' => array('count' => 0, 'data' => null));

        if (Config::getVerbose()) {
            error_log("No results from service returned");
            print_r($result);
        }

        return $result;
    }

    # show at least one entry, but more are ok
    $showEntries = ($parameters['show'] ?? 1);
    $showEntries = ($showEntries < 1) ? 1 : $showEntries;

    $filteredEntries = array_slice($result['results']['apotheken']['apotheke'], 0, $showEntries);

    $result = array(
        'results' => array(
            'count' => sizeof($filteredEntries),
            'data' => $filteredEntries)
    );

    if (Config::getVerbose()) {
        error_log("Results returned:");
        print_r($result);
    }

    return $result;
}

try {
    $mqttClient->loop();
} catch (DataTransferException|InvalidMessageException|ProtocolViolationException|MqttClientException $e) {
    error_log($e->getMessage());
    exit(121);
}

try {
    $mqttClient->disconnect();
} catch (DataTransferException $e) {
    error_log(sprintf("Can't disconnect from MQTT: %s", $e->getMessage()));
    exit(121);
}