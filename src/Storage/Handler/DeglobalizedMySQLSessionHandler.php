<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Vicus\Storage\Handler;

/**
 * Description of GlobalMySQLSessionHandler
 * /usr/local/lib/php/MySQL-Sessions.php
 * @author Michael Koert <mkoert at bluebikeproductions.com>
 */
class DeglobalizedMySQLSessionHandler extends \SessionHandler implements \SessionHandlerInterface
{

	protected $sessionLink = null;


	    /**
     * No locking is done. This means sessions are prone to loss of data due to
     * race conditions of concurrent requests to the same session. The last session
     * write will win in this case. It might be useful when you implement your own
     * logic to deal with this like an optimistic approach.
     */
    const LOCK_NONE = 0;

    /**
     * Creates an application-level lock on a session. The disadvantage is that the
     * lock is not enforced by the database and thus other, unaware parts of the
     * application could still concurrently modify the session. The advantage is it
     * does not require a transaction.
     * This mode is not available for SQLite and not yet implemented for oci and sqlsrv.
     */
    const LOCK_ADVISORY = 1;

    /**
     * Issues a real row lock. Since it uses a transaction between opening and
     * closing a session, you have to be careful when you use same database connection
     * that you also use for your application logic. This mode is the default because
     * it's the only reliable solution across DBMSs.
     */
    const LOCK_TRANSACTIONAL = 2;


    /**
     * @var string Database driver
     */
    private $driver;

    /**
     * @var string Table name
     */
    private $table = 'sessions';

    /**
     * @var string Column for session id
     */
    private $idCol = 'sess_id';

    /**
     * @var string Column for session data
     */
    private $dataCol = 'sess_data';

    /**
     * @var string Column for lifetime
     */
    private $lifetimeCol = 'sess_lifetime';

    /**
     * @var string Column for timestamp
     */
    private $timeCol = 'sess_time';

    /**
     * @var string Username when lazy-connect
     */
    private $username = '';

    /**
     * @var string Password when lazy-connect
     */
    private $password = '';

    /**
     * @var array Connection options when lazy-connect
     */
    private $connectionOptions = array();

    /**
     * @var int The strategy for locking, see constants
     */
    private $lockMode = self::LOCK_TRANSACTIONAL;

    /**
     * It's an array to support multiple reads before closing which is manual, non-standard usage
     *
     * @var \PDOStatement[] An array of statements to release advisory locks
     */
    private $unlockStatements = array();

    /**
     * @var bool True when the current session exists but expired according to session.gc_maxlifetime
     */
    private $sessionExpired = false;

    /**
     * @var bool Whether a transaction is active
     */
    private $inTransaction = false;

    /**
     * @var bool Whether gc() has been called
     */
    private $gcCalled = false;


	public function __construct($dbSessionLink, $dbOptions, $storageOptions = null)
	{
		$this->sessionLink = $dbSessionLink;

		$this->table = isset($dbOptions['db_table']) ? $dbOptions['db_table'] : $this->table;
		$this->idCol = isset($dbOptions['db_id_col']) ? $dbOptions['db_id_col'] : $this->idCol;
		$this->dataCol = isset($dbOptions['db_data_col']) ? $dbOptions['db_data_col'] : $this->dataCol;
		$this->lifetimeCol = isset($dbOptions['db_lifetime_col']) ? $dbOptions['db_lifetime_col'] : $this->lifetimeCol;
		$this->timeCol = isset($dbOptions['db_time_col']) ? $dbOptions['db_time_col'] : $this->timeCol;
		$this->username = isset($dbOptions['db_username']) ? $dbOptions['db_username'] : $this->username;
		$this->password = isset($dbOptions['db_password']) ? $dbOptions['db_password'] : $this->password;
		$this->connectionOptions = isset($dbOptions['db_connection_options']) ? $dbOptions['db_connection_options'] : $this->connectionOptions;
		$this->lockMode = isset($dbOptions['lock_mode']) ? $dbOptions['lock_mode'] : $this->lockMode;

	}

	public function close()
	{
		return(true);
	}

	public function destroy($session_id)
	{
		$query = "DELETE FROM `{$this->table}` WHERE `{$this->idCol}` = '$session_id' ";
		$results = $this->sessionLink->query($query);

		return($results);
	}

	public function gc($maxlifetime)
	{
		$query = "DELETE FROM `{$this->table}` WHERE `{$this->timeCol}` < DATE_SUB(NOW(), INTERVAL {$maxlifetime} SECOND) ";
		$results = $this->sessionLink->query($query);

		return($results);
	}

	 /**
     * Returns true when the current session exists but expired according to session.gc_maxlifetime.
     *
     * Can be used to distinguish between a new session and one that expired due to inactivity.
     *
     * @return bool Whether current session expired
     */
    public function isSessionExpired()
    {
        return $this->sessionExpired;
    }

	public function open($save_path, $name)
	{

		if (null === $this->sessionLink) {
            $this->connect($this->dsn ?: $save_path);
        }

		return true;
	}

	public function read($session_id)
	{
		
		$query = "SELECT `{$this->dataCol}` FROM `{$this->table}` WHERE `{$this->idCol}` = '$session_id' ";
		$results = $this->sessionLink->query($query);

		$row = $results->fetch_row();

		return($row[0]);
	}

	public function write($session_id, $session_data)
	{
		$maxlifetime = (int) ini_get('session.gc_maxlifetime');

		$results = $this->sessionLink->query("SET profiling=1");

		$session_data = $this->sessionLink->real_escape_string($session_data);

		$time = time();

		$query = "INSERT INTO {$this->table} (`{$this->idCol}`, `{$this->dataCol}`, `{$this->timeCol}`, `{$this->lifetimeCol}`, `LastUpdated`) VALUES ('$session_id', '$session_data', {$time}, $maxlifetime, now()) ";
		$query .= "ON DUPLICATE KEY UPDATE `{$this->dataCol}` = '$session_data', `{$this->timeCol}` = {$time}, `{$this->lifetimeCol}` = {$maxlifetime}, LastUpdated = now() ";

		$results = $this->sessionLink->query($query);

		return($results);
	}




	/**
	 * NOT WORKING!!!
     * Lazy-connects to the database.
     *
     * @param string $dsn DSN string
     */
    private function connect()
    {
		$this->sessionLink = mysqli_connect($this->host_master,$this->user,$this->password,$this->name) or die("Error " . mysqli_error($this->sessionLink));

//        $this->pdo = new \PDO($dsn, $this->username, $this->password, $this->connectionOptions);
//        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//        $this->driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

}
