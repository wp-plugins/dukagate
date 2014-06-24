<?php

Class DG_CURRENCYCONVERTER {

    var $_amt = 1;
    var $_to = "";
    var $_from = "";
    var $_error = "";

    function DG_CURRENCYCONVERTER($amt=1, $to="", $from="") {
        $this->_amt = $amt;
        $this->_to = $to;
        $this->_from = $from;
    }

    function error() {
        return $this->_error;
    }

    function convert($amt=NULL, $to="", $from="") {
		$conversion_rate = 1;
        if ($amt == 0) {
            return 0;
        }
        if ($amt > 1)
            $this->_amt = $amt;
        if (!empty($to))
            $this->_to = $to;
        if (!empty($from))
            $this->_from = $from;

        $host = "http://rate-exchange.appspot.com/currency?from=".$this->_from."&to=".$this->_to;
        $response = "";
		$hCURL = curl_init();
		if($hCURL){
			curl_setopt( $hCURL, CURLOPT_HEADER, false);
			curl_setopt( $hCURL, CURLOPT_RETURNTRANSFER, true);
			
			curl_setopt( $hCURL, CURLOPT_TIMEOUT, 30 );
			curl_setopt( $hCURL, CURLOPT_URL, $host);
			curl_setopt( $hCURL, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($hCURL);
			curl_close($hCURL);
		}
		$dg_currencies = get_option('dukagate_currency_conversions');
		if (!empty($response)) {
			$j_response = json_decode($response, true);
			if(intval($j_response['rate']) > 0){
				$dg_currencies[$this->_from.''.$this->_to] = $response;
				update_option('dukagate_currency_conversions', $dg_currencies);
				$conversion_rate =  (double) $j_response['rate'];
			}
			
		}else{
			$saved_rate = $dg_currencies[$this->_from.''.$this->_to];
			if(!empty($saved_rate)){
				$j_response = json_decode($saved_rate, true);
				$conversion_rate =  (double) $j_response['rate'];
			}
		}
		return $conversion_rate;
    }

}

?>