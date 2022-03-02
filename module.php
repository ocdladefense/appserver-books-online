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


		$lastYear = new DateTime();

		$lastYear = $lastYear->format('Y-m-d');

		
		$soql = "SELECT Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, Id, OrderId, Order.ActivatedDate, Order.EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%{$productName}%' AND IsActive = True) AND Order.EffectiveDate >= 2021-03-01 AND Order.StatusCode != 'Draft' ORDER BY Order.ActivatedDate DESC";

		$resp = $api->query($soql);

		// Convert these to subscriptions; 
		var_dump($resp); exit;
		$formatted = array();

		foreach($resp->getRecords() as $record) {
			$item = array(
				"FirstName" => $record["Contact__r"]["FirstName"],
				"LastName" => $record["Contact__r"]["LastName"],
				"Email" => $record["Contact__r"]["Email"],
				"ExpirationDate" => $expiration
			);

			$formatted []=$item;
		}

		return $formatted;
	}





	public function doMail($to, $subject, $title, $content, $headers = array()){

		$headers = [
			"From" 		   => "notifications@ocdla.org",
			"Content-Type" => "text/html"
		];

		$headers = HttpHeaderCollection::fromArray($headers);



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

		$records = $this->goingToExpire();
		var_dump($records);exit;

		$member1 = array(
			"FirstName" => "Jennifer",
			"LastName" => "Root",
			"Email" => "jroot@ocdla.org",
			"ExpirationDate" => "March 5, 2022"
		);

		$member2 = array(
			"FirstName" => "José",
			"LastName" => "Bernal",
			"Email" => "jbernal.web.dev@gmail.com",
			"ExpirationDate" => "March 5, 2022"
		);

	

		$members = array($member1, $member2);

		foreach($members as $member) {

			
			$subject = "Books Online notifications";
	
			$notice = new Template("expiring-first-notification");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);

			$list->add($this->doMail($member["Email"], $subject, "Your Books Online subscription", $content));
		}

		return $list;
	}
}