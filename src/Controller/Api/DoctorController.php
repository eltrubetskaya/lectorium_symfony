<?php

namespace App\Controller\Api;

use App\Entity\Appointment;
use App\Entity\Doctor;
use App\Entity\Schedule;
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
     * @param Doctor $doctor
     * @return JsonResponse
     * @throws \Exception
     */
    public function schedule(Doctor $doctor): JsonResponse
    {
        $schedule = $doctor->getSchedule();
        $currentDay = (new \DateTime('now'))->format('w');
        /** @var Schedule $item */
        foreach ($schedule as $item) {
            $day = (new \DateTime('next ' . $item->getDay()))->format('w');
            if ($day < $currentDay) {
                $item->setEnabled(true);
                /** @var Appointment $appointment */
                foreach ($item->getAppointments() as $appointment) {
                    if ($appointment->getStatus() === Appointment::STATUS_CREATED) {
                        $appointment->setStatus(Appointment::STATUS_COMPLETED);
                    }
                }
            }
        }
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['schedule' => $doctor->getSchedule()], Response::HTTP_OK);
    }
}
