<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

class SiteController extends Controller
{
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
     * @throws NotFoundHttpException
     */
    public function actionIndex($currency='rub')
    {
        $currency = strtoupper($currency);

        $component = Yii::$app->currency;

        if (!$data = $component->getDailyDB())
        {
            $component->saveDailyDB();
            $data = $component->getDailyDB();
        }

        if ($currency === $component->rubCode)
        {
            $data = $component->getCoordinateRUB($data);
        }
        else if (!in_array($currency, $component->currencyList))
        {
            throw new NotFoundHttpException('The requested page does not exist.',404);
        }
        else {
            $data = $component->getCoordinateOther($data,$currency);
        }

        return $this->render('index', [
            'currency' => $component->getDaily($currency),
            'data' => $data,
            'valuteChar' => $component->charList
        ]);
    }
}
