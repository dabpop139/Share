<?php
require_once 'tpdb/TpLoader.php';

use Swoole\Process;
use Swoole\Runtime;
use Swoole\Coroutine\Http\Server;
use Swoole\Coroutine\WaitGroup;

$lockfname = 'lockex-tpswoole-main';
$lockpath = '/tmp/'.$lockfname;

$fplock = fopen($lockpath, 'w');
// LOCK_NB 设置非阻塞等待
if (!flock($fplock, LOCK_EX|LOCK_NB)) {
    // echo 'Error: fetch LOCK_EX error -> '.$lockpath.PHP_EOL;
    exit();
}
// echo 'Success: fetch LOCK_EX success -> '.$lockpath.PHP_EOL;

Swoole\Process::daemon();
Runtime::enableCoroutine();

// 记录内存初始使用
date_default_timezone_set('Asia/Shanghai');
define('MEMORY_LIMIT_ON', false);
define('APP_DEBUG', false);

define('ENV_PREFIX', 'PHP_');
define('ROOT_PATH', realpath(__DIR__) . DIRECTORY_SEPARATOR); // 绝对根目录例：/var/web/AdminX/

// 加载环境变量配置文件
if (is_file(ROOT_PATH . '.env')) {
    $env = parse_ini_file(ROOT_PATH . '.env', true);

    foreach ($env as $key => $val) {
        $name = ENV_PREFIX . strtoupper($key);

        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $item = $name . '_' . strtoupper($k);
                putenv("$item=$v");
            }
        } else {
            putenv("$name=$val");
        }
    }
}

// TpLog::init();
// TpLog::record('TpLog Test!!!', TpLog::INFO, true);
// TpLog::save();

// 多进程管理模块
$pool = new Process\Pool(6);
// 让每个OnWorkerStart回调都自动创建一个协程
$pool->set(['enable_coroutine' => true]);
$pool->on('workerStart', function ($pool, $id) {

    // 热加载需要在这里引入
    require_once 'common/function.php';

    // 这里reuse port重复利用端口一定要设置为true，否则会报绑定端口错误
    $server = new Server('0.0.0.0', '9502', false, true);

    $dsnConfig = [
        'DB_TYPE'    => 'mysql', // 数据库类型
        'DB_HOST'    => get_app_env('database.hostname'), // 服务器地址
        'DB_NAME'    => get_app_env('database.database'), // 数据库名
        'DB_USER'    => get_app_env('database.username'), // 用户名
        'DB_PWD'     => get_app_env('database.password'), // 密码
        'DB_PORT'    => get_app_env('database.hostport'), // 端口
        'DB_PREFIX'  => '', // 数据库表前缀
        'DB_CHARSET' => 'utf8', // 数据库编码
        'DB_DEBUG'   => true, // 数据库调试模式 开启后可以记录SQL日志
    ];
    $redisConfig = [
        'DATA_CACHE_PREFIX' => 'tplite_', //缓存前缀
        'DATA_CACHE_TYPE'   => 'Redis', //默认动态缓存为Redis
        'REDIS_RW_SEPARATE' => false, //Redis读写分离 true 开启
        'REDIS_HOST'        => get_app_env('redis.host'), //redis服务器ip，多台用逗号隔开；读写分离开启时，第一台负责写，其它[随机]负责读；
        'REDIS_PORT'        => get_app_env('redis.port'), //端口号
        'REDIS_TIMEOUT'     => 90, //KeepAlive超时时间
        'REDIS_PERSISTENT'  => false, //是否长连接
        'REDIS_AUTH'        => get_app_env('redis.auth'), //AUTH认证密码
    ];
    $logConfig = [
        'LOG_TYPE'             => 'File',
        'LOG_PATH'             => '',
        'LOG_RECORD'           => true, // 进行日志记录
        'LOG_EXCEPTION_RECORD' => true, // 是否记录异常信息日志
        'LOG_LEVEL'            => 'EMERG,ALERT,CRIT,ERR,WARN,NOTIC,INFO,DEBUG,SQL',  // 允许记录的日志级别
        // 'LOG_LEVEL'            => 'EMERG,ALERT,CRIT,ERR',  // 允许记录的日志级别
    ];

    C($dsnConfig);
    C($redisConfig);

    // print_r($dsnConfig);

    Swoole\Process::signal(SIGTERM, function () use ($server) {
        $server->shutdown();
    });

    $server->handle('/hello', function ($request, $response) use ($id) {
        $response->end($id .' => '. date('Y-m-d H:i:s'));
    });

    $server->handle('/debug', function ($request, $response) use (&$dsnConfig) {
        go(function () use ($request, $response, &$dsnConfig) {

            $microtime = microtime(true);
            $microtime = $microtime*10000;
            $dbLinkId  = intval(substr($microtime,8));

            // 每个协程配置一个PDO连接
            $dbConfig = $dsnConfig;
            $dbConfig['DB_LINKID'] = $dbLinkId; // 这个配置实际没什么作用只是让TpDb::getInstance返回不同的实例

            $tpMM = new TpMM($dbConfig);

            $tpMM->M('users', '')->where(['id' => 1])->find();
            $tpMM->freeM(); // TpMM中设置了析构函数自动处理,这里不确定是否需要显式调用

            ajaxReturn($response, ['time' => date('Y-m-d H:i:s')]);
            // $response->end(date('Y-m-d H:i:s'));
            return;
        });
    });


    $server->start();
});
$pool->start();
