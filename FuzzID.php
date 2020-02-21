<?php

class ID
{

    private $length = 5;
    private $secure = '2786a0ca60c30c97ab7242e85a611118';
    private $codes  = '123456789ABCDEFGHJKMNPQRSTUVWXYZ';

    private $validator_index = 0; //校验码所在位置(不能大于等于$length),不需要时可设为-1.

    public function encode($id)
    {
        $length_num = ($this->validator_index > -1) ? $this->length - 1 : $this->length;
        $length_all = $this->length;
        //获取原始数的二进制数
        $id_bin    = $this->fillZero($this->to2($id), $length_num * 5);
        $id_blocks = [];
        for ($i = 0; $i < $length_num; $i++) $id_blocks[] = substr($id_bin, $i * 5, 5);
        //计算最后的BLOCK
        $main_dec      = $this->to10($id_blocks[$length_num - 1]);
        $numbers_dec[] = $main_dec;
        //循环计算每个BLOCK
        for ($i = $length_num - 2; $i >= 0; $i--) {
            $block_original = $id_blocks[$i];
            $block_operator = $this->fillZero($this->to2($this->getSecureByIndex($main_dec)), 4);
            for ($j = 0; $j < 4; $j++) {
                if ('1' === $block_operator[$j]) {
                    $block_original[$j] = ('0' === $block_original[$j]) ? '1' : '0';
                }
            }
            //拼接每个结果
            $numbers_dec[] = $this->to10($block_original);
            //为下个作准备
            $main_dec++;
        }
        $numbers_dec = array_reverse($numbers_dec);
        //结合校验码输出结果
        $num_index  = 0;
        $final_code = '';
        for ($i = 0; $i < $length_all; $i++) {
            if ($i === $this->validator_index) { //计算校验码
                $sum        = array_sum($numbers_dec);
                $remainder  = base_convert($this->secure[0], 16, 10);
                $result     = 32 - (($sum + (int)$remainder) % 32);
                $final_code .= $this->getCodeByIndex($result);
            } else { //计算数值码
                $final_code .= $this->getCodeByIndex($numbers_dec[$num_index]);
                $num_index++;
            }
        }
        return $final_code;
    }

    public function decode($code)
    {
        $length_num = ($this->validator_index > -1) ? $this->length - 1 : $this->length;
        $length_all = $this->length;
        //获取每个BLOCK的十进制数
        $blocks_dec  = [];
        $numbers_dec = [];
        for ($i = 0; $i < $length_all; $i++) {
            $index = $this->getIndexByCode($code[$i]);
            if ($i === $this->validator_index) { //校验位
                $blocks_dec[] = $index;
            } else {
                $blocks_dec[]  = $index;
                $numbers_dec[] = $index;
            }
        }
        //判断校验区
        if ($this->validator_index > -1) {
            $sum       = array_sum($blocks_dec);
            $secure  = base_convert($this->secure[0], 16, 10);
            $remainder = ($sum + (int)$secure) % 32;
            if ($remainder !== 0 ) return false;
        }
        //转换数值区
        $numbers_count = count($numbers_dec);
        $main_dec      = $numbers_dec[$numbers_count - 1];
        $id_blocks[]   = $this->fillZero($this->to2($main_dec), 5);
        for ($i = count($numbers_dec) - 2; $i >= 0; $i--) {
            $block_result   = $this->fillZero($this->to2($numbers_dec[$i]), 5);
            $block_operator = $this->fillZero($this->to2($this->getSecureByIndex($main_dec)), 4);
            for ($j = 0; $j < 4; $j++) {
                if ('1' === $block_operator[$j]) {
                    $block_result[$j] = ('0' === $block_result[$j]) ? '1' : '0';
                }
            }
            //拼接每个结果
            $id_blocks[] = $block_result;
            //为下个作准备
            $main_dec++;
        }
        $id_blocks = array_reverse($id_blocks);
        $id_bin    = '';
        foreach ($id_blocks as $bin) $id_bin .= $bin;
        return $this->to10($id_bin);
    }

    private function fillZero($str, int $length)
    {
        $str        = '' . $str;
        $str_length = strlen($str);
        if ($str_length > $length) return false; //超出长度
        //循环每位补零
        for ($i = $str_length; $i < $length; $i++) {
            $str = '0' . $str;
        }
        return $str;
    }

    private function to10($bin)
    {
        $out = base_convert($bin, 2, 10);
        return $out;
    }

    private function to2($dec)
    {
        $out = base_convert($dec, 10, 2);
        return $out;
    }

    private function getCodeByIndex($index)
    {
        $i = $index % 32;
        return $this->codes[$i];
    }

    private function getSecureByIndex($index)
    {
        $i = $index % 32;
        return $this->secure[$i];
    }

    private function getIndexByCode($code)
    {
        return strpos($this->codes, $code);
    }

}

$ids = new ID();
for ($i = 1; $i < 100; $i++) {
    $code = $ids->encode($i);
    $str   = $ids->decode($code);
    echo "$i:" . $code . '|' . $str;
    if ($i != $str) echo '------------------------FALSE';
    echo "<br>";
}

