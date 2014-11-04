<?php
include('lib/functions.php');

$BASE_URL = "http://illinidumps.com";

function censor_string($text) {
	//$patterns = array('/fuck/i', '/cock/i', '/titty/i', '/titties/i', '/tits/i', '/cunt/i', '/whore/i', '/pussy/i');
	//$replacements = array('****', '****', '*****', '*******', '****', '****', '*****', '*****');
	
	$patterns = array();
	$replacements = array();
	
	$patterns[0] = '/fuck/i';
	$replacements[0] = '****';
	
	$patterns[1] = '/cock /i';
	$replacements[1] = '****';
	
	$patterns[2] = '/titty/i';
	$replacements[2] = '*****';
	
	$patterns[3] = '/titties/i';
	$replacements[3] = '*******';
	
	$patterns[4] = '/tits/i';
	$replacements[4] = '****';
	
	$patterns[5] = '/cunt/i';
	$replacements[5] = '****';
	
	$patterns[6] = '/whore/i';
	$replacements[6] = '*****';
	
	$patterns[7] = '/pussy/i';
	$replacements[7] = '*****';
	
	$patterns[8] = '/blowjob/i';
	$replacements[8] = '*******';

	$patterns[9] = '/cocks/i';
	$replacements[9] = '*****';

	$patterns[10] = '/dick/i';
	$replacements[10] = '****';
	
	return preg_replace($patterns, $replacements, $text);
}

function load_bathroom($id) {
    global $BASE_URL;
    $url = $BASE_URL . "/api/locations/" . $id;
    $bathroom = json_decode(curl_get($url));

	$imgsrc = "/images/" . $bathroom->image;
	$name = $bathroom->name;
	echo "<h3>".$bathroom->name."</h3>";
	// echo "<h5>Building #".$bathroom->id."</h5>";
	echo "<h5>".$bathroom->address."</h5>";
	
	// INITIAL SCORE (WHAT EVERYTHING IS RANKED ON)
	echo '<h3><strong><span class="orange">Score: </span><span id="main_score" class="blue"> ';
	if(is_null($bathroom->score)) {
		echo "-";
	} else {
		echo $bathroom->score;
	}
	echo "</span></strong></h3>";

	echo "<span id=\"main_votes\">",$bathroom->votes,"</span> votes counted.<br><hr>";
	
	// ADDITIONAL OPTIONAL SCORING (NO WEIGHT ON RANKING)
	echo "<h4><span id=\"smell_score\" class=\"blue\"> ";
	if(is_null($bathroom->smell_score)) {
		echo "x.x";
	} else {
		echo $bathroom->smell_score;
	}
	echo "</span><span class=\"orange\">   Smell</span></h4>";
	
	echo "<h4><span id=\"crowd_score\" class=\"blue\"> ";
	if(is_null($bathroom->crowd_score)) {
		echo "x.x";
	} else {
		echo $bathroom->crowd_score;
	}
	echo "</span><span class=\"orange\">    Crowdedness</span></h4>";
	
	echo "<h4><span id=\"clean_score\" class=\"blue\"> ";
	if(is_null($bathroom->clean_score)) {
		echo "x.x";
	} else {
		echo $bathroom->clean_score;
	}
	echo "</span><span class=\"orange\">    Cleanliness</span></h4>";
	
	
	
	echo "<img src=\"".$imgsrc."\" alt=\"$name\">\n";
}

function load_votes($id) {
 
    echo '<form id="vote_main">';
	echo "<input type=\"hidden\" name=\"id\" id=\"id\" value='$id'>";
	echo '
	<input type="radio" name="rating" class= "vote" id="rate1" value="1"/> Good (+2)<br>
    <input type="radio" name="rating" class = "vote" id="rate2" value="2"/> Average (+1)<br>
    <input type="radio" name="rating" class="vote" id="rate3" value="3"/> Bad (-2)<br>
	';
    echo '</form>';
	

}

function load_optional_votes($id) {
	echo '<div class="optionalinstructions">On a scale of (1-5), 5 being the best smelling, the least crowded, and the cleanest...</div>
		
		
        <div class="optionalcategory">
		1 <input type="radio" class="optvote" name="smell" id="smell1" value="1">
		2 <input type="radio" class="optvote" name="smell" id="smell2" value="2">
		3 <input type="radio" class="optvote" name="smell" id="smell3" value="3">
		4 <input type="radio" class="optvote" name="smell" id="smell4" value="4">
		5 <input type="radio" class="optvote" name="smell" id="smell5" value="5">
		  Smell
		</div>
		
        <div class="optionalcategory">
		1 <input type="radio" class="optvote" name="crowd" id="crowd1" value="1">
		2 <input type="radio" class="optvote" name="crowd" id="crowd2" value="2">
		3 <input type="radio" class="optvote" name="crowd" id="crowd3" value="3">
		4 <input type="radio" class="optvote" name="crowd" id="crowd4" value="4">
		5 <input type="radio" class="optvote" name="crowd" id="crowd5" value="5">
		  Crowdedness
		</div>
        
        <div class="optionalcategory">
		1 <input type="radio" class="optvote" name="clean" id="clean1" value="1">
		2 <input type="radio" class="optvote" name="clean" id="clean2" value="2">
		3 <input type="radio" class="optvote" name="clean" id="clean3" value="3">
		4 <input type="radio" class="optvote" name="clean" id="clean4" value="4">
		5 <input type="radio" class="optvote" name="clean" id="clean5" value="5">
		  Cleanliness
        </div>
	';
}

function load_comments($id) {
    global $BASE_URL;
    $comments = json_decode(curl_get($BASE_URL . "/api/comments/" . $id));

	echo '
		<input type="hidden" id="commentid" value=',$id,'>
	';

	//while($row = mysql_fetch_assoc($comment_result)) {
    foreach($comments->comments as $comment) {
		if($comment->hidden != 1) {
			$author = censor_string($comment->author);
			$comment_text = censor_string($comment->comment);
            echo '<div class="comment">';
			echo "<div class=\"slug\"><span class=\"blue commentname\">$author</span><span class=\"orange\"> says...</span></div>";
			echo "<div><p class=\"blue commenttext\">$comment_text</p></div>";
            echo '</div>';
		}
	}
}

?>
