<?php
/**
 * PoundPay Client Library
 *
 * @category   APIClients
 * @package    PoundPay
 * @author     PoundPay Inc.
* @version    v2.1.0
 * @link       http://dev.poundpay.com/
 */


namespace PoundPay;
require_once __DIR__ . '/Autoload.php';

class Resource {
    /** @var APIClient set by PoundPay\Core::configure() **/
    protected static $_client;
    /** @var string must be set by subclass **/
    protected static $_name;

    public function __construct($vars = array()) {
        $this->setVars($vars);
    }

    public function setVars($vars) {
        foreach ($vars as $key => $val) {
            $this->$key = $val;
        }
    }

    public static function all() {
        $resp = self::$_client->get(static::$_name);
        $resources = array();
        foreach ($resp->json[static::$_name] as $vars) {
            $resources[] = new static($vars);
        }
        return $resources;
    }

    public static function find($sid) {
        $resp = self::$_client->get(self::getPath($sid));
        return new static($resp->json);
    }

    public function save() {
        if (isset($this->sid)) {
            $vars = self::update($this->sid, get_object_vars($this));
        } else {
            $vars = self::create(get_object_vars($this));
        }
        $this->setVars($vars);
        return $this;
    }

    public function delete($sid) {
        self::$_client->delete(self::getPath($sid));
    }

    protected static function update($sid, $vars) {
        $resp = self::$_client->put(self::getPath($sid), $vars);
        return $resp->json;
    }

    protected static function create($vars) {
        $resp = self::$_client->post(static::$_name, $vars);
        return $resp->json;
    }

    protected static function getPath($sid) {
        return static::$_name . '/' . $sid;
    }

    public static function setClient($client) {
        self::$_client = $client;
    }

    public static function getClient() {
        return self::$_client;
    }
}
