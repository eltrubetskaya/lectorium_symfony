<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\AppointmentRepository;
use App\Service\Payment\Braintree\BraintreeService;
use Braintree\Customer;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Braintree\Transaction;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/payment")
 */
class PaymentController extends AbstractController
{
    /**
     * @Route("/generate-token", name="payment_generate_client_token", methods={"POST"})
     *
     * @SWG\Response(
     *     response=200,
     *     description="Response get Token",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Bad Request",
     * )
     * @SWG\Tag(name="Payment")
     * @Security(name="ApiKeyAuth")
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function generateClientToken(Request $request, BraintreeService $service): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $customerId = null;
        try {
            if (null === $user->getCustomerId()) {
                $result = $service->createCustomer($user);
                if ($result instanceof Error) {
                    foreach ($result->errors->deepAll() as $error) {
                        $response['errors'][] = [
                            'message' => $error->message,
                            'code' => $error->code,
                            'attribute' => $error->attribute,
                        ];
                    }

                    return new JsonResponse([
                        'message' => json_encode($response)
                    ], Response::HTTP_BAD_REQUEST);
                }
                /** @var Customer $customer */
                $customer = $result->customer;
                $user->setCustomerId($customer->id);
                $this->getDoctrine()->getManager()->flush();
                $customerId = $customer->id;
            } else {
                $result = $service->getCustomer($user->getCustomerId());
                if ($result instanceof Customer) {
                    $customerId = $user->getCustomerId();
                }
            }
            if (null !== $customerId) {
                $token = $service->generate([
                    BraintreeService::CUSTOMER_ID => $customerId
                ]);

                return new JsonResponse([
                    'clientToken' => $token
                ], Response::HTTP_OK);
            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }
        $user->setCustomerId(null);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([
            'message' => 'Error generate token'
        ], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @Route("/{id}/pay", name="payment_pay", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="nonce", type="string", example="fake-valid-nonce"),
     *     )
     * )
     *
     * @SWG\Response(
     *     response=201,
     *     description="Transaction created",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Bad Request",
     * )
     * @SWG\Response(
     *     response=404,
     *     description="Appointment not found.",
     * )
     * @SWG\Tag(name="Payment")
     * @Security(name="ApiKeyAuth")
     *
     * @param Request $request
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function sale(Request $request, int $id, BraintreeService $service, AppointmentRepository $repository): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $appointmnet = $repository->findOneBy([
            'id' => $id,
            'user' => $user
        ]);
        if (null === $appointmnet) {
            return new JsonResponse([
                'message' => 'Appointment not found.'
            ], Response::HTTP_NOT_FOUND);
        }
        $data = json_decode($request->getContent(), false);
        if (!$data || !is_object($data)) {
            return $this->json([
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'message' => 'Empty content.'.gettype($data)
            ], Response::HTTP_NOT_ACCEPTABLE);
        }
        $customerId = null;
        try {
            if (null === $user->getCustomerId()) {
                $result = $service->createCustomer($user);
                if ($result instanceof Error) {
                    foreach ($result->errors->deepAll() as $error) {
                        $response['errors'][] = [
                            'message' => $error->message,
                            'code' => $error->code,
                            'attribute' => $error->attribute,
                        ];
                    }

                    return new JsonResponse([
                        'message' => json_encode($response)
                    ], Response::HTTP_BAD_REQUEST);
                }
                /** @var Customer $customer */
                $customer = $result->customer;
                $user->setCustomerId($customer->id);
                $this->getDoctrine()->getManager()->flush();
                $customerId = $customer->id;
            } else {
                $result = $service->getCustomer($user->getCustomerId());
                if ($result instanceof Customer) {
                    $customerId = $user->getCustomerId();
                }
            }
            if (null !== $customerId) {
                $response = $service->sale($data->nonce, 300.00);

                if ($response instanceof Successful) {
                    /** @var Transaction $receiptData */
                    $receiptData = $response->transaction;
                    $appointmnet->setTransactionId($receiptData->id);
                    $this->getDoctrine()->getManager()->flush();

                    return new JsonResponse(['message' => 'Transaction created'], Response::HTTP_CREATED);
                }

            }

        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage()
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'message' => 'Error transaction sale'
        ], Response::HTTP_BAD_REQUEST);
    }
}