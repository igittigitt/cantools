<?php
/**
 * elmconfig.php
 * Parser plugin for ELMConfig logfiles
 */

class CANLOG_PARSER_ELMCONFIG extends CANLOG_PARSER
{
	public function skip_heading($fh)
	{
		/**
		 * Version: ELMConfig 0.2.17c
		 * Adapter: ELM327
		 * Driver: VCP
		 * Baudrate: 500000
		 * Connection: Scan
		 *
		 * 06:45:26.036 040 F9 01 00 00 00 01 B0 27 
		 * ...
		 */
/*		
		$line = 0;
		
		// skip header
		for ($i = 1; $i <= 6; $i++) {
			fgets($this->fh);
			$line++;
			if (feof($this->fh)) {
				$this->err = "Unexpected end of log found in: " . $this->fp;
				return false;
			}
		}
*/
		return false;
		
	}
	
	public function check_format($fh)
	{
/*
		$line = fgets($this->fh, 50);
		rewind($this->fh);
		if (substr($line, 0, 18) == "Version: ELMConfig") {
			return self::CANLOG_FMT_ELM;
		}
*/
		return false;
	}

}
