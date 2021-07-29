<?
function left_margin_add(&$item, $key, $val) {
	if($item['LEFT_MARGIN'] >= $val)
    $item['LEFT_MARGIN'] ++;
}

function menu_margin_sort($a, $b)  {
	if ($a['LEFT_MARGIN'] == $b['LEFT_MARGIN']) return 0;
	return ($a['LEFT_MARGIN'] < $b['LEFT_MARGIN']) ? -1 : 1;
}
?>