<?php

/**
 * Swagger的JSON文档处理
 */

namespace App;

class Node
{
    const MODIFY_METHOD_TIPS = "<span style='color:green;'>*****【修改接口】*****</span><br />";

    private $_data;
    private $_changeRow;
    private $_row = 1;
    private $_idx = 0;
    private $_parent = [];
    private $_diffParent = [];
    private $_diffInfoList = [];

    public function __construct($data, $changeRow = null)
    {
        $this->_data = $data;
        $this->_changeRow = $changeRow;
        if (empty($this->_data)) {
            throw new \Exception("data error");
        }
    }

    /**
     * 获取差异层级
     * 注意：差异在相同的方法中会分开返回
     */
    public function getParentList()
    {
        $this->_getParentList($this->_data);
        return $this->_diffParent;
    }

    private function _getParentList($data)
    {
        foreach ($data as $key => $obj) {
            $this->_row++;

            $this->_parent[$this->_idx] = $key;
            if ($this->_isChangeRow($this->_row)) {
                array_push($this->_diffParent, $this->_parent);
            }

            if (is_object($obj) || is_array($obj)) {
                $this->_idx++;
                $this->_getParentList($obj);
            }
        }
        $this->_row++;
        if ($this->_isChangeRow($this->_row)) {
            array_push($this->_diffParent, $this->_parent);
        }
        $this->_idx--;
    }

    /**
     * 获取差异API
     */
    public function getMethod()
    {
        $changeMethod = [];
        $parentList = $this->getParentList();
        foreach ($parentList as $parent) {
            $path = false;
            foreach ($parent as $val) {
                if ($path) {
                    array_push($changeMethod, $val);
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
     * 获取差异API详情
     */
    public function getDiffMethodInfo()
    {
        $diffMethod = $this->getMethod();
        $this->_getDiffMethodInfo($this->_data, $diffMethod);
        return $this->_diffInfoList;
    }

    private function _getDiffMethodInfo($data, $diffMethod)
    {
        foreach ($data as $key => $obj) {
            if ($key && in_array($key, $diffMethod)) {
                $addTips = preg_replace('/"description":"(.*?)"/', '"description":"' . self::MODIFY_METHOD_TIPS . '$1"', json_encode($obj, JSON_UNESCAPED_UNICODE), 1);
                array_push($this->_diffInfoList, json_decode('{"' . $key . '":' . $addTips . '}'));
            }
            if (is_object($obj) || is_array($obj)) {
                $this->_getDiffMethodInfo($obj, $diffMethod);
            }
        }
    }

    private function _isChangeRow($row)
    {
        return in_array($row, $this->_changeRow);
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
}