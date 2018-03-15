<?php
/**
 * Created by PhpStorm.
 * User: Choate
 * Date: 2018/3/12
 * Time: 15:07
 */

namespace choate\smses;

interface AdapterInterface
{
    public function send($mobile, $content, $region = '86');

    public function batchSend(array $mobiles, $content, $region = '86');

    public function getName();
}