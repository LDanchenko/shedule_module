<?php

abstract class Utils {
    public static function normalize($value, $minLimitValue, $maxLimitValue) {
        $maxLimitValue = $maxLimitValue - $minLimitValue; // тут странно  - передали 1 0 вернет 1

        if ($maxLimitValue == 0.0) {
            $maxLimitValue = 1.0;
        }

        return ($value - $minLimitValue) / $maxLimitValue; // тут примером 0.5/1 вернется то же самое - 0.5
    }

    //получили три коеф. по трем классам, и 0 и 1 - min max
    public static function normalizeCollection($values, $minLimitValue, $maxLimitValue) {
        $result = array();
        foreach ($values as $value) {
            $result[] = self::normalize($value, $minLimitValue, $maxLimitValue); //
        }

        return $result;
    }
	//делить полученный коефициент на определенный нами приоритет
    public static function applyWeightImpact($value, $weight) {
        return $value ** (1 / $weight); //** возведение в степень
    }

    public static function removeFromArrayByValue(&$array, $value) {
        if (($key = array_search($value, $array)) !== false) { //если находится элемент
            unset($array[$key]); //стереть
        }
    }

}
