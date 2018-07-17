<?php
session_start(); 
date_default_timezone_set("Asia/Kolkata");
	$time1=time();
$page=array();
$ext=array("php","html");
$file_exception=array("/header.php/i","/footer.php/i","/menu.php/i","/db.php/i","/guru.php/i","/guru.html/i","/suggest.php/i","/advance_search.php/i","/frame_php.php/i","/db_search.php/i","/new_search.php/i");
$dir_exception=array("/sdba/i","/sms/i","/Classes/i","/api/i","/css/i","/js/i","/temp/i","/lab/i");
$cwd=getcwd();
read_dir($cwd,$ext);
function read_dir($dir,$extension)
{
	global $page,$file_exception,$dir_exception;
	$allpages=scandir($dir);
	foreach($allpages as $p)
	{
      		if($p=="." || $p=="..")
              		continue;
		$n=1;
		if(!is_dir("$dir/$p"))
		{
			preg_replace($file_exception,"",$p,$n,$m);
			if($m>=1)
				continue;
      			$filename=explode(".",$p);
			if(isset($filename[1]))
			{
				foreach($extension as $ext)
				if(preg_match("/$ext/i",$filename[1]))
				{
					//echo "[File] $dir/$p<br />";
					$page[]="$dir/$p";
				}
			}
		}
		else
		{
			preg_replace($dir_exception,"",$p,$n,$m);
			if($m>=1)
				continue;
			//echo "[Dir]$dir/$p<br />";
			read_dir("$dir/$p",$extension);
		}
		
	}
	return;

}
function reduce($comb)
{
	$temp="";
	foreach($comb as $key=>$val)
	{
		$temp_str=explode(" ",$val);
		$temp[]=implode(" ",array_unique($temp_str));
	}
	$temp=array_unique($temp);
	return $temp;
}
function guru($total,$comb)
{
	//make combination
	global $n,$count,$comb,$c;
	$count++;
	for($c=0;isset($comb[$c]) && strlen($comb[$c])<$count;$c++);
	for($i=0;$i<$total;$i++)
		for($j=$c;isset($comb[$j]) && strlen($comb[$j])==$count;$j++)
			$comb[]=$comb[$i]." ".$comb[$j];
	$comb=reduce($comb);
	if($count/2<=$total)	
		guru($total,$comb);
}
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
if(isset($_GET["res_page"]))
	$res_page=$_GET["res_page"];
else
	$res_page=0;
$results="";
if(isset($_SESSION["q"]) && strlen($_SESSION["q"])>0)
{
	$search=$_SESSION["q"];
	$words=explode(' ',$search);
	$comb=$words;
	$total=count($words);
	$n=$total;
	$count=0;
	$c=0;
	/*if(count($words)<5)
	{
		//takes So mutch time
		guru($total,$comb);
		echo "<br /><hr />count: ".count($comb)."<br />";
		$comb=array_reverse($comb);
	}*/
	echo "<i style='color:orange'>Result for searching:</i> <b style='color:DeepPink'>$search</b><hr style='color:Gainsboro' />";
	$count=0;
	$query=array();
	sort($page);
	$match_count=0;
	$pos=null;
	$match_details=null;
	$results="";
	$match_details_to_file="";
	$max_res=0;
	if(!isset($_SESSION["state1"]))
		$_SESSION["state1"]=0;
	if($res_page>0)
	{
		$from=$_SESSION["state1"];
		$to=count($page);
		$_SESSION["state2"]=$from;
	}
	if($res_page<0)
	{
		$from=$_SESSION["state2"];
		$to=0;
		$_SESSION["state1"]=$from;
	}
	if($res_page==0)
	{
		$from=0;
		$_SESSION["state1"]=0;
		$to=count($page);
		$_SESSION["state2"]=$to-1;
	}
	
	//echo "$res_page->$from-$to<br />";
	//echo "sessione_before->".$_SESSION["state1"]."-".$_SESSION["state2"]."<br />";
	//echo "sessione_after->".(($_SESSION["state1"]*100)/count($page))."-".(($_SESSION["state2"]*100)/count($page))."<br />";
	for($start=$from;;)
	{
		if($res_page>=0)
			if($start>=$to)
				break;
		if($res_page<0)
			if($start<$to)
				break;
		$str=$page[$start];
		//echo $str."<br />";
		$contents[$str]=file_get_contents($str);
		$string=preg_replace("/'/",'"',$contents[$str]);
		for($i=0;$i<strlen($string)-1;$i++)
		{
			//php
			if($string[$i]=='<' && $string[$i+1]=='?')
			{
				$i+=2;
				guru:
				$j=$i;
				//double quoat
				while($j<strlen($string)-1 && !($string[$j]=="\"" && $string[$j-1]!="\\"))
				{ 
					$j++;
					if($string[$j]=='>' && $string[$j-1]=='?')
					{
						$j=-1;
						break;
					}
				}
				if($j!=-1 && $j<strlen($string)-1)
				{
					$i=$j;
					$i++;
					while($i<strlen($string)-1 && !($string[$i]=="\"" && $string[$i-1]!="\\"))
					{
						$i++;
					}
					$i++;
					goto guru;
				}
				while($i<strlen($string)-1 && !($string[$i]=='>' && $string[$i-1]=='?'))
				{
						$i++;				
				}
				
			}
			//script
			else if($string[$i]=='<' && ($string[$i+1]=='s' || $string[$i+1]=='S') && ($string[$i+2]=='c' || $string[$i+2]=='C') && ($string[$i+7]==' ' || $string[$i+7]==">"))
			{
				while(!($string[$i]=='>' && ($string[$i-1]=='t' || $string[$i-1]=='T') && ($string[$i-2]=='p' || $string[$i-2]=='P') && ($string[$i-3]=='i' || $string[$i-3]=='I') && $string[$i-7]=='/' && $string[$i-8]=='<'))
				{
					$i++;
				}
				
			}
			
			//style
			else if($string[$i]=='<' && ($string[$i+1]=='s' || $string[$i+1]=='S') && ($string[$i+2]=='t' || $string[$i+2]=='T') && ($string[$i+3]=='y' || $string[$i+3]=='Y') && ($string[$i+6]==' ' || $string[$i+6]==">"))
			{
				while(!($string[$i]=='>' && ($string[$i-1]=='e' || $string[$i-1]=='E') && ($string[$i-2]=='l' || $string[$i-2]=='L') && ($string[$i-3]=='y' || $string[$i-3]=='Y') && $string[$i-6]=='/'))
				{
					$i++;
				}
				
			}
			//skip tags
			else if($string[$i]=='<')
			{
				$f=1;
				while(!($string[$i]=='>' && $f%2!=0))
				{
					if($string[$i]=="\"")
						$f++;
					$i++;
				}
			}
			
			if(($i)<strlen($string)-1 && $string[$i]!='>')
				$query[$str][$i]=$string[$i];
				//print_r($query);
				//echo htmlentities($string[$i]);
				//echo "<table style='display:inline;'><tr><td>$string[$i]</td></tr><tr><td><small>$i</small></td></tr></table>";
		}
		if(isset($query[$str]))
		{
			$result=add_result($str,$query[$str]);
			if($res_page>=0)
				$results.=$result;
			if($res_page<0)
				$results=$result.$results;
			if($result!="")
				$max_res++;
		}
		if($res_page>=0)
			$start++;
		if($res_page<0)
			$start--;
		if($max_res>=10)
		{
			if($res_page>=0)
			{
				$_SESSION["state1"]=$start;
				if($start>=count($page))
					$_SESSION["state1"]--;
			}
			if($res_page<0)
			{
				$_SESSION["state2"]=$start;
			}
			break;
		}
	} 
		
		//echo "sessione_after->".$_SESSION["state1"]."-".$_SESSION["state2"]."<br />";

		file_put_contents("highlights.txt",$match_details_to_file);
		$time2=time();
		$time=$time2-$time1;
		echo "<h4 style='color:Maroon'>Nor of Match: $match_count&nbsp&nbspDuration:&nbsp&nbsp<b>$time</b></h4>\n\t<table>$results\n\t</table>";
}
else
	echo "<h1>Query Empty</h1>";
		function add_result($path,$page)
		{
			$str="";
			global $comb,$match_count,$match_details,$match_details_to_file,$words;
			//foreach($query as $path=>$page)
			$wd=0;$match="";
			$words_completed=0;
			foreach($comb as $key2=>$val)
			{
				$i=0;
				foreach($page as $key=>$char)
				{
					same_char_again:
					if($i<strlen($val))
					if(!strcasecmp($char,$val[$i]))
					{
						if($i==strlen($val)-1)
						{
							$pos=$key-$i;
							$match_details[][$i]=$pos;
							$i=0;
							$wd++;
							if($match=="")
								$match=$val;
							else if($words_completed<count($words))
								$match.=" ".$val;
							$words_completed++;
							//if($words_completed>=count($words))
								//goto next_page;
						}
						$i++;
					}	
					else if($char!=$val[$i])
					{
						if($i>0)
						{
							$i=0;
							goto same_char_again;
						}
					}
				}
				next_word:
			}
			next_page:
			
			if($wd>0)
			{
				$match_count++;
				//htdocs for xampp,www for wamp and ampps
				$path2=explode("htdocs",$path);
				$host=$_SERVER['HTTP_HOST'];
				$url=str_replace("\\","/","http://$host$path2[1]");
				$_SESSION[md5($url)]=$path;
				$_SESSION[md5(md5($url))]=$url;
				$str.="\n\t\t<tr><th><button class='bt' onclick='window.open(\"frame_php.php?page=".md5($url)."&type=1&q=$match\");' onmouseover='this.style.textDecoration=\"underline\";'  onmouseout='this.style.textDecoration=\"none\";'>$url&nbsp&nbsp</button></th></tr>\n";
				$nor_of_res=0;
				foreach($match_details as $sentence=>$len_record)
					foreach($len_record as $length=>$position)
						$match_details_to_file.=md5(md5($url))."=>$sentence=>$length=>$position\n";
				shuffle($match_details);
				foreach($match_details as $sentence=>$len_record)
				{
					foreach($len_record as $length=>$position)
					{
						if($nor_of_res++>=4)
							break;
						$str.="<tr><td>";
						for($z=$position;$z>$position-40 && isset($page[$z]);$z--);
						$start=0;
						for(++$z;$z<$position+100 && isset($page[$z]);$z++)
						{
							if($page[$z]==" " || !isset($page[$z-1]))
								$start=1;
							if($start)
							{
								if($z==$position)
									$str.="<b class='highlight'>";
								$str.="$page[$z]";
								if($z==($position+$length))
									$str.="</b>";
								
								
							}
						}
						$str.="</td></tr>";
					}
				}
				if($nor_of_res==4)
					$str.="<tr><td>.....&nbsp</td></tr>";
				else
					$str.="<tr><td>&nbsp</td></tr>";
				$match_details=null;
			}
			return $str;
		}
?>
