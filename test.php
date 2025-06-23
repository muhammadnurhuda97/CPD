<?php

$apikey = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiIzOWE5NzY0NC0zNDRlLTQyNWEtOTk4YS0wYjQyY2E1YzYyY2IiLCJhY2NvdW50SWQiOiIxOTI5ZDM3Ni1kZjZiLTQ0NTMtOGJmNS02NDY5ZjQ5YjIyNzAiLCJjcmVhdGVkQXQiOiIxNzQzMTg3MDY1ODY2Iiwicm9sZSI6ImRldmVsb3BlciIsInN1YiI6InBlbXVkYWRpZ2l0YWwxQGdtYWlsLmNvbSIsIm5hbWUiOiJDaXB0YSBQZW11ZGEgRGlnaXRhbCIsImxpbmsiOiJwZW11ZGEtZGlnaXRhbCIsImlzU2VsZkRvbWFpbiI6bnVsbCwiaWF0IjoxNzQzMTg3MDY1fQ.Sq_78k6qeKydztX7XSbiaL292PhQ4YkubKTBX_KZIqTJ3C33amif5RhRP_yC3sZl4UjKNEYZ8_e1OeZSz0LqdVUfUbp48kqzTDz7wOoh9D94O0ggNI6toO6icU5eeFF-bq1pYra9d_YPKlOmwzqzSxNcjT55LdGjEWy6kdCHGbVzQBzCwg8uTnaoIbPBihkwFdz_vuE2FI6iDVFPf_1tF6xz-G9tUmm9L5GNQ1H2UpxDoe4U3kcPWJYSgT2msZjnQMctWD_5ZfFZ7dZtdTixGnMTO3bSr4KMCaJqSlsq-lw5kHb9ZCTWcq0Sa3_4l78vPKKUUskIlt32jYbpMrKDPA';
$curl = curl_init();
 // Store your API key in a variable

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.mayar.id/hl/v1/payment/create',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode([  // Use json_encode to send the body as valid JSON
    "name" => "andre",
    "email" => "alikusnadie@gmail.com",
    "amount" => 170000,
    "mobile" => "085797522261",
    "redirectUrl" => "https://kelaskami.com/nexst23",
    "description" => "kemana ini menjadi a",
    "expiredAt" => "2024-02-29T09:41:09.401Z"
  ]),
  CURLOPT_HTTPHEADER => array(
    'Authorization: Bearer ' . $apikey,  // Correct Authorization header
    'Content-Type: application/json'  // Set the content type as JSON
  ),
));


// // Set up the cURL request
// curl_setopt_array($curl, array(
//   CURLOPT_URL => 'https://api.mayar.id/hl/v1/product?page=1&pageSize=10',  // Endpoint URL
//   CURLOPT_RETURNTRANSFER => true,  // To return the response as a string
//   CURLOPT_ENCODING => '',  // Default encoding
//   CURLOPT_MAXREDIRS => 10,  // Maximum redirects
//   CURLOPT_TIMEOUT => 0,  // No timeout
//   CURLOPT_FOLLOWLOCATION => true,  // Follow redirects
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,  // HTTP version
//   CURLOPT_CUSTOMREQUEST => 'GET',  // HTTP method GET
//   CURLOPT_HTTPHEADER => array(
//     // 'Authorization: Bearer eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VySWQiOiJjMmZmZDhkNC1hZGU5LTRlY2ItODcxYi03MjBhZDZkMjMyNWYiLCJhY2NvdW50SWQiOiI3YTAxNjJmZS0zMTYzLTRjNzUtYmE4YS1lNzg0N2U5OWU0NzQiLCJjcmVhdGVkQXQiOiIxNzQzMTg0NzQ1Mzc4Iiwicm9sZSI6ImRldmVsb3BlciIsInN1YiI6ImVrb2FrdW5tYXlhckBnbWFpbC5jb20iLCJuYW1lIjoiUGVtYnVhdGFuIERlc2FpbiBXZWJzaXRlIiwibGluayI6ImRlc3dlYnMiLCJpc1NlbGZEb21haW4iOm51bGwsImlhdCI6MTc0MzE4NDc0NX0.XBSpoLjShWh-durbmiZCbxN12BEwYgD8FpWmZYHxN4QVPkoksOOFL3FTKWEGr2tiy2yptu4AmGTe5Mf0-SFwGRjMPHUDanTqyceXyK7DxlNnTuDQgd-HzNzaJhY7K12c1HvIdjBg5t4xeEQl4oT8Q1QOsij_K7xp8iUlnUgSDiKmANfo33Gi0NJeCaXKXGHjN1xUzQo3OUdsu2ltttfSrHVm6xRPzOJ588vtSsfBeyz_Ci6WPviBEfZ3QVGeyMNK0tVQPHNGrhClH6V8QbFA-g7evW9wzc667EsHrujqmvKbBzn-R36mObnqQdSMEywzAyYDg2iR3N4JSGKsV1ytSQ'  // Your API key
//     'Authorization: Bearer ' . $apikey  // Your API key CPD
//   ),
// ));

// curl_setopt_array($curl, array(
//   CURLOPT_URL => 'https://api.mayar.id/hl/v1/payment',
//   CURLOPT_RETURNTRANSFER => true,
//   CURLOPT_ENCODING => '',
//   CURLOPT_MAXREDIRS => 10,
//   CURLOPT_TIMEOUT => 0,
//   CURLOPT_FOLLOWLOCATION => true,
//   CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//   CURLOPT_CUSTOMREQUEST => 'GET',
//   CURLOPT_HTTPHEADER => array(
//     'Authorization: Bearer ' . $apikey  // Use the variable for the API key
//   ),
// ));

// Execute the cURL request and capture the response
$response = curl_exec($curl);

// Close the cURL session
curl_close($curl);

// Output the response
echo $response;

?>

