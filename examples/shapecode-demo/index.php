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
    $filename,
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
    // materials as of 2014-06-24
    $all_materials = (array) json_decode('
        {
            "White Strong & Flexible": 6,
            "White Strong & Flexible Polished": 62,
            "Orange Strong & Flexible Polished": 95,
            "Green Strong & Flexible Polished": 94,
            "Yellow Strong & Flexible Polished": 93,
            "Black Strong & Flexible": 25,
            "Red Strong & Flexible Polished": 76,
            "Pink Strong & Flexible Polished": 77,
            "Blue Strong & Flexible Polished": 78,
            "Purple Strong & Flexible Polished": 75,
            "Elasto Plastic": 82,
            "Frosted Ultra Detail": 61,
            "Frosted Detail": 60,
            "White Detail": 5,
            "Black Detail": 7,
            "Transparent Detail": 4,
            "Full Color Sandstone": 26,
            "Sandstone": 27,
            "Glazed Ceramics": 63,
            "Gloss Black Ceramics": 64,
            "Satin Black Ceramics": 70,
            "Pastel Yellow Ceramics": 74,
            "Eggshell Blue Ceramics": 72,
            "Avocado Green Ceramics": 73,
            "Raw Brass": 84,
            "Raw Bronze": 86,
            "Polished Brass": 85,
            "14K Gold": 91,
            "Gold Plated Brass": 83,
            "Polished Grey Steel": 90,
            "Matte Black Steel": 89,
            "Polished Bronze": 87,
            "Polished Nickel Steel": 88,
            "Premium Silver": 81,
            "Polished Silver": 54,
            "Raw Silver": 53,
            "Stainless Steel": 23,
            "Matte Gold Steel": 31,
            "Polished Gold Steel": 39,
            "Matte Bronze Steel": 37,
            "Polished Bronze Steel": 38,
            "Metallic Plastic": 28,
            "Polished Metallic Plastic": 66,
            "Castable Wax": 92
        }
    ');
    $all_material_names = array_keys($all_materials);

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

    // validate default material
    if (! in_array($default_material, $all_material_names)) {
        throw new Exception('Unknown default material: "' . htmlspecialchars($default_material) . '"');
        return false;
    }

    // validate markup
    if (! is_numeric($markup)) {
        throw new Exception('Non-numeric markup: "' . htmlspecialchars($markup) . '"');
        return false;
    }

    // build materials list
    $materials = array();
    foreach ($all_materials as $material_name => $material_id) {

        // skip disallowed materials
        if (is_array($disallowed_materials)
            && in_array($material_name, $disallowed_materials)
        ) {
            continue;
        }

        $materials[$material_id] = array(
            'id' => $material_id,
            'markup' => $markup,
            'isActive' => 1
        );
    }

    return $sc->models(array(
        'file' => $file,
        'fileName' => $filename,
        'hasRightsToModel' => 1,
        'acceptTermsAndConditions' => 1,
        'title' => $name,
        'description' => $description,
        'isPublic' => 0,
        'isForSale' => 0,
        'isDownloadable' => 0,
        'tags' => $tags,
        'materials' => $materials,
        'defaultMaterialId' => $all_materials[$default_material] // use ID
    ));
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (isset($_REQUEST['reset'])) {
    session_destroy();
}

if (! isset($_SESSION['oauth_token']) && ! isset($_SESSION['oauth_verify']) && ! isset($_POST['authenticate'])) {
    ?><!DOCTYPE HTML>
<html>
    <head>
        <title>Shapeways upload demo</title>
    </head>
    <body>
        <p>You are not logged in.</p>
        <form method="post">
            <button type="submit" name="authenticate" value="1">Login</button>
        </form>
    </body>
</html>
    <?php
    die();
}

// get OAuth tokens from external file, not included in repo
require_once('private_shapeways_tokens.php');

if (isset($_FILES['upload']) && $_FILES['upload']['error'] === 0) {

    $upload_data = upload3DFile(
        $_FILES['upload']['tmp_name'], // path to the file to upload
        $_FILES['upload']['name'],
        $_POST['title'], // name of the 3D model
        isset($_POST['description']) ? $_POST['description'] : '', // description of the 3D model
        isset($_POST['tags']) ? $_POST['tags'] : '', // provided like « a,b,c »...
        'Black Strong & Flexible', // defaultMaterialName
        array('Avocado Green Ceramics', 'Raw Bronze'), // $excludedMaterials, array with all material names not allowed
        isset($_POST['markup']) ? $_POST['markup'] : '', // $markup, same markup for each material

        // for Oauth protocol (correct me if this is wrong)
        SHAPEWAYS_CONSUMER_KEY, // $consumer_key,
        SHAPEWAYS_CONSUMER_SECRET, // $consumer_secret
        SHAPEWAYS_ACCESS_TOKEN, // $access_token,
        SHAPEWAYS_ACCESS_TOKEN_SECRET // $access_token_secret
    );

    if ($upload_data->result === 'success') {
        ?><!DOCTYPE HTML>
<html>
    <head>
        <title>Shapeways upload demo</title>
    </head>
    <body>
        <p>You are logged in.</p>
        <form method="post">
            <button type="submit" name="reset" value="1">Logout</button>
        </form>
        <hr />
        <p>Model uploaded successfully.</p>
        <p><a href="http://www.shapeways.com/model/<?php echo $upload_data->modelId; ?>/?key=<?php echo $upload_data->secretKey; ?>" target="_blank">Take a look</a></p>
    </body>
</html>
        <?php
    } elseif ($upload_data->result === 'failure') {
        ?><!DOCTYPE HTML>
<html>
    <head>
        <title>Shapeways upload demo</title>
    </head>
    <body>
        <p>You are logged in.</p>
        <form method="post">
            <button type="submit" name="reset" value="1">Logout</button>
        </form>
        <hr />
        <p>Error in model upload.</p>
        <p><pre><?php print_r($upload_data); ?></pre></p>
    </body>
</html>
        <?php
    }
    die();
}

?><!DOCTYPE HTML>
<html>
    <head>
        <title>Shapeways upload demo</title>
    </head>
    <body>
        <p>You are logged in.</p>
        <form method="post">
            <button type="submit" name="reset" value="1">Logout</button>
        </form>
        <hr />
        <form method="post" enctype="multipart/form-data">
            <label for="upload">Select model file to upload:</label>
            <input type="file" name="upload" id="upload" required /><br />
            <label for="title">Model title:</label>
            <input type="text" name="title" id="title" required /><br />
            <label for="description">Model description (optional)</label>
            <input type="text" name="description" id="description" /><br />
            <label for="tags">Model tags (optional)</label>
            <input type="text" name="tags" id="tags" /><br />
            <label for="markup">Material markup</label>
            <input type="text" name="markup" id="markup" required value="0.00" /><br />
            <button type="submit">Upload</button>
        </form>
    </body>
</html>
