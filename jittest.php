<?php
    // Find out where we are:
    define('DOCROOT', __DIR__);

    // Propagate this change to all executables:
    chdir(DOCROOT);

    // Include autoloader:
    require_once DOCROOT . '/vendor/autoload.php';

    define('_EOL', "\n");

    $startUrl = 'http://localhost/sym/image/';
    $endUrl =  'https://2013.deuxhuithuit.com/workspace/assets/img/logo288-accueil-black.png';

    $cpt = 0;
    $cpt_ligne = 0;
    function createUrl()
    {
        $handle = fopen("workspace/uploads/jit_images.txt", "r");
        while ($line = fgetcsv($handle)) {
            if ($line[0]{0} === '#') {
                continue;
            }
            $array[] = array(
                'w' => @$line[1],
                'h' => @$line[2],
                'bg' => @$line[3],
                'url' => $line[0],
            );
        }
        return $array;
    }

    $testUrls = createUrl();

    foreach ($testUrls as $testUrl) {
       $cpt_ligne++;
        try {
            if (!testOneUrl($testUrl, $cpt_ligne)) {
                throw new Exception('Test did not return true');
            }
            echo 'Test succeeded!' . _EOL;
            $cpt++;
        } catch (Exception $ex) {
            echo 'Erreur! ' . $ex->getMessage() . _EOL;
        }
    }

    echo _EOL . 'Executed ' . count($testUrls) . ' tests, test succeeded : ' . $cpt . '.' . _EOL . _EOL;

    function testOneUrl(array $testUrl, $cpt)
    {
        // 1. Request the image from server
        $image = fetchImage($testUrl['url']);

        // 2. Test to see if the request worked
        if ($image['info']['http_code'] !== 200 || $image['body'] === false) {
            throw new Exception('Could not load image');
        }

        $imgObj = imagecreatefromstring($image['body']);
        $width = imagesx($imgObj);
        $height = imagesy($imgObj);

        // RGBA for the server image
        $imgBg = imagecolorat($imgObj, 0, 0);
        $color = imagecolorsforindex($imgObj, $imgBg);

        $red = $color['red'];
        $green = $color['green'];
        $blue = $color['blue'];
        $opacity = $color['alpha'];
        $rgba = $red . $green . $blue . $opacity;

        echo  "Ligne : " . $cpt . " -> width : ", $testUrl['w'], ' ', $width, " height : ", $testUrl['h'] ,  ' ', $height, _EOL;

        // 3. Validate the dimension
        if ($testUrl['w'] <= 0 || $testUrl['h'] <= 0  || $testUrl['w'] != $width || $testUrl['h'] != $height ) {
             throw new Exception('Dimensons are not identical'); 
        }

        // 4. optional background color test
        if (!empty($testUrl['bg'])) {
            if ($color){
                list($r, $g, $b, $a) = sscanf($testUrl['bg'], "#%02x%02x%02x%02x");
                //echo "{$testUrl['bg']} -> $r $g $b $a", _EOL;
                $rgbaUrl = $r . $g . $b . $a;
                if ($rgba != $rgbaUrl) {
                    echo $rgbaUrl . " " . $rgba;
                    print_r($color);
                    throw new Exception('Background image not the same'); 
                }
            }
        }
        return true;
    }

    function fetchImage($uri)
    {
        // create the Gateway object
        $gateway = new Gateway();
        // set our url
        $gateway->init($uri);
        // set some options
        $gateway->setopt(CURLOPT_HEADER, true);
        $gateway->setopt(CURLOPT_RETURNTRANSFER, true);
        $gateway->setopt(CURLOPT_FOLLOWLOCATION, true);
        $gateway->setopt(CURLOPT_MAXREDIRS, 2);
        // get the raw body response, ignore errors
        $response = @$gateway->exec();
        $info = $gateway->getInfoLast();

        // clean up
        $gateway->flush();

        // get header size
        $headerSize = $info['header_size'];
        $header = substr($response, 0, $headerSize - 2);
        $body = substr($response, $headerSize);

        return array(
            'info' => $info,
            'headers' => $header,
            'body' => $body,
        );
    }
