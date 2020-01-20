<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\models\GenF;
use yii\helpers\Url;
use yii\helpers\Json;

class matrix extends Model
{

    public static function my_det($A){
        if(count($A)===1){
            return $A[array_keys($A)[0]][array_keys($A[array_keys($A)[0]])[0]];
        }else if(count($A)===2){
            return ($A[array_keys($A)[0]][array_keys($A[array_keys($A)[0]])[0]]*$A[array_keys($A)[1]][array_keys($A[array_keys($A)[1]])[1]] - $A[array_keys($A)[0]][array_keys($A[array_keys($A)[0]])[1]]*$A[array_keys($A)[1]][array_keys($A[array_keys($A)[0]])[0]]);
        }else{
            $a_dop = [];
            $det = 0;
            $cur_str = 0;
            $cur_stb = 0;
            foreach($A as $k1=>$v1){
                if($cur_str==0){
                    $cur_stb = 0;
                    foreach($v1 as $k2=>$v2){
                        $a_dop = $A;
                        foreach($a_dop as $k3=>$v3){
                            unset($a_dop[$k3][$k2]);
                        }
                        unset($a_dop[$k1]);
                        $det = 1*$det + 1*$A[$k1][$k2]*pow(-1, (1*$cur_str+1)+(1*$cur_stb+1))*self::my_det($a_dop);
                        $cur_stb = $cur_stb + 1;
                    }
                }
                $cur_str = $cur_str + 1;
            }
            return $det;
        }
    }

    // транспонирование матрицы
    public static function matrix_t($A){
        $AT = [];
        $old_matrix = $A;
        self::normalize_matrix($A);
        $cur_i = 0;
        foreach($old_matrix as $i => $v_i){
            $cur_j = 0;
            foreach($v_i as $j => $v_j){
                $AT[$j][$i] = $A[$cur_i][$cur_j];
                $cur_j = $cur_j + 1;
            }
            $cur_i = $cur_i + 1;
        }
        return $AT;
    }

// сложение матриц
    public static function matrix_sum($A,$B){
        $C = [];
        foreach ($A as $i => $v_i)
        { $C[$i] = [];
            foreach ($v_i as $j => $v_j){$C[$i][$j] = 1*$A[$i][$j] + 1*$B[$i][$j];}
        }
        return $C;
    }

// умножение матрицы на число
    public static function matrix_mult_number($a, $A)  // a - число, A - матрица (двумерный массив)
    {
        $B = [];
        foreach($A as $i=>$v_i)
        {$B[$i] = [];
            foreach($v_i as $j=>$v_j){$B[$i][$j] = $a*$A[$i][$j];}
        }
        return $B;
    }

// умножение матриц
    public static function matrix_multiply($A,$B){
        self::normalize_matrix($A);
        self::normalize_matrix($B);
        $colsA = count($A[array_keys($A)[0]]);
        $rowsB = count($B);
        $C = [];
        if ($colsA != $rowsB){return false;}
        foreach($A as $i => $v_i){$C[$i] = [];}
        foreach($B[array_keys($B)[0]] as $k => $v_k){
            foreach($A as $i => $v_i)
            {
                $t = 0;
                foreach($B as $j => $v_j){$t = $t + $A[$i][$j]*$B[$j][$k];}
                $C[$i][$k] = $t;
            }
        }
        return $C;
    }

// умножение матриц (соответствие ключей) A - ключ массива СТРОКА(['key1'=>[1,2], 'key2'=>[3,4]])!
// B - ключ массива СТОЛБЕЦ([['key3'=>5, 'key4'=>6], ['key3'=>7,'key4'=>8]])!
    public static function matrix_multiply_inkey($A,$B){
        $colsA = count($A[array_keys($A)[0]]);
        $rowsB = count($B);
        $C = [];
        if ($colsA != $rowsB){return false;}
        foreach($A as $i => $v_i){$C[$i] = [];}
        foreach($B[array_keys($B)[0]] as $k => $v_k){
            foreach($A as $i => $v_i)
            {
                $t = 0;
                foreach($B as $j => $v_j){$t = $t + $A[$i][$j]*$B[$j][$k];}
                $C[$i][$k] = $t;
            }
        }
        return $C;
    }

// возведение матрицы в степень
    public static function matrix_pow($n, $A){
        if ($n == 1){return $A;}
        else{
            return self::matrix_multiply($A, self::matrix_pow($n - 1, $A));
        }
    }

// определитель матрицы
    public static function matrix_determinant($A){
        self::normalize_matrix($A);
        $N = count($A);
        $B = [];
        $denom = 1;
        $exchanges = 0;
        for ($i = 0; $i < $N; ++$i)
        {$B[$i] = [];
            for ($j = 0; $j < $N; ++$j){$B[$i][$j] = $A[$i][$j];}
        }
        for($i = 0; $i < $N-1; ++$i)
        {
            $maxN = $i;
            $maxValue = 1*abs($B[$i][$i]);
            for ($j = $i+1; $j < $N; ++$j)
            {$value = 1*abs($B[$j][$i]);
                if (1*$value > 1*$maxValue){ $maxN = $j; $maxValue = $value; }
            }
            if (1*$maxN > 1*$i)
            {$temp = $B[$i]; $B[$i] = $B[$maxN]; $B[$maxN] = $temp;
                $exchanges = $exchanges + 1;
            }
            else {if($maxValue == 0){return $maxValue;}}
            $value1 = $B[$i][$i];
            for ($j = $i + 1; $j < $N; ++$j)
            {$value2 = $B[$j][$i];
                $B[$j][$i] = 0;
                for($k = $i + 1; $k < $N; ++$k) $B[$j][$k] = ($B[$j][$k]*$value1-$B[$i][$k]*$value2)/$denom;
            }
            $denom = $value1;
        }

        if (fmod($exchanges, 2)){
            return -1*$B[1*$N - 1][1*$N - 1];
        }else{
            if(1*$N > 0){
                return $B[1*$N - 1][1*$N - 1];
            }else{
                return false;
            }
        }

    }

    public static function normalize_matrix(&$A){
        $A_new = [];
        $cur_i = 0;
        foreach($A as $i => $v_i){
            $cur_j = 0;
            foreach($v_i as $j => $v_j){
                $A_new[$cur_i][$cur_j] = $v_j;
                $cur_j = $cur_j + 1;
            }
            $cur_i = $cur_i + 1;
        }
        $A = $A_new;
    }
    public static function inkey_matrix(&$A, $Old){
        $A_new = [];
        $cur_i = 0;
        foreach($Old as $i => $v_i){
            $cur_j = 0;
            foreach($v_i as $j => $v_j){
                $A_new[$i][$j] = 1*$A[$cur_i][$cur_j];
                $cur_j = $cur_j + 1;
            }
            $cur_i = $cur_i + 1;
        }
        $A = $A_new;
    }

// ранг матрицы
    public static function matrix_rank($A)
    {
        self::normalize_matrix($A);
        $m = count($A);
        $n = count($A[0]);
        $k = ($m < $n ? $m : $n);
        $r = 1;
        $rank = 0;
        while ($r <= $k)
        { $B = [];
            $cur_i = 0;
            foreach($A as $i => $v_i){
                if($cur_i < $r){
                    $B[$i] = [];
                }
                $cur_i = $cur_i + 1;
            }
            $cur_a = 0;
            foreach($A as $a => $v_a){
                if($cur_a < $m - $r + 1){
                    $cur_b = 0;
                    foreach($A as $b => $v_b){
                        if($cur_b < $n - $r + 1){
                            $cur_c = 0;
                            foreach($A as $c => $v_c)
                            {
                                if($cur_c < $r){
                                    $cur_d = 0;
                                    foreach($A as $d => $v_d){
                                        if($cur_d < $r){
                                            $B[$c][$d] = $A[1*$a + 1*$c][1*$b + 1*$d];
                                        }
                                        $cur_d = $cur_d + 1;
                                    }
                                }
                                $cur_c = $cur_c + 1;
                            }
                            if(self::matrix_determinant($B) != 0){$rank = $r;}
                        }
                        $cur_b = $cur_b + 1;
                    }
                }
                $cur_a = $cur_a + 1;
            }
            $r++;
        }
        return $rank;
    }

// союзная матрица
    public static function matrix_adjugate($A){
        $old_matrix = $A;
        self::normalize_matrix($A);
        $N = count($A);
        $adjA = [];
        for ($i = 0; $i < $N; $i++)
        { $adjA[$i] = [];
            for ($j = 0; $j < $N; $j++)
            {$B = [];
                $sign = ( fmod((1*$i + 1*$j), 2) == 0) ? 1 : -1;
                for ($m = 0; $m < $j; $m++)
                {$B[$m] = [];
                    for ($n = 0; $n < $i; $n++){$B[$m][$n] = 1*$A[$m][$n];}
                    for ($n = $i + 1; $n < $N; $n++){$B[$m][$n-1] = 1*$A[$m][$n];}
                }
                for ($m = $j + 1; $m < $N; $m++)
                {$B[$m-1] = [];
                    for ($n = 0; $n < $i; $n++){$B[$m - 1][$n] = 1*$A[$m][$n];}
                    for ($n = $i + 1; $n < $N; $n++){$B[$m - 1][$n - 1] = 1*$A[$m][$n];}
                }
                if(self::matrix_determinant($B) !== false){
                    $adjA[$i][$j] = $sign * 1*self::matrix_determinant($B);
                }else{
                    $adjA = false;
                }
            }
        }
        self::inkey_matrix($adjA, $old_matrix);
        return $adjA;
    }

// обратная матрица
    public static function matrix_inverse($A){
        $old_matrix = $A;
        self::normalize_matrix($A);
        $det = self::matrix_determinant($A);
        if($det == 0){return false;}
        $A_runtime = $A;
        $N = count($A);
        $A = self::matrix_adjugate($A);
        if($A === false || ( count(matrix::matrix_adjugate($A)) === 1 && count(matrix::matrix_adjugate($A)[array_keys($A)[0]]) === 1 )){
            $A = [[1/$A_runtime[0][0]]];
            self::inkey_matrix($A, $old_matrix);
            return $A;
        }else{
            for ($i = 0; $i < $N; $i++){
                for ($j = 0; $j < $N; $j++){$A[$i][$j] = 1*$A[$i][$j]/$det;}
            }
            self::inkey_matrix($A, $old_matrix);
            return $A;
        }
    }

// суммарное значение
    public static function arr_sum($A, $stb = null){
        $cur_sum = 0;
        if($stb === null){
            foreach($A as $k_1=>$v_1){
                if(is_array($A[$k_1]) === true){
                    if(is_array($A[$k_1][array_keys($A[$k_1])[0]]) === false){
                        if($A[$k_1][array_keys($A[$k_1])[0]]){
                            $cur_sum = $cur_sum + 1*$A[$k_1][array_keys($A[$k_1])[0]];
                        }
                    }
                }else{
                    $cur_sum = $cur_sum + 1*$A[$k_1];
                }
            }
        }else{
            foreach($A as $k_1=>$v_1){
                if(is_array($A[$k_1]) === true){
                    if(is_array($A[$k_1][$stb]) === false){
                        if($A[$k_1][$stb]){
                            $cur_sum = $cur_sum + 1*$A[$k_1][$stb];
                        }
                    }
                }
            }
        }
        return $cur_sum;
    }

// среднее значение
    public static function arr_avg($A, $stb = null){
        $cur_avg = 0;
        if($stb === null){
            if(is_array($A[array_keys($A)[0]]) === true){
                if(is_array($A[array_keys($A)[0]][array_keys($A[array_keys($A)[0]])[0]]) === false){
                    if($A[array_keys($A)[0]][array_keys($A[array_keys($A)[0]])[0]]){
                        $cur_avg = self::arr_sum($A)/count($A);
                    }
                }
            }else{
                $cur_avg = self::arr_sum($A)/count($A);
            }
        }else{
            if(is_array($A[array_keys($A)[0]]) === true){
                if(is_array($A[array_keys($A)[0]][$stb]) === false){
                    if($A[array_keys($A)[0]][$stb]){
                        $cur_avg = self::arr_sum($A, $stb)/count($A);
                    }
                }
            }
        }
        return $cur_avg;
    }

}
?>