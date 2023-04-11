<?php

require 'vendor/autoload.php';


$site_url = "http://localhost/techhome/";

$cat_url = $site_url."wp-json/wp/v2/categories";
$media_url = $site_url."wp-json/wp/v2/media";
$post_url = $site_url."wp-json/wp/v2/posts";

$wordpress_username = "techhome";
$wordpress_password = "pseo@gmail.com";

$auth = base64_encode($wordpress_username.":".$wordpress_password);

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


    $request_url = "$cat_url?search=$category";

    $options = array(
        'http' => array(
            'header' => array(
                "Authorization: Basic $auth",
                "Content-Type: application/json",
            ),
            'method' => 'GET',
        ),
    );
    
    // Make the REST API request and handle the response
    $context = stream_context_create($options);
    $result = file_get_contents($request_url, false, $context);

    $category_data = json_decode($result, true);
    if (!empty($category_data))
    {
        $category_id = $category_data[0]['id'];
        
    }
    
    $image_url = $feature;
    $image_filename = basename($image_url);

    // Get the image data using file_get_contents()
    $image_data = file_get_contents($image_url);
    $headers = array(
        'Content-Disposition: attachment; filename="' . $image_filename . '"',
        'Content-Type: image/jpeg', // Change this based on your image format
        'Authorization: Basic ' . $auth
    );
    $options = array(
        'httpversion' => '1.1',
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => $image_data,
    );

    $context = stream_context_create(array('http' => $options));
    $response = file_get_contents($media_url, false, $context);
    $image_data = json_decode($response, true);

    // Get the ID of the uploaded image
    $image_id = $image_data['id'];

    $post_data = array(
        'title' => $title,
        'content' => $content,
        'status' => 'publish',
        'featured_media' => $image_id ,// Set the image ID as featured image ID
        'acf' =>[
            'mobile' => $mobile,
            'location' => $location
        ],
        'categories' => $category_id,
    );
    $headers = array(
        'Content-Type: application/json',
        'Authorization: Basic ' . $auth
    );
    $options = array(
        'httpversion' => '1.1',
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => json_encode($post_data),
    );
    
    // Make the REST API request to create the post and handle the response 
    $context = stream_context_create(array('http' => $options));
    $result = file_get_contents($post_url, false, $context);
    if ($result === false) {
        echo "Error creating post: " . print_r(error_get_last(), true);     
    } else {
        $post_id = json_decode($result, true)['id'];
        echo "Post created with ID $post_id \n";
    }
}
