<?php 
session_start();
include('db.php');
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error)
{
    die("Connection failed: " . $conn->connect_error);
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
if(isset($_GET["page"]) && isset($_GET["q"]) && isset($_GET["type"]) && strlen($_GET["q"])>0)
{
	$query=$_GET["q"];
	$q=explode(" ",$query);
	$q=array_unique($q);
	$query2=implode(" ",$q);
	$page=str_replace("\\","/",$_SESSION[$_GET["page"]]);
	$url=$_SESSION[md5($_GET["page"])];
	$url=str_replace("\\","/",$_SESSION[md5($_GET["page"])]);
	$url2=$url;
	$cur=explode("/",$page);
	array_pop($cur);
	$cwd=implode("/",$cur);
	$cur=$_SESSION[md5($_GET["page"])];
	$cur=explode("/",$cur);
	$file_ext=explode(".",array_pop($cur));
	$url=implode("/",$cur);
	if($_GET["type"]!=0)
		$match_details_to_file=file_get_contents("highlights.txt");
	else
	{
		$sql= "SELECT * FROM search where match_checksum='".$_SESSION['match_checksum']."';";
		$content = $conn->query($sql);
		if ($content->num_rows > 0) {
			while($row = $content->fetch_assoc()) 
			{
				$match_details_to_file=$row["match_details"];
			}
		}
	}
	$match_details=explode("\n",$match_details_to_file);
	$match_details2=null;
	foreach($match_details as $pages)
		$match_details2[]=explode("=>",$pages);
	$match_details_for_db="";
	for($i=0;$i<count($match_details2)-1;$i++)
		{
			if($match_details2[$i][0]==md5($_GET["page"]))
			{
				$match_details_for_db.=implode("=>",$match_details2[$i])."\n";
				$position[$match_details2[$i][3]]=$match_details2[$i][2];
				$match_details2[$i][2]."=>".$match_details2[$i][3]."<br />";
			}
		}	
	$sql= "insert into search (query,url,page,match_checksum,match_details) values('$query2','$url2','$page','".md5($match_details_for_db)."','$match_details_for_db');";
	$conn->query($sql);
	$conn->query("update search set priority=priority+1 where match_checksum='".md5($match_details_for_db)."';");
	krsort($position);
	$htm=file_get_contents($page);
?>
<html>
<head><title>Result</title></head>
<style>
body{height:100%;margin:0px 0px 0px 0px;}
#top{background-color:#F0F0F0;height:2.9%;margin:0px 0px 0px 0px;}
#bottom{height:97%;margin:0px 0px 0px 0px;}
.url{color:black;text-decoration:none;padding:2px 5px 2px 5px;background-color:#C0C0C0;margin:0px 0px 0px 4px;border-radius:2px;}
iframe{padding:0px 0px 0px 0px;margin:0px 0px 0px 0px;}
.l{float:left;}
.r{float:right;margin-right:1%;}
</style>
<body>
	<div id="top">
		<center>
			<b class='l'>
				<a class='url' href="<?php echo "$url2"; ?>" onmouseover="this.style.boxShadow='0 4px 8px 0 #101010, 0 6px 20px 0 #606060';this.style.textShadow='0px 0px 15px #FFFFFF';" onmouseout="this.style.boxShadow='none';this.style.textShadow='none';">Visit actual page</a>
			</b>
			<?php echo "$url2"; ?>
			<b class='r'>
				Result for query: <?php if(isset($query)) echo $query; ?>
			</b>
		</center>
	</div>
	<div id="bottom">
		<?php
			foreach($position as $pos=>$len)
			{
				if(isset($prev) && $pos+$len+1>$prev)
					continue;
					//echo"$pos->".($len+1)."=".($pos+$len+1)."<br />";
					$highlight=substr($htm,$pos,$len+1);
					foreach($q as $rep)
					{
						if(!strcasecmp($highlight,$rep))
						{
							$word="<font style='background-color:yellow;color:blue;'>$highlight</font>";
							break;
						}
						else
							$word=$highlight;
					}
					$htm=substr_replace($htm,$word,$pos,$len+1)."<br />";
				$prev=$pos;
			}
			if($file_ext[1]=="txt")
				$file_ext[1]="html";
			file_put_contents("$cwd\\guru.$file_ext[1]",$htm);
			echo "<iframe src='$url/guru.$file_ext[1]' width='100%' height='100%' style='border:none'></iframe>";
			//delete("$cwd\\guru.$file_ext[1]");
			//unlink("$cwd\\guru.$file_ext[1]");
		}
		else 
			echo "<script>alert('Query empty');history.go(-1);</script>";
		?>
	</div>
</body>
</html>