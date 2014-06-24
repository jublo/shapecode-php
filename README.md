shapecode-php
=============
*A Shapeways API library in PHP.*

Copyright (C) 2014 Jublo Solutions <support@jublo.net>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Lesser General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.

### Requirements

- PHP 5.2.0 or higher
- CURL extension
- JSON extension
- OpenSSL extension


Authentication
--------------

To authenticate your API requests on behalf of a certain Shapeways user
(following OAuth 1.0a), take a look at these steps:

```php
require_once ('shapecode.php');
Shapecode::setConsumerKey('YOURKEY', 'YOURSECRET'); // static, see 'Using multiple Shapecode instances'

$sc = Shapecode::getInstance();
```

You may either set the OAuth token and secret, if you already have them:
```php
$sc->setToken('YOURTOKEN', 'YOURTOKENSECRET');
```

Or you authenticate, like this:

```php
session_start();

if (! isset($_SESSION['oauth_token'])) {
    // get the request token
    $reply = $sc->oauth1_requestToken(array(
        'oauth_callback' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ));

    // store the token
    $_SESSION['oauth_token'] = $reply->oauth_token;
    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;
    $_SESSION['oauth_verify'] = true;

    // redirect to auth website
    header('Location: ' . $reply->authentication_url);
    die();

} elseif (isset($_GET['oauth_verifier']) && isset($_SESSION['oauth_verify'])) {
    // verify the token
    $sc->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
    unset($_SESSION['oauth_verify']);

    // get the access token
    $reply = $sc->oauth1_accessToken(array(
        'oauth_verifier' => $_GET['oauth_verifier']
    ));

    // store the token (which is different from the request token!)
    $_SESSION['oauth_token'] = $reply->oauth_token;
    $_SESSION['oauth_token_secret'] = $reply->oauth_token_secret;

    // send to same URL, without oauth GET parameters
    header('Location: ' . basename(__FILE__));
    die();
}

// assign access token on each page load
$sc->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
```

Usage examples
--------------

When you have an access token, calling the API is simple:

```php
$sc->setToken($_SESSION['oauth_token'], $_SESSION['oauth_token_secret']); // see above

$reply = (array) $sc->api();
print_r($reply);
```

Adding a model to your cart is as easy as this:

```php
$reply = $sc->orders_cart('modelId=480903');
```

For more complex parameters (see the [Shapeways API documentation](https://developers.shapeways.com/)),
giving all parameters in an array is supported, too:

```php
$params = array(
    'modelId' => '480903',
    'materialId' => 61,
    'quantity' => 3
);
$reply = $sc->orders_cart($params);
```

When **uploading files to Shapeways**, just give the file path:

```php
$params = array(
    'file' => 'in-some-folder/there-is/the-model.stl',
    'fileName' => 'the-model.stl',
    'hasRightsToModel' => 1,
    'acceptTermsAndConditions' => 1,
    'title' => 'My great model',
    'description' => 'Lorem ipsum dolor sit amet',
    'isPublic' => 1,
    'isForSale' => 1,
    'isDownloadable' => 0,
    'tags' => array(
        'ideas',
        'miniatures',
        'stuff'
    )
);
$reply = $sc->models($params); // required HTTP POST is auto-detected
```

Mapping API methods to Shapecode function calls
-----------------------------------------------

As you can see from the last example, there is a general way how the Shapeways
API methods map to Shapecode function calls. The general rules are:

1. Omit the version info in the function name.

    Example: ```/v1``` is not part of the Shapecode function call.

2. For each slash in a Shapeways API method, use an underscore in the Shapecode function.

    Example: ```orders/cart/v1``` maps to ```Shapecode::orders_cart()```.

3. For each underscore in a Shapeways API method, use camelCase in the Shapecode function.

    Example: ```oauth1/request_token/v1``` maps to ```Shapecode::oauth1_requestToken()```.

4. For each parameter template in method, use UPPERCASE in the Shapecode function.
    Also don’t forget to include the parameter in your parameter list.

    Example:
    - ```materials/{materialId}/v1``` maps to ```Shapecode::materials_MATERIALID('materialId=73')```.

HTTP methods (GET, POST, DELETE etc.)
-------------------------------------

Never care about which HTTP method (verb) to use when calling a Shapeways API.
Shapecode is intelligent enough to find out on its own.
For the automatic detection to work, be sure to use the correct required parameters,
as outlined in the [Shapeways API documentation](https://developers.shapeways.com/).

The only exception to the above is the `DELETE models/{modelId}` method.
To call it, use the `delete=1` parameter. It will trigger the DELETE method,
but is not sent to the API.

Response codes
--------------

The HTTP response code that the API gave is included in any return values.
You can find it within the return object’s ```httpstatus``` property.

To know whether your API call was successful, check the ```$reply->result``` string,
which either reads ```success``` or ```failure```.

Return formats
--------------
The default return format for API calls is a PHP object.
Upon your choice, you may also get PHP arrays directly:

```php
$sc->setReturnFormat(SHAPECODE_RETURNFORMAT_ARRAY);
```

The Shapeways API natively responds to API calls in JSON (JS Object Notation).
To get a JSON string, set the corresponding return format:

```php
$sc->setReturnFormat(SHAPECODE_RETURNFORMAT_JSON);
```

Using multiple Shapecode instances
----------------------------------

By default, Shapecode works with just one instance. This programming paradigma is
called a *singleton*.

Getting the main Shapecode object is done like this:

```php
$sc = Shapecode::getInstance();
```

If you need to run requests to the Shapeways API for multiple users at once,
Shapecode supports this as well. Instead of getting the instance like shown above,
create a new object:

```php
$sc1 = new Shapecode;
$sc2 = new Shapecode;
```

Please note that your OAuth consumer key and secret is shared within
multiple Shapecode instances, while the OAuth request and access tokens with their
secrets are *not* shared.

How Do I…?
----------

### …walk through paged results?

The Shapeways API utilizes a technique called ‘paging’ for
large result sets. Pages separates results into pages of no more than
36 results at a time, and provides a means to move backwards and
forwards through these pages.

Here is how you can walk through paged results with Shapecode.

1. Get the first result set of a paged method:
```php
$page = 1;
$result1 = $sc->models();
```

2. To navigate forth, increment the ```$page```:
```php
$page++;
```

3. If ```$nextCursor``` is not 0, use this cursor to request the next result page:
```php
    $result2 = $sc->models("page=$page");
```

It might make sense to use the pages in a loop.  Watch out, though,
not to send more than the allowed number of requests
per rate-limit timeframe, or else you will hit your rate-limit.

### …know what cacert.pem is for?

Connections to the Shapeways API are done over a secured SSL connection.
Shapecode-php checks if the Shapeways API server has a valid SSL certificate.
Valid certificates have a correct signature-chain.
The cacert.pem file contains a list of all public certificates for root
certificate authorities. You can find more information about this file
at http://curl.haxx.se/docs/caextract.html.

### …set the timeout for requests to the Shapeways API?

For connecting to Shapeways, Shapecode uses the cURL library.
You can specify both the connection timeout and the request timeout,
in milliseconds:

```php
$sc->setConnectionTimeout(2000);
$sc->setTimeout(5000);
```
