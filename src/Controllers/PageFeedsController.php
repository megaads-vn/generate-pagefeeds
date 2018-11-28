<?php
namespace Megaads\Generatepagefeeds\Controllers;


use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Megaads\Generatesitemap\Models\Stores;
use Mockery\Exception;

class PageFeedsControllers extends Controller
{
    protected $storeRouteName = 'frontend::store::listByStore';
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
        $rawStringQuery = 'slug, status, ';
        try {
            $query = $this->buildFilter($request);
            if ( $request->has('change_pos') && $request->get('change_pos') == 'change') {
                $addAlias = 'concat(`auto_text`, concat(" ", `title`)) as "title"';
            } else {
                $addAlias = 'concat(concat(`title`," "),`auto_text`) as "title"';
            }
            $rawStringQuery = $rawStringQuery . $addAlias;
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
                    if ( $item->status == 'enable' ) {
                        $dataItem[] = route($this->storeRouteName, ['slug' => $item->slug]);
                        $dataItem[] = $item->title;
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
}