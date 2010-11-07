<?php
$score = $score['Score'];
?>
<table>
<tr><td colspan='3'><?= $score['game_date'] ?></td></tr>
<tr><td>&nbsp;</td><td>1 Half</td><td>Total</td></tr>
<tr><td><?= $score['home'] ?></td><td><?= $score['home_score_half'] ?></td><td><?= $score['home_score_total'] ?></td></tr>
<tr><td><?= $score['visitor'] ?></td><td><?= $score['visitor_score_half'] ?></td><td><?= $score['visitor_score_total'] ?></td></tr>
</table>
