<?php
require 'vendor/autoload.php';
use GuzzleHttp\Client;

$wordpress_url = 'https://test2.mpcc.in/';

$client = new Client([
    'base_uri' => $wordpress_url,
    'auth' => [ 'princepal', 'princepal@@##94' ]
]);

$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load("D:\downloads\python\dog.xlsx");
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
   
    $response = $client->post('wp-json/wp/v2/media', [
        'multipart' => [
        [
        'name' => 'file',
        'contents' => fopen($feature, 'r')
        ]
        ]
    ]);
    
    $featured_image_id = json_decode($response->getBody())->id;

    $post_data = [
        'title' => $title,
        'content' => $content,
        'status' => 'publish',
        'comment_status' => 'open',
        'ping_status' => 'open',
        'featured_media' => $featured_image_id
    ];

    $response = $client->post('wp-json/wp/v2/posts', [
    'json' => $post_data
    ]);

    $post_id = json_decode($response->getBody())->id;

    if ($response->getStatusCode() == 201) 
    {
        echo 'Post created successfully! with'.$post_id;
        echo '</br>';
    } else
    {
        echo 'There was an error creating the post.';
    }
   
}