<?php

$gtin = '78935761';

if (gtinIsValid($gtin)) {
    echo "Valido!";
    die;
} 
echo "INVALIDO";

function gtinIsValid($gtin)
{
    $num = str_pad($gtin, 18, '0', STR_PAD_LEFT);
    $dv = substr($num, -1);
    $num = substr($num, 0, 17);
    $factor = 3;
    $sum = 0;
    $values = str_split($num, 1); 
    foreach($values as $value) {
        $mult = ($factor * $value);
        $sum += $mult;
        if ($factor == 3) {
            $factor = 1;
        } else {
            $factor = 3;
        }
    }
    $mmc = (ceil($sum/10))*10;
    $mod = $mmc - $sum;
    if ($dv == $mod) {
        return true;
    }
    return false;
}