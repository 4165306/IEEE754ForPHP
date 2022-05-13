<?php

/**
 * IEEE754规范的二进制转双精度小数 
 * 可参考本类继续实现单精度小数、小数转16进制等
 * 更多参考可参照此链接:https://segmentfault.com/a/1190000041768195/
 * 非常感谢`冰封百度`的文章指点
 */

 /**
  * IEEE754规则简介
  * 原16进制：405E4C42C0000000
  * 转换成二进制： 100000001011110010011000100001011000000000000000000000000000000
  * 补0至64位: 0100000001011110010011000100001011000000000000000000000000000000
  * 分为3个部分(IEEE754规范要求)
  *     符号位置: 0 （0为正数，1为负数）
  *     指数位置: 1000000010
  *     尾数位置: 11110010011000100001011000000000000000000000000000000
  * 指数转整数: 1*(2^10)+0*(2^9)+0*(2^8)+...+1*(2^1)+0*(2^0) - 1023
  * 尾数补充变科学计数: 1.11110010011000100001011000000000000000000000000000000 * 2^指数
  * 尾数: 1111100.10011000100001011000000000000000000000000000000 向右移动.到什么位置取决于指数是多少 此数据假设指数为6
  * 整数部分: 二进制转10进制(1111100)
  * 小数部分: 1x(1/(2^1))+0x(1/(2^2))+0x(1/(2^3))+...+0x(1/(2^47))
  * 结果： 整数+小数
  */
class IEEE754
{
    /**
     * @param string $hex
     * @return float
     */
    public function hex64ToDouble(string $hex): float
    {
        $bin = base_convert($hex, 16, 2);
        // 填充至64位 右侧补0
        $bin = str_pad($bin, 64, '0', STR_PAD_LEFT);
        if (strlen($bin) !== 64) {
            // 此处请改写为自定义异常抛出 或根据自己喜好返回false
            throw new IncorrectFormatException('Super long of hex:' . $hex);
        }
        // 符号位置
        $S = $bin[0];
        // 指数位置
        $E = substr($bin, 1, 11);
        // M部分需要按照规定前边补1. 形成1.01100101格式
        $M = '1.' . substr($bin, 12, strlen($bin));
        $index = $this->getIndex($E);
        // 分离整数小数
        $numArr = explode('.', $this->moveDecimalPointToRight($M, $index));
        $I = bindec($numArr[0]);
        $F = $numArr[1];
        $float = $this->convertFloat($F);
        // 对正负进行判断
        if ($S === '0') {
            return $I + $float;
        }
        return floatval('-' . ($I + $float));
    }

    /**
     * 指数计算
     */
    private function getIndex(string $e): float|int
    {
        $int = 0;
        for ($i = 0; $i < strlen($e); ++$i) {
            $int += $e[$i] * (pow(2, 10 - $i));
        }
        $int -= 1023;
        return $int;
    }

    /**
     * 移动小数点.
     */
    private function moveDecimalPointToRight(string $m, int $index): string
    {
        $startPoint = 0;
        $num = $m;
        for ($i = 0; $i < strlen($m); ++$i) {
            if ($startPoint !== 0) {
                ++$startPoint;
            }
            if ($m[$i] === '.') {
                $startPoint = $i;
                $num = substr_replace($num, '', $i, 1);
            }
            if ($startPoint == $index) {
                $num = substr_replace($num, '.', ++$i, 0);
                break;
            }
        }
        return $num;
    }

    /**
     * 转换小数部分
     */
    private function convertFloat(string $f): float
    {
        $float = 0;
        for ($j = 0; $j < strlen($f); ++$j) {
            $float += $f[$j] * (1 / pow(2, $j + 1));
        }
        return $float;
    }
}