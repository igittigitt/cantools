<?php
/**
 * canlog_parser.php
 * Base class für logfile plugins
 */

class CANLOG_PARSER
{
	protected $file = "";
	protected $line = 0;	# index of current line in file
	protected $err = "";
	protected $file_magic = "";
	
	public function __construct($file)
	{
		$this->file = $file;
	}
	
	public function rewind($fh)
	{
		rewind($fh);
		$this->line = 0;
		$this->err = "";
		
		// Skip headings of file
		if ( ! $this->skip_heading($fh)) {
			$this->err = $this->parser->last_err();
			return false;
		}
		
		return true;
	}

	/**
	 * Return next line (up to CRLF or EOF) of logfile or FALSE
	 */
	public function next_line($fh)
	{
		if ( ! $fh || feof($fh)) {
			$this->err = "Unexpected end of file while skipping headers";
			return false;
		}
		$line = fgets($fh);
		if ($line === FALSE) {
			return false;
		}
		$this->line++;
		return $line;
	}

	public function last_err()
	{
		return $this->err;
	}
	
	public function hash($a)
	{
		return implode("", $a);
	}

	/**
	 *
	 */
	public function check_format($fh)
	{
		if ( ! $fh || feof($fh)) {
			$this->err = "Unexpected end of file while checking headers";
			return false;
		}
		$magic_len = strlen($this->file_magic);
		$line = fread($fh, $magic_len);
		if (strlen($line) != $magic_len) {
			$this->err = "Can't read enough chars from logfile to match LAWICEL header";
			return false;
		}
		if (substr($line, 0, $magic_len) != $this->header_magic ) {
			$this->err = "Header of logfile does not match LAWICEL format";
			return false;
		}
		rewind($fh);
		return true;
	}

	/********************************************/
	/*** METHODS BEING OVERLOADED BY SUBCLASS ***/
	/********************************************/

	public function skip_heading($fh)
	{
	}
	
	public function next_msg($fh)
	{
	}

	public function last_ts()
	{
	}

}
