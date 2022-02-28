<?php

namespace Ocdla;





class Subscription {


	private $contactId;



	private $OrderItem;



	public function __construct($OrderItem) {

		$this->OrderItem = $OrderItem;
	}

	public function daysRemaining() {

		
		$expiryDate = $this->getExpiration();


		
		return Date::daysFromToday($expiryDate);
	}


	
	public function getExpiration() {

		$orderStartDate = $this->OrderItem["Order"]["EffectiveDate"];
		$startDate = new \DateTime($orderStartDate);
		
		// Uncomment to mock an Order EffectiveDate
		$expiryDate = clone $startDate;
		$expiryDate->modify("+365 day");

		return $expiryDate;
	}



	public function getAlertHtml($message) {


		$expiryDate = $this->getExpiration();
		$daysRemaining = $this->daysRemaining();


		$template = "/var/www/html/extensions/BooksOnline/templates/bon-alert.tpl.php";
		

		$renewThroughDate = clone $expiryDate;
		$renewThroughDate->modify("+365 day");

		$notice = Date::getFriendlyDateDifference($message,$daysRemaining);

		if(null == $notice) return $notice;


		$params = array(
			"expiryDate" => $expiryDate,
			"renewThroughDate" => $renewThroughDate,
			"message" => $notice
		);

		return Template::renderTemplate($template, $params);
	}


}