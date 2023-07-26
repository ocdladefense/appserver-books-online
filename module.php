<?php

use Mysql\Database;
use Http\HttpRequest;
use Http\HttpHeader;
use Mysql\DbHelper;
use Mysql\QueryBuilder;
use Http\HttpHeaderCollection;

use function Mysql\insert;
use function Mysql\update;
use function Mysql\select;
use function Session\get_current_user;





class BonModule extends Module {

    public function __construct() {

        parent::__construct();
    }

	public function range($d1, $d2) {

		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True)  GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) >= {$r1} AND MAX(Order.EffectiveDate) <= {$r2}";
	}



    public function list($range) {


		$productName = "Books Online";
		$daysUntilExpiry = 365 - $range;

		$d1 = new \DateTime();
		$d2 = new \DateTime();
		$d1->modify('-365 day');
		$d2->modify('-'.$daysUntilExpiry.' day');

		$date1 = $d1->format('Y-m-d');
		$date2 = $d2->format('Y-m-d');


        $expiring = $this->expiresBetween($date1, $date2);
		var_dump($expiring);
		exit;
        $messages = $this->getMessages($expiring);


        // $results = MailClient::sendMail($messages);

        var_dump($results, $messages);
        exit;
    }


    public function sendMail() {
        $range = 30;

		$productName = "Books Online";
		$daysUntilExpiry = 365 - $range;

		$d1 = new \DateTime();
		$d2 = new \DateTime();
		$d1->modify('-365 day');
		$d2->modify('-'.$daysUntilExpiry.' day');

		$date1 = $d1->format('Y-m-d');
		$date2 = $d2->format('Y-m-d');


        $expiring = $this->expiresBetween($date1, $date2);

        $messages = $this->getMessages($expiring, true);
		// var_dump($messages);exit;

        $results = MailClient::sendMail($messages);

        var_dump($results, $messages);
        exit;
    }








	public function sendBonExpirations($daysBefore = 30) {

		$productName = "Books Online";
		$purchaseDaysInPast = 365 - $daysBefore;

		$purchaseDate = new \DateTime();
		$purchaseDate->modify('-'.$purchaseDaysInPast.' day');
		


    	$expiring = $this->goingToExpire($purchaseDate);

        $messages = $this->getMessages($expiring, false);
		

        $results = MailClient::sendMail($messages);

        var_dump($results, $messages);
        exit;
    }


    public function testBonExpirations() {
        $range = 30;

		$productName = "Books Online";
		$daysUntilExpiry = 365 - $range;

		$d1 = new \DateTime();
		$d2 = new \DateTime();
		$d1->modify('-365 day');
		$d2->modify('-'.$daysUntilExpiry.' day');

		$date1 = $d1->format('Y-m-d');
		$date2 = $d2->format('Y-m-d');


        $expiring = $this->expiresBetween($date1, $date2);

        $messages = $this->getMessages($expiring, true);
		// var_dump($messages);exit;

        $results = MailClient::sendMail($messages);

        var_dump($results, $messages);
        exit;
    }

	








    /**
     * Books Online purchases made 365 days in the past would be expiring TODAY.  So we query for purchases made (365 - $day) in the past; effectively
     * this means that we query for subscriptions expiring EXACTLY $days from TODAY.
     */
    public function goingToExpire($purchaseDate) {
		
		$api = loadApi();


		$purchaseDateFormatted = $purchaseDate->format('Y-m-d');

	
		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True) AND Contact__r.Email != null GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) = {$purchaseDateFormatted}";

		// var_dump($soql);

		$resp = $api->query($soql);

		// Convert these to subscriptions; 
		// var_dump($resp); exit;
		$formatted = array();

		foreach($resp->getRecords() as $record) {
			$friendly = new \DateTime($record["EffectiveDate"]);
			$friendly->modify('+365 day');
			
			$text = $friendly->format('F j, Y');

			$item = array(
				"FirstName" => $record["FirstName"],
				"LastName" => $record["LastName"],
				"Email" => $record["Email"],
				"ExpirationDate" => $text
			);

			$formatted []=$item;
		}

		return $formatted;
	}





    public function expiresBetween($start = null, $end = null) {
		
		$api = loadApi();


	
		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True) AND Contact__r.Email != null GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) >= {$start} AND MAX(Order.EffectiveDate) <= {$end}";

        var_dump($soql);

		$resp = $api->query($soql);

		// Convert these to subscriptions; 
		// var_dump($resp); exit;
		$formatted = array();

		foreach($resp->getRecords() as $record) {
			$friendly = new \DateTime($record["EffectiveDate"]);
			$friendly->modify('+365 day');
			
			$text = $friendly->format('F j, Y');

			$item = array(
				"FirstName" => $record["FirstName"],
				"LastName" => $record["LastName"],
				"Email" => $record["Email"],
				"ExpirationDate" => $text
			);

			$formatted []=$item;
		}

		return $formatted;
	}




// https://appdev.ocdla.org/bon/expiring/test
	// First notice; send at 30 day expiry;
	// Second notice at 7 days.
	public function getMessages($expiring, $test = true) {
		$list = new \MailMessageList();

		$title = "Your Books Online Subscription";


		$headers = [
			"From" 		   	=> "notifications@ocdla.org",
			"Content-Type" 	=> "text/html",
			"Cc"			=> "jroot@ocdla.org, info@ocdla.org",
            "Bcc"           => "jbernal.web.dev@gmail.com"
		];

		$headers = HttpHeaderCollection::fromArray($headers);

		foreach($expiring as $member) {
	
			$notice = new \Template("expiring-first-notification");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);

			$template = new Template("email");
			$template->addPath(get_theme_path());
			$body = $template->render(array(
				"content" 	=> $content,
				"title" 	=> $title
			));

			$message = $test ? new \MailMessage("jbernal.web.dev@gmail.com") : new \MailMessage($member["Email"]);
			$message->setSubject("Books Online notifications");
			$message->setTitle("OCDLA Books Online Subscription");
			$message->setBody($body);
			$message->setHeaders($headers);

			$list->add($message);
		}


		return $list;
	}



}