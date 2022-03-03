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

	

    public function goingToExpire() {
		
		$api = $this->loadForceApi();

		$productName = "Books Online";


		$d1 = new DateTime();
		$d2 = new DateTime();
		$d1->modify('-365 day');
		$d2->modify('-335 day');

		$r1 = $d1->format('Y-m-d');
		$r2 = $d2->format('Y-m-d');


		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True)  GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) >= {$r1} AND MAX(Order.EffectiveDate) <= {$r2}";

		$resp = $api->query($soql);

		// Convert these to subscriptions; 
		// var_dump($resp); exit;
		$formatted = array();

		foreach($resp->getRecords() as $record) {
			$friendly = new DateTime($record["EffectiveDate"]);
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





	public function doMail($to, $subject, $title, $content, $headers = array()){

		$headers = [
			"From" 		   	=> "Notifications <notifications@ocdla.app>",
			"Content-Type" 	=> "text/html",
			"Cc"			=> "jroot@ocdla.org",
			"Bcc" 			=> "jbernal.web.dev@gmail.com"
		];

		$headers = HttpHeaderCollection::fromArray($headers);

		// var_dump($headers);exit;

		$message = new MailMessage($to);
		$message->setSubject($subject);
		$message->setBody($content);
		$message->setHeaders($headers);
		$message->setTitle($title);

		return $message;
	}


// First notice; send at 30 day expiry;
// Second notice at 7 days.
	public function testMail() {
		$list = new MailMessageList();

		$subscribers = $this->goingToExpire();
		// var_dump($subscribers);exit;


		$member1 = array(
			"FirstName" => "Jennifer",
			"LastName" => "Root",
			"Email" => "jroot@ocdla.org",
			"ExpirationDate" => "March 5, 2022"
		);

		$member2 = array(
			"FirstName" => "José",
			"LastName" => "Bernal",
			"Email" => "test-hm2my9xjf@srv1.mail-tester.com",
			"ExpirationDate" => "March 5, 2022"
		);

		$member3 = array(
			"FirstName" => "José",
			"LastName" => "Bernal",
			"Email" => "admin@ocdla.org",
			"ExpirationDate" => "March 5, 2022"
		);

	

		$members = array($member1, $member2, $member3);

		foreach($subscribers as $member) {

			
			$subject = "Books Online notifications";
	
			$notice = new Template("expiring-first-notification");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);

			$list->add($this->doMail($member["Email"], $subject, "OCDLA Books Online 
			Subscription", $content));
		}

		return $list;
	}
}