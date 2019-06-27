<?php
namespace Megaads\Generatepagefeeds\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Megaads\Generatesitemap\Models\Stores;
use Mockery\Exception;

class PageFeedsControllers extends Controller
{
    const STATUS_ENABLE = 'active';
    protected $storeRouteName = 'frontend::store::listByStore';
    protected $couponStoreRoute = 'frontend::store::listByStore::item';
    protected $dealStoreRoute = 'frontend::deal::detail';
    private $publicPath = "";
    private $googleClient;

    public function __construct()
    {
        $this->publicPath = base_path() . '/public';
        $this->googleClient = app()->make('googleClient');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generate(Request $request)
    {
        $multipleLocale = config('pagefeeds.multipleLocales');
        if (!$multipleLocale) {
            $result = $this->generatePageFeeds($request);
        } else {
            $result = $this->generateAllLocales($request);
        }
        return response()->json(['status' => 'success', 'message' => $result]);
    }

    /**
     * @param Request $request
     * @return null
     */
    public function pageFeedsAll(Request $request)
    {
        return $this->generatePageFeeds($request);
    }


    /**
     * @param Request $request
     * @return 
     */
    public function couponFeeds(Request $request) 
    {
        $response = [
            'status' => 'fail'
        ]; 

        if ( $request->has('spreadSheetId') ) {
            $spreadSheetId  = $request->get('spreadSheetId');
            $values = $this->getCouponFeed('coupon', $request);
            $result = $this->googleClient->addValues($spreadSheetId, $values, 'A1:B');
            $response['status'] = 'successful';
            $response['result'] = $result;
        } else {
            $response['message'] = 'Invalid params';
        }

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @return
     */
    public function dealFeeds(Request $request) 
    {
        $response = [
            'status' => 'fail'
        ]; 

        if ( $request->has('spreadSheetId') ) {
            $spreadSheetId  = $request->get('spreadSheetId');
            $values = $this->getCouponFeed('deal', $request);
            $result = $this->googleClient->addValues($spreadSheetId, $values, 'A1:B');
            $response['status'] = 'successful';
            $response['result'] = $result;
        } else {
            $response['message'] = 'Invalid params';
        }

        return response()->json($response);
    }

    /**
     * ------------------------------------------
     *              PRIVATE FUNCTION
     * ------------------------------------------
     */


    /**
     * @param Request $request
     * @return null
     * @throws \Exception
     */
    private function generatePageFeeds(Request $request)
    {
        $result = NULL;
        $multipleLocale = config('pagefeeds.multipleLocales');
        $values = $this->getFeedData( ['slug', 'title'], $request);
        if ($multipleLocale) {
            $multiSpreadSheetFile = config('pagefeeds.locales');
            $localesKey = \Request::segment(1);
            if ( array_key_exists($localesKey, $multiSpreadSheetFile) && $multiSpreadSheetFile[$localesKey]["run"]) {
                $spreadSheetId = $multiSpreadSheetFile[$localesKey]["id"];
                if ( !empty($spreadSheetId) ) {
                    $result = $this->googleClient->addValues($spreadSheetId, $values, 'A1:B');
                }
            }
        } else {
            $spreadSheetId = config('pagefeeds.spreadSheetId');
            if ( !empty($spreadSheetId) ) {
                $result = $this->googleClient->addValues($spreadSheetId, $values, 'A1:B');
            }
        }
        return $result;
    }

    /**
     *
     */
    private function generateAllLocales(Request $request)
    {
        $configLocales = config('app.locales', []);
        $result = NULL;
        $data = [];
        if ( $request->has('change_pos') ) {
            $data['change_pos'] = $request->get('change_pos');
        }
        foreach ($configLocales as $keyLocale => $nameLocale) {
            $url = config('app.domain') . '/' . $keyLocale . '/pagefeeds-generator-multiple';
            $request = $this->curlRequest($url, $data);
            $response = json_decode($request);
        }
    }

    /***
     * @param $table
     * @param $columns
     * @param $request
     * @throws \Exception
     */
    private function getFeedData($columns, Request $request)
    {
        $rawStringQuery = 'slug, status, title, auto_text';
        $isChangeTitlePos = env('PAGEFEEDS_TITLE', false);
        try {
            $query = $this->buildFilter($request);
            $tableItems = $query->select(\DB::raw($rawStringQuery))->get();
            $results = [
                [
                    'Page URL',
                    'Custom Label'
                ]
            ];
            if ( !empty($tableItems) ) {
                foreach($tableItems as $item) {
                    $dataItem = array();
                    $title  = trim($item->title);
                    $autotext = trim($item->auto_text);
                    if ( $item->status == 'enable' ) {
                        $dataItem[] = route($this->storeRouteName, ['slug' => $item->slug]);
                        $dataItem[] = ($isChangeTitlePos) ? $autotext . ' ' . $title : $title . ' ' . $autotext;
                        array_push($results, $dataItem);
                    }
                }
            }
            return $results;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /***
     * @param Request $request
     * @param $table
     * @return \Illuminate\Database\Query\Builder
     */
    private function buildFilter(Request $request)
    {
        try {
            $query = new Stores();
            $status = $request->get('status', 'enable');
            $type = $request->get('type', 'store');
            $query->where('type', $type);
            $query->where('status', $status);

            return $query;
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    /***
     * @param $url
     * @param array $data
     * @param string $method
     * @param bool $isAsync
     * @return mixed
     */
    private function curlRequest($url, $data = [], $method = "GET" , $isAsync = false) {
        $channel = curl_init();
        curl_setopt($channel, CURLOPT_URL, $url);
        curl_setopt($channel, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($channel, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($channel, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($channel, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($channel, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($channel, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($channel, CURLOPT_MAXREDIRS, 3);
        curl_setopt($channel, CURLOPT_POSTREDIR, 1);
        curl_setopt($channel, CURLOPT_TIMEOUT, 10);
        curl_setopt($channel, CURLOPT_CONNECTTIMEOUT, 10);
        if($isAsync){
            curl_setopt($channel, CURLOPT_NOSIGNAL, 1);
            curl_setopt($channel, CURLOPT_TIMEOUT_MS, 400);
        }
        $response = curl_exec($channel);
        return $response;
    }

    /**
     * 
     * 
     */
    private function getCouponFeed($table, Request $request) 
    {
        $feedData = NULL;
        $status = $request->get('status', self::STATUS_ENABLE);
        $tableId = $table . '_id';
        $tableSlug = $table . '_slug';
        $tableTitle = $table . '_title';
        $routeName = $table . 'StoreRoute';
        try {
            $result = \DB::table($table)
                        ->where("$table.status", $status)
                        ->join('store', 'store.id', '=', "$table.store_id")
                        ->get(["$table.id as $tableId", "$table.slug as $tableSlug", "$table.title as $tableTitle", "store.slug as store_slug", "store.id as store_id"]);
            if ( !empty($result) ) {
                $feedData = [
                    [
                        'Page URL',
                        'Custom Label'
                    ]
                ];

                foreach($result as $item) {
                    $dataItem = array();
                    $route = route($this->$routeName, ['slug' => $item->store_slug, 'itemId' => $item->$tableId]);
                    if ( $table == 'deal' ) {   
                        $route = route($this->$routeName, ['itemId' => $item->$tableId]);
                    }
                    $dataItem[] = $route;
                    $dataItem[] = $item->$tableTitle;
                    array_push($feedData, $dataItem);
                }
            }
            return $feedData;
        } catch ( \Exception $exception ) {
            throw new \Exception($exception->getMessage());
        }
    }
}