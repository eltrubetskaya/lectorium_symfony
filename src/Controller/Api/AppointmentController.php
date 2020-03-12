<?php

namespace App\Controller\Api;

use App\Entity\Appointment;
use App\Entity\Schedule;
use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Service\Payment\Braintree\BraintreeService;
use Braintree\Result\Error;
use Braintree\Result\Successful;
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
    public const TRANSACTION_ERROR_CANNOT_REFUND = 'Cannot refund transaction unless it is settled.';

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

    /**
     * @Route("/{id}/cancel", name="appointment_cancel", methods={"PUT"})
     *
     * @SWG\Response(
     *     response=204,
     *     description="Appointment cancelled",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Payment errors...",
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
     *     description="Appointment not found",
     * )
     * @SWG\Tag(name="Appointment")
     * @Security(name="ApiKeyAuth")
     *
     * @param AppointmentRepository $repository
     * @param int $id
     *
     * @return JsonResponse
     */
    public function cancel(AppointmentRepository $repository, int $id, BraintreeService $service): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        /** @var Appointment $appointment */
        $appointment = $repository->findOneBy([
            'id' => $id,
            'user' => $user
        ]);
        if ($appointment) {
            $result = $service->refund($appointment->getTransactionId());
            if ($result instanceof Error) {
                foreach ($result->errors->deepAll() as $error) {
                    $response['errors'][] = [
                        'message' => $error->message,
                        'code' => $error->code,
                        'attribute' => $error->attribute,
                    ];
                }
                $response[] = [
                    'message' => $result->message
                ];
                if ($result->message === self::TRANSACTION_ERROR_CANNOT_REFUND) {
                    $resultVoid = $service->void($appointment->getTransactionId());
                    if ($resultVoid instanceof Error) {
                        foreach ($resultVoid->errors->deepAll() as $error) {
                            $responseVoid['errors'][] = [
                                'message' => $error->message,
                                'code' => $error->code,
                                'attribute' => $error->attribute,
                            ];
                        }
                        $responseVoid[] = [
                            'message' => $resultVoid->message
                        ];
                        $response = array_merge($response, $responseVoid);
                    }
                }

                return $this->json([
                    'message' => json_encode($response),
                ], Response::HTTP_BAD_REQUEST);
            }
            if ($result instanceof Successful) {
                $appointment
                    ->setStatus(Appointment::STATUS_CANCELLED)
                    ->setRefunded(true)
                ;
                $appointment->getSchedule()->setEnabled(true);
                $this->getDoctrine()->getManager()->flush();

                return new JsonResponse([], Response::HTTP_NO_CONTENT);
            }
        }

        return new JsonResponse(['message' => 'Appointment not found.'], Response::HTTP_NOT_FOUND);
    }
}
