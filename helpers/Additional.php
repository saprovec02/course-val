<?php

namespace app\helpers;

use Yii;

class Additional
{
    public static function getRound($value, $precision = 4)
    {
        return round($value,$precision);
    }

}