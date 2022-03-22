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

	private $templates = array(
		"expiring-first-notification" 	=> array(
			"name" => "BON First Notification",
			"subject" => "Books Online notifications",
			"title" => "Your Books Online Subscription"
		),
		"expiring-second-notification"	=> array(
			"name" => "BON Second Notification",
			"subject" => "Books Online notifications",
			"title" => "Your Books Online Subscription"
		),
		"disregard"	=> array(
			"name" => "BON Our Mistake",
			"subject" => "Correction: Books Online notifications",
			"title" => "Your Books Online Subscription"
		)
	);


	public function __construct() {
		parent::__construct("mail");
	}


	public function getTemplates() {

		return $this->templates;
	}


	
	public function getCustomFields() {

		$form = new \Template("custom-fields");
		$form->addPath(__DIR__ . "/templates");


		$subscribers = $this->getSample();

		return $form->render(array("subscribers"=>$subscribers));
	}




	public function getPreview($template) {

		$data = $this->templates[$template];


		$list = new \MailMessageList();

		
		$user = current_user();
		$req = $this->getRequest();
		$body = $req->getBody();
		// var_dump($body);exit;
		$sample = $this->getSample();
		$subscriber = $sample[0];
		

		$subject = $data["subject"];
		$title = $data["title"];


		$notice = new \Template($template);
		$notice->addPath(__DIR__ . "/templates");
		$content = $notice->render($subscriber);
	

		return array($subject,$title,$content);
	}






    public function goingToExpire($days = 30) {
		
		$api = loadApi();

		$productName = "Books Online";
		$put = 365 - $days;

		$d1 = new \DateTime();
		$d2 = new \DateTime();
		$d1->modify('-365 day');
		$d2->modify('-'.$put.' day');

		$r1 = $d1->format('Y-m-d');
		$r2 = $d2->format('Y-m-d');

	
		$soql = "SELECT Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email, MAX(Order.EffectiveDate) EffectiveDate FROM OrderItem WHERE Product2Id IN(SELECT Id FROM Product2 WHERE Name LIKE '%Books Online%' AND IsActive = True) AND Contact__r.Email != null GROUP BY Contact__c, Contact__r.FirstName, Contact__r.LastName, Contact__r.Email HAVING MAX(Order.EffectiveDate) = {$r2}";

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
	public function getMessages($sample = false) {
		$list = new \MailMessageList();

		$headers = [
			"From" 		   	=> "notifications@ocdla.org",
			"Content-Type" 	=> "text/html",
			"Cc"			=> "jroot@ocdla.org",
            "Bcc"           => "jbernal.web.dev@gmail.com"
		];

		$headers = HttpHeaderCollection::fromArray($headers);

		// get 'em 30 days out.
		$subscribers = $sample ? $this->getSample() : $this->goingToExpire(30);

		foreach($subscribers as $member) {
	
			$notice = new \Template("disregard");
			$notice->addPath(__DIR__ . "/templates");
			$content = $notice->render($member);


			$message = new \MailMessage($member["Email"]);
			$message->setSubject("Books Online notifications");
			$message->setTitle("OCDLA Books Online Subscription");
			$message->setBody($content);
			$message->setHeaders($headers);

			$list->add($message);
		}


		return $list;
	}




	private function getSample() {
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

	

		return array($member1, $member2, $member3);
	}





}