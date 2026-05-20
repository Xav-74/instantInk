<?php

/*
* A PHP Client for HP instantInk API
*/

class instantInk_API
{
    //HP URLs - subject to change
    const PORTAL_URL     = 'https://portal.hpsmart.com';
    const USERMGMT_URL   = 'https://us1.api.ws-hp.com';
    const INSTANTINK_URL = 'https://instantink.hpconnected.com';
    
    const USER_AGENT     = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36';
    
    private $shellSessionId;
    private $shellSessionExpires;
    private $accessToken;
    private $accessTokenExpires;
    private $tenantAccessToken;
    private $tenantAccessTokenExpires;
    private $tenantId;
    private $accountId;
    

    public function __construct($shellSessionId = null)
    {
        if ($shellSessionId) {
            $this->shellSessionId = trim($shellSessionId);
        }
    }


    private function _request($method, $url, array $headers = [], $data = null)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => $headers,
        ]);
        
        // Set data
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $response  = curl_exec($ch);
        
        if ($response === false) {
            $e = curl_error($ch);
            unset($ch);
            throw new \Exception('cURL : '.$e);
        }
        
        // Get response
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);
        unset($ch);
                
        return (object)[
            'headers' => $header,
            'body' => $body,
            'httpCode' => $httpCode
        ];
    }


    private function _setDefaultHeaders()           //Define default headers
    {				
			$headers = [
			'Accept: application/json',
            'User-Agent: '.$this::USER_AGENT,
            'Origin: '.$this::PORTAL_URL,
            'Referer: '. $this::PORTAL_URL.'/',
        ];
     	return $headers;
	}	


    private function _saveTokens()
    {
        $filename = dirname(__FILE__).'/../data/instantInk_tokens.json';
        $tokens = [
            'shellSessionId'           => $this->shellSessionId,
            'shellSessionExpires'      => $this->shellSessionExpires,
            'accessToken'              => $this->accessToken,
            'accessTokenExpires'       => $this->accessTokenExpires,
            'tenantId'                 => $this->tenantId,
            'tenantAccessToken'        => $this->tenantAccessToken,
            'tenantAccessTokenExpires' => $this->tenantAccessTokenExpires,
            'accountId'                => $this->accountId,
            'saved_at'             => date('Y-m-d H:i:s'),
        ];
        file_put_contents($filename, json_encode($tokens, JSON_PRETTY_PRINT));
	    log::add('instantInk', 'debug', '| Tokens saved to : ' . $filename);
    }


    private function _loadTokens()
    {
        $filename = dirname(__FILE__).'/../data/instantInk_tokens.json';
        if (file_exists($filename)) {
            $tokens = json_decode(file_get_contents($filename), true);
            $this->shellSessionId           = $tokens['shellSessionId'] ?? null;
            $this->shellSessionExpires      = $tokens['shellSessionExpires'] ?? 0;
            $this->accessToken              = $tokens['accessToken'] ?? null;
            $this->accessTokenExpires       = $tokens['accessTokenExpires'] ?? 0;
            $this->tenantId                 = $tokens['tenantId'] ?? null;
            $this->tenantAccessToken        = $tokens['tenantAccessToken'] ?? null;
            $this->tenantAccessTokenExpires = $tokens['tenantAccessTokenExpires'] ?? 0;
            $this->accountId                = $tokens['accountId'] ?? null;
            log::add('instantInk', 'debug', '| Tokens loaded from : ' . $filename);
        }
    }

    
    private function _isAccessTokenExpired()
    {
        if (!$this->accessToken || !$this->accessTokenExpires) return true;
        return time() >= ($this->accessTokenExpires - 60);
    }


    private function _isSessionExpired()
    {
        if (!$this->shellSessionId) return true;
        return time() >= ($this->shellSessionExpires - 3600);
    }


    private function _checkValidToken()
    {
        if ($this->_isAccessTokenExpired()) {
            log::add('instantInk', 'debug', '| accessToken about to expire, refreshing...');
            $this->refreshTokens();
        }
    }


    private function _getTenantId($stratusId)
    {
        $method = 'GET';
        $url = $this::USERMGMT_URL.'/v3/usermgtsvc/usertenantdetails'.'?userResourceId='.$stratusId.'&state=Active&tenantType=Personal';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer '.$this->accessToken;
                
        $result = $this->_request($method, $url, $headers);
        log::add('instantInk', 'debug', '| Result getTenantId() : ['.$result->httpCode.'] '.$result->body);
        
        if ($result->httpCode !== 200) {
            log::add('instantInk', 'debug', '| getTenantId error');
            throw new \Exception('getTenantId error');
        }

        $json = json_decode($result->body, true);
        $tenantId = $json['resourceList'][0]['tenantResourceId'] ?? null;

        if (!$tenantId) {
            log::add('instantInk', 'debug', '| No tenantId found');
            throw new \Exception('No tenantId found');
        }

        return $tenantId;
    }


    private function _getAccountId()
    {
        $method = 'GET';
        $url = $this::INSTANTINK_URL.'/api/dashboard/v1/ucde';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer ' . $this->tenantAccessToken;
        
        $result = $this->_request($method, $url, $headers);
        $json = json_decode($result->body, true);
                
        log::add('instantInk', 'debug', '| Result getAccountId() : ['.$result->httpCode.'] '.$result->body);
        return $json['account_identifier'];
    }


    public function getConnectionStatus()
    {
        $this->_loadTokens();
        return [
            'has_session'     => !empty($this->shellSessionId),
            'session_expired' => $this->_isSessionExpired(),
            'session_expires' => $this->shellSessionExpires ? date('d/m/Y', $this->shellSessionExpires) : '?',
            'has_token'       => !empty($this->accessToken),
            'token_expired'   => $this->_isAccessTokenExpired(),
            'token_expires'   => $this->accessTokenExpires ? date('d/m/Y H:i', $this->accessTokenExpires) : '?',
        ];
    }
        
    
    public function refreshTokens()
    {
        if (!$this->shellSessionId) {
            log::add('instantInk', 'debug', '| unknown shellSessionId, please reconnect');
            throw new \Exception('shellSessionId unknown, please reconnect');
        }

        //Step 1 - accessToken
        $method = 'POST';
        $url = $this::PORTAL_URL . '/api/session/v3/token';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Content-Type: application/json';
        $headers[] = 'Cookie: shell-session-id='.$this->shellSessionId;
        $data = json_encode(['tenantType' => 'orgless', 'shellTenantsData' => (object)[]]);

        $result = $this->_request($method, $url, $headers, $data);
        log::add('instantInk', 'debug', '| Result refreshTokens() - step 1 : ['.$result->httpCode.'] '.$result->body);
                
        if ($result->httpCode === 401 || $result->httpCode === 403) {
            log::add('instantInk', 'debug', '| invalid ou expired shellSessionId, please reconnect');
            throw new \Exception('invalid ou expired shellSessionId, please reconnect');
        }
        
        if ($result->httpCode !== 200) {
            log::add('instantInk', 'debug', '| refreshTokens error');
            throw new \Exception('refreshTokens error');
        }

        $json = json_decode($result->body, true);
        $token = $json['shellTenantlessData']['token'];
           
        if (!$token) {
            log::add('instantInk', 'debug', '| No accessToken found');
            throw new \Exception('No accessToken found');
        }
        $this->accessToken = $token;
        $this->accessTokenExpires = time() + (int)($json['shellStratusAccessTokenExpireIn'] ?? 3599);

        if (!empty($json['shellSessionId'])) {
            $this->shellSessionId     = $json['shellSessionId'];
            $this->shellSessionExpires = time() + (int)($json['shellSessionIdExpireIn'] ?? 7776000);
            log::add('instantInk', 'debug', '| shellSessionId refreshed');
        }

        //Step 2 - tenantToken
        if (!$this->tenantId) {
            $parts   = explode('.', $this->accessToken);
            $decoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
            $stratusId = $decoded['stratus_id'] ?? null;

            if (!$stratusId) {
                log::add('instantInk', 'debug', '| No stratus_id found');
                throw new \Exception('No stratus_id found');
            }

            $this->tenantId = $this->_getTenantId($stratusId);
            log::add('instantInk', 'debug', '| tenantId found : '.$this->tenantId);
        }

        $method2 = 'POST';
        $url2 = $this::PORTAL_URL . '/api/session/v3/token';
        $headers2 = $this->_setDefaultHeaders();
        $headers2[] = 'Content-Type: application/json';
        $headers2[] = 'Cookie: shell-session-id='.$this->shellSessionId;
        $data2 = json_encode(['tenantType' => 'organization', 'shellTenantsData' => ['organization' => $this->tenantId]]);

        $result2 = $this->_request($method2, $url2, $headers2, $data2);
        log::add('instantInk', 'debug', '| Result refreshTokens() - step 2 : ['.$result2->httpCode.'] '.$result2->body);

        if ($result2->httpCode !== 200) {
            log::add('instantInk', 'debug', '| refreshTokens error');
            throw new \Exception('refreshTokens error');
        }

        $json2 = json_decode($result2->body, true);
        $token2 = $json2['shellTenantData']['token'];
           
        if (!$token2) {
            log::add('instantInk', 'debug', '| No tenantToken found');
            throw new \Exception('No tenantToken found');
        }
        $this->tenantAccessToken = $token2;
        $this->tenantAccessTokenExpires = time() + (int)($json2['shellStratusAccessTokenExpireIn'] ?? 3599);

        log::add('instantInk', 'debug', '| Access token valid — expires in '. ($json['shellStratusAccessTokenExpireIn'] ?? '?') . 's');
        log::add('instantInk', 'debug', '| Tenant token valid — expires in '. ($json2['shellStratusAccessTokenExpireIn'] ?? '?') . 's');

        //Step 3 - accountId
        $this->accountId = $this->_getAccountId();
        log::add('instantInk', 'debug', '| Account Id : '.$this->accountId);
                
        $this->_saveTokens();
        return;
    }

    
    public function connection()
    {
        $this->_loadTokens();
        $this->_checkValidToken();

        $method = 'GET';
        $url = $this::USERMGMT_URL.'/v3/usermgtsvc/users/me';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer '.$this->accessToken;
                
        $result = $this->_request($method, $url, $headers);
        log::add('instantInk', 'debug', '| Result connection() : ['.$result->httpCode.'] '.$result->body);
        
        if ($result->httpCode !== 200) {
            log::add('instantInk', 'debug', '| connection error');
            throw new \Exception('connection error');
        }

        $json = json_decode($result->body, true);
        return [
            'email'     => $json['email']['value'] ?? '',
            'firstName' => $json['givenName']       ?? '',
            'lastName'  => $json['familyName']      ?? '',
        ];
    }


    public function getPrinters()
    {
        $method = 'GET';
        $url = $this::INSTANTINK_URL.'/api/dashboard/v1/subscription/'.$this->accountId.'/printer';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer ' . $this->tenantAccessToken;
        
        $result = $this->_request($method, $url, $headers);
                        
        log::add('instantInk', 'debug', '| Result getPrinters() : ['.$result->httpCode.'] '.$result->body);
        return $result;
    }


    public function getInstantInkDataDashboard()
    {
        $method = 'GET';
        $url = $this::INSTANTINK_URL.'/api/dashboard/v1/subscription/'.$this->accountId.'?flow=dashboard';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer ' . $this->tenantAccessToken;
        
        $result = $this->_request($method, $url, $headers);
                        
        log::add('instantInk', 'debug', '| Result getInstantInkDataDashboard() : ['.$result->httpCode.'] '.$result->body);
        return $result;
    }


    public function getInstantInkDataBillingCycle($id)
    {
        $method = 'GET';
        $url = $this::INSTANTINK_URL.'/api/dashboard/v1/subscription/'.$this->accountId.'/billing_cycle/'.$id;
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer ' . $this->tenantAccessToken;
        
        $result = $this->_request($method, $url, $headers);
                        
        log::add('instantInk', 'debug', '| Result getInstantInkDataBillingCycle() : ['.$result->httpCode.'] '.$result->body);
        return $result;
    }
    
    
    public function getInstantInkDataInkStatus()
    {
        $method = 'GET';
        $url = $this::INSTANTINK_URL.'/api/dashboard/v3/subscription/'.$this->accountId.'/ink_status';
        $headers = $this->_setDefaultHeaders();
        $headers[] = 'Authorization: Bearer ' . $this->tenantAccessToken;
        
        $result = $this->_request($method, $url, $headers);
                        
        log::add('instantInk', 'debug', '| Result getInstantInkDataInkStatus() : ['.$result->httpCode.'] '.$result->body);
        return $result;
    }
}
