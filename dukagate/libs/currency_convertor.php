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
		if (!empty($response)) {
			$response = json_decode($response, true);
			return (double) $response['rate'];
		}else{
			return 1;
		}
    }

}

?>