<?php

// get OAuth tokens from external file, not included in repo
require_once('private_shapeways_tokens.php');

/**
 * Uploads a 3D model
 *
 * @param string $file                 Path to the model to upload
 * @param string $name                 Model name
 * @param string $description          Model description
 * @param string $tags                 Comma-separated list of tags to apply
 * @param bool   $colorflag            TODO
 * @param string $default_material     Name of the default material
 * @param array  $disallowed_materials Array of materials not allowed
 * @param TODO   $markup               TODO
 * @param string $consumer_key         OAuth app key
 * @param string $consumer_secret      OAuth app secret
 * @param string $access_token         OAuth access token to the account to upload to
 * @param string $access_token_secret  OAuth access token secret
 *
 * @return array API data
 */
function upload3DFile(
    $file,
    $name,
    $description,
    $tags,
    $colorflag,
    $default_material,
    $disallowed_materials,
    $markup,
    $consumer_key,
    $consumer_secret,
    $access_token,
    $access_token_secret
)
{
    require_once('src/shapecode.php');
    Shapecode::setConsumerKey($consumer_key, $consumer_secret);
    $sc = Shapecode::getInstance();
    $sc->setToken($access_token, $access_token_secret);

    // comma-separated list of tags?
    if (is_string($tags)) {
        $tags = explode(',', $tags);
        // remove leading and trailing spaces
        for ($i = 0; $i < count($tags); $i++) {
            $tags[$i] = trim($tags[$i]);
        }
    }

    // TODO: obtain allowed materials list (not excluded)
    // TODO: obtain default material ID (not name)
    // TODO: What's colorFlag?
    // TODO: What's markup?

    return $sc->models(array(
        'file' => $file,
        'fileName' => basename($file),
        'hasRightsToModel' => 1,
        'acceptTermsAndConditions' => 1,
        'title' => $name,
        'description' => $description,
        'isPublic' => 0,
        'isForSale' => 0,
        'isDownloadable' => 0,
        'tags' => $tags,
        'materials' => array(),
        'defaultMaterialId' => 6
    ));
}

print_r(upload3DFile(
    'demo-data/cube-1cm3-centered_in_meter.stl', // path to the file to upload
    'Test Model', // name of the 3D model
    'This is a test model showing a cube.', // description of the 3D model
    'test, demo, coding, cube', // provided like « a,b,c »...
    true, // colorFlag boolean
    'White Strong & Flexible', // defaultMaterialName example: White Strong & Flexible
    array('Disallowed 1', 'Disallowed 2'), // $excludedMaterials, // array with all material names not allowed...
    'markup', // $markup,  // same markup for each material

    // for Oauth protocol (correct me if this is wrong)
    SHAPEWAYS_CONSUMER_KEY, // $consumer_key,
    SHAPEWAYS_CONSUMER_SECRET, // $consumer_secret
    SHAPEWAYS_ACCESS_TOKEN, // $access_token,
    SHAPEWAYS_ACCESS_TOKEN_SECRET // $access_token_secret
));

/*
shows stuff like:

stdClass Object ( [result] => success [modelId] => 1709722 [modelVersion] => 0 [title] => Test Model [fileName] => cube-1cm3-centered_in_meter.stl [contentLength] => 684 [fileMd5Checksum] => a62f5646bcb5a1437cb38ad07c45adf7 [description] => This is a test model showing a cube. [isPublic] => 1 [isForSale] => 1 [isDownloadable] => 0 [materials] => stdClass Object ( [6] => stdClass Object ( [materialId] => 6 [markup] => 0 [isActive] => 0 ) [62] => stdClass Object ( [materialId] => 62 [markup] => 0 [isActive] => 0 ) [25] => stdClass Object ( [materialId] => 25 [markup] => 0 [isActive] => 0 ) [76] => stdClass Object ( [materialId] => 76 [markup] => 0 [isActive] => 0 ) [77] => stdClass Object ( [materialId] => 77 [markup] => 0 [isActive] => 0 ) [78] => stdClass Object ( [materialId] => 78 [markup] => 0 [isActive] => 0 ) [75] => stdClass Object ( [materialId] => 75 [markup] => 0 [isActive] => 0 ) [61] => stdClass Object ( [materialId] => 61 [markup] => 0 [isActive] => 0 ) [60] => stdClass Object ( [materialId] => 60 [markup] => 0 [isActive] => 0 ) [5] => stdClass Object ( [materialId] => 5 [markup] => 0 [isActive] => 0 ) [7] => stdClass Object ( [materialId] => 7 [markup] => 0 [isActive] => 0 ) [4] => stdClass Object ( [materialId] => 4 [markup] => 0 [isActive] => 0 ) [26] => stdClass Object ( [materialId] => 26 [markup] => 0 [isActive] => 0 ) [27] => stdClass Object ( [materialId] => 27 [markup] => 0 [isActive] => 0 ) [74] => stdClass Object ( [materialId] => 74 [markup] => 0 [isActive] => 0 ) [72] => stdClass Object ( [materialId] => 72 [markup] => 0 [isActive] => 0 ) [64] => stdClass Object ( [materialId] => 64 [markup] => 0 [isActive] => 0 ) [63] => stdClass Object ( [materialId] => 63 [markup] => 0 [isActive] => 0 ) [70] => stdClass Object ( [materialId] => 70 [markup] => 0 [isActive] => 0 ) [73] => stdClass Object ( [materialId] => 73 [markup] => 0 [isActive] => 0 ) [86] => stdClass Object ( [materialId] => 86 [markup] => 0 [isActive] => 0 ) [84] => stdClass Object ( [materialId] => 84 [markup] => 0 [isActive] => 0 ) [89] => stdClass Object ( [materialId] => 89 [markup] => 0 [isActive] => 0 ) [83] => stdClass Object ( [materialId] => 83 [markup] => 0 [isActive] => 0 ) [90] => stdClass Object ( [materialId] => 90 [markup] => 0 [isActive] => 0 ) [85] => stdClass Object ( [materialId] => 85 [markup] => 0 [isActive] => 0 ) [87] => stdClass Object ( [materialId] => 87 [markup] => 0 [isActive] => 0 ) [88] => stdClass Object ( [materialId] => 88 [markup] => 0 [isActive] => 0 ) [81] => stdClass Object ( [materialId] => 81 [markup] => 0 [isActive] => 0 ) [54] => stdClass Object ( [materialId] => 54 [markup] => 0 [isActive] => 0 ) [53] => stdClass Object ( [materialId] => 53 [markup] => 0 [isActive] => 0 ) [23] => stdClass Object ( [materialId] => 23 [markup] => 0 [isActive] => 0 ) [31] => stdClass Object ( [materialId] => 31 [markup] => 0 [isActive] => 0 ) [39] => stdClass Object ( [materialId] => 39 [markup] => 0 [isActive] => 0 ) [37] => stdClass Object ( [materialId] => 37 [markup] => 0 [isActive] => 0 ) [38] => stdClass Object ( [materialId] => 38 [markup] => 0 [isActive] => 0 ) [28] => stdClass Object ( [materialId] => 28 [markup] => 0 [isActive] => 0 ) [66] => stdClass Object ( [materialId] => 66 [markup] => 0 [isActive] => 0 ) [82] => stdClass Object ( [materialId] => 82 [markup] => 0 [isActive] => 0 ) ) [secretKey] => ******************************** [defaultMaterialId] => 6 [categories] => stdClass Object ( [99] => stdClass Object ( [categoryId] => 99 [title] => Download [level] => 1 [parentId] => ) ) [printable] => unknown [nextActionSuggestions] => stdClass Object ( [getModel] => stdClass Object ( [method] => GET [restUrl] => https://api.shapeways.com/models/1709722/v1 [link] => /models/1709722/v1 ) [downloadModel] => stdClass Object ( [method] => GET [restUrl] => https://api.shapeways.com/models/1709722/files/0/v1?file=1 [link] => /models/1709722/files/0/v1?file=1 ) [updateModelDetails] => stdClass Object ( [method] => PUT [restUrl] => https://api.shapeways.com/models/1709722/info/v1 [link] => /models/1709722/info/v1 ) [updateModelFile] => stdClass Object ( [method] => POST [restUrl] => https://api.shapeways.com/models/1709722/files/v1 [link] => /models/1709722/files/v1 ) [addModelPhoto] => stdClass Object ( [method] => POST [restUrl] => https://api.shapeways.com/models/v1 [link] => /models/v1 ) ) [httpstatus] => 200 )

*/
