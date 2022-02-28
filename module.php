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



    public function getRecentCarList($court = 'Oregon Appellate Court', DateTime $begin = null, DateTime $end = null) {
		$begin = null == $begin ? new DateTime() : $begin;
		
		$beginMysql = $begin->format('Y-m-j');

		if(null == $end) {
			$query = "SELECT * FROM car WHERE decision_date = '{$beginMysql}'";
			$query .= " AND court = '{$court}'";
		} else {
			$endMysql = $end->format('Y-m-j');
	
			$query = "SELECT * FROM car WHERE decision_date >= '{$beginMysql}'";
			$query .= " AND decision_date <= '{$endMysql}'";
			$query .= " AND court = '{$court}'";
		}



		// print $query;exit;
		// ORDER BY year DESC, month DESC, day DESC";
		$cars = select($query);
		
		// var_dump($cars);exit;

		$list = new Template("email-list");
		$list->addPath(__DIR__ . "/templates");

		$listHtml = $list->render(["cars" => $cars]);

		$body = new Template("email-body");
		$body->addPath(__DIR__ . "/templates");

		$params = [
			"year" => $begin->format('Y'),
			"month" => $begin->format('m'),
			"day" => $begin->format('j'),
			"date" => $begin->format('l, M j  Y'),
			"carList" => $listHtml 
		];

	
		return $body->render($params);
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


		$to = "jbernal.web.dev@gmail.com";//"jenny.root@comcast.net";
		$subject = "Books Online notifications";

		$notice = new Template("expiring-first-notification");
		$notice->addPath(__DIR__ . "/templates");

		$firstName = "Jennifer";
		$expirationDate = "March 5, 2022";

		$content = $notice->render(array(
			"firstName" => $firstName,
			"expirationDate" => $expirationDate
		));

		$range = new DateTime("2022-1-10");
		$end = new DateTime();
		
		

		return $this->doMail($to, $subject, "Your Books Online subscription", $content);
	}
}