<?php

namespace Megaads\Generatepagefeeds\Services;


class GoogleClientService
{
    const CLIENT_APP_NAME = 'client-driver';
    private $credentialFile = "";
    private $client;

    public function __construct()
    {
        $this->credentialFile = public_path() . '/' .config('pagefeeds.credentialFile');
        $this->client = $this->getClient();
    }

    /**
     * @param $spreadSheetId
     * @param $values
     * @param $range
     * @return string
     */
    public function addValues($spreadSheetId, $values, $range)
    {
        $data = [];
        $data[] = new \Google_Service_Sheets_ValueRange([
            'range' => $range,
            'values' => $values
        ]);
        // Additional ranges to update ...
        $body = new \Google_Service_Sheets_BatchUpdateValuesRequest([
            'valueInputOption' => 'RAW',
            'data' => $data
        ]);
        $clearSheetValue = $this->clearSheetValues($spreadSheetId, $range);
        if ( !empty($clearSheetValue) ) {
            $service = new \Google_Service_Sheets($this->client);
            $result = $service->spreadsheets_values->batchUpdate($spreadSheetId, $body);
            return sprintf("%d cells updated." ,  $result->getTotalUpdatedCells());
        } else {
            throw new \Exception("Cannot clear spreadsheet value. Please check again!");
        }
    }

    /**
     * @param $title
     * @return mixed
     */
    public function createSpreadSheet($title)
    {
        try {
            $service = new \Google_Service_Sheets($this->client);
            // [START sheets_create]
            $spreadsheet = new \Google_Service_Sheets_Spreadsheet([
                'properties' => [
                    'title' => $title
                ]
            ]);
            $spreadsheet = $service->spreadsheets->create($spreadsheet, [
                'fields' => 'spreadsheetId'
            ]);
            $spreadsheetId = $spreadsheet->spreadsheetId;

            $this->setPermission($spreadsheetId);
            return ['status' => 'success', 'spreadsheetId' => $spreadsheetId];
        } catch (\Exception $exception) {
            \Log::error("Create SpreadSheet Error " . $exception->getMessage());
            return ['status' => 'failed', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @param $spreadSheetId
     * @param $range
     * @return \Google_Service_Sheets_ClearValuesResponse
     */
    private function clearSheetValues($spreadSheetId, $range)
    {
        try {
            $service = new \Google_Service_Sheets($this->client);
            $requestBody = new \Google_Service_Sheets_ClearValuesRequest();
            $response = $service->spreadsheets_values->clear($spreadSheetId, $range, $requestBody);
            return $response;
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage());
        }
    }

    /**
     * @param $spreadSheetName
     * @return \Illuminate\Http\JsonResponse|string
     */
    private function checkExists($spreadSheetName)
    {
        try{
            $service = new \Google_Service_Drive($this->client);
            $spreadSheetId = "";
            $listFile = $service->files->listFiles();
            if ( !empty($listFile) ) {
                foreach ($listFile as $file) {
                    if ($file->name == $spreadSheetName) {
                        $spreadSheetId = $file->id;
                        break;
                    }
                }
            }
            return $spreadSheetId;
        } catch (\Exception $exception) {
            return response()->json(['status' => 'failed', 'message' => $exception->getMessage()]);
        }
    }


    /**
     * @param $spreadSheetId
     * @return \Illuminate\Http\JsonResponse
     */
    private function setPermission($spreadSheetId)
    {
        $service = new \Google_Service_Drive($this->client);
        $service->getClient()->setUseBatch(true);

        try{
            $batch = $service->createBatch();

            $userPermission = new \Google_Service_Drive_Permission([
                'type' => 'user',
                'role' => 'writer',
                'emailAddress' => 'adword.megaads@gmail.com'
            ]);

            $request = $service->permissions->create($spreadSheetId, $userPermission, array('fields' => 'id'));
            $batch->add($request, 'user');
            $results = $batch->execute();

        } catch (\Exception $exception) {
            throw new \Exception('Error ' . $exception->getMessage());
        } finally {
            $service->getClient()->setUseBatch(false);
        }
    }


    /**
     * @return \Google_Client
     * @throws \Exception
     */
    private function getClient()
    {
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->credentialFile);
        try {

            $client = new \Google_Client();
            $client->useApplicationDefaultCredentials();
            $client->setApplicationName(self::CLIENT_APP_NAME);
            $client->setScopes(['https://www.googleapis.com/auth/drive','https://spreadsheets.google.com/feeds']);

            if ( $client->isAccessTokenExpired() ) {
                $client->refreshTokenWithAssertion();
            }

            $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];

            $client->setAccessToken($accessToken);

            return $client;
        } catch (\Exception $ex) {
            throw new \Exception('Error ' . $ex->getMessage());
        }
    }

}