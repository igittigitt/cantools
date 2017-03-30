<?php
/**
 * elmconfig.php
 * Parser plugin for ELMConfig logfiles
 */

class CANLOG_PARSER_LAWICEL extends CANLOG_PARSER
{
	protected $file_magic = "Time   ID     DLC Data ";
	
	private $ts_corr = NULL;  # correctional value to null log timecode
	private $ts_last = NULL;
	private $ts_base = 0;
	private $line_fmt = "%2d,%3d %3x %1d %2x %2x %2x %2x %2x %2x %2x %2x";  # SEC,MS ID DLC DB0 ... DB7

	/**
	 * Simply skip first line, if heading present.
	 *
	 * Time   ID     DLC Data                    Comment
	 * 18,876 070      8 00 B0 C8 64 9D B0 01 D6 
	 * ...
	 */
	public function skip_heading($fh)
	{
		if ($this->line > 0) {
			$this->err = "Calling skip_heading() not allowed in line ".$this->line;
			return false;
		}
		$line = $this->next_line($fh);
		if ($line === false) {  # EOF
			return false;
		}
		if (substr($line, 0, strlen($this->file_magic)) != $this->file_magic) {
			rewind($fh);
		}
		return true;
	}

	/**
	 *
	 */
	public function next_msg($fh)
	{
		while (1) {
			$line = $this->next_line($fh);
			if ($line === false) {  # EOF
				return array();
			}
			$line = trim($line);
			if ($line != "") break;
		}
		
		$rec = sscanf($line, $this->line_fmt);

		// transform timestamp into 0-based timecode (ms)
		$ts = $rec[0] * 1000 + $rec[1];  # normalize to milliseconds
		if ($this->ts_corr == NULL) {
			$this->ts_corr = $ts;
		}
		
		// handle timecode rollover
		if ($this->ts_last != NULL && $ts < $this->ts_last) {
			$this->ts_base += 60000;
#print "DEBUG: ts rollover detected, set ts_log_base to ".$this->ts_base."\n";
		}
		$this->ts_last = $ts;
		
		// calculate relative timecode
		$rel_ts = $this->ts_base + $ts - $this->ts_corr;
#print "DEBUG: log_ts=$ts rel_ts=$rel_ts\n";
		
		$dlc = $rec[3];
		$data = array_slice($rec, 4, $dlc);
		if ($dlc < 8) {
			$data += array_fill($dlc, 8-$dlc, 0);  # always return 8 entries, pad with "0"
		}

		$id = $rec[2];

		return array(
			'id' => $id,
			'line' => $this->line,
			'rel_ts' => $rel_ts,
			'abs_ts' => $ts,
			'dlc' => $dlc,
			'data' => $data,
		);
	}

	public function last_ts()
	{
		return $this->ts_last;
	}
}
