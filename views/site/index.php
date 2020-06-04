<?php

/* @var $this yii\web\View */

$this->title = Yii::$app->name;
?>
<div class="site-index">
    <div class="body-content">

        <h2 class="text-center">Текущий курс валют: <?=(Yii::$app->request->get('currency'))?$valuteChar[strtoupper(Yii::$app->request->get('currency'))]:'Российский рубль';?></h2>

        <table class="table">
            <thead>
            <tr>
                <th scope="col">Цифр. код</th>
                <th scope="col">Букв. код</th>
                <th scope="col">Единиц</th>
                <th scope="col">Валюта</th>
                <th scope="col">Курс</th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($currency as $value){
                echo '<tr>
                <th>'.$value->NumCode.'</th>
                <td>'.$value->CharCode.'</td>
                <td>1</td>
                <td>'.$value->Name.'</td>
                <td>'.$value->Value.'</td>
            </tr>';
            }
            ?>
            </tbody>
        </table>

        <br/>

        <h3 class="text-center">Статистика курса валют за прошлый месяц: <?=(Yii::$app->request->get('currency'))?$valuteChar[strtoupper(Yii::$app->request->get('currency'))]:'Российский рубль';?></h3>

        <canvas id="myChart"></canvas>

        <script>
            $(document).ready(function() {
                var canvas = document.getElementById("myChart");

                Chart.defaults.global.defaultFontFamily = "Lato";
                Chart.defaults.global.defaultFontSize = 16;

                var data = {
                    labels: [<?php echo implode(',', $data['labels'])?>],
                    datasets: [
                        <?php
                        foreach ($data['currency'] as $key => $value){
                            echo '{
                                   label: "'.$value['label'].'",
                                   data: ['.implode(',', $value['course']).'],
                                   lineTension: 0,
                                   fill: false,
                                   borderColor: "'.$value['color'].'"
                                   },';
                        }
                        ?>
                    ]
                };

                var chartOptions = {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            boxWidth: 40,
                            fontColor: 'black'
                        }
                    }
                };

                var lineChart = new Chart(canvas, {
                    type: 'line',
                    data: data,
                    options: chartOptions
                });
            });
        </script>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@2.8.0"></script>
