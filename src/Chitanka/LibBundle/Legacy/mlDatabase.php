<?php
namespace Chitanka\LibBundle\Legacy;

class mlDatabase {

	/** Database server name */
	protected $server;
	/** Database user name */
	protected $user;
	/** Database user password */
	protected $pass;
	/** Database name */
	protected $dbName;
	protected $prefix = '';
	protected $charset = 'utf8';
	protected $collationConn = 'utf8_general_ci';
	/**
		Connection to the database
		@var resource
	*/
	protected $conn = NULL;
	protected $doLog = true;
	protected $errno;

	protected $slave = null;
	protected $master = null;


	public function __construct($server, $user, $pass, $dbName) {
		$this->server = $server;
		$this->user = $user;
		$this->pass = $pass;
		$this->dbName = $dbName;
		$date = date('Y-m-d');
		$this->logFile = dirname(__FILE__)."/../../../../app/logs/db-$date.sql";
		$this->errLogFile = dirname(__FILE__)."/../../../../app/logs/db-error-$date";
	}


	public function setSlave($server, $user, $pass, $name) {
		$this->slave = new mlDatabase($server, $user, $pass, $name);
	}

	public function setMaster($server, $user, $pass, $name) {
		$this->master = new mlDatabase($server, $user, $pass, $name);
		$this->master->disableLogging();
	}

	public function exists($table, $keys = array()) {
		return $this->getCount($table, $keys) > 0;
	}


	public function getObjects($table, $dbkey = array(), $kfield = null) {
		fillOnNull($kfield, 'id');
		$res = $this->select($table, $dbkey);
		$objs = array();
		while ( $row = mysql_fetch_assoc($res) ) {
			$objs[ $row[$kfield] ] = $row;
		}
		return $objs;
	}

	public function getNames($table, $dbkey = array(), $nfield = null, $kfield = null) {
		fillOnNull($nfield, 'name');
		fillOnNull($kfield, 'id');
		$sel = array($kfield, $nfield);
		$res = $this->select($table, $dbkey, $sel, $nfield);
		$objs = array();
		while ( $row = mysql_fetch_row($res) ) {
			$objs[ $row[0] ] = $row[1];
		}
		return $objs;
	}


	public function getFields($table, $dbkey, $fields) {
		$res = $this->select($table, $dbkey, $fields);
		if ( $this->numRows($res) == 0 ) {
			return null;
		}
		$row = $this->fetchRow($res);
		return count($row) > 1 ? $row : $row[0];
	}

	public function getFieldsMulti($table, $dbkey, $fields) {
		$res = $this->select($table, $dbkey, $fields);
		$data = array();
		while ( $row = $this->fetchRow($res) ) {
			$data[] = count($row) > 1 ? $row : $row[0];
		}
		return $data;
	}


	public function getRandomRow($table) {
		$res = $this->select($table, array(), array('MIN(id)', 'MAX(id)'));
		list($min, $max) = $this->fetchRow($res);
		do {
			$res = $this->select($table, array('id' => rand($min, $max)));
			$row = $this->fetchAssoc($res);
			if ( !empty($row) ) return $row;
		} while (true);
	}

	public function getCount($table, $keys = array()) {
		$res = $this->select($table, $keys, 'COUNT(*)');
		list($count) = mysql_fetch_row($res);
		return (int) $count;
	}


	public function iterateOverResult($query, $func, $obj = null,
			$buffered = false) {
		$result = $this->query($query, $buffered);
		$out = '';
		if ($result) {
			while ( $row = mysql_fetch_assoc($result) ) {
				$out .= is_null($obj) ? $func($row) : $obj->$func($row);
			}
			$this->freeResult($result);
		}
		return $out;
	}


	public function select($table, $keys = array(), $fields = array(),
			$orderby = '', $offset = 0, $limit = 0, $groupby = '') {

		$q = $this->selectQ($table, $keys, $fields, $orderby, $offset, $limit);
		return $this->query($q);
	}

	public function selectQ($table, $keys = array(), $fields = array(),
			$orderby = '', $offset = 0, $limit = 0, $groupby = '') {

		settype($fields, 'array');
		$sel = empty($fields) ? '*' : implode(', ', $fields);
		$sorder = empty($orderby) ? '' : ' ORDER BY '.$orderby;
		$sgroup = empty($groupby) ? '' : ' GROUP BY '.$groupby;
		$slimit = $limit > 0 ? " LIMIT $offset, $limit" : '';
		return "SELECT $sel FROM $table".$this->makeWhereClause($keys).
			$sgroup . $sorder . $slimit;
	}


	public function extselect($qparts) {
		return $this->query( $this->extselectQ($qparts) );
	}

	/**
		Build an SQL SELECT statement with LEFT JOIN clause(s) from an array
		(Idea from phpBB).
		@param $qparts	Associative array with following possible keys:
			SELECT, FROM, LEFT JOIN, WHERE, GROUP BY, ORDER BY, LIMIT
	*/
	public function extselectQ($qparts, $distinct = false) {
		$qd = $distinct ? ' DISTINCT' : '';
		$q = "SELECT$qd $qparts[SELECT] FROM $qparts[FROM]";
		if ( isset($qparts['LEFT JOIN']) ) {
			foreach ($qparts['LEFT JOIN'] as $table => $onrule) {
				$q .= " LEFT JOIN $table ON ($onrule)";
			}
		}
		if ( isset($qparts['WHERE']) ) {
			$q .= $this->makeWhereClause($qparts['WHERE']);
		}
		foreach ( array('GROUP BY', 'ORDER BY') as $key ) {
			if ( isset($qparts[$key]) ) {
				$q .= " $key $qparts[$key]";
			}
		}
		if ( isset($qparts['LIMIT']) ) {
			if ( is_array($qparts['LIMIT']) ) {
				list($offset, $limit) = $qparts['LIMIT'];
			} else {
				$offset = 0;
				$limit = (int) $qparts['LIMIT'];
			}
			$q .= $limit > 0 ? " LIMIT $offset, $limit" : '';
		}
		return $q;
	}


	public function insert($table, $data, $ignore = false, $putId = true) {
		return $this->query($this->insertQ($table, $data, $ignore, $putId));
	}

	public function insertQ($table, $data, $ignore = false, $putId = true) {
		if ( empty($data) ) {
			return '';
		}

		if ($putId && ! array_key_exists('id', $data) && ($id = $this->autoIncrementId($table)) ) {
			$data['id'] = $id;
		}

		$signore = $ignore ? ' IGNORE' : '';
		return "INSERT$signore INTO $table". $this->makeSetClause($data);
	}


	public function multiinsert($table, $data, $fields, $ignore = false) {
		return $this->query($this->multiinsertQ($table, $data, $fields, $ignore));
	}

	public function multiinsertQ($table, $data, $fields, $ignore = false) {
		if ( empty($data) || empty($fields) ) {
			return '';
		}
		$vals = ' (`'. implode('`, `', $fields) .'`) VALUES';
		$fcnt = count($fields);
		foreach ($data as $rdata) {
			$vals .= ' (';
			for ($i=0; $i < $fcnt; $i++) {
				$val = isset($rdata[$i]) ? $this->normalizeValue($rdata[$i]) : "''";
				$vals .= $val .', ';
			}
			$vals = rtrim($vals, ' ,') .'),';
		}
		$signore = $ignore ? ' IGNORE' : '';
		return "INSERT$signore INTO $table". rtrim($vals, ',');
	}


	public function update($table, $data, $keys) {
		return $this->query( $this->updateQ($table, $data, $keys) );
	}

	public function updateQ($table, $data, $keys) {
		if ( empty($data) ) { return ''; }
		if ( empty($keys) ) { return $this->insertQ($table, $data, true); }
		if ( !is_array($keys) ) {
			$keys = array('id' => $keys);
		}
		return 'UPDATE '. $table . $this->makeSetClause($data) .
			$this->makeWhereClause($keys);
	}


	public function replace($table, $data) {
		return $this->query( $this->replaceQ($table, $data) );
	}

	public function replaceQ($table, $data) {
		if ( empty($data) ) { return ''; }
		return 'REPLACE '.$table.$this->makeSetClause($data);
	}

	public function delete($table, $keys, $limit = 0) {
		return $this->query( $this->deleteQ($table, $keys, $limit) );
	}

	public function deleteQ($table, $keys, $limit = 0) {
		if ( empty($keys) ) { return ''; }
		if ( !is_array($keys) ) $keys = array('id' => $keys);
		$q = 'DELETE FROM '. $table . $this->makeWhereClause($keys);
		if ( !empty($limit) ) $q .= " LIMIT $limit";
		return $q;
	}


	public function makeSetClause($data, $putKeyword = true) {
		if ( empty($data) ) { return ''; }
		$keyword = $putKeyword ? ' SET ' : '';
		$cl = array();
		foreach ($data as $field => $value) {
			if ($value === null) {
				continue;
			}
			if ( is_numeric($field) ) { // take the value as is
				$cl[] = $value;
			} else {
				$cl[] = "`$field` = ". $this->normalizeValue($value);
			}
		}
		return $keyword . implode(', ', $cl);
	}


	/**
	@param $keys Array with mixed keys (associative and numeric).
		By numeric key take the value as is if the value is a string, or send it
		recursive to makeWhereClause() with OR-joining if the value is an array.
		By string key use “=” for compare relation if the value is string;
		if the value is an array, use the first element as a relation and the
		second as comparison value.
		An example follows:
		$keys = array(
			'k1 <> 1', // numeric key, string value
			array('k2' => 2, 'k3' => 3), // numeric key, array value
			'k4' => 4, // string key, scalar value
			'k5' => array('>=', 5), // string key, array value (rel, val)
		)
	@param $join How to join the elements from $keys
	@param $putKeyword Should the keyword “WHERE” precede the clause
	*/
	public function makeWhereClause($keys, $join = 'AND', $putKeyword = true) {
		if ( empty($keys) ) {
			return $putKeyword ? ' WHERE 1' : '';
		}
		$cl = $putKeyword ? ' WHERE ' : '';
		$whs = array();
		foreach ($keys as $field => $rawval) {
			if ( is_numeric($field) ) { // take the value as is
				$field = $rel = '';
				if ( is_array($rawval) ) {
					$njoin = $join == 'AND' ? 'OR' : 'AND';
					$val = '('.$this->makeWhereClause($rawval, $njoin, false).')';
				} else {
					$val = $rawval;
				}
			} else {
				if ( is_array($rawval) ) {
					list($rel, $val) = $rawval;
					if (($rel == 'IN' || $rel == 'NOT IN') && is_array($val)) {
						// set relation — build an SQL set
						$cb = array($this, 'normalizeValue');
						$val = '('. implode(', ', array_map($cb, $val)) .')';
					} else {
						$val = $this->normalizeValue($val);
					}
				} else {
					$rel = '='; // default relation
					$val = $this->normalizeValue($rawval);
				}
			}
			$whs[] = "$field $rel $val";
		}
		$cl .= '('. implode(") $join (", $whs) . ')';
		return $cl;
	}


	public function normalizeValue($value) {
		/*if ( is_null($value) ) {
			return 'NULL';
		} else */
		if ( is_bool($value) ) {
			$value = $value ? 1 : 0;
		} else if ($value instanceof \DateTime) {
			$value = $value->format('Y-m-d H:i:s');
		} else {
			$value = $this->escape($value);
		}
		return '\''. $value .'\'';
	}

	public function setPrefix($prefix) { $this->prefix = $prefix; }


	public function escape($string) {
		if ($this->slave) {
			return $this->slave->escape($string);
		}

		if ( !isset($this->conn) ) { $this->connect(); }
		return mysql_real_escape_string($string, $this->conn);
	}


	/**
		Send a query to the database.
		@param string $query
		@param bool $useBuffer Use buffered or unbuffered query
		@return resource, or false by failure
	*/
	public function query($query, $useBuffer = true) {
		if ( empty($query) ) {
			return true;
		}

		if ( $this->slave && ! self::isWriteQuery($query) ) {
			return $this->slave->query($query, $useBuffer);
		}

		if ( !isset($this->conn) ) { $this->connect(); }
		$res = $useBuffer
			? mysql_query($query, $this->conn)
			: mysql_unbuffered_query($query, $this->conn);
		if ( !$res ) {
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			$this->log("Error $this->errno: $this->error\nQuery: $query\n"
				/*."Backtrace\n". print_r(debug_backtrace(), true)*/, true);
			return false;
		}

		if ( self::isWriteQuery($query) ) {
			$u = isset($GLOBALS['user']) ? $GLOBALS['user']->id : 0;
			$this->log("/*U=$u*/ $query;", false);

			if ($this->master) {
				$this->master->query($query);
			}
		}

		return $res;
	}


	public function transaction($queries) {
		$res = array();
		$this->query('START TRANSACTION');
		foreach ( (array) $queries as $query) {
			$lres = $this->query($query);
			if ($lres === false) {
				return false;
			}
			$res[] = $lres;
		}
		$this->query('COMMIT');
		return $res;
	}


	static public function isWriteQuery($query)
	{
		return preg_match('/UPDATE|INSERT|REPLACE|DELETE|START|COMMIT|ALTER/', $query);
	}


	/** @return array Associative array */
	public function fetchAssoc($result) {
		return mysql_fetch_assoc($result);
	}

	/** @return array */
	public function fetchRow($result) {
		return mysql_fetch_row($result);
	}

	/** @return integer */
	public function numRows($result) {
		return mysql_num_rows($result);
	}

	/** @return integer */
	public function affectedRows() {
		return mysql_affected_rows($this->conn);
	}

	public function freeResult($result) {
		return mysql_free_result($result);
	}

	/**
		Return next auto increment for a table
		@param string $tableName
		@return integer
	*/
	public function autoIncrementId($tableName) {
		$res = $this->query('SHOW TABLE STATUS LIKE "'.$tableName.'"');
		$row = mysql_fetch_assoc($res);
		return $row['Auto_increment'];
	}


	protected function connect() {
		$this->conn = mysql_connect($this->server, $this->user, $this->pass, true)
			or $this->mydie("Проблем: Няма връзка с базата. Изчакайте пет минути и опитайте отново да заредите страницата.");
		mysql_select_db($this->dbName, $this->conn)
			or $this->mydie("Could not select database $this->dbName.");
		mysql_query("SET NAMES '$this->charset' COLLATE '$this->collationConn'", $this->conn)
			or $this->mydie("Could not set names to '$this->charset':");
	}


	protected function mydie($msg) {
		header('Content-Type: text/plain; charset=UTF-8');
		header('HTTP/1.1 503 Service Temporarily Unavailable');
		die($msg .' '. mysql_error());
	}


	public function enableLogging()
	{
		$this->doLog = true;
	}

	public function disableLogging()
	{
		$this->doLog = false;
	}

	protected function log($msg, $isError = true)
	{
		if ($this->doLog) {
			file_put_contents($isError ? $this->errLogFile : $this->logFile,
				'/*'.date('Y-m-d H:i:s').'*/ '. $msg."\n", FILE_APPEND);
		}
	}

}
