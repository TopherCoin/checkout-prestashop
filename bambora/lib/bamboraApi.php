<?php
/**
 * 888                             888
 * 888                             888
 * 88888b.   8888b.  88888b.d88b.  88888b.   .d88b.  888d888  8888b.
 * 888 "88b     "88b 888 "888 "88b 888 "88b d88""88b 888P"       "88b
 * 888  888 .d888888 888  888  888 888  888 888  888 888     .d888888
 * 888 d88P 888  888 888  888  888 888 d88P Y88..88P 888     888  888
 * 88888P"  "Y888888 888  888  888 88888P"   "Y88P"  888     "Y888888
 *
 * @category    Online Payment Gatway
 * @package     Bambora_Online
 * @author      Bambora Online
 * @copyright   Bambora (http://bambora.com)
 */

include('bamboraModels.php');
include('bamboraEndpoints.php');

class BamboraApi
{
    private $apiKey = "";

    /**
     * __construct
     * 
     * @param mixed $apiKey 
     */
    function __construct($apiKey = "")
    {
        $this->apiKey = $apiKey;
    }

    /**
     * getcheckoutresponse
     * 
     * @param mixed $bamboracheckoutrequest 
     * @return mixed
     */
    public function getcheckoutresponse($bamboracheckoutrequest)
    {
        $serviceUrl = BamboraendpointConfig::getCheckoutEndpoint().'/checkout' ;

        $jsonData = json_encode($bamboracheckoutrequest);
        $expresscheckoutresponse = $this ->_callRestService($serviceUrl, $jsonData, "POST");

        return json_decode($expresscheckoutresponse, true);
    }

    /**
     * getcheckoutpaymentwindowjs
     * 
     * @return string
     */
    public function getcheckoutpaymentwindowjs()
    {
        $url = BamboraendpointConfig::getCheckoutAssets().'/paymentwindow-v1.min.js';

        return $url;
    }

    /**
     * capture
     * 
     * @param mixed $transactionid 
     * @param mixed $amount 
     * @param mixed $currency 
     * @return mixed
     */
    public function capture($transactionid, $amount, $currency)
	{
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/capture';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return json_decode($result, true);

	}

    /**
     * credit
     * 
     * @param mixed $transactionid 
     * @param mixed $amount 
     * @param mixed $currency 
     * @param mixed $invoicelines 
     * @return mixed
     */
    public function credit($transactionid, $amount, $currency, $invoicelines)
	{
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/credit';

        $data = array();
        $data["amount"] = $amount;
        $data["currency"] = $currency;
        $data["invoicelines"] = $invoicelines;

        $jsonData = json_encode($data);

        $result = $this->_callRestService($serviceUrl, $jsonData, "POST");
        return json_decode($result, true);
	}

    /**
     * delete
     * 
     * @param mixed $transactionid 
     * @return mixed
     */
    public function delete($transactionid)
	{
        $serviceUrl = BamboraendpointConfig::getTransactionEndpoint().'/transactions/'.  sprintf('%.0F',$transactionid) . '/delete';

        $result = $this->_callRestService($serviceUrl, null, "POST");
        return json_decode($result, true);
    }

    /**
     * gettransaction
     * 
     * @param mixed $transactionid 
     * @return mixed
     */
    public function gettransaction($transactionid)
	{
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid);

        $result = $this->_callRestService($serviceUrl, null, "GET");
        return json_decode($result, true);
	}

    /**
     * gettransactionoperations
     * 
     * @param mixed $transactionid 
     * @return mixed
     */
    public function gettransactionoperations($transactionid)
    {
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/transactions/'. sprintf('%.0F',$transactionid) .'/transactionoperations';

        $result = $this->_callRestService($serviceUrl, null, "GET");
        return json_decode($result,true);
    }

    /**
     * getPaymentTypes
     * 
     * @param mixed $currency 
     * @param mixed $amount 
     * @return mixed
     */
    public function getPaymentTypes($currency, $amount)
    {
        $serviceUrl = BamboraendpointConfig::getMerchantEndpoint().'/paymenttypes?currency='. $currency .'&amount='.$amount;

        $result = $this->_callRestService($serviceUrl, null, "GET");
        return $result;
    }

    /**
     * getAvaliablePaymentcardidsForMerchant
     * 
     * @param mixed $currency 
     * @param mixed $amount 
     * @return array
     */
    public function getAvaliablePaymentcardidsForMerchant($currency, $amount)
    {
        $res = array();
        $serviceRes = $this->getPaymentTypes($currency, $amount);

        $availablePaymentTypesResjson = json_decode($serviceRes, true);
        if ($availablePaymentTypesResjson['meta']['result'] == true)
        {
            foreach($availablePaymentTypesResjson['paymentcollections'] as $payment )
            {
                foreach($payment['paymentgroups'] as $card)
                {
                    //enshure unique id:
                    $cardname = $card['id'];
                    $res[$cardname] = $card['id'];
                }
            }

            ksort($res);
        }
        return $res;
    }

    /**
     * _callRestService
     * 
     * @param mixed $serviceUrl 
     * @param mixed $jsonData 
     * @param mixed $postOrGet 
     * @return mixed
     */
    private function _callRestService($serviceUrl,  $jsonData, $postOrGet)
    {
        $headers = array(
            'Content-Type: application/json',
            'Content-Length: '.strlen(@$jsonData),
            'Accept: application/json',
            'Authorization: '.$this->apiKey,
            'X-EPay-System: ' . BamboraHelpers::getModuleHeaderInfo()
        );

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST,$postOrGet);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_URL, $serviceUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($curl);
        return $result;
    }
}
