# Google page feeds generate automatically for Laravel
This package help generate google page feed automatic.

#####Install
using require command to install this package:
```
   composer require megaads/generate-pagefeeds
```

After complete install package, Add to app config provider
```
Megaads\Generatepagefeeds\GeneratepagefeedsServiceProvider::class
```
and run command publish to publish package config file.

```
php artisan vendor:publish --provider="Megaads\Generatepagefeeds\GeneratepagefeedsServiceProvider"
```
The package file name ``pagefeeds.php`` and see like that: 

```
return [
    'spreadSheetId' => '',
    'sheetName' => '',
    'credentialFile' => 'credentials.json',
    'multipleLocales' => false,
    'locales' => [
        'us' => [
            "id" => "",
            "run" => false
        ],
        'uk' => [
            "id" => "",
            "run" => false
        ],
        'ca' => [
            "id" => "",
            "run" => false
        ],
        'fr' => [
            "id" => "",
            "run" => false
        ],
        'vn' => [
            "id" => "",
            "run" => false
        ],
    ]
];
```
``spreadSheetId`` param is default google spreadsheet file. If your website is mulilanguages and if you want to 
generate page feeds file base on each other languages, so change ``multipleLocales`` to ``true`` and set google
spreadsheet id for each locales.

#####Run

After install and config complete. Just run this url to generate file google spreadsheet. Make sure all file spreadsheet
is shared.

```
http://yourdomain/pagefeeds-generator
http://yourdomain/coupon-feeds-generator?spreadSheetId=abcxyz
http://yourdomain/deal-feeds-generator?spreadSheetId=abcxyz
```

Thanks