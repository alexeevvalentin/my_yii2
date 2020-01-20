<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\models\matrix;
use app\models\GenF;
use yii\helpers\Url;
use yii\helpers\Json;

Yii::$app->view->registerCss(
    ".correlation_table_style{border-collapse:collapse;}".
    ".correlation_td_style{text-align:center;border:1px solid grey;padding:3px;}".
    ".correlation_span_top{font-weight:bold;color:#0539A9;}".
    ".correlation_span_middle{}".
    ".correlation_span_bottom{font-weight:bold;color:#F45B04;}".
    ".span_value{font-size:16px;font-style:normal;color:#FF9A18;font-weight:bold;}".
    ".span_value_true{font-size:16px;font-style:normal;color:#258A31;font-weight:bold;}".
    ".span_value_false{font-size:16px;font-style:normal;color:#FF5C5C;font-weight:bold;}".
    ".span_value_true_comment{font-size:12px;font-style:italic;color:#1F7729;font-weight:bold;}".
    ".span_value_false_comment{font-size:12px;font-style:italic;color:#8E3131;font-weight:bold;}".
    ".div_comment{font-size:12px;font-style:italic;color:#3A78D7;}".
    ".div_step{padding-left:30px;}".
    ".name_section{text-decoration:underline;padding-top:8px;padding-bottom:3px;}".
    ".name_part{font-style:italic;padding-top:16px;padding-bottom:3px;}".
    ".name_analisys{font-weight:bold;padding-bottom:8px;}".
    ".uravn_regression_color{color:#FF9A18;}".
    ".description_coef_reg{color:#3A78D7;padding-top:3px;}".
    ".hr{width:100%;border-top:1px solid grey;margin-top:3px;margin-bottom:3px;}".
    ".span_bold{font-weight:bold;}"
);

class regression extends Model
{

    // calculation
    public static function calculate_correlation_analisys($Y, $Z){
        $X_matrix = [];
        $Y_matrix = [];
        $X_detect_monodata = [];
        foreach($Z as $in_str=>$val_str){
            foreach($val_str as $in_col=>$val_col){
                $cur_avg = matrix::arr_avg($Z, $in_col);
                $X_matrix[$in_str][$in_col] = 1*$Z[$in_str][$in_col] - 1*$cur_avg;
                $X_detect_monodata[$in_col] = 1*$X_detect_monodata[$in_col] + abs(1*$X_matrix[$in_str][$in_col]);
            }
        }

        foreach($X_detect_monodata as $k=>$v){
            if(1*$v === 0){
                foreach($X_matrix as $k1=>$v1){
                    unset($X_matrix[$k1][$k]);
                    unset($Z[$k1][$k]);
                }
            }
        }

        $cur_avg = matrix::arr_avg($Y, array_keys($Y[array_keys($Y)[0]])[0]);

        $Q2y = 0;
        $n = 0;
        foreach($Y as $in_str=>$val_str){
            $Y_matrix[$in_str][array_keys($Y[array_keys($Y)[0]])[0]] = 1*$Y[$in_str][array_keys($Y[array_keys($Y)[0]])[0]] - 1*$cur_avg;
            $Q2y = $Q2y + $Y_matrix[$in_str][array_keys($Y[array_keys($Y)[0]])[0]]*$Y_matrix[$in_str][array_keys($Y[array_keys($Y)[0]])[0]];
            $n = $n + 1;
        }
        $Q2y = $Q2y/($n);

        $X_matrix_T = matrix::matrix_t($X_matrix);
        $X_matrix_TX = matrix::matrix_multiply_inkey($X_matrix_T, $X_matrix);
        $X_matrix_TY = matrix::matrix_multiply_inkey($X_matrix_T, $Y_matrix);
        $X_m_final = matrix::matrix_mult_number((1/($n)), $X_matrix_TX);
        $XY_m_final = matrix::matrix_mult_number((1/($n)), $X_matrix_TY);

        $U = [];

        foreach($X_m_final as $in_i=>$v_i){
            $U[$in_i] = [];
            foreach($v_i as $in_j=>$v_j){
                if($in_i === $in_j){
                    $U[$in_i][$in_j] = sqrt(1*$X_m_final[$in_i][$in_j]);
                }else{
                    $U[$in_i][$in_j] = 0;
                }
            }
        }

        $U_1 = matrix::matrix_inverse($U);

        // -----------------------------------------------------------
        // ---- Случай вырожденной матрицы, обычно возникает когда в корреляционный анализ поступает матрица Z с содержанием данных свободного коэффициента (1, 1, 1, 1, ...)
        if($U_1 === false){
            return false;
        }
        // -----------------------------------------------------------

        $U_1covZ = matrix::matrix_multiply_inkey($U_1, $X_m_final);

        $R = matrix::matrix_multiply_inkey($U_1covZ, $U_1);
        $U_1covZY = matrix::matrix_multiply_inkey($U_1, $XY_m_final);
        $Qy = sqrt($Q2y);
        $Ry = matrix::matrix_mult_number((1/$Qy), $U_1covZY);
        $R_fin = [];

        $all_count_parameters = count($Z[array_keys($Z)[0]]) + count($Y[array_keys($Y)[0]]);

        $Ry_normal = $Ry;
        $R_normal = $R;

        matrix::normalize_matrix($Ry_normal);
        matrix::normalize_matrix($R_normal);

        for ($i = 0; $i < $all_count_parameters; $i++){
            for ($j = 0; $j < $all_count_parameters; $j++){
                if(1*$i === 0){
                    if(1*$i === 1*$j){
                        $R_fin[$i][$j] = 1;
                    }else{
                        $R_fin[$i][$j] = $Ry_normal[1*$j - 1][$i];
                    }
                }else{
                    if(1*$i === 1*$j){
                        $R_fin[$i][$j] = 1;
                    }else{
                        if(1*$j === 0){
                            $R_fin[$i][$j] = $Ry_normal[1*$i - 1][$j];
                        }else{
                            $R_fin[$i][$j] = $R_normal[1*$i - 1][1*$j - 1];
                        }
                    }
                }
            }
        }

        $R_added = [];
        $R_added[array_keys($Y[array_keys($Y)[0]])[0]][array_keys($Y[array_keys($Y)[0]])[0]] = 0;
        foreach($R as $ri => $vi){
            $R_added[$ri][array_keys($Y[array_keys($Y)[0]])[0]] = 0;
            foreach($vi as $rj => $vj){
                if(!$R_added[array_keys($Y[array_keys($Y)[0]])[0]][$rj]){
                    $R_added[array_keys($Y[array_keys($Y)[0]])[0]][$rj] = 0;
                }
                $R_added[$ri][$rj] = 0;
            }
        }

        if(array_keys($Y[array_keys($Y)[0]])[0] !== array_keys($R)[0]) {
            matrix::inkey_matrix($R_fin, $R_added);
        }

        $result_arr = [];
        $result_arr['R'] = $R;
        $result_arr['Ry'] = $Ry;
        $result_arr['Ry_T'] = matrix::matrix_t($Ry);
        $result_arr['R_1'] = matrix::matrix_inverse($R);
        $result_arr['R_fin'] = $R_fin;
        $R__2 = 1*matrix::matrix_multiply(matrix::matrix_multiply($result_arr['Ry_T'], $result_arr['R_1']), $result_arr['Ry'])[0][0];
        $result_arr['r'] = sqrt($R__2);
        $result_arr['R_2'] = 100*$R__2;

        return $result_arr;

    }
    public static function calculate_regression_analisys($Y, $Z, $Z_forecast='', $Head_name=''){
        $resum_result = [];
        $regr_res = self::calculate_regression($Y, $Z);
        $resum_result['Coef_regr'] = $regr_res['ZTZ_1ZTY'];
        $z_map_key = [];
        foreach($Z as $k_1=>$v_1){
            foreach($v_1 as $k_2=>$v_2){
                if(isset($Z[1*$k_1 - 1][$k_2])){
                    $z_map_key[$k_2] = $z_map_key[$k_2] + abs(1*$Z[1*$k_1 - 1][$k_2] - 1*$Z[1*$k_1][$k_2]);
                }
            }
        }
        $resum_result['Z_map_key'] = $z_map_key;
        $Yavg = matrix::arr_avg($Y, array_keys($Y[array_keys($Y)[0]])[0]);
        $Zavg = [];
        $coef_elast = [];

        if($Head_name !== ''){
            foreach($z_map_key as $k_1=>$v_1){
                if(isset($Head_name[$k_1])){
                    $Zavg[$k_1] = matrix::arr_avg($Z, $k_1);
                    $coef_elast[$k_1] = $regr_res['ZTZ_1ZTY'][$k_1][array_keys($regr_res['ZTZ_1ZTY'][$k_1])[0]]*$Zavg[$k_1]/$Yavg;
                }
            }
        }else{
            foreach($z_map_key as $k_1=>$v_1){
                if($v_1 !== 0){
                    $Zavg[$k_1] = matrix::arr_avg($Z, $k_1);
                    $coef_elast[$k_1] = $regr_res['ZTZ_1ZTY'][$k_1][array_keys($regr_res['ZTZ_1ZTY'][$k_1])[0]]*$Zavg[$k_1]/$Yavg;
                }
            }
        }

        $resum_result['Coef_elast'] = $coef_elast;

        $Qm = 1*$regr_res['Ys_Ya_2_sum'];
        $Qos = 1*$regr_res['Yi_Ys_2_sum'];
        $Qob = 1*$regr_res['Yi_Ya_2_sum'];

        $resum_result['Qm'] = $Qm;
        $resum_result['Qos'] = $Qos;
        $resum_result['Qob'] = $Qob;

        $mAll = 0;
        $mCoef = 0;

        if($Head_name !== ''){
            foreach($z_map_key as $k_1=>$v_1){
                $mAll = $mAll + 1;
                if(isset($Head_name[$k_1])){
                    $mCoef = $mCoef + 1;
                }
            }
        }else{
            foreach($z_map_key as $k_1=>$v_1) {
                $mAll = $mAll + 1;
                if($v_1 !== 0){
                    $mCoef = $mCoef + 1;
                }
            }
        }

        $v2 = count($Y) - $mAll;
        $nGen = count($Y);

        $resum_result['nGen'] = $nGen;
        $resum_result['mAll'] = $mAll;
        $resum_result['mCoef'] = $mCoef;

        $F_part_1 = $Qm/$mCoef;
        $F_part_2 = $Qos/$v2;
        $F_cur_criteria = $F_part_1/$F_part_2;
        $F_tbl = self::F_Fisher($mCoef, $v2, 0.05);

        $resum_result['v1'] = $mCoef;
        $resum_result['v2'] = $v2;

        $resum_result['F_cur_criteria'] = $F_cur_criteria;
        $resum_result['F_tbl'] = $F_tbl;

        $s2 = $Qos/$v2;
        $covaa = matrix::matrix_mult_number($s2,$regr_res['ZTZ_1']);

        $t_arr = [];
        $t_tbl = self::t_Student($v2, 0.025);

        foreach ($covaa as $k=>$v){
            $cur_sa = sqrt($covaa[$k][$k]);
            $cur_a = $resum_result['Coef_regr'][$k][array_keys($resum_result['Coef_regr'][$k])[0]];
            $t_arr[$k] = $cur_a/$cur_sa;
        }

        $resum_result['t_cur_criteria'] = $t_arr;
        $resum_result['t_tbl'] = $t_tbl;

        // ---------------------------------- ПРОГНОСТИЧЕСКИЕ ХАРАКТЕРИСТИКИ МОДЕЛИ --------------------------------------------------------------------
        // ---------------------------------- спирмен
        $EnEc_nons = [];
        $vn = 0;
        $tn = 0;
        $tn_max = 0;
        $EnEc_nons_cur = 0;
        foreach ($regr_res['Yi_Ys'] as $k=>$v){
            if($regr_res['Yi_Ys'][1*$k+1]){
                $EnEc_nons_cur = 1*$regr_res['Yi_Ys'][1*$k+1][0] - 1*$regr_res['Yi_Ys'][1*$k][0];
                if($EnEc_nons_cur >= 0){
                    $vn = $vn + 1;
                    $tn = $tn + 1;
                }else{
                    $tn = 0;
                }
                if($tn_max < $tn){
                    $tn_max = $tn;
                }
                array_push($EnEc_nons, $EnEc_nons_cur);
            }
        }

        $vn_formula = (1/3)*(2*$nGen - 1) - 1.96*(sqrt((16*$nGen - 29)/90));

        $resum_result['vn_formula'] = $vn_formula;
        $resum_result['vn_cur'] = $vn;

        $t0n = 0;
        if($nGen <= 26){
            $t0n = 5;
        }else if($nGen > 26 && $nGen <= 153){
            $t0n = 6;
        }else if($nGen > 153 && $nGen <= 1170){
            $t0n = 7;
        }

        $resum_result['tn_formula'] = $t0n;
        $resum_result['tn_cur'] = $tn_max;

        $Eabs_ISORT = [];
        foreach($regr_res['Yi_Ys_abs'] as $k => $v){
            array_push($Eabs_ISORT, $regr_res['Yi_Ys_abs'][$k][0]);
        }

        $spirmen = [];

        foreach($z_map_key as $ths_key => $ths_val){
            $d2sum_cur = 0;
            $Z_ISORT = [];
            $rank_arr = [];

            if((1*$z_map_key[$ths_key] !== 0 && $Head_name === '') || ($Head_name !== '' && isset($Head_name[$ths_key]))){

                foreach($Z as $ths_key2 => $ths_val2){
                    array_push($Z_ISORT, $Z[$ths_key2][$ths_key]);
                }

                arsort($Eabs_ISORT);
                arsort($Z_ISORT);

                $cur_rank = 0;

                foreach($Eabs_ISORT as $k_1 => $v_1){
                    $rank_arr[$k_1]['e_rank'] = 1*$cur_rank + 1;
                    $rank_arr[$k_1]['e_value'] = 1*$Eabs_ISORT[$k_1];
                    $cur_rank = $cur_rank + 1;
                }

                $cur_rank = 0;

                foreach($Z_ISORT as $k_1 => $v_1){
                    $rank_arr[$k_1]['z'.$ths_key.'_rank'] = 1*$cur_rank + 1;
                    $rank_arr[$k_1]['z'.$ths_key.'_value'] = 1*$Z_ISORT[$k_1];
                    $rank_arr[$k_1]['d'] = 1*$cur_rank + 1 - 1*$rank_arr[$k_1]['e_rank'];
                    $rank_arr[$k_1]['d2'] = (1*$cur_rank + 1 - 1*$rank_arr[$k_1]['e_rank'])*(1*$cur_rank + 1 - 1*$rank_arr[$k_1]['e_rank']);
                    $d2sum_cur = $d2sum_cur + 1*$rank_arr[$k_1]['d2'];
                    $cur_rank = $cur_rank + 1;
                }
                $k_Spearmen = 1 - 6*$d2sum_cur/($nGen*($nGen*$nGen - 1));
                $t_kS = abs($k_Spearmen*(sqrt($nGen-2)/(sqrt(1 - $k_Spearmen*$k_Spearmen))));

                $spirmen[$ths_key]['k_Spearmen'] = $k_Spearmen;
                $spirmen[$ths_key]['t_kS'] = $t_kS;
                $spirmen[$ths_key]['t_kS_tbl'] = self::t_Student($v2, 0.025);
            }
        }

        $resum_result['spirmen_data'] = $spirmen;

        // ---------------------------------- оценка автокорреляции остатков модели
        $sumeiei1 = 0;
        $sumei = 0;
        foreach($regr_res['Yi_Ys'] as $k=>$v){
            $sumei = $sumei + $v[0]*$v[0];
            if($regr_res['Yi_Ys'][1*$k - 1]){
                $sumeiei1 = $sumeiei1 + ($v[0] - $regr_res['Yi_Ys'][1*$k - 1][0])*($v[0] - $regr_res['Yi_Ys'][1*$k - 1][0]);
            }
        }
        $DW = $sumeiei1/$sumei;
        $dwtbl = self::DW_tbl($nGen, $mAll, 0.05);

        $resum_result['DW_data']['DW'] = $DW;
        $resum_result['DW_data']['DW_tbl'] = $dwtbl;

        // ---------------------------------- проверка гипотезы о равенстве нулю математического ожидания случайной составляющей

        $E_avg = matrix::arr_avg($regr_res['Yi_Ys']);
        $Esum2 = 0;
        $Esum3 = 0;
        $Esum4 = 0;
        foreach($regr_res['Yi_Ys'] as $in_key => $in_val){
            $Esum2 = $Esum2 + ($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg);
            $Esum3 = $Esum3 + ($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg);
            $Esum4 = $Esum4 + ($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg)*($in_val[0] - 1*$E_avg);
        }
        $tps = sqrt($Esum2/(1*$nGen-1));

        $tGM = abs($E_avg/$tps);
        $tGM_tbl = self::t_Student(1*$nGen - 1, 0.025);

        $resum_result['tGM_data']['tGM'] = $tGM;
        $resum_result['tGM_data']['tGM_tbl'] = $tGM_tbl;

        // ---------------------------------- гипотеза о нормальности распределения случайной составляющей

        $A_part_1 = (1/$nGen)*$Esum3;
        $A_part_2 = sqrt((1/$nGen)*$Esum2);
        $A_part_2 = $A_part_2*$A_part_2*$A_part_2;
        $Assim = $A_part_1/$A_part_2;

        $E_part1 = (1/$nGen)*$Esum4;
        $E_part2 = sqrt((1/$nGen)*$Esum2);
        $E_part2 = $E_part2*$E_part2*$E_part2*$E_part2;
        $Ecsc = $E_part1/$E_part2 - 3;

        $resum_result['norm_raspr']['Assim'] = $Assim;
        $resum_result['norm_raspr']['Eks'] = $Ecsc;

        $S_Assim = sqrt((6*($nGen - 2))/(($nGen+1)*($nGen+3)));
        $S_Ecsc = sqrt((24*$nGen*($nGen - 2)*($nGen - 3))/(($nGen+1)*($nGen+1)*($nGen+3)*($nGen+5)));

        $resum_result['norm_raspr']['S_Assim'] = $S_Assim;
        $resum_result['norm_raspr']['S_Eks'] = $S_Ecsc;

        $resum_result['norm_raspr']['Assim < 1.5*S_Assim'] = $Assim.' < '.(1.5*$S_Assim);
        $resum_result['norm_raspr']['abs(Eks + 6/(n + 1)) < 1.5*S_Eks'] = abs($Ecsc + 6/($nGen + 1)).' < '.(1.5*$S_Ecsc);

        // ---------------------------------- оценка прогностических свойств моделей

        $Y_pr = [];
        $Z_pr = [];

        foreach($Y as $k_1 => $v_1){
            if(1*$k_1 < 1*$nGen - 3){
                foreach($v_1 as $k_2 => $v_2){
                    $Y_pr[$k_1][$k_2] = $v_2;
                }
            }
        }

        foreach($Z as $k_1 => $v_1){
            if(1*$k_1 < 1*$nGen - 3){
                foreach($v_1 as $k_2 => $v_2){
                    $Z_pr[$k_1][$k_2] = $v_2;
                }
            }
        }

        $regr_res_3 = self::calculate_regression($Y_pr, $Z_pr);

        $KT_p1 = 0;
        $KT_p2 = 0;
        $KT_p3 = 0;

        for($i = 0; $i < 3; $i++){
            $Yi_cur_3 = $Y[count($Y_pr) + $i][array_keys($Y[array_keys($Y)[0]])[0]];
            $Ys_cur_3 = 0;
            foreach($regr_res_3['ZTZ_1ZTY'] as $k_1=>$v_1){
                foreach($v_1 as $k_2=>$v_2){
                    $Zi_cur_3 = $Z[count($Y_pr) + $i][$k_1];
                    $Ys_cur_3 = $Ys_cur_3 + $v_2*$Zi_cur_3;
                }
            }
            $KT_p1 = 1*$KT_p1 + ($Yi_cur_3 - $Ys_cur_3)*($Yi_cur_3 - $Ys_cur_3);
            $KT_p2 = 1*$KT_p2 + $Yi_cur_3 * $Yi_cur_3;
            $KT_p3 = 1*$KT_p3 + $Ys_cur_3 * $Ys_cur_3;
        }


        $KT1 = sqrt($KT_p1/$KT_p2);
        $KT2 = sqrt($KT_p1/($KT_p2 + $KT_p3));
        $UT1 = (sqrt($KT_p1/3))/( sqrt($KT_p2/3) + sqrt($KT_p3/3) );

        $resum_result['progn_har']['KT1'] = $KT1;
        $resum_result['progn_har']['KT2'] = $KT2;
        $resum_result['progn_har']['UT1'] = $UT1;

        // ---------------------------------- прогноз

        if($Z_forecast !== '') {
            $Y_forecast = [];
            foreach ($Z_forecast as $k_1 => $v_1) {
                foreach ($v_1 as $k_2 => $v_2) {
                    $Y_forecast[$k_1]['forecast'] = 1 * $Y_forecast[$k_1]['forecast'] + $regr_res['ZTZ_1ZTY'][$k_2][array_keys($regr_res['ZTZ_1ZTY'][$k_2])[0]] * $Z_forecast[$k_1][$k_2];
                }
            }

            $resum_result['Z_forecast'] = $Z_forecast;

            $Z_forecastT = matrix::matrix_t($Z_forecast);

            $new_V2 = $v2;
            $ZpZTZ_1 = matrix::matrix_multiply_inkey($Z_forecast, $regr_res['ZTZ_1']);
            $new_S = sqrt($regr_res['Yi_Ys_2_sum'] / $new_V2);
            $for_S = matrix::matrix_multiply($ZpZTZ_1, $Z_forecastT);
            $for_Sf = [];
            foreach ($for_S as $k_1 => $v_1) {
                foreach ($v_1 as $k_2 => $v_2) {
                    if (1 * $k_1 == 1 * $k_2) {
                        $for_Sf[$k_1] = $new_S * sqrt(1 + 1 * $v_2);
                    }
                }
            }
            $t_st = self::t_Student(1 * $new_V2, 0.025);
            $index_key = 0;

            foreach ($Y_forecast as $k_1 => $v_1) {

                $Y_forecast[$k_1]['interval_step'] = $t_st * $for_Sf[$index_key];
                $Y_forecast[$k_1]['interval_low'] = 1 * $Y_forecast[$k_1]['forecast'] - $t_st * $for_Sf[$index_key];
                $Y_forecast[$k_1]['interval_top'] = 1 * $Y_forecast[$k_1]['forecast'] + $t_st * $for_Sf[$index_key];

                $index_key = 1 * $index_key + 1;
            }

            $resum_result['Y_forecast'] = $Y_forecast;
        }

        return $resum_result;

    }
    public static function calculate_regression($Y, $Z){

        $result_arr = [];

        $ZT = matrix::matrix_t($Z);
        $result_arr['ZT'] = $ZT;
        $ZTZ = matrix::matrix_multiply_inkey($ZT, $Z);
        $result_arr['ZTZ'] = $ZTZ;
        $ZTZ_1 = matrix::matrix_inverse($ZTZ);
        $result_arr['ZTZ_1'] = $ZTZ_1;

        $ZTY = matrix::matrix_multiply_inkey($ZT, $Y);
        $result_arr['ZTY'] = $ZTY;

        $ZTZ_1ZTY = matrix::matrix_multiply_inkey($ZTZ_1, $ZTY);
        $result_arr['ZTZ_1ZTY'] = $ZTZ_1ZTY;

        $Ys = [];

        $result_arr['Yi_2'] = [];
        $result_arr['Ys_2'] = [];
        $result_arr['Yi_2_sum'] = [];
        $result_arr['Ys_2_sum'] = [];
        $Yi_2_sum = 0;
        $Ys_2_sum = 0;

        $Yi_Ys_sum = 0;
        $result_arr['Yi_Ys'] = [];
        $Yi_Ys_abs_sum = 0;
        $result_arr['Yi_Ys_abs'] = [];
        $Yi_Ys_2_sum = 0;
        $result_arr['Yi_Ys_2'] = [];
        $Yi_Ys_3_sum = 0;
        $result_arr['Yi_Ys_3'] = [];
        $Yi_Ys_4_sum = 0;
        $result_arr['Yi_Ys_4'] = [];

        $result_arr['Yi_Ys_sum'] = [];
        $result_arr['Yi_Ys_abs_sum'] = [];
        $result_arr['Yi_Ys_2_sum'] = [];
        $result_arr['Yi_Ys_3_sum'] = [];
        $result_arr['Yi_Ys_4_sum'] = [];

        $Yi_Ya_sum = 0;
        $result_arr['Yi_Ya'] = [];
        $Yi_Ya_abs_sum = 0;
        $result_arr['Yi_Ya_abs'] = [];
        $Yi_Ya_2_sum = 0;
        $result_arr['Yi_Ya_2'] = [];
        $Yi_Ya_3_sum = 0;
        $result_arr['Yi_Ya_3'] = [];
        $Yi_Ya_4_sum = 0;
        $result_arr['Yi_Ya_4'] = [];

        $result_arr['Yi_Ya_sum'] = [];
        $result_arr['Yi_Ya_abs_sum'] = [];
        $result_arr['Yi_Ya_2_sum'] = [];
        $result_arr['Yi_Ya_3_sum'] = [];
        $result_arr['Yi_Ya_4_sum'] = [];

        $Ys_Ya_sum = 0;
        $result_arr['Ys_Ya'] = [];
        $Ys_Ya_abs_sum = 0;
        $result_arr['Ys_Ya_abs'] = [];
        $Ys_Ya_2_sum = 0;
        $result_arr['Ys_Ya_2'] = [];
        $Ys_Ya_3_sum = 0;
        $result_arr['Ys_Ya_3'] = [];
        $Ys_Ya_4_sum = 0;
        $result_arr['Ys_Ya_4'] = [];

        $result_arr['Ys_Ya_sum'] = [];
        $result_arr['Ys_Ya_abs_sum'] = [];
        $result_arr['Ys_Ya_2_sum'] = [];
        $result_arr['Ys_Ya_3_sum'] = [];
        $result_arr['Ys_Ya_4_sum'] = [];

        $Ya = matrix::arr_avg($Y, array_keys($Y[array_keys($Y)[0]])[0]);

        $result_arr['Y_avg'] = $Ya;

        foreach($Y as $in_keyY=>$in_valY){
            $Ys_cur = 0;
            foreach($result_arr['ZTZ_1ZTY'] as $in_key=>$in_val){
                if(!isset($result_arr['Z_avg'][$in_key])) {
                    $result_arr['Z_avg'][$in_key] = matrix::arr_avg($Z, $in_key);
                    $result_arr['Z_coef'][$in_key] = $result_arr['ZTZ_1ZTY'][$in_key][array_keys($result_arr['ZTZ_1ZTY'][$in_key])[0]];
                }
                $Ys_cur = $Ys_cur + $result_arr['ZTZ_1ZTY'][$in_key][array_keys($in_valY)[0]] * $Z[$in_keyY][$in_key]; // mark keyname Y
            }

            $Yi_cur = $Y[$in_keyY][array_keys($in_valY)[0]];

            array_push($result_arr['Yi_2'], $Yi_cur * $Yi_cur);
            array_push($result_arr['Ys_2'], $Ys_cur * $Ys_cur);

            $Yi_2_sum = $Yi_2_sum + $Yi_cur * $Yi_cur;
            $Ys_2_sum = $Ys_2_sum + $Ys_cur * $Ys_cur;

            $result_arr['Yi_2_sum'] = $Yi_2_sum;
            $result_arr['Ys_2_sum'] = $Ys_2_sum;

            $Yi_Ys = $Y[$in_keyY][array_keys($in_valY)[0]] - $Ys_cur;                                                  // mark keyname Y
            $Yi_Ya = $Y[$in_keyY][array_keys($in_valY)[0]] - $Ya;                                                      // mark keyname Y
            $Ys_Ya = $Ys_cur - $Ya;

            $Yi_Ys_sum = $Yi_Ys_sum + $Yi_Ys;
            $Yi_Ys_abs_sum = $Yi_Ys_abs_sum + abs($Yi_Ys);
            $Yi_Ys_2_sum = $Yi_Ys_2_sum + $Yi_Ys * $Yi_Ys;
            $Yi_Ys_3_sum = $Yi_Ys_3_sum + $Yi_Ys * $Yi_Ys * $Yi_Ys;
            $Yi_Ys_4_sum = $Yi_Ys_4_sum + $Yi_Ys * $Yi_Ys * $Yi_Ys * $Yi_Ys;

            $result_arr['Yi_Ys_sum'] = $Yi_Ys_sum;
            $result_arr['Yi_Ys_abs_sum'] = $Yi_Ys_abs_sum;
            $result_arr['Yi_Ys_2_sum'] = $Yi_Ys_2_sum;
            $result_arr['Yi_Ys_3_sum'] = $Yi_Ys_3_sum;
            $result_arr['Yi_Ys_4_sum'] = $Yi_Ys_4_sum;

            array_push($result_arr['Yi_Ys'], [$Yi_Ys]);
            array_push($result_arr['Yi_Ys_abs'], [abs($Yi_Ys)]);

            array_push($result_arr['Yi_Ys_2'], [$Yi_Ys*$Yi_Ys]);
            array_push($result_arr['Yi_Ys_3'], [$Yi_Ys*$Yi_Ys*$Yi_Ys]);
            array_push($result_arr['Yi_Ys_4'], [$Yi_Ys*$Yi_Ys*$Yi_Ys*$Yi_Ys]);

            $Yi_Ya_sum = $Yi_Ya_sum + $Yi_Ya;
            $Yi_Ya_abs_sum = $Yi_Ya_abs_sum + abs($Yi_Ya);
            $Yi_Ya_2_sum = $Yi_Ya_2_sum + $Yi_Ya*$Yi_Ya;
            $Yi_Ya_3_sum = $Yi_Ya_3_sum + $Yi_Ya*$Yi_Ya*$Yi_Ya;
            $Yi_Ya_4_sum = $Yi_Ya_4_sum + $Yi_Ya*$Yi_Ya*$Yi_Ya*$Yi_Ya;

            $result_arr['Yi_Ya_sum'] = $Yi_Ya_sum;
            $result_arr['Yi_Ya_abs_sum'] = $Yi_Ya_abs_sum;
            $result_arr['Yi_Ya_2_sum'] = $Yi_Ya_2_sum;
            $result_arr['Yi_Ya_3_sum'] = $Yi_Ya_3_sum;
            $result_arr['Yi_Ya_4_sum'] = $Yi_Ya_4_sum;

            array_push($result_arr['Yi_Ya'], [$Yi_Ya]);
            array_push($result_arr['Yi_Ya_abs'], [abs($Yi_Ya)]);
            array_push($result_arr['Yi_Ya_2'], [$Yi_Ya*$Yi_Ya]);
            array_push($result_arr['Yi_Ya_3'], [$Yi_Ya*$Yi_Ya*$Yi_Ya]);
            array_push($result_arr['Yi_Ya_4'], [$Yi_Ya*$Yi_Ya*$Yi_Ya*$Yi_Ya]);

            $Ys_Ya_sum = $Ys_Ya_sum + $Ys_Ya;
            $Ys_Ya_abs_sum = $Ys_Ya_abs_sum + abs($Ys_Ya);
            $Ys_Ya_2_sum = $Ys_Ya_2_sum + $Ys_Ya*$Ys_Ya;
            $Ys_Ya_3_sum = $Ys_Ya_3_sum + $Ys_Ya*$Ys_Ya*$Ys_Ya;
            $Ys_Ya_4_sum = $Ys_Ya_4_sum + $Ys_Ya*$Ys_Ya*$Ys_Ya*$Ys_Ya;

            $result_arr['Ys_Ya_sum'] = $Ys_Ya_sum;
            $result_arr['Ys_Ya_abs_sum'] = $Ys_Ya_abs_sum;
            $result_arr['Ys_Ya_2_sum'] = $Ys_Ya_2_sum;
            $result_arr['Ys_Ya_3_sum'] = $Ys_Ya_3_sum;
            $result_arr['Ys_Ya_4_sum'] = $Ys_Ya_4_sum;

            array_push($result_arr['Ys_Ya'], [$Ys_Ya]);
            array_push($result_arr['Ys_Ya_abs'], [abs($Ys_Ya)]);
            array_push($result_arr['Ys_Ya_2'], [$Ys_Ya*$Ys_Ya]);
            array_push($result_arr['Ys_Ya_3'], [$Ys_Ya*$Ys_Ya*$Ys_Ya]);
            array_push($result_arr['Ys_Ya_4'], [$Ys_Ya*$Ys_Ya*$Ys_Ya*$Ys_Ya]);

            array_push($Ys, [$Ys_cur]);

        }

        return $result_arr;

    }

    // statistic data
    public static function DW_tbl($n, $k, $alpha){
        $n_correct = self::DW_n($n);
        $k_correct = self::DW_k($k);

        $dw = [];

        // 0.05

        $dw['0.05']=[];

        $dw['0.05']['15']=[];

        $dw['0.05']['15']['2']=[];
        $dw['0.05']['15']['2']['dl']= 1.08;$dw['0.05']['15']['2']['du']= 1.36;
        $dw['0.05']['15']['3']=[];
        $dw['0.05']['15']['3']['dl']= 0.95;$dw['0.05']['15']['3']['du']= 1.54;
        $dw['0.05']['15']['4']=[];
        $dw['0.05']['15']['4']['dl']= 0.82;$dw['0.05']['15']['4']['du']= 1.75;
        $dw['0.05']['15']['5']=[];
        $dw['0.05']['15']['5']['dl']= 0.69;$dw['0.05']['15']['5']['du']= 1.97;
        $dw['0.05']['15']['6']=[];
        $dw['0.05']['15']['6']['dl']= 0.56;$dw['0.05']['15']['6']['du']= 2.21;

        $dw['0.05']['16']=[];
        $dw['0.05']['16']['2']=[];
        $dw['0.05']['16']['2']['dl']= 1.1;$dw['0.05']['16']['2']['du']= 1.37;
        $dw['0.05']['16']['3']=[];
        $dw['0.05']['16']['3']['dl']= 0.98;$dw['0.05']['16']['3']['du']= 1.54;
        $dw['0.05']['16']['4']=[];
        $dw['0.05']['16']['4']['dl']= 0.86;$dw['0.05']['16']['4']['du']= 1.73;
        $dw['0.05']['16']['5']=[];
        $dw['0.05']['16']['5']['dl']= 0.74;$dw['0.05']['16']['5']['du']= 1.93;
        $dw['0.05']['16']['6']=[];
        $dw['0.05']['16']['6']['dl']= 0.62;$dw['0.05']['16']['6']['du']= 2.15;

        $dw['0.05']['17']=[];
        $dw['0.05']['17']['2']=[];
        $dw['0.05']['17']['2']['dl']= 1.13;$dw['0.05']['17']['2']['du']= 1.38;
        $dw['0.05']['17']['3']=[];
        $dw['0.05']['17']['3']['dl']= 1.02;$dw['0.05']['17']['3']['du']= 1.54;
        $dw['0.05']['17']['4']=[];
        $dw['0.05']['17']['4']['dl']= 0.9;$dw['0.05']['17']['4']['du']= 1.71;
        $dw['0.05']['17']['5']=[];
        $dw['0.05']['17']['5']['dl']= 0.78;$dw['0.05']['17']['5']['du']= 1.9;
        $dw['0.05']['17']['6']=[];
        $dw['0.05']['17']['6']['dl']= 0.67;$dw['0.05']['17']['6']['du']= 2.1;

        $dw['0.05']['18']=[];
        $dw['0.05']['18']['2']=[];
        $dw['0.05']['18']['2']['dl']= 1.16;$dw['0.05']['18']['2']['du']= 1.39;
        $dw['0.05']['18']['3']=[];
        $dw['0.05']['18']['3']['dl']= 1.05;$dw['0.05']['18']['3']['du']= 1.53;
        $dw['0.05']['18']['4']=[];
        $dw['0.05']['18']['4']['dl']= 0.93;$dw['0.05']['18']['4']['du']= 1.69;
        $dw['0.05']['18']['5']=[];
        $dw['0.05']['18']['5']['dl']= 0.82;$dw['0.05']['18']['5']['du']= 1.87;
        $dw['0.05']['18']['6']=[];
        $dw['0.05']['18']['6']['dl']= 0.71;$dw['0.05']['18']['6']['du']= 2.06;

        $dw['0.05']['19']=[];
        $dw['0.05']['19']['2']=[];
        $dw['0.05']['19']['2']['dl']= 1.18;$dw['0.05']['19']['2']['du']= 1.4;
        $dw['0.05']['19']['3']=[];
        $dw['0.05']['19']['3']['dl']= 1.08;$dw['0.05']['19']['3']['du']= 1.53;
        $dw['0.05']['19']['4']=[];
        $dw['0.05']['19']['4']['dl']= 0.97;$dw['0.05']['19']['4']['du']= 1.68;
        $dw['0.05']['19']['5']=[];
        $dw['0.05']['19']['5']['dl']= 0.86;$dw['0.05']['19']['5']['du']= 1.85;
        $dw['0.05']['19']['6']=[];
        $dw['0.05']['19']['6']['dl']= 0.75;$dw['0.05']['19']['6']['du']= 2.02;

        $dw['0.05']['20']=[];
        $dw['0.05']['20']['2']=[];
        $dw['0.05']['20']['2']['dl']= 1.2;$dw['0.05']['20']['2']['du']= 1.41;
        $dw['0.05']['20']['3']=[];
        $dw['0.05']['20']['3']['dl']= 1.1;$dw['0.05']['20']['3']['du']= 1.54;
        $dw['0.05']['20']['4']=[];
        $dw['0.05']['20']['4']['dl']= 1;$dw['0.05']['20']['4']['du']= 1.68;
        $dw['0.05']['20']['5']=[];
        $dw['0.05']['20']['5']['dl']= 0.9;$dw['0.05']['20']['5']['du']= 1.83;
        $dw['0.05']['20']['6']=[];
        $dw['0.05']['20']['6']['dl']= 0.79;$dw['0.05']['20']['6']['du']= 1.99;

        $dw['0.05']['21']=[];
        $dw['0.05']['21']['2']=[];
        $dw['0.05']['21']['2']['dl']= 1.22;$dw['0.05']['21']['2']['du']= 1.42;
        $dw['0.05']['21']['3']=[];
        $dw['0.05']['21']['3']['dl']= 1.13;$dw['0.05']['21']['3']['du']= 1.54;
        $dw['0.05']['21']['4']=[];
        $dw['0.05']['21']['4']['dl']= 1.03;$dw['0.05']['21']['4']['du']= 1.67;
        $dw['0.05']['21']['5']=[];
        $dw['0.05']['21']['5']['dl']= 0.93;$dw['0.05']['21']['5']['du']= 1.81;
        $dw['0.05']['21']['6']=[];
        $dw['0.05']['21']['6']['dl']= 0.83;$dw['0.05']['21']['6']['du']= 1.96;

        $dw['0.05']['22']=[];
        $dw['0.05']['22']['2']=[];
        $dw['0.05']['22']['2']['dl']= 1.24;$dw['0.05']['22']['2']['du']= 1.43;
        $dw['0.05']['22']['3']=[];
        $dw['0.05']['22']['3']['dl']= 1.15;$dw['0.05']['22']['3']['du']= 1.54;
        $dw['0.05']['22']['4']=[];
        $dw['0.05']['22']['4']['dl']= 1.05;$dw['0.05']['22']['4']['du']= 1.66;
        $dw['0.05']['22']['5']=[];
        $dw['0.05']['22']['5']['dl']= 0.96;$dw['0.05']['22']['5']['du']= 1.8;
        $dw['0.05']['22']['6']=[];
        $dw['0.05']['22']['6']['dl']= 0.86;$dw['0.05']['22']['6']['du']= 1.94;

        $dw['0.05']['23']=[];
        $dw['0.05']['23']['2']=[];
        $dw['0.05']['23']['2']['dl']= 1.26;$dw['0.05']['23']['2']['du']= 1.44;
        $dw['0.05']['23']['3']=[];
        $dw['0.05']['23']['3']['dl']= 1.17;$dw['0.05']['23']['3']['du']= 1.54;
        $dw['0.05']['23']['4']=[];
        $dw['0.05']['23']['4']['dl']= 1.08;$dw['0.05']['23']['4']['du']= 1.66;
        $dw['0.05']['23']['5']=[];
        $dw['0.05']['23']['5']['dl']= 0.99;$dw['0.05']['23']['5']['du']= 1.79;
        $dw['0.05']['23']['6']=[];
        $dw['0.05']['23']['6']['dl']= 0.9;$dw['0.05']['23']['6']['du']= 1.92;

        $dw['0.05']['24']=[];
        $dw['0.05']['24']['2']=[];
        $dw['0.05']['24']['2']['dl']= 1.27;$dw['0.05']['24']['2']['du']= 1.45;
        $dw['0.05']['24']['3']=[];
        $dw['0.05']['24']['3']['dl']= 1.19;$dw['0.05']['24']['3']['du']= 1.55;
        $dw['0.05']['24']['4']=[];
        $dw['0.05']['24']['4']['dl']= 1.1;$dw['0.05']['24']['4']['du']= 1.66;
        $dw['0.05']['24']['5']=[];
        $dw['0.05']['24']['5']['dl']= 1.01;$dw['0.05']['24']['5']['du']= 1.78;
        $dw['0.05']['24']['6']=[];
        $dw['0.05']['24']['6']['dl']= 0.93;$dw['0.05']['24']['6']['du']= 1.9;

        $dw['0.05']['25']=[];
        $dw['0.05']['25']['2']=[];
        $dw['0.05']['25']['2']['dl']= 1.29;$dw['0.05']['25']['2']['du']= 1.45;
        $dw['0.05']['25']['3']=[];
        $dw['0.05']['25']['3']['dl']= 1.21;$dw['0.05']['25']['3']['du']= 1.55;
        $dw['0.05']['25']['4']=[];
        $dw['0.05']['25']['4']['dl']= 1.12;$dw['0.05']['25']['4']['du']= 1.66;
        $dw['0.05']['25']['5']=[];
        $dw['0.05']['25']['5']['dl']= 1.04;$dw['0.05']['25']['5']['du']= 1.77;
        $dw['0.05']['25']['6']=[];
        $dw['0.05']['25']['6']['dl']= 0.95;$dw['0.05']['25']['6']['du']= 1.89;

        $dw['0.05']['26']=[];
        $dw['0.05']['26']['2']=[];
        $dw['0.05']['26']['2']['dl']= 1.3;$dw['0.05']['26']['2']['du']= 1.46;
        $dw['0.05']['26']['3']=[];
        $dw['0.05']['26']['3']['dl']= 1.22;$dw['0.05']['26']['3']['du']= 1.55;
        $dw['0.05']['26']['4']=[];
        $dw['0.05']['26']['4']['dl']= 1.14;$dw['0.05']['26']['4']['du']= 1.65;
        $dw['0.05']['26']['5']=[];
        $dw['0.05']['26']['5']['dl']= 1.06;$dw['0.05']['26']['5']['du']= 1.76;
        $dw['0.05']['26']['6']=[];
        $dw['0.05']['26']['6']['dl']= 0.98;$dw['0.05']['26']['6']['du']= 1.88;

        $dw['0.05']['27']=[];
        $dw['0.05']['27']['2']=[];
        $dw['0.05']['27']['2']['dl']= 1.32;$dw['0.05']['27']['2']['du']= 1.47;
        $dw['0.05']['27']['3']=[];
        $dw['0.05']['27']['3']['dl']= 1.24;$dw['0.05']['27']['3']['du']= 1.56;
        $dw['0.05']['27']['4']=[];
        $dw['0.05']['27']['4']['dl']= 1.16;$dw['0.05']['27']['4']['du']= 1.65;
        $dw['0.05']['27']['5']=[];
        $dw['0.05']['27']['5']['dl']= 1.08;$dw['0.05']['27']['5']['du']= 1.76;
        $dw['0.05']['27']['6']=[];
        $dw['0.05']['27']['6']['dl']= 1.01;$dw['0.05']['27']['6']['du']= 1.86;

        $dw['0.05']['28']=[];
        $dw['0.05']['28']['2']=[];
        $dw['0.05']['28']['2']['dl']= 1.33;$dw['0.05']['28']['2']['du']= 1.48;
        $dw['0.05']['28']['3']=[];
        $dw['0.05']['28']['3']['dl']= 1.26;$dw['0.05']['28']['3']['du']= 1.56;
        $dw['0.05']['28']['4']=[];
        $dw['0.05']['28']['4']['dl']= 1.18;$dw['0.05']['28']['4']['du']= 1.65;
        $dw['0.05']['28']['5']=[];
        $dw['0.05']['28']['5']['dl']= 1.1;$dw['0.05']['28']['5']['du']= 1.75;
        $dw['0.05']['28']['6']=[];
        $dw['0.05']['28']['6']['dl']= 1.03;$dw['0.05']['28']['6']['du']= 1.85;

        $dw['0.05']['29']=[];
        $dw['0.05']['29']['2']=[];
        $dw['0.05']['29']['2']['dl']= 1.34;$dw['0.05']['29']['2']['du']= 1.48;
        $dw['0.05']['29']['3']=[];
        $dw['0.05']['29']['3']['dl']= 1.27;$dw['0.05']['29']['3']['du']= 1.56;
        $dw['0.05']['29']['4']=[];
        $dw['0.05']['29']['4']['dl']= 1.2;$dw['0.05']['29']['4']['du']= 1.65;
        $dw['0.05']['29']['5']=[];
        $dw['0.05']['29']['5']['dl']= 1.12;$dw['0.05']['29']['5']['du']= 1.74;
        $dw['0.05']['29']['6']=[];
        $dw['0.05']['29']['6']['dl']= 1.05;$dw['0.05']['29']['6']['du']= 1.84;

        $dw['0.05']['30']=[];
        $dw['0.05']['30']['2']=[];
        $dw['0.05']['30']['2']['dl']= 1.35;$dw['0.05']['30']['2']['du']= 1.49;
        $dw['0.05']['30']['3']=[];
        $dw['0.05']['30']['3']['dl']= 1.28;$dw['0.05']['30']['3']['du']= 1.57;
        $dw['0.05']['30']['4']=[];
        $dw['0.05']['30']['4']['dl']= 1.21;$dw['0.05']['30']['4']['du']= 1.65;
        $dw['0.05']['30']['5']=[];
        $dw['0.05']['30']['5']['dl']= 1.14;$dw['0.05']['30']['5']['du']= 1.74;
        $dw['0.05']['30']['6']=[];
        $dw['0.05']['30']['6']['dl']= 1.07;$dw['0.05']['30']['6']['du']= 1.83;

        $dw['0.05']['31']=[];
        $dw['0.05']['31']['2']=[];
        $dw['0.05']['31']['2']['dl']= 1.36;$dw['0.05']['31']['2']['du']= 1.5;
        $dw['0.05']['31']['3']=[];
        $dw['0.05']['31']['3']['dl']= 1.3;$dw['0.05']['31']['3']['du']= 1.57;
        $dw['0.05']['31']['4']=[];
        $dw['0.05']['31']['4']['dl']= 1.23;$dw['0.05']['31']['4']['du']= 1.65;
        $dw['0.05']['31']['5']=[];
        $dw['0.05']['31']['5']['dl']= 1.16;$dw['0.05']['31']['5']['du']= 1.74;
        $dw['0.05']['31']['6']=[];
        $dw['0.05']['31']['6']['dl']= 1.09;$dw['0.05']['31']['6']['du']= 1.83;

        $dw['0.05']['32']=[];
        $dw['0.05']['32']['2']=[];
        $dw['0.05']['32']['2']['dl']= 1.37;$dw['0.05']['32']['2']['du']= 1.5;
        $dw['0.05']['32']['3']=[];
        $dw['0.05']['32']['3']['dl']= 1.31;$dw['0.05']['32']['3']['du']= 1.57;
        $dw['0.05']['32']['4']=[];
        $dw['0.05']['32']['4']['dl']= 1.24;$dw['0.05']['32']['4']['du']= 1.65;
        $dw['0.05']['32']['5']=[];
        $dw['0.05']['32']['5']['dl']= 1.18;$dw['0.05']['32']['5']['du']= 1.73;
        $dw['0.05']['32']['6']=[];
        $dw['0.05']['32']['6']['dl']= 1.11;$dw['0.05']['32']['6']['du']= 1.82;

        $dw['0.05']['33']=[];
        $dw['0.05']['33']['2']=[];
        $dw['0.05']['33']['2']['dl']= 1.38;$dw['0.05']['33']['2']['du']= 1.51;
        $dw['0.05']['33']['3']=[];
        $dw['0.05']['33']['3']['dl']= 1.32;$dw['0.05']['33']['3']['du']= 1.58;
        $dw['0.05']['33']['4']=[];
        $dw['0.05']['33']['4']['dl']= 1.26;$dw['0.05']['33']['4']['du']= 1.65;
        $dw['0.05']['33']['5']=[];
        $dw['0.05']['33']['5']['dl']= 1.19;$dw['0.05']['33']['5']['du']= 1.73;
        $dw['0.05']['33']['6']=[];
        $dw['0.05']['33']['6']['dl']= 1.13;$dw['0.05']['33']['6']['du']= 1.81;

        $dw['0.05']['34']=[];
        $dw['0.05']['34']['2']=[];
        $dw['0.05']['34']['2']['dl']= 1.39;$dw['0.05']['34']['2']['du']= 1.51;
        $dw['0.05']['34']['3']=[];
        $dw['0.05']['34']['3']['dl']= 1.33;$dw['0.05']['34']['3']['du']= 1.58;
        $dw['0.05']['34']['4']=[];
        $dw['0.05']['34']['4']['dl']= 1.27;$dw['0.05']['34']['4']['du']= 1.65;
        $dw['0.05']['34']['5']=[];
        $dw['0.05']['34']['5']['dl']= 1.21;$dw['0.05']['34']['5']['du']= 1.73;
        $dw['0.05']['34']['6']=[];
        $dw['0.05']['34']['6']['dl']= 1.15;$dw['0.05']['34']['6']['du']= 1.81;

        $dw['0.05']['35']=[];
        $dw['0.05']['35']['2']=[];
        $dw['0.05']['35']['2']['dl']= 1.4;$dw['0.05']['35']['2']['du']= 1.52;
        $dw['0.05']['35']['3']=[];
        $dw['0.05']['35']['3']['dl']= 1.34;$dw['0.05']['35']['3']['du']= 1.58;
        $dw['0.05']['35']['4']=[];
        $dw['0.05']['35']['4']['dl']= 1.28;$dw['0.05']['35']['4']['du']= 1.65;
        $dw['0.05']['35']['5']=[];
        $dw['0.05']['35']['5']['dl']= 1.22;$dw['0.05']['35']['5']['du']= 1.73;
        $dw['0.05']['35']['6']=[];
        $dw['0.05']['35']['6']['dl']= 1.16;$dw['0.05']['35']['6']['du']= 1.8;

        $dw['0.05']['36']=[];
        $dw['0.05']['36']['2']=[];
        $dw['0.05']['36']['2']['dl']= 1.41;$dw['0.05']['36']['2']['du']= 1.52;
        $dw['0.05']['36']['3']=[];
        $dw['0.05']['36']['3']['dl']= 1.35;$dw['0.05']['36']['3']['du']= 1.59;
        $dw['0.05']['36']['4']=[];
        $dw['0.05']['36']['4']['dl']= 1.29;$dw['0.05']['36']['4']['du']= 1.65;
        $dw['0.05']['36']['5']=[];
        $dw['0.05']['36']['5']['dl']= 1.24;$dw['0.05']['36']['5']['du']= 1.73;
        $dw['0.05']['36']['6']=[];
        $dw['0.05']['36']['6']['dl']= 1.18;$dw['0.05']['36']['6']['du']= 1.8;

        $dw['0.05']['37']=[];
        $dw['0.05']['37']['2']=[];
        $dw['0.05']['37']['2']['dl']= 1.42;$dw['0.05']['37']['2']['du']= 1.53;
        $dw['0.05']['37']['3']=[];
        $dw['0.05']['37']['3']['dl']= 1.36;$dw['0.05']['37']['3']['du']= 1.59;
        $dw['0.05']['37']['4']=[];
        $dw['0.05']['37']['4']['dl']= 1.31;$dw['0.05']['37']['4']['du']= 1.66;
        $dw['0.05']['37']['5']=[];
        $dw['0.05']['37']['5']['dl']= 1.25;$dw['0.05']['37']['5']['du']= 1.72;
        $dw['0.05']['37']['6']=[];
        $dw['0.05']['37']['6']['dl']= 1.19;$dw['0.05']['37']['6']['du']= 1.8;

        $dw['0.05']['38']=[];
        $dw['0.05']['38']['2']=[];
        $dw['0.05']['38']['2']['dl']= 1.43;$dw['0.05']['38']['2']['du']= 1.54;
        $dw['0.05']['38']['3']=[];
        $dw['0.05']['38']['3']['dl']= 1.37;$dw['0.05']['38']['3']['du']= 1.59;
        $dw['0.05']['38']['4']=[];
        $dw['0.05']['38']['4']['dl']= 1.32;$dw['0.05']['38']['4']['du']= 1.66;
        $dw['0.05']['38']['5']=[];
        $dw['0.05']['38']['5']['dl']= 1.26;$dw['0.05']['38']['5']['du']= 1.72;
        $dw['0.05']['38']['6']=[];
        $dw['0.05']['38']['6']['dl']= 1.21;$dw['0.05']['38']['6']['du']= 1.79;

        $dw['0.05']['39']=[];
        $dw['0.05']['39']['2']=[];
        $dw['0.05']['39']['2']['dl'] = 1.43;$dw['0.05']['39']['2']['du'] = 1.54;
        $dw['0.05']['39']['3']=[];
        $dw['0.05']['39']['3']['dl'] = 1.38;$dw['0.05']['39']['3']['du'] = 1.6;
        $dw['0.05']['39']['4']=[];
        $dw['0.05']['39']['4']['dl'] = 1.33;$dw['0.05']['39']['4']['du'] = 1.66;
        $dw['0.05']['39']['5']=[];
        $dw['0.05']['39']['5']['dl'] = 1.27;$dw['0.05']['39']['5']['du'] = 1.72;
        $dw['0.05']['39']['6']=[];
        $dw['0.05']['39']['6']['dl'] = 1.22;$dw['0.05']['39']['6']['du'] = 1.79;

        $dw['0.05']['40']=[];
        $dw['0.05']['40']['2']=[];
        $dw['0.05']['40']['2']['dl']= 1.44;$dw['0.05']['40']['2']['du']= 1.54;
        $dw['0.05']['40']['3']=[];
        $dw['0.05']['40']['3']['dl']= 1.39;$dw['0.05']['40']['3']['du']= 1.6;
        $dw['0.05']['40']['4']=[];
        $dw['0.05']['40']['4']['dl']= 1.34;$dw['0.05']['40']['4']['du']= 1.66;
        $dw['0.05']['40']['5']=[];
        $dw['0.05']['40']['5']['dl']= 1.29;$dw['0.05']['40']['5']['du']= 1.72;
        $dw['0.05']['40']['6']=[];
        $dw['0.05']['40']['6']['dl']= 1.23;$dw['0.05']['40']['6']['du']= 1.79;

        $dw['0.05']['45']=[];
        $dw['0.05']['45']['2']=[];
        $dw['0.05']['45']['2']['dl']= 1.48;$dw['0.05']['45']['2']['du']= 1.57;
        $dw['0.05']['45']['3']=[];
        $dw['0.05']['45']['3']['dl']= 1.43;$dw['0.05']['45']['3']['du']= 1.62;
        $dw['0.05']['45']['4']=[];
        $dw['0.05']['45']['4']['dl']= 1.38;$dw['0.05']['45']['4']['du']= 1.67;
        $dw['0.05']['45']['5']=[];
        $dw['0.05']['45']['5']['dl']= 1.34;$dw['0.05']['45']['5']['du']= 1.72;
        $dw['0.05']['45']['6']=[];
        $dw['0.05']['45']['6']['dl']= 1.29;$dw['0.05']['45']['6']['du']= 1.78;

        $dw['0.05']['50']=[];
        $dw['0.05']['50']['2']=[];
        $dw['0.05']['50']['2']['dl']= 1.5;$dw['0.05']['50']['2']['du']= 1.59;
        $dw['0.05']['50']['3']=[];
        $dw['0.05']['50']['3']['dl']= 1.46;$dw['0.05']['50']['3']['du']= 1.63;
        $dw['0.05']['50']['4']=[];
        $dw['0.05']['50']['4']['dl']= 1.42;$dw['0.05']['50']['4']['du']= 1.67;
        $dw['0.05']['50']['5']=[];
        $dw['0.05']['50']['5']['dl']= 1.38;$dw['0.05']['50']['5']['du']= 1.72;
        $dw['0.05']['50']['6']=[];
        $dw['0.05']['50']['6']['dl']= 1.34;$dw['0.05']['50']['6']['du']= 1.77;

        $dw['0.05']['55']=[];
        $dw['0.05']['55']['2']=[];
        $dw['0.05']['55']['2']['dl']= 1.53;$dw['0.05']['55']['2']['du']= 1.6;
        $dw['0.05']['55']['3']=[];
        $dw['0.05']['55']['3']['dl']= 1.49;$dw['0.05']['55']['3']['du']= 1.64;
        $dw['0.05']['55']['4']=[];
        $dw['0.05']['55']['4']['dl']= 1.45;$dw['0.05']['55']['4']['du']= 1.68;
        $dw['0.05']['55']['5']=[];
        $dw['0.05']['55']['5']['dl']= 1.41;$dw['0.05']['55']['5']['du']= 1.72;
        $dw['0.05']['55']['6']=[];
        $dw['0.05']['55']['6']['dl']= 1.38;$dw['0.05']['55']['6']['du']= 1.77;

        $dw['0.05']['60']=[];
        $dw['0.05']['60']['2']=[];
        $dw['0.05']['60']['2']['dl']= 1.55;$dw['0.05']['60']['2']['du']= 1.62;
        $dw['0.05']['60']['3']=[];
        $dw['0.05']['60']['3']['dl']= 1.51;$dw['0.05']['60']['3']['du']= 1.65;
        $dw['0.05']['60']['4']=[];
        $dw['0.05']['60']['4']['dl']= 1.48;$dw['0.05']['60']['4']['du']= 1.69;
        $dw['0.05']['60']['5']=[];
        $dw['0.05']['60']['5']['dl']= 1.44;$dw['0.05']['60']['5']['du']= 1.73;
        $dw['0.05']['60']['6']=[];
        $dw['0.05']['60']['6']['dl']= 1.41;$dw['0.05']['60']['6']['du']= 1.77;

        $dw['0.05']['65']=[];
        $dw['0.05']['65']['2']=[];
        $dw['0.05']['65']['2']['dl']= 1.57;$dw['0.05']['65']['2']['du']= 1.63;
        $dw['0.05']['65']['3']=[];
        $dw['0.05']['65']['3']['dl']= 1.54;$dw['0.05']['65']['3']['du']= 1.66;
        $dw['0.05']['65']['4']=[];
        $dw['0.05']['65']['4']['dl']= 1.5;$dw['0.05']['65']['4']['du']= 1.7;
        $dw['0.05']['65']['5']=[];
        $dw['0.05']['65']['5']['dl']= 1.47;$dw['0.05']['65']['5']['du']= 1.73;
        $dw['0.05']['65']['6']=[];
        $dw['0.05']['65']['6']['dl']= 1.44;$dw['0.05']['65']['6']['du']= 1.77;

        $dw['0.05']['70']=[];
        $dw['0.05']['70']['2']=[];
        $dw['0.05']['70']['2']['dl']= 1.58;$dw['0.05']['70']['2']['du']= 1.64;
        $dw['0.05']['70']['3']=[];
        $dw['0.05']['70']['3']['dl']= 1.55;$dw['0.05']['70']['3']['du']= 1.67;
        $dw['0.05']['70']['4']=[];
        $dw['0.05']['70']['4']['dl']= 1.52;$dw['0.05']['70']['4']['du']= 1.7;
        $dw['0.05']['70']['5']=[];
        $dw['0.05']['70']['5']['dl']= 1.49;$dw['0.05']['70']['5']['du']= 1.74;
        $dw['0.05']['70']['6']=[];
        $dw['0.05']['70']['6']['dl']= 1.46;$dw['0.05']['70']['6']['du']= 1.77;

        $dw['0.05']['75']=[];
        $dw['0.05']['75']['2']=[];
        $dw['0.05']['75']['2']['dl']= 1.6;$dw['0.05']['75']['2']['du']= 1.65;
        $dw['0.05']['75']['3']=[];
        $dw['0.05']['75']['3']['dl']= 1.57;$dw['0.05']['75']['3']['du']= 1.68;
        $dw['0.05']['75']['4']=[];
        $dw['0.05']['75']['4']['dl']= 1.54;$dw['0.05']['75']['4']['du']= 1.71;
        $dw['0.05']['75']['5']=[];
        $dw['0.05']['75']['5']['dl']= 1.51;$dw['0.05']['75']['5']['du']= 1.74;
        $dw['0.05']['75']['6']=[];
        $dw['0.05']['75']['6']['dl']= 1.49;$dw['0.05']['75']['6']['du']= 1.77;

        $dw['0.05']['80']=[];
        $dw['0.05']['80']['2']=[];
        $dw['0.05']['80']['2']['dl']= 1.61;$dw['0.05']['80']['2']['du']= 1.66;
        $dw['0.05']['80']['3']=[];
        $dw['0.05']['80']['3']['dl']= 1.59;$dw['0.05']['80']['3']['du']= 1.69;
        $dw['0.05']['80']['4']=[];
        $dw['0.05']['80']['4']['dl']= 1.56;$dw['0.05']['80']['4']['du']= 1.72;
        $dw['0.05']['80']['5']=[];
        $dw['0.05']['80']['5']['dl']= 1.53;$dw['0.05']['80']['5']['du']= 1.74;
        $dw['0.05']['80']['6']=[];
        $dw['0.05']['80']['6']['dl']= 1.51;$dw['0.05']['80']['6']['du']= 1.77;

        $dw['0.05']['85']=[];
        $dw['0.05']['85']['2']=[];
        $dw['0.05']['85']['2']['dl']= 1.62;$dw['0.05']['85']['2']['du']= 1.67;
        $dw['0.05']['85']['3']=[];
        $dw['0.05']['85']['3']['dl']= 1.6;$dw['0.05']['85']['3']['du']= 1.7;
        $dw['0.05']['85']['4']=[];
        $dw['0.05']['85']['4']['dl']= 1.57;$dw['0.05']['85']['4']['du']= 1.72;
        $dw['0.05']['85']['5']=[];
        $dw['0.05']['85']['5']['dl']= 1.55;$dw['0.05']['85']['5']['du']= 1.75;
        $dw['0.05']['85']['6']=[];
        $dw['0.05']['85']['6']['dl']= 1.52;$dw['0.05']['85']['6']['du']= 1.77;

        $dw['0.05']['90']=[];
        $dw['0.05']['90']['2']=[];
        $dw['0.05']['90']['2']['dl']= 1.63;$dw['0.05']['90']['2']['du']= 1.68;
        $dw['0.05']['90']['3']=[];
        $dw['0.05']['90']['3']['dl']= 1.61;$dw['0.05']['90']['3']['du']= 1.7;
        $dw['0.05']['90']['4']=[];
        $dw['0.05']['90']['4']['dl']= 1.59;$dw['0.05']['90']['4']['du']= 1.73;
        $dw['0.05']['90']['5']=[];
        $dw['0.05']['90']['5']['dl']= 1.57;$dw['0.05']['90']['5']['du']= 1.75;
        $dw['0.05']['90']['6']=[];
        $dw['0.05']['90']['6']['dl']= 1.54;$dw['0.05']['90']['6']['du']= 1.78;

        $dw['0.05']['95']=[];
        $dw['0.05']['95']['2']=[];
        $dw['0.05']['95']['2']['dl']= 1.64;$dw['0.05']['95']['2']['du']= 1.69;
        $dw['0.05']['95']['3']=[];
        $dw['0.05']['95']['3']['dl']= 1.62;$dw['0.05']['95']['3']['du']= 1.71;
        $dw['0.05']['95']['4']=[];
        $dw['0.05']['95']['4']['dl']= 1.6;$dw['0.05']['95']['4']['du']= 1.73;
        $dw['0.05']['95']['5']=[];
        $dw['0.05']['95']['5']['dl']= 1.58;$dw['0.05']['95']['5']['du']= 1.75;
        $dw['0.05']['95']['6']=[];
        $dw['0.05']['95']['6']['dl']= 1.56;$dw['0.05']['95']['6']['du']= 1.78;

        $dw['0.05']['100']=[];
        $dw['0.05']['100']['2']=[];
        $dw['0.05']['100']['2']['dl']= 1.65;$dw['0.05']['100']['2']['du']= 1.69;
        $dw['0.05']['100']['3']=[];
        $dw['0.05']['100']['3']['dl']= 1.63;$dw['0.05']['100']['3']['du']= 1.72;
        $dw['0.05']['100']['4']=[];
        $dw['0.05']['100']['4']['dl']= 1.61;$dw['0.05']['100']['4']['du']= 1.74;
        $dw['0.05']['100']['5']=[];
        $dw['0.05']['100']['5']['dl']= 1.59;$dw['0.05']['100']['5']['du']= 1.76;
        $dw['0.05']['100']['6']=[];
        $dw['0.05']['100']['6']['dl']= 1.57;$dw['0.05']['100']['6']['du']= 1.78;

        // 0.01

        $dw['0.01']=[];

        $dw['0.01']['15']=[];

        $dw['0.01']['15']['2']=[];
        $dw['0.01']['15']['2']['dl']= 0.81;$dw['0.01']['15']['2']['du']= 1.07;
        $dw['0.01']['15']['3']=[];
        $dw['0.01']['15']['3']['dl']= 0.7;$dw['0.01']['15']['3']['du']= 1.25;
        $dw['0.01']['15']['4']=[];
        $dw['0.01']['15']['4']['dl']= 0.59;$dw['0.01']['15']['4']['du']= 1.46;
        $dw['0.01']['15']['5']=[];
        $dw['0.01']['15']['5']['dl']= 0.49;$dw['0.01']['15']['5']['du']= 1.7;
        $dw['0.01']['15']['6']=[];
        $dw['0.01']['15']['6']['dl']= 0.39;$dw['0.01']['15']['6']['du']= 1.96;

        $dw['0.01']['16']=[];
        $dw['0.01']['16']['2']=[];
        $dw['0.01']['16']['2']['dl']= 0.84;$dw['0.01']['16']['2']['du']= 1.09;
        $dw['0.01']['16']['3']=[];
        $dw['0.01']['16']['3']['dl']= 0.74;$dw['0.01']['16']['3']['du']= 1.25;
        $dw['0.01']['16']['4']=[];
        $dw['0.01']['16']['4']['dl']= 0.63;$dw['0.01']['16']['4']['du']= 1.44;
        $dw['0.01']['16']['5']=[];
        $dw['0.01']['16']['5']['dl']= 0.53;$dw['0.01']['16']['5']['du']= 1.66;
        $dw['0.01']['16']['6']=[];
        $dw['0.01']['16']['6']['dl']= 0.44;$dw['0.01']['16']['6']['du']= 1.9;

        $dw['0.01']['17']=[];
        $dw['0.01']['17']['2']=[];
        $dw['0.01']['17']['2']['dl']= 0.87;$dw['0.01']['17']['2']['du']= 1.1;
        $dw['0.01']['17']['3']=[];
        $dw['0.01']['17']['3']['dl']= 0.77;$dw['0.01']['17']['3']['du']= 1.25;
        $dw['0.01']['17']['4']=[];
        $dw['0.01']['17']['4']['dl']= 0.67;$dw['0.01']['17']['4']['du']= 1.43;
        $dw['0.01']['17']['5']=[];
        $dw['0.01']['17']['5']['dl']= 0.57;$dw['0.01']['17']['5']['du']= 1.63;
        $dw['0.01']['17']['6']=[];
        $dw['0.01']['17']['6']['dl']= 0.48;$dw['0.01']['17']['6']['du']= 1.85;

        $dw['0.01']['18']=[];
        $dw['0.01']['18']['2']=[];
        $dw['0.01']['18']['2']['dl']= 0.9;$dw['0.01']['18']['2']['du']= 1.12;
        $dw['0.01']['18']['3']=[];
        $dw['0.01']['18']['3']['dl']= 0.8;$dw['0.01']['18']['3']['du']= 1.26;
        $dw['0.01']['18']['4']=[];
        $dw['0.01']['18']['4']['dl']= 0.71;$dw['0.01']['18']['4']['du']= 1.42;
        $dw['0.01']['18']['5']=[];
        $dw['0.01']['18']['5']['dl']= 0.61;$dw['0.01']['18']['5']['du']= 1.6;
        $dw['0.01']['18']['6']=[];
        $dw['0.01']['18']['6']['dl']= 0.52;$dw['0.01']['18']['6']['du']= 1.8;

        $dw['0.01']['19']=[];
        $dw['0.01']['19']['2']=[];
        $dw['0.01']['19']['2']['dl']= 0.93;$dw['0.01']['19']['2']['du']= 1.13;
        $dw['0.01']['19']['3']=[];
        $dw['0.01']['19']['3']['dl']= 0.83;$dw['0.01']['19']['3']['du']= 1.26;
        $dw['0.01']['19']['4']=[];
        $dw['0.01']['19']['4']['dl']= 0.74;$dw['0.01']['19']['4']['du']= 1.41;
        $dw['0.01']['19']['5']=[];
        $dw['0.01']['19']['5']['dl']= 0.65;$dw['0.01']['19']['5']['du']= 1.58;
        $dw['0.01']['19']['6']=[];
        $dw['0.01']['19']['6']['dl']= 0.56;$dw['0.01']['19']['6']['du']= 1.77;

        $dw['0.01']['20']=[];
        $dw['0.01']['20']['2']=[];
        $dw['0.01']['20']['2']['dl']= 0.95;$dw['0.01']['20']['2']['du']= 1.15;
        $dw['0.01']['20']['3']=[];
        $dw['0.01']['20']['3']['dl']= 0.86;$dw['0.01']['20']['3']['du']= 1.27;
        $dw['0.01']['20']['4']=[];
        $dw['0.01']['20']['4']['dl']= 0.77;$dw['0.01']['20']['4']['du']= 1.41;
        $dw['0.01']['20']['5']=[];
        $dw['0.01']['20']['5']['dl']= 0.68;$dw['0.01']['20']['5']['du']= 1.57;
        $dw['0.01']['20']['6']=[];
        $dw['0.01']['20']['6']['dl']= 0.6;$dw['0.01']['20']['6']['du']= 1.74;

        $dw['0.01']['21']=[];
        $dw['0.01']['21']['2']=[];
        $dw['0.01']['21']['2']['dl']= 0.97;$dw['0.01']['21']['2']['du']= 1.16;
        $dw['0.01']['21']['3']=[];
        $dw['0.01']['21']['3']['dl']= 0.89;$dw['0.01']['21']['3']['du']= 1.27;
        $dw['0.01']['21']['4']=[];
        $dw['0.01']['21']['4']['dl']= 0.8;$dw['0.01']['21']['4']['du']= 1.41;
        $dw['0.01']['21']['5']=[];
        $dw['0.01']['21']['5']['dl']= 0.72;$dw['0.01']['21']['5']['du']= 1.55;
        $dw['0.01']['21']['6']=[];
        $dw['0.01']['21']['6']['dl']= 0.63;$dw['0.01']['21']['6']['du']= 1.71;

        $dw['0.01']['22']=[];
        $dw['0.01']['22']['2']=[];
        $dw['0.01']['22']['2']['dl']= 1;$dw['0.01']['22']['2']['du']= 1.17;
        $dw['0.01']['22']['3']=[];
        $dw['0.01']['22']['3']['dl']= 0.91;$dw['0.01']['22']['3']['du']= 1.28;
        $dw['0.01']['22']['4']=[];
        $dw['0.01']['22']['4']['dl']= 0.83;$dw['0.01']['22']['4']['du']= 1.4;
        $dw['0.01']['22']['5']=[];
        $dw['0.01']['22']['5']['dl']= 0.75;$dw['0.01']['22']['5']['du']= 1.54;
        $dw['0.01']['22']['6']=[];
        $dw['0.01']['22']['6']['dl']= 0.66;$dw['0.01']['22']['6']['du']= 1.69;

        $dw['0.01']['23']=[];
        $dw['0.01']['23']['2']=[];
        $dw['0.01']['23']['2']['dl']= 1.02;$dw['0.01']['23']['2']['du']= 1.19;
        $dw['0.01']['23']['3']=[];
        $dw['0.01']['23']['3']['dl']= 0.94;$dw['0.01']['23']['3']['du']= 1.29;
        $dw['0.01']['23']['4']=[];
        $dw['0.01']['23']['4']['dl']= 0.86;$dw['0.01']['23']['4']['du']= 1.4;
        $dw['0.01']['23']['5']=[];
        $dw['0.01']['23']['5']['dl']= 0.77;$dw['0.01']['23']['5']['du']= 1.53;
        $dw['0.01']['23']['6']=[];
        $dw['0.01']['23']['6']['dl']= 0.7;$dw['0.01']['23']['6']['du']= 1.67;

        $dw['0.01']['24']=[];
        $dw['0.01']['24']['2']=[];
        $dw['0.01']['24']['2']['dl']= 1.04;$dw['0.01']['24']['2']['du']= 1.2;
        $dw['0.01']['24']['3']=[];
        $dw['0.01']['24']['3']['dl']= 0.96;$dw['0.01']['24']['3']['du']= 1.3;
        $dw['0.01']['24']['4']=[];
        $dw['0.01']['24']['4']['dl']= 0.88;$dw['0.01']['24']['4']['du']= 1.41;
        $dw['0.01']['24']['5']=[];
        $dw['0.01']['24']['5']['dl']= 0.8;$dw['0.01']['24']['5']['du']= 1.53;
        $dw['0.01']['24']['6']=[];
        $dw['0.01']['24']['6']['dl']= 0.72;$dw['0.01']['24']['6']['du']= 1.66;

        $dw['0.01']['25']=[];
        $dw['0.01']['25']['2']=[];
        $dw['0.01']['25']['2']['dl']= 1.05;$dw['0.01']['25']['2']['du']= 1.21;
        $dw['0.01']['25']['3']=[];
        $dw['0.01']['25']['3']['dl']= 0.98;$dw['0.01']['25']['3']['du']= 1.3;
        $dw['0.01']['25']['4']=[];
        $dw['0.01']['25']['4']['dl']= 0.9;$dw['0.01']['25']['4']['du']= 1.41;
        $dw['0.01']['25']['5']=[];
        $dw['0.01']['25']['5']['dl']= 0.83;$dw['0.01']['25']['5']['du']= 1.52;
        $dw['0.01']['25']['6']=[];
        $dw['0.01']['25']['6']['dl']= 0.75;$dw['0.01']['25']['6']['du']= 1.65;

        $dw['0.01']['26']=[];
        $dw['0.01']['26']['2']=[];
        $dw['0.01']['26']['2']['dl']= 1.07;$dw['0.01']['26']['2']['du']= 1.22;
        $dw['0.01']['26']['3']=[];
        $dw['0.01']['26']['3']['dl']= 1;$dw['0.01']['26']['3']['du']= 1.31;
        $dw['0.01']['26']['4']=[];
        $dw['0.01']['26']['4']['dl']= 0.93;$dw['0.01']['26']['4']['du']= 1.41;
        $dw['0.01']['26']['5']=[];
        $dw['0.01']['26']['5']['dl']= 0.85;$dw['0.01']['26']['5']['du']= 1.52;
        $dw['0.01']['26']['6']=[];
        $dw['0.01']['26']['6']['dl']= 0.78;$dw['0.01']['26']['6']['du']= 1.64;

        $dw['0.01']['27']=[];
        $dw['0.01']['27']['2']=[];
        $dw['0.01']['27']['2']['dl']= 1.09;$dw['0.01']['27']['2']['du']= 1.23;
        $dw['0.01']['27']['3']=[];
        $dw['0.01']['27']['3']['dl']= 1.02;$dw['0.01']['27']['3']['du']= 1.32;
        $dw['0.01']['27']['4']=[];
        $dw['0.01']['27']['4']['dl']= 0.95;$dw['0.01']['27']['4']['du']= 1.41;
        $dw['0.01']['27']['5']=[];
        $dw['0.01']['27']['5']['dl']= 0.88;$dw['0.01']['27']['5']['du']= 1.51;
        $dw['0.01']['27']['6']=[];
        $dw['0.01']['27']['6']['dl']= 0.81;$dw['0.01']['27']['6']['du']= 1.63;

        $dw['0.01']['28']=[];
        $dw['0.01']['28']['2']=[];
        $dw['0.01']['28']['2']['dl']= 1.1;$dw['0.01']['28']['2']['du']= 1.24;
        $dw['0.01']['28']['3']=[];
        $dw['0.01']['28']['3']['dl']= 1.04;$dw['0.01']['28']['3']['du']= 1.32;
        $dw['0.01']['28']['4']=[];
        $dw['0.01']['28']['4']['dl']= 0.97;$dw['0.01']['28']['4']['du']= 1.41;
        $dw['0.01']['28']['5']=[];
        $dw['0.01']['28']['5']['dl']= 0.9;$dw['0.01']['28']['5']['du']= 1.51;
        $dw['0.01']['28']['6']=[];
        $dw['0.01']['28']['6']['dl']= 0.83;$dw['0.01']['28']['6']['du']= 1.62;

        $dw['0.01']['29']=[];
        $dw['0.01']['29']['2']=[];
        $dw['0.01']['29']['2']['dl']= 1.12;$dw['0.01']['29']['2']['du']= 1.25;
        $dw['0.01']['29']['3']=[];
        $dw['0.01']['29']['3']['dl']= 1.05;$dw['0.01']['29']['3']['du']= 1.33;
        $dw['0.01']['29']['4']=[];
        $dw['0.01']['29']['4']['dl']= 0.99;$dw['0.01']['29']['4']['du']= 1.42;
        $dw['0.01']['29']['5']=[];
        $dw['0.01']['29']['5']['dl']= 0.92;$dw['0.01']['29']['5']['du']= 1.51;
        $dw['0.01']['29']['6']=[];
        $dw['0.01']['29']['6']['dl']= 0.85;$dw['0.01']['29']['6']['du']= 1.61;

        $dw['0.01']['30']=[];
        $dw['0.01']['30']['2']=[];
        $dw['0.01']['30']['2']['dl']= 1.13;$dw['0.01']['30']['2']['du']= 1.26;
        $dw['0.01']['30']['3']=[];
        $dw['0.01']['30']['3']['dl']= 1.07;$dw['0.01']['30']['3']['du']= 1.34;
        $dw['0.01']['30']['4']=[];
        $dw['0.01']['30']['4']['dl']= 1.01;$dw['0.01']['30']['4']['du']= 1.42;
        $dw['0.01']['30']['5']=[];
        $dw['0.01']['30']['5']['dl']= 0.94;$dw['0.01']['30']['5']['du']= 1.51;
        $dw['0.01']['30']['6']=[];
        $dw['0.01']['30']['6']['dl']= 0.88;$dw['0.01']['30']['6']['du']= 1.61;

        $dw['0.01']['31']=[];
        $dw['0.01']['31']['2']=[];
        $dw['0.01']['31']['2']['dl']= 1.15;$dw['0.01']['31']['2']['du']= 1.27;
        $dw['0.01']['31']['3']=[];
        $dw['0.01']['31']['3']['dl']= 1.08;$dw['0.01']['31']['3']['du']= 1.34;
        $dw['0.01']['31']['4']=[];
        $dw['0.01']['31']['4']['dl']= 1.02;$dw['0.01']['31']['4']['du']= 1.42;
        $dw['0.01']['31']['5']=[];
        $dw['0.01']['31']['5']['dl']= 0.96;$dw['0.01']['31']['5']['du']= 1.51;
        $dw['0.01']['31']['6']=[];
        $dw['0.01']['31']['6']['dl']= 0.9;$dw['0.01']['31']['6']['du']= 1.6;

        $dw['0.01']['32']=[];
        $dw['0.01']['32']['2']=[];
        $dw['0.01']['32']['2']['dl']= 1.16;$dw['0.01']['32']['2']['du']= 1.28;
        $dw['0.01']['32']['3']=[];
        $dw['0.01']['32']['3']['dl']= 1.1;$dw['0.01']['32']['3']['du']= 1.35;
        $dw['0.01']['32']['4']=[];
        $dw['0.01']['32']['4']['dl']= 1.04;$dw['0.01']['32']['4']['du']= 1.43;
        $dw['0.01']['32']['5']=[];
        $dw['0.01']['32']['5']['dl']= 0.98;$dw['0.01']['32']['5']['du']= 1.51;
        $dw['0.01']['32']['6']=[];
        $dw['0.01']['32']['6']['dl']= 0.92;$dw['0.01']['32']['6']['du']= 1.6;

        $dw['0.01']['33']=[];
        $dw['0.01']['33']['2']=[];
        $dw['0.01']['33']['2']['dl']= 1.17;$dw['0.01']['33']['2']['du']= 1.29;
        $dw['0.01']['33']['3']=[];
        $dw['0.01']['33']['3']['dl']= 1.11;$dw['0.01']['33']['3']['du']= 1.36;
        $dw['0.01']['33']['4']=[];
        $dw['0.01']['33']['4']['dl']= 1.05;$dw['0.01']['33']['4']['du']= 1.43;
        $dw['0.01']['33']['5']=[];
        $dw['0.01']['33']['5']['dl']= 1;$dw['0.01']['33']['5']['du']= 1.51;
        $dw['0.01']['33']['6']=[];
        $dw['0.01']['33']['6']['dl']= 0.94;$dw['0.01']['33']['6']['du']= 1.59;

        $dw['0.01']['34']=[];
        $dw['0.01']['34']['2']=[];
        $dw['0.01']['34']['2']['dl']= 1.18;$dw['0.01']['34']['2']['du']= 1.3;
        $dw['0.01']['34']['3']=[];
        $dw['0.01']['34']['3']['dl']= 1.13;$dw['0.01']['34']['3']['du']= 1.36;
        $dw['0.01']['34']['4']=[];
        $dw['0.01']['34']['4']['dl']= 1.07;$dw['0.01']['34']['4']['du']= 1.43;
        $dw['0.01']['34']['5']=[];
        $dw['0.01']['34']['5']['dl']= 1.01;$dw['0.01']['34']['5']['du']= 1.51;
        $dw['0.01']['34']['6']=[];
        $dw['0.01']['34']['6']['dl']= 0.95;$dw['0.01']['34']['6']['du']= 1.59;

        $dw['0.01']['35']=[];
        $dw['0.01']['35']['2']=[];
        $dw['0.01']['35']['2']['dl']= 1.19;$dw['0.01']['35']['2']['du']= 1.31;
        $dw['0.01']['35']['3']=[];
        $dw['0.01']['35']['3']['dl']= 1.14;$dw['0.01']['35']['3']['du']= 1.37;
        $dw['0.01']['35']['4']=[];
        $dw['0.01']['35']['4']['dl']= 1.08;$dw['0.01']['35']['4']['du']= 1.44;
        $dw['0.01']['35']['5']=[];
        $dw['0.01']['35']['5']['dl']= 1.03;$dw['0.01']['35']['5']['du']= 1.51;
        $dw['0.01']['35']['6']=[];
        $dw['0.01']['35']['6']['dl']= 0.97;$dw['0.01']['35']['6']['du']= 1.59;

        $dw['0.01']['36']=[];
        $dw['0.01']['36']['2']=[];
        $dw['0.01']['36']['2']['dl']= 1.21;$dw['0.01']['36']['2']['du']= 1.32;
        $dw['0.01']['36']['3']=[];
        $dw['0.01']['36']['3']['dl']= 1.15;$dw['0.01']['36']['3']['du']= 1.38;
        $dw['0.01']['36']['4']=[];
        $dw['0.01']['36']['4']['dl']= 1.1;$dw['0.01']['36']['4']['du']= 1.44;
        $dw['0.01']['36']['5']=[];
        $dw['0.01']['36']['5']['dl']= 1.04;$dw['0.01']['36']['5']['du']= 1.51;
        $dw['0.01']['36']['6']=[];
        $dw['0.01']['36']['6']['dl']= 0.99;$dw['0.01']['36']['6']['du']= 1.59;

        $dw['0.01']['37']=[];
        $dw['0.01']['37']['2']=[];
        $dw['0.01']['37']['2']['dl']= 1.22;$dw['0.01']['37']['2']['du']= 1.32;
        $dw['0.01']['37']['3']=[];
        $dw['0.01']['37']['3']['dl']= 1.16;$dw['0.01']['37']['3']['du']= 1.38;
        $dw['0.01']['37']['4']=[];
        $dw['0.01']['37']['4']['dl']= 1.11;$dw['0.01']['37']['4']['du']= 1.45;
        $dw['0.01']['37']['5']=[];
        $dw['0.01']['37']['5']['dl']= 1.06;$dw['0.01']['37']['5']['du']= 1.51;
        $dw['0.01']['37']['6']=[];
        $dw['0.01']['37']['6']['dl']= 1;$dw['0.01']['37']['6']['du']= 1.59;

        $dw['0.01']['38']=[];
        $dw['0.01']['38']['2']=[];
        $dw['0.01']['38']['2']['dl']= 1.23;$dw['0.01']['38']['2']['du']= 1.33;
        $dw['0.01']['38']['3']=[];
        $dw['0.01']['38']['3']['dl']= 1.18;$dw['0.01']['38']['3']['du']= 1.39;
        $dw['0.01']['38']['4']=[];
        $dw['0.01']['38']['4']['dl']= 1.12;$dw['0.01']['38']['4']['du']= 1.45;
        $dw['0.01']['38']['5']=[];
        $dw['0.01']['38']['5']['dl']= 1.07;$dw['0.01']['38']['5']['du']= 1.52;
        $dw['0.01']['38']['6']=[];
        $dw['0.01']['38']['6']['dl']= 1.02;$dw['0.01']['38']['6']['du']= 1.58;

        $dw['0.01']['39']=[];
        $dw['0.01']['39']['2']=[];
        $dw['0.01']['39']['2']['dl']= 1.24;$dw['0.01']['39']['2']['du']= 1.34;
        $dw['0.01']['39']['3']=[];
        $dw['0.01']['39']['3']['dl']= 1.19;$dw['0.01']['39']['3']['du']= 1.39;
        $dw['0.01']['39']['4']=[];
        $dw['0.01']['39']['4']['dl']= 1.14;$dw['0.01']['39']['4']['du']= 1.45;
        $dw['0.01']['39']['5']=[];
        $dw['0.01']['39']['5']['dl']= 1.09;$dw['0.01']['39']['5']['du']= 1.52;
        $dw['0.01']['39']['6']=[];
        $dw['0.01']['39']['6']['dl']= 1.03;$dw['0.01']['39']['6']['du']= 1.58;

        $dw['0.01']['40']=[];
        $dw['0.01']['40']['2']=[];
        $dw['0.01']['40']['2']['dl']= 1.25;$dw['0.01']['40']['2']['du']= 1.34;
        $dw['0.01']['40']['3']=[];
        $dw['0.01']['40']['3']['dl']= 1.2;$dw['0.01']['40']['3']['du']= 1.4;
        $dw['0.01']['40']['4']=[];
        $dw['0.01']['40']['4']['dl']= 1.15;$dw['0.01']['40']['4']['du']= 1.46;
        $dw['0.01']['40']['5']=[];
        $dw['0.01']['40']['5']['dl']= 1.1;$dw['0.01']['40']['5']['du']= 1.52;
        $dw['0.01']['40']['6']=[];
        $dw['0.01']['40']['6']['dl']= 1.05;$dw['0.01']['40']['6']['du']= 1.58;

        $dw['0.01']['45']=[];
        $dw['0.01']['45']['2']=[];
        $dw['0.01']['45']['2']['dl']= 1.29;$dw['0.01']['45']['2']['du']= 1.38;
        $dw['0.01']['45']['3']=[];
        $dw['0.01']['45']['3']['dl']= 1.24;$dw['0.01']['45']['3']['du']= 1.42;
        $dw['0.01']['45']['4']=[];
        $dw['0.01']['45']['4']['dl']= 1.2;$dw['0.01']['45']['4']['du']= 1.48;
        $dw['0.01']['45']['5']=[];
        $dw['0.01']['45']['5']['dl']= 1.16;$dw['0.01']['45']['5']['du']= 1.53;
        $dw['0.01']['45']['6']=[];
        $dw['0.01']['45']['6']['dl']= 1.11;$dw['0.01']['45']['6']['du']= 1.58;

        $dw['0.01']['50']=[];
        $dw['0.01']['50']['2']=[];
        $dw['0.01']['50']['2']['dl']= 1.32;$dw['0.01']['50']['2']['du']= 1.4;
        $dw['0.01']['50']['3']=[];
        $dw['0.01']['50']['3']['dl']= 1.28;$dw['0.01']['50']['3']['du']= 1.45;
        $dw['0.01']['50']['4']=[];
        $dw['0.01']['50']['4']['dl']= 1.24;$dw['0.01']['50']['4']['du']= 1.49;
        $dw['0.01']['50']['5']=[];
        $dw['0.01']['50']['5']['dl']= 1.2;$dw['0.01']['50']['5']['du']= 1.54;
        $dw['0.01']['50']['6']=[];
        $dw['0.01']['50']['6']['dl']= 1.16;$dw['0.01']['50']['6']['du']= 1.59;

        $dw['0.01']['55']=[];
        $dw['0.01']['55']['2']=[];
        $dw['0.01']['55']['2']['dl']= 1.36;$dw['0.01']['55']['2']['du']= 1.43;
        $dw['0.01']['55']['3']=[];
        $dw['0.01']['55']['3']['dl']= 1.32;$dw['0.01']['55']['3']['du']= 1.47;
        $dw['0.01']['55']['4']=[];
        $dw['0.01']['55']['4']['dl']= 1.28;$dw['0.01']['55']['4']['du']= 1.51;
        $dw['0.01']['55']['5']=[];
        $dw['0.01']['55']['5']['dl']= 1.25;$dw['0.01']['55']['5']['du']= 1.55;
        $dw['0.01']['55']['6']=[];
        $dw['0.01']['55']['6']['dl']= 1.21;$dw['0.01']['55']['6']['du']= 1.59;

        $dw['0.01']['60']=[];
        $dw['0.01']['60']['2']=[];
        $dw['0.01']['60']['2']['dl']= 1.38;$dw['0.01']['60']['2']['du']= 1.45;
        $dw['0.01']['60']['3']=[];
        $dw['0.01']['60']['3']['dl']= 1.35;$dw['0.01']['60']['3']['du']= 1.48;
        $dw['0.01']['60']['4']=[];
        $dw['0.01']['60']['4']['dl']= 1.32;$dw['0.01']['60']['4']['du']= 1.52;
        $dw['0.01']['60']['5']=[];
        $dw['0.01']['60']['5']['dl']= 1.28;$dw['0.01']['60']['5']['du']= 1.56;
        $dw['0.01']['60']['6']=[];
        $dw['0.01']['60']['6']['dl']= 1.25;$dw['0.01']['60']['6']['du']= 1.6;

        $dw['0.01']['65']=[];
        $dw['0.01']['65']['2']=[];
        $dw['0.01']['65']['2']['dl']= 1.41;$dw['0.01']['65']['2']['du']= 1.47;
        $dw['0.01']['65']['3']=[];
        $dw['0.01']['65']['3']['dl']= 1.38;$dw['0.01']['65']['3']['du']= 1.5;
        $dw['0.01']['65']['4']=[];
        $dw['0.01']['65']['4']['dl']= 1.35;$dw['0.01']['65']['4']['du']= 1.53;
        $dw['0.01']['65']['5']=[];
        $dw['0.01']['65']['5']['dl']= 1.31;$dw['0.01']['65']['5']['du']= 1.57;
        $dw['0.01']['65']['6']=[];
        $dw['0.01']['65']['6']['dl']= 1.28;$dw['0.01']['65']['6']['du']= 1.61;

        $dw['0.01']['70']=[];
        $dw['0.01']['70']['2']=[];
        $dw['0.01']['70']['2']['dl']= 1.43;$dw['0.01']['70']['2']['du']= 1.49;
        $dw['0.01']['70']['3']=[];
        $dw['0.01']['70']['3']['dl']= 1.4;$dw['0.01']['70']['3']['du']= 1.52;
        $dw['0.01']['70']['4']=[];
        $dw['0.01']['70']['4']['dl']= 1.37;$dw['0.01']['70']['4']['du']= 1.55;
        $dw['0.01']['70']['5']=[];
        $dw['0.01']['70']['5']['dl']= 1.34;$dw['0.01']['70']['5']['du']= 1.58;
        $dw['0.01']['70']['6']=[];
        $dw['0.01']['70']['6']['dl']= 1.31;$dw['0.01']['70']['6']['du']= 1.61;

        $dw['0.01']['75']=[];
        $dw['0.01']['75']['2']=[];
        $dw['0.01']['75']['2']['dl']= 1.45;$dw['0.01']['75']['2']['du']= 1.5;
        $dw['0.01']['75']['3']=[];
        $dw['0.01']['75']['3']['dl']= 1.42;$dw['0.01']['75']['3']['du']= 1.53;
        $dw['0.01']['75']['4']=[];
        $dw['0.01']['75']['4']['dl']= 1.39;$dw['0.01']['75']['4']['du']= 1.56;
        $dw['0.01']['75']['5']=[];
        $dw['0.01']['75']['5']['dl']= 1.37;$dw['0.01']['75']['5']['du']= 1.59;
        $dw['0.01']['75']['6']=[];
        $dw['0.01']['75']['6']['dl']= 1.34;$dw['0.01']['75']['6']['du']= 1.62;

        $dw['0.01']['80']=[];
        $dw['0.01']['80']['2']=[];
        $dw['0.01']['80']['2']['dl']= 1.47;$dw['0.01']['80']['2']['du']= 1.52;
        $dw['0.01']['80']['3']=[];
        $dw['0.01']['80']['3']['dl']= 1.44;$dw['0.01']['80']['3']['du']= 1.54;
        $dw['0.01']['80']['4']=[];
        $dw['0.01']['80']['4']['dl']= 1.42;$dw['0.01']['80']['4']['du']= 1.57;
        $dw['0.01']['80']['5']=[];
        $dw['0.01']['80']['5']['dl']= 1.39;$dw['0.01']['80']['5']['du']= 1.6;
        $dw['0.01']['80']['6']=[];
        $dw['0.01']['80']['6']['dl']= 1.36;$dw['0.01']['80']['6']['du']= 1.62;

        $dw['0.01']['85']=[];
        $dw['0.01']['85']['2']=[];
        $dw['0.01']['85']['2']['dl']= 1.48;$dw['0.01']['85']['2']['du']= 1.53;
        $dw['0.01']['85']['3']=[];
        $dw['0.01']['85']['3']['dl']= 1.46;$dw['0.01']['85']['3']['du']= 1.55;
        $dw['0.01']['85']['4']=[];
        $dw['0.01']['85']['4']['dl']= 1.43;$dw['0.01']['85']['4']['du']= 1.58;
        $dw['0.01']['85']['5']=[];
        $dw['0.01']['85']['5']['dl']= 1.41;$dw['0.01']['85']['5']['du']= 1.6;
        $dw['0.01']['85']['6']=[];
        $dw['0.01']['85']['6']['dl']= 1.39;$dw['0.01']['85']['6']['du']= 1.63;

        $dw['0.01']['90']=[];
        $dw['0.01']['90']['2']=[];
        $dw['0.01']['90']['2']['dl']= 1.5;$dw['0.01']['90']['2']['du']= 1.54;
        $dw['0.01']['90']['3']=[];
        $dw['0.01']['90']['3']['dl']= 1.47;$dw['0.01']['90']['3']['du']= 1.56;
        $dw['0.01']['90']['4']=[];
        $dw['0.01']['90']['4']['dl']= 1.45;$dw['0.01']['90']['4']['du']= 1.59;
        $dw['0.01']['90']['5']=[];
        $dw['0.01']['90']['5']['dl']= 1.43;$dw['0.01']['90']['5']['du']= 1.61;
        $dw['0.01']['90']['6']=[];
        $dw['0.01']['90']['6']['dl']= 1.41;$dw['0.01']['90']['6']['du']= 1.64;

        $dw['0.01']['95']=[];
        $dw['0.01']['95']['2']=[];
        $dw['0.01']['95']['2']['dl']= 1.51;$dw['0.01']['95']['2']['du']= 1.55;
        $dw['0.01']['95']['3']=[];
        $dw['0.01']['95']['3']['dl']= 1.49;$dw['0.01']['95']['3']['du']= 1.57;
        $dw['0.01']['95']['4']=[];
        $dw['0.01']['95']['4']['dl']= 1.47;$dw['0.01']['95']['4']['du']= 1.6;
        $dw['0.01']['95']['5']=[];
        $dw['0.01']['95']['5']['dl']= 1.45;$dw['0.01']['95']['5']['du']= 1.62;
        $dw['0.01']['95']['6']=[];
        $dw['0.01']['95']['6']['dl']= 1.42;$dw['0.01']['95']['6']['du']= 1.64;

        $dw['0.01']['100']=[];
        $dw['0.01']['100']['2']=[];
        $dw['0.01']['100']['2']['dl']= 1.52;$dw['0.01']['100']['2']['du']= 1.56;
        $dw['0.01']['100']['3']=[];
        $dw['0.01']['100']['3']['dl']= 1.5;$dw['0.01']['100']['3']['du']= 1.58;
        $dw['0.01']['100']['4']=[];
        $dw['0.01']['100']['4']['dl']= 1.48;$dw['0.01']['100']['4']['du']= 1.6;
        $dw['0.01']['100']['5']=[];
        $dw['0.01']['100']['5']['dl']= 1.46;$dw['0.01']['100']['5']['du']= 1.63;
        $dw['0.01']['100']['6']=[];
        $dw['0.01']['100']['6']['dl']= 1.44;$dw['0.01']['100']['6']['du']= 1.65;

        $alpha = $alpha.'';
        $n_correct = $n_correct.'';
        $k_correct = $k_correct.'';

        return $dw[$alpha][$n_correct][$k_correct];

    }
    public static function t_Student($v, $alpha){
        $v_correct = self::t_v($v);
        $t = [];

        $t['1']=[];
        $t['1']['0.1']=3.08;$t['1']['0.05']=6.31;$t['1']['0.025']=12.71;$t['1']['0.01']=31.82;$t['1']['0.005']=63.66;

        $t['2']=[];
        $t['2']['0.1']=1.89;$t['2']['0.05']=2.92;$t['2']['0.025']=4.3;$t['2']['0.01']=6.96;$t['2']['0.005']=9.92;
        $t['3']=[];
        $t['3']['0.1']=1.64;$t['3']['0.05']=2.35;$t['3']['0.025']=3.18;$t['3']['0.01']=4.54;$t['3']['0.005']=5.84;
        $t['4']=[];
        $t['4']['0.1']=1.53;$t['4']['0.05']=2.13;$t['4']['0.025']=2.78;$t['4']['0.01']=3.75;$t['4']['0.005']=4.6;
        $t['5']=[];
        $t['5']['0.1']= 1.48;$t['5']['0.05']= 2.02;$t['5']['0.025']= 2.57;$t['5']['0.01']= 3.36;$t['5']['0.005']= 4.03;
        $t['6']=[];
        $t['6']['0.1']= 1.44;$t['6']['0.05']= 1.94;$t['6']['0.025']= 2.45;$t['6']['0.01']= 3.14;$t['6']['0.005']= 3.71;
        $t['7']=[];
        $t['7']['0.1']= 1.41;$t['7']['0.05']= 1.89;$t['7']['0.025']= 2.36;$t['7']['0.01']= 3;$t['7']['0.005']= 3.5;
        $t['8']=[];
        $t['8']['0.1']= 1.4;$t['8']['0.05']= 1.86;$t['8']['0.025']= 2.31;$t['8']['0.01']= 2.9;$t['8']['0.005']= 3.36;
        $t['9']=[];
        $t['9']['0.1']= 1.38;$t['9']['0.05']= 1.83;$t['9']['0.025']= 2.26;$t['9']['0.01']= 2.82;$t['9']['0.005']= 3.25;
        $t['10']=[];
        $t['10']['0.1']= 1.37;$t['10']['0.05']= 1.81;$t['10']['0.025']= 2.23;$t['10']['0.01']= 2.76;$t['10']['0.005']= 3.17;
        $t['11']=[];
        $t['11']['0.1']= 1.36;$t['11']['0.05']= 1.8;$t['11']['0.025']= 2.2;$t['11']['0.01']= 2.72;$t['11']['0.005']= 3.11;
        $t['12']=[];
        $t['12']['0.1']= 1.36;$t['12']['0.05']= 1.78;$t['12']['0.025']= 2.18;$t['12']['0.01']= 2.68;$t['12']['0.005']= 3.05;
        $t['13']=[];
        $t['13']['0.1']= 1.35;$t['13']['0.05']= 1.77;$t['13']['0.025']= 2.16;$t['13']['0.01']= 2.65;$t['13']['0.005']= 3.01;
        $t['14']=[];
        $t['14']['0.1']= 1.34;$t['14']['0.05']= 1.76;$t['14']['0.025']= 2.14;$t['14']['0.01']= 2.62;$t['14']['0.005']= 2.98;
        $t['15']=[];
        $t['15']['0.1']= 1.34;$t['15']['0.05']= 1.75;$t['15']['0.025']= 2.13;$t['15']['0.01']= 2.6;$t['15']['0.005']= 2.95;
        $t['16']=[];
        $t['16']['0.1']= 1.34;$t['16']['0.05']= 1.75;$t['16']['0.025']= 2.12;$t['16']['0.01']= 2.58;$t['16']['0.005']= 2.92;
        $t['17']=[];
        $t['17']['0.1']= 1.33;$t['17']['0.05']= 1.74;$t['17']['0.025']= 2.11;$t['17']['0.01']= 2.57;$t['17']['0.005']= 2.9;
        $t['18']=[];
        $t['18']['0.1']= 1.33;$t['18']['0.05']= 1.73;$t['18']['0.025']= 2.1;$t['18']['0.01']= 2.55;$t['18']['0.005']= 2.88;
        $t['19']=[];
        $t['19']['0.1']= 1.33;$t['19']['0.05']= 1.73;$t['19']['0.025']= 2.09;$t['19']['0.01']= 2.54;$t['19']['0.005']= 2.86;
        $t['20']=[];
        $t['20']['0.1']= 1.33;$t['20']['0.05']= 1.72;$t['20']['0.025']= 2.09;$t['20']['0.01']= 2.53;$t['20']['0.005']= 2.85;
        $t['21']=[];
        $t['21']['0.1']= 1.32;$t['21']['0.05']= 1.72;$t['21']['0.025']= 2.08;$t['21']['0.01']= 2.52;$t['21']['0.005']= 2.83;
        $t['22']=[];
        $t['22']['0.1']= 1.32;$t['22']['0.05']= 1.72;$t['22']['0.025']= 2.07;$t['22']['0.01']= 2.51;$t['22']['0.005']= 2.82;
        $t['23']=[];
        $t['23']['0.1']= 1.32;$t['23']['0.05']= 1.71;$t['23']['0.025']= 2.07;$t['23']['0.01']= 2.5;$t['23']['0.005']= 2.81;
        $t['24']=[];
        $t['24']['0.1']= 1.32;$t['24']['0.05']= 1.71;$t['24']['0.025']= 2.06;$t['24']['0.01']= 2.49;$t['24']['0.005']= 2.8;
        $t['25']=[];
        $t['25']['0.1']= 1.32;$t['25']['0.05']= 1.71;$t['25']['0.025']= 2.06;$t['25']['0.01']= 2.49;$t['25']['0.005']= 2.79;

        $t['30']=[];
        $t['30']['0.1']= 1.31;$t['30']['0.05']= 1.7;$t['30']['0.025']= 2.04;$t['30']['0.01']= 2.46;$t['30']['0.005']= 2.75;

        $t['40']=[];
        $t['40']['0.1']= 1.3;$t['40']['0.05']= 1.68;$t['40']['0.025']= 2.02;$t['40']['0.01']= 2.42;$t['40']['0.005']= 2.7;

        $t['50']=[];
        $t['50']['0.1']= 1.3;$t['50']['0.05']= 1.68;$t['50']['0.025']= 2.01;$t['50']['0.01']= 2.4;$t['50']['0.005']= 2.68;

        $t['60']=[];
        $t['60']['0.1']= 1.3;$t['60']['0.05']= 1.67;$t['60']['0.025']= 2;$t['60']['0.01']= 2.39;$t['60']['0.005']= 2.66;

        $t['70']=[];
        $t['70']['0.1']= 1.29;$t['70']['0.05']= 1.67;$t['70']['0.025']= 1.99;$t['70']['0.01']= 2.38;$t['70']['0.005']= 2.65;

        $t['80']=[];
        $t['80']['0.1']= 1.29;$t['80']['0.05']= 1.66;$t['80']['0.025']= 1.99;$t['80']['0.01']= 2.37;$t['80']['0.005']= 2.64;

        $t['90']=[];
        $t['90']['0.1']= 1.29;$t['90']['0.05']= 1.66;$t['90']['0.025']= 1.99;$t['90']['0.01']= 2.37;$t['90']['0.005']= 2.63;

        $t['100']=[];
        $t['100']['0.1']= 1.29;$t['100']['0.05']= 1.66;$t['100']['0.025']= 1.98;$t['100']['0.01']= 2.36;$t['100']['0.005']= 2.63;

        $t['infinity']=[];
        $t['infinity']['0.1']= 1.28;$t['infinity']['0.05']= 1.64;$t['infinity']['0.025']= 1.96;$t['infinity']['0.01']= 2.33;$t['infinity']['0.005']= 2.58;

        $v_correct = $v_correct.'';
        $alpha = $alpha.'';

        return $t[$v_correct][$alpha];

    }
    public static function F_Fisher($v1, $v2, $alpha){

        $v1_correct = self::F_v1($v1);
        $v2_correct = self::F_v2($v2);

        $Fc = [];

        $Fc['1']=[];
        // v1 = 1
        $Fc['1']['1']=[];$Fc['1']['1']['0.1']=39.9;$Fc['1']['1']['0.05']=161.4;$Fc['1']['1']['0.01']=4052.2;
        $Fc['1']['2']=[];$Fc['1']['2']['0.1']=8.53;$Fc['1']['2']['0.05']=18.51;$Fc['1']['2']['0.01']=98.5;
        $Fc['1']['3']=[];$Fc['1']['3']['0.1']=5.54;$Fc['1']['3']['0.05']=10.13;$Fc['1']['3']['0.01']=34.12;
        $Fc['1']['4']=[];$Fc['1']['4']['0.1']=4.54;$Fc['1']['4']['0.05']=7.71;$Fc['1']['4']['0.01']=21.2;
        $Fc['1']['5']=[];$Fc['1']['5']['0.1']=4.06;$Fc['1']['5']['0.05']=6.61;$Fc['1']['5']['0.01']=16.26;
        $Fc['1']['6']=[];$Fc['1']['6']['0.1']=3.78;$Fc['1']['6']['0.05']=5.99;$Fc['1']['6']['0.01']=13.74;
        $Fc['1']['7']=[];$Fc['1']['7']['0.1']=3.59;$Fc['1']['7']['0.05']=5.59;$Fc['1']['7']['0.01']=12.25;
        $Fc['1']['8']=[];$Fc['1']['8']['0.1']=3.46;$Fc['1']['8']['0.05']=5.32;$Fc['1']['8']['0.01']=11.26;
        $Fc['1']['9']=[];$Fc['1']['9']['0.1']=3.36;$Fc['1']['9']['0.05']=5.12;$Fc['1']['9']['0.01']=10.56;
        $Fc['1']['10']=[];$Fc['1']['10']['0.1']=3.28;$Fc['1']['10']['0.05']=4.96;$Fc['1']['10']['0.01']=10.04;
        $Fc['1']['11']=[];$Fc['1']['11']['0.1']=3.23;$Fc['1']['11']['0.05']=4.84;$Fc['1']['11']['0.01']=9.65;
        $Fc['1']['12']=[];$Fc['1']['12']['0.1']=3.18;$Fc['1']['12']['0.05']=4.75;$Fc['1']['12']['0.01']=9.33;
        $Fc['1']['13']=[];$Fc['1']['13']['0.1']=3.14;$Fc['1']['13']['0.05']=4.67;$Fc['1']['13']['0.01']=9.07;
        $Fc['1']['14']=[];$Fc['1']['14']['0.1']=3.1;$Fc['1']['14']['0.05']=4.6;$Fc['1']['14']['0.01']=8.86;
        $Fc['1']['15']=[];$Fc['1']['15']['0.1']=3.07;$Fc['1']['15']['0.05']=4.54;$Fc['1']['15']['0.01']=8.68;
        $Fc['1']['16']=[];$Fc['1']['16']['0.1']=3.05;$Fc['1']['16']['0.05']=4.49;$Fc['1']['16']['0.01']=8.53;
        $Fc['1']['17']=[];$Fc['1']['17']['0.1']=3.03;$Fc['1']['17']['0.05']=4.45;$Fc['1']['17']['0.01']=8.40;
        $Fc['1']['18']=[];$Fc['1']['18']['0.1']=3.01;$Fc['1']['18']['0.05']=4.41;$Fc['1']['18']['0.01']=8.29;
        $Fc['1']['19']=[];$Fc['1']['19']['0.1']=2.99;$Fc['1']['19']['0.05']=4.38;$Fc['1']['19']['0.01']=8.18;
        $Fc['1']['20']=[];$Fc['1']['20']['0.1']=2.97;$Fc['1']['20']['0.05']=4.35;$Fc['1']['20']['0.01']=8.1;
        $Fc['1']['21']=[];$Fc['1']['21']['0.1']=2.96;$Fc['1']['21']['0.05']=4.32;$Fc['1']['21']['0.01']=8.02;
        $Fc['1']['22']=[];$Fc['1']['22']['0.1']=2.95;$Fc['1']['22']['0.05']=4.3;$Fc['1']['22']['0.01']=7.95;
        $Fc['1']['23']=[];$Fc['1']['23']['0.1']=2.94;$Fc['1']['23']['0.05']=4.28;$Fc['1']['23']['0.01']=7.88;
        $Fc['1']['24']=[];$Fc['1']['24']['0.1']=2.93;$Fc['1']['24']['0.05']=4.26;$Fc['1']['24']['0.01']=7.82;
        $Fc['1']['25']=[];$Fc['1']['25']['0.1']=2.92;$Fc['1']['25']['0.05']=4.24;$Fc['1']['25']['0.01']=7.77;
        $Fc['1']['26']=[];$Fc['1']['26']['0.1']=2.91;$Fc['1']['26']['0.05']=4.23;$Fc['1']['26']['0.01']=7.72;
        $Fc['1']['27']=[];$Fc['1']['27']['0.1']=2.9;$Fc['1']['27']['0.05']=4.21;$Fc['1']['27']['0.01']=7.68;
        $Fc['1']['28']=[];$Fc['1']['28']['0.1']=2.89;$Fc['1']['28']['0.05']=4.2;$Fc['1']['28']['0.01']=7.64;
        $Fc['1']['29']=[];$Fc['1']['29']['0.1']=2.89;$Fc['1']['29']['0.05']=4.18;$Fc['1']['29']['0.01']=7.6;
        $Fc['1']['30']=[];$Fc['1']['30']['0.1']=2.88;$Fc['1']['30']['0.05']=4.17;$Fc['1']['30']['0.01']=7.56;
        $Fc['1']['40']=[];$Fc['1']['40']['0.1']=2.84;$Fc['1']['40']['0.05']=4.08;$Fc['1']['40']['0.01']=7.31;
        $Fc['1']['50']=[];$Fc['1']['50']['0.1']=2.81;$Fc['1']['50']['0.05']=4.03;$Fc['1']['50']['0.01']=7.17;
        $Fc['1']['60']=[];$Fc['1']['60']['0.1']=2.79;$Fc['1']['60']['0.05']=4;$Fc['1']['60']['0.01']=7.08;
        $Fc['1']['100']=[];$Fc['1']['100']['0.1']=2.76;$Fc['1']['100']['0.05']=3.94;$Fc['1']['100']['0.01']=6.9;
        $Fc['1']['200']=[];$Fc['1']['200']['0.1']=2.73;$Fc['1']['200']['0.05']=3.89;$Fc['1']['200']['0.01']=6.76;
        $Fc['1']['1000']=[];$Fc['1']['1000']['0.1']=2.71;$Fc['1']['1000']['0.05']=3.85;$Fc['1']['1000']['0.01']=6.66;

        $Fc['2']=[];
        // v1 = 2
        $Fc['2']['1']=[];   $Fc['2']['1']['0.1']= 49.5;   $Fc['2']['1']['0.05']= 199.5;  $Fc['2']['1']['0.01']= 4999.5;
        $Fc['2']['2']=[];   $Fc['2']['2']['0.1']= 9;      $Fc['2']['2']['0.05']= 19;     $Fc['2']['2']['0.01']= 99;
        $Fc['2']['3']=[];   $Fc['2']['3']['0.1']= 5.46;   $Fc['2']['3']['0.05']= 9.55;   $Fc['2']['3']['0.01']= 30.82;
        $Fc['2']['4']=[];   $Fc['2']['4']['0.1']= 4.32;   $Fc['2']['4']['0.05']= 6.94;   $Fc['2']['4']['0.01']= 18;
        $Fc['2']['5']=[];   $Fc['2']['5']['0.1']= 3.78;   $Fc['2']['5']['0.05']= 5.79;   $Fc['2']['5']['0.01']= 13.27;
        $Fc['2']['6']=[];   $Fc['2']['6']['0.1']= 3.46;   $Fc['2']['6']['0.05']= 5.14;   $Fc['2']['6']['0.01']= 10.92;
        $Fc['2']['7']=[];   $Fc['2']['7']['0.1']= 3.26;   $Fc['2']['7']['0.05']= 4.74;   $Fc['2']['7']['0.01']= 9.55;
        $Fc['2']['8']=[];   $Fc['2']['8']['0.1']= 3.11;   $Fc['2']['8']['0.05']= 4.46;   $Fc['2']['8']['0.01']= 8.65;
        $Fc['2']['9']=[];   $Fc['2']['9']['0.1']= 3.01;   $Fc['2']['9']['0.05']= 4.26;   $Fc['2']['9']['0.01']= 8.02;
        $Fc['2']['10']=[];  $Fc['2']['10']['0.1']= 2.92;  $Fc['2']['10']['0.05']= 4.1;   $Fc['2']['10']['0.01']= 7.56;
        $Fc['2']['11']=[];  $Fc['2']['11']['0.1']= 2.86;  $Fc['2']['11']['0.05']= 3.98;  $Fc['2']['11']['0.01']= 7.21;
        $Fc['2']['12']=[];  $Fc['2']['12']['0.1']= 2.81;  $Fc['2']['12']['0.05']= 3.89;  $Fc['2']['12']['0.01']= 6.93;
        $Fc['2']['13']=[];  $Fc['2']['13']['0.1']= 2.76;  $Fc['2']['13']['0.05']= 3.81;  $Fc['2']['13']['0.01']= 6.7;
        $Fc['2']['14']=[];  $Fc['2']['14']['0.1']= 2.73;  $Fc['2']['14']['0.05']= 3.74;  $Fc['2']['14']['0.01']= 6.51;
        $Fc['2']['15']=[];  $Fc['2']['15']['0.1']= 2.7;   $Fc['2']['15']['0.05']= 3.68;  $Fc['2']['15']['0.01']= 6.36;
        $Fc['2']['16']=[];  $Fc['2']['16']['0.1']= 2.67;  $Fc['2']['16']['0.05']= 3.63;  $Fc['2']['16']['0.01']= 6.23;
        $Fc['2']['17']=[];  $Fc['2']['17']['0.1']= 2.64;  $Fc['2']['17']['0.05']= 3.59;  $Fc['2']['17']['0.01']= 6.11;
        $Fc['2']['18']=[];  $Fc['2']['18']['0.1']= 2.62;  $Fc['2']['18']['0.05']= 3.55;  $Fc['2']['18']['0.01']= 6.01;
        $Fc['2']['19']=[];  $Fc['2']['19']['0.1']= 2.61;  $Fc['2']['19']['0.05']= 3.52;  $Fc['2']['19']['0.01']= 5.93;
        $Fc['2']['20']=[];  $Fc['2']['20']['0.1']= 2.59;  $Fc['2']['20']['0.05']= 3.49;  $Fc['2']['20']['0.01']= 5.85;
        $Fc['2']['21']=[];  $Fc['2']['21']['0.1']= 2.57;  $Fc['2']['21']['0.05']= 3.47;  $Fc['2']['21']['0.01']= 5.78;
        $Fc['2']['22']=[];  $Fc['2']['22']['0.1']= 2.56;  $Fc['2']['22']['0.05']= 3.44;  $Fc['2']['22']['0.01']= 5.72;
        $Fc['2']['23']=[];  $Fc['2']['23']['0.1']= 2.55;  $Fc['2']['23']['0.05']= 3.42;  $Fc['2']['23']['0.01']= 5.66;
        $Fc['2']['24']=[];  $Fc['2']['24']['0.1']= 2.54;  $Fc['2']['24']['0.05']= 3.4;   $Fc['2']['24']['0.01']= 5.61;
        $Fc['2']['25']=[];  $Fc['2']['25']['0.1']= 2.53;  $Fc['2']['25']['0.05']= 3.39;  $Fc['2']['25']['0.01']= 5.57;
        $Fc['2']['26']=[];  $Fc['2']['26']['0.1']= 2.52;  $Fc['2']['26']['0.05']= 3.37;  $Fc['2']['26']['0.01']= 5.53;
        $Fc['2']['27']=[];  $Fc['2']['27']['0.1']= 2.51;  $Fc['2']['27']['0.05']= 3.35;  $Fc['2']['27']['0.01']= 5.49;
        $Fc['2']['28']=[];  $Fc['2']['28']['0.1']= 2.5;   $Fc['2']['28']['0.05']= 3.34;  $Fc['2']['28']['0.01']= 5.45;
        $Fc['2']['29']=[];  $Fc['2']['29']['0.1']= 2.5;   $Fc['2']['29']['0.05']= 3.33;  $Fc['2']['29']['0.01']= 5.42;
        $Fc['2']['30']=[];  $Fc['2']['30']['0.1']= 2.49;  $Fc['2']['30']['0.05']= 3.32;  $Fc['2']['30']['0.01']= 5.39;
        $Fc['2']['40']=[];  $Fc['2']['40']['0.1']= 2.44;  $Fc['2']['40']['0.05']= 3.23;  $Fc['2']['40']['0.01']= 5.18;
        $Fc['2']['50']=[];  $Fc['2']['50']['0.1']= 2.41;  $Fc['2']['50']['0.05']= 3.18;  $Fc['2']['50']['0.01']= 5.06;
        $Fc['2']['60']=[];  $Fc['2']['60']['0.1']= 2.39;  $Fc['2']['60']['0.05']= 3.15;  $Fc['2']['60']['0.01']= 4.98;
        $Fc['2']['100']=[]; $Fc['2']['100']['0.1']= 2.36; $Fc['2']['100']['0.05']= 3.09; $Fc['2']['100']['0.01']= 4.82;
        $Fc['2']['200']=[]; $Fc['2']['200']['0.1']= 2.33; $Fc['2']['200']['0.05']= 3.04; $Fc['2']['200']['0.01']= 4.71;
        $Fc['2']['1000']=[];$Fc['2']['1000']['0.1']= 2.31;$Fc['2']['1000']['0.05']= 3;   $Fc['2']['1000']['0.01']= 4.63;

        $Fc['3']=[];
        // v1 = 3
        $Fc['3']['1']=[];   $Fc['3']['1']['0.1']= 53.6;   $Fc['3']['1']['0.05']= 215.7;  $Fc['3']['1']['0.01']= 5403.4;
        $Fc['3']['2']=[];   $Fc['3']['2']['0.1']= 9.16;   $Fc['3']['2']['0.05']= 19.16;  $Fc['3']['2']['0.01']= 99.17;
        $Fc['3']['3']=[];   $Fc['3']['3']['0.1']= 5.39;   $Fc['3']['3']['0.05']= 9.28;   $Fc['3']['3']['0.01']= 29.46;
        $Fc['3']['4']=[];   $Fc['3']['4']['0.1']= 4.19;   $Fc['3']['4']['0.05']= 6.59;   $Fc['3']['4']['0.01']= 16.69;
        $Fc['3']['5']=[];   $Fc['3']['5']['0.1']= 3.62;   $Fc['3']['5']['0.05']= 5.41;   $Fc['3']['5']['0.01']= 12.06;
        $Fc['3']['6']=[];   $Fc['3']['6']['0.1']= 3.29;   $Fc['3']['6']['0.05']= 4.76;   $Fc['3']['6']['0.01']= 9.78;
        $Fc['3']['7']=[];   $Fc['3']['7']['0.1']= 3.07;   $Fc['3']['7']['0.05']= 4.35;   $Fc['3']['7']['0.01']= 8.45;
        $Fc['3']['8']=[];   $Fc['3']['8']['0.1']= 2.92;   $Fc['3']['8']['0.05']= 4.07;   $Fc['3']['8']['0.01']= 7.59;
        $Fc['3']['9']=[];   $Fc['3']['9']['0.1']= 2.81;   $Fc['3']['9']['0.05']= 3.86;   $Fc['3']['9']['0.01']= 6.99;
        $Fc['3']['10']=[];  $Fc['3']['10']['0.1']= 2.73;  $Fc['3']['10']['0.05']= 3.71;  $Fc['3']['10']['0.01']= 6.55;
        $Fc['3']['11']=[];  $Fc['3']['11']['0.1']= 2.66;  $Fc['3']['11']['0.05']= 3.59;  $Fc['3']['11']['0.01']= 6.22;
        $Fc['3']['12']=[];  $Fc['3']['12']['0.1']= 2.61;  $Fc['3']['12']['0.05']= 3.49;  $Fc['3']['12']['0.01']= 5.95;
        $Fc['3']['13']=[];  $Fc['3']['13']['0.1']= 2.56;  $Fc['3']['13']['0.05']= 3.41;  $Fc['3']['13']['0.01']= 5.74;
        $Fc['3']['14']=[];  $Fc['3']['14']['0.1']= 2.52;  $Fc['3']['14']['0.05']= 3.34;  $Fc['3']['14']['0.01']= 5.56;
        $Fc['3']['15']=[];  $Fc['3']['15']['0.1']= 2.49;  $Fc['3']['15']['0.05']= 3.29;  $Fc['3']['15']['0.01']= 5.42;
        $Fc['3']['16']=[];  $Fc['3']['16']['0.1']= 2.46;  $Fc['3']['16']['0.05']= 3.24;  $Fc['3']['16']['0.01']= 5.29;
        $Fc['3']['17']=[];  $Fc['3']['17']['0.1']= 2.44;  $Fc['3']['17']['0.05']= 3.2;   $Fc['3']['17']['0.01']= 5.18;
        $Fc['3']['18']=[];  $Fc['3']['18']['0.1']= 2.42;  $Fc['3']['18']['0.05']= 3.16;  $Fc['3']['18']['0.01']= 5.09;
        $Fc['3']['19']=[];  $Fc['3']['19']['0.1']= 2.4;   $Fc['3']['19']['0.05']= 3.13;  $Fc['3']['19']['0.01']= 5.01;
        $Fc['3']['20']=[];  $Fc['3']['20']['0.1']= 2.38;  $Fc['3']['20']['0.05']= 3.1;   $Fc['3']['20']['0.01']= 4.94;
        $Fc['3']['21']=[];  $Fc['3']['21']['0.1']= 2.36;  $Fc['3']['21']['0.05']= 3.07;  $Fc['3']['21']['0.01']= 4.87;
        $Fc['3']['22']=[];  $Fc['3']['22']['0.1']= 2.35;  $Fc['3']['22']['0.05']= 3.05;  $Fc['3']['22']['0.01']= 4.82;
        $Fc['3']['23']=[];  $Fc['3']['23']['0.1']= 2.34;  $Fc['3']['23']['0.05']= 3.03;  $Fc['3']['23']['0.01']= 4.76;
        $Fc['3']['24']=[];  $Fc['3']['24']['0.1']= 2.33;  $Fc['3']['24']['0.05']= 3.01;  $Fc['3']['24']['0.01']= 4.72;
        $Fc['3']['25']=[];  $Fc['3']['25']['0.1']= 2.32;  $Fc['3']['25']['0.05']= 2.99;  $Fc['3']['25']['0.01']= 4.68;
        $Fc['3']['26']=[];  $Fc['3']['26']['0.1']= 2.31;  $Fc['3']['26']['0.05']= 2.98;  $Fc['3']['26']['0.01']= 4.64;
        $Fc['3']['27']=[];  $Fc['3']['27']['0.1']= 2.3;   $Fc['3']['27']['0.05']= 2.96;  $Fc['3']['27']['0.01']= 4.6;
        $Fc['3']['28']=[];  $Fc['3']['28']['0.1']= 2.29;  $Fc['3']['28']['0.05']= 2.95;  $Fc['3']['28']['0.01']= 4.57;
        $Fc['3']['29']=[];  $Fc['3']['29']['0.1']= 2.28;  $Fc['3']['29']['0.05']= 2.93;  $Fc['3']['29']['0.01']= 4.54;
        $Fc['3']['30']=[];  $Fc['3']['30']['0.1']= 2.28;  $Fc['3']['30']['0.05']= 2.92;  $Fc['3']['30']['0.01']= 4.51;
        $Fc['3']['40']=[];  $Fc['3']['40']['0.1']= 2.23;  $Fc['3']['40']['0.05']= 2.84;  $Fc['3']['40']['0.01']= 4.31;
        $Fc['3']['50']=[];  $Fc['3']['50']['0.1']= 2.2;   $Fc['3']['50']['0.05']= 2.79;  $Fc['3']['50']['0.01']= 4.2;
        $Fc['3']['60']=[];  $Fc['3']['60']['0.1']= 2.18;  $Fc['3']['60']['0.05']= 2.76;  $Fc['3']['60']['0.01']= 4.13;
        $Fc['3']['100']=[]; $Fc['3']['100']['0.1']= 2.14; $Fc['3']['100']['0.05']= 2.7;  $Fc['3']['100']['0.01']= 3.98;
        $Fc['3']['200']=[]; $Fc['3']['200']['0.1']= 2.11; $Fc['3']['200']['0.05']= 2.65; $Fc['3']['200']['0.01']= 3.88;
        $Fc['3']['1000']=[];$Fc['3']['1000']['0.1']= 2.09;$Fc['3']['1000']['0.05']= 2.61;$Fc['3']['1000']['0.01']= 3.8;

        $Fc['4']=[];
        // v1 = 4
        $Fc['4']['1']=[];   $Fc['4']['1']['0.1']= 55.8;   $Fc['4']['1']['0.05']= 224.6;   $Fc['4']['1']['0.01']= 5624.6;
        $Fc['4']['2']=[];   $Fc['4']['2']['0.1']= 9.24;   $Fc['4']['2']['0.05']= 19.25;   $Fc['4']['2']['0.01']= 99.25;
        $Fc['4']['3']=[];   $Fc['4']['3']['0.1']= 5.34;   $Fc['4']['3']['0.05']= 9.12;    $Fc['4']['3']['0.01']= 28.71;
        $Fc['4']['4']=[];   $Fc['4']['4']['0.1']= 4.11;   $Fc['4']['4']['0.05']= 6.39;    $Fc['4']['4']['0.01']= 15.98;
        $Fc['4']['5']=[];   $Fc['4']['5']['0.1']= 3.52;   $Fc['4']['5']['0.05']= 5.19;    $Fc['4']['5']['0.01']= 11.39;
        $Fc['4']['6']=[];   $Fc['4']['6']['0.1']= 3.18;   $Fc['4']['6']['0.05']= 4.53;    $Fc['4']['6']['0.01']= 9.15;
        $Fc['4']['7']=[];   $Fc['4']['7']['0.1']= 2.96;   $Fc['4']['7']['0.05']= 4.12;    $Fc['4']['7']['0.01']= 7.85;
        $Fc['4']['8']=[];   $Fc['4']['8']['0.1']= 2.81;   $Fc['4']['8']['0.05']= 3.84;    $Fc['4']['8']['0.01']= 7.01;
        $Fc['4']['9']=[];   $Fc['4']['9']['0.1']= 2.69;   $Fc['4']['9']['0.05']= 3.63;    $Fc['4']['9']['0.01']= 6.42;
        $Fc['4']['10']=[];  $Fc['4']['10']['0.1']= 2.61;  $Fc['4']['10']['0.05']= 3.48;   $Fc['4']['10']['0.01']= 5.99;
        $Fc['4']['11']=[];  $Fc['4']['11']['0.1']= 2.54;  $Fc['4']['11']['0.05']= 3.36;   $Fc['4']['11']['0.01']= 5.67;
        $Fc['4']['12']=[];  $Fc['4']['12']['0.1']= 2.48;  $Fc['4']['12']['0.05']= 3.26;   $Fc['4']['12']['0.01']= 5.41;
        $Fc['4']['13']=[];  $Fc['4']['13']['0.1']= 2.43;  $Fc['4']['13']['0.05']= 3.18;   $Fc['4']['13']['0.01']= 5.21;
        $Fc['4']['14']=[];  $Fc['4']['14']['0.1']= 2.39;  $Fc['4']['14']['0.05']= 3.11;   $Fc['4']['14']['0.01']= 5.04;
        $Fc['4']['15']=[];  $Fc['4']['15']['0.1']= 2.36;  $Fc['4']['15']['0.05']= 3.06;   $Fc['4']['15']['0.01']= 4.89;
        $Fc['4']['16']=[];  $Fc['4']['16']['0.1']= 2.33;  $Fc['4']['16']['0.05']= 3.01;   $Fc['4']['16']['0.01']= 4.77;
        $Fc['4']['17']=[];  $Fc['4']['17']['0.1']= 2.31;  $Fc['4']['17']['0.05']= 2.96;   $Fc['4']['17']['0.01']= 4.67;
        $Fc['4']['18']=[];  $Fc['4']['18']['0.1']= 2.29;  $Fc['4']['18']['0.05']= 2.93;   $Fc['4']['18']['0.01']= 4.58;
        $Fc['4']['19']=[];  $Fc['4']['19']['0.1']= 2.27;  $Fc['4']['19']['0.05']= 2.9;    $Fc['4']['19']['0.01']= 4.5;
        $Fc['4']['20']=[];  $Fc['4']['20']['0.1']= 2.25;  $Fc['4']['20']['0.05']= 2.87;   $Fc['4']['20']['0.01']= 4.43;
        $Fc['4']['21']=[];  $Fc['4']['21']['0.1']= 2.23;  $Fc['4']['21']['0.05']= 2.84;   $Fc['4']['21']['0.01']= 4.37;
        $Fc['4']['22']=[];  $Fc['4']['22']['0.1']= 2.22;  $Fc['4']['22']['0.05']= 2.82;   $Fc['4']['22']['0.01']= 4.31;
        $Fc['4']['23']=[];  $Fc['4']['23']['0.1']= 2.21;  $Fc['4']['23']['0.05']= 2.8;    $Fc['4']['23']['0.01']= 4.26;
        $Fc['4']['24']=[];  $Fc['4']['24']['0.1']= 2.19;  $Fc['4']['24']['0.05']= 2.78;   $Fc['4']['24']['0.01']= 4.22;
        $Fc['4']['25']=[];  $Fc['4']['25']['0.1']= 2.18;  $Fc['4']['25']['0.05']= 2.76;   $Fc['4']['25']['0.01']= 4.18;
        $Fc['4']['26']=[];  $Fc['4']['26']['0.1']= 2.17;  $Fc['4']['26']['0.05']= 2.74;   $Fc['4']['26']['0.01']= 4.14;
        $Fc['4']['27']=[];  $Fc['4']['27']['0.1']= 2.17;  $Fc['4']['27']['0.05']= 2.73;   $Fc['4']['27']['0.01']= 4.11;
        $Fc['4']['28']=[];  $Fc['4']['28']['0.1']= 2.16;  $Fc['4']['28']['0.05']= 2.71;   $Fc['4']['28']['0.01']= 4.07;
        $Fc['4']['29']=[];  $Fc['4']['29']['0.1']= 2.15;  $Fc['4']['29']['0.05']= 2.7;    $Fc['4']['29']['0.01']= 4.04;
        $Fc['4']['30']=[];  $Fc['4']['30']['0.1']= 2.14;  $Fc['4']['30']['0.05']= 2.69;   $Fc['4']['30']['0.01']= 4.02;
        $Fc['4']['40']=[];  $Fc['4']['40']['0.1']= 2.09;  $Fc['4']['40']['0.05']= 2.61;   $Fc['4']['40']['0.01']= 3.83;
        $Fc['4']['50']=[];  $Fc['4']['50']['0.1']= 2.06;  $Fc['4']['50']['0.05']= 2.56;   $Fc['4']['50']['0.01']= 3.72;
        $Fc['4']['60']=[];  $Fc['4']['60']['0.1']= 2.04;  $Fc['4']['60']['0.05']= 2.53;   $Fc['4']['60']['0.01']= 3.65;
        $Fc['4']['100']=[]; $Fc['4']['100']['0.1']= 2;    $Fc['4']['100']['0.05']= 2.46;  $Fc['4']['100']['0.01']= 3.51;
        $Fc['4']['200']=[]; $Fc['4']['200']['0.1']= 1.97; $Fc['4']['200']['0.05']= 2.42;  $Fc['4']['200']['0.01']= 3.41;
        $Fc['4']['1000']=[];$Fc['4']['1000']['0.1']= 1.95;$Fc['4']['1000']['0.05']= 2.38; $Fc['4']['1000']['0.01']= 3.34;

        $Fc['5']=[];
        // v1 = 5
        $Fc['5']['1']=[];   $Fc['5']['1']['0.1']= 57.2;   $Fc['5']['1']['0.05']= 230.2;   $Fc['5']['1']['0.01']= 5763.6;
        $Fc['5']['2']=[];   $Fc['5']['2']['0.1']= 9.29;   $Fc['5']['2']['0.05']= 19.3;    $Fc['5']['2']['0.01']= 99.3;
        $Fc['5']['3']=[];   $Fc['5']['3']['0.1']= 5.31;   $Fc['5']['3']['0.05']= 9.01;    $Fc['5']['3']['0.01']= 28.24;
        $Fc['5']['4']=[];   $Fc['5']['4']['0.1']= 4.05;   $Fc['5']['4']['0.05']= 6.26;    $Fc['5']['4']['0.01']= 15.52;
        $Fc['5']['5']=[];   $Fc['5']['5']['0.1']= 3.45;   $Fc['5']['5']['0.05']= 5.05;    $Fc['5']['5']['0.01']= 10.97;
        $Fc['5']['6']=[];   $Fc['5']['6']['0.1']= 3.11;   $Fc['5']['6']['0.05']= 4.39;    $Fc['5']['6']['0.01']= 8.75;
        $Fc['5']['7']=[];   $Fc['5']['7']['0.1']= 2.88;   $Fc['5']['7']['0.05']= 3.97;    $Fc['5']['7']['0.01']= 7.46;
        $Fc['5']['8']=[];   $Fc['5']['8']['0.1']= 2.73;   $Fc['5']['8']['0.05']= 3.69;    $Fc['5']['8']['0.01']= 6.63;
        $Fc['5']['9']=[];   $Fc['5']['9']['0.1']= 2.61;   $Fc['5']['9']['0.05']= 3.48;    $Fc['5']['9']['0.01']= 6.06;
        $Fc['5']['10']=[];  $Fc['5']['10']['0.1']= 2.52;  $Fc['5']['10']['0.05']= 3.33;   $Fc['5']['10']['0.01']= 5.64;
        $Fc['5']['11']=[];  $Fc['5']['11']['0.1']= 2.45;  $Fc['5']['11']['0.05']= 3.2;    $Fc['5']['11']['0.01']= 5.32;
        $Fc['5']['12']=[];  $Fc['5']['12']['0.1']= 2.39;  $Fc['5']['12']['0.05']= 3.11;   $Fc['5']['12']['0.01']= 5.06;
        $Fc['5']['13']=[];  $Fc['5']['13']['0.1']= 2.35;  $Fc['5']['13']['0.05']= 3.03;   $Fc['5']['13']['0.01']= 4.86;
        $Fc['5']['14']=[];  $Fc['5']['14']['0.1']= 2.31;  $Fc['5']['14']['0.05']= 2.96;   $Fc['5']['14']['0.01']= 4.7;
        $Fc['5']['15']=[];  $Fc['5']['15']['0.1']= 2.27;  $Fc['5']['15']['0.05']= 2.9;    $Fc['5']['15']['0.01']= 4.56;
        $Fc['5']['16']=[];  $Fc['5']['16']['0.1']= 2.24;  $Fc['5']['16']['0.05']= 2.85;   $Fc['5']['16']['0.01']= 4.44;
        $Fc['5']['17']=[];  $Fc['5']['17']['0.1']= 2.22;  $Fc['5']['17']['0.05']= 2.81;   $Fc['5']['17']['0.01']= 4.34;
        $Fc['5']['18']=[];  $Fc['5']['18']['0.1']= 2.2;   $Fc['5']['18']['0.05']= 2.77;   $Fc['5']['18']['0.01']= 4.25;
        $Fc['5']['19']=[];  $Fc['5']['19']['0.1']= 2.18;  $Fc['5']['19']['0.05']= 2.74;   $Fc['5']['19']['0.01']= 4.17;
        $Fc['5']['20']=[];  $Fc['5']['20']['0.1']= 2.16;  $Fc['5']['20']['0.05']= 2.71;   $Fc['5']['20']['0.01']= 4.1;
        $Fc['5']['21']=[];  $Fc['5']['21']['0.1']= 2.14;  $Fc['5']['21']['0.05']= 2.68;   $Fc['5']['21']['0.01']= 4.04;
        $Fc['5']['22']=[];  $Fc['5']['22']['0.1']= 2.13;  $Fc['5']['22']['0.05']= 2.66;   $Fc['5']['22']['0.01']= 3.99;
        $Fc['5']['23']=[];  $Fc['5']['23']['0.1']= 2.11;  $Fc['5']['23']['0.05']= 2.64;   $Fc['5']['23']['0.01']= 3.94;
        $Fc['5']['24']=[];  $Fc['5']['24']['0.1']= 2.1;   $Fc['5']['24']['0.05']= 2.62;   $Fc['5']['24']['0.01']= 3.9;
        $Fc['5']['25']=[];  $Fc['5']['25']['0.1']= 2.09;  $Fc['5']['25']['0.05']= 2.6;    $Fc['5']['25']['0.01']= 3.86;
        $Fc['5']['26']=[];  $Fc['5']['26']['0.1']= 2.08;  $Fc['5']['26']['0.05']= 2.59;   $Fc['5']['26']['0.01']= 3.82;
        $Fc['5']['27']=[];  $Fc['5']['27']['0.1']= 2.07;  $Fc['5']['27']['0.05']= 2.57;   $Fc['5']['27']['0.01']= 3.78;
        $Fc['5']['28']=[];  $Fc['5']['28']['0.1']= 2.06;  $Fc['5']['28']['0.05']= 2.56;   $Fc['5']['28']['0.01']= 3.75;
        $Fc['5']['29']=[];  $Fc['5']['29']['0.1']= 2.06;  $Fc['5']['29']['0.05']= 2.55;   $Fc['5']['29']['0.01']= 3.73;
        $Fc['5']['30']=[];  $Fc['5']['30']['0.1']= 2.05;  $Fc['5']['30']['0.05']= 2.53;   $Fc['5']['30']['0.01']= 3.7;
        $Fc['5']['40']=[];  $Fc['5']['40']['0.1']= 2;     $Fc['5']['40']['0.05']= 2.45;   $Fc['5']['40']['0.01']= 3.51;
        $Fc['5']['50']=[];  $Fc['5']['50']['0.1']= 1.97;  $Fc['5']['50']['0.05']= 2.4;    $Fc['5']['50']['0.01']= 3.41;
        $Fc['5']['60']=[];  $Fc['5']['60']['0.1']= 1.95;  $Fc['5']['60']['0.05']= 2.37;   $Fc['5']['60']['0.01']= 3.34;
        $Fc['5']['100']=[]; $Fc['5']['100']['0.1']= 1.91; $Fc['5']['100']['0.05']= 2.31;  $Fc['5']['100']['0.01']= 3.21;
        $Fc['5']['200']=[]; $Fc['5']['200']['0.1']= 1.88; $Fc['5']['200']['0.05']= 2.26;  $Fc['5']['200']['0.01']= 3.11;
        $Fc['5']['1000']=[];$Fc['5']['1000']['0.1']= 1.85;$Fc['5']['1000']['0.05']= 2.22; $Fc['5']['1000']['0.01']= 3.04;

        $Fc['6']=[];
        // v1 = 6
        $Fc['6']['1']=[];   $Fc['6']['1']['0.1']= 58.2;   $Fc['6']['1']['0.05']= 234;    $Fc['6']['1']['0.01']= 5859;
        $Fc['6']['2']=[];   $Fc['6']['2']['0.1']= 9.33;   $Fc['6']['2']['0.05']= 19.33;  $Fc['6']['2']['0.01']= 99.33;
        $Fc['6']['3']=[];   $Fc['6']['3']['0.1']= 5.28;   $Fc['6']['3']['0.05']= 8.94;   $Fc['6']['3']['0.01']= 27.91;
        $Fc['6']['4']=[];   $Fc['6']['4']['0.1']= 4.01;   $Fc['6']['4']['0.05']= 6.16;   $Fc['6']['4']['0.01']= 15.21;
        $Fc['6']['5']=[];   $Fc['6']['5']['0.1']= 3.4;    $Fc['6']['5']['0.05']= 4.95;   $Fc['6']['5']['0.01']= 10.67;
        $Fc['6']['6']=[];   $Fc['6']['6']['0.1']= 3.05;   $Fc['6']['6']['0.05']= 4.28;   $Fc['6']['6']['0.01']= 8.47;
        $Fc['6']['7']=[];   $Fc['6']['7']['0.1']= 2.83;   $Fc['6']['7']['0.05']= 3.87;   $Fc['6']['7']['0.01']= 7.19;
        $Fc['6']['8']=[];   $Fc['6']['8']['0.1']= 2.67;   $Fc['6']['8']['0.05']= 3.58;   $Fc['6']['8']['0.01']= 6.37;
        $Fc['6']['9']=[];   $Fc['6']['9']['0.1']= 2.55;   $Fc['6']['9']['0.05']= 3.37;   $Fc['6']['9']['0.01']= 5.8;
        $Fc['6']['10']=[];  $Fc['6']['10']['0.1']= 2.46;  $Fc['6']['10']['0.05']= 3.22;  $Fc['6']['10']['0.01']= 5.39;
        $Fc['6']['11']=[];  $Fc['6']['11']['0.1']= 2.39;  $Fc['6']['11']['0.05']= 3.09;  $Fc['6']['11']['0.01']= 5.07;
        $Fc['6']['12']=[];  $Fc['6']['12']['0.1']= 2.33;  $Fc['6']['12']['0.05']= 3;     $Fc['6']['12']['0.01']= 4.82;
        $Fc['6']['13']=[];  $Fc['6']['13']['0.1']= 2.28;  $Fc['6']['13']['0.05']= 2.92;  $Fc['6']['13']['0.01']= 4.62;
        $Fc['6']['14']=[];  $Fc['6']['14']['0.1']= 2.24;  $Fc['6']['14']['0.05']= 2.85;  $Fc['6']['14']['0.01']= 4.46;
        $Fc['6']['15']=[];  $Fc['6']['15']['0.1']= 2.21;  $Fc['6']['15']['0.05']= 2.79;  $Fc['6']['15']['0.01']= 4.32;
        $Fc['6']['16']=[];  $Fc['6']['16']['0.1']= 2.18;  $Fc['6']['16']['0.05']= 2.74;  $Fc['6']['16']['0.01']= 4.2;
        $Fc['6']['17']=[];  $Fc['6']['17']['0.1']= 2.15;  $Fc['6']['17']['0.05']= 2.7;   $Fc['6']['17']['0.01']= 4.1;
        $Fc['6']['18']=[];  $Fc['6']['18']['0.1']= 2.13;  $Fc['6']['18']['0.05']= 2.66;  $Fc['6']['18']['0.01']= 4.01;
        $Fc['6']['19']=[];  $Fc['6']['19']['0.1']= 2.11;  $Fc['6']['19']['0.05']= 2.63;  $Fc['6']['19']['0.01']= 3.94;
        $Fc['6']['20']=[];  $Fc['6']['20']['0.1']= 2.09;  $Fc['6']['20']['0.05']= 2.6;   $Fc['6']['20']['0.01']= 3.87;
        $Fc['6']['21']=[];  $Fc['6']['21']['0.1']= 2.08;  $Fc['6']['21']['0.05']= 2.57;  $Fc['6']['21']['0.01']= 3.81;
        $Fc['6']['22']=[];  $Fc['6']['22']['0.1']= 2.06;  $Fc['6']['22']['0.05']= 2.55;  $Fc['6']['22']['0.01']= 3.76;
        $Fc['6']['23']=[];  $Fc['6']['23']['0.1']= 2.05;  $Fc['6']['23']['0.05']= 2.53;  $Fc['6']['23']['0.01']= 3.71;
        $Fc['6']['24']=[];  $Fc['6']['24']['0.1']= 2.04;  $Fc['6']['24']['0.05']= 2.51;  $Fc['6']['24']['0.01']= 3.67;
        $Fc['6']['25']=[];  $Fc['6']['25']['0.1']= 2.02;  $Fc['6']['25']['0.05']= 2.49;  $Fc['6']['25']['0.01']= 3.63;
        $Fc['6']['26']=[];  $Fc['6']['26']['0.1']= 2.01;  $Fc['6']['26']['0.05']= 2.47;  $Fc['6']['26']['0.01']= 3.59;
        $Fc['6']['27']=[];  $Fc['6']['27']['0.1']= 2;     $Fc['6']['27']['0.05']= 2.46;  $Fc['6']['27']['0.01']= 3.56;
        $Fc['6']['28']=[];  $Fc['6']['28']['0.1']= 2;     $Fc['6']['28']['0.05']= 2.45;  $Fc['6']['28']['0.01']= 3.53;
        $Fc['6']['29']=[];  $Fc['6']['29']['0.1']= 1.99;  $Fc['6']['29']['0.05']= 2.43;  $Fc['6']['29']['0.01']= 3.5;
        $Fc['6']['30']=[];  $Fc['6']['30']['0.1']= 1.98;  $Fc['6']['30']['0.05']= 2.42;  $Fc['6']['30']['0.01']= 3.47;
        $Fc['6']['40']=[];  $Fc['6']['40']['0.1']= 1.93;  $Fc['6']['40']['0.05']= 2.34;  $Fc['6']['40']['0.01']= 3.29;
        $Fc['6']['50']=[];  $Fc['6']['50']['0.1']= 1.9;   $Fc['6']['50']['0.05']= 2.29;  $Fc['6']['50']['0.01']= 3.19;
        $Fc['6']['60']=[];  $Fc['6']['60']['0.1']= 1.87;  $Fc['6']['60']['0.05']= 2.25;  $Fc['6']['60']['0.01']= 3.12;
        $Fc['6']['100']=[]; $Fc['6']['100']['0.1']= 1.83; $Fc['6']['100']['0.05']= 2.19; $Fc['6']['100']['0.01']= 2.99;
        $Fc['6']['200']=[]; $Fc['6']['200']['0.1']= 1.8;  $Fc['6']['200']['0.05']= 2.14; $Fc['6']['200']['0.01']= 2.89;
        $Fc['6']['1000']=[];$Fc['6']['1000']['0.1']= 1.78;$Fc['6']['1000']['0.05']= 2.11;$Fc['6']['1000']['0.01']= 2.82;

        $Fc['7']=[];
        // v1 = 7
        $Fc['7']['1']=[];   $Fc['7']['1']['0.1']= 58.9;   $Fc['7']['1']['0.05']= 236.8;   $Fc['7']['1']['0.01']= 5928.4;
        $Fc['7']['2']=[];   $Fc['7']['2']['0.1']= 9.35;   $Fc['7']['2']['0.05']= 19.35;   $Fc['7']['2']['0.01']= 99.36;
        $Fc['7']['3']=[];   $Fc['7']['3']['0.1']= 5.27;   $Fc['7']['3']['0.05']= 8.89;    $Fc['7']['3']['0.01']= 27.67;
        $Fc['7']['4']=[];   $Fc['7']['4']['0.1']= 3.98;   $Fc['7']['4']['0.05']= 6.09;    $Fc['7']['4']['0.01']= 14.98;
        $Fc['7']['5']=[];   $Fc['7']['5']['0.1']= 3.37;   $Fc['7']['5']['0.05']= 4.88;    $Fc['7']['5']['0.01']= 10.46;
        $Fc['7']['6']=[];   $Fc['7']['6']['0.1']= 3.01;   $Fc['7']['6']['0.05']= 4.21;    $Fc['7']['6']['0.01']= 8.26;
        $Fc['7']['7']=[];   $Fc['7']['7']['0.1']= 2.78;   $Fc['7']['7']['0.05']= 3.79;    $Fc['7']['7']['0.01']= 6.99;
        $Fc['7']['8']=[];   $Fc['7']['8']['0.1']= 2.62;   $Fc['7']['8']['0.05']= 3.5;     $Fc['7']['8']['0.01']= 6.18;
        $Fc['7']['9']=[];   $Fc['7']['9']['0.1']= 2.51;   $Fc['7']['9']['0.05']= 3.29;    $Fc['7']['9']['0.01']= 5.61;
        $Fc['7']['10']=[];  $Fc['7']['10']['0.1']= 2.41;  $Fc['7']['10']['0.05']= 3.14;   $Fc['7']['10']['0.01']= 5.2;
        $Fc['7']['11']=[];  $Fc['7']['11']['0.1']= 2.34;  $Fc['7']['11']['0.05']= 3.01;   $Fc['7']['11']['0.01']= 4.89;
        $Fc['7']['12']=[];  $Fc['7']['12']['0.1']= 2.28;  $Fc['7']['12']['0.05']= 2.91;   $Fc['7']['12']['0.01']= 4.64;
        $Fc['7']['13']=[];  $Fc['7']['13']['0.1']= 2.23;  $Fc['7']['13']['0.05']= 2.83;   $Fc['7']['13']['0.01']= 4.44;
        $Fc['7']['14']=[];  $Fc['7']['14']['0.1']= 2.19;  $Fc['7']['14']['0.05']= 2.76;   $Fc['7']['14']['0.01']= 4.28;
        $Fc['7']['15']=[];  $Fc['7']['15']['0.1']= 2.16;  $Fc['7']['15']['0.05']= 2.71;   $Fc['7']['15']['0.01']= 4.14;
        $Fc['7']['16']=[];  $Fc['7']['16']['0.1']= 2.13;  $Fc['7']['16']['0.05']= 2.66;   $Fc['7']['16']['0.01']= 4.03;
        $Fc['7']['17']=[];  $Fc['7']['17']['0.1']= 2.1;   $Fc['7']['17']['0.05']= 2.61;   $Fc['7']['17']['0.01']= 3.93;
        $Fc['7']['18']=[];  $Fc['7']['18']['0.1']= 2.08;  $Fc['7']['18']['0.05']= 2.58;   $Fc['7']['18']['0.01']= 3.84;
        $Fc['7']['19']=[];  $Fc['7']['19']['0.1']= 2.06;  $Fc['7']['19']['0.05']= 2.54;   $Fc['7']['19']['0.01']= 3.77;
        $Fc['7']['20']=[];  $Fc['7']['20']['0.1']= 2.04;  $Fc['7']['20']['0.05']= 2.51;   $Fc['7']['20']['0.01']= 3.7;
        $Fc['7']['21']=[];  $Fc['7']['21']['0.1']= 2.02;  $Fc['7']['21']['0.05']= 2.49;   $Fc['7']['21']['0.01']= 3.64;
        $Fc['7']['22']=[];  $Fc['7']['22']['0.1']= 2.01;  $Fc['7']['22']['0.05']= 2.46;   $Fc['7']['22']['0.01']= 3.59;
        $Fc['7']['23']=[];  $Fc['7']['23']['0.1']= 1.99;  $Fc['7']['23']['0.05']= 2.44;   $Fc['7']['23']['0.01']= 3.54;
        $Fc['7']['24']=[];  $Fc['7']['24']['0.1']= 1.98;  $Fc['7']['24']['0.05']= 2.42;   $Fc['7']['24']['0.01']= 3.5;
        $Fc['7']['25']=[];  $Fc['7']['25']['0.1']= 1.97;  $Fc['7']['25']['0.05']= 2.4;    $Fc['7']['25']['0.01']= 3.46;
        $Fc['7']['26']=[];  $Fc['7']['26']['0.1']= 1.96;  $Fc['7']['26']['0.05']= 2.39;   $Fc['7']['26']['0.01']= 3.42;
        $Fc['7']['27']=[];  $Fc['7']['27']['0.1']= 1.95;  $Fc['7']['27']['0.05']= 2.37;   $Fc['7']['27']['0.01']= 3.39;
        $Fc['7']['28']=[];  $Fc['7']['28']['0.1']= 1.94;  $Fc['7']['28']['0.05']= 2.36;   $Fc['7']['28']['0.01']= 3.36;
        $Fc['7']['29']=[];  $Fc['7']['29']['0.1']= 1.93;  $Fc['7']['29']['0.05']= 2.35;   $Fc['7']['29']['0.01']= 3.33;
        $Fc['7']['30']=[];  $Fc['7']['30']['0.1']= 1.93;  $Fc['7']['30']['0.05']= 2.33;   $Fc['7']['30']['0.01']= 3.3;
        $Fc['7']['40']=[];  $Fc['7']['40']['0.1']= 1.87;  $Fc['7']['40']['0.05']= 2.25;   $Fc['7']['40']['0.01']= 3.12;
        $Fc['7']['50']=[];  $Fc['7']['50']['0.1']= 1.84;  $Fc['7']['50']['0.05']= 2.2;    $Fc['7']['50']['0.01']= 3.02;
        $Fc['7']['60']=[];  $Fc['7']['60']['0.1']= 1.82;  $Fc['7']['60']['0.05']= 2.17;   $Fc['7']['60']['0.01']= 2.95;
        $Fc['7']['100']=[]; $Fc['7']['100']['0.1']= 1.78; $Fc['7']['100']['0.05']= 2.1;   $Fc['7']['100']['0.01']= 2.82;
        $Fc['7']['200']=[]; $Fc['7']['200']['0.1']= 1.75; $Fc['7']['200']['0.05']= 2.06;  $Fc['7']['200']['0.01']= 2.73;
        $Fc['7']['1000']=[];$Fc['7']['1000']['0.1']= 1.72;$Fc['7']['1000']['0.05']= 2.02; $Fc['7']['1000']['0.01']= 2.66;

        $Fc['8']=[];
        // v1 = 8
        $Fc['8']['1']=[];   $Fc['8']['1']['0.1']= 59.4;   $Fc['8']['1']['0.05']= 238.9;   $Fc['8']['1']['0.01']= 5981.1;
        $Fc['8']['2']=[];   $Fc['8']['2']['0.1']= 9.37;   $Fc['8']['2']['0.05']= 19.37;   $Fc['8']['2']['0.01']= 99.37;
        $Fc['8']['3']=[];   $Fc['8']['3']['0.1']= 5.25;   $Fc['8']['3']['0.05']= 8.85;    $Fc['8']['3']['0.01']= 27.49;
        $Fc['8']['4']=[];   $Fc['8']['4']['0.1']= 3.95;   $Fc['8']['4']['0.05']= 6.04;    $Fc['8']['4']['0.01']= 14.8;
        $Fc['8']['5']=[];   $Fc['8']['5']['0.1']= 3.34;   $Fc['8']['5']['0.05']= 4.82;    $Fc['8']['5']['0.01']= 10.29;
        $Fc['8']['6']=[];   $Fc['8']['6']['0.1']= 2.98;   $Fc['8']['6']['0.05']= 4.15;    $Fc['8']['6']['0.01']= 8.1;
        $Fc['8']['7']=[];   $Fc['8']['7']['0.1']= 2.75;   $Fc['8']['7']['0.05']= 3.73;    $Fc['8']['7']['0.01']= 6.84;
        $Fc['8']['8']=[];   $Fc['8']['8']['0.1']= 2.59;   $Fc['8']['8']['0.05']= 3.44;    $Fc['8']['8']['0.01']= 6.03;
        $Fc['8']['9']=[];   $Fc['8']['9']['0.1']= 2.47;   $Fc['8']['9']['0.05']= 3.23;    $Fc['8']['9']['0.01']= 5.47;
        $Fc['8']['10']=[];  $Fc['8']['10']['0.1']= 2.38;  $Fc['8']['10']['0.05']= 3.07;   $Fc['8']['10']['0.01']= 5.06;
        $Fc['8']['11']=[];  $Fc['8']['11']['0.1']= 2.3;   $Fc['8']['11']['0.05']= 2.95;   $Fc['8']['11']['0.01']= 4.74;
        $Fc['8']['12']=[];  $Fc['8']['12']['0.1']= 2.24;  $Fc['8']['12']['0.05']= 2.85;   $Fc['8']['12']['0.01']= 4.5;
        $Fc['8']['13']=[];  $Fc['8']['13']['0.1']= 2.2;   $Fc['8']['13']['0.05']= 2.77;   $Fc['8']['13']['0.01']= 4.3;
        $Fc['8']['14']=[];  $Fc['8']['14']['0.1']= 2.15;  $Fc['8']['14']['0.05']= 2.7;    $Fc['8']['14']['0.01']= 4.14;
        $Fc['8']['15']=[];  $Fc['8']['15']['0.1']= 2.12;  $Fc['8']['15']['0.05']= 2.64;   $Fc['8']['15']['0.01']= 4;
        $Fc['8']['16']=[];  $Fc['8']['16']['0.1']= 2.09;  $Fc['8']['16']['0.05']= 2.59;   $Fc['8']['16']['0.01']= 3.89;
        $Fc['8']['17']=[];  $Fc['8']['17']['0.1']= 2.06;  $Fc['8']['17']['0.05']= 2.55;   $Fc['8']['17']['0.01']= 3.79;
        $Fc['8']['18']=[];  $Fc['8']['18']['0.1']= 2.04;  $Fc['8']['18']['0.05']= 2.51;   $Fc['8']['18']['0.01']= 3.71;
        $Fc['8']['19']=[];  $Fc['8']['19']['0.1']= 2.02;  $Fc['8']['19']['0.05']= 2.48;   $Fc['8']['19']['0.01']= 3.63;
        $Fc['8']['20']=[];  $Fc['8']['20']['0.1']= 2;     $Fc['8']['20']['0.05']= 2.45;   $Fc['8']['20']['0.01']= 3.56;
        $Fc['8']['21']=[];  $Fc['8']['21']['0.1']= 1.98;  $Fc['8']['21']['0.05']= 2.42;   $Fc['8']['21']['0.01']= 3.51;
        $Fc['8']['22']=[];  $Fc['8']['22']['0.1']= 1.97;  $Fc['8']['22']['0.05']= 2.4;    $Fc['8']['22']['0.01']= 3.45;
        $Fc['8']['23']=[];  $Fc['8']['23']['0.1']= 1.95;  $Fc['8']['23']['0.05']= 2.37;   $Fc['8']['23']['0.01']= 3.41;
        $Fc['8']['24']=[];  $Fc['8']['24']['0.1']= 1.94;  $Fc['8']['24']['0.05']= 2.36;   $Fc['8']['24']['0.01']= 3.36;
        $Fc['8']['25']=[];  $Fc['8']['25']['0.1']= 1.93;  $Fc['8']['25']['0.05']= 2.34;   $Fc['8']['25']['0.01']= 3.32;
        $Fc['8']['26']=[];  $Fc['8']['26']['0.1']= 1.92;  $Fc['8']['26']['0.05']= 2.32;   $Fc['8']['26']['0.01']= 3.29;
        $Fc['8']['27']=[];  $Fc['8']['27']['0.1']= 1.91;  $Fc['8']['27']['0.05']= 2.31;   $Fc['8']['27']['0.01']= 3.26;
        $Fc['8']['28']=[];  $Fc['8']['28']['0.1']= 1.9;   $Fc['8']['28']['0.05']= 2.29;   $Fc['8']['28']['0.01']= 3.23;
        $Fc['8']['29']=[];  $Fc['8']['29']['0.1']= 1.89;  $Fc['8']['29']['0.05']= 2.28;   $Fc['8']['29']['0.01']= 3.2;
        $Fc['8']['30']=[];  $Fc['8']['30']['0.1']= 1.88;  $Fc['8']['30']['0.05']= 2.27;   $Fc['8']['30']['0.01']= 3.17;
        $Fc['8']['40']=[];  $Fc['8']['40']['0.1']= 1.83;  $Fc['8']['40']['0.05']= 2.18;   $Fc['8']['40']['0.01']= 2.99;
        $Fc['8']['50']=[];  $Fc['8']['50']['0.1']= 1.8;   $Fc['8']['50']['0.05']= 2.13;   $Fc['8']['50']['0.01']= 2.89;
        $Fc['8']['60']=[];  $Fc['8']['60']['0.1']= 1.77;  $Fc['8']['60']['0.05']= 2.1;    $Fc['8']['60']['0.01']= 2.82;
        $Fc['8']['100']=[]; $Fc['8']['100']['0.1']= 1.73; $Fc['8']['100']['0.05']= 2.03;  $Fc['8']['100']['0.01']= 2.69;
        $Fc['8']['200']=[]; $Fc['8']['200']['0.1']= 1.7;  $Fc['8']['200']['0.05']= 1.98;  $Fc['8']['200']['0.01']= 2.6;
        $Fc['8']['1000']=[];$Fc['8']['1000']['0.1']= 1.68;$Fc['8']['1000']['0.05']= 1.95; $Fc['8']['1000']['0.01']= 2.53;

        $Fc['9']=[];
        // v1 = 9
        $Fc['9']['1']=[];   $Fc['9']['1']['0.1']= 59.9;   $Fc['9']['1']['0.05']= 240.5;  $Fc['9']['1']['0.01']= 6022.5;
        $Fc['9']['2']=[];   $Fc['9']['2']['0.1']= 9.38;   $Fc['9']['2']['0.05']= 19.38;  $Fc['9']['2']['0.01']= 99.39;
        $Fc['9']['3']=[];   $Fc['9']['3']['0.1']= 5.24;   $Fc['9']['3']['0.05']= 8.81;   $Fc['9']['3']['0.01']= 27.35;
        $Fc['9']['4']=[];   $Fc['9']['4']['0.1']= 3.94;   $Fc['9']['4']['0.05']= 6;      $Fc['9']['4']['0.01']= 14.66;
        $Fc['9']['5']=[];   $Fc['9']['5']['0.1']= 3.32;   $Fc['9']['5']['0.05']= 4.77;   $Fc['9']['5']['0.01']= 10.16;
        $Fc['9']['6']=[];   $Fc['9']['6']['0.1']= 2.96;   $Fc['9']['6']['0.05']= 4.1;    $Fc['9']['6']['0.01']= 7.98;
        $Fc['9']['7']=[];   $Fc['9']['7']['0.1']= 2.72;   $Fc['9']['7']['0.05']= 3.68;   $Fc['9']['7']['0.01']= 6.72;
        $Fc['9']['8']=[];   $Fc['9']['8']['0.1']= 2.56;   $Fc['9']['8']['0.05']= 3.39;   $Fc['9']['8']['0.01']= 5.91;
        $Fc['9']['9']=[];   $Fc['9']['9']['0.1']= 2.44;   $Fc['9']['9']['0.05']= 3.18;   $Fc['9']['9']['0.01']= 5.35;
        $Fc['9']['10']=[];  $Fc['9']['10']['0.1']= 2.35;  $Fc['9']['10']['0.05']= 3.02;  $Fc['9']['10']['0.01']= 4.94;
        $Fc['9']['11']=[];  $Fc['9']['11']['0.1']= 2.27;  $Fc['9']['11']['0.05']= 2.9;   $Fc['9']['11']['0.01']= 4.63;
        $Fc['9']['12']=[];  $Fc['9']['12']['0.1']= 2.21;  $Fc['9']['12']['0.05']= 2.8;   $Fc['9']['12']['0.01']= 4.39;
        $Fc['9']['13']=[];  $Fc['9']['13']['0.1']= 2.16;  $Fc['9']['13']['0.05']= 2.71;  $Fc['9']['13']['0.01']= 4.19;
        $Fc['9']['14']=[];  $Fc['9']['14']['0.1']= 2.12;  $Fc['9']['14']['0.05']= 2.65;  $Fc['9']['14']['0.01']= 4.03;
        $Fc['9']['15']=[];  $Fc['9']['15']['0.1']= 2.09;  $Fc['9']['15']['0.05']= 2.59;  $Fc['9']['15']['0.01']= 3.89;
        $Fc['9']['16']=[];  $Fc['9']['16']['0.1']= 2.06;  $Fc['9']['16']['0.05']= 2.54;  $Fc['9']['16']['0.01']= 3.78;
        $Fc['9']['17']=[];  $Fc['9']['17']['0.1']= 2.03;  $Fc['9']['17']['0.05']= 2.49;  $Fc['9']['17']['0.01']= 3.68;
        $Fc['9']['18']=[];  $Fc['9']['18']['0.1']= 2;     $Fc['9']['18']['0.05']= 2.46;  $Fc['9']['18']['0.01']= 3.6;
        $Fc['9']['19']=[];  $Fc['9']['19']['0.1']= 1.98;  $Fc['9']['19']['0.05']= 2.42;  $Fc['9']['19']['0.01']= 3.52;
        $Fc['9']['20']=[];  $Fc['9']['20']['0.1']= 1.96;  $Fc['9']['20']['0.05']= 2.39;  $Fc['9']['20']['0.01']= 3.46;
        $Fc['9']['21']=[];  $Fc['9']['21']['0.1']= 1.95;  $Fc['9']['21']['0.05']= 2.37;  $Fc['9']['21']['0.01']= 3.4;
        $Fc['9']['22']=[];  $Fc['9']['22']['0.1']= 1.93;  $Fc['9']['22']['0.05']= 2.34;  $Fc['9']['22']['0.01']= 3.35;
        $Fc['9']['23']=[];  $Fc['9']['23']['0.1']= 1.92;  $Fc['9']['23']['0.05']= 2.32;  $Fc['9']['23']['0.01']= 3.3;
        $Fc['9']['24']=[];  $Fc['9']['24']['0.1']= 1.91;  $Fc['9']['24']['0.05']= 2.3;   $Fc['9']['24']['0.01']= 3.26;
        $Fc['9']['25']=[];  $Fc['9']['25']['0.1']= 1.89;  $Fc['9']['25']['0.05']= 2.28;  $Fc['9']['25']['0.01']= 3.22;
        $Fc['9']['26']=[];  $Fc['9']['26']['0.1']= 1.88;  $Fc['9']['26']['0.05']= 2.27;  $Fc['9']['26']['0.01']= 3.18;
        $Fc['9']['27']=[];  $Fc['9']['27']['0.1']= 1.87;  $Fc['9']['27']['0.05']= 2.25;  $Fc['9']['27']['0.01']= 3.15;
        $Fc['9']['28']=[];  $Fc['9']['28']['0.1']= 1.87;  $Fc['9']['28']['0.05']= 2.24;  $Fc['9']['28']['0.01']= 3.12;
        $Fc['9']['29']=[];  $Fc['9']['29']['0.1']= 1.86;  $Fc['9']['29']['0.05']= 2.22;  $Fc['9']['29']['0.01']= 3.09;
        $Fc['9']['30']=[];  $Fc['9']['30']['0.1']= 1.85;  $Fc['9']['30']['0.05']= 2.21;  $Fc['9']['30']['0.01']= 3.07;
        $Fc['9']['40']=[];  $Fc['9']['40']['0.1']= 1.79;  $Fc['9']['40']['0.05']= 2.12;  $Fc['9']['40']['0.01']= 2.89;
        $Fc['9']['50']=[];  $Fc['9']['50']['0.1']= 1.76;  $Fc['9']['50']['0.05']= 2.07;  $Fc['9']['50']['0.01']= 2.78;
        $Fc['9']['60']=[];  $Fc['9']['60']['0.1']= 1.74;  $Fc['9']['60']['0.05']= 2.04;  $Fc['9']['60']['0.01']= 2.72;
        $Fc['9']['100']=[]; $Fc['9']['100']['0.1']= 1.69; $Fc['9']['100']['0.05']= 1.97; $Fc['9']['100']['0.01']= 2.59;
        $Fc['9']['200']=[]; $Fc['9']['200']['0.1']= 1.66; $Fc['9']['200']['0.05']= 1.93; $Fc['9']['200']['0.01']= 2.5;
        $Fc['9']['1000']=[];$Fc['9']['1000']['0.1']= 1.64;$Fc['9']['1000']['0.05']= 1.89;$Fc['9']['1000']['0.01']= 2.42;

        $Fc['10']=[];
        // v1 = 10
        $Fc['10']['1']=[];   $Fc['10']['1']['0.1']= 60.2;   $Fc['10']['1']['0.05']= 241.9;  $Fc['10']['1']['0.01']= 6055.8;
        $Fc['10']['2']=[];   $Fc['10']['2']['0.1']= 9.39;   $Fc['10']['2']['0.05']= 19.4;   $Fc['10']['2']['0.01']= 99.4;
        $Fc['10']['3']=[];   $Fc['10']['3']['0.1']= 5.23;   $Fc['10']['3']['0.05']= 8.79;   $Fc['10']['3']['0.01']= 27.23;
        $Fc['10']['4']=[];   $Fc['10']['4']['0.1']= 3.92;   $Fc['10']['4']['0.05']= 5.96;   $Fc['10']['4']['0.01']= 14.55;
        $Fc['10']['5']=[];   $Fc['10']['5']['0.1']= 3.3;    $Fc['10']['5']['0.05']= 4.74;   $Fc['10']['5']['0.01']= 10.05;
        $Fc['10']['6']=[];   $Fc['10']['6']['0.1']= 2.94;   $Fc['10']['6']['0.05']= 4.06;   $Fc['10']['6']['0.01']= 7.87;
        $Fc['10']['7']=[];   $Fc['10']['7']['0.1']= 2.7;    $Fc['10']['7']['0.05']= 3.64;   $Fc['10']['7']['0.01']= 6.62;
        $Fc['10']['8']=[];   $Fc['10']['8']['0.1']= 2.54;   $Fc['10']['8']['0.05']= 3.35;   $Fc['10']['8']['0.01']= 5.81;
        $Fc['10']['9']=[];   $Fc['10']['9']['0.1']= 2.42;   $Fc['10']['9']['0.05']= 3.14;   $Fc['10']['9']['0.01']= 5.26;
        $Fc['10']['10']=[];  $Fc['10']['10']['0.1']= 2.32;  $Fc['10']['10']['0.05']= 2.98;  $Fc['10']['10']['0.01']= 4.85;
        $Fc['10']['11']=[];  $Fc['10']['11']['0.1']= 2.25;  $Fc['10']['11']['0.05']= 2.85;  $Fc['10']['11']['0.01']= 4.54;
        $Fc['10']['12']=[];  $Fc['10']['12']['0.1']= 2.19;  $Fc['10']['12']['0.05']= 2.75;  $Fc['10']['12']['0.01']= 4.3;
        $Fc['10']['13']=[];  $Fc['10']['13']['0.1']= 2.14;  $Fc['10']['13']['0.05']= 2.67;  $Fc['10']['13']['0.01']= 4.1;
        $Fc['10']['14']=[];  $Fc['10']['14']['0.1']= 2.1;   $Fc['10']['14']['0.05']= 2.6;   $Fc['10']['14']['0.01']= 3.94;
        $Fc['10']['15']=[];  $Fc['10']['15']['0.1']= 2.06;  $Fc['10']['15']['0.05']= 2.54;  $Fc['10']['15']['0.01']= 3.8;
        $Fc['10']['16']=[];  $Fc['10']['16']['0.1']= 2.03;  $Fc['10']['16']['0.05']= 2.49;  $Fc['10']['16']['0.01']= 3.69;
        $Fc['10']['17']=[];  $Fc['10']['17']['0.1']= 2;     $Fc['10']['17']['0.05']= 2.45;  $Fc['10']['17']['0.01']= 3.59;
        $Fc['10']['18']=[];  $Fc['10']['18']['0.1']= 1.98;  $Fc['10']['18']['0.05']= 2.41;  $Fc['10']['18']['0.01']= 3.51;
        $Fc['10']['19']=[];  $Fc['10']['19']['0.1']= 1.96;  $Fc['10']['19']['0.05']= 2.38;  $Fc['10']['19']['0.01']= 3.43;
        $Fc['10']['20']=[];  $Fc['10']['20']['0.1']= 1.94;  $Fc['10']['20']['0.05']= 2.35;  $Fc['10']['20']['0.01']= 3.37;
        $Fc['10']['21']=[];  $Fc['10']['21']['0.1']= 1.92;  $Fc['10']['21']['0.05']= 2.32;  $Fc['10']['21']['0.01']= 3.31;
        $Fc['10']['22']=[];  $Fc['10']['22']['0.1']= 1.9;   $Fc['10']['22']['0.05']= 2.3;   $Fc['10']['22']['0.01']= 3.26;
        $Fc['10']['23']=[];  $Fc['10']['23']['0.1']= 1.89;  $Fc['10']['23']['0.05']= 2.27;  $Fc['10']['23']['0.01']= 3.21;
        $Fc['10']['24']=[];  $Fc['10']['24']['0.1']= 1.88;  $Fc['10']['24']['0.05']= 2.25;  $Fc['10']['24']['0.01']= 3.17;
        $Fc['10']['25']=[];  $Fc['10']['25']['0.1']= 1.87;  $Fc['10']['25']['0.05']= 2.24;  $Fc['10']['25']['0.01']= 3.13;
        $Fc['10']['26']=[];  $Fc['10']['26']['0.1']= 1.86;  $Fc['10']['26']['0.05']= 2.22;  $Fc['10']['26']['0.01']= 3.09;
        $Fc['10']['27']=[];  $Fc['10']['27']['0.1']= 1.85;  $Fc['10']['27']['0.05']= 2.2;   $Fc['10']['27']['0.01']= 3.06;
        $Fc['10']['28']=[];  $Fc['10']['28']['0.1']= 1.84;  $Fc['10']['28']['0.05']= 2.19;  $Fc['10']['28']['0.01']= 3.03;
        $Fc['10']['29']=[];  $Fc['10']['29']['0.1']= 1.83;  $Fc['10']['29']['0.05']= 2.18;  $Fc['10']['29']['0.01']= 3;
        $Fc['10']['30']=[];  $Fc['10']['30']['0.1']= 1.82;  $Fc['10']['30']['0.05']= 2.16;  $Fc['10']['30']['0.01']= 2.98;
        $Fc['10']['40']=[];  $Fc['10']['40']['0.1']= 1.76;  $Fc['10']['40']['0.05']= 2.08;  $Fc['10']['40']['0.01']= 2.8;
        $Fc['10']['50']=[];  $Fc['10']['50']['0.1']= 1.73;  $Fc['10']['50']['0.05']= 2.03;  $Fc['10']['50']['0.01']= 2.7;
        $Fc['10']['60']=[];  $Fc['10']['60']['0.1']= 1.71;  $Fc['10']['60']['0.05']= 1.99;  $Fc['10']['60']['0.01']= 2.63;
        $Fc['10']['100']=[]; $Fc['10']['100']['0.1']= 1.66; $Fc['10']['100']['0.05']= 1.93; $Fc['10']['100']['0.01']= 2.5;
        $Fc['10']['200']=[]; $Fc['10']['200']['0.1']= 1.63; $Fc['10']['200']['0.05']= 1.88; $Fc['10']['200']['0.01']= 2.41;
        $Fc['10']['1000']=[];$Fc['10']['1000']['0.1']= 1.61;$Fc['10']['1000']['0.05']= 1.84;$Fc['10']['1000']['0.01']= 2.34;

        $Fc['11']=[];
        // v1 = 11
        $Fc['11']['1']=[];   $Fc['11']['1']['0.1']= 60.5;   $Fc['11']['1']['0.05']= 243;    $Fc['11']['1']['0.01']= 6083.3;
        $Fc['11']['2']=[];   $Fc['11']['2']['0.1']= 9.4;    $Fc['11']['2']['0.05']= 19.4;   $Fc['11']['2']['0.01']= 99.4;
        $Fc['11']['3']=[];   $Fc['11']['3']['0.1']= 5.22;   $Fc['11']['3']['0.05']= 8.76;   $Fc['11']['3']['0.01']= 27.13;
        $Fc['11']['4']=[];   $Fc['11']['4']['0.1']= 3.91;   $Fc['11']['4']['0.05']= 5.94;   $Fc['11']['4']['0.01']= 14.45;
        $Fc['11']['5']=[];   $Fc['11']['5']['0.1']= 3.28;   $Fc['11']['5']['0.05']= 4.7;    $Fc['11']['5']['0.01']= 9.96;
        $Fc['11']['6']=[];   $Fc['11']['6']['0.1']= 2.92;   $Fc['11']['6']['0.05']= 4.03;   $Fc['11']['6']['0.01']= 7.79;
        $Fc['11']['7']=[];   $Fc['11']['7']['0.1']= 2.68;   $Fc['11']['7']['0.05']= 3.6;    $Fc['11']['7']['0.01']= 6.54;
        $Fc['11']['8']=[];   $Fc['11']['8']['0.1']= 2.52;   $Fc['11']['8']['0.05']= 3.31;   $Fc['11']['8']['0.01']= 5.73;
        $Fc['11']['9']=[];   $Fc['11']['9']['0.1']= 2.4;    $Fc['11']['9']['0.05']= 3.1;    $Fc['11']['9']['0.01']= 5.18;
        $Fc['11']['10']=[];  $Fc['11']['10']['0.1']= 2.3;   $Fc['11']['10']['0.05']= 2.94;  $Fc['11']['10']['0.01']= 4.77;
        $Fc['11']['11']=[];  $Fc['11']['11']['0.1']= 2.23;  $Fc['11']['11']['0.05']= 2.82;  $Fc['11']['11']['0.01']= 4.46;
        $Fc['11']['12']=[];  $Fc['11']['12']['0.1']= 2.17;  $Fc['11']['12']['0.05']= 2.72;  $Fc['11']['12']['0.01']= 4.22;
        $Fc['11']['13']=[];  $Fc['11']['13']['0.1']= 2.12;  $Fc['11']['13']['0.05']= 2.63;  $Fc['11']['13']['0.01']= 4.02;
        $Fc['11']['14']=[];  $Fc['11']['14']['0.1']= 2.07;  $Fc['11']['14']['0.05']= 2.57;  $Fc['11']['14']['0.01']= 3.86;
        $Fc['11']['15']=[];  $Fc['11']['15']['0.1']= 2.04;  $Fc['11']['15']['0.05']= 2.51;  $Fc['11']['15']['0.01']= 3.73;
        $Fc['11']['16']=[];  $Fc['11']['16']['0.1']= 2.01;  $Fc['11']['16']['0.05']= 2.46;  $Fc['11']['16']['0.01']= 3.62;
        $Fc['11']['17']=[];  $Fc['11']['17']['0.1']= 1.98;  $Fc['11']['17']['0.05']= 2.41;  $Fc['11']['17']['0.01']= 3.52;
        $Fc['11']['18']=[];  $Fc['11']['18']['0.1']= 1.95;  $Fc['11']['18']['0.05']= 2.37;  $Fc['11']['18']['0.01']= 3.43;
        $Fc['11']['19']=[];  $Fc['11']['19']['0.1']= 1.93;  $Fc['11']['19']['0.05']= 2.34;  $Fc['11']['19']['0.01']= 3.36;
        $Fc['11']['20']=[];  $Fc['11']['20']['0.1']= 1.91;  $Fc['11']['20']['0.05']= 2.31;  $Fc['11']['20']['0.01']= 3.29;
        $Fc['11']['21']=[];  $Fc['11']['21']['0.1']= 1.9;   $Fc['11']['21']['0.05']= 2.28;  $Fc['11']['21']['0.01']= 3.24;
        $Fc['11']['22']=[];  $Fc['11']['22']['0.1']= 1.88;  $Fc['11']['22']['0.05']= 2.26;  $Fc['11']['22']['0.01']= 3.18;
        $Fc['11']['23']=[];  $Fc['11']['23']['0.1']= 1.87;  $Fc['11']['23']['0.05']= 2.24;  $Fc['11']['23']['0.01']= 3.14;
        $Fc['11']['24']=[];  $Fc['11']['24']['0.1']= 1.85;  $Fc['11']['24']['0.05']= 2.22;  $Fc['11']['24']['0.01']= 3.09;
        $Fc['11']['25']=[];  $Fc['11']['25']['0.1']= 1.84;  $Fc['11']['25']['0.05']= 2.2;   $Fc['11']['25']['0.01']= 3.06;
        $Fc['11']['26']=[];  $Fc['11']['26']['0.1']= 1.83;  $Fc['11']['26']['0.05']= 2.18;  $Fc['11']['26']['0.01']= 3.02;
        $Fc['11']['27']=[];  $Fc['11']['27']['0.1']= 1.82;  $Fc['11']['27']['0.05']= 2.17;  $Fc['11']['27']['0.01']= 2.99;
        $Fc['11']['28']=[];  $Fc['11']['28']['0.1']= 1.81;  $Fc['11']['28']['0.05']= 2.15;  $Fc['11']['28']['0.01']= 2.96;
        $Fc['11']['29']=[];  $Fc['11']['29']['0.1']= 1.8;   $Fc['11']['29']['0.05']= 2.14;  $Fc['11']['29']['0.01']= 2.93;
        $Fc['11']['30']=[];  $Fc['11']['30']['0.1']= 1.79;  $Fc['11']['30']['0.05']= 2.13;  $Fc['11']['30']['0.01']= 2.91;
        $Fc['11']['40']=[];  $Fc['11']['40']['0.1']= 1.74;  $Fc['11']['40']['0.05']= 2.04;  $Fc['11']['40']['0.01']= 2.73;
        $Fc['11']['50']=[];  $Fc['11']['50']['0.1']= 1.7;   $Fc['11']['50']['0.05']= 1.99;  $Fc['11']['50']['0.01']= 2.62;
        $Fc['11']['60']=[];  $Fc['11']['60']['0.1']= 1.68;  $Fc['11']['60']['0.05']= 1.95;  $Fc['11']['60']['0.01']= 2.56;
        $Fc['11']['100']=[]; $Fc['11']['100']['0.1']= 1.64; $Fc['11']['100']['0.05']= 1.89; $Fc['11']['100']['0.01']= 2.43;
        $Fc['11']['200']=[]; $Fc['11']['200']['0.1']= 1.6;  $Fc['11']['200']['0.05']= 1.84; $Fc['11']['200']['0.01']= 2.34;
        $Fc['11']['1000']=[];$Fc['11']['1000']['0.1']= 1.58;$Fc['11']['1000']['0.05']= 1.8; $Fc['11']['1000']['0.01']= 2.27;

        $Fc['12']=[];
        // v1 = 12
        $Fc['12']['1']=[];   $Fc['12']['1']['0.1']= 60.7;   $Fc['12']['1']['0.05']= 243.9;  $Fc['12']['1']['0.01']= 6106.3;
        $Fc['12']['2']=[];   $Fc['12']['2']['0.1']= 9.41;   $Fc['12']['2']['0.05']= 19.41;  $Fc['12']['2']['0.01']= 99.42;
        $Fc['12']['3']=[];   $Fc['12']['3']['0.1']= 5.22;   $Fc['12']['3']['0.05']= 8.74;   $Fc['12']['3']['0.01']= 27.05;
        $Fc['12']['4']=[];   $Fc['12']['4']['0.1']= 3.9;    $Fc['12']['4']['0.05']= 5.91;   $Fc['12']['4']['0.01']= 14.37;
        $Fc['12']['5']=[];   $Fc['12']['5']['0.1']= 3.27;   $Fc['12']['5']['0.05']= 4.68;   $Fc['12']['5']['0.01']= 9.89;
        $Fc['12']['6']=[];   $Fc['12']['6']['0.1']= 2.9;    $Fc['12']['6']['0.05']= 4;      $Fc['12']['6']['0.01']= 7.72;
        $Fc['12']['7']=[];   $Fc['12']['7']['0.1']= 2.67;   $Fc['12']['7']['0.05']= 3.57;   $Fc['12']['7']['0.01']= 6.47;
        $Fc['12']['8']=[];   $Fc['12']['8']['0.1']= 2.5;    $Fc['12']['8']['0.05']= 3.28;   $Fc['12']['8']['0.01']= 5.67;
        $Fc['12']['9']=[];   $Fc['12']['9']['0.1']= 2.38;   $Fc['12']['9']['0.05']= 3.07;   $Fc['12']['9']['0.01']= 5.11;
        $Fc['12']['10']=[];  $Fc['12']['10']['0.1']= 2.28;  $Fc['12']['10']['0.05']= 2.91;  $Fc['12']['10']['0.01']= 4.71;
        $Fc['12']['11']=[];  $Fc['12']['11']['0.1']= 2.21;  $Fc['12']['11']['0.05']= 2.79;  $Fc['12']['11']['0.01']= 4.4;
        $Fc['12']['12']=[];  $Fc['12']['12']['0.1']= 2.15;  $Fc['12']['12']['0.05']= 2.69;  $Fc['12']['12']['0.01']= 4.16;
        $Fc['12']['13']=[];  $Fc['12']['13']['0.1']= 2.1;   $Fc['12']['13']['0.05']= 2.6;   $Fc['12']['13']['0.01']= 3.96;
        $Fc['12']['14']=[];  $Fc['12']['14']['0.1']= 2.05;  $Fc['12']['14']['0.05']= 2.53;  $Fc['12']['14']['0.01']= 3.8;
        $Fc['12']['15']=[];  $Fc['12']['15']['0.1']= 2.02;  $Fc['12']['15']['0.05']= 2.48;  $Fc['12']['15']['0.01']= 3.67;
        $Fc['12']['16']=[];  $Fc['12']['16']['0.1']= 1.99;  $Fc['12']['16']['0.05']= 2.42;  $Fc['12']['16']['0.01']= 3.55;
        $Fc['12']['17']=[];  $Fc['12']['17']['0.1']= 1.96;  $Fc['12']['17']['0.05']= 2.38;  $Fc['12']['17']['0.01']= 3.46;
        $Fc['12']['18']=[];  $Fc['12']['18']['0.1']= 1.93;  $Fc['12']['18']['0.05']= 2.34;  $Fc['12']['18']['0.01']= 3.37;
        $Fc['12']['19']=[];  $Fc['12']['19']['0.1']= 1.91;  $Fc['12']['19']['0.05']= 2.31;  $Fc['12']['19']['0.01']= 3.3;
        $Fc['12']['20']=[];  $Fc['12']['20']['0.1']= 1.89;  $Fc['12']['20']['0.05']= 2.28;  $Fc['12']['20']['0.01']= 3.23;
        $Fc['12']['21']=[];  $Fc['12']['21']['0.1']= 1.88;  $Fc['12']['21']['0.05']= 2.25;  $Fc['12']['21']['0.01']= 3.17;
        $Fc['12']['22']=[];  $Fc['12']['22']['0.1']= 1.86;  $Fc['12']['22']['0.05']= 2.23;  $Fc['12']['22']['0.01']= 3.12;
        $Fc['12']['23']=[];  $Fc['12']['23']['0.1']= 1.84;  $Fc['12']['23']['0.05']= 2.2;   $Fc['12']['23']['0.01']= 3.07;
        $Fc['12']['24']=[];  $Fc['12']['24']['0.1']= 1.83;  $Fc['12']['24']['0.05']= 2.18;  $Fc['12']['24']['0.01']= 3.03;
        $Fc['12']['25']=[];  $Fc['12']['25']['0.1']= 1.82;  $Fc['12']['25']['0.05']= 2.16;  $Fc['12']['25']['0.01']= 2.99;
        $Fc['12']['26']=[];  $Fc['12']['26']['0.1']= 1.81;  $Fc['12']['26']['0.05']= 2.15;  $Fc['12']['26']['0.01']= 2.96;
        $Fc['12']['27']=[];  $Fc['12']['27']['0.1']= 1.8;   $Fc['12']['27']['0.05']= 2.13;  $Fc['12']['27']['0.01']= 2.93;
        $Fc['12']['28']=[];  $Fc['12']['28']['0.1']= 1.79;  $Fc['12']['28']['0.05']= 2.12;  $Fc['12']['28']['0.01']= 2.9;
        $Fc['12']['29']=[];  $Fc['12']['29']['0.1']= 1.78;  $Fc['12']['29']['0.05']= 2.1;   $Fc['12']['29']['0.01']= 2.87;
        $Fc['12']['30']=[];  $Fc['12']['30']['0.1']= 1.77;  $Fc['12']['30']['0.05']= 2.09;  $Fc['12']['30']['0.01']= 2.84;
        $Fc['12']['40']=[];  $Fc['12']['40']['0.1']= 1.71;  $Fc['12']['40']['0.05']= 2;     $Fc['12']['40']['0.01']= 2.66;
        $Fc['12']['50']=[];  $Fc['12']['50']['0.1']= 1.68;  $Fc['12']['50']['0.05']= 1.95;  $Fc['12']['50']['0.01']= 2.56;
        $Fc['12']['60']=[];  $Fc['12']['60']['0.1']= 1.66;  $Fc['12']['60']['0.05']= 1.92;  $Fc['12']['60']['0.01']= 2.5;
        $Fc['12']['100']=[]; $Fc['12']['100']['0.1']= 1.61; $Fc['12']['100']['0.05']= 1.85; $Fc['12']['100']['0.01']= 2.37;
        $Fc['12']['200']=[]; $Fc['12']['200']['0.1']= 1.58; $Fc['12']['200']['0.05']= 1.8;  $Fc['12']['200']['0.01']= 2.27;
        $Fc['12']['1000']=[];$Fc['12']['1000']['0.1']= 1.55;$Fc['12']['1000']['0.05']= 1.76;$Fc['12']['1000']['0.01']= 2.2;

        $Fc['13']=[];
        // v1 = 13
        $Fc['13']['1']=[];   $Fc['13']['1']['0.1']= 60.9;   $Fc['13']['1']['0.05']= 244.7;   $Fc['13']['1']['0.01']= 6125.9;
        $Fc['13']['2']=[];   $Fc['13']['2']['0.1']= 9.41;   $Fc['13']['2']['0.05']= 19.42;   $Fc['13']['2']['0.01']= 99.42;
        $Fc['13']['3']=[];   $Fc['13']['3']['0.1']= 5.21;   $Fc['13']['3']['0.05']= 8.73;    $Fc['13']['3']['0.01']= 26.98;
        $Fc['13']['4']=[];   $Fc['13']['4']['0.1']= 3.89;   $Fc['13']['4']['0.05']= 5.89;    $Fc['13']['4']['0.01']= 14.31;
        $Fc['13']['5']=[];   $Fc['13']['5']['0.1']= 3.26;   $Fc['13']['5']['0.05']= 4.66;    $Fc['13']['5']['0.01']= 9.82;
        $Fc['13']['6']=[];   $Fc['13']['6']['0.1']= 2.89;   $Fc['13']['6']['0.05']= 3.98;    $Fc['13']['6']['0.01']= 7.66;
        $Fc['13']['7']=[];   $Fc['13']['7']['0.1']= 2.65;   $Fc['13']['7']['0.05']= 3.55;    $Fc['13']['7']['0.01']= 6.41;
        $Fc['13']['8']=[];   $Fc['13']['8']['0.1']= 2.49;   $Fc['13']['8']['0.05']= 3.26;    $Fc['13']['8']['0.01']= 5.61;
        $Fc['13']['9']=[];   $Fc['13']['9']['0.1']= 2.36;   $Fc['13']['9']['0.05']= 3.05;    $Fc['13']['9']['0.01']= 5.05;
        $Fc['13']['10']=[];  $Fc['13']['10']['0.1']= 2.27;  $Fc['13']['10']['0.05']= 2.89;   $Fc['13']['10']['0.01']= 4.65;
        $Fc['13']['11']=[];  $Fc['13']['11']['0.1']= 2.19;  $Fc['13']['11']['0.05']= 2.76;   $Fc['13']['11']['0.01']= 4.34;
        $Fc['13']['12']=[];  $Fc['13']['12']['0.1']= 2.13;  $Fc['13']['12']['0.05']= 2.66;   $Fc['13']['12']['0.01']= 4.1;
        $Fc['13']['13']=[];  $Fc['13']['13']['0.1']= 2.08;  $Fc['13']['13']['0.05']= 2.58;   $Fc['13']['13']['0.01']= 3.91;
        $Fc['13']['14']=[];  $Fc['13']['14']['0.1']= 2.04;  $Fc['13']['14']['0.05']= 2.51;   $Fc['13']['14']['0.01']= 3.75;
        $Fc['13']['15']=[];  $Fc['13']['15']['0.1']= 2;     $Fc['13']['15']['0.05']= 2.45;   $Fc['13']['15']['0.01']= 3.61;
        $Fc['13']['16']=[];  $Fc['13']['16']['0.1']= 1.97;  $Fc['13']['16']['0.05']= 2.4;    $Fc['13']['16']['0.01']= 3.5;
        $Fc['13']['17']=[];  $Fc['13']['17']['0.1']= 1.94;  $Fc['13']['17']['0.05']= 2.35;   $Fc['13']['17']['0.01']= 3.4;
        $Fc['13']['18']=[];  $Fc['13']['18']['0.1']= 1.92;  $Fc['13']['18']['0.05']= 2.31;   $Fc['13']['18']['0.01']= 3.32;
        $Fc['13']['19']=[];  $Fc['13']['19']['0.1']= 1.89;  $Fc['13']['19']['0.05']= 2.28;   $Fc['13']['19']['0.01']= 3.24;
        $Fc['13']['20']=[];  $Fc['13']['20']['0.1']= 1.87;  $Fc['13']['20']['0.05']= 2.25;   $Fc['13']['20']['0.01']= 3.18;
        $Fc['13']['21']=[];  $Fc['13']['21']['0.1']= 1.86;  $Fc['13']['21']['0.05']= 2.22;   $Fc['13']['21']['0.01']= 3.12;
        $Fc['13']['22']=[];  $Fc['13']['22']['0.1']= 1.84;  $Fc['13']['22']['0.05']= 2.2;    $Fc['13']['22']['0.01']= 3.07;
        $Fc['13']['23']=[];  $Fc['13']['23']['0.1']= 1.83;  $Fc['13']['23']['0.05']= 2.18;   $Fc['13']['23']['0.01']= 3.02;
        $Fc['13']['24']=[];  $Fc['13']['24']['0.1']= 1.81;  $Fc['13']['24']['0.05']= 2.15;   $Fc['13']['24']['0.01']= 2.98;
        $Fc['13']['25']=[];  $Fc['13']['25']['0.1']= 1.8;   $Fc['13']['25']['0.05']= 2.14;   $Fc['13']['25']['0.01']= 2.94;
        $Fc['13']['26']=[];  $Fc['13']['26']['0.1']= 1.79;  $Fc['13']['26']['0.05']= 2.12;   $Fc['13']['26']['0.01']= 2.9;
        $Fc['13']['27']=[];  $Fc['13']['27']['0.1']= 1.78;  $Fc['13']['27']['0.05']= 2.1;    $Fc['13']['27']['0.01']= 2.87;
        $Fc['13']['28']=[];  $Fc['13']['28']['0.1']= 1.77;  $Fc['13']['28']['0.05']= 2.09;   $Fc['13']['28']['0.01']= 2.84;
        $Fc['13']['29']=[];  $Fc['13']['29']['0.1']= 1.76;  $Fc['13']['29']['0.05']= 2.08;   $Fc['13']['29']['0.01']= 2.81;
        $Fc['13']['30']=[];  $Fc['13']['30']['0.1']= 1.75;  $Fc['13']['30']['0.05']= 2.06;   $Fc['13']['30']['0.01']= 2.79;
        $Fc['13']['40']=[];  $Fc['13']['40']['0.1']= 1.7;   $Fc['13']['40']['0.05']= 1.97;   $Fc['13']['40']['0.01']= 2.61;
        $Fc['13']['50']=[];  $Fc['13']['50']['0.1']= 1.66;  $Fc['13']['50']['0.05']= 1.92;   $Fc['13']['50']['0.01']= 2.51;
        $Fc['13']['60']=[];  $Fc['13']['60']['0.1']= 1.64;  $Fc['13']['60']['0.05']= 1.89;   $Fc['13']['60']['0.01']= 2.44;
        $Fc['13']['100']=[]; $Fc['13']['100']['0.1']= 1.59; $Fc['13']['100']['0.05']= 1.82;  $Fc['13']['100']['0.01']= 2.31;
        $Fc['13']['200']=[]; $Fc['13']['200']['0.1']= 1.56; $Fc['13']['200']['0.05']= 1.77;  $Fc['13']['200']['0.01']= 2.22;
        $Fc['13']['1000']=[];$Fc['13']['1000']['0.1']= 1.53;$Fc['13']['1000']['0.05']= 1.73; $Fc['13']['1000']['0.01']= 2.15;

        $Fc['14']=[];
        // v1 = 14
        $Fc['14']['1']=[];   $Fc['14']['1']['0.1']= 61.1;   $Fc['14']['1']['0.05']= 245.4;   $Fc['14']['1']['0.01']= 6142.7;
        $Fc['14']['2']=[];   $Fc['14']['2']['0.1']= 9.42;   $Fc['14']['2']['0.05']= 19.42;   $Fc['14']['2']['0.01']= 99.43;
        $Fc['14']['3']=[];   $Fc['14']['3']['0.1']= 5.2;    $Fc['14']['3']['0.05']= 8.71;    $Fc['14']['3']['0.01']= 26.92;
        $Fc['14']['4']=[];   $Fc['14']['4']['0.1']= 3.88;   $Fc['14']['4']['0.05']= 5.87;    $Fc['14']['4']['0.01']= 14.25;
        $Fc['14']['5']=[];   $Fc['14']['5']['0.1']= 3.25;   $Fc['14']['5']['0.05']= 4.64;    $Fc['14']['5']['0.01']= 9.77;
        $Fc['14']['6']=[];   $Fc['14']['6']['0.1']= 2.88;   $Fc['14']['6']['0.05']= 3.96;    $Fc['14']['6']['0.01']= 7.6;
        $Fc['14']['7']=[];   $Fc['14']['7']['0.1']= 2.64;   $Fc['14']['7']['0.05']= 3.53;    $Fc['14']['7']['0.01']= 6.36;
        $Fc['14']['8']=[];   $Fc['14']['8']['0.1']= 2.48;   $Fc['14']['8']['0.05']= 3.24;    $Fc['14']['8']['0.01']= 5.56;
        $Fc['14']['9']=[];   $Fc['14']['9']['0.1']= 2.35;   $Fc['14']['9']['0.05']= 3.03;    $Fc['14']['9']['0.01']= 5.01;
        $Fc['14']['10']=[];  $Fc['14']['10']['0.1']= 2.26;  $Fc['14']['10']['0.05']= 2.86;   $Fc['14']['10']['0.01']= 4.6;
        $Fc['14']['11']=[];  $Fc['14']['11']['0.1']= 2.18;  $Fc['14']['11']['0.05']= 2.74;   $Fc['14']['11']['0.01']= 4.29;
        $Fc['14']['12']=[];  $Fc['14']['12']['0.1']= 2.12;  $Fc['14']['12']['0.05']= 2.64;   $Fc['14']['12']['0.01']= 4.05;
        $Fc['14']['13']=[];  $Fc['14']['13']['0.1']= 2.07;  $Fc['14']['13']['0.05']= 2.55;   $Fc['14']['13']['0.01']= 3.86;
        $Fc['14']['14']=[];  $Fc['14']['14']['0.1']= 2.02;  $Fc['14']['14']['0.05']= 2.48;   $Fc['14']['14']['0.01']= 3.7;
        $Fc['14']['15']=[];  $Fc['14']['15']['0.1']= 1.99;  $Fc['14']['15']['0.05']= 2.42;   $Fc['14']['15']['0.01']= 3.56;
        $Fc['14']['16']=[];  $Fc['14']['16']['0.1']= 1.95;  $Fc['14']['16']['0.05']= 2.37;   $Fc['14']['16']['0.01']= 3.45;
        $Fc['14']['17']=[];  $Fc['14']['17']['0.1']= 1.93;  $Fc['14']['17']['0.05']= 2.33;   $Fc['14']['17']['0.01']= 3.35;
        $Fc['14']['18']=[];  $Fc['14']['18']['0.1']= 1.9;   $Fc['14']['18']['0.05']= 2.29;   $Fc['14']['18']['0.01']= 3.27;
        $Fc['14']['19']=[];  $Fc['14']['19']['0.1']= 1.88;  $Fc['14']['19']['0.05']= 2.26;   $Fc['14']['19']['0.01']= 3.19;
        $Fc['14']['20']=[];  $Fc['14']['20']['0.1']= 1.86;  $Fc['14']['20']['0.05']= 2.22;   $Fc['14']['20']['0.01']= 3.13;
        $Fc['14']['21']=[];  $Fc['14']['21']['0.1']= 1.84;  $Fc['14']['21']['0.05']= 2.2;    $Fc['14']['21']['0.01']= 3.07;
        $Fc['14']['22']=[];  $Fc['14']['22']['0.1']= 1.83;  $Fc['14']['22']['0.05']= 2.17;   $Fc['14']['22']['0.01']= 3.02;
        $Fc['14']['23']=[];  $Fc['14']['23']['0.1']= 1.81;  $Fc['14']['23']['0.05']= 2.15;   $Fc['14']['23']['0.01']= 2.97;
        $Fc['14']['24']=[];  $Fc['14']['24']['0.1']= 1.8;   $Fc['14']['24']['0.05']= 2.13;   $Fc['14']['24']['0.01']= 2.93;
        $Fc['14']['25']=[];  $Fc['14']['25']['0.1']= 1.79;  $Fc['14']['25']['0.05']= 2.11;   $Fc['14']['25']['0.01']= 2.89;
        $Fc['14']['26']=[];  $Fc['14']['26']['0.1']= 1.77;  $Fc['14']['26']['0.05']= 2.09;   $Fc['14']['26']['0.01']= 2.86;
        $Fc['14']['27']=[];  $Fc['14']['27']['0.1']= 1.76;  $Fc['14']['27']['0.05']= 2.08;   $Fc['14']['27']['0.01']= 2.82;
        $Fc['14']['28']=[];  $Fc['14']['28']['0.1']= 1.75;  $Fc['14']['28']['0.05']= 2.06;   $Fc['14']['28']['0.01']= 2.79;
        $Fc['14']['29']=[];  $Fc['14']['29']['0.1']= 1.75;  $Fc['14']['29']['0.05']= 2.05;   $Fc['14']['29']['0.01']= 2.77;
        $Fc['14']['30']=[];  $Fc['14']['30']['0.1']= 1.74;  $Fc['14']['30']['0.05']= 2.04;   $Fc['14']['30']['0.01']= 2.74;
        $Fc['14']['40']=[];  $Fc['14']['40']['0.1']= 1.68;  $Fc['14']['40']['0.05']= 1.95;   $Fc['14']['40']['0.01']= 2.56;
        $Fc['14']['50']=[];  $Fc['14']['50']['0.1']= 1.64;  $Fc['14']['50']['0.05']= 1.89;   $Fc['14']['50']['0.01']= 2.46;
        $Fc['14']['60']=[];  $Fc['14']['60']['0.1']= 1.62;  $Fc['14']['60']['0.05']= 1.86;   $Fc['14']['60']['0.01']= 2.39;
        $Fc['14']['100']=[]; $Fc['14']['100']['0.1']= 1.57; $Fc['14']['100']['0.05']= 1.79;  $Fc['14']['100']['0.01']= 2.27;
        $Fc['14']['200']=[]; $Fc['14']['200']['0.1']= 1.54; $Fc['14']['200']['0.05']= 1.74;  $Fc['14']['200']['0.01']= 2.17;
        $Fc['14']['1000']=[];$Fc['14']['1000']['0.1']= 1.51;$Fc['14']['1000']['0.05']= 1.7;  $Fc['14']['1000']['0.01']= 2.1;

        $Fc['15']=[];
        // v1 = 15
        $Fc['15']['1']=[];   $Fc['15']['1']['0.1']= 61.2;   $Fc['15']['1']['0.05']= 245.9;   $Fc['15']['1']['0.01']= 6157.3;
        $Fc['15']['2']=[];   $Fc['15']['2']['0.1']= 9.42;   $Fc['15']['2']['0.05']= 19.43;   $Fc['15']['2']['0.01']= 99.43;
        $Fc['15']['3']=[];   $Fc['15']['3']['0.1']= 5.2;    $Fc['15']['3']['0.05']= 8.7;     $Fc['15']['3']['0.01']= 26.9;
        $Fc['15']['4']=[];   $Fc['15']['4']['0.1']= 3.87;   $Fc['15']['4']['0.05']= 5.86;    $Fc['15']['4']['0.01']= 14.2;
        $Fc['15']['5']=[];   $Fc['15']['5']['0.1']= 3.24;   $Fc['15']['5']['0.05']= 4.62;    $Fc['15']['5']['0.01']= 9.72;
        $Fc['15']['6']=[];   $Fc['15']['6']['0.1']= 2.87;   $Fc['15']['6']['0.05']= 3.94;    $Fc['15']['6']['0.01']= 7.56;
        $Fc['15']['7']=[];   $Fc['15']['7']['0.1']= 2.63;   $Fc['15']['7']['0.05']= 3.51;    $Fc['15']['7']['0.01']= 6.31;
        $Fc['15']['8']=[];   $Fc['15']['8']['0.1']= 2.46;   $Fc['15']['8']['0.05']= 3.22;    $Fc['15']['8']['0.01']= 5.52;
        $Fc['15']['9']=[];   $Fc['15']['9']['0.1']= 2.34;   $Fc['15']['9']['0.05']= 3.01;    $Fc['15']['9']['0.01']= 4.96;
        $Fc['15']['10']=[];  $Fc['15']['10']['0.1']= 2.24;  $Fc['15']['10']['0.05']= 2.84;   $Fc['15']['10']['0.01']= 4.56;
        $Fc['15']['11']=[];  $Fc['15']['11']['0.1']= 2.17;  $Fc['15']['11']['0.05']= 2.72;   $Fc['15']['11']['0.01']= 4.25;
        $Fc['15']['12']=[];  $Fc['15']['12']['0.1']= 2.1;   $Fc['15']['12']['0.05']= 2.62;   $Fc['15']['12']['0.01']= 4.01;
        $Fc['15']['13']=[];  $Fc['15']['13']['0.1']= 2.05;  $Fc['15']['13']['0.05']= 2.53;   $Fc['15']['13']['0.01']= 3.82;
        $Fc['15']['14']=[];  $Fc['15']['14']['0.1']= 2.01;  $Fc['15']['14']['0.05']= 2.46;   $Fc['15']['14']['0.01']= 3.66;
        $Fc['15']['15']=[];  $Fc['15']['15']['0.1']= 1.97;  $Fc['15']['15']['0.05']= 2.4;    $Fc['15']['15']['0.01']= 3.52;
        $Fc['15']['16']=[];  $Fc['15']['16']['0.1']= 1.94;  $Fc['15']['16']['0.05']= 2.35;   $Fc['15']['16']['0.01']= 3.41;
        $Fc['15']['17']=[];  $Fc['15']['17']['0.1']= 1.91;  $Fc['15']['17']['0.05']= 2.31;   $Fc['15']['17']['0.01']= 3.31;
        $Fc['15']['18']=[];  $Fc['15']['18']['0.1']= 1.89;  $Fc['15']['18']['0.05']= 2.27;   $Fc['15']['18']['0.01']= 3.23;
        $Fc['15']['19']=[];  $Fc['15']['19']['0.1']= 1.86;  $Fc['15']['19']['0.05']= 2.23;   $Fc['15']['19']['0.01']= 3.15;
        $Fc['15']['20']=[];  $Fc['15']['20']['0.1']= 1.84;  $Fc['15']['20']['0.05']= 2.2;    $Fc['15']['20']['0.01']= 3.09;
        $Fc['15']['21']=[];  $Fc['15']['21']['0.1']= 1.83;  $Fc['15']['21']['0.05']= 2.18;   $Fc['15']['21']['0.01']= 3.03;
        $Fc['15']['22']=[];  $Fc['15']['22']['0.1']= 1.81;  $Fc['15']['22']['0.05']= 2.15;   $Fc['15']['22']['0.01']= 2.98;
        $Fc['15']['23']=[];  $Fc['15']['23']['0.1']= 1.8;   $Fc['15']['23']['0.05']= 2.13;   $Fc['15']['23']['0.01']= 2.93;
        $Fc['15']['24']=[];  $Fc['15']['24']['0.1']= 1.78;  $Fc['15']['24']['0.05']= 2.11;   $Fc['15']['24']['0.01']= 2.89;
        $Fc['15']['25']=[];  $Fc['15']['25']['0.1']= 1.77;  $Fc['15']['25']['0.05']= 2.09;   $Fc['15']['25']['0.01']= 2.85;
        $Fc['15']['26']=[];  $Fc['15']['26']['0.1']= 1.76;  $Fc['15']['26']['0.05']= 2.07;   $Fc['15']['26']['0.01']= 2.82;
        $Fc['15']['27']=[];  $Fc['15']['27']['0.1']= 1.75;  $Fc['15']['27']['0.05']= 2.06;   $Fc['15']['27']['0.01']= 2.78;
        $Fc['15']['28']=[];  $Fc['15']['28']['0.1']= 1.74;  $Fc['15']['28']['0.05']= 2.04;   $Fc['15']['28']['0.01']= 2.75;
        $Fc['15']['29']=[];  $Fc['15']['29']['0.1']= 1.73;  $Fc['15']['29']['0.05']= 2.03;   $Fc['15']['29']['0.01']= 2.73;
        $Fc['15']['30']=[];  $Fc['15']['30']['0.1']= 1.72;  $Fc['15']['30']['0.05']= 2.01;   $Fc['15']['30']['0.01']= 2.7;
        $Fc['15']['40']=[];  $Fc['15']['40']['0.1']= 1.66;  $Fc['15']['40']['0.05']= 1.92;   $Fc['15']['40']['0.01']= 2.52;
        $Fc['15']['50']=[];  $Fc['15']['50']['0.1']= 1.63;  $Fc['15']['50']['0.05']= 1.87;   $Fc['15']['50']['0.01']= 2.42;
        $Fc['15']['60']=[];  $Fc['15']['60']['0.1']= 1.6;   $Fc['15']['60']['0.05']= 1.84;   $Fc['15']['60']['0.01']= 2.35;
        $Fc['15']['100']=[]; $Fc['15']['100']['0.1']= 1.56; $Fc['15']['100']['0.05']= 1.77;  $Fc['15']['100']['0.01']= 2.22;
        $Fc['15']['200']=[]; $Fc['15']['200']['0.1']= 1.52; $Fc['15']['200']['0.05']= 1.72;  $Fc['15']['200']['0.01']= 2.13;
        $Fc['15']['1000']=[];$Fc['15']['1000']['0.1']= 1.49;$Fc['15']['1000']['0.05']= 1.68; $Fc['15']['1000']['0.01']= 2.06;

        $Fc['20']=[];
        // v1 = 20
        $Fc['20']['1']=[];   $Fc['20']['1']['0.1']= 61.7;   $Fc['20']['1']['0.05']= 248;     $Fc['20']['1']['0.01']= 6208.7;
        $Fc['20']['2']=[];   $Fc['20']['2']['0.1']= 9.44;   $Fc['20']['2']['0.05']= 19.45;   $Fc['20']['2']['0.01']= 99.45;
        $Fc['20']['3']=[];   $Fc['20']['3']['0.1']= 5.18;   $Fc['20']['3']['0.05']= 8.66;    $Fc['20']['3']['0.01']= 26.69;
        $Fc['20']['4']=[];   $Fc['20']['4']['0.1']= 3.84;   $Fc['20']['4']['0.05']= 5.8;     $Fc['20']['4']['0.01']= 14.02;
        $Fc['20']['5']=[];   $Fc['20']['5']['0.1']= 3.21;   $Fc['20']['5']['0.05']= 4.56;    $Fc['20']['5']['0.01']= 9.55;
        $Fc['20']['6']=[];   $Fc['20']['6']['0.1']= 2.84;   $Fc['20']['6']['0.05']= 3.87;    $Fc['20']['6']['0.01']= 7.4;
        $Fc['20']['7']=[];   $Fc['20']['7']['0.1']= 2.59;   $Fc['20']['7']['0.05']= 3.44;    $Fc['20']['7']['0.01']= 6.16;
        $Fc['20']['8']=[];   $Fc['20']['8']['0.1']= 2.42;   $Fc['20']['8']['0.05']= 3.15;    $Fc['20']['8']['0.01']= 5.36;
        $Fc['20']['9']=[];   $Fc['20']['9']['0.1']= 2.3;    $Fc['20']['9']['0.05']= 2.94;    $Fc['20']['9']['0.01']= 4.81;
        $Fc['20']['10']=[];  $Fc['20']['10']['0.1']= 2.2;   $Fc['20']['10']['0.05']= 2.77;   $Fc['20']['10']['0.01']= 4.41;
        $Fc['20']['11']=[];  $Fc['20']['11']['0.1']= 2.12;  $Fc['20']['11']['0.05']= 2.65;   $Fc['20']['11']['0.01']= 4.1;
        $Fc['20']['12']=[];  $Fc['20']['12']['0.1']= 2.06;  $Fc['20']['12']['0.05']= 2.54;   $Fc['20']['12']['0.01']= 3.86;
        $Fc['20']['13']=[];  $Fc['20']['13']['0.1']= 2.01;  $Fc['20']['13']['0.05']= 2.46;   $Fc['20']['13']['0.01']= 3.66;
        $Fc['20']['14']=[];  $Fc['20']['14']['0.1']= 1.96;  $Fc['20']['14']['0.05']= 2.39;   $Fc['20']['14']['0.01']= 3.51;
        $Fc['20']['15']=[];  $Fc['20']['15']['0.1']= 1.92;  $Fc['20']['15']['0.05']= 2.33;   $Fc['20']['15']['0.01']= 3.37;
        $Fc['20']['16']=[];  $Fc['20']['16']['0.1']= 1.89;  $Fc['20']['16']['0.05']= 2.28;   $Fc['20']['16']['0.01']= 3.26;
        $Fc['20']['17']=[];  $Fc['20']['17']['0.1']= 1.86;  $Fc['20']['17']['0.05']= 2.23;   $Fc['20']['17']['0.01']= 3.16;
        $Fc['20']['18']=[];  $Fc['20']['18']['0.1']= 1.84;  $Fc['20']['18']['0.05']= 2.19;   $Fc['20']['18']['0.01']= 3.08;
        $Fc['20']['19']=[];  $Fc['20']['19']['0.1']= 1.81;  $Fc['20']['19']['0.05']= 2.16;   $Fc['20']['19']['0.01']= 3;
        $Fc['20']['20']=[];  $Fc['20']['20']['0.1']= 1.79;  $Fc['20']['20']['0.05']= 2.12;   $Fc['20']['20']['0.01']= 2.94;
        $Fc['20']['21']=[];  $Fc['20']['21']['0.1']= 1.78;  $Fc['20']['21']['0.05']= 2.1;    $Fc['20']['21']['0.01']= 2.88;
        $Fc['20']['22']=[];  $Fc['20']['22']['0.1']= 1.76;  $Fc['20']['22']['0.05']= 2.07;   $Fc['20']['22']['0.01']= 2.83;
        $Fc['20']['23']=[];  $Fc['20']['23']['0.1']= 1.74;  $Fc['20']['23']['0.05']= 2.05;   $Fc['20']['23']['0.01']= 2.78;
        $Fc['20']['24']=[];  $Fc['20']['24']['0.1']= 1.73;  $Fc['20']['24']['0.05']= 2.03;   $Fc['20']['24']['0.01']= 2.74;
        $Fc['20']['25']=[];  $Fc['20']['25']['0.1']= 1.72;  $Fc['20']['25']['0.05']= 2.01;   $Fc['20']['25']['0.01']= 2.7;
        $Fc['20']['26']=[];  $Fc['20']['26']['0.1']= 1.71;  $Fc['20']['26']['0.05']= 1.99;   $Fc['20']['26']['0.01']= 2.66;
        $Fc['20']['27']=[];  $Fc['20']['27']['0.1']= 1.7;   $Fc['20']['27']['0.05']= 1.97;   $Fc['20']['27']['0.01']= 2.63;
        $Fc['20']['28']=[];  $Fc['20']['28']['0.1']= 1.69;  $Fc['20']['28']['0.05']= 1.96;   $Fc['20']['28']['0.01']= 2.6;
        $Fc['20']['29']=[];  $Fc['20']['29']['0.1']= 1.68;  $Fc['20']['29']['0.05']= 1.94;   $Fc['20']['29']['0.01']= 2.57;
        $Fc['20']['30']=[];  $Fc['20']['30']['0.1']= 1.67;  $Fc['20']['30']['0.05']= 1.93;   $Fc['20']['30']['0.01']= 2.55;
        $Fc['20']['40']=[];  $Fc['20']['40']['0.1']= 1.61;  $Fc['20']['40']['0.05']= 1.84;   $Fc['20']['40']['0.01']= 2.37;
        $Fc['20']['50']=[];  $Fc['20']['50']['0.1']= 1.57;  $Fc['20']['50']['0.05']= 1.78;   $Fc['20']['50']['0.01']= 2.27;
        $Fc['20']['60']=[];  $Fc['20']['60']['0.1']= 1.54;  $Fc['20']['60']['0.05']= 1.75;   $Fc['20']['60']['0.01']= 2.2;
        $Fc['20']['100']=[]; $Fc['20']['100']['0.1']= 1.49; $Fc['20']['100']['0.05']= 1.68;  $Fc['20']['100']['0.01']= 2.07;
        $Fc['20']['200']=[]; $Fc['20']['200']['0.1']= 1.46; $Fc['20']['200']['0.05']= 1.62;  $Fc['20']['200']['0.01']= 1.97;
        $Fc['20']['1000']=[];$Fc['20']['1000']['0.1']= 1.43;$Fc['20']['1000']['0.05']= 1.58; $Fc['20']['1000']['0.01']= 1.9;

        $Fc['25']=[];
        // v1 = 25
        $Fc['25']['1']=[];   $Fc['25']['1']['0.1']= 62.1;   $Fc['25']['1']['0.05']= 249.3;   $Fc['25']['1']['0.01']= 6239.8;
        $Fc['25']['2']=[];   $Fc['25']['2']['0.1']= 9.45;   $Fc['25']['2']['0.05']= 19.46;   $Fc['25']['2']['0.01']= 99.46;
        $Fc['25']['3']=[];   $Fc['25']['3']['0.1']= 5.17;   $Fc['25']['3']['0.05']= 8.63;    $Fc['25']['3']['0.01']= 26.58;
        $Fc['25']['4']=[];   $Fc['25']['4']['0.1']= 3.83;   $Fc['25']['4']['0.05']= 5.77;    $Fc['25']['4']['0.01']= 13.91;
        $Fc['25']['5']=[];   $Fc['25']['5']['0.1']= 3.19;   $Fc['25']['5']['0.05']= 4.52;    $Fc['25']['5']['0.01']= 9.45;
        $Fc['25']['6']=[];   $Fc['25']['6']['0.1']= 2.81;   $Fc['25']['6']['0.05']= 3.83;    $Fc['25']['6']['0.01']= 7.3;
        $Fc['25']['7']=[];   $Fc['25']['7']['0.1']= 2.57;   $Fc['25']['7']['0.05']= 3.4;     $Fc['25']['7']['0.01']= 6.06;
        $Fc['25']['8']=[];   $Fc['25']['8']['0.1']= 2.4;    $Fc['25']['8']['0.05']= 3.11;    $Fc['25']['8']['0.01']= 5.26;
        $Fc['25']['9']=[];   $Fc['25']['9']['0.1']= 2.27;   $Fc['25']['9']['0.05']= 2.89;    $Fc['25']['9']['0.01']= 4.71;
        $Fc['25']['10']=[];  $Fc['25']['10']['0.1']= 2.17;  $Fc['25']['10']['0.05']= 2.73;   $Fc['25']['10']['0.01']= 4.31;
        $Fc['25']['11']=[];  $Fc['25']['11']['0.1']= 2.1;   $Fc['25']['11']['0.05']= 2.6;    $Fc['25']['11']['0.01']= 4.01;
        $Fc['25']['12']=[];  $Fc['25']['12']['0.1']= 2.03;  $Fc['25']['12']['0.05']= 2.5;    $Fc['25']['12']['0.01']= 3.76;
        $Fc['25']['13']=[];  $Fc['25']['13']['0.1']= 1.98;  $Fc['25']['13']['0.05']= 2.41;   $Fc['25']['13']['0.01']= 3.57;
        $Fc['25']['14']=[];  $Fc['25']['14']['0.1']= 1.93;  $Fc['25']['14']['0.05']= 2.34;   $Fc['25']['14']['0.01']= 3.41;
        $Fc['25']['15']=[];  $Fc['25']['15']['0.1']= 1.89;  $Fc['25']['15']['0.05']= 2.28;   $Fc['25']['15']['0.01']= 3.28;
        $Fc['25']['16']=[];  $Fc['25']['16']['0.1']= 1.86;  $Fc['25']['16']['0.05']= 2.23;   $Fc['25']['16']['0.01']= 3.16;
        $Fc['25']['17']=[];  $Fc['25']['17']['0.1']= 1.83;  $Fc['25']['17']['0.05']= 2.18;   $Fc['25']['17']['0.01']= 3.07;
        $Fc['25']['18']=[];  $Fc['25']['18']['0.1']= 1.8;   $Fc['25']['18']['0.05']= 2.14;   $Fc['25']['18']['0.01']= 2.98;
        $Fc['25']['19']=[];  $Fc['25']['19']['0.1']= 1.78;  $Fc['25']['19']['0.05']= 2.11;   $Fc['25']['19']['0.01']= 2.91;
        $Fc['25']['20']=[];  $Fc['25']['20']['0.1']= 1.76;  $Fc['25']['20']['0.05']= 2.07;   $Fc['25']['20']['0.01']= 2.84;
        $Fc['25']['21']=[];  $Fc['25']['21']['0.1']= 1.74;  $Fc['25']['21']['0.05']= 2.05;   $Fc['25']['21']['0.01']= 2.78;
        $Fc['25']['22']=[];  $Fc['25']['22']['0.1']= 1.73;  $Fc['25']['22']['0.05']= 2.02;   $Fc['25']['22']['0.01']= 2.73;
        $Fc['25']['23']=[];  $Fc['25']['23']['0.1']= 1.71;  $Fc['25']['23']['0.05']= 2;      $Fc['25']['23']['0.01']= 2.69;
        $Fc['25']['24']=[];  $Fc['25']['24']['0.1']= 1.7;   $Fc['25']['24']['0.05']= 1.98;   $Fc['25']['24']['0.01']= 2.64;
        $Fc['25']['25']=[];  $Fc['25']['25']['0.1']= 1.68;  $Fc['25']['25']['0.05']= 1.96;   $Fc['25']['25']['0.01']= 2.6;
        $Fc['25']['26']=[];  $Fc['25']['26']['0.1']= 1.67;  $Fc['25']['26']['0.05']= 1.94;   $Fc['25']['26']['0.01']= 2.57;
        $Fc['25']['27']=[];  $Fc['25']['27']['0.1']= 1.66;  $Fc['25']['27']['0.05']= 1.92;   $Fc['25']['27']['0.01']= 2.54;
        $Fc['25']['28']=[];  $Fc['25']['28']['0.1']= 1.65;  $Fc['25']['28']['0.05']= 1.91;   $Fc['25']['28']['0.01']= 2.51;
        $Fc['25']['29']=[];  $Fc['25']['29']['0.1']= 1.64;  $Fc['25']['29']['0.05']= 1.89;   $Fc['25']['29']['0.01']= 2.48;
        $Fc['25']['30']=[];  $Fc['25']['30']['0.1']= 1.63;  $Fc['25']['30']['0.05']= 1.88;   $Fc['25']['30']['0.01']= 2.45;
        $Fc['25']['40']=[];  $Fc['25']['40']['0.1']= 1.57;  $Fc['25']['40']['0.05']= 1.78;   $Fc['25']['40']['0.01']= 2.27;
        $Fc['25']['50']=[];  $Fc['25']['50']['0.1']= 1.53;  $Fc['25']['50']['0.05']= 1.73;   $Fc['25']['50']['0.01']= 2.17;
        $Fc['25']['60']=[];  $Fc['25']['60']['0.1']= 1.5;   $Fc['25']['60']['0.05']= 1.69;   $Fc['25']['60']['0.01']= 2.1;
        $Fc['25']['100']=[]; $Fc['25']['100']['0.1']= 1.45; $Fc['25']['100']['0.05']= 1.62;  $Fc['25']['100']['0.01']= 1.97;
        $Fc['25']['200']=[]; $Fc['25']['200']['0.1']= 1.41; $Fc['25']['200']['0.05']= 1.56;  $Fc['25']['200']['0.01']= 1.87;
        $Fc['25']['1000']=[];$Fc['25']['1000']['0.1']= 1.38;$Fc['25']['1000']['0.05']= 1.52; $Fc['25']['1000']['0.01']= 1.79;

        $Fc['30']=[];
        // v1 = 30
        $Fc['30']['1']=[];   $Fc['30']['1']['0.1']= 62.3;   $Fc['30']['1']['0.05']= 250.1;   $Fc['30']['1']['0.01']= 6260.6;
        $Fc['30']['2']=[];   $Fc['30']['2']['0.1']= 9.46;   $Fc['30']['2']['0.05']= 19.46;   $Fc['30']['2']['0.01']= 99.47;
        $Fc['30']['3']=[];   $Fc['30']['3']['0.1']= 5.17;   $Fc['30']['3']['0.05']= 8.62;    $Fc['30']['3']['0.01']= 26.5;
        $Fc['30']['4']=[];   $Fc['30']['4']['0.1']= 3.82;   $Fc['30']['4']['0.05']= 5.75;    $Fc['30']['4']['0.01']= 13.84;
        $Fc['30']['5']=[];   $Fc['30']['5']['0.1']= 3.17;   $Fc['30']['5']['0.05']= 4.5;     $Fc['30']['5']['0.01']= 9.38;
        $Fc['30']['6']=[];   $Fc['30']['6']['0.1']= 2.8;    $Fc['30']['6']['0.05']= 3.81;    $Fc['30']['6']['0.01']= 7.23;
        $Fc['30']['7']=[];   $Fc['30']['7']['0.1']= 2.56;   $Fc['30']['7']['0.05']= 3.38;    $Fc['30']['7']['0.01']= 5.99;
        $Fc['30']['8']=[];   $Fc['30']['8']['0.1']= 2.38;   $Fc['30']['8']['0.05']= 3.08;    $Fc['30']['8']['0.01']= 5.2;
        $Fc['30']['9']=[];   $Fc['30']['9']['0.1']= 2.25;   $Fc['30']['9']['0.05']= 2.86;    $Fc['30']['9']['0.01']= 4.65;
        $Fc['30']['10']=[];  $Fc['30']['10']['0.1']= 2.16;  $Fc['30']['10']['0.05']= 2.7;    $Fc['30']['10']['0.01']= 4.25;
        $Fc['30']['11']=[];  $Fc['30']['11']['0.1']= 2.08;  $Fc['30']['11']['0.05']= 2.57;   $Fc['30']['11']['0.01']= 3.94;
        $Fc['30']['12']=[];  $Fc['30']['12']['0.1']= 2.01;  $Fc['30']['12']['0.05']= 2.47;   $Fc['30']['12']['0.01']= 3.7;
        $Fc['30']['13']=[];  $Fc['30']['13']['0.1']= 1.96;  $Fc['30']['13']['0.05']= 2.38;   $Fc['30']['13']['0.01']= 3.51;
        $Fc['30']['14']=[];  $Fc['30']['14']['0.1']= 1.91;  $Fc['30']['14']['0.05']= 2.31;   $Fc['30']['14']['0.01']= 3.35;
        $Fc['30']['15']=[];  $Fc['30']['15']['0.1']= 1.87;  $Fc['30']['15']['0.05']= 2.25;   $Fc['30']['15']['0.01']= 3.21;
        $Fc['30']['16']=[];  $Fc['30']['16']['0.1']= 1.84;  $Fc['30']['16']['0.05']= 2.19;   $Fc['30']['16']['0.01']= 3.1;
        $Fc['30']['17']=[];  $Fc['30']['17']['0.1']= 1.81;  $Fc['30']['17']['0.05']= 2.15;   $Fc['30']['17']['0.01']= 3;
        $Fc['30']['18']=[];  $Fc['30']['18']['0.1']= 1.78;  $Fc['30']['18']['0.05']= 2.11;   $Fc['30']['18']['0.01']= 2.92;
        $Fc['30']['19']=[];  $Fc['30']['19']['0.1']= 1.76;  $Fc['30']['19']['0.05']= 2.07;   $Fc['30']['19']['0.01']= 2.84;
        $Fc['30']['20']=[];  $Fc['30']['20']['0.1']= 1.74;  $Fc['30']['20']['0.05']= 2.04;   $Fc['30']['20']['0.01']= 2.78;
        $Fc['30']['21']=[];  $Fc['30']['21']['0.1']= 1.72;  $Fc['30']['21']['0.05']= 2.01;   $Fc['30']['21']['0.01']= 2.72;
        $Fc['30']['22']=[];  $Fc['30']['22']['0.1']= 1.7;   $Fc['30']['22']['0.05']= 1.98;   $Fc['30']['22']['0.01']= 2.67;
        $Fc['30']['23']=[];  $Fc['30']['23']['0.1']= 1.69;  $Fc['30']['23']['0.05']= 1.96;   $Fc['30']['23']['0.01']= 2.62;
        $Fc['30']['24']=[];  $Fc['30']['24']['0.1']= 1.67;  $Fc['30']['24']['0.05']= 1.94;   $Fc['30']['24']['0.01']= 2.58;
        $Fc['30']['25']=[];  $Fc['30']['25']['0.1']= 1.66;  $Fc['30']['25']['0.05']= 1.92;   $Fc['30']['25']['0.01']= 2.54;
        $Fc['30']['26']=[];  $Fc['30']['26']['0.1']= 1.65;  $Fc['30']['26']['0.05']= 1.9;    $Fc['30']['26']['0.01']= 2.5;
        $Fc['30']['27']=[];  $Fc['30']['27']['0.1']= 1.64;  $Fc['30']['27']['0.05']= 1.88;   $Fc['30']['27']['0.01']= 2.47;
        $Fc['30']['28']=[];  $Fc['30']['28']['0.1']= 1.63;  $Fc['30']['28']['0.05']= 1.87;   $Fc['30']['28']['0.01']= 2.44;
        $Fc['30']['29']=[];  $Fc['30']['29']['0.1']= 1.62;  $Fc['30']['29']['0.05']= 1.85;   $Fc['30']['29']['0.01']= 2.41;
        $Fc['30']['30']=[];  $Fc['30']['30']['0.1']= 1.61;  $Fc['30']['30']['0.05']= 1.84;   $Fc['30']['30']['0.01']= 2.39;
        $Fc['30']['40']=[];  $Fc['30']['40']['0.1']= 1.54;  $Fc['30']['40']['0.05']= 1.74;   $Fc['30']['40']['0.01']= 2.2;
        $Fc['30']['50']=[];  $Fc['30']['50']['0.1']= 1.5;   $Fc['30']['50']['0.05']= 1.69;   $Fc['30']['50']['0.01']= 2.1;
        $Fc['30']['60']=[];  $Fc['30']['60']['0.1']= 1.48;  $Fc['30']['60']['0.05']= 1.65;   $Fc['30']['60']['0.01']= 2.03;
        $Fc['30']['100']=[]; $Fc['30']['100']['0.1']= 1.42; $Fc['30']['100']['0.05']= 1.57;  $Fc['30']['100']['0.01']= 1.89;
        $Fc['30']['200']=[]; $Fc['30']['200']['0.1']= 1.38; $Fc['30']['200']['0.05']= 1.52;  $Fc['30']['200']['0.01']= 1.79;
        $Fc['30']['1000']=[];$Fc['30']['1000']['0.1']= 1.35;$Fc['30']['1000']['0.05']= 1.47; $Fc['30']['1000']['0.01']= 1.72;

        $Fc['60']=[];
        // v1 = 60
        $Fc['60']['1']=[];   $Fc['60']['1']['0.1']= 62.8;   $Fc['60']['1']['0.05']= 252.2;   $Fc['60']['1']['0.01']= 6313;
        $Fc['60']['2']=[];   $Fc['60']['2']['0.1']= 9.47;   $Fc['60']['2']['0.05']= 19.48;   $Fc['60']['2']['0.01']= 99.48;
        $Fc['60']['3']=[];   $Fc['60']['3']['0.1']= 5.15;   $Fc['60']['3']['0.05']= 8.57;    $Fc['60']['3']['0.01']= 26.32;
        $Fc['60']['4']=[];   $Fc['60']['4']['0.1']= 3.79;   $Fc['60']['4']['0.05']= 5.69;    $Fc['60']['4']['0.01']= 13.65;
        $Fc['60']['5']=[];   $Fc['60']['5']['0.1']= 3.14;   $Fc['60']['5']['0.05']= 4.43;    $Fc['60']['5']['0.01']= 9.2;
        $Fc['60']['6']=[];   $Fc['60']['6']['0.1']= 2.76;   $Fc['60']['6']['0.05']= 3.74;    $Fc['60']['6']['0.01']= 7.06;
        $Fc['60']['7']=[];   $Fc['60']['7']['0.1']= 2.51;   $Fc['60']['7']['0.05']= 3.3;     $Fc['60']['7']['0.01']= 5.82;
        $Fc['60']['8']=[];   $Fc['60']['8']['0.1']= 2.34;   $Fc['60']['8']['0.05']= 3.01;    $Fc['60']['8']['0.01']= 5.03;
        $Fc['60']['9']=[];   $Fc['60']['9']['0.1']= 2.21;   $Fc['60']['9']['0.05']= 2.79;    $Fc['60']['9']['0.01']= 4.48;
        $Fc['60']['10']=[];  $Fc['60']['10']['0.1']= 2.11;  $Fc['60']['10']['0.05']= 2.62;   $Fc['60']['10']['0.01']= 4.08;
        $Fc['60']['11']=[];  $Fc['60']['11']['0.1']= 2.03;  $Fc['60']['11']['0.05']= 2.49;   $Fc['60']['11']['0.01']= 3.78;
        $Fc['60']['12']=[];  $Fc['60']['12']['0.1']= 1.96;  $Fc['60']['12']['0.05']= 2.38;   $Fc['60']['12']['0.01']= 3.54;
        $Fc['60']['13']=[];  $Fc['60']['13']['0.1']= 1.9;   $Fc['60']['13']['0.05']= 2.3;    $Fc['60']['13']['0.01']= 3.34;
        $Fc['60']['14']=[];  $Fc['60']['14']['0.1']= 1.86;  $Fc['60']['14']['0.05']= 2.22;   $Fc['60']['14']['0.01']= 3.18;
        $Fc['60']['15']=[];  $Fc['60']['15']['0.1']= 1.82;  $Fc['60']['15']['0.05']= 2.16;   $Fc['60']['15']['0.01']= 3.05;
        $Fc['60']['16']=[];  $Fc['60']['16']['0.1']= 1.78;  $Fc['60']['16']['0.05']= 2.11;   $Fc['60']['16']['0.01']= 2.93;
        $Fc['60']['17']=[];  $Fc['60']['17']['0.1']= 1.75;  $Fc['60']['17']['0.05']= 2.06;   $Fc['60']['17']['0.01']= 2.83;
        $Fc['60']['18']=[];  $Fc['60']['18']['0.1']= 1.72;  $Fc['60']['18']['0.05']= 2.02;   $Fc['60']['18']['0.01']= 2.75;
        $Fc['60']['19']=[];  $Fc['60']['19']['0.1']= 1.7;   $Fc['60']['19']['0.05']= 1.98;   $Fc['60']['19']['0.01']= 2.67;
        $Fc['60']['20']=[];  $Fc['60']['20']['0.1']= 1.68;  $Fc['60']['20']['0.05']= 1.95;   $Fc['60']['20']['0.01']= 2.61;
        $Fc['60']['21']=[];  $Fc['60']['21']['0.1']= 1.66;  $Fc['60']['21']['0.05']= 1.92;   $Fc['60']['21']['0.01']= 2.55;
        $Fc['60']['22']=[];  $Fc['60']['22']['0.1']= 1.64;  $Fc['60']['22']['0.05']= 1.89;   $Fc['60']['22']['0.01']= 2.5;
        $Fc['60']['23']=[];  $Fc['60']['23']['0.1']= 1.62;  $Fc['60']['23']['0.05']= 1.86;   $Fc['60']['23']['0.01']= 2.45;
        $Fc['60']['24']=[];  $Fc['60']['24']['0.1']= 1.61;  $Fc['60']['24']['0.05']= 1.84;   $Fc['60']['24']['0.01']= 2.4;
        $Fc['60']['25']=[];  $Fc['60']['25']['0.1']= 1.59;  $Fc['60']['25']['0.05']= 1.82;   $Fc['60']['25']['0.01']= 2.36;
        $Fc['60']['26']=[];  $Fc['60']['26']['0.1']= 1.58;  $Fc['60']['26']['0.05']= 1.8;    $Fc['60']['26']['0.01']= 2.33;
        $Fc['60']['27']=[];  $Fc['60']['27']['0.1']= 1.57;  $Fc['60']['27']['0.05']= 1.79;   $Fc['60']['27']['0.01']= 2.29;
        $Fc['60']['28']=[];  $Fc['60']['28']['0.1']= 1.56;  $Fc['60']['28']['0.05']= 1.77;   $Fc['60']['28']['0.01']= 2.26;
        $Fc['60']['29']=[];  $Fc['60']['29']['0.1']= 1.55;  $Fc['60']['29']['0.05']= 1.75;   $Fc['60']['29']['0.01']= 2.23;
        $Fc['60']['30']=[];  $Fc['60']['30']['0.1']= 1.54;  $Fc['60']['30']['0.05']= 1.74;   $Fc['60']['30']['0.01']= 2.21;
        $Fc['60']['40']=[];  $Fc['60']['40']['0.1']= 1.47;  $Fc['60']['40']['0.05']= 1.64;   $Fc['60']['40']['0.01']= 2.02;
        $Fc['60']['50']=[];  $Fc['60']['50']['0.1']= 1.42;  $Fc['60']['50']['0.05']= 1.58;   $Fc['60']['50']['0.01']= 1.91;
        $Fc['60']['60']=[];  $Fc['60']['60']['0.1']= 1.4;   $Fc['60']['60']['0.05']= 1.53;   $Fc['60']['60']['0.01']= 1.84;
        $Fc['60']['100']=[]; $Fc['60']['100']['0.1']= 1.34; $Fc['60']['100']['0.05']= 1.45;  $Fc['60']['100']['0.01']= 1.69;
        $Fc['60']['200']=[]; $Fc['60']['200']['0.1']= 1.29; $Fc['60']['200']['0.05']= 1.39;  $Fc['60']['200']['0.01']= 1.58;
        $Fc['60']['1000']=[];$Fc['60']['1000']['0.1']= 1.25;$Fc['60']['1000']['0.05']= 1.33; $Fc['60']['1000']['0.01']= 1.5;

        $Fc['120']=[];
        // v1 = 120
        $Fc['120']['1']=[];   $Fc['120']['1']['0.1']= 63.1;   $Fc['120']['1']['0.05']= 253.3;   $Fc['120']['1']['0.01']= 6339.4;
        $Fc['120']['2']=[];   $Fc['120']['2']['0.1']= 9.48;   $Fc['120']['2']['0.05']= 19.49;   $Fc['120']['2']['0.01']= 99.49;
        $Fc['120']['3']=[];   $Fc['120']['3']['0.1']= 5.14;   $Fc['120']['3']['0.05']= 8.55;    $Fc['120']['3']['0.01']= 26.22;
        $Fc['120']['4']=[];   $Fc['120']['4']['0.1']= 3.78;   $Fc['120']['4']['0.05']= 5.66;    $Fc['120']['4']['0.01']= 13.56;
        $Fc['120']['5']=[];   $Fc['120']['5']['0.1']= 3.12;   $Fc['120']['5']['0.05']= 4.4;     $Fc['120']['5']['0.01']= 9.11;
        $Fc['120']['6']=[];   $Fc['120']['6']['0.1']= 2.74;   $Fc['120']['6']['0.05']= 3.7;     $Fc['120']['6']['0.01']= 6.97;
        $Fc['120']['7']=[];   $Fc['120']['7']['0.1']= 2.49;   $Fc['120']['7']['0.05']= 3.27;    $Fc['120']['7']['0.01']= 5.74;
        $Fc['120']['8']=[];   $Fc['120']['8']['0.1']= 2.32;   $Fc['120']['8']['0.05']= 2.97;    $Fc['120']['8']['0.01']= 4.95;
        $Fc['120']['9']=[];   $Fc['120']['9']['0.1']= 2.18;   $Fc['120']['9']['0.05']= 2.75;    $Fc['120']['9']['0.01']= 4.4;
        $Fc['120']['10']=[];  $Fc['120']['10']['0.1']= 2.08;  $Fc['120']['10']['0.05']= 2.58;   $Fc['120']['10']['0.01']= 4;
        $Fc['120']['11']=[];  $Fc['120']['11']['0.1']= 2;     $Fc['120']['11']['0.05']= 2.45;   $Fc['120']['11']['0.01']= 3.69;
        $Fc['120']['12']=[];  $Fc['120']['12']['0.1']= 1.93;  $Fc['120']['12']['0.05']= 2.34;   $Fc['120']['12']['0.01']= 3.45;
        $Fc['120']['13']=[];  $Fc['120']['13']['0.1']= 1.88;  $Fc['120']['13']['0.05']= 2.25;   $Fc['120']['13']['0.01']= 3.25;
        $Fc['120']['14']=[];  $Fc['120']['14']['0.1']= 1.83;  $Fc['120']['14']['0.05']= 2.18;   $Fc['120']['14']['0.01']= 3.09;
        $Fc['120']['15']=[];  $Fc['120']['15']['0.1']= 1.79;  $Fc['120']['15']['0.05']= 2.11;   $Fc['120']['15']['0.01']= 2.96;
        $Fc['120']['16']=[];  $Fc['120']['16']['0.1']= 1.75;  $Fc['120']['16']['0.05']= 2.06;   $Fc['120']['16']['0.01']= 2.84;
        $Fc['120']['17']=[];  $Fc['120']['17']['0.1']= 1.72;  $Fc['120']['17']['0.05']= 2.01;   $Fc['120']['17']['0.01']= 2.75;
        $Fc['120']['18']=[];  $Fc['120']['18']['0.1']= 1.69;  $Fc['120']['18']['0.05']= 1.97;   $Fc['120']['18']['0.01']= 2.66;
        $Fc['120']['19']=[];  $Fc['120']['19']['0.1']= 1.67;  $Fc['120']['19']['0.05']= 1.93;   $Fc['120']['19']['0.01']= 2.58;
        $Fc['120']['20']=[];  $Fc['120']['20']['0.1']= 1.64;  $Fc['120']['20']['0.05']= 1.9;    $Fc['120']['20']['0.01']= 2.52;
        $Fc['120']['21']=[];  $Fc['120']['21']['0.1']= 1.62;  $Fc['120']['21']['0.05']= 1.87;   $Fc['120']['21']['0.01']= 2.46;
        $Fc['120']['22']=[];  $Fc['120']['22']['0.1']= 1.6;   $Fc['120']['22']['0.05']= 1.84;   $Fc['120']['22']['0.01']= 2.4;
        $Fc['120']['23']=[];  $Fc['120']['23']['0.1']= 1.59;  $Fc['120']['23']['0.05']= 1.81;   $Fc['120']['23']['0.01']= 2.35;
        $Fc['120']['24']=[];  $Fc['120']['24']['0.1']= 1.57;  $Fc['120']['24']['0.05']= 1.79;   $Fc['120']['24']['0.01']= 2.31;
        $Fc['120']['25']=[];  $Fc['120']['25']['0.1']= 1.56;  $Fc['120']['25']['0.05']= 1.77;   $Fc['120']['25']['0.01']= 2.27;
        $Fc['120']['26']=[];  $Fc['120']['26']['0.1']= 1.54;  $Fc['120']['26']['0.05']= 1.75;   $Fc['120']['26']['0.01']= 2.23;
        $Fc['120']['27']=[];  $Fc['120']['27']['0.1']= 1.53;  $Fc['120']['27']['0.05']= 1.73;   $Fc['120']['27']['0.01']= 2.2;
        $Fc['120']['28']=[];  $Fc['120']['28']['0.1']= 1.52;  $Fc['120']['28']['0.05']= 1.71;   $Fc['120']['28']['0.01']= 2.17;
        $Fc['120']['29']=[];  $Fc['120']['29']['0.1']= 1.51;  $Fc['120']['29']['0.05']= 1.7;    $Fc['120']['29']['0.01']= 2.14;
        $Fc['120']['30']=[];  $Fc['120']['30']['0.1']= 1.5;   $Fc['120']['30']['0.05']= 1.68;   $Fc['120']['30']['0.01']= 2.11;
        $Fc['120']['40']=[];  $Fc['120']['40']['0.1']= 1.42;  $Fc['120']['40']['0.05']= 1.58;   $Fc['120']['40']['0.01']= 1.92;
        $Fc['120']['50']=[];  $Fc['120']['50']['0.1']= 1.38;  $Fc['120']['50']['0.05']= 1.51;   $Fc['120']['50']['0.01']= 1.8;
        $Fc['120']['60']=[];  $Fc['120']['60']['0.1']= 1.35;  $Fc['120']['60']['0.05']= 1.47;   $Fc['120']['60']['0.01']= 1.73;
        $Fc['120']['100']=[]; $Fc['120']['100']['0.1']= 1.28; $Fc['120']['100']['0.05']= 1.38;  $Fc['120']['100']['0.01']= 1.57;
        $Fc['120']['200']=[]; $Fc['120']['200']['0.1']= 1.23; $Fc['120']['200']['0.05']= 1.3;   $Fc['120']['200']['0.01']= 1.45;
        $Fc['120']['1000']=[];$Fc['120']['1000']['0.1']= 1.18;$Fc['120']['1000']['0.05']= 1.24; $Fc['120']['1000']['0.01']= 1.35;

        $Fc['infinity']=[];
        // v1 = infinity
        $Fc['infinity']['1']=[];   $Fc['infinity']['1']['0.1']= 63.3;   $Fc['infinity']['1']['0.05']= 254.3;   $Fc['infinity']['1']['0.01']= 6365.8;
        $Fc['infinity']['2']=[];   $Fc['infinity']['2']['0.1']= 9.49;   $Fc['infinity']['2']['0.05']= 19.5;    $Fc['infinity']['2']['0.01']= 99.5;
        $Fc['infinity']['3']=[];   $Fc['infinity']['3']['0.1']= 5.13;   $Fc['infinity']['3']['0.05']= 8.53;    $Fc['infinity']['3']['0.01']= 26.13;
        $Fc['infinity']['4']=[];   $Fc['infinity']['4']['0.1']= 3.76;   $Fc['infinity']['4']['0.05']= 5.63;    $Fc['infinity']['4']['0.01']= 13.46;
        $Fc['infinity']['5']=[];   $Fc['infinity']['5']['0.1']= 3.1;    $Fc['infinity']['5']['0.05']= 4.36;    $Fc['infinity']['5']['0.01']= 9.02;
        $Fc['infinity']['6']=[];   $Fc['infinity']['6']['0.1']= 2.72;   $Fc['infinity']['6']['0.05']= 3.67;    $Fc['infinity']['6']['0.01']= 6.88;
        $Fc['infinity']['7']=[];   $Fc['infinity']['7']['0.1']= 2.47;   $Fc['infinity']['7']['0.05']= 3.23;    $Fc['infinity']['7']['0.01']= 5.65;
        $Fc['infinity']['8']=[];   $Fc['infinity']['8']['0.1']= 2.29;   $Fc['infinity']['8']['0.05']= 2.93;    $Fc['infinity']['8']['0.01']= 4.86;
        $Fc['infinity']['9']=[];   $Fc['infinity']['9']['0.1']= 2.16;   $Fc['infinity']['9']['0.05']= 2.71;    $Fc['infinity']['9']['0.01']= 4.31;
        $Fc['infinity']['10']=[];  $Fc['infinity']['10']['0.1']= 2.06;  $Fc['infinity']['10']['0.05']= 2.54;   $Fc['infinity']['10']['0.01']= 3.91;
        $Fc['infinity']['11']=[];  $Fc['infinity']['11']['0.1']= 1.97;  $Fc['infinity']['11']['0.05']= 2.4;    $Fc['infinity']['11']['0.01']= 3.6;
        $Fc['infinity']['12']=[];  $Fc['infinity']['12']['0.1']= 1.9;   $Fc['infinity']['12']['0.05']= 2.3;    $Fc['infinity']['12']['0.01']= 3.36;
        $Fc['infinity']['13']=[];  $Fc['infinity']['13']['0.1']= 1.85;  $Fc['infinity']['13']['0.05']= 2.21;   $Fc['infinity']['13']['0.01']= 3.17;
        $Fc['infinity']['14']=[];  $Fc['infinity']['14']['0.1']= 1.8;   $Fc['infinity']['14']['0.05']= 2.13;   $Fc['infinity']['14']['0.01']= 3;
        $Fc['infinity']['15']=[];  $Fc['infinity']['15']['0.1']= 1.76;  $Fc['infinity']['15']['0.05']= 2.07;   $Fc['infinity']['15']['0.01']= 2.87;
        $Fc['infinity']['16']=[];  $Fc['infinity']['16']['0.1']= 1.72;  $Fc['infinity']['16']['0.05']= 2.01;   $Fc['infinity']['16']['0.01']= 2.75;
        $Fc['infinity']['17']=[];  $Fc['infinity']['17']['0.1']= 1.69;  $Fc['infinity']['17']['0.05']= 1.96;   $Fc['infinity']['17']['0.01']= 2.65;
        $Fc['infinity']['18']=[];  $Fc['infinity']['18']['0.1']= 1.66;  $Fc['infinity']['18']['0.05']= 1.92;   $Fc['infinity']['18']['0.01']= 2.57;
        $Fc['infinity']['19']=[];  $Fc['infinity']['19']['0.1']= 1.63;  $Fc['infinity']['19']['0.05']= 1.88;   $Fc['infinity']['19']['0.01']= 2.49;
        $Fc['infinity']['20']=[];  $Fc['infinity']['20']['0.1']= 1.61;  $Fc['infinity']['20']['0.05']= 1.84;   $Fc['infinity']['20']['0.01']= 2.42;
        $Fc['infinity']['21']=[];  $Fc['infinity']['21']['0.1']= 1.59;  $Fc['infinity']['21']['0.05']= 1.81;   $Fc['infinity']['21']['0.01']= 2.36;
        $Fc['infinity']['22']=[];  $Fc['infinity']['22']['0.1']= 1.57;  $Fc['infinity']['22']['0.05']= 1.78;   $Fc['infinity']['22']['0.01']= 2.31;
        $Fc['infinity']['23']=[];  $Fc['infinity']['23']['0.1']= 1.55;  $Fc['infinity']['23']['0.05']= 1.76;   $Fc['infinity']['23']['0.01']= 2.26;
        $Fc['infinity']['24']=[];  $Fc['infinity']['24']['0.1']= 1.53;  $Fc['infinity']['24']['0.05']= 1.73;   $Fc['infinity']['24']['0.01']= 2.21;
        $Fc['infinity']['25']=[];  $Fc['infinity']['25']['0.1']= 1.52;  $Fc['infinity']['25']['0.05']= 1.71;   $Fc['infinity']['25']['0.01']= 2.17;
        $Fc['infinity']['26']=[];  $Fc['infinity']['26']['0.1']= 1.5;   $Fc['infinity']['26']['0.05']= 1.69;   $Fc['infinity']['26']['0.01']= 2.13;
        $Fc['infinity']['27']=[];  $Fc['infinity']['27']['0.1']= 1.49;  $Fc['infinity']['27']['0.05']= 1.67;   $Fc['infinity']['27']['0.01']= 2.1;
        $Fc['infinity']['28']=[];  $Fc['infinity']['28']['0.1']= 1.48;  $Fc['infinity']['28']['0.05']= 1.65;   $Fc['infinity']['28']['0.01']= 2.06;
        $Fc['infinity']['29']=[];  $Fc['infinity']['29']['0.1']= 1.47;  $Fc['infinity']['29']['0.05']= 1.64;   $Fc['infinity']['29']['0.01']= 2.03;
        $Fc['infinity']['30']=[];  $Fc['infinity']['30']['0.1']= 1.46;  $Fc['infinity']['30']['0.05']= 1.62;   $Fc['infinity']['30']['0.01']= 2.01;
        $Fc['infinity']['40']=[];  $Fc['infinity']['40']['0.1']= 1.38;  $Fc['infinity']['40']['0.05']= 1.51;   $Fc['infinity']['40']['0.01']= 1.8;
        $Fc['infinity']['50']=[];  $Fc['infinity']['50']['0.1']= 1.33;  $Fc['infinity']['50']['0.05']= 1.44;   $Fc['infinity']['50']['0.01']= 1.68;
        $Fc['infinity']['60']=[];  $Fc['infinity']['60']['0.1']= 1.29;  $Fc['infinity']['60']['0.05']= 1.39;   $Fc['infinity']['60']['0.01']= 1.6;
        $Fc['infinity']['100']=[]; $Fc['infinity']['100']['0.1']= 1.21; $Fc['infinity']['100']['0.05']= 1.28;  $Fc['infinity']['100']['0.01']= 1.43;
        $Fc['infinity']['200']=[]; $Fc['infinity']['200']['0.1']= 1.14; $Fc['infinity']['200']['0.05']= 1.19;  $Fc['infinity']['200']['0.01']= 1.28;
        $Fc['infinity']['1000']=[];$Fc['infinity']['1000']['0.1']= 1.06;$Fc['infinity']['1000']['0.05']= 1.08; $Fc['infinity']['1000']['0.01']= 1.11;

        $v1_correct = $v1_correct.'';
        $v2_correct = $v2_correct.'';
        $alpha = $alpha.'';

        return $Fc[$v1_correct][$v2_correct][$alpha];

    }
    public static function DW_n($n){
        if($n >= 1 && $n <= 15){return 15;}
        else if($n == 16){return 16;}
        else if($n == 17){return 17;}
        else if($n == 18){return 18;}
        else if($n == 19){return 19;}
        else if($n == 20){return 20;}
        else if($n == 21){return 21;}
        else if($n == 22){return 22;}
        else if($n == 23){return 23;}
        else if($n == 24){return 24;}
        else if($n == 25){return 25;}
        else if($n == 26){return 26;}
        else if($n == 27){return 27;}
        else if($n == 28){return 28;}
        else if($n == 29){return 29;}
        else if($n == 30){return 30;}
        else if($n == 31){return 31;}
        else if($n == 32){return 32;}
        else if($n == 33){return 33;}
        else if($n == 34){return 34;}
        else if($n == 35){return 35;}
        else if($n == 36){return 36;}
        else if($n == 37){return 37;}
        else if($n == 38){return 38;}
        else if($n == 39){return 39;}
        else if($n == 40){return 40;}
        else if($n > 40 && $n < 45){
            return self::get_number_approx($n, 40, 45);
        }
        else if($n >= 45 && $n < 50){
            return self::get_number_approx($n, 45, 50);
        }
        else if($n >= 50 && $n < 55){
            return self::get_number_approx($n, 50, 55);
        }
        else if($n >= 55 && $n < 60){
            return self::get_number_approx($n, 55, 60);
        }
        else if($n >= 60 && $n < 65){
            return self::get_number_approx($n, 60, 65);
        }
        else if($n >= 65 && $n < 70){
            return self::get_number_approx($n, 65, 70);
        }
        else if($n >= 70 && $n < 75){
            return self::get_number_approx($n, 70, 75);
        }
        else if($n >= 75 && $n < 80){
            return self::get_number_approx($n, 75, 80);
        }
        else if($n >= 80 && $n < 85){
            return self::get_number_approx($n, 80, 85);
        }
        else if($n >= 85 && $n < 90){
            return self::get_number_approx($n, 85, 90);
        }
        else if($n >= 90 && $n < 95){
            return self::get_number_approx($n, 90, 95);
        }
        else if($n >= 95 && $n < 100){
            return self::get_number_approx($n, 95, 100);
        }
        else if($n >= 100){return 100;}
    }
    public static function DW_k($k){
        if($k >= 1 && $k <= 2){return 2;}
        else if($k == 3){return 3;}
        else if($k == 4){return 4;}
        else if($k == 5){return 5;}
        else if($k >= 6){return 6;}
    }
    public static function t_v($v){
        if($v == 1){return 1;}
        else if($v == 2){return 2;}
        else if($v == 3){return 3;}
        else if($v == 4){return 4;}
        else if($v == 5){return 5;}
        else if($v == 6){return 6;}
        else if($v == 7){return 7;}
        else if($v == 8){return 8;}
        else if($v == 9){return 9;}
        else if($v == 10){return 10;}
        else if($v == 11){return 11;}
        else if($v == 12){return 12;}
        else if($v == 13){return 13;}
        else if($v == 14){return 14;}
        else if($v == 15){return 15;}
        else if($v == 16){return 16;}
        else if($v == 17){return 17;}
        else if($v == 18){return 18;}
        else if($v == 19){return 19;}
        else if($v == 20){return 20;}
        else if($v == 21){return 21;}
        else if($v == 22){return 22;}
        else if($v == 23){return 23;}
        else if($v == 24){return 24;}
        else if($v == 25){return 25;}
        else if($v > 25 && $v < 30){
            return self::get_number_approx($v, 25, 30);
        }
        else if($v >= 30 && $v < 40){
            return self::get_number_approx($v, 30, 40);
        }
        else if($v >= 40 && $v < 50){
            return self::get_number_approx($v, 40, 50);
        }
        else if($v >= 50 && $v < 60){
            return self::get_number_approx($v, 50, 60);
        }
        else if($v >= 60 && $v < 70){
            return self::get_number_approx($v, 60, 70);
        }
        else if($v >= 70 && $v < 80){
            return self::get_number_approx($v, 70, 80);
        }
        else if($v >= 80 && $v < 90){
            return self::get_number_approx($v, 80, 90);
        }
        else if($v >= 90 && $v < 100){
            return self::get_number_approx($v, 90, 100);
        }
        else if($v == 100){return 100;}
        else if($v > 100){return 'infinity';}
    }
    public static function F_v1($v1){
        if($v1 == 1){return 1;}
        else if($v1 == 2){return 2;}
        else if($v1 == 3){return 3;}
        else if($v1 == 4){return 4;}
        else if($v1 == 5){return 5;}
        else if($v1 == 6){return 6;}
        else if($v1 == 7){return 7;}
        else if($v1 == 8){return 8;}
        else if($v1 == 9){return 9;}
        else if($v1 == 10){return 10;}
        else if($v1 == 11){return 11;}
        else if($v1 == 12){return 12;}
        else if($v1 == 13){return 13;}
        else if($v1 == 14){return 14;}
        else if($v1 == 15){return 15;}
        else if($v1 > 15 && $v1 < 20){
            return self::get_number_approx($v1, 15, 20);
        }
        else if($v1 >= 20 && $v1 < 25){
            return self::get_number_approx($v1, 20, 25);
        }
        else if($v1 >= 25 && $v1 < 30){
            return self::get_number_approx($v1, 25, 30);
        }
        else if($v1 >= 30 && $v1 < 60){
            return self::get_number_approx($v1, 30, 60);
        }
        else if($v1 >= 60 && $v1 < 120){
            return self::get_number_approx($v1, 60, 120);
        }
        else if($v1 == 120){return 120;}
        else if($v1 > 120){return 'infinity';}
    }
    public static function F_v2($v2){
        if($v2 == 1){return 1;}
        else if($v2 == 2){return 2;}
        else if($v2 == 3){return 3;}
        else if($v2 == 4){return 4;}
        else if($v2 == 5){return 5;}
        else if($v2 == 6){return 6;}
        else if($v2 == 7){return 7;}
        else if($v2 == 8){return 8;}
        else if($v2 == 9){return 9;}
        else if($v2 == 10){return 10;}
        else if($v2 == 11){return 11;}
        else if($v2 == 12){return 12;}
        else if($v2 == 13){return 13;}
        else if($v2 == 14){return 14;}
        else if($v2 == 15){return 15;}
        else if($v2 == 16){return 16;}
        else if($v2 == 17){return 17;}
        else if($v2 == 18){return 18;}
        else if($v2 == 19){return 19;}
        else if($v2 == 20){return 20;}
        else if($v2 == 21){return 21;}
        else if($v2 == 22){return 22;}
        else if($v2 == 23){return 23;}
        else if($v2 == 24){return 24;}
        else if($v2 == 25){return 25;}
        else if($v2 == 26){return 26;}
        else if($v2 == 27){return 27;}
        else if($v2 == 28){return 28;}
        else if($v2 == 29){return 29;}
        else if($v2 == 30){return 30;}
        else if($v2 > 30 && $v2 < 40){
            return self::get_number_approx($v2, 30, 40);
        }
        else if($v2 >= 40 && $v2 < 50){
            return self::get_number_approx($v2, 40, 50);
        }
        else if($v2 >= 50 && $v2 < 60){
            return self::get_number_approx($v2, 50, 60);
        }
        else if($v2 >= 60 && $v2 < 100){
            return self::get_number_approx($v2, 60, 100);
        }
        else if($v2 >= 100 && $v2 < 200){
            return self::get_number_approx($v2, 100, 200);
        }
        else if($v2 >= 200 && $v2 < 1000){
            return self::get_number_approx($v2, 200, 1000);
        }
        else if($v2 >= 1000){return 1000;}
    }
    public static function get_number_approx($value, $low_value, $top_value){
        $v_low = 1*$value - 1*$low_value;
        $v_top = 1*$top_value - 1*$value;
        if($v_low < $v_top){
            return $low_value;
        }else if($v_low > $v_top){
            return $top_value;
        }else if($v_low === $v_top){
            return $low_value;
        }
    }

    public static function print_correlation_analisys($data_correlation, $Y, $X, $Head_name){

        $str_table_correlation = '<table class="correlation_table_style">';
        $in_i = 0;
        foreach($data_correlation['R_fin'] as $k=>$v){
            $str_table_correlation = $str_table_correlation.'<tr>';

            if(1*$in_i === 0){
                $str_table_correlation = $str_table_correlation.'<td></td>';
                foreach($Y[array_keys($Y)[0]] as $k_y=>$v_y){
                    $str_table_correlation = $str_table_correlation.'<td class="correlation_td_style">'.$Head_name[$k_y].'</td>';
                }
                foreach($X[array_keys($X)[0]] as $k_x=>$v_x){
                    $str_table_correlation = $str_table_correlation.'<td class="correlation_td_style">'.$Head_name[$k_x].'</td>';
                }
                $str_table_correlation = $str_table_correlation.'</tr><tr>';
            }

            $in_j = 0;
            foreach($data_correlation['R_fin'][$k] as $k2=>$y2){
                $sch_j_name = 0;
                if(1*$in_j === 0){
                    if(1*$in_i === 0){
                        foreach($Y[array_keys($Y)[0]] as $k_y=>$v_y){
                            $str_table_correlation = $str_table_correlation.'<td class="correlation_td_style">'.$Head_name[$k_y].'</td>';
                        }
                    }else{
                        foreach($X[array_keys($X)[0]] as $k_x=>$v_x){
                            $sch_j_name = $sch_j_name + 1;
                            if($sch_j_name === 1*$in_i){
                                $str_table_correlation = $str_table_correlation.'<td class="correlation_td_style">'.$Head_name[$k_x].'</td>';
                            }
                        }
                    }
                }

                $str_table_correlation = $str_table_correlation.'<td class="correlation_td_style">';
                if($data_correlation['R_fin'][$k][$k2] > 0.5){
                    $str_table_correlation = $str_table_correlation.'<span class="correlation_span_top">'. number_format(1*$data_correlation['R_fin'][$k][$k2], 3, '.', '') .'</span>';
                }else if($data_correlation['R_fin'][$k][$k2] < 0.5 && $data_correlation['R_fin'][$k][$k2] > -1*0.5){
                    $str_table_correlation = $str_table_correlation.'<span class="correlation_span_middle">'. number_format(1*$data_correlation['R_fin'][$k][$k2], 3, '.', '') .'</span>';
                }else if($data_correlation['R_fin'][$k][$k2] < -1*0.5){
                    $str_table_correlation = $str_table_correlation.'<span class="correlation_span_bottom">'. number_format(1*$data_correlation['R_fin'][$k][$k2], 3, '.', '') .'</span>';
                }
                $str_table_correlation = $str_table_correlation.'</td>';
                $in_j = 1*$in_j + 1;
            }
            $str_table_correlation = $str_table_correlation.'</tr>';
            $in_i = 1*$in_i + 1;
        }
        $str_table_correlation = $str_table_correlation.'</table>';

        $str_table_correlation = '<div class="name_part">Корреляционная матрица:</div>'.$str_table_correlation;
        $str_table_correlation = '<div class="name_analisys">Корреляционный анализ</div>'.$str_table_correlation;

        $str_table_correlation = $str_table_correlation.'<div class="name_part">Коэффициент детерминации (R<sup>2</sup>): <span class="span_value">'. number_format(1*$data_correlation['R_2'], 3, '.', '') .'%;</div>';
        $str_table_correlation = $str_table_correlation.'<div class="div_comment">Данное значение показывает что совокупность имеющихся факторов (X) объясняет '. number_format($data_correlation['R_2'], 3, '.', '') .'% общей вариации результативного признака (Y), а на долю прочих факторов приходится '. number_format((100 - 1*$data_correlation['R_2']), 3, '.', '') .'% ее вариации. </div>';

        $str_table_correlation = $str_table_correlation.'<div class="name_part">Коэффициент множественной корреляции (r): <span class="span_value">'. number_format($data_correlation['r'], 3, '.', '') .';</div>';
        $str_table_correlation = $str_table_correlation.'<div class="div_comment">Если данный коэффициент близок к единице, то вариация результативного признака (Y) практических полностью объясняется вариацией независимых переменных (X). </div>';

        return $str_table_correlation;

    }

    public static function print_regression_analisys($data_regression, $Y, $Z, $Head_name){
        $result_str_regression['base'] = '<div class="name_analisys">Регрессионный анализ</div>';

        $description_coef_reg = '';
        $consolid_str = '';

        $key_Y = array_keys($Y[array_keys($Y)[0]])[0];

        $key_Z = array_keys($Z[array_keys($Z)[0]]);

        $key_Z_clear_val = [];

        $h = 0;

        foreach($key_Z as $k1=>$v1){
            $cur_coef = $data_regression['Coef_regr'][$v1][array_keys($data_regression['Coef_regr'][$v1])[0]];
            if(isset($Head_name[$v1])){
                $description_coef_reg = $description_coef_reg.'<div>Коэффициент при '.$Head_name[$v1].': <span class="span_bold">'.$cur_coef.'</span></div>';
                $key_Z_clear_val[$v1] = $cur_coef;
            }else{
                $description_coef_reg = $description_coef_reg.'<div>Свободный коэффициент: <span class="span_bold">'.$cur_coef.'</span></div>';
            }
            if($h > 0){
                if($cur_coef >= 0){
                    $consolid_str = $consolid_str.'<span class="span_bold"> + '.number_format(1*$cur_coef, 2, '.', '').'</span>*'.$Head_name[$v1];
                }else{
                    $consolid_str = $consolid_str.'<span class="span_bold"> - '.number_format(-1*$cur_coef, 2, '.', '').'</span>*'.$Head_name[$v1];
                }
            }else if($h === 0){
                if($cur_coef >= 0){
                    if($v1 !== '' && isset($Head_name[$v1])){
                        $consolid_str = $consolid_str.'<span class="span_bold">'.number_format(1*$cur_coef, 2, '.', '').'</span>*'.$Head_name[$v1];
                    }else{
                        $consolid_str = $consolid_str.'<span class="span_bold">'.number_format(1*$cur_coef, 2, '.', '').'</span>';
                    }
                }else{
                    if($v1 !== '' && isset($Head_name[$v1])){
                        $consolid_str = $consolid_str.'<span class="span_bold"> - '.number_format(-1*$cur_coef, 2, '.', '').'</span>*'.$Head_name[$v1];
                    }else{
                        $consolid_str = $consolid_str.'<span class="span_bold"> - '.number_format(-1*$cur_coef, 2, '.', '').'</span>';
                    }
                }
            }
            $h = $h + 1;
        }

        $result_str_regression['base'] = $result_str_regression['base'].'<div class="name_part">Уравнение регрессии: <span class="uravn_regression_color">'.$consolid_str.'</span></div>';
        $result_str_regression['base'] = $result_str_regression['base'].'<div class="description_coef_reg">'.$description_coef_reg.'</div>';

        foreach($key_Z_clear_val as $k1=>$v1){
            if(1*$data_regression['Coef_elast'][$k1] >= 0){
                $result_str_regression['base'] = $result_str_regression['base'].'<div class="name_part">Коэффициент эластичности ('.$Head_name[$k1].'): <span class="span_value">'.number_format(1*$data_regression['Coef_elast'][$k1], 2, '.', '').'</span></div><div class="div_comment">(Данный коэффициент показывает, что увеличение средней величины '.$Head_name[$k1].' на 1% ведет к увеличению '.$Head_name[$key_Y].' на '.number_format(1*$data_regression['Coef_elast'][$k1], 2, '.', '').'%)</div>';
            }else{
                $result_str_regression['base'] = $result_str_regression['base'].'<div class="name_part">Коэффициент эластичности ('.$Head_name[$k1].'): <span class="span_value">'.number_format(1*$data_regression['Coef_elast'][$k1], 2, '.', '').'</span></div><div class="div_comment">(Данный коэффициент показывает, что увеличение средней величины '.$Head_name[$k1].' на 1% ведет к уменьшению '.$Head_name[$key_Y].' на '.number_format(-1*$data_regression['Coef_elast'][$k1], 2, '.', '').'%)</div>';
            }
        }

        $result_str_regression['dispersion_analisys'] = '<div class="name_section">Дисперсионный анализ</div>';

        $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Вариация модельная: <span class="span_value">'.number_format(1*$data_regression['Qm'], 2, '.', '').'</span></div>';
        $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Вариация остаточная: <span class="span_value">'.number_format(1*$data_regression['Qos'], 2, '.', '').'</span></div>';
        $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Вариация общая: <span class="span_value">'.number_format(1*$data_regression['Qob'], 2, '.', '').'</span></div>';

        if(1*$data_regression['F_cur_criteria'] > 1*$data_regression['F_tbl']){
            $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Критерий Фишера F: <span class="span_value_true">'.number_format(1*$data_regression['F_cur_criteria'], 2).'</span></div><div class="div_comment">с вероятностью 95% можно говорить о <span class="span_value_true_comment">значимости в целом построенной модели регрессии</span>, т.к. F > F<sub>0,05</sub>('.$data_regression['v1'].'; '.$data_regression['v2'].'), т.е. '.(number_format(1*$data_regression['F_cur_criteria'], 2)).' > '.$data_regression['F_tbl'].'</div>';
        }else{
            $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Критерий Фишера F: <span class="span_value_false">'.number_format(1*$data_regression['F_cur_criteria'], 2).'</span></div><div class="div_comment">с вероятностью 95% можно говорить о <span class="span_value_false_comment">незначимости в целом построенной модели регрессии</span>, т.к. F <= F<sub>0,05</sub>('.$data_regression['v1'].'; '.$data_regression['v2'].'), т.е. '.(number_format(1*$data_regression['F_cur_criteria'], 2)).' <= '.$data_regression['F_tbl'].'</div>';
        }

        $h = 0;
        foreach($data_regression['t_cur_criteria'] as $k1=>$v1){
            if(isset($Head_name[$k1])){$cur_name_key = $Head_name[$k1];}
            else{$cur_name_key = 'Свободный коэффициент';}
            if(abs($v1) > 1*$data_regression['t_tbl']){
                $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Критерий Стьюдента t'.$h.'('.$cur_name_key.'): <span class="span_value_true">'.number_format(1*$v1, 2).'</span></div><div class="div_comment">при 5%-ном уровне значимости коэффициент регрессии a'.$h.'('.$cur_name_key.') существенно отличен от нуля т.к. |t<sub>'.$h.'('.$cur_name_key.')</sub>| > t<sub>кр.</sub>(0.05; '.$data_regression['v2'].'), т.е. '.number_format(abs(1*$v1), 2).' > '.$data_regression['t_tbl'].'</div>';
            }else{
                $result_str_regression['dispersion_analisys'] = $result_str_regression['dispersion_analisys'].'<div class="name_part">Критерий Стьюдента t'.$h.'('.$cur_name_key.'): <span class="span_value_false">'.number_format(1*$v1, 2).'</span></div><div class="div_comment">при 5%-ном уровне значимости коэффициент регрессии a'.$h.'('.$cur_name_key.') может быть признан <span class="span_value_false_comment">незначимым</span> т.к. |t<sub>'.$h.'('.$cur_name_key.')</sub>| < t<sub>кр.</sub>(0.05; '.$data_regression['v2'].'), т.е. '.number_format(abs(1*$v1), 2).' < '.$data_regression['t_tbl'].', а следовательно, <span class="span_value_false_comment">исключен из уравнения регрессии</span>.</div>';
            }
            $h = $h + 1;
        }

        $result_str_regression['ipsurreg'] = '<div class="name_section">Информационные и прогностические свойства уравнения регрессии</div>';

        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Критерий "восходящих" и "нисходящих" серий:</div>';

        $fl_nar = 0;

        if(1*$data_regression['vn_cur'] > 1*$data_regression['vn_formula']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Условие 1: <span class="span_bold">'.html_entity_decode('&#651;').'(n) > (1/3 * (2*n - 1) - 1.96*((16*n - 29)/90)^(1/2))</span> ( <span class="span_value_true">'.$data_regression['vn_cur'].' > '. number_format(1*$data_regression['vn_formula'], 3) .'</span> )</div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Условие 1: <span class="span_bold">'.html_entity_decode('&#651;').'(n) > (1/3 * (2*n - 1) - 1.96*((16*n - 29)/90)^(1/2))</span> ( <span class="span_value_false">'.$data_regression['vn_cur'].' > '. number_format(1*$data_regression['vn_formula'], 3) .'</span> )</div>';
            $fl_nar = 1;
        }

        if(1*$data_regression['tn_cur'] < 1*$data_regression['tn_formula']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Условие 2: <span class="span_bold">'.html_entity_decode('&#428;').'(n) < '.html_entity_decode('&#428;').'<sub>0</sub>(n)</span> ( <span class="span_value_true">'.$data_regression['tn_cur'].' < '.$data_regression['tn_formula'].'</span> )</div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Условие 2: <span class="span_bold">'.html_entity_decode('&#428;').'(n) < '.html_entity_decode('&#428;').'<sub>0</sub>(n)</span> ( <span class="span_value_false">'.$data_regression['tn_cur'].' < '.$data_regression['tn_formula'].'</span> )</div>';
            $fl_nar = 1;
        }

        if($fl_nar === 0){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как ни одно из условий <span class="span_value_true_comment">не нарушено</span>, то <span class="span_value_true_comment">гипотеза о случайности выборки не отвергается</span>.</div></div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как <span class="span_value_false_comment">нарушено</span> одно из условий, то <span class="span_value_false_comment">гипотезу о случайности выборки следует отвергнуть</span>.</div></div>';
        }

        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Оценка остатков модели на гетероскедастичность:</div>';

        foreach($data_regression['spirmen_data'] as $k1=>$v1){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Коэффициент Спирмена для "'.$Head_name[$k1].'": <span class="span_value">'.number_format(1*$data_regression['spirmen_data'][$k1]['k_Spearmen'],3).'</span>.</div>';
            if(1*$data_regression['spirmen_data'][$k1]['t_kS'] > 1*$data_regression['spirmen_data'][$k1]['t_kS_tbl']){
                $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">т.к. <span class="span_value_false">'.number_format(1*$data_regression['spirmen_data'][$k1]['t_kS'],3).'</span>(t<sub>расч.</sub>) > '.$data_regression['spirmen_data'][$k1]['t_kS_tbl'].' (t<sub>кр.</sub>), то <span class="span_value_false_comment">св-во гетероскедастичности в остатках обнаруживается</span>.</div></div>';
            }else{
                $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">т.к. <span class="span_value_true">'.number_format(1*$data_regression['spirmen_data'][$k1]['t_kS'],3).'</span>(t<sub>расч.</sub>) <= '.$data_regression['spirmen_data'][$k1]['t_kS_tbl'].' (t<sub>кр.</sub>), то <span class="span_value_true_comment">св-во гетероскедастичности в остатках не обнаруживается</span>.</div></div>';
            }
        }

        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Оценка автокорреляции остатков модели:</div>';

        if(0 <= 1*$data_regression['DW_data']['DW'] && 1*$data_regression['DW_data']['DW'] < 1*$data_regression['DW_data']['DW_tbl']['dl']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие 0 <= <span class="span_value_false">'. number_format(1*$data_regression['DW_data']['DW'],3) .'</span> < '.$data_regression['DW_data']['DW_tbl']['dl'].' (d<sub>L</sub>), то принимается гипотеза о <span class="span_value_false_comment">положительной автокорреляции остатков</span></div>';
        }else if(1*$data_regression['DW_data']['DW_tbl']['du'] <= 1*$data_regression['DW_data']['DW'] && 1*$data_regression['DW_data']['DW'] < 4-1*$data_regression['DW_data']['DW_tbl']['du']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие '.$data_regression['DW_data']['DW_tbl']['du'].' (d<sub>U</sub>) <= <span class="span_value_true">'. number_format(1*$data_regression['DW_data']['DW'], 3) .'</span> < '. (4-1*$data_regression['DW_data']['DW_tbl']['du']) .' (4 - d<sub>U</sub>), то принимается гипотеза об <span class="span_value_true_comment">отсутствии автокорреляции остатков</span></div>';
        }else if(4 - 1*$data_regression['DW_data']['DW_tbl']['dl'] < 1*$data_regression['DW_data']['DW'] && 1*$data_regression['DW_data']['DW'] <= 4){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие '. (4 - 1*$data_regression['DW_data']['DW_tbl']['dl']) .' (4 - d<sub>L</sub>) < <span class="span_value_false">'. number_format(1*$data_regression['DW_data']['DW'], 3) .'</span> <= 4, то принимается гипотеза об <span class="span_value_false_comment">отрицательной автокорреляции остатков</span></div>';
        }else if((1*$data_regression['DW_data']['DW_tbl']['dl'] < 1*$data_regression['DW_data']['DW'] && 1*$data_regression['DW_data']['DW'] < 1*$data_regression['DW_data']['DW_tbl']['du'])){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие '.$data_regression['DW_data']['DW_tbl']['dl'] .' (d<sub>L</sub>) < <span class="span_value">'. number_format(1*$data_regression['DW_data']['DW'], 3) .'</span> < '.$data_regression['DW_data']['DW_tbl']['du'].' ((d<sub>U</sub>)), то не существует статистических оснований принять или отвергнуть гипотезу об отсутствии автокорреляции</div>';
        }else if((4 - 1*$data_regression['DW_data']['DW_tbl']['du'] < 1*$data_regression['DW_data']['DW'] && 1*$data_regression['DW_data']['DW'] < 4 - 1*$data_regression['DW_data']['DW_tbl']['dl'])){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие '. (4-1*$data_regression['DW_data']['DW_tbl']['du']) .' (4 - d<sub>U</sub>) < <span class="span_value">'. number_format(1*$data_regression['DW_data']['DW'], 3) .'</span> < '. (4-1*$data_regression['DW_data']['DW_tbl']['dl']) .' (4 - (d<sub>L</sub>)), то не существует статистических оснований принять или отвергнуть гипотезу об отсутствии автокорреляции</div>';
        }


        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Проверка гипотезы о равенстве нулю математического ожидания случайной составляющей:</div>';

        if(1*$data_regression['tGM_data']['tGM'] < 1*$data_regression['tGM_data']['tGM_tbl']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие |t<sub>p</sub>| < t<sub>кр.(0.05; '. (1*$data_regression['nGen']-1) .')</sub> ( <span class="span_value_true">'. (number_format(1*$data_regression['tGM_data']['tGM'],6)) .'...</span> < '. $data_regression['tGM_data']['tGM_tbl'] .'), гипотеза о <span class="span_value_true_comment">равенстве нулю математического ожидания</span> переменной ряда остатков <span class="span_value_true_comment">не отвергается</span> (условие Гаусса-Маркова).</div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняется условие |t<sub>p</sub>| > t<sub>кр.(0.05; '. (1*$data_regression['nGen']-1) .')</sub> ( <span class="span_value_false">'. (number_format(1*$data_regression['tGM_data']['tGM'],6)) .'...</span> > '. $data_regression['tGM_data']['tGM_tbl'] .'), гипотеза о <span class="span_value_false_comment">равенстве нулю математического ожидания</span> переменной ряда остатков <span class="span_value_false_comment">отвергается</span> (условие Гаусса-Маркова).</div>';
        }


        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Гипотеза о нормальности распределения случайной составляющей:</div>';

        $fl_NormRasp = 1;

        if(1*$data_regression['norm_raspr']['Assim'] < 1.5*$data_regression['norm_raspr']['S_Assim']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Условие 1: <span class="span_bold">A < 1.5 * '.html_entity_decode('&#963;').'<sub>A</sub></span> ( <span class="span_value_true">'.number_format(1*$data_regression['norm_raspr']['Assim'],3) .' < '. number_format(1.5*$data_regression['norm_raspr']['S_Assim'],3) .'</span> )</div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Условие 1: <span class="span_bold">A < 1.5 * '.html_entity_decode('&#963;').'<sub>A</sub></span> ( <span class="span_value_false">'.number_format(1*$data_regression['norm_raspr']['Assim'],3) .' >= '. number_format(1.5*$data_regression['norm_raspr']['S_Assim'],3) .'</span> )</div>';
            $fl_NormRasp = 0;
        }

        if(abs(1*$data_regression['norm_raspr']['Eks'] + 6/(1*$data_regression['nGen'] + 1)) < 1.5*$data_regression['norm_raspr']['S_Eks']){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Условие 2: <span class="span_bold">|'.html_entity_decode('&#917;').' + 6/(n + 1)| < 1.5 * '.html_entity_decode('&#963;').'<sub>'.html_entity_decode('&#917;').'</sub></span> ( <span class="span_value_true">' . number_format(abs(1*$data_regression['norm_raspr']['Eks'] + 6/(1*$data_regression['nGen'] + 1)),3) .' < '. number_format(1.5*$data_regression['norm_raspr']['S_Eks'],3) .'</span> )</div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Условие 2: <span class="span_bold">|'.html_entity_decode('&#917;').' + 6/(n + 1)| < 1.5 * '.html_entity_decode('&#963;').'<sub>'.html_entity_decode('&#917;').'</sub></span> ( <span class="span_value_false">' . number_format(abs(1*$data_regression['norm_raspr']['Eks'] + 6/(1*$data_regression['nGen'] + 1)),3) .' >= '. number_format(1.5*$data_regression['norm_raspr']['S_Eks'],3) .'</span> )</div>';
            $fl_NormRasp = 0;
        }

        if($fl_NormRasp === 1){
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как выполняются оба условия, то <span class="span_value_true_comment">распределение остатков</span> может быть признано <span class="span_value_true_comment">нормальным</span>, если оно является случайным.</div></div>';
        }else{
            $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Так как не выполнена система условий, то <span class="span_value_false_comment">распределение остатков не может быть признано нормальным</span>.</div></div>';
        }

        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Оценка прогностических свойств моделей:</div>';
        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_step"><div class="name_part">Коэффициент K<sub>T1</sub> (Тейла): <span class="span_value">'.number_format(1*$data_regression['progn_har']['KT1'],4).'</span></div>';
        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Коэффициент K<sub>T2</sub>: <span class="span_value">'.number_format(1*$data_regression['progn_har']['KT2'],4).'</span></div>';
        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="name_part">Коэффициент U<sub>T1</sub>: <span class="span_value">'.number_format(1*$data_regression['progn_har']['UT1'],4).'</span></div>';
        $result_str_regression['ipsurreg'] = $result_str_regression['ipsurreg'].'<div class="div_comment">Если данные коэффициенты достаточно близки к нулю, то это может свидетельствовать об относительном успехе сделанного прогноза.</div></div>';


        if(isset($data_regression['Y_forecast'])){
            $result_str_regression['build_forecast'] = '<div class="name_section">Простроение прогноза</div>';
            $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="name_part">Уравнение регрессии: <span class="uravn_regression_color">'.$consolid_str.'</span></div>';
            $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="description_coef_reg">'.$description_coef_reg.'</div>';

            $h = 1;
            foreach($data_regression['Y_forecast'] as $k=>$v){

                $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="div_step"><div class="name_part">Прогнозное значение №'.$h.': <span class="span_value">'.number_format(1*$data_regression['Y_forecast'][$k]['forecast'],3,'.','').'</span>';
                $runtime_string = ' при ( ';
                foreach($data_regression['Z_forecast'][$k] as $kk=>$vv){
                    if(isset($Head_name[$kk])){
                        $runtime_string = $runtime_string.$Head_name[$kk].' => '.$vv.' ';
                    }/*else{
                        $runtime_string = $runtime_string.'Свободный коэффициент => '.$vv.' ';
                    }*/
                }
                $runtime_string = $runtime_string.')';
                $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].$runtime_string.'</div>';
                $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="name_part">Интервал : <span class="span_value">'.number_format(1*$data_regression['Y_forecast'][$k]['interval_low'],3,'.','').'</span> < <span class="span_value">'.number_format(1*$data_regression['Y_forecast'][$k]['forecast'],3,'.','').'</span> < <span class="span_value">'.number_format(1*$data_regression['Y_forecast'][$k]['interval_top'],3,'.','').'</span></div>';
                $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="div_comment">Иначе говоря, с вероятностью 95% ожидаемая величина попадает в данный интервал.</div></div>';
                $result_str_regression['build_forecast'] = $result_str_regression['build_forecast'].'<div class="hr"></div>';
                $h = $h + 1;
            }

        }

        return $result_str_regression;
    }

    //report
    public static function report_regression($Y, $X, $Z='', $Head_name='', $Z_forecast = ''){

        $report = [];

        //print_r(array_keys($Y[array_keys($Y)[0]])[0]);

        if(array_keys($Y[array_keys($Y)[0]])[0] === 0){
            return 'необходимо определить ключ для Y!';
        }
        if(array_keys($X[array_keys($X)[0]])[0] === 0){
            return 'необходимо определить ключи для X!';
        }
        if($Z !== '' && $Z !== '1'){
            if(array_keys($Z[array_keys($Z)[0]])[0] === 0){
                return 'необходимо определить ключи для Z!';
            }
        }

        if($Head_name === ''){
            $Head_name = [];
            foreach(array_keys($Y[array_keys($Y)[0]]) as $k=>$v){
                $Head_name[$v] = 'Зависимая переменная ('.$v.')';
            }
            foreach(array_keys($X[array_keys($X)[0]]) as $k=>$v){
                $Head_name[$v] = 'Независимая переменная ('.$v.')';
            }
        }

        if($Z_forecast !== '' && is_array($Z)){
            if(count($Z_forecast[array_keys($Z_forecast)[0]]) !== count($Z[array_keys($Z)[0]])){
                return 'Не совпадает структура данных $Z с $Z_forecast';
            }else{
                foreach($Z_forecast as $k1=>$v1){
                    $h = 0;
                    foreach($v1 as $k2=>$v2){
                        unset($Z_forecast[$k1][$k2]);
                        $Z_forecast[$k1][array_keys($Z[array_keys($Z)[0]])[$h]] = $v2;
                        $h = $h + 1;
                    }
                }
            }
        }

        $data_correlation = self::calculate_correlation_analisys($Y, $X);
        $report['correlation_data'] = $data_correlation;
        $str_table_correlation = self::print_correlation_analisys($data_correlation, $Y, $X, $Head_name);
        $report['print_correlation'] = $str_table_correlation;

        if($Z===''){
            if(count(array_keys($X[array_keys($X)[0]])) === 1){
                $Z = $X;
            }else if(count(array_keys($X[array_keys($X)[0]])) > 1){
                $arr_keys_X = array_keys($X[array_keys($X)[0]]);

                $delete_in_Z = [];

                foreach($arr_keys_X as $k1=>$v1){
                    foreach($arr_keys_X as $k2=>$v2){
                        if($v1 !== $v2){
                            if(abs($report['correlation_data']['R_fin'][$v1][$v2]) > 0.5){
                                if(abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1]) > abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v2])){
                                    $delete_in_Z[$v2] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v2];
                                }else{
                                    $delete_in_Z[$v1] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1];
                                }
                            }
                        }
                    }
                    if( abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1]) < 0.5 && !isset($delete_in_Z[$v1])){
                        $delete_in_Z[$v1] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1];
                    }
                }
                foreach($delete_in_Z as $k1=>$v1){
                    foreach($X as $k2=>$v2){
                        unset($X[$k2][$k1]);
                    }
                }
                $Z = $X;
                if(is_array($Z_forecast)){
                    foreach($delete_in_Z as $k1=>$v1){
                        foreach($Z_forecast as $k2=>$v2){
                            unset($Z_forecast[$k2][$k1]);
                        }
                    }
                }
            }
        }else if($Z==='1'){

            if(count(array_keys($X[array_keys($X)[0]])) === 1){
                $Z = [];
                foreach($X as $k1=>$v1){
                    $Z[$k1]['free_coef'] = 1;
                    foreach($v1 as $k2=>$v2){
                        $Z[$k1][$k2]=$v2;
                    }
                }
                if(is_array($Z_forecast)){
                    foreach($Z_forecast as $k1=>$v1){
                        $h = 0;
                        foreach($v1 as $k2=>$v2){
                            unset($Z_forecast[$k1][$k2]);
                            $Z_forecast[$k1][array_keys($Z[array_keys($Z)[0]])[$h]] = $v2;
                            $h = $h + 1;
                        }
                    }
                }

            }else if(count(array_keys($X[array_keys($X)[0]])) > 1){
                $arr_keys_X = array_keys($X[array_keys($X)[0]]);

                $delete_in_Z = [];

                foreach($arr_keys_X as $k1=>$v1){
                    foreach($arr_keys_X as $k2=>$v2){
                        if($v1 !== $v2){
                            if(abs($report['correlation_data']['R_fin'][$v1][$v2]) > 0.5){
                                if(abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1]) > abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v2])){
                                    $delete_in_Z[$v2] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v2];
                                }else{
                                    $delete_in_Z[$v1] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1];
                                }
                            }
                        }
                    }
                    if( abs($report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1]) < 0.5 && !isset($delete_in_Z[$v1])){
                        $delete_in_Z[$v1] = $report['correlation_data']['R_fin'][ array_keys($Y[array_keys($Y)[0]])[0] ][$v1];
                    }
                }
                foreach($delete_in_Z as $k1=>$v1){
                    foreach($X as $k2=>$v2){
                        unset($X[$k2][$k1]);
                    }
                }

                $Z = [];
                foreach($X as $k1=>$v1){
                    $Z[$k1]['free_coef'] = 1;
                    foreach($v1 as $k2=>$v2){
                        $Z[$k1][$k2]=$v2;
                    }
                }

                if(is_array($Z_forecast)){
                    foreach($delete_in_Z as $k1=>$v1){
                        foreach($Z_forecast as $k2=>$v2){
                            unset($Z_forecast[$k2][$k1]);
                        }
                    }

                    foreach($Z_forecast as $k1=>$v1){
                        $h = 0;
                        foreach($v1 as $k2=>$v2){
                            unset($Z_forecast[$k1][$k2]);
                            $Z_forecast[$k1][array_keys($Z[array_keys($Z)[0]])[$h]] = $v2;
                            $h = $h + 1;
                        }
                    }
                }
            }

        }

        $data_regression = self::calculate_regression_analisys($Y, $Z, $Z_forecast, $Head_name);
        $report['regression_data'] = $data_regression;
        $str_regression = self::print_regression_analisys($data_regression, $Y, $Z, $Head_name);
        $report['print_regression'] = $str_regression;

        print_r($report);

        /*
        $regression_result = self::calculate_regression($Y, $Z);
        $coef_elast = [];
        foreach($regression_result['Z_coef'] as $k=>$v){
            $coef_elast[$k] = $v*($regression_result['Z_avg'][$k]/$regression_result['Y_avg']);
        }

        print_r($coef_elast);
        */

    }

}

?>