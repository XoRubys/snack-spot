<?php

namespace SnackSpot\Utils;

class PriceFormatter
{
    /**
     * 格式化价格为两位小数字符串
     * @param float|int|string $price 价格
     * @return string 格式化后的价格字符串，如 5.00, 3.30
     */
    public static function format($price)
    {
        return number_format((float)$price, 2, '.', '');
    }

    /**
     * 格式化价格数组中的指定字段
     * @param array $data 数据数组
     * @param array $fields 需要格式化的字段名
     * @return array 格式化后的数组
     */
    public static function formatFields(array $data, array $fields)
    {
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = self::format($data[$field]);
            }
        }
        return $data;
    }
}
