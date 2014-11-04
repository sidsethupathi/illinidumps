<?php
include("library.php");
if(isset($_GET['id']) && !isset($_GET['sort'])) {
    $bathroom = json_decode(curl_get($BASE_URL . "/api/locations/" . $_GET['id']));
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $BASE_URL . "/locations/" . $bathroom->slug);
} elseif(isset($_GET['id']) && isset($_GET['sort'])) {
    $bathroom = json_decode(curl_get($BASE_URL . "/api/locations/" . $_GET['id']));
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $BASE_URL . "/locations/" . $bathroom->slug . "/" . $_GET['sort']);
} elseif(isset($_GET['sort'])) {
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: " . $BASE_URL . "/sort/" . $_GET['sort']);
}
?>
<!DOCTYPE html>

<?php
include("connect.php");

if(isset($_GET['lid'])) {
$bathroom = json_decode(curl_get($BASE_URL . "/api/locations/" . $_GET['lid']));
$location_id = $bathroom->id;
}


$bathrooms = json_decode(curl_get($BASE_URL . "/api/locations"));
$rand_top = sizeof($bathrooms->bathrooms);
$rand_location_id = rand(1,$rand_top);
$rand_location_slug = json_decode(curl_get($BASE_URL . "/api/locations/" . $rand_location_id))->slug;

// Defines SQL query based on GET variable for category
if(!isset($_GET['category']) && !isset($_GET['search'])) {
	$sql = "SELECT * FROM locations ORDER BY name asc";
	$tag = '';
} elseif(isset($_GET['category'])) {
	$category = $_GET['category'];
	if($category == 'best') {
		$sql = "SELECT * FROM locations WHERE score>0 ORDER BY score DESC LIMIT 0,10";
		$tag = "Best";
	}
	if($category == 'worst') {
		$sql = "SELECT * FROM locations WHERE score != 'NULL' ORDER BY score ASC LIMIT 0,10";
		$tag = "Worst";
	}
	if($category == 'popular') {
		$sql = "SELECT * FROM locations WHERE votes>0 ORDER BY votes DESC LIMIT 0,10";
		$tag = "Popular";
	}
}
?>

<html>
<head>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

	<title>IlliniDumps - Where to go when you've got to go.</title>
	<link rel=stylesheet type="text/css" href="/styles.css">
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.3.1/jquery.cookie.min.js"></script>
	<script type="text/javascript" src="/staticjs.js"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=true"></script>

</head>

<body onload="initialize()">
<div id="contentholder">
	<header>

    <div id="header-left">
	<h1><a id="title" href="/"><span class="orange">IlliniDumps</span></a></h1>
	<div id="tagline"><em>Where to go when you've got to go.</em></div>
	</div>

    <div id="header-right">
    <a class="social_media_icon" href="http://www.twitter.com/IlliniDumps" target="_blank"><img src="/images/twitter_32.png" /></a>
    <a class="social_media_text" href="http://www.twitter.com/IlliniDumps" target="_blank"><span class="orange">Follow us on Twitter: @IlliniDumps</span></a>

    <a class="social_media_icon" href="https://www.facebook.com/pages/IlliniDumps/189290074511151" target="_blank"><img src="/images/facebook_32.png" /></a>
    <a class="social_media_text" href="https://www.facebook.com/pages/IlliniDumps/189290074511151" target="_blank"><span class="orange">Like us on Facebook: IlliniDumps</span></a>
	
    <a class="social_media_icon" href="mailto:illinidumps@gmail.com"><img src="/images/email_32.png" /></a>
    <a class="social_media_text" href="mailto:illinidumps@gmail.com"><span class="orange"><em>Missing a location? Email us at illinidumps@gmail.com</em></span></a>
	<div>
    
	</header>

	<br>
	<span id="options">
	<a href="/sort/best"><span class="blue">Best</span></a> | <a href="/sort/worst"><span class="orange">Worst</span></a> | <a href="/sort/popular"><span class="blue">Popular</span></a> | <a href="#" id="find_close"><span class="orange">Nearby</span></a>

	</span>	
	

	<div id="content">	
		<div id="left">
		<br>
        <div id="searchbox"><a href="#"><img id="show_search" src="/images/search_icon.png" width="16" height="16"></a><input id="search" autocomplete="off" placeholder="Search locations"></div>
		<h3><?php echo $tag, " Locations";?></h3>
		<div id="locations">
					<?php
						$result = mysql_query($sql);
						echo "<ol id=\"ol_nav\" class=\"nav\">";
						while($row = mysql_fetch_assoc($result)) {
						if($row['hidden'] == 0) {
						echo "\n\t\t\t<li>";
							if(!isset($_GET['category'])) {
								echo "<a href=\"/locations/",$row['slug'],"\" id=\"",$row['id'],"\">",$row['name'],"</a>";
							}
							if(isset($_GET['category'])) {
								$category = $_GET['category'];
								echo "<a href=\"/locations/".$row['slug']."/$category\" alt=\"".$row['name']."\" id=\"".$row['id']."\">".$row['name'];
								if($category == 'popular') {
									echo " (".$row['votes']." votes)</a>"; // I think this makes the list
								}
									echo "</a>";
							}
						}
                        echo "</li>";
					}
					?>
		</ol>

					<?php
						$result = mysql_query($sql);
						echo "<select id\"select_nav\" class=\"nav\"><option value=\"#\">Pick a building...</option>";
						while($row = mysql_fetch_assoc($result)) {
                            if($row['hidden'] == 0) {
                                echo "<option value=\"",$row['slug'],"\">", $row['name'], "</option>";
                            }
                        }
                        echo "</select>";
					?>
		</div>
		</div>
		
		<div id="middle">
			<br>
			<?php
			if(!isset($_GET['lid'])) {
			echo '
			<span id="preresults">
            <span class="blue center">Search. </span><br><span class="orange center">Rate. </span><br><span class="blue center">Dump.</span>
			</span> ';
			}
			?>
			<?php
			if(isset($_GET['lid'])) {
			echo '
			<div id="result">';
			load_bathroom($location_id);
			echo '
			</div>
			<div id="vote">';
			load_votes($location_id);
			echo '
			<input type="submit" class="vote button" id="submit" value="submit rating">
			</div>
			
			<div id="optionalvote">';
			load_optional_votes($location_id);
			echo '
			<input type="submit" class="optvote button" id="submitoptional" value="submit ratings">
			</div>
			
			

			<div id="comments">
			
            <div id="commentsform">
			<h3>Comments</h3>
				Name <br><input type="text" id="commentname" name="name"><br>
				Comment <em>(please refrain from excessive vulgarities)</em><br><textarea name="comment" id="commentbody" rows="4" ></textarea>
				<input type="submit" id="submitcomment" class="button" value="submit comment">
            </div>

			<a name="comments"></a>';
			load_comments($location_id);
			echo '
			</div>
			
		</div>
		
		<div id="right">
			
			
			<div id="mapholder">
				<div id="map_canvas"></div>
			</div>
			
			<div id="ads">
            <script type="text/javascript"><!--
            google_ad_client = "ca-pub-9058116659047247";
            /* IlliniDumps */
            google_ad_slot = "2442954654";
            google_ad_width = 300;
            google_ad_height = 250;
            //-->
            </script>
            <script type="text/javascript"
            src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
            </script>
			</div>
			
					
			
			
			
			
		</div>

	
	</div>
';
}
?>
</div>
<!-- Piwik -->
<script type="text/javascript">
var pkBaseURL = (("https:" == document.location.protocol) ? "https://illinidumps.com/piwik/" : "http://illinidumps.com/piwik/");
document.write(unescape("%3Cscript src='" + pkBaseURL + "piwik.js' type='text/javascript'%3E%3C/script%3E"));
</script><script type="text/javascript">
try {
var piwikTracker = Piwik.getTracker(pkBaseURL + "piwik.php", 1);
piwikTracker.trackPageView();
piwikTracker.enableLinkTracking();
} catch( err ) {}
</script><noscript><p><img src="http://illinidumps.com/piwik/piwik.php?idsite=1" style="border:0" alt="" /></p></noscript>
<!-- End Piwik Tracking Code -->

</body>

</html>
