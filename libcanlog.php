<?php
/**
 * libcanstat.inc.php
 *
 * This library exports:
 *   CANLOG::autodetect_format(FILE) - return format-ID (e.g. 'LAWICEL') of logfile given
 */

function errorexit($msg, $ec=1) {
	print("$msg\n");
	exit($ec);
}

class CANLOG
{
	private $err = "";		# last errormessage
	private $file = NULL;	# fullpath to logfile
	private $format = "";	# format of logfile (ELMCONFIG|LAWICEL)
	private $fh = NULL;		# filehandle of logfile
	private $parsers = array();	# array of parser plugins found in /parser dir
	private $parser = NULL;	# Class instance of parser plugin choosen
	private $ids = array();	# array of ids parsed (if full-read)
	
	const CANLOG_FMT_LAWICEL = "LAWICEL";
	const CANLOG_FMT_ELM     = "ELMCONFIG";
	
	// ************************************************************************* //
	// ***                      CONSTRUCTOR / DESTRUCTOR                     *** //
	// ************************************************************************* //

	/**
	 *
	 */
	public function __construct()
	{
		$this->register_parsers();
	}

	
	// ************************************************************************* //
	// ***                           STATIC FUNCTIONS                        *** //
	// ************************************************************************* //


	// ************************************************************************* //
	// ***                           PUBLIC FUNCTIONS                        *** //
	// ************************************************************************* //

	/**
	 * Return last error string (or "" if none)
	 */
	public function lasterr()
	{
		return $this->err;
	}

	
	/**
	 * Open specified logfile, determine format (opt) and skip headings.
	 * Leave file opened for successive reads using function "read_next" or "read_all"
	 */
	public function open($file, $format="")
	{
		$this->file = $file;
		if ( ! ($this->fh = fopen($this->file, "r")) ) {
			throw new Exception("Error opening logfile ".$this->file);
			return false;
		}
		
		// load format plugin
		if ($format == "") {
			$format = $this->autodetect_format();
		}
		if ( ! isset($this->parsers[$format])) {
			throw new Exception("No logfile parser for ".$format);
		}
		require_once($this->parsers[$format]);
		$class = "CANLOG_PARSER_" . $format;
		$this->parser = new $class($this->file);

		// Skip headings of file
		if ( ! $this->parser->skip_heading($this->fh)) {
			$this->err = $this->parser->last_err();
			return false;
		}

		return true;
	}
	
	public function rewind()
	{
		return $this->parser->rewind($this->fh);
	}
	
	/**
	 * Close current file
	 */
	public function close()
	{
		return fclose($this->fh);
	}
	
	/**
	 * Check end-of-file reached
	 */
	public function eof()
	{
		return feof($this->fh);
	}
	
	/**
	 * Just a wrapper of parser object
	 */
	public function next_msg($filter="")
	{
		while (1)
		{
			$msg = $this->parser->next_msg($this->fh);
			if ($msg === false) {
				$this->err = $this->parser->last_err();
				return false;
			}
			if ( ! $msg) {
				break;
			}
			
			if ( ! is_array($filter) || ! $filter) {
				break;
			}
			
			if (is_array($filter)) {
				$all_match = true;
				foreach ($filter as $field => $crit) {
					switch ($field) {
						case "id":
							if ($msg['id'] != $crit) {
								$all_match = false;
							}
							break;
					}
				}
				if ($all_match == true) {
					break;
				}
			}
		}
		
		return $msg;
	}

	public function length()
	{
		return $this->parser->last_ts();
	}
	
	/**
	 *
	 */
	public function has_id($id_hex)
	{
		$id = hexdec($id_hex);
		return array_key_exists($id, $this->ids);
	}

	/**
	 *
	 */	
	public function dump_id($id_hex)
	{
		if ( ! $this->has_id($id_hex)) return false;
		$id = hexdec($id_hex);
		return $this->ids[$id];
	}

	/**
	 *
	 */	
	public function dump_ids()
	{
		return $this->ids;
	}
	
	/**
	 *
	 */
	public function count_ids()
	{
		return count($this->ids);
	}
	
	
	// ************************************************************************* //
	// ***                          PRIVATE FUNCTIONS                        *** //
	// ************************************************************************* //

	/**
	 * Scan for format plugins
	 */
	private function register_parsers()
	{
		// scan parser dir and build list
		$parser_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . "parser" . DIRECTORY_SEPARATOR;
		if ( ! is_dir($parser_dir)) {
			throw new Exception("Parser plugin direcotry not found: ".$parser_dir);
		}
		foreach (scandir($parser_dir) as $f) {
			if (substr($f, -4) == ".php") {
				$n = strtoupper(basename($f, ".php"));
				if ($n == "CANLOG_PARSER") continue;  # skip base class
				$this->parsers[$n] = $parser_dir . $f;
			}
		}
		if (empty($this->parsers)) {
			throw new Exception("No parsers found in plugin direcotry: ".$parser_dir);
		}

		// load base parser plugin
		if ( ! is_file($parser_dir."canlog_parser.php")) {
			throw new Exception("Base parser class not found: ".$parser_dir."canlog_parser.php");
		}
		require_once($parser_dir."canlog_parser.php");
	}

	private function load_parser()
	{
	}
	
	/**
	 * Detect format of logfile given
	 */
	private function autodetect_format()
	{
return "LAWICEL";

		// Optionally determine file format
		$fmt = strtoupper("");
		if ($fmt == self::CANLOG_FMT_AUTO) {
			if ( ! ($fmt = $this->detect_format()) ) {
				return false;
			}
		}

		// Load parser plugin
		if ( ! isset($this->parsers[$fmt])) {
			$this->err = "Unhandled log format: ".$fmt;
			return false;
		}
		$pfile = $this->parsers[$fmt];
		if ( ! is_file($pfile)) {
			$this->err = "Parser plugin not found: ".$pfile;
			return false;
		}
		require_once($pfile);
		$class = "CANLOG_PARSER_".$fmt;
		$this->parser = new $class($this->fh);
		if ( ! is_subclass_of($this->parser, "CANLOG_PARSER")) {
			$this->err = "Could not create instance of ".$class;
			return false;
		}
		$this->fmt = $fmt;

		// load all available parser subclasses and try to match
		$fmt = "";
		foreach ($this->parsers as $pnam => $pfile) {
			require_once($pfile);
			$class = "CANLOG_PARSER_".$pnam;
			$p = new $class($this->fh);
			if ($p->check_format()) {
				$fmt = $pnam;
				break;
			}
		}
		if ($fmt == "") {
			$this->err = "Unknown logfile format. Does not match: ".implode(", ",array_keys($this->parsers));
			return false;
		}
		return $fmt;
	}


}



/*

}

function ksort_hex(&$a) {
	uksort($a, "_ksort_hex_fn");
}

function sort_hex(&$a) {
	usort($a, "_ksort_hex_fn");
}

function _ksort_hex_fn($a, $b) {
	if ($a == $b) return 0;
	return(hexdec($a) > hexdec($b));
}
*/