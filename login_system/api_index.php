<?php if (!session_id()) { session_start(); }
header('Access-Control-Allow-Origin: *');  

$received_json = json_decode(file_get_contents('php://input'), true);

if(isset($_GET['post']))
{
    header('Content-Type: application/json;charset=UTF-8');
    header('Access-Control-Allow-Methods: DELETE, HEAD, GET, OPTIONS, POST, PUT');
    header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
    header('Access-Control-Max-Age: 1728000');
}

class QueryResult implements JsonSerializable {
    
    public $data;
    public $sql;
    public $query;
    public $request;
    public $success;
    public $message;

    public function __construct($request) {
        $this->sql = $processed_sql_statement;
	$this->request = $request;
    }

    public function jsonSerialize() {
        $this->content = 
        [
        		'request' => $this->request,
				'sql' => [ 'query' => $this->query ],
				'result' => $this->data,
				'status' => ['success' => $this->success, 'message' => $this->message]
        ];
        return $this->content;
    }
}
class Request implements JsonSerializable {
    
    public $type;
    public $distinct;
    public $skip;
    public $take;
    public $whereCondition;

    public function __construct($type) {
        $this->type = $type;
        //$this->distinct = $distinct;
    }

    public function jsonSerialize() {
        $this->content = 
        [
                'type' => $this->type,
                'whereCondition' => $this->whereCondition,
                'skip' => $this->skip,
                'distinct' => $this->distinct,
                'take' => $this->take
        ];
        return $this->content;
    }
}

class DataLayer{ 
	public function __construct(&$queryResult) {
	} 

	function GetConnection()
	{
        $mysqli = new mysqli("localhost","user", "password", "database");
		mysqli_set_charset($mysqli, 'utf8');
		if (mysqli_connect_errno())
		{
			die("Failed to connect to MySQL: " . mysqli_connect_error());
		}
		return $mysqli;
	}
	
	function GetLogip(&$queryResult) {
                $mysqli = $this->GetConnection();
                $sql= " SELECT *, count(`id`) as `cnt` FROM `logip` GROUP BY `lng`, `lat` ORDER BY count(`id`) DESC ";
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
                               $queryResult->data[] = [ 'count' => floatval($row['cnt']), 'lat' => floatval ($row['lat']), 'lng' => floatval($row['lng']) ];
                        }
                }
        }
	function GetPosts(&$queryResult) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql= "SELECT " .($queryResult->request->distinct ? " DISTINCT `lat`, `lng` " : "*");
		$direction = $queryResult->request->type == 'message' ? 'ASC':'DESC';
$sql .= 
<<<EOT
		 FROM {$queryResult->request->type} {$queryResult->request->whereCondition}  ORDER BY `timestamp` {$direction} LIMIT {$queryResult->request->take}  OFFSET {$queryResult->request->skip}
EOT;

//echo($sql);

		if ($result = $mysqli->query($sql)) { 
			while($row = mysqli_fetch_array($result)) {
                                if($queryResult->request->type == 'logip')
                                {
                                    $queryResult->data[] = [ 'id' => floatval($row['id']), 'lat' => floatval ($row['lat']), 'lng' => floatval($row['lng']) ];
                                }
                                else
			        if($queryResult->request->type == 'product')
                                {
				    $queryResult->data[] = [ 'id' => floatval($row['id']), 'title' => $row['title'], 'description' => $row['description'] ];
                                }
                                else
			        if($queryResult->request->type == 'image')
                                {
				    $queryResult->data[] = [ 'id' => floatval($row['id']), 'fb' => $row['fb'], 'small' => $row['small'], 'url' => $row['url'] ];
                                }
			        else
			        if($queryResult->request->type == 'user')
                                {
				    $queryResult->data[] = [ 'id' => floatval($row['id']), 'username' => $row['username'], 'password' => $row['password'] ];
                                }
			}
			$result->close();
		}
		$mysqli->close();
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }
	
	function GetAllPosts(&$queryResult) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$direction = $queryResult->request->type == 'message' ? 'ASC':'DESC';
		$sql=
<<<EOT
		SELECT *, `user`.`id` AS `user_id`, `user`.`fb` AS `user_fb`, `user`.`image` AS `uimage` FROM `user` JOIN `{$queryResult->request->type}` WHERE `user`.`id`=`{$queryResult->request->type}`.`user_id` {$queryResult->request->whereCondition} ORDER BY `{$queryResult->request->type}`.`id` {$direction} LIMIT {$queryResult->request->take}  OFFSET {$queryResult->request->skip} 
EOT;
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {

                                if($queryResult->request->type == 'product')
                                {
				     $queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['user_fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'verified' => $row['verified'],
					'price' => $row['price'],
					'category_id' => $row['category_id']];
                                }
                                else
			        if($queryResult->request->type == 'image')
                                {
				     $queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'url' => $row['url'],
					'small' => $row['small']];
                                }else
			        if($queryResult->request->type == 'message')
                                {
				     $queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['user_fb'],
					'uimage' => $row['uimage'],
					'content' => $row['content'], 
					'tag' => $row['tag'],
					'timestamp' => $row['timestamp'],
					'up' => $row['up'],
					'down' => $row['down'],
					'flags' => $row['flags'],
					'avatar' => $row['avatar'],
					'status' => $row['status'],
					'hidden' => $row['hidden'],
					'ip' => $row['ip']];
                                }
			}
			$result->close();
		}
		$mysqli->close();
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }
	
	function GetMyPosts(&$queryResult) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql=
<<<EOT
		SELECT *, `user`.`id` AS `user_id`, `user`.`fb` AS `user_fb`, `user`.`image` AS `uimage` FROM `user` JOIN `{$queryResult->request->type}` WHERE `user`.`id`=`{$queryResult->request->type}`.`user_id` AND `user`.`id`= '{$_SESSION['user_id']}' ORDER BY `{$queryResult->request->type}`.`id` DESC LIMIT {$queryResult->request->take}  OFFSET {$queryResult->request->skip} 
EOT;
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {

                                if($queryResult->request->type == 'product')
                                {
				     $queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['user_fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'verified' => $row['verified'],
					'price' => $row['price'],
					'category_id' => $row['category_id']];
                                }
                                else
			        if($queryResult->request->type == 'image')
                                {
				     $queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'url' => $row['url'],
					'small' => $row['small'],
 					'status' => $row['status']];
                                }
			}
			$result->close();
		}
		$mysqli->close();
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }
	
	function GetAllImages(&$queryResult) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql=
<<<EOT
		SELECT *, `user`.`id` AS `user_id`, `user`.`image` AS `uimage` FROM `user` JOIN `image` WHERE `user`.`id`=`product`.`user_id` ORDER BY `product`.`id` DESC LIMIT {$queryResult->request->take}  OFFSET {$queryResult->request->skip} 
EOT;
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {

                                if($queryResult->request->type == 'post')
                                {
				$queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'verified' => $row['verified'],
					'price' => $row['price'],
					'category_id' => $row['category_id']];}
                                else
			        if($queryResult->request->type == 'image')
                                {
				$queryResult->data[] = [ 
					'user_id' => $row['user_id'], 
					'id' => $row['id'], 
					'title' => $row['title'], 
					'description' => $row['description'],
					'first_name' => $row['first_name'],
					'last_name' => $row['last_name'],
					'location' => $row['location'],
					'url' => $row['url'],
					'fb' => $row['fb'],
					'uimage' => $row['uimage'],
					'timestamp' => $row['timestamp'],
					'url' => $row['url'],
					'status' => $row['status'],
					'small' => $row['small']];}

				
			}
			$result->close();
		}
		$mysqli->close();
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }
	
	function ReisterNewUser($email, $password) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql=
<<<EOT
		INSERT INTO `user` (`username`, `email`, `password`) VALUES ('{$email}', '{$email}', '{$password}')
EOT;

		if ($result = $mysqli->query($sql)) {
                }
        }
	
	function GetUserPosts(&$queryResult) {

		$mysqli = $this->GetConnection();

		$pID = 0;
		$sql=
<<<EOT
		SELECT * FROM {$queryResult->request->type} LIMIT {$queryResult->request->take}  OFFSET {$queryResult->request->skip} ORDER BY `id` DESC
EOT;
		if ($result = $mysqli->query($sql)) {
			while($row = mysqli_fetch_array($result)) {
				$queryResult->data[] = [ 'id' => $row['id'], 'price' => $row['price'], 'image' => $row['uimage'], 'url' => $row['uurl'], 'first_name' => $row['first_name'], 'last_name' => $row['last_name'], 'title' => $row['title'], 'description' => $row['description'] ];
			}
			$result->close();
		}
		$mysqli->close();
        $queryResult->query = str_replace("\t", " ", str_replace("\n", " ", $sql));
    }

}

if(isset($_GET['read'])&& $_GET['read']=='logip')
{
    $request = new request(substr($_GET['read'], 3));
    $request->take = $_GET['take'];
    $request->distinct = isset($_GET['distinct']);
    $request->skip = $_GET['skip'];
    $queryResult = new QueryResult($request);
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetLogip($queryResult);
    echo json_encode($queryResult, JSON_PRETTY_PRINT);
}else
if(isset($_GET['read'])&& substr($_GET['read'], 0, 3)=='all')
{
    $request = new request(substr($_GET['read'], 3));
    $request->take = $_GET['take'];
    $request->distinct = isset($_GET['distinct']);
    $request->whereCondition = isset($_GET['whereCondition'])? ' AND ' . $_GET['whereCondition'] : "";
    $request->skip = $_GET['skip'];
    $queryResult = new QueryResult($request);
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetAllPosts($queryResult);
    echo json_encode($queryResult, JSON_PRETTY_PRINT);
}else
if(isset($_GET['read'])&& substr($_GET['read'], 0, 2)=='my')
{
    $request = new request(substr($_GET['read'], 2, $_GET['read'].length-1));
    $request->take = $_GET['take'];
    $request->distinct = isset($_GET['distinct']);
    $request->skip = $_GET['skip'];
    $queryResult = new QueryResult($request);
    $dataLayer = new DataLayer($queryResult);
    $dataLayer->GetMyPosts($queryResult);
    echo json_encode($queryResult, JSON_PRETTY_PRINT);
}else
if(isset($_GET['read']))
{	
		$request = new request($_GET['read']);
		$request->distinct = isset($_GET['distinct']);
		$request->take = $_GET['take'];
		$request->skip = $_GET['skip'];
		$queryResult = new QueryResult($request);
		$dataLayer = new DataLayer($queryResult);
		$dataLayer->GetPosts($queryResult);
		echo json_encode($queryResult, JSON_PRETTY_PRINT);
}
else
if(isset($received_json['user_action']))
{
	if('user_action_login' == $received_json['user_action'] && isset($received_json['email']) && isset($received_json['password']))
	{	
		$request = new request('user');
		$request->take = 100;
		$request->skip = 0;
		$queryResult = new QueryResult($request);
		$request->whereCondition = " WHERE `password` = '".$received_json['password']."' ";
		$dataLayer = new DataLayer($queryResult);
		$dataLayer->GetPosts($queryResult);
		if(count($queryResult) > 0)
		{
			if($received_json['password'] == $queryResult->data[0]['password'])
			{
				$queryResult->message = 'Welcome '.$queryResult->data[0]['username'];
				$queryResult->success = true;
			}
			else
			{
				$queryResult->message = 'Wrong username or password!';
				$queryResult->success = false;
			}
		}
		else
		{
			$dataLayer->ReisterNewUser($received_json['email'], $received_json['password']);
			$queryResult->success = true;
		}
		echo json_encode($queryResult, JSON_PRETTY_PRINT);
	}
	else if('user_action_register' == $received_json['user_action'] && isset($received_json['email']) && isset($received_json['password']))
	{	
		$request = new request('user');
		$request->take = 100;
		$request->skip = 0;
		$queryResult = new QueryResult($request);
		$request->whereCondition = " WHERE `password` = '".$received_json['password']."' ";
		$dataLayer = new DataLayer($queryResult);
		$dataLayer->GetPosts($queryResult);
		if(count($queryResult) > 0)
		{
			if($received_json['password'] == $queryResult->data[0]['password'])
			{
				$queryResult->message = 'You are registred. Please Login!';
				$queryResult->success = false; // Can login but avoid automatic login
			}
			else
			{
				$queryResult->message = 'Username not available!';
				$queryResult->success = false;
			}
		}
		else
		{
			$dataLayer->ReisterNewUser($received_json['email'], $received_json['password']);
			$queryResult->success = true;
		}
		$dataLayer->GetPosts($queryResult);
		// Anonimize password
		for($i = 0; $i<count($queryResult->data); $i++)
		{
			$queryResult->data[$i]['password'] = null;
		}
		echo json_encode($queryResult, JSON_PRETTY_PRINT);
	}
}
else
{
  echo 
<<<EOT
<pre>
<h3 style='color: blue'>A generic API that include debug information for romanian economic agents and individuals</h3>
<b>Database description:</b>
Users have zero to many products.

</pre>
EOT;
    echo "<ol>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/api/index.php?read=product&take=10&skip=0'>Products</a> Last 10 products</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/api/index.php?read=image&take=10&skip=0'>Images</a> Last 10 images</li>";
    echo "    <li>Query  <a href='http://".$_SERVER['SERVER_NAME']."/api/index.php?read=logip&take=10&skip=0'>Locations</a> Last 10 locations</li>";
    echo "</ol>";
}


//echo '{ "GET": '.json_encode($_GET).',  "POST": '.json_encode($_POST).'}';
?>
