<?php

namespace Bon;

use Mysql\Database;
use Mysql\DbHelper;
use Mysql\QueryBuilder;
use Http\HttpRequest;
use Http\HttpHeader;
use Http\HttpHeaderCollection;
use GIS\Political\Countries\US\Oregon;
use Ocdla\Date;


use function Mysql\insert;
use function Mysql\update;
use function Mysql\select;
/**
 * 
 * 
 * 
 * Standard Mail class.
 * 
 * Each Mail class should have methods for:
 * 
 * @method getTemplates Return an array of named templates that can be rendered into an email
 * body.
 * 
 * @method getSample Return a sample for the given email template.  The sample will typically be
 * an HTML template.
 * 
 * 
 */





class Mail extends \Presentation\Component {



	public function __construct() {
		parent::__construct("mail");
	}


	public function getTemplates() {

		return array(
			"bon-expiring-first-notification" 	=> "BON First Notification",
			"bon-expiring-second-notification"	=> "BON Second Notification"
		);
	}


	
	public function getCustomFields() {

		$form = new \Template("custom-fields");
		$form->addPath(__DIR__ . "/templates");



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

		$subscribers = array($member1,$member2,$member3);

		// Get the current user's ContactId.
		$subscribers = array("someid" => "José Bernal (Exp. March 5, 2022)");

		return $form->render(array("subscribers"=>$subscribers));
	}




	public function getPreview() {
		$list = new \MailMessageList();

		
		$user = current_user();
		$req = $this->getRequest();
		$body = $req->getBody();

	

	

		// $members = array($member1, $member2, $member3);
		$member = array(
			"FirstName" => "Jennifer",
			"LastName" => "Root",
			"Email" => "jroot@ocdla.org",
			"ExpirationDate" => "March 5, 2022"
		);
		

			$subject = "Books Online notifications";
	
			$notice = new \Template("expiring-first-notification");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);
		

		return array("Your OCDLA Books Online subscription","Books Online notification", $content);
	}






    public function goingToExpire($days = 30) {
		
		$api = $this->loadForceApi();

		$productName = "Books Online";
		$put = 365 - $days;

		$d1 = new \DateTime();
		$d2 = new \DateTime();
		$d1->modify('-365 day');
		$d2->modify('-'.$put.' day');

		$r1 = $d1->format('Y-m-d');
		$r2 = $d2->format('Y-m-d');

	
		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True)  GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) = {$r2}";

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



// First notice; send at 30 day expiry;
// Second notice at 7 days.
	public function testMail() {
		$list = new \MailMessageList();

		// get 'em 30 days out.
		$subscribers = $this->goingToExpire(30);
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
	
			$notice = new \Template("expiring-first-notification");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);

			$list->add($this->doMail($member["Email"], $subject, "OCDLA Books Online 
			Subscription", $content));
		}

		return $list;
	}







	/*
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
	*/


}