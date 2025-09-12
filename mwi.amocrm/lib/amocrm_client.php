<?php
// lib/amocrm_client.php
class MwiAmoClient
{
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $subdomain;
    protected $accessToken;
    protected $refreshToken;

    public function __construct()
    {
        $this->clientId = COption::GetOptionString("mwi.amocrm","client_id","");
        $this->clientSecret = COption::GetOptionString("mwi.amocrm","client_secret","");
        $this->redirectUri = COption::GetOptionString("mwi.amocrm","redirect_uri","");
        $this->accessToken = COption::GetOptionString("mwi.amocrm","access_token","");
        $this->refreshToken = COption::GetOptionString("mwi.amocrm","refresh_token","");
    }

    public function getAuthUrl()
    {
        $params = http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'response_type' => 'code',
        ]);
        return "https://www.amocrm.ru/oauth?{$params}";
    }

    public function exchangeCodeForToken($code)
    {
        $url = "https://".$this->getOauthDomain()."/oauth2/access_token";
        $post = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri
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

    protected function getOauthDomain()
    {
        return "www.amocrm.ru";
    }

    protected function getApiDomain()
    {
        $account = COption::GetOptionString("mwi.amocrm","account_domain","");
        if($account){
            return $account;
        }
        return "example.amocrm.ru";
    }

    protected function httpPostJson($url, $data)
    {
        $ch = curl_init($url);
        $json = json_encode($data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: '.strlen($json)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $res = curl_exec($ch);
        curl_close($ch);
        return json_decode($res, true);
    }

    protected function httpRequest($method, $url, $data = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = ['Authorization: Bearer '.$this->accessToken, 'Content-Type: application/json'];
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

    protected function refreshAccessTokenIfNeeded()
    {
        $expires = COption::GetOptionString("mwi.amocrm","token_expires",0);
        if(time() > $expires - 60){
            $url = "https://".$this->getOauthDomain()."/oauth2/access_token";
            $post = [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refreshToken,
                'redirect_uri' => $this->redirectUri
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
        }
        return true;
    }

    public function createLead(array $leadData)
    {
        $this->refreshAccessTokenIfNeeded();
        $url = "https://".$this->getApiDomain()."/api/v4/leads";
        $payload = [$leadData];
        $resp = $this->httpRequest('POST', $url, $payload);
        if($resp['code'] === 401){
            $this->refreshAccessTokenIfNeeded();
            $resp = $this->httpRequest('POST', $url, $payload);
        }
        return $resp;
    }
}
