<?php
/**
 * 
 */
require_once("libcanlog.php");

if ($argc <= 2) {
	print("Usage: ".basename(__FILE__)." FILE CAN-ID\n");
	exit(1);
}
$f = $argv[1];
$id = strtoupper($argv[2]);
$filter = array(
	'id' => hexdec($id),  # include-list '(433|502)' or exclude list '(?!433|503)'
);

$log = new CANLOG();
if ( ! $log->open($f)) errorexit($log->lasterr());
$val = array(0=>array(), 1=>array(), 2=>array(), 3=>array(), 4=>array(), 5=>array(), 6=>array(), 7=>array());
while ($msg = $log->next_msg($filter))
{
	for ($i=0; $i<=7; $i++) {
		if ( ! in_array($msg['data'][$i], $val[$i])) {
			$val[$i][] = $msg['data'][$i];
		}
	}
		
	#printf("%03X %02X %02X %02X %02X %02X %02X %02X %02X\n", $msg['id'], $msg['data'][0], $msg['data'][1], $msg['data'][2], $msg['data'][3], $msg['data'][4], $msg['data'][5], $msg['data'][6], $msg['data'][7]);
	#print_r($msg);
}
#exit(0);
if ($msg === false) {
	print "ERROR: ";
	errorexit($log->lasterr());
}

$show_bytes = array();
for ($i=0; $i<=7; $i++) {
	if (count($val[$i]) < 2) continue;
	$show_bytes[] = $i;
}
if (empty($show_bytes)) {
	print "No bytechanges for ID ".$id."\n";
}

$log->rewind();
while ($msg = $log->next_msg($filter))
{
	printf("%03X", $msg['id']);
	for ($i=0; $i<=7; $i++) {
		if (in_array($i, $show_bytes)) {
			printf(" %02X", $msg['data'][$i]);
		} else {
			printf(" --");
		}
	}
	print "\n";
}


print "Done\n";
exit(0);

/*

if ( ! $log->has_id($id)) errorexit("CAN-ID $id not found in logfile $f");
print $log->count_ids()." messages in logfile $f\n";
$rec = $log->dump_id($id);
$data = $rec['data'];
#print_r($data);

$timeline = array();
foreach($data as $a) {
	$b = $a['data'];
	foreach($a['events'] as $ts) {
		$timeline[$ts] = $b;
	}
}
ksort($timeline);

$data_chg = array();
$last_data = array();
foreach($timeline as $ts => $data) {
	if (empty($last_data)) {
		$last_data = $data;
		continue;
	}
	$new_data = array();
	$c = false;
	for ($i = 0; $i <= 7; $i++) {
		if ($data[$i] != $last_data[$i]) {
			$new_data[$i] = $data[$i];
			$c = true;
		}
	}
	if ($c == true) {
		$data_chg[$ts] = $new_data;
	}
}
print_r($data_chg); exit(0);
#print_r($timeline);
foreach($timeline as $ts => $data_chg) {
	print "$ts";
	for ($i = 0; $i <= 7; $i++) {
		$b = $data[$i]; # 
		print "\t$b";
	}
	print "\n";
}

exit(0);

*/