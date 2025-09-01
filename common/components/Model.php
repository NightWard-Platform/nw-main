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

        $event = $insert ? self::tableName() . ".create" : self::tableName() . ".update";
        Yii::$app->rabbitMq->exchange_publish(
            $event,
            json_encode([
                'id' => $this->id,
                'attributes' => $this->sttributes,
                'changed' => $changedAttributes,
            ])
        );
    }
    public function afterDelete()
    {
        parent::afterDelete();
        Yii::$app->rabbitMq->exchange_publish(
            self::tableName() . ".delete",
            json_encode([
                'id' => $this->id
            ])
        );
    }
}
