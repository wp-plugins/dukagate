<?php

/**
 * Invoice Manager
 */
class DukaGate_Invoice{

    /**
    * Generate invoice
	*/
	static function generate_invoice($cart_products, $order_form_info, $invoice){
		$dg_shop_settings = get_option('dukagate_shop_settings');
		$dg_advanced_shop_settings = get_option('dukagate_advanced_shop_settings');
		require_once(DG_DUKAGATE_DIR.'/libs/tcpdf/tcpdf.php');
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		$pdf->SetCreator($dg_shop_settings['shopname']);
		$pdf->SetAuthor($dg_shop_settings['shopname']);
		$pdf->SetTitle('Invoice '.$invoice);
		$pdf->SetSubject('Invoice '.$invoice);
		$pdf->SetKeywords('Invoice '.$invoice);
		
		// set default header data
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		
		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		
		//set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		
		//set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		//set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// add a page
		$pdf->AddPage();
		
		$html = file_get_contents(DG_DUKAGATE_PDF_TEMPLATE_DIR.$dg_advanced_shop_settings['pdf_invoice_file'].'.php');
		
		$orderinfo = '';
		$total = 0.00;
		foreach ($cart_products as $cart_items => $cart) {
			$orderinfo .= '<tr>';
			$orderinfo .= '<td>'.$cart['product'].' ('.$cart['children'].')</td>';
			$orderinfo .= '<td>'.$cart['quantity'].'</td>';
			$orderinfo .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['price'],2).'</td>';
			$orderinfo .= '<td>'.$dg_shop_settings['currency_symbol'].' '. number_format($cart['total'],2).'</td>';
			$orderinfo .= '</tr>';
			$total += $cart['total'];
		}
		
		$total = $dg_shop_settings['currency_symbol'].' '.$total;
		
		$userinfo = '';
		foreach ($order_form_info as $order_in => $order) {
			foreach ($order as $key => $value) {
				$userinfo .= $key.' :: '.$value .='<br/>';
			}
		}
		
		
		$array1 = array('%date%','%invoice%', '%shopname%', '%user%', '%orderinfo%','%total%');
		$array2 = array(date("Y-m-d"),$invoice, $dg_shop_settings['shopname'], $userinfo, $orderinfo,$total);
		$html = str_replace($array1, $array2, $html);
		// output the HTML content
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->lastPage();

		//Close and output PDF document
		$file_name = 'invoice_' . $invoice . '.pdf';
		$output_path = DG_DUKAGATE_INVOICE_DIR . '/' . $file_name;
		$pdf->Output($output_path,'F');
	}
	
	/**
	 * Read directory and get template files
	 */
	static function list_files(){
		$dir = DG_DUKAGATE_PDF_TEMPLATE_DIR;
		$templates = array();
		$tpfile = '';
		if ( !is_dir( $dir ) )
			return;
		if ( ! $dh = opendir( $dir ) )
			return;
		while ( ( $template = readdir( $dh ) ) !== false ) {
			if ( substr( $template, -4 ) == '.php' ){
				$tpfile = preg_replace("/\\.[^.\\s]{3,4}$/", "", $template);
				if($tpfile != 'index')
					$templates[] =$tpfile;
			}
		}
		closedir( $dh );
		return $templates;
	}
}
?>