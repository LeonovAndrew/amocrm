<?php
namespace Mwi\Amocrm;

use COption;

class Client
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $accessToken;
    protected $refreshToken;
    protected $accountDomain;

    public function __construct()
    {
        $this->clientId     = COption::GetOptionString("mwi.amocrm","client_id","");
        $this->clientSecret = COption::GetOptionString("mwi.amocrm","client_secret","");
        $this->redirectUri  = COption::GetOptionString("mwi.amocrm","redirect_uri","");
        $this->accessToken  = COption::GetOptionString("mwi.amocrm","access_token","");
        $this->refreshToken = COption::GetOptionString("mwi.amocrm","refresh_token","");
    }

    public function getAuthUrl(): string
    {
        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
        ]);
        return "https://amocrm.ru/oauth?{$params}";
    }

    public function exchangeCodeForToken(string $code)
    {
        $url = "https://dveriprovans.amocrm.ru/oauth2/access_token";
        $post = [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
            'redirect_uri'  => $this->redirectUri,
        ];
        $resp = $this->httpPostJson($url, $post);
        if(isset($resp['access_token'])){
            COption::SetOptionString("mwi.amocrm","access_token",$resp['access_token']);
            COption::SetOptionString("mwi.amocrm","refresh_token",$resp['refresh_token']);
            COption::SetOptionString("mwi.amocrm","token_expires", time() + intval($resp['expires_in']));
            $this->accessToken = $resp['access_token'];
            $this->refreshToken = $resp['refresh_token'];

            return true;
        }
        return $resp;
    }

    protected function httpPostJson(string $url, array $data): array
    {
        $ch = curl_init($url);
        $json = json_encode($data);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $json,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Content-Length: '.strlen($json)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true) ?: [];
    }

    protected function httpRequest(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init($url);
        $headers = ['Authorization: Bearer '.$this->accessToken, 'Content-Type: application/json'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($method === 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code'=>$httpCode,'body'=>json_decode($res,true)];
    }

    protected function refreshAccessTokenIfNeeded(): void
    {
        // Приводим к int, чтобы безопасно делать вычитание
        $expires = (int)COption::GetOptionString("mwi.amocrm","token_expires",0);

        if(time() > $expires - 60){
            $url = "https://dveriprovans.amocrm.ru/oauth2/access_token";
            $post = [
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'redirect_uri'  => $this->redirectUri,
            ];

            $resp = $this->httpPostJson($url, $post);

            if(isset($resp['access_token'])){
                COption::SetOptionString("mwi.amocrm","access_token",$resp['access_token']);
                COption::SetOptionString("mwi.amocrm","refresh_token",$resp['refresh_token']);
                COption::SetOptionString("mwi.amocrm","token_expires", time() + intval($resp['expires_in']));
                $this->accessToken = $resp['access_token'];
                $this->refreshToken = $resp['refresh_token'];
            }
        }
    }


    public function createLead(array $leadData): array
    {
        $this->refreshAccessTokenIfNeeded();
        $domain = COption::GetOptionString("mwi.amocrm","account_domain","example.amocrm.ru");
        $url = "https://{$domain}/api/v4/leads";
        $payload = $leadData;
        $resp = $this->httpRequest('POST', $url, $payload);
        if($resp['code'] === 401){
            $this->refreshAccessTokenIfNeeded();
            $resp = $this->httpRequest('POST', $url, $payload);
        }
        return $resp;
    }

    /**
     * Создать контакт
     * @param array $contactData ['name'=>'Имя', 'first_name'=>'Имя', 'last_name'=>'Фамилия', 'email'=>'test@mail.ru', 'phone'=>'79991234567']
     * @return array ['code'=>HTTP_CODE, 'body'=>RESPONSE]
     */
    public function createContact(array $contactData): array
    {
        $this->refreshAccessTokenIfNeeded();
        $domain = COption::GetOptionString("mwi.amocrm","account_domain","example.amocrm.ru");
        $url = "https://{$domain}/api/v4/contacts";

        // amoCRM принимает массив контактов
        $payload = $contactData;
        $resp = $this->httpRequest('POST', $url, $payload);

        // если токен просрочен, пробуем обновить
        if($resp['code'] === 401){
            $this->refreshAccessTokenIfNeeded();
            $resp = $this->httpRequest('POST', $url, $payload);
        }

        return $resp;
    }

    /**
     * Создать лид с привязкой к контактам
     * @param array $leadData ['name'=>'Лид','pipeline_id'=>1234567,'status_id'=>'NEW']
     * @param array $contactIds [id1,id2,...] — массив ID контактов для привязки
     * @return array
     */
    public function createLeadWithContacts(array $leadData, array $contactIds = []): array
    {
        if(!empty($contactIds)){
            $leadData['contacts'] = array_map(fn($id)=>['id'=>$id], $contactIds);
        }
        return $this->createLead($leadData);
    }
}
