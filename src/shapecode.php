<?php

/**
 * A Shapeways API library in PHP.
 *
 * @package   shapecode
 * @version   1.1.0-dev
 * @author    Jublo Solutions <support@jublo.net>
 * @copyright 2014 Jublo Solutions <support@jublo.net>
 * @license   http://opensource.org/licenses/LGPL-3.0 GNU Lesser General Public License 3.0
 * @link      https://github.com/jublonet/shapecode-php
 */

/**
 * Define constants
 */
$constants = explode(' ', 'OBJECT ARRAY JSON');
foreach ($constants as $i => $id) {
    $id = 'SHAPECODE_RETURNFORMAT_' . $id;
    defined($id) or define($id, $i);
}
$constants = array(
    'CURLE_SSL_CERTPROBLEM' => 58,
    'CURLE_SSL_CACERT' => 60,
    'CURLE_SSL_CACERT_BADFILE' => 77,
    'CURLE_SSL_CRL_BADFILE' => 82,
    'CURLE_SSL_ISSUER_ERROR' => 83
);
foreach ($constants as $id => $i) {
    defined($id) or define($id, $i);
}
unset($constants);
unset($i);
unset($id);

/**
 * A Shapeways API library in PHP.
 *
 * @package shapecode
 * @subpackage shapecode-php
 */
class Shapecode
{
    /**
     * The current singleton instance
     */
    private static $_instance = null;

    /**
     * The OAuth consumer key of your registered app
     */
    protected static $_oauth_consumer_key = null;

    /**
     * The corresponding consumer secret
     */
    protected static $_oauth_consumer_secret = null;

    /**
     * The API endpoint to use
     */
    protected static $_endpoint = 'https://api.shapeways.com/%s/v1';

    /**
     * The Request or access token. Used to sign requests
     */
    protected $_oauth_token = null;

    /**
     * The corresponding request or access token secret
     */
    protected $_oauth_token_secret = null;

    /**
     * The format of data to return from API calls
     */
    protected $_return_format = SHAPECODE_RETURNFORMAT_OBJECT;

    /**
     * The file formats that Shapeways accepts as image uploads
     */
    protected $_supported_media_files = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG);

    /**
     * The current Shapecode version
     */
    protected $_version = '1.1.0-dev';

    /**
     * Auto-detect cURL absence
     */
    protected $_use_curl = true;

    /**
     * Request timeout
     */
    protected $_timeout = 2000;

    /**
     * Connection timeout
     */
    protected $_connectionTimeout = 5000;

    /**
     *
     * Class constructor
     *
     */
    public function __construct()
    {
        // Pre-define $_use_curl depending on cURL availability
        $this->setUseCurl(function_exists('curl_init'));
    }

    /**
     * Returns singleton class instance
     * Always use this method unless you're working with multiple authenticated users at once
     *
     * @return Shapecode The instance
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::$_instance = new self;
        }
        return self::$_instance;
    }

    /**
     * Sets the OAuth consumer key and secret (App key)
     *
     * @param string $key    OAuth consumer key
     * @param string $secret OAuth consumer secret
     *
     * @return void
     */
    public static function setConsumerKey($key, $secret)
    {
        self::$_oauth_consumer_key    = $key;
        self::$_oauth_consumer_secret = $secret;
    }

    /**
     * Gets the current Shapeways version
     *
     * @return string The version number
     */
    public function getVersion()
    {
        return $this->_version;
    }

    /**
     * Sets the OAuth request or access token and secret (User key)
     *
     * @param string $token  OAuth request or access token
     * @param string $secret OAuth request or access token secret
     *
     * @return void
     */
    public function setToken($token, $secret)
    {
        $this->_oauth_token        = $token;
        $this->_oauth_token_secret = $secret;
    }

    /**
     * Sets if Shapecode should use cURL
     *
     * @param bool $use_curl Request uses cURL or not
     *
     * @return void
     */
    public function setUseCurl($use_curl)
    {
        if ($use_curl && ! function_exists('curl_init')) {
            throw new \Exception('To use cURL, the PHP curl extension must be available.');
        }

        $this->_use_curl = (bool) $use_curl;
    }

    /**
     * Sets request timeout in milliseconds
     *
     * @param int $timeout Request timeout in milliseconds
     *
     * @return void
     */
    public function setTimeout($timeout)
    {
        $this->_timeout = (int) $timeout;
    }

    /**
     * Sets connection timeout in milliseconds
     *
     * @param int $timeout Connection timeout in milliseconds
     *
     * @return void
     */
    public function setConnectionTimeout($timeout)
    {
        $this->_connectionTimeout = (int) $timeout;
    }

    /**
     * Sets the format for API replies
     *
     * @param int $return_format One of these:
     *                           SHAPECODE_RETURNFORMAT_OBJECT (default)
     *                           SHAPECODE_RETURNFORMAT_ARRAY
     *
     * @return void
     */
    public function setReturnFormat($return_format)
    {
        $this->_return_format = $return_format;
    }

    /**
     * Get allowed API methods, sorted by HTTP method
     * Watch out for multiple-methods!
     *
     * @return array $apimethods
     */
    public function getApiMethods()
    {
        static $apimethods = array(
            'GET' => array(
                // API
                'api',

                // Cart
                'orders/cart',

                // Materials
                'materials',
                'materials/{materialId}',

                // Models
                'models',
                'models/{modelId}',
                'models/{modelId}/info',
                'models/{modelId}/files/{fileVersion}',

                // Printers
                'printers',
                'printers/{printerId}',

                // Category
                'categories',
                'categories/{categoryId}'
            ),
            'POST' => array(
                // OAuth1
                'oauth1/access_token',
                'oauth1/request_token',

                // Cart
                'orders/cart (POST)',

                // Models
                'models (POST)',
                'models/{modelId}/files',
                'models/{modelId}/photos',

                // Price
                'price'
            ),
            'PUT' => array(
                // Models
                'models/{modelId}/info (PUT)'
            ),
            'DELETE' => array(
                'models/{modelId} (DELETE)'
            )
        );
        return $apimethods;
    }

    /**
     * Main API handler working on any requests you issue
     *
     * @param string $fn    The member function you called
     * @param array $params The parameters you sent along
     *
     * @return mixed The API reply encoded in the set return_format
     */

    public function __call($fn, $params)
    {
        // parse parameters
        $apiparams = array();
        if (count($params) > 0) {
            if (is_array($params[0])) {
                $apiparams = $params[0];
                if (! is_array($apiparams)) {
                    $apiparams = array();
                }
            } else {
                parse_str($params[0], $apiparams);
                if (! is_array($apiparams)) {
                    $apiparams = array();
                }
                // remove auto-added slashes if on magic quotes steroids
                if (get_magic_quotes_gpc()) {
                    foreach($apiparams as $key => $value) {
                        if (is_array($value)) {
                            $apiparams[$key] = array_map('stripslashes', $value);
                        } else {
                            $apiparams[$key] = stripslashes($value);
                        }
                    }
                }
            }
        }

        // stringify null and boolean parameters
        foreach ($apiparams as $key => $value) {
            if (! is_scalar($value)) {
                continue;
            }
            if (is_null($value)) {
                $apiparams[$key] = 'null';
            } elseif (is_bool($value)) {
                $apiparams[$key] = $value ? 'true' : 'false';
            }
        }

        // reset token when requesting a new token (causes 401 for signature error on 2nd+ requests)
        if ($fn === 'oauth_requestToken') {
            $this->setToken(null, null);
        }

        // map function name to API method
        $method = '';

        // replace _ by /
        $path = explode('_', $fn);
        for ($i = 0; $i < count($path); $i++) {
            if ($i > 0) {
                $method .= '/';
            }
            $method .= $path[$i];
        }

        // replace AA by URL parameters
        $method_template = $method;
        $match           = array();
        if (preg_match('/[A-Z_]{2,}/', $method, $match)) {
            foreach ($match as $param) {
                $param_l = strtolower($param);
                if (substr($param_l, -2) === 'id') {
                    $param_l = substr($param_l, 0, -2) . 'Id';
                }
                if (substr($param_l, -7) === 'version') {
                    $param_l = substr($param_l, 0, -7) . 'Version';
                }
                $method_template = str_replace($param, '{' . $param_l . '}', $method_template);
                if (!isset($apiparams[$param_l])) {
                    for ($i = 0; $i < 26; $i++) {
                        $method_template = str_replace(chr(65 + $i), '_' . chr(97 + $i), $method_template);
                    }
                    $method_template = str_replace(
                        array('_id', '_version'),
                        array('Id', 'Version'),
                        $method_template
                    );
                    throw new Exception(
                        'To call the templated method "' . $method_template
                        . '", specify the parameter value for "' . $param_l . '".'
                    );
                }
                $method  = str_replace($param, $apiparams[$param_l], $method);
                unset($apiparams[$param_l]);
            }
        }

        // replace A-Z by _a-z
        for ($i = 0; $i < 26; $i++) {
            $method  = str_replace(chr(65 + $i), '_' . chr(97 + $i), $method);
            $method_template = str_replace(chr(65 + $i), '_' . chr(97 + $i), $method_template);
        }
        $method_template = str_replace(
            array('_id', '_version'),
            array('Id', 'Version'),
            $method_template
        );

        $httpmethod = $this->_detectMethod($method_template, $apiparams);

        return $this->_callApi(
            $httpmethod,
            $method,
            $method_template,
            $apiparams
        );
    }

    /**
     * Check if there were any SSL certificate errors
     *
     * @param int $validation_result The curl error number
     *
     * @return void
     */
    protected function _validateSslCertificate($validation_result)
    {
        if (in_array(
                $validation_result,
                array(
                    CURLE_SSL_CERTPROBLEM,
                    CURLE_SSL_CACERT,
                    CURLE_SSL_CACERT_BADFILE,
                    CURLE_SSL_CRL_BADFILE,
                    CURLE_SSL_ISSUER_ERROR
                )
            )
        ) {
            throw new \Exception(
                'Error ' . $validation_result
                . ' while validating the Shapeways API certificate.'
            );
        }
    }

    /**
     * Signing helpers
     */

    /**
     * URL-encodes the given data
     *
     * @param mixed $data
     *
     * @return mixed The encoded data
     */
    protected function _url($data)
    {
        if (is_array($data)) {
            return array_map(array(
                $this,
                '_url'
            ), $data);
        } elseif (is_scalar($data)) {
            return str_replace(array(
                '+',
                '!',
                '*',
                "'",
                '(',
                ')'
            ), array(
                ' ',
                '%21',
                '%2A',
                '%27',
                '%28',
                '%29'
            ), rawurlencode($data));
        } else {
            return '';
        }
    }

    /**
     * Gets the base64-encoded SHA1 hash for the given data
     *
     * @param string $data The data to calculate the hash from
     *
     * @return string The hash
     */
    protected function _sha1($data)
    {
        if (self::$_oauth_consumer_secret === null) {
            throw new Exception('To generate a hash, the consumer secret must be set.');
        }
        if (! function_exists('hash_hmac')) {
            throw new Exception('To generate a hash, the PHP hash extension must be available.');
        }
        return base64_encode(hash_hmac(
            'sha1',
            $data,
            self::$_oauth_consumer_secret
            . '&'
            . ($this->_oauth_token_secret != null
                ? $this->_oauth_token_secret
                : ''
            ),
            true
        ));
    }

    /**
     * Generates a (hopefully) unique random string
     *
     * @param int optional $length The length of the string to generate
     *
     * @return string The random string
     */
    protected function _nonce($length = 8)
    {
        if ($length < 1) {
            throw new Exception('Invalid nonce length.');
        }
        return substr(md5(microtime(true)), 0, $length);
    }

    /**
     * Generates an OAuth signature
     *
     * @param string          $httpmethod Usually either 'GET' or 'POST' or 'DELETE'
     * @param string          $method     The API method to call
     * @param array  optional $params     The API call parameters, associative
     * @param bool   optional append_to_get Whether to append the OAuth params to GET
     *
     * @return string Authorization HTTP header
     */
    protected function _sign($httpmethod, $method, $params = array(), $append_to_get = false)
    {
        if (self::$_oauth_consumer_key === null) {
            throw new Exception('To generate a signature, the consumer key must be set.');
        }
        $sign_params      = array(
            'consumer_key'     => self::$_oauth_consumer_key,
            'version'          => '1.0',
            'timestamp'        => time(),
            'nonce'            => $this->_nonce(),
            'signature_method' => 'HMAC-SHA1'
        );
        $sign_base_params = array();
        foreach ($sign_params as $key => $value) {
            $sign_base_params['oauth_' . $key] = $this->_url($value);
        }
        if ($this->_oauth_token != null) {
            $sign_base_params['oauth_token'] = $this->_url($this->_oauth_token);
        }
        $oauth_params = $sign_base_params;
        foreach ($params as $key => $value) {
            $sign_base_params[$key] = $this->_url($value);
        }
        ksort($sign_base_params);
        $sign_base_string = '';
        foreach ($sign_base_params as $key => $value) {
            $sign_base_string .= $key . '=' . $value . '&';
        }
        $sign_base_string = substr($sign_base_string, 0, -1);
        $signature        = $this->_sha1($httpmethod . '&' . $this->_url($method) . '&' . $this->_url($sign_base_string));

        $params = $append_to_get ? $sign_base_params : $oauth_params;
        $params['oauth_signature'] = $signature;
        $keys = $params;
        ksort($keys);
        if ($append_to_get) {
            $authorization = '';
            foreach ($keys as $key => $value) {
                $authorization .= $key . '="' . $this->_url($value) . '", ';
            }
            return authorization.substring(0, authorization.length - 1);
        }
        $authorization = 'OAuth ';
        foreach ($keys as $key => $value) {
            $authorization .= $key . "=\"" . $this->_url($value) . "\", ";
        }
        return substr($authorization, 0, -2);
    }

    /**
     * Detects HTTP method to use for API call
     *
     * @param string       $method The API method to call
     * @param array  byref $params The parameters to send along
     *
     * @return string The HTTP method that should be used
     */
    protected function _detectMethod($method, &$params)
    {
        // multi-HTTP method endpoints
        switch ($method) {
            case 'orders/cart':
                // detect orders/cart from number of params
                $method = count($params) > 0 ? $method . ' (POST)' : $method;
                break;
            case 'models':
                // detect models from required fileName param
                $method = isset($params['fileName']) ? $method . ' (POST)' : $method;
                break;
            case 'models/{modelId}':
                // detect models/{modelId} from delete param
                if (isset($params['delete']) && $params['delete']) {
                    $method .= ' (DELETE)';
                    unset($params['delete']);
                }
                break;
            case 'models/{modelId}/info':
                // detect models/{modelId}/info from any param
                $method = count($params) > 0 ? $method . ' (PUT)' : $method;
                break;
        }

        $apimethods = $this->getApiMethods();
        foreach ($apimethods as $httpmethod => $methods) {
            if (in_array($method, $methods)) {
                return $httpmethod;
            }
        }
        throw new Exception('Can\'t find HTTP method to use for "' . $method . '".');
    }

    /**
     * Detect filenames in upload parameters,
     * encode loaded files
     *
     * @param string       $method_template  Templated API method to call
     * @param array  byref $params           Parameters to send along
     *
     * @return void
     */
    protected function _encodeFiles($method_template, &$params)
    {
        // only check specific parameters
        $possible_files = array(
            'models' => 'file',
            'models/{modelId}/files' => 'file'
        );
        // method might have files?
        if (! in_array($method_template, array_keys($possible_files))) {
            return;
        }

        $possible_files = explode(' ', $possible_files[$method_template]);

        foreach ($params as $key => $value) {
            // check for filenames
            if (in_array($key, $possible_files)) {
                if (// is it a file, a readable one?
                    @file_exists($value)
                    && @is_readable($value)
                ) {
                    // try to read the file
                    $data = @file_get_contents($value);
                    if ($data === false || strlen($data) === 0) {
                        continue;
                    }
                    $params[$key] = rawurlencode(base64_encode($data));
                }
            }
        }

        return;
    }


    /**
     * Builds the complete API endpoint url
     *
     * @param string $method The API method to call
     *
     * @return string The URL to send the request to
     */
    protected function _getEndpoint($method)
    {
        $url = sprintf(self::$_endpoint, $method);
        // special trailing slash for this method
        if ($method === 'api') {
            $url .= '/';
        }
        return $url;
    }

    /**
     * Calls the API
     *
     * @param string          $httpmethod      HTTP method to use for making the request
     * @param string          $method          API method to call
     * @param string          $method_template Templated API method to call
     * @param array  optional $params          parameters to send along
     *
     * @return mixed The API reply, encoded in the set return_format
     */

    protected function _callApi($httpmethod, $method, $method_template, $params = array())
    {
        if ($this->_oauth_token === null
            && substr($method, 0, 5) !== 'oauth'
        ) {
                throw new \Exception('To call this API, the OAuth access token must be set.');
        }
        if ($this->_use_curl) {
            return $this->_callApiCurl($httpmethod, $method, $method_template, $params);
        }
        return $this->_callApiNoCurl($httpmethod, $method, $method_template, $params);
    }

    /**
     * Calls the API using cURL
     *
     * @param string          $httpmethod      HTTP method to use for making the request
     * @param string          $method          API method to call
     * @param string          $method_template Templated API method to call
     * @param array  optional $params          parameters to send along
     *
     * @return mixed The API reply, encoded in the set return_format
     */

    protected function _callApiCurl($httpmethod, $method, $method_template, $params = array())
    {
        if (! function_exists('curl_init')) {
            throw new Exception('To make API requests, the PHP curl extension must be available.');
        }
        if (! function_exists('json_encode')) {
            throw new Exception('To make API requests, the PHP json extension must be available.');
        }
        $authorization   = null;
        $url             = $this->_getEndpoint($method);
        $request_headers = array();
        if ($httpmethod === 'GET') {
            if (json_encode($params) !== '{}'
                && json_encode($params) !== '[]'
            ) {
                $url .= '?' . http_build_query($params);
            }
            $authorization = $this->_sign($httpmethod, $url, $params);
            $ch = curl_init($url);
        } else {
            if (substr($method, 0, 7) === 'oauth1/') {
                $authorization = $this->_sign($httpmethod, $url, $params);
                $params        = http_build_query($params);
            } else {
                $authorization = $this->_sign($httpmethod, $url, array());
                // load files, if any
                $this->_encodeFiles($method, $params);
                $params = json_encode($params);
            }
            $ch = curl_init($url);
            if ($httpmethod === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                $request_headers[] = 'Content-Length: ' . strlen($params);
                $request_headers[] = 'Content-Type: application/json';
            } else {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $httpmethod);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            }
        }
        $request_headers = array();
        if (isset($authorization)) {
            $request_headers[] = 'Authorization: ' . $authorization;
            $request_headers[] = 'Expect:';
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, __DIR__ . '/cacert.pem');

        if (isset($this->_timeout)) {
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->_timeout);
        }

        if (isset($this->_connectionTimeout)) {
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->_connectionTimeout);
        }

        $result = curl_exec($ch);

        // certificate validation results
        $validation_result = curl_errno($ch);
        $this->_validateSslCertificate($validation_result);

        $httpstatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $reply      = $this->_parseApiReply($result);
        if ($this->_return_format === SHAPECODE_RETURNFORMAT_OBJECT) {
            $reply->httpstatus = $httpstatus;
        } elseif ($this->_return_format === SHAPECODE_RETURNFORMAT_ARRAY) {
            $reply['httpstatus'] = $httpstatus;
        }
        return $reply;
    }

    /**
     * Calls the API without cURL
     *
     * @param string          $httpmethod      HTTP method to use for making the request
     * @param string          $method          API method to call
     * @param string          $method_template Templated API method to call
     * @param array  optional $params          parameters to send along
     *
     * @return mixed The API reply, encoded in the set return_format
     */

    protected function _callApiNoCurl($httpmethod, $method, $method_template, $params = array())
    {
        if (! function_exists('json_encode')) {
            throw new Exception('To make API requests, the PHP json extension must be available.');
        }
        $authorization = null;
        $url           = $this->_getEndpoint($method);
        $hostname      = parse_url($url, PHP_URL_HOST);
        $request_headers = array();
        if ($httpmethod === 'GET') {
            if (json_encode($params) !== '{}'
                && json_encode($params) !== '[]'
            ) {
                $url .= '?' . http_build_query($params);
            }
            $authorization = $this->_sign($httpmethod, $url, $params);
            $ch = curl_init($url);
        } else {
            if (substr($method_template, 0, 7) === 'oauth1/') {
                $authorization = $this->_sign($httpmethod, $url, $params);
                $params        = http_build_query($params);
            } else {
                $authorization = $this->_sign($httpmethod, $url, array());
                // load files, if any
                $this->_encodeFiles($method_template, $params);
                $params = json_encode($params);
                if ($httpmethod === 'POST') {
                    $request_headers[] = 'Content-Length: ' . strlen($params);
                    $request_headers[] = 'Content-Type: application/json';
                }
            }
        }
        if (isset($authorization)) {
            $request_headers[] = 'Authorization: ' . $authorization;
            $request_headers[] = 'Expect:';
        }

        $context = stream_context_create(array(
            'http' => array(
                'method'           => $httpmethod,
                'protocol_version' => '1.1',
                'header'           => implode("\r\n", $request_headers),
                'timeout'          => $this->_timeout / 1000,
                'content'          => $httpmethod === 'POST' ? $params : null
            ),
            'ssl' => array(
                'verify_peer'  => true,
                'cafile'       => __DIR__ . '/cacert.pem',
                'verify_depth' => 5,
                'peer_name'    => $hostname
            )
        ));

        $reply   = @file_get_contents($url, false, $context);
        $headers = $http_response_header;
        $result  = '';
        foreach ($headers as $header) {
            $result .= $header . "\r\n";
        }
        $result .= "\r\n" . $reply;

        // find HTTP status
        $httpstatus = '500';
        $match      = array();
        if (preg_match('/HTTP\/\d\.\d (\d{3})/', $headers[0], $match)) {
            $httpstatus = $match[1];
        }

        $reply      = $this->_parseApiReply($result);
        switch ($this->_return_format) {
            case SHAPECODE_RETURNFORMAT_ARRAY:
                $reply['httpstatus'] = $httpstatus;
                break;
            case SHAPECODE_RETURNFORMAT_OBJECT:
                $reply->httpstatus = $httpstatus;
                break;
        }
        return $reply;
    }

    /**
     * Parses the API reply to encode it in the set return_format
     *
     * @param string $reply The actual reply, JSON-encoded or URL-encoded
     *
     * @return array|object The parsed reply
     */
    protected function _parseApiReply($reply)
    {
        // split headers and body
        $headers = array();
        $reply = explode("\r\n\r\n", $reply, 4);

        // check if using proxy
        $proxy_strings = array();
        $proxy_strings[strtolower('HTTP/1.0 200 Connection Established')] = true;
        $proxy_strings[strtolower('HTTP/1.1 200 Connection Established')] = true;
        if (array_key_exists(strtolower(substr($reply[0], 0, 35)), $proxy_strings)) {
            array_shift($reply);
        } elseif (count($reply) > 2) {
            $headers = array_shift($reply);
            $reply = array(
                $headers,
                implode("\r\n", $reply)
            );
        }

        $headers_array = explode("\r\n", $reply[0]);
        foreach ($headers_array as $header) {
            $header_array = explode(': ', $header, 2);
            $key = $header_array[0];
            $value = '';
            if (count($header_array) > 1) {
                $value = $header_array[1];
            }
            $headers[$key] = $value;
        }
        if (count($reply) > 1) {
            $reply = $reply[1];
        } else {
            $reply = '';
        }

        $need_array = $this->_return_format === SHAPECODE_RETURNFORMAT_ARRAY;
        if ($reply == '[]') {
            switch ($this->_return_format) {
                case SHAPECODE_RETURNFORMAT_ARRAY:
                    return array();
                case SHAPECODE_RETURNFORMAT_JSON:
                    return '{}';
                case SHAPECODE_RETURNFORMAT_OBJECT:
                    return new stdClass;
            }
        }
        if (! $parsed = json_decode($reply, $need_array)) {
            if ($reply) {
                if (stripos($reply, '<' . '?xml version="1.0" encoding="UTF-8"?' . '>') === 0) {
                    // we received XML...
                    // since this only happens for errors,
                    // don't perform a full decoding
                    preg_match('/<request>(.*)<\/request>/', $reply, $request);
                    preg_match('/<error>(.*)<\/error>/', $reply, $error);
                    $parsed['request'] = htmlspecialchars_decode($request[1]);
                    $parsed['error'] = htmlspecialchars_decode($error[1]);
                } else {
                    // assume query format
                    $reply = explode('&', $reply);
                    foreach ($reply as $element) {
                        if (stristr($element, '=')) {
                            list($key, $value) = explode('=', $element, 2);
                            $value = rawurldecode($value);
                            // force SSL
                            if ($key === 'authentication_url'
                                && substr($value, 0, 7) === 'http://'
                            ) {
                                $value = 'https://' . substr($value, 7);
                            }
                            $parsed[$key] = $value;
                            // extract oauth token (API doesn't return separate param)
                            $token_position = strpos($value, 'oauth_token=');
                            if ($token_position > -1) {
                                $parsed['oauth_token'] = substr($value, $token_position + 12);
                            }
                        } else {
                            $parsed['message'] = $element;
                        }
                    }
                }
            }
            $reply = json_encode($parsed);
        }
        switch ($this->_return_format) {
            case SHAPECODE_RETURNFORMAT_ARRAY:
                return $parsed;
            case SHAPECODE_RETURNFORMAT_JSON:
                return $reply;
            case SHAPECODE_RETURNFORMAT_OBJECT:
                return (object) $parsed;
        }
        return $parsed;
    }
}

?>
