<?php
/**
 * Show statistics of all IDs found in trace, their intervall and count
 */
require("libcanlog.php");

if ($argc <= 1) {
	print("Usage: ".basename(__FILE__)." FILE\n");
	exit(1);
}
$f = $argv[1];

$sortby = "-id"; # by ID
#$sortby = "-cnt"; # by count
#$sortby = "-int"; # by intervall
$sortord = "-asc"; # ascending, lowest first
#$sortord = "-desc"; # descending, highest first

$log = new CANLOG();
$ids = array();
if ( ! $log->open($f)) errorexit($log->lasterr());
while ($msg = $log->next_msg())
{
#print_r($msg);exit(0);
	$id = $msg['id'];
	$hash = implode("", $msg['data']);

	if ( ! isset($ids[$id])) {
		$ids[$id] = array(
			'count' => 1,
			'events' => array($msg['rel_ts']),
			'periods' => array(),
			'datas' => array($hash => $msg['data']),
		);
	}
	else {
		$last_rel_ts = end($ids[$id]['events']);
		$ids[$id]['count'] += 1;
		$ids[$id]['periods'][] = $msg['rel_ts'] - $last_rel_ts;
		$ids[$id]['events'][] = $msg['rel_ts'];
		if ( ! isset($ids[$id]['datas'][$hash])) {
			$ids[$id]['datas'][$hash] = $msg['data'];
		}
	}
}
#print_r($ids); exit(0);

#$l = $log->lines();
print round($log->length() / 1000)." seconds recorded\r\n";
#$last = end($ids);
#print ($last['events'$ts/1000)." ms\n";
print count($ids)." IDs found\r\n";
print "\r\n";


// sort
if ($sortby == "-id") {
	if ($sortord == "-asc") {
		ksort($ids);
	}
	elseif ($sortord == "-desc") {
		krsort($ids);
	}
}
elseif ($sortby == "-cnt") {
	if ($sortord == "-asc") {
		uasort($ids, function($a, $b) {
			if ($a['count'] == $b['count']) return 0;
			return ($a['count'] < $b['count']) ? -1 : 1;
		});
	}
	elseif ($sortord == "-desc") {
		uasort($ids, function($a, $b) {
			if ($a['count'] == $b['count']) return 0;
			return ($a['count'] > $b['count']) ? -1 : 1;
		});
	}
}
elseif ($sortby == "-int") {
	if ($sortord == "-asc") {
		uasort($ids, function($a, $b) {
			if (max($a['periods']) == max($b['periods'])) return 0;
			return (max($a['periods']) < max($b['periods'])) ? -1 : 1;
		});
	}
	elseif ($sortord == "-desc") {
		uasort($ids, function($a, $b) {
			if (max($a['periods']) == max($b['periods'])) return 0;
			return (max($a['periods']) > max($b['periods'])) ? -1 : 1;
		});
	}
}

// print data
#$max_hyst = 5;
print("ID  COUNT   AVG   MIN   MAX  DCHG\r\n");
print("---------------------------------\r\n");
foreach($ids as $id => $rec) {
	$cnt = $rec['count'];
	$min_ts = min($rec['periods']);
	$max_ts = max($rec['periods']);
	$avg_ts = round(array_sum($rec['periods']) / $cnt);
	#if ($max_ts - $min_ts > $max_hyst) {
	#	$avg_ts = "$avg_ts";
	#}
	$udata_cnt = count($rec['datas']) - 1;
	if ($udata_cnt == 0) $udata_cnt = "-";
	printf("%03X %5d %5s %5d %5d %5s\r\n", $id, $cnt, $avg_ts, $min_ts, $max_ts, $udata_cnt);
}

exit(0);