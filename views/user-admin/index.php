<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
$this->params['breadcrumbs'][] = $this->title;
?>

<h1><?= Html::encode($this->title) ?></h1>
<?php // echo $this->render('_search', ['model' => $searchModel]); ?>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'columns' => [
        [
            'attribute' => 'id',
            'content' => function($model) {
                return Html::a(Html::encode($model->id), ['user-admin/view', 'id' => $model->id]);
            },
        ],
        [
            'attribute' => 'username',
            'content' => function($model) {
                return Html::a(Html::encode($model->username), ['user-admin/view', 'id' => $model->id]);
            },
        ],
        [
            'label' => 'Auth Methods',
            'content' => function($model) {
                /** $model app\models\User */
                $html = [];
                if ($model->getPasswordType() !== 'NONE') {
                    $html[] = 'PW';
                }
                if (!empty($model->authClients)) {
                    foreach($model->authClients as $authClient) {
                        $html[] = Html::encode($authClient->source);
                    }
                }

                return empty($html) ? '<span class="not-set">(none)</span>' : implode(', ', $html);
            },
        ],

        'email:email',
        [
            'attribute' => 'status',
            'value' => 'statusLabel',
            'format' => 'raw',
            'filter' => \app\models\User::getStatuses(),
        ],
        'created_at:datetime',
        // 'updated_at',

        [
            'class' => 'yii\grid\ActionColumn',
            'contentOptions' => ['class' => 'action-column'],
        ],
    ],
]); ?>
