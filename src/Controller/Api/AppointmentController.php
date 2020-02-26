<?php

namespace App\Controller\Api;

use App\Entity\Appointment;
use App\Entity\Schedule;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api/appointment")
 */
class AppointmentController extends AbstractController
{
    /**
     * @Route("/create", name="appointment_create", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="scheduleId", type="integer", example="1"),
     *     )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="Appointment created",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid fields:...",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="API key is missing or invalid",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Access token does not have the required scope",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Schedule not found",
     * )
     * @SWG\Tag(name="Appointment")
     * @Security(name="ApiKeyAuth")
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function create(Request $request, ValidatorInterface $validator): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $data = json_decode($request->getContent(), false);
        if (!$data || !is_object($data)) {
            return $this->json([
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'message' => 'Empty content.'.gettype($data)
            ], Response::HTTP_NOT_ACCEPTABLE);
        }
        /** @var Schedule $schedule */
        $schedule = $this->getDoctrine()->getRepository(Schedule::class)->findOneBy([
            'enabled' =>true,
            'id' => $data->scheduleId
        ]);
        if (null === $schedule) {
            return $this->json([
                'code' => Response::HTTP_NOT_FOUND,
                'message' => 'Schedule not found'
            ], Response::HTTP_NOT_FOUND);
        }
        $appointment = new Appointment();
        $appointment
            ->setUser($user)
            ->setSchedule($schedule)
        ;
        $schedule->setEnabled(false);

        $errors = $validator->validate($appointment);

        if (\count($errors) > 0) {
            $message = 'Invalid fields: ';
            $fields = [];
            /** @var ConstraintViolation $error */
            foreach ($errors as $error) {
                $fields[] = $error->getPropertyPath() . ' (' . $error->getMessage() . ')';
            }

            return $this->json([
                'message' => $message . implode(', ', $fields),
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->getDoctrine()->getManager()->persist($appointment);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['message' => 'Appointment created'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/list", name="appointment_list", methods={"GET"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Returns doctors data",
     * )
     * @SWG\Tag(name="Appointment")
     * @Security(name="ApiKeyAuth")
     *
     * @param AppointmentRepository $repository
     * @return JsonResponse
     */
    public function list(AppointmentRepository $repository): JsonResponse
    {
        $appointments = $repository->findBy([
            'user' =>$this->getUser()
        ]);

        return $this->json(['appointments' => $appointments], Response::HTTP_OK);
    }
}
