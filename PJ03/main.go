package main

import (
	"flag"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"time"
)

var recycleBinPath string
var deleteLogPath string
var counterNum int

func init() {
	flag.Parse()
	counterNum = 0
	recycleBinPath = "/tmp/.trash"
	deleteLogPath = "/tmp/grm.log"
	// 检查目录是否存在
	_, err := os.Stat(recycleBinPath)
	if os.IsNotExist(err) {
		// 目录不存在，创建目录
		err := os.MkdirAll(recycleBinPath, 0755)
		if err != nil {
			fmt.Printf("创建回收站目录失败: %s\n", err)
			os.Exit(1)
		}
	} else if err != nil {
		fmt.Printf("获取回收站目录信息失败: %s\n", err)
		os.Exit(1)
	}
}

func writeLog(message string) {
	currentTime := time.Now()
	formattedTime := currentTime.Format("2006-01-02 15:04:05")
	logInfo := fmt.Sprintf("%s %s\n", formattedTime, message)
	file, _ := os.OpenFile(deleteLogPath, os.O_APPEND|os.O_CREATE|os.O_WRONLY, 0644)
	defer file.Close()
	file.WriteString(logInfo)
}

func moveToRecycleBin(filePath string) (error, string) {
	counterNum++
	currentTime := time.Now()
	formattedTime := currentTime.Format("2006-01-02_150405")
	formattedTime = fmt.Sprintf("%s.%d", formattedTime, counterNum)

	fileName := filepath.Base(filePath)
	recyclePath := filepath.Join(recycleBinPath, fileName+"."+formattedTime)

	absPath, err := filepath.Abs(filePath)
	if err != nil {
		return err, recyclePath
	}

	writeLog(fmt.Sprintf("%s %s", recyclePath, absPath))
	// fmt.Println(fileName)
	// fmt.Println(recyclePath)
	return os.Rename(filePath, recyclePath), recyclePath
	// return nil
}

func deleteFiles(pattern string) error {
	files, err := filepath.Glob(pattern)
	if err != nil {
		return err
	}

	for _, file := range files {
		err, recyclePath := moveToRecycleBin(file)
		if err != nil {
			fmt.Printf("移动文件到回收站失败!!!: %s\n", err)
			continue
		}
		fmt.Printf("已将文件移动到回收站: %s\n", recyclePath)
	}

	return nil
}

func main() {
	// fmt.Println(flag.Args())
	fmt.Printf("删除日志记录方便文件恢复: %s\n", deleteLogPath)
	// forbidArr := []string{"/", "/bin", "/boot", "/data", "/dev", "/etc", "/home", "/lib", "/lib64", "/log", "/lost+found", "/media", "/mnt", "/opt", "/proc", "/root", "/run", "/sbin", "/srv", "/sys", "/tmp", "/usr", "/var", "/www", "/wwwroot", "/web"}
	for _, arg := range flag.Args() {
		absPath, err := filepath.Abs(arg)
		if err != nil {
			fmt.Println("无法获取绝对路径: ", err)
			continue
		}
		// if StringInArray(absPath, forbidArr) {
		// 	fmt.Printf("跳过危险的删除!!!: %s\n", absPath)
		// 	continue
		// }
		if strings.Count(absPath, "/") == 1 {
			fmt.Printf("跳过危险的删除!!!: %s\n", absPath)
			continue
		}
		if strings.Contains(arg, "*") || strings.Contains(arg, "?") {
			//!!! 通配符的条件执行不到,Linux系统会自动解析通配符为多个参数.
			err := deleteFiles(arg)
			if err != nil {
				fmt.Printf("移动文件到回收站失败!!!: %s\n", err)
			}
		} else {
			err, recyclePath := moveToRecycleBin(arg)
			if err != nil {
				fmt.Printf("移动文件到回收站失败!!!: %s\n", err)
			} else {
				fmt.Printf("已将文件移动到回收站: %s\n", recyclePath)
			}
		}
	}
	fmt.Println("文件删除成功")
}

// StringInArray 函数用于判断字符串是否存在于数组中
func StringInArray(str string, arr []string) bool {
	for _, item := range arr {
		if item == str {
			return true
		}
	}
	return false
}