<?php

namespace App\Controller;

use App\Services\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route("/")
     */
    public function index(Mailer $mailer, EntityManagerInterface $entity)
    {
        $mailer->sendEmail();

        return new Response('Test');

    }

}