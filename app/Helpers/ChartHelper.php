<?php

namespace App\Helpers;

use App\Models\User;
use Modules\Core\Models\Currency;
use Asantibanez\LivewireCharts\Models\ColumnChartModel;
use Asantibanez\LivewireCharts\Models\LineChartModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChartHelper
{

    public static function ConvertLinesMutli($chart_data, $labels, $title): LineChartModel
    {
        $model = new LineChartModel();

        $model->withLegend();
        $model->multiLine();
        $model->setTitle($title);

        $model->setColors(array_column($chart_data, 'backgroundColor'));
        foreach ($chart_data as $chart_datum) {
            foreach ($chart_datum['data'] as $index => $datum) {
                $model->addSeriesPoint($chart_datum['label'], $labels[$index], $datum, $chart_datum['backgroundColor']);
            }
        }
        return $model;
    }
    public static function ConvertColumnsMutli($chart_data, $labels, $title)
    {
        $model = new ColumnChartModel();

        $model->withLegend();
        $model->multiColumn();
        $model->setTitle($title);
        $model->setColors(array_column($chart_data, 'backgroundColor'));
        foreach ($chart_data as $chart_datum) {
            foreach ($chart_datum['data'] as $index => $datum) {

                $model->addSeriesColumn($chart_datum['label'], $labels[$index], $datum, $chart_datum['backgroundColor']);
            }
        }
        return $model;
    }


}
