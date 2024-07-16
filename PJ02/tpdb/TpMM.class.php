<?php
/**
 * ThinkPHP 实例化一个没有模型文件的Model 对快捷方法M的改造
 */
class TpMM {

    protected $_models = [];
    protected $connection = [];

    public function __construct($connection) {
        $this->connection = $connection;
    }

    /**
     * 实例化一个没有模型文件的Model
     * @param string $name Model名称 支持指定基础模型 例如 MongoModel:User
     * @param string $tablePrefix 表前缀
     * @return Think\Model
     */
    public function M($name = '', $tablePrefix = '')
    {
        $connection = $this->connection;
        if (strpos($name, ':')) {
            list($class, $name) = explode(':', $name);
        } else {
            $class = 'TpModel';
        }
        $guid = (is_array($connection) ? implode('', $connection) : $connection) . $tablePrefix . $name . '_' . $class;
        if (!isset($this->_models[$guid])) {
            $this->_models[$guid] = new $class($name, $tablePrefix, $connection);
        }
        return $this->_models[$guid];
    }

    public function freeM()
    {
        foreach ($this->_models as $guid => $model) {
            // 这里只能清除默认连接
            $this->_models[$guid]->db(0, null);
            $this->_models[$guid] = null;

            unset($this->_models[$guid]);
            return null;
        }

        TpDb::unsetInstance($this->connection);
    }

    public function __destruct() {
        $this->freeM();
    }

}
