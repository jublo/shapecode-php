<?php

/**
 * Uploads a 3D model
 *
 * @param string $file                 Path to the model to upload
 * @param string $name                 Model name
 * @param string $description          Model description
 * @param string $tags                 Comma-separated list of tags to apply
 * @param string $default_material     Name of the default material
 * @param array  $disallowed_materials Array of materials not allowed
 * @param float  $markup               Price markup to earn
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
    $default_material,
    $disallowed_materials,
    $markup,
    $consumer_key,
    $consumer_secret,
    $access_token,
    $access_token_secret
)
{
    // materials as of 2014-02-16
    $all_materials = (array) json_decode('
        {
            "White Strong & Flexible": 6,
            "White Strong & Flexible Polished": 62,
            "Black Strong & Flexible": 25,
            "Coral Red Strong & Flexible Polished": 76,
            "Hot Pink Strong & Flexible Polished": 77,
            "Royal Blue Strong & Flexible Polished": 78,
            "Violet Purple Strong & Flexible Polished": 75,
            "Elasto Plastic": 82,
            "Frosted Ultra Detail": 61,
            "Frosted Detail": 60,
            "White Detail": 5,
            "Black Detail": 7,
            "Transparent Detail": 4,
            "Full Color Sandstone": 26,
            "Sandstone": 27,
            "Gloss Black Ceramics": 64,
            "Pastel Yellow Ceramics": 74,
            "Eggshell Blue Ceramics": 72,
            "Glazed Ceramics": 63,
            "Avocado Green Ceramics": 73,
            "Satin Black Ceramics": 70,
            "Matte Black Steel": 89,
            "Polished Grey Steel": 90,
            "Polished Brass": 85,
            "Polished Bronze": 87,
            "Polished Nickel Steel": 88,
            "Raw Bronze": 86,
            "Gold Plated Brass": 83,
            "Raw Brass": 84,
            "Premium Silver": 81,
            "Polished Silver": 54,
            "Raw Silver": 53,
            "Stainless Steel": 23,
            "Matte Gold Steel": 31,
            "Polished Gold Steel": 39,
            "Matte Bronze Steel": 37,
            "Polished Bronze Steel": 38,
            "Polished Alumide": 66,
            "Alumide": 28
        }
    ');
    $all_material_names = array_keys($all_materials);

    require_once('../src/shapecode.php');
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

// get OAuth tokens from external file, not included in repo
require_once('private_shapeways_tokens.php');

print_r(upload3DFile(
    '../demo-data/cube-1cm3-centered_in_meter.stl', // path to the file to upload
    'Test Model', // name of the 3D model
    'This is a test model showing a cube.', // description of the 3D model
    'test, demo, coding, cube', // provided like « a,b,c »...
    'White Strong & Flexible', // defaultMaterialName example: White Strong & Flexible
    array('Disallowed 1', 'Disallowed 2'), // $excludedMaterials, // array with all material names not allowed...
    1.99, // $markup, same markup for each material

    // for Oauth protocol (correct me if this is wrong)
    SHAPEWAYS_CONSUMER_KEY, // $consumer_key,
    SHAPEWAYS_CONSUMER_SECRET, // $consumer_secret
    SHAPEWAYS_ACCESS_TOKEN, // $access_token,
    SHAPEWAYS_ACCESS_TOKEN_SECRET // $access_token_secret
));

