<?php

namespace app\modules\admin\controllers;

use app\components\BaseController;
use app\models\News;
use app\models\NewsType;
use app\modules\admin\models\search\NewsSearch;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use Yii;

class NewsController extends BaseController
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'activate' => ['post'],
                    'block' => ['post'],
                    'delete' => ['post'],
                    'operations' => ['post'],
                    'preview-upload' => ['post'],
                    'gallery-upload' => ['post'],
                ],
            ],
        ];
    }
    
    public function actions()
    {
        return [
            'operations' => [
                'class' => 'app\modules\admin\controllers\common\OperationsAction',
                'modelName' => 'app\models\News',
            ],
            'activate' => [
                'class' => 'app\modules\admin\controllers\common\ActivateAction',
                'modelName' => 'app\models\News',
            ],
            'block' => [
                'class' => 'app\modules\admin\controllers\common\BlockAction',
                'modelName' => 'app\models\News',
            ],
            'delete' => [
                'class' => 'app\modules\admin\controllers\common\DeleteAction',
                'modelName' => 'app\models\News',
            ],
            'text-upload' => [
                'class'      => 'app\controllers\common\UploadAction',
                'modelName'  => 'app\models\News',
                'attribute'  => 'text', 
                'inputName'  => 'file',
                'type'       => 'image',
                'resultName' => 'filelink'
            ],
            'preview-upload' => [
                'class'     => 'app\controllers\common\UploadAction',
                'modelName' => 'app\models\News',
                'attribute' => 'preview', 
                'inputName' => 'file',
                'type'      => 'image',
            ],
            'gallery-upload' => [
                'class'     => 'app\controllers\common\UploadAction',
                'modelName' => 'app\models\News',
                'attribute' => 'gallery', 
                'inputName' => 'file',
                'type'      => 'image',
                'multiple'  => true,
                'template'  => '/shared/files/gallery-item',
            ],
        ];
    }
        
    public function actionIndex() 
    {
        $newsSearch = new NewsSearch();
        $dataProvider = $newsSearch->search(Yii::$app->request->get());
        $statuses = News::getStatuses();
        
        return $this->render('index', [
            'newsSearch' => $newsSearch,
            'dataProvider' => $dataProvider,
            'statuses' => $statuses,
            'types' => NewsType::find()->orderBy('title')->asArray()->all()
        ]);
    }

    public function actionEdit($id = null)
    {
        $model = new News();

        if ($id) {
            $model = $this->loadModel($model, $id);
        }
        
        if (Yii::$app->request->isPost) {
            if ($model->load(Yii::$app->request->post()) && $model->save()) {
                Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully'));
                return $this->response(['redirect' => Url::toRoute(['edit', 'id' => $model->id])]);   
            } else {
                return $this->response(['errors' => $model->getErrors(), 'prefix' => 'news-']);            
            }
        }

        return $this->render('edit', [
            'model' => $model,
            'types' => NewsType::find()->orderBy('title')->asArray()->all()
        ]);
    }
}
