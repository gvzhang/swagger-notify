<?php

/**
 * 获取GIT中的信息
 */

namespace App;

class TextProcess
{
    const ADD_METHOD_TIPS = "<span style='color:red;'>*****【新增接口】*****</span><br />";
    const DELETE_METHOD_TIPS = "<span style='color:#878B95;'>*****【删除接口】*****</span><br />";
    const MODIFY_METHOD_TIPS = "<span style='color:green;'>*****【修改接口】*****</span><br />";

    private $_command;
    private $_status;
    private $_log;
    private $_repoPath;
    private $_diffInfoList = [];
    private $_checkMethod = false;
    private $_nameJson = null;
    private $_httpMethod = null;
    private $_replacePattern = '/"description":\s*"(.*)?",(\\n)*\s*"(swagger|info|host|basePath|schemes|consumes|produces|paths|definitions|parameters|responses|securityDefinitions|security|tags|externalDocs)/';
    private $_apiNamePattern = '/[\-|\+]\s+\"\/(.*)?\":\s\{/';
    private $_numPattern = '/@{2}\s\-[0-9]+,[0-9]+\s\+([0-9]+),[0-9]+\s@{2}/';

    public function __construct($repoPath)
    {
        $this->_repoPath = $repoPath;
        if (empty($this->_repoPath) || !is_dir($this->_repoPath)) {
            throw new \InvalidArgumentException("repoPath Error");
        }
    }

    /**
     * 获取修改的接口
     * @param $diffLog
     * @return array
     */
    public function getLastDiffLine($diffLog)
    {
        // 防止重复匹配新增或删除的接口
        $matchResult = [];
        foreach ($diffLog as $key => $log) {
            preg_match_all($this->_numPattern, $log, $matchesModify, PREG_PATTERN_ORDER);
            if ($matchesModify[1]) {
                // 匹配是否为非方法（属性）修改
                if (!preg_match($this->_apiNamePattern, $diffLog[$key + 2]) && !preg_match($this->_apiNamePattern, $diffLog[$key + 3])) {
                    array_push($matchResult, $matchesModify[1][0]);
                }
            }
        }
        return $matchResult;
    }

    /**
     * 获取新增的接口
     * @param $diffLog
     * @return array
     */
    public function getAddMethodInfo($diffLog)
    {
        $matchKey = [];
        $matchKeyAll = [];
        foreach ($diffLog as $key => $log) {
            // 1、是否为行数字符串
            // 2、匹配是否为方法修改
            if (preg_match($this->_numPattern, $log)) {
                array_push($matchKeyAll, $key);

                if (preg_match('/@{2}\s\-[0-9]+,2\s\+([0-9]+),[1-9]([0-9]+)*\s@{2}/', $log)) {
                    if (preg_match($this->_apiNamePattern, $diffLog[$key + 2])) {
                        array_push($matchKey, $key);
                        // offset开始位置
                        $matchKey[$key] = 2;
                    }

                    //兼容最后一个接口的新增，最后一个符号需要加上","，会被看做修改行
                    if (preg_match($this->_apiNamePattern, $diffLog[$key + 3])) {
                        array_push($matchKey, $key);
                        // offset开始位置
                        $matchKey[$key] = 3;
                    }
                }
            }
        }

        if ($matchKey) {
            $matchResult = [];
            foreach ($matchKeyAll as $key => $match) {
                if (in_array($match, array_keys($matchKey))) {
                    if (isset($matchKeyAll[$key + 1])) {
                        $sliceLength = $matchKeyAll[$key + 1] - $match - 3;
                    } else {
                        $sliceLength = count($diffLog) - $match - 3;
                    }
                    $matchArr = array_slice($diffLog, $match + $matchKey[$match], $sliceLength);
                    $matchStr = implode(PHP_EOL, $matchArr);
                    $clearStr = rtrim(preg_replace('/\+\s/', '', $matchStr), ",");
                    $addTips = preg_replace($this->_replacePattern, "\"description\":\"" . self::ADD_METHOD_TIPS . "$1\",\"$3", $clearStr);
                    array_push($matchResult, json_decode("{" . $addTips . "}"));
                }
            }
            return $matchResult;
        } else {
            return [];
        }
    }

    /**
     * 获取删除的接口
     * @param $diffLog
     * @return array
     */
    public function getDeleteMethodInfo($diffLog)
    {
        $matchKey = [];
        $matchKeyAll = [];
        foreach ($diffLog as $key => $log) {
            // 1、是否为行数字符串
            // 2、匹配是否为方法修改
            if (preg_match($this->_numPattern, $log)) {
                array_push($matchKeyAll, $key);

                if (preg_match('/@{2}\s\-([0-9]+),[1-9]([0-9]+)*\s\+[0-9]+,2\s@{2}/', $log)) {
                    if (preg_match($this->_apiNamePattern, $diffLog[$key + 2])) {
                        array_push($matchKey, $key);
                        // offset开始位置
                        $matchKey[$key] = 2;
                    }

                    //兼容最后一个接口的删除，最后一个符号需要删除","，会被看做修改行
                    if (preg_match($this->_apiNamePattern, $diffLog[$key + 3])) {
                        array_push($matchKey, $key);
                        // offset开始位置
                        $matchKey[$key] = 3;
                    }
                }
            }
        }
        if ($matchKey) {
            $matchResult = [];
            foreach ($matchKeyAll as $key => $match) {
                if (in_array($match, array_keys($matchKey))) {
                    if (isset($matchKeyAll[$key + 1])) {
                        $sliceLength = $matchKeyAll[$key + 1] - $match - 3;
                    } else {
                        $sliceLength = count($diffLog) - $match - 3;
                    }
                    $matchArr = array_slice($diffLog, $match + $matchKey[$match], $sliceLength);
                    $matchStr = implode(PHP_EOL, $matchArr);
                    $clearStr = rtrim(preg_replace('/\-\s/', '', $matchStr), ",");
                    $addTips = preg_replace($this->_replacePattern, "\"description\":\"" . self::DELETE_METHOD_TIPS . "$1\",\"deprecated\":true,\"$3", $clearStr);
                    array_push($matchResult, json_decode("{" . $addTips . "}"));
                }
            }
            return $matchResult;
        } else {
            return [];
        }
    }

    /**
     * 获取差异API详情
     * @param Node $node
     * @return array
     */
    public function getDiffMethodInfo(Node $node)
    {
        $this->_getDiffMethodInfo($node->getData(), $node->getMethod());
        return $this->_diffInfoList;
    }

    private function _getDiffMethodInfo($data, $diffMethod)
    {
        foreach ($data as $key => $obj) {
            // API方法以及HTTP方法相同才计入差异
            if ($this->_checkMethod && ($key === $this->_httpMethod)) {
                $addTips = preg_replace($this->_replacePattern, "\"description\":\"" . self::MODIFY_METHOD_TIPS . "$1\",\"$3", json_encode($obj, JSON_UNESCAPED_UNICODE));
                array_push($this->_diffInfoList, json_decode(str_replace("##DETAIL##", '"' . $key . '":' . $addTips, $this->_nameJson)));
                $this->_checkMethod = false;
                $this->_nameJson = "";
                $this->_httpMethod = "";
            }
            if ($key && in_array($key, array_keys($diffMethod))) {
                $this->_checkMethod = true;
                $this->_nameJson = '{"' . $key . '":{##DETAIL##}}';
                $this->_httpMethod = $diffMethod[$key];
            }
            if (is_object($obj) || is_array($obj)) {
                $this->_getDiffMethodInfo($obj, $diffMethod);
            }
        }
    }

    /**
     * 获取改动的文件
     * @return mixed
     */
    public function getChangFiles()
    {
        $fileList = [];
        $matchFile = "";
        $diffLog = $this->_getDiff();
        $logDiff = false;
        foreach ($diffLog as $key => $log) {
            // 匹配差异JSON文件
            $matchRes = preg_match_all('/diff\s--git\sa\/(.*)?\s/', $log, $matches, PREG_PATTERN_ORDER);
            if ($matchRes) {
                $logDiff = false;
                $file = pathinfo($matches[1][0]);
                // 新增文件、删除文件、重命名文件不做比较
                if (strtolower($file["extension"]) == "json"
                    && !preg_match('/deleted\sfile\smode/', $diffLog[$key + 1])
                    && !preg_match('/new\sfile\smode/', $diffLog[$key + 1])
                    && !preg_match('/similarity\s/', $diffLog[$key + 1])
                ) {
                    $logDiff = true;
                    $matchFile = $file['basename'];
                    $fileList[$matchFile] = [];
                }
            }

            if ($logDiff) {
                array_push($fileList[$matchFile], $log);
            }
        }
        return $fileList;
    }

    /**
     * 获取差异文本
     * @return mixed
     */
    private function _getDiff()
    {
        if ($this->_log) {
            return $this->_log;
        }
        $cmd[] = sprintf('cd ' . $this->_repoPath);
        $cmd[] = sprintf('git diff HEAD^ HEAD --unified=1');
        $command = join(' && ', $cmd);
        $this->_runLocalCommand($command);
        return $this->_log;
    }

    private function _runLocalCommand($command)
    {
        $command = trim($command);
        Logger::write('---------------------------------');
        Logger::write('---- Executing: $ ' . $command);

        $status = 1;
        $log = '';

        exec($command . ' 2>&1', $log, $status);
        // 执行过的命令
        $this->_command = $command;
        // 执行的状态
        $this->_status = !$status;
        // 操作日志
        $this->_log = $log;

        Logger::write(implode(PHP_EOL, $log));
        Logger::write('---------------------------------');

        return $this->_status;
    }

    public function getCommand()
    {
        return $this->_command;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function getLog()
    {
        return $this->_log;
    }
}