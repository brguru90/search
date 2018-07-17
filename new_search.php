<?php session_start(); 
$current_page=$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
?>
<html>
<head><title>Search</title></head>
<style>
input{height:34px;font-size:18px;padding-bottom:4px;border-radius:5px;}
input[type='text']{width:500px;padding-left:5px;}
#submit{width:100px;text-align:center;border:outset 2px silver;font-size:15px;color:#585858;background-color:#F0F0F0 ;}
table *{text-align:left;font-size:12px;}
.highlight{background-color:yellow;}
.bt{font-size:12.8px;color:blue;border:none;background:none;padding:0px 0px 0px 0px;}
.frm tr,.frm td{border:none;}
#search0,#search1,#search2{visibility:hidden;}
</style>
<script>
	var s=0;
	var trigger;
	function timeout()
	{
		clearInterval(trigger);
		trigger=setInterval(function(){s++;document.getElementById("timeout").innerHTML="<b>Duration:</b> "+s;}, 1000);
	}
	function suggest(q)
	{
		var xmlhttp=new XMLHttpRequest()
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState ==4 && xmlhttp.status == 200) {
					document.getElementById("hints").innerHTML=this.responseText;
				}
		}
		xmlhttp.open("GET", "suggest.php?q="+q, true);
		xmlhttp.send();
	}
</script>
<body style='padding-left:40px;'>
<center><h4><?php echo $current_page; ?></h4></center>
	<form onsubmit="return false;">
		<table class="frm">
			<tr>
				<td><input list="hints" type="text" id="q" value="<?php if(isset($_GET["q"])) echo $_GET["q"]; ?>" onkeyup="suggest(this.value);" /></td>
				<td><input onclick="search();" onmouseover="down(this)" onmouseout="up(this)" type="submit" id='submit' value="search" /></td>
				<td><input onclick="prev();timeout();more();" onmouseover="down(this)" onmouseout="up(this)" type="button" id='search2' value="&lt&lt" /></td>
				<td><input onclick="init();timeout();more();" onmouseover="down(this)" onmouseout="up(this)" type="button" id='search1' value="Reset" /></td>
				<td><input onclick="next();timeout();more();" onmouseover="down(this)" onmouseout="up(this)" type="button" id='search0' value="&gt&gt" /></td>
			</tr>
			<tr>
				<td rowspan='2'><datalist id='hints'></datalist></td>
			</tr>
		</table>
	</form>
<div id='result'>
<?php
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
	echo "<h4 style='color:Maroon' id='timeout'>Nor of Match: $match_count&nbsp&nbsp</h4>\n\t<table>$str\n\t</table><br /><input type='submit' onmousedown='down(this)' onmouseup='up(this)' style='width:150px' value='Content search' onclick='timeout();more();' /><br />";
}
?>
</div>
<script>
var count=0;
function init(){count=0;}
function next(){count=1;}
function prev(){count=-1;}
	function down(e){
		e.style.borderTop='inset 3px silver';
		e.style.borderLeft='inset 3px silver';
		e.style.borderBottom='outset 1px white';
		e.style.borderRight='outset 1px white';
		e.style.fontSize='14px';
	}
	function up(e){
		//e.style.borderBottom='inset 1px white';
		//e.style.borderRight='inset 1px white';
		e.style.borderTop='outset 1px silver';
		e.style.borderLeft='outset 1px silver';
		e.style.borderBottom='outset 2px silver';
		e.style.borderRight='outset 2px silver';
		e.style.fontSize='15px';
	}
	function search()
	{
		var xmlhttp=new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState ==4 && xmlhttp.status == 200) {
					document.getElementById("result").innerHTML=this.responseText+"<input type='submit' onmousedown='down(this)' onmouseup='up(this)' style='width:150px' value='Content search' onclick='timeout();more();' />";
				}
		}
		xmlhttp.open("GET", "db_search.php?q="+document.getElementById('q').value, true);
		xmlhttp.send();
	}
	function more()
	{
		document.getElementById("search0").style.visibility="visible";
		document.getElementById("search1").style.visibility="visible";
		document.getElementById("search2").style.visibility="visible";
		var xmlhttp=new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
			if (xmlhttp.readyState ==4 && xmlhttp.status == 200) {
					clearInterval(trigger);
					s=0;
					document.getElementById("result").innerHTML=this.responseText ;
				}
		}
		xmlhttp.open("GET", "advance_search.php?q="+document.getElementById('q').value+"&res_page="+count, true);
		xmlhttp.send();
	}
</script>
</body>
</html>