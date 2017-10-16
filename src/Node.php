<?php

/**
 * Swagger的JSON文档处理
 */

namespace App;

class Node
{
    private $_data;
    private $_changeRow;
    private $_row;
    private $_idx;
    private $_parent;
    private $_diffParent;
    private $_defRow;

    public function __construct($data, $changeRow = null)
    {
        $this->_data = $data;
        $this->_changeRow = $changeRow;
        if (empty($this->_data)) {
            throw new \Exception("data error");
        }
        $this->_parentListInit();
        $this->_defRefInit();
    }

    /**
     * 获取差异层级
     * 注意：差异在相同的方法中会分开返回
     */
    public function getParentList()
    {
        $this->_getParentList($this->_data, $this->_changeRow);
        $resDiffParent = $this->_diffParent;
        $this->_parentListInit();

        $resRefDiffParent = [];
        foreach ($resDiffParent as $key => $diff) {
            $defRefRes = $this->_getDefRef($this->_data, $diff, $key);
            foreach ($defRefRes as $k => $r) {
                $resRefDiffParent[$k] = $r;
            }
        }
        ksort($resRefDiffParent);
        return $resRefDiffParent;
    }

    /**
     * 清理类变量
     */
    private function _parentListInit()
    {
        $this->_parent = [];
        $this->_diffParent = [];
        $this->_row = 1;
        $this->_idx = 0;
    }

    /**
     * 清理类变量
     */
    private function _defRefInit()
    {
        $this->_row = 1;
        $this->_defRow = 0;
    }

    /**
     * 递归获取引用MODEL的关联层级
     * @param $data
     * @param $diff
     * @param $diffRow
     * @return array
     */
    private function _getDefRef($data, $diff, $diffRow)
    {
        if ($diff[0] == "definitions") {
            $modelName = $diff[1];
            $refModelName = "#/definitions/" . $modelName;
            $this->_defRow($data, $refModelName);
            $refRow = $this->_defRow;
            $this->_defRefInit();

            // 获取关联MODEL的方法差异层级
            $this->_getParentList($data, [$refRow]);
            $refResDiffParent = $this->_diffParent;
            $refResDiffParentKeys = array_keys($refResDiffParent);
            $this->_parentListInit();

            return $this->_getDefRef($data, current($refResDiffParent), $refResDiffParentKeys[0]);
        }
        return [$diffRow => $diff];
    }

    /**
     * 获取引用的行数
     * @param $data
     * @param $refModelName
     */
    private function _defRow($data, $refModelName)
    {
        foreach ($data as $key => $obj) {
            $this->_row++;
            if ($refModelName === $obj) {
                $this->_defRow = $this->_row;
            }

            if (is_object($obj) || is_array($obj)) {
                $this->_defRow($obj, $refModelName);
            }
        }
        $this->_row++;
    }

    /**
     * 获取指定行数的层级
     * @param $data
     * @param $changeRow
     */
    private function _getParentList($data, $changeRow)
    {
        foreach ($data as $key => $obj) {
            $this->_row++;

            $this->_parent[$this->_idx] = $key;
            if (in_array($this->_row, $changeRow)) {
                $this->_diffParent[$this->_row] = $this->_parent;
            }

            if (is_object($obj) || is_array($obj)) {
                $this->_idx++;
                $this->_getParentList($obj, $changeRow);
            }
        }
        $this->_row++;
        if (in_array($this->_row, $changeRow)) {
            $this->_diffParent[$this->_row] = $this->_parent;
        }
        $this->_idx--;
    }

    /**
     * 获取差异API
     */
    public function getApiContent()
    {
        $changeMethod = [];
        $parentList = $this->getParentList();
        foreach ($parentList as $parent) {
            $path = false;
            foreach ($parent as $key => $val) {
                if ($path) {
                    $changeMethod[$val] = $parent[$key + 1];
                    break;
                }
                if (strtolower($val) == "paths") {
                    $path = true;
                }
            }
        }
        return $changeMethod;
    }

    /**
     * 在definitions属性中，获取关联MODEL的JSON数据
     * @param $refName
     * @return mixed
     */
    public function getRefModelInfo($refName)
    {
        if (isset($this->_data->definitions) && !empty($this->_data->definitions)) {
            $ret = false;
            foreach ($this->_data->definitions as $key => $obj) {
                if ($key === $refName) {
                    $ret = $obj;
                }
            }
            return $ret;
        } else {
            return false;
        }
    }

    public function getParent()
    {
        return $this->_parent;
    }

    public function setChangeRow($changeRow)
    {
        $this->_changeRow = $changeRow;
    }

    public function getRow()
    {
        return $this->_row;
    }

    public function getData()
    {
        return $this->_data;
    }
}