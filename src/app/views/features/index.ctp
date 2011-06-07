<h1>Upcoming Features</h1>
<p>We're constantly trying to improve things here at SharpBetTracker to
help us all win more money, and we'd like to work together with you to
do so!</p> 
 
<p>Here on this page, you'll see upcoming improvements that we're
working. You can up and downvote these improvements to tell us what is
most important to you. Upvote things you'd like us to work on first
and downvote things that you don't like or find useful.</p> 
 
<p>Better yet, you can even make comments on each potential improvement
to tell us exactly what you think or to suggest something we you'd
like to see that we didn't think of!</p> 
 
<p>In addition, you can always suggest something you'd like to see by
going to our <?= $html->link('Feedback Page', '/pages/feedback') ?> and telling us
directly.</p> 

<p>Let's <b>work together</b> to beat the books!</p> 
<?php

$html->css('features', 'stylesheet', array('inline' => false));

$loggedIn = !empty($userid);

foreach ($features as $feature) {
	$fid = $feature['Feature']['id'];
	$title = $feature['Feature']['title'];
	$descr = $feature['Feature']['description'];
	
	$userVoted = isset($userVotes[$fid]);
	$dir = $userVoted ? $userVotes[$fid] : null;
	
	$votes = empty($feature['FeatureVote']) ? array('up' => 0, 'down' => 0) : $feature['FeatureVote'];
	echo "<div class='vote'><div class='votes'>".($votes['up'] + $votes['down'])." votes (".dispVotes($votes['up'], $votes['down']).")";
	
	$class1 = $class2 = $disabled = '';
	if ($userVoted) {
		$class1 = $class2 = 'disabled';
		$disabled = 'disabled="disabled"';
		if ($dir == 1) {
			$class1 .= ' selected';
		} else {
			$class2 .= ' selected';
		}
	}
	echo "<form action='".$html->url("/features/vote/$fid/1")."' method='get'><button $disabled class='$class1' type='submit'><img alt='up' src='".$html->url('/img/icons/up22.png')."' /></button></form>";
	echo "<form action='".$html->url("/features/vote/$fid/2")."' method='get'><button $disabled class='$class2' type='submit'><img alt='down' src='".$html->url('/img/icons/down22.png')."' /></button></form>";
	
	echo "</div>";
	
	echo "<div class='voteinfo'><h1>$title</h1>";
	echo "<p>$descr</p>";
	echo "<div>".$html->link('Comment', '/features/info/'.$fid)."</div></div>";
}

function dispVotes($up, $down) {
    $total = $up - $down;
    if ($total == 0) {
        return 0;
    } else if ($total > 0) {
        return "+$total";
    } else {
        return "$total";
    }
}
