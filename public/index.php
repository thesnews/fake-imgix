<?php

use Intervention\Image\ImageManager;

/**
 * This is intended to run as a separate service, yo. Do not attempt
 * to use this as a production replacement. You're going to have a bad
 * time.
 *
 * This does require Intervention/Image to function, all you need to do
 * is include that via composer and set the source root URL.
 *
 */

require_once '../vendor/autoload.php';

$manager = new ImageManager([
    'driver' => 'gd'
]);

$source = isset($_ENV['IMGIX_SOURCE_ROOT']) ? $_ENV['IMGIX_SOURCE_ROOT'] : 'http://localhost:8000/';

$validActions = [
    'blur' => function ($image, $params) {
        // imgix takes a value between 1 - 2000
        // intervention between 0 and 100
        $val = round(((int) $params['blur'] / 2000) * 100);

        return $image->blur($val);
    },
    'pixelate' => function ($image, $params) {
        $val = (int) $params['pixelate'];
        return $image->pixelate($val);
    }
];

$uri = trim(str_replace('fakeimgix', '', trim($_SERVER['REQUEST_URI'], '/')), '/');

$key = md5($uri);

if (strpos($uri, '?') !== false) {
    $query =  substr($uri, strrpos($uri, '?') + 1);
    $uri = substr($uri, 0, strrpos($uri, '?'));
} else {
    $query = '';
    $uri = $uri;
}

$params = [];
parse_str($query, $params);

$file = substr($uri, strrpos($uri, '/') + 1);
$extension = substr($file, strrpos($file, '.') + 1);

$temp_dir = sys_get_temp_dir();
$temp_file = implode('/', [$temp_dir, uniqid() . '.' . $extension]);

$store_path = $temp_dir . '/' . $key . '.' . $extension;

if (!file_exists($store_path)) {

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $source . '/' . $uri,
        CURLOPT_HTTPHEADER => ['Host: localhost'],
        CURLOPT_USERAGENT => 'SNworks FakeImgix Like Mozilla',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $cookie,
        CURLOPT_ENCODING => '',
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_AUTOREFERER => true,
        CURLOPT_SSL_VERIFYPEER => false
    ));

    $response = curl_exec($curl);

    curl_close($curl);
    if (file_put_contents($temp_file, $response) === false) {
        throw new \Exception('Unable to write temporary file', 500);
    }

    $handle = $manager->make($response);

    if (isset($params['w']) || isset($params['h'])) {
        $width = isset($params['w']) ? $params['w'] : 0;
        $height = isset($params['h']) ? $params['h'] : 0;

        if (!$height && $width) {
            $height = $width;
        } elseif (!$width && $height) {
            $width = $height;
        }

        $preserveAr = true;
        if (isset($params['ar'])) {
            $ar = explode(':', $params['ar']);
            $height = ceil($width / ($ar[0] / $ar[1]));
            $preserveAr = false;
        }

        if ($preserveAr) {
            $handle->resize($width, $height, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        } else {
            $handle->fit($width, $height, function ($constraint) {
                $constraint->upsize();
            }, 'top-left');
        }
    }

    foreach ($params as $k => $v) {
        if (!array_key_exists($k, $validActions)) {
            continue;
        }

        $handle = call_user_func($validActions[$k], $handle, $params);
    }

    $handle->save($store_path);
    @unlink($temp_file);
} else {
    $handle = $manager->make($store_path);
}

header('Content-Type: ' . $handle->mime());
echo file_get_contents($store_path);
