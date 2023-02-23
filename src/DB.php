<?php
/**
 * Created by PhpStorm.
 * User: rustysun
 * Date: 2016/10/3
 * Time: 下午8:40
 */
namespace Lube\Data;
use Error;
use PDO;

class DB {
    /**
     * 单例实例
     * @var DB
     */
    protected static $_instance;
    /**
     * PDO实例
     * @var PDO
     */
    protected $pdo;
    /**
     * PDO准备语句
     * @var \PDOStatement
     */
    protected $st;
    /**
     * 最后的SQL语句
     * @var string
     */
    protected $lastSQL;
    /**
     * 配置信息
     * @var \Lubye\Utils\Config
     */
    protected $config;

    private function __clone() {
    }

    /**
     * 构造函数
     * @param \Lubye\Utils\Config $config
     */
    private function __construct($config) {
        $this->config = $config;
    }

    /**
     * 默认数据库
     * @static
     * @param \Lubye\Utils\Config $config
     * @return DB
     */
    public static function getInstance($config) {
        if (!self::$_instance instanceof DB) {
            self::$_instance = new DB($config);
            self::$_instance->connect();
        }
        return self::$_instance;
    }

    /**
     * 连接数据库
     * @return void
     */
    public function connect() {
        $this->pdo = new PDO($this->config->get('dsn'), $this->config->get('username'), $this->config->get('password'), $this->config->get('option', TRUE));
    }

    /**
     * 断开连接
     * @return void
     */
    public function disConnect() {
        $this->pdo = NULL;
        $this->st = NULL;
    }

    /**
     * 抛出错误
     * @param array|null $msg
     * @throws Error
     */
    protected function throwError($msg = NULL) {
        $msg = !$msg ? $this->pdo->errorInfo() : $msg;
        throw new Error('数据库错误：' . $msg[2]);
    }

    /**
     * 最后添加的id
     * @return string
     */
    public function getLastInsertId() {
        return $this->pdo->lastInsertId();
    }

    /**
     * 查询sql
     * @param string $statement
     * @return DB
     */
    public function query($statement) {
        $res = $this->pdo->query($statement);
        if (!$res) {
            $this->throwError();
        }
        $this->st = $res;
        $this->lastSQL = $statement;
        return $this;
    }

    /**
     * 序列化一次数据
     * @return mixed
     */
    public function fetchOne() {
        return $this->st->fetch();
    }

    /**
     * 序列化所有数据
     * @return array
     */
    public function fetchAll() {
        return $this->st->fetchAll();
    }

    public function fetchObject() {
        return $this->st->fetchObject();
    }

    /**
     * 影响的行数
     * @return int
     */
    public function affectRows() {
        return $this->st->rowCount();
    }

    /**
     * @param      $sql
     * @param null $bind_params
     * @return \PDOStatement
     */
    public function execute($sql, $bind_params = NULL) {
        if (!$sql) {
            $this->throwError(['RUSTDB0001', 1, 'SQL语句不能为空']);
        }
        $res = $this->pdo->prepare($sql);
        if (!$res) {
            $this->throwError();
        }
        //$this->st = $res;
        $this->lastSQL = $sql;
        //获取要执行SQL
        if (!$res->execute($bind_params)) {
            $this->throwError();
        }
        return $res;
    }
}