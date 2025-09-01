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

        $rabbit->consume('webhooks', ['#'], function ($body) {
            echo "NEW MSG: $body" . "\n";
            $response = json_decode($body);
            echo $response->id . "\n";
            $className = "common\\models\\" . $response->model_name;
            echo $className . "\n";
            $model = $className::findOne($response->id);
            if ($model) {
                echo "ATTRIBUTTES: " . json_encode($model->attributes) . "\n";
                $model->setAttributes($response->attributes, false);
                echo "SET ATTRIBUTES SUCCESFFULLY" . json_encode($response->attributes) . "\n";
                $model->updateAttributes($response->attributes);
                echo "SAVE SUCCESFULLY" . "\n";
            } else {
                echo "!model\n";
            }
        });
    }
}
