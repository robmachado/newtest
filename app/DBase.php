<?php

namespace App;

use \PDO;

class DBase
{
    public $host = "";
    public $port = "";
    public $db = "";
    public $user = "";
    public $pass = "";
    public $myparam = 'array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"))';
    public $dsn = '';
    public $lastId = 0;
    public $ultId = 0;
    public $conta = 0;

    protected $transactionCounter = 0;
    private $dbType = 'MySQL';

    /**
     * Construtor da classe
     *
     * @param string $dbType
     * @param string $host
     * @param integer $port
     * @param string $db
     * @param string $user
     * @param string $pass
     * return none
     */
    public function __construct($dbType = 'MSSQL', $host = '', $port = '', $db = '', $user = '', $pass = '')
    {
        if ($host != '') {
            $this->host = $host;
        }
        if ($port != '') {
            $this->port = $port;
        }
        if ($db != '') {
            $this->db = $db;
        }
        if ($user != '') {
            $this->user = $user;
        }
        if ($pass != '') {
            $this->pass = $pass;
        }
        
        if ($dbType == 'MSSQL') {
            $this->dsn = "dblib:host=$this->host:$this->port;dbname=$this->db";
            $this->dbType = 'MSSQL';
        } else {
            $this->dsn = "mysql:host=$this->host;dbname=$this->db";
            $this->dbType = 'MySQL';
        }
    }
    
    /**
     * Conectar a base de dados
     *
     * @param string $user
     * @param string $pass
     * @return object PDO
     */
    public function connect($user = '', $pass = '')
    {
        if ($user == '') {
            $user = $this->user;
            $pass = $this->pass;
        }
        try {
            $dbh = new PDO($this->dsn, $user, $pass);
        } catch (\PDOException $e) {
            echo "Error: Falha na conexão .. " . $e->getMessage() . "<br/>";
            return false;
        }
        return $dbh;
    }
    
    /**
     * Executa uma pesquisa SQL
     *
     * @param object $dbh
     * @param string $sqlComm
     * @param array $data
     * @return array
     */
    public function querySQL(
        $dbh,
        $sqlComm,
        $data = array()
    ) {
        unset($aRet);
        $aRet = array();
        $properties = array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY);
        try {
            $sth = $dbh->prepare($sqlComm, $properties);
            $sth->execute($data);
            $aRet = $sth->fetchAll();
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        return $aRet;
    }
    
    /**
     * Executa comando SQL
     *
     * @param object $dbh
     * @param string $sqlComm
     * @param variant $data
     * @return boolean
     */
    public function execSQL($dbh, $sqlComm, $data = '')
    {
        if (is_array($sqlComm)) {
            foreach ($sqlComm as $sql) {
                try {
                    if (is_array($data)) {
                        $stmt = $dbh->prepare($sql);
                        $count = $stmt->execute($data);
                    } else {
                        $stmt = $dbh->prepare($sql);
                        $count = $stmt->execute();
                    }
                } catch (\PDOException $e) {
                    echo $e->getMessage();
                    return false;
                }
            }
        } else {
            $count = 0;
            try {
                if (is_array($data)) {
                    $stmt = $dbh->prepare($sqlComm);
                    $count = $stmt->execute($data);
                } else {
                    $stmt = $dbh->prepare($sqlComm);
                    $count = $stmt->execute();
                }
            } catch (\PDOException $e) {
                echo $e->getMessage();
                return false;
            }
        }
        return $count;
    }

    /**
     * Inicia a Transaction
     * @param object $dbh
     * return none
     */
    public function beginTrans($dbh)
    {
        if ($this->dbType == 'MSSQL') {
            $dbh->exec('BEGIN TRANSACTION');
        } elseif ($this->dbType == 'MySQL') {
            $dbh->beginTransaction();
        }
        $this->transactionCounter = 1;
    }
    
    /**
     * Retorna os comandos da Transaction
     * @param object $dbh
     * return none
     */
    public function rollbackTrans($dbh)
    {
        if ($this->transactionCounter == 1) {
            if ($this->dbType == 'MSSQL') {
                $dbh->exec('ROLLBACK TRANSACTION');
            } elseif ($this->dbType == 'MySQL') {
                $dbh->rollBack();
            }
            $this->transactionCounter = 0;
        }
    }

    /**
     * Realiza os comandos da Transaction
     * @param object $dbh
     * return none
     */
    public function commitTrans($dbh)
    {
        if ($this->transactionCounter == 1) {
            if ($this->dbType == 'MSSQL') {
                $dbh->exec('COMMIT TRANSACTION');
            } elseif ($this->dbType == 'MySQL') {
                $dbh->commit();
            }
            $this->transactionCounter = 0;
        }
    }

    /**
     * Busca o Id do último comando "insert"
     * @param object $dbh
     * return integer
     */
    public function lastInsertId($dbh)
    {
        if ($this->dbType == 'MSSQL') {
            $stmt = $dbh->query('SELECT @@IDENTITY AS id');
            $mixRc = (is_object($stmt) and $stmt->errorCode() == '00000');
            if ($mixRc === false) {
                return false;
            }
            $rowTd = $stmt->fetch(PDO::FETCH_NUM);
            if (!is_array($rowTd)) {
                doLog(
                    __FILE__,
                    __LINE__,
                    'insertRecord: $objSth->fetch() returns %s',
                    gettype($rowTd)
                );
                return false;
            }
            $stmt->closeCursor();
            $strLastRowId = trim($rowTd[0]);
            $strLastRowId = preg_replace('/[,.]0+$/', '', $strLastRowId);
            $strLastRowId = preg_replace('/[,.]/', '', $strLastRowId);
            $this->lastId = $strLastRowId;
        } elseif ($this->dbType == 'MySQL') {
            $strLastRowId = $dbh->lastInsertId();
        }
        return $strLastRowId;
    }
    
    /**
     * Converte uma data para formato inteiro do windows
     * ou seja o numero de dias desde 01/01/1900
     * @param date $data
     * @return integer
     */
    public function ts2win($data)
    {
        $data_inicial = '1900-01-01';
        // Usa a função strtotime() e pega o timestamp das duas datas:
        $time_inicial = strtotime($data_inicial);
        $time_final = strtotime($data);
        // Calcula a diferença de segundos entre as duas datas:
        $diferenca = $time_final - $time_inicial;
        // Calcula a diferença de dias
        $dias = (int) floor($diferenca / (60 * 60 * 24)) + 3;
        return $dias;
    }
}
