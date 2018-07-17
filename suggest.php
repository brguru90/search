<?php
if(isset($_GET["q"]) && strlen($_GET["q"])>0)
{
	include('db.php');
	$conn = new mysqli($servername, $username, $password, $dbname);
	$search=$_GET["q"];
	$opt=array();
	$sql= "SELECT * FROM search where query like '%$search%' order by priority desc;";
	$content = $conn->query($sql);
	if ($content->num_rows > 0) {
		while($row = $content->fetch_assoc()) 
		{
			$opt[]="<option value='".$row['query']."'>\n";
		}
		$opt=array_unique($opt);
		echo implode("\n",$opt);
	}
	$conn->close();
}	
?>