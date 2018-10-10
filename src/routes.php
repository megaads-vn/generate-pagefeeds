<?php
if (config('generate-sitemap.multiplesitemap')) {
    $avaiablelocales = config('app.locales');
    $locale = Request::segment(1);
    if (!array_key_exists($locale, $avaiablelocales)) {
        $locale = '';
    }
} else {
    $locale = '';
}

Route::group(['prefix' => $locale, 'namespace' => '\Megaads\Generatepagefeeds\Controllers'], function() {
    Route::get('/pagefeeds-generator-multiple', 'PageFeedsControllers@pageFeedsAll');
});

Route::group(['namespace' => '\Megaads\Generatepagefeeds\Controllers'], function() {
    Route::get('/pagefeeds-generator', 'PageFeedsControllers@generate');
});