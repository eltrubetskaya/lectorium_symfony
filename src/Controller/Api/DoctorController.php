<?php

namespace App\Controller\Api;

use App\Entity\Doctor;
use App\Repository\DoctorRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;

/**
 * @Route("/api/doctor")
 */
class DoctorController extends AbstractController
{
    /**
     * @Route("/list", name="doctor_list", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns doctors data",
     * )
     * @SWG\Tag(name="Doctor")
     *
     * @param DoctorRepository $repository
     * @return JsonResponse
     */
    public function list(DoctorRepository $repository): JsonResponse
    {
        $doctors = $repository->findAll();

        return $this->json(['doctors' => $doctors], Response::HTTP_OK);
    }

    /**
     * @Route("/{id}/schedule", name="doctor_schedule", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns doctors schedule data",
     * )
     * @SWG\Tag(name="Doctor")
     *
     * @param DoctorRepository $repository
     * @return JsonResponse
     */
    public function schedule(Doctor $doctor): JsonResponse
    {
        return $this->json(['schedule' => $doctor->getSchedule()], Response::HTTP_OK);
    }
}
