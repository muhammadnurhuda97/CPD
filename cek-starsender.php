<?php

$curl = curl_init();

// Hitung waktu sekarang + 30 menit (dalam milidetik)
$scheduleTimestamp = (time() + (30 * 60)) * 1000; // 30 menit ke depan

$pesan = [
  "messageType" => "text",
  "to" => "082245342997", // Ganti dengan nomor tujuan (pakai format internasional jika perlu, misal 628123456789)
  "body" => "Pesan ini akan dikirim 30 menit dari sekarang.",
  "delay" => 10, // Delay dalam detik (opsional, antar pesan kalau batch)
  "schedule" => $scheduleTimestamp // Timestamp dalam milidetik
];

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://api.starsender.online/api/send',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => json_encode($pesan),
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'Authorization: d019dbe1-04a7-4605-bcfe-7cbec821ae66' // Ganti dengan API key Anda
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
