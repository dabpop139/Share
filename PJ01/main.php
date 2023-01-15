#!/usr/bin/env php
<?php
// namespace app\phpjobs;

date_default_timezone_set('Asia/Shanghai');

class PHPCronController
{
    // 脚本目录
    private $runnerScript;

    // 命令集合
    private $cronJobs = [];

    private $loger = null;

    /**
     * @param array $cronJobs
     * @param $loger
     */
    public function init($cronJobs = [], $loger = null)
    {
        // 设置yii脚本目录，这里在最后添加了一个空格，方便后面与命令进行拼接
        // $this->runnerScript = dirname(dirname(__DIR__)) . '/yii ';
        // $this->runnerScript = 'php ';
        // 下面是要执行的计划任务，注意时间是不补0的数字
        $this->cronJobs = $cronJobs;
        $this->loger = $loger;
    }

    public function actionRun()
    {
        // 格式化当前时间戳并转成 分 时 日 月 周 格式
        /*
        |i  有前导零的分钟数 00 到 59>
        |G  小时，24 小时格式，没有前导零 0 到 23
        |j  月份中的第几天，没有前导零 1 到 31
        |n  数字表示的月份，没有前导零 1 到 12
        |w  星期中的第几天，数字表示 0（表示星期天）到 6（表示星期六）
        */
        $now = explode(' ', date('i G j n w', time()));
        $raw = $this->parseCron($this->cronJobs);
        // print_r($raw);
        foreach ($raw as $command => $cron) {
            // 上面已经列出了所有的情况，所以当前时间循环时如果有一项不符合则不能向下执行
            foreach ($now as $k => $piece) {
                // 去掉前导0
                if (strlen($piece) > 1) {
                    $piece = ltrim($piece, '0');
                    if ($piece == '') {
                        $piece = '0';
                    }
                }
                if (!in_array($piece, $raw[$command][$k])) {
                    continue 2;
                }
            }
            // 下面是调用系统函数执行shell命令
            $this->runCommandBackground($command);
        }
    }

    /**
     * 解析需要执行的命令
     * @param $cronJobs
     * @return array
     */
    public function parseCron($cronJobs)
    {
        // 解析后的数组
        $raw = [];
        foreach ($cronJobs as $command => $cron) {
            // $command -> hello/index  $cron -> */5 * * * *
            // 将命令用空格分割成数组
            $cronArr = explode(' ', $cron, 5); // ['*/5', '*', '*', '*', '*']
            // 针对每一个位置进行解析
            $dimensions = [
                [0, 59], //Minutes
                [0, 23], //Hours
                [1, 31], //Days
                [1, 12], //Months
                [0, 6],  //Weekdays
            ];
            foreach ($cronArr as $key => $item) {
                // 标记是哪种命令格式，通过使用的crontab命令可以分为两大类
                // 1.每几分钟或每小时这样的 */10 * * * *
                // 2.几点几分这样的 10,20,30-50 * * * *

                // 去掉前导0
                if (strlen($item) > 1) {
                    $item = ltrim($item, '0');
                    if ($item == '') {
                        $item = '0';
                    }
                }

                list($repeat, $every) = explode('/', $item, 2) + [false, 1];
                if ($repeat === '*') {
                    $raw[$command][$key] = range($dimensions[$key][0], $dimensions[$key][1]);
                } else {
                    // 处理逗号拼接的命令
                    $tmpRaw = explode(',', $item);
                    foreach ($tmpRaw as $tmp) {
                        // 处理10-20这样范围的命令
                        $tmp = explode('-', $tmp, 2);
                        if (count($tmp) == 2) {
                            if (isset($raw[$command][$key])) {
                                $raw[$command][$key] = array_merge($raw[$command][$key], range($tmp[0], $tmp[1]));
                            } else {
                                $raw[$command][$key] = range($tmp[0], $tmp[1]);
                            }
                        } else {
                            $raw[$command][$key][] = $tmp[0];
                        }
                    }
                }
                // 判断*/10 这种类型的
                if ($every > 1) {
                    foreach ($raw[$command][$key] as $k => $v) {
                        if ($v % $every != 0) {
                            unset($raw[$command][$key][$k]);
                        }
                    }
                }
            }
        }
        return $raw;
    }

    /**
     * 以守护进程模式执行命令
     * @param $command
     */
    public function runCommandBackground($command)
    {
        $commandScript = $command . ' &';
        if ($this->loger) {
            try {
                $this->loger->log('notice', 'CMD: ' . $commandScript);
            } catch (\Exception $exception) {

            }
        }
        // var_dump($commandScript);
        @system($commandScript);
    }
}

class Loger
{
    private $filepath = '';

    /**
     * Loger constructor.
     * @param string $filepath
     */
    public function __construct($filepath = '')
    {
        $this->filepath = $filepath;
    }

    public function log($level, $message)
    {
        $fname = date('Y-m-d').'.log';
        if (is_array($message)) {
            $message = json_encode($message);
        }
        $mess = 'LOG TYPE[' . strtoupper($level) . '] ' . date('Y-m-d H:i:s') . ' ' . $message . PHP_EOL;
        file_put_contents($this->filepath.$fname, $mess, FILE_APPEND);
    }
}

// 获取Linux Ps进程数
function unixGetProcessNums($psname)
{
    $outstr = shell_exec('ps -ef|grep '.$psname.'|grep -v grep');
    $outarr = explode(PHP_EOL, $outstr);
    $hasNum = 0;
    foreach ($outarr as $item) {
        if (trim($item) != '') {
            $hasNum++;
        }
    }
    return $hasNum;
}

//+++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++
// $now = explode(' ', date('i G j n w', time()));
// var_dump($now);
// die;

$phpbin = '/usr/local/php/bin/php';
$runpath = '';
$logpath = __DIR__.'/logs/'; // 后面的/不能少

if (!is_dir($logpath)) {
    mkdir($logpath, 0755, true);
}

$preCronJobs = [
    '*/1 * * * * | 01-test' => $phpbin . ' ' . $runpath . ' 01-test.php Admin/index',
];
//+++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++
//+++++++++++++++++++++++++++++++++++++++
$loger = new Loger($logpath);

$cronJobs = [];
foreach ($preCronJobs as $key => $cmd) {
    $keyArr   = explode('|', $key, 2);
    $name     = trim($keyArr[0]);
    $period   = trim($keyArr[1]);
    $hashname = '_' . md5(strtolower($name)) . '_';

    // 判断进程避免重复执行
    if (unixGetProcessNums($hashname) > 0) {
        $loger->log('warning', 'Job cancel, previous job still run');
        continue;
    }
    $cronJobs[$cmd.' '.$hashname.' > '.$logpath.'phpcron_'.$name.'.log 2>&1'] = $period;
}


$phpCrontab = new PHPCronController();
// $result = $phpCrontab->parseCron($cronJobs);
// var_dump($result);
$phpCrontab->init($cronJobs, $loger);
$phpCrontab->actionRun();