<?php
/**
 * Created by PhpStorm.
 * User: andrey
 * Date: 30.09.19
 * Time: 19:43
 */

namespace App\Services;


class Mailer
{
    /**
     * @var string
     */
    private $mailerFrom;

    public function __construct(string $mailerFrom)
    {
        $this->mailerFrom = $mailerFrom;
    }

    public function sendEmail()
    {
        echo 'Email from ' . $this->mailerFrom;
    }
}