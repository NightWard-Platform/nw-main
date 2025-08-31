<?php

/**
 * @author Inpassor <inpassor@yandex.com>
 * @link https://github.com/Inpassor/yii2-daemon
 */

namespace console\workers;

declare(ticks=1);

class Webhook extends \inpassor\daemon\Worker
{
    public $active = true;
    public $maxProcesses = 1;
    public $delay = 1;
    public function run()
    {
        echo 'work' . "\n";
    }
}
