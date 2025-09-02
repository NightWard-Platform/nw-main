<?php

/**
 * @author Inpassor <inpassor@yandex.com>
 * @link https://github.com/Inpassor/yii2-daemon
 */

namespace console\workers;

use Yii;
use common\models\User;

declare(ticks=1);

class Webhook extends \inpassor\daemon\Worker
{
    public $active = true;
    public $maxProcesses = 1;
    public $delay = 1;
    public function run()
    {
        $rabbit = Yii::$app->rabbitmq;

        echo "waiting for messages.." . "\n";

        $rabbit->consume('webhooks', Yii::$app->params['webhooks_consume'], function ($body) {
            $response = json_decode($body);
            $className = "common\\models\\" . $response->model_name;
            $model = $className::findOne($response->id);
            if ($model) {
                $model->setAttributes($response->attributes, false);
                $model->updateAttributes($response->attributes);
            }
        });
    }
}
