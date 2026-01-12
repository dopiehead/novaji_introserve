<?php
$ch = curl_init("https://www.cbn.gov.ng/api/GetAllCirculars");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$out = curl_exec($ch);


if ($out === false) {
    echo curl_error($ch);
} else {
    echo "SUCCESS";
}

curl_close($ch);