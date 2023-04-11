<?php
require 'vendor/autoload.php';

// Set the API endpoint URL
$site_url = "http://localhost/techhome/";
$url = $site_url.'wp-json/wp/v2/posts';

// Set your username and password
$username = 'techhome';
$password = 'pseo@gmail.com';


$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("book.xlsx");
$worksheet = $spreadsheet->getActiveSheet();

foreach ($worksheet->getRowIterator(2) as $row) 
{
    $rowData = [];
    foreach ($row->getCellIterator() as $cell) 
    {
        $rowData[] = $cell->getValue();
    }
    $title = $rowData[0];
    $content = $rowData[1];
    $feature = $rowData[2];
    $mobile = $rowData[3];
    $location = $rowData[4];
    $category = $rowData[5];

    $caturl = $site_url.'wp-json/wp/v2/categories';
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $caturl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
    ));

    $response = curl_exec($curl);

    $categories = json_decode($response);
    $cat_id = "";

    foreach($categories as $cate)
    {
        if($cate->name === $category)
        {
            $cat_id = $cate->id;
            break;
        }
    }
    
    $path = $feature ;
    $request_url = $site_url.'wp-json/wp/v2/media';

    $image = file_get_contents( $path );
    
    $api = curl_init();
    curl_setopt( $api, CURLOPT_URL, $request_url );
    curl_setopt( $api, CURLOPT_POST, 1 );
    curl_setopt( $api, CURLOPT_POSTFIELDS, $image );
    curl_setopt( $api, CURLOPT_HTTPHEADER, array( 'Content-Disposition: attachment; filename="' . basename($path) . '"' ) );
    curl_setopt( $api, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt( $api, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
    curl_setopt( $api, CURLOPT_USERPWD, $username . ':' . $password );
    $result = curl_exec( $api );
    curl_close( $api );

    $media_id = json_decode( $result )->id;
    // Set the post data
    $post_data = array(
        'title' => $title,
        'content' => $content,
        'status' => 'publish',
        'categories' => [$cat_id],
        'acf' => array(
            'mobile' => $mobile,
            'location' => $location,
        ),
        'featured_media' => $media_id
    );

    // Set the post fields
    $post_fields = json_encode($post_data);

    // Set the cURL options
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        CURLOPT_USERPWD => $username . ':' . $password,
        CURLOPT_HTTPAUTH => CURLAUTH_BASIC
    ));

    // Execute the cURL request
    $response = curl_exec($curl);

    // Get the HTTP status code
    $http_status_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    // Close the cURL handle
    curl_close($curl);

    // Output the API response
    if ($http_status_code == 201) {
        // Post created successfully
        $post_id = json_decode($response)->id;
        echo 'Post created with ID: ' . $post_id;
    } else {
        // Error creating post
        echo 'Error creating post: ' . $response;
    }
}
?>