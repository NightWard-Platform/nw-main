<?php

namespace common\components;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class Model extends ActiveRecord
{
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (in_array($this->getTableSchema()->name, Yii::$app->params['webhooks_produce'])) {
            $tableName = self::getTableSchema()->name;
            $event = $insert ? $tableName . ".create" : $tableName . ".update";
            Yii::$app->rabbitmq->exchange_publish(
                $event,
                json_encode([
                    'id' => $this->id,
                    'model_name' => (new \ReflectionClass($this))->getShortName(),
                    'attributes' => $this->attributes,
                    'changed' => $changedAttributes,
                ])
            );
        }
    }
    public function afterDelete()
    {
        parent::afterDelete();
        if (in_array($this->getTableSchema()->name, Yii::$app->params['webhooks_produce'])) {
            Yii::$app->rabbitmq->exchange_publish(
                self::getTableSchema()->name . ".delete",
                json_encode([
                    'id' => $this->id
                ])
            );
        }
    }
}
