<?php

namespace app\controllers;

use app\models\Currency;
use phpDocumentor\Reflection\Types\Object_;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;

class SiteController extends Controller
{
    public static $valute = ['USD', 'EUR', 'CNY', 'JPY'];

    public static $valuteChar = [
        'USD' => 'Доллар США',
        'EUR' => 'Евро',
        'CNY' => 'Китайский юань',
        'JPY' => 'Японских иен',
        'RUB' => 'Российский рубль'
    ];

    public static $valuteColor = [
        'USD' => 'Red',
        'EUR' => 'Yellow',
        'CNY' => 'Blue',
        'JPY' => 'Fuchsia',
        'RUB' => 'Black'
    ];

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @param string $currency
     * @return string
     */
    public function actionIndex($currency='rub')
    {
        $currency = strtoupper($currency);

        if (!$data = $this->getDailyDB())
        {
            $this->saveDailyDB();
            $data = $this->getDailyDB();
        }

        if ($currency === 'RUB')
        {
            $data = $this->getCoordinateRUB($data);
        }
        else{
            $data = $this->getCoordinateOther($data,$currency);
        }

        return $this->render('index', [
            'currency' => $this->getDaily($currency),
            'data' => $data,
            'valuteChar' => self::$valuteChar
        ]);
    }

    /**
     * Получение ежедневного курса
     *
     * @return array
     */
    public function getXmlDailyRU()
    {
       return Yii::$app->cache->getOrSet('xml-daily', function () {

           $result = [];

           $data = json_decode(file_get_contents('https://www.cbr-xml-daily.ru/daily_json.js'));

           foreach ($data->Valute as $key => $value){
               if (in_array($key, self::$valute)){
                   $value->Value = $value->Value/$value->Nominal;
                   $result[$key] = $value;
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

            $value->Value = round($value->Value/$data[$currency]->Value, 4);
            $result[] = $value;
        }

        $rub = [
            'NumCode' => 643,
            'CharCode' => 'RUB',
            'Name' => 'Российский рубль',
            'Value' => round(1/$data[$currency]->Value,4)
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

        return Currency::find()
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

                    if (in_array($key, self::$valute))
                    {
                        $model = new Currency();
                        $model->date = $date;
                        $model->valute = $key;
                        $model->value = round($value->Value/$value->Nominal,4);
                        $model->save();
                    }
                }
            }
        }
    }

    public function getCoordinateRUB($data)
    {
        $resultXY = [];

        foreach (self::$valute as $value){

            foreach ($data as $item){

                if ($value === $item->valute)
                {
                    $resultXY['currency'][$value]['course'][] = $item->value;
                    $resultXY['labels'][] = (int)substr($item->date,8);
                }

                $resultXY['currency'][$value]['label'] = self::$valuteChar[$value];
                $resultXY['currency'][$value]['color'] = self::$valuteColor[$value];
            }
        }

        $resultXY['labels'] = array_unique($resultXY['labels']);

        return $resultXY;
    }

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

        foreach (self::$valute as $value){

            if ($currency === $value)
            {
                continue;
            }

            foreach ($data as $item){

                if ($value === $item->valute)
                {
                    $resultXY['currency'][$value]['course'][] = round($item->value/$course[$item->date],4);
                    $resultXY['labels'][] = (int)substr($item->date,8);
                }

                $resultXY['currency'][$value]['label'] = self::$valuteChar[$value];
                $resultXY['currency'][$value]['color'] = self::$valuteColor[$value];
            }
        }

        foreach ($course as $key => $value){
            $resultXY['currency']['RUB']['course'][] = round(1/$value,4);
        }

        $resultXY['currency']['RUB']['label'] = self::$valuteChar['RUB'];
        $resultXY['currency']['RUB']['color'] = self::$valuteColor['RUB'];

        $resultXY['labels'] = array_unique($resultXY['labels']);

        return $resultXY;
    }
}
