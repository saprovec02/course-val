<?php


namespace app\components;

use app\helpers\Additional;
use yii\base\Component;

class Currency extends Component
{
    /**
     * @var string
     */
    public $url;

    /**
     * @var array
     */
    public $charList;

    /**
     * @var array
     */
    public $colorList;

    /**
     * @var string
     */
    public $rubCode;

    /**
     * @var array
     */
    public $currencyList;


    public function init(){
        parent::init();
        $this->url = \Yii::$app->params['currency']['url'];
        $this->charList = \Yii::$app->params['currency']['currencyChar'];
        $this->colorList = \Yii::$app->params['currency']['currencyColor'];
        $this->rubCode = \Yii::$app->params['currency']['rub'];
        $this->currencyList = \Yii::$app->params['currency']['currency'];
    }

    /**
     * Получение ежедневного курса
     *
     * @return array
     */
    public function getXmlDailyRU(){
        return \Yii::$app->cache->getOrSet('xml-daily', function () {

            $result = [];

            if ($data = @file_get_contents($this->url)) {

                $data = json_decode($data);

                foreach ($data->Valute as $key => $value) {
                    if (in_array($key, $this->currencyList)) {
                        $value->Value /= $value->Nominal;
                        $result[$key] = $value;
                    }
                }
            }

            return $result;
        },3600);
    }

    /**
     * Расчет ежедневного курса
     *
     * @param string $currency
     * @return array
     */
    public function getDaily($currency='RUB')
    {
        $data = $this->getXmlDailyRU();

        if ($currency === 'RUB')
        {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value){

            if ($currency === $key){
                continue;
            }

            $value->Value = Additional::getRound($value->Value/$data[$currency]->Value);
            $result[] = $value;
        }

        $rub = [
            'NumCode' => 643,
            'CharCode' => 'RUB',
            'Name' => 'Российский рубль',
            'Value' => Additional::getRound(1/$data[$currency]->Value)
        ];

        $result[] = (object)$rub;

        return $result;
    }

    /**
     * Получение статистики за прошлый месяц из базы данных
     *
     * @return array
     */
    public function getDailyDB()
    {
        $maxDay = date('t', mktime(0, 0, 0, date('m') - 1));

        return \app\models\Currency::find()
            ->where(['between',
                'date',
                date('Y-m-d', mktime(0, 0, 0, date('m') - 1, 1)),
                date('Y-m-d', mktime(0, 0, 0, date('m') - 1, $maxDay))
            ])
            ->orderBy(['date' => SORT_ASC])
            ->all();
    }

    /**
     * Запись статистики за прошлый месяц в базу данных
     *
     * @return void
     */
    public function saveDailyDB()
    {
        $maxDay = date('t', mktime(0, 0, 0, date('m') - 1));

        for ($i = 1; $i <= $maxDay ; $i++) {

            $date = date('Y-m-d', mktime(0, 0, 0, date('m') - 1, $i));
            $dateArchive = date('Y/m/d', mktime(0, 0, 0, date('m') - 1, $i));

            if ($data = @file_get_contents('https://www.cbr-xml-daily.ru/archive/'.$dateArchive.'/daily_json.js'))
            {
                $data = json_decode($data);

                foreach ($data->Valute as $key => $value){

                    if (in_array($key, $this->currencyList))
                    {
                        $model = new \app\models\Currency();
                        $model->date = $date;
                        $model->valute = $key;
                        $model->value = Additional::getRound($value->Value/$value->Nominal);
                        $model->save();
                    }
                }
            }
        }
    }

    /**
     * Сборка координат для графика курса рубля
     *
     * @param $data
     * @return array
     */
    public function getCoordinateRUB($data)
    {
        $resultXY = [];

        foreach ($this->currencyList as $value){

            foreach ($data as $item){

                if ($value === $item->valute)
                {
                    $resultXY['currency'][$value]['course'][] = $item->value;
                    $resultXY['labels'][] = (int)substr($item->date,8);
                }

                $resultXY['currency'][$value]['label'] = $this->charList[$value];
                $resultXY['currency'][$value]['color'] = $this->colorList[$value];
            }
        }

        $resultXY['labels'] = array_unique($resultXY['labels']);

        return $resultXY;
    }

    /**
     * Сборка координат для графика курса валют
     *
     * @param $data
     * @param $currency
     * @return array
     */
    public function getCoordinateOther($data, $currency)
    {
        $course = [];

        foreach ($data as $value){

            if ($currency === $value->valute)
            {
                $course[$value->date] = $value->value;
            }
        }

        $resultXY = [];

        foreach ($this->currencyList as $value){

            if ($currency === $value)
            {
                continue;
            }

            foreach ($data as $item){

                if ($value === $item->valute)
                {
                    $resultXY['currency'][$value]['course'][] = Additional::getRound($item->value/$course[$item->date]);
                    $resultXY['labels'][] = (int)substr($item->date,8);
                }

                $resultXY['currency'][$value]['label'] = $this->charList[$value];
                $resultXY['currency'][$value]['color'] = $this->colorList[$value];
            }
        }

        foreach ($course as $key => $value){
            $resultXY['currency']['RUB']['course'][] = Additional::getRound(1/$value);
        }

        $resultXY['currency']['RUB']['label'] = $this->charList['RUB'];
        $resultXY['currency']['RUB']['color'] = $this->colorList['RUB'];

        $resultXY['labels'] = array_unique($resultXY['labels']);

        return $resultXY;
    }

}