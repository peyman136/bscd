<?php

use Illuminate\Support\Facades\Route;
/*
function getDynamicKey() {
    $hwid = php_uname(); // اطلاعات سخت‌افزاری سیستم
    $appSecret = config('app.key'); // کلید ثابت اپلیکیشن
    $serverIp = gethostbyname(gethostname()); // آدرس IP سرور
    $macAddress = exec('getmac'); // مک‌آدرس سیستم
    $entropy = '';//getPersistentEntropy(); // نویز تصادفی ثابت

    return [$hwid , $appSecret , $serverIp , $macAddress , $entropy];
    //return hash('sha256', $hwid . $appSecret . $serverIp . $macAddress . $entropy);
}

function generateStableKey(array $inputs, string $salt = 'default_salt') {
    // ترکیب همه ورودی‌ها با جداکننده خاص
    $data = implode('|', $inputs);

    // استفاده از HMAC برای افزایش امنیت و جلوگیری از حدس زدن کلید
    $key = hash_hmac('sha256', $data, $salt);

    return $key;
}

// 🔹 نمونه استفاده:
$inputTexts = ['MyApp', 'User123', 'DeviceXYZ'];
$generatedKey = generateStableKey($inputTexts, 'my_secret_salt');

echo "Generated Key: " . $generatedKey;
dd(getDynamicKey());*/

Route::get('/', function () {
    return view('welcome');
});
