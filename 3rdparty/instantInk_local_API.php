<?php

/*
* A PHP Client for HP instantInk API
*/

class instantInk_local_API
{
    const PRINTER_URL     = '/DevMgmt/ConsumableConfigDyn.xml';
    
    private $ipAddress;
    
    public function __construct($ipAddress = null)
    {
        if ($ipAddress) {
            $this->ipAddress = trim($ipAddress);
        }
        else { $this->ipAddress = ''; }
    }


    private function _request($method, $url)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        
        $response  = curl_exec($ch);
        
        if ($response === false) {
            $e = curl_error($ch);
            unset($ch);
            throw new \Exception('cURL : '.$e);
        }
    
        unset($ch);
                
        return $response;
    }
    
    
    public function getXML()
    {
        $method = 'GET';
        $url = 'http://'.$this->ipAddress.$this::PRINTER_URL;
        
        $result = $this->_request($method, $url);
                        
        log::add('instantInk', 'debug', '| Result getXML() : '.substr(str_replace(["\r", "\n", "\t"], ' ', $result), 0, 2500));
        return $result;
    }


    public function parseXMLConsumables($xmlString)
    {
        $xmlString = ltrim($xmlString, "\xEF\xBB\xBF");
        $xmlString = str_replace("\r\n", "\n", $xmlString);

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($xmlString, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        $xml = simplexml_import_dom($dom);

        $result = [];

        if ($loaded) {
            $xml->registerXPathNamespace('ccdyn', 'http://www.hp.com/schemas/imaging/con/ledm/consumableconfigdyn/2007/11/19');
            $xml->registerXPathNamespace('dd',    'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
            
            foreach ($xml->xpath('//ccdyn:ConsumableInfo') as $consumable) {
                $consumable->registerXPathNamespace('dd', 'http://www.hp.com/schemas/imaging/con/dictionaries/1.0/');
                $label   = (string)($consumable->xpath('dd:ConsumableLabelCode')[0] ?? '');
                $percent = (int)($consumable->xpath('dd:ConsumablePercentageLevelRemaining')[0] ?? 0);
                if ($label) {
                    $result[] = ['label' => $label, 'percent' => $percent];
                }
            }
        }
        
        log::add('instantInk', 'debug', '| Result parseXMLConsumables() : '.json_encode($result));
        return $result;
    }
}
