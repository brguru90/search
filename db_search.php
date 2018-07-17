<?php
session_start();
include('db.php');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error)
{
    die("Connection failed: ");
}
$sql = "create table search
(
id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
query VARCHAR(100) NOT NULL,
url VARCHAR(200) NOT NULL,
page VARCHAR(200) NOT NULL,
match_checksum varchar(100) not null unique,
priority int(10) not null default 0,
match_details mediumtext NOT NULL
);";
$conn->query($sql);
if(isset($_GET["query"]))
{
	$_SESSION["q"]=$_GET["query"];
}
else if(isset($_GET["q"]))
{
	$_SESSION["q"]=$_GET["q"];
}
else{
	unset($_SESSION["q"]);
}
if(isset($_SESSION["q"]) && strlen($_SESSION["q"])>0)
{
	$search=$_SESSION["q"];
	$words=explode(' ',$search);
	$match_count=0;
	echo "<i style='color:orange'>Result for searching:</i> <b style='color:DeepPink'>$search</b><hr style='color:Gainsboro' />";
	$str="";
	foreach($words as $q)
	{
		$sql= "SELECT * FROM search where query like '%$q%' order by priority desc;";
		$content = $conn->query($sql);
		if ($content->num_rows > 0) {
			while($row = $content->fetch_assoc()) 
			{
				$url=$row["url"];
				$str.="\n\t\t<tr><th><button class='bt' onclick='window.open(\"frame_php.php?page=".md5($row["url"])."&type=1&q=$search\");' onmouseover='this.style.textDecoration=\"underline\";'  onmouseout='this.style.textDecoration=\"none\";'>$url&nbsp&nbsp</button></th></tr>\n";
				$_SESSION[md5($url)]=$row["page"];
				$_SESSION[md5(md5($url))]=$url;
				$_SESSION["match_checksum"]=$row["match_checksum"];
				$match_count++;
			}
		}
	}
	echo "<h4 style='color:Maroon' id='timeout'>Nor of Match: $match_count&nbsp&nbsp</h4>\n\t<table>$str\n\t</table><br /><br />";
}
?>