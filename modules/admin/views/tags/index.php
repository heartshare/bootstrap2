<?php
use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\CheckboxColumn;
use yii\grid\GridView;

$this->title = Yii::t('app', 'Tags');
?>
<?= Html::a(Yii::t('app', 'Add'), Url::toRoute('edit'), ['class' => 'btn btn-default']) ?>

<?= Html::beginForm(Url::toRoute('operations'), 'post') ?>

<?php \yii\widgets\Pjax::begin(); ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel'  => $tagSearch,
    'options' => ['class' => 'gridview'],
    'layout' => Yii::$app->getModule('admin')->defaultGridTemplate($dataProvider, ['delete']),
    'tableOptions' => ['class' => 'table ' . ($dataProvider->count ? 'table-hover' : '')],
    'columns' => [
            // checkbox
        [
            'class' => CheckboxColumn::classname(),
            'contentOptions' => ['style' => 'width: 30px']
        ],
            // title
        [
            'attribute' => 'title',
            'format' => 'raw',
            'value' => function ($model) {
                return Html::a($model['title'], ['edit', 'id' => $model['id']]);
            }
        ],
            // count
        [
            'attribute' => 'count',
        ],
            // action buttons
        [
            'class' => 'yii\grid\ActionColumn',
            'contentOptions' => ['class' => 'text-right'],
            'template'=>'{delete}',
            'buttons' => Yii::$app->getModule('admin')->defaultGridButtons(['delete'])
        ],
    ],
]) ?>

<?php \yii\widgets\Pjax::end(); ?>

<?= Html::endForm(); ?>