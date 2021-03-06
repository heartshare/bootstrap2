<?php
use yii\widgets\ActiveForm;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

$this->title = Yii::t('app', 'Roles');
?>
<?= $this->render('/shared/forms/header', ['title' => $model->description, 'model' => $model]) ?>

<?php $form = ActiveForm::begin(['options' => ['class' => 'form']]); ?>

    <?php if ($model->isSuperUser()):?>
    <div class="alert alert-warning" role="alert">
        <?= Yii::t('app', 'This role has all privileges by default, it can not be edited') ?>
    </div>
    <?php endif?>

    <!-- name -->
    <?= $form->field($model, 'name')->textInput()->hint(Yii::t('app', 'Only latin letters')) ?>
    
    <!-- description -->
    <?= $form->field($model, 'description')->textInput() ?>
    
    <!-- permissions -->
    <?= $form->field($model, 'permissions')
        ->dropDownList(ArrayHelper::map($permissions, 'name', function ($row) {
            return Yii::t('app', $row->description);
        }), [
            'multiple' => true,
            'size' => 15,
            'disabled' => $model->isSuperUser()
        ]) ?>
        
    <!-- roles -->
    <?= $form->field($model, 'roles')
        ->dropDownList(ArrayHelper::map($roles, 'name', function ($row) {
            return Yii::t('app', $row->description);
        }), [
            'multiple' => true,
            'size' => 15,
            'disabled' => $model->isSuperUser()
        ])
        ->hint(Yii::t('app', 'The role will receive all the privileges of the selected role')) ?>

    <?php if (!$model->isSuperUser()):?>
    <?= $this->render('/shared/forms/controls', ['model' => $model]) ?>
    <?php endif?>

<?php ActiveForm::end(); ?>
