<?php

namespace App\Controller\Api;

use App\Entity\User;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Swagger\Annotations as SWG;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/login", name="user_login", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="username", type="string", example="testuser"),
     *          @SWG\Property(property="password", type="string", example="testuser"),
     *     )
     * )
     * @SWG\Response(
     *     response=200,
     *     description="OK (successfully authenticated)",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Login failed: Username or password is not correct.",
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Empty content.",
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     *
     * @param UserPasswordEncoderInterface $encoder
     * @return JsonResponse
     * @throws \Exception
     */
    public function login(Request $request, UserPasswordEncoderInterface $encoder): JsonResponse
    {
        $data = json_decode($request->getContent(), false);
        if (!$data || !is_object($data)) {
            return $this->json([
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'message' => 'Empty content.'.gettype($data)
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        /** @var User $user */
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy([
            'username' => $data->username
        ]);

        if (!$user || !$encoder->isPasswordValid($user, $data->password)) {
            return $this->json([
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => 'Login failed: Username or password is not correct.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $user->generateApiToken();
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([
            'apiToken' => $user->getApiToken()
        ], Response::HTTP_OK);
    }

    /**
     * @Route("/sign-up", name="sign_up", methods={"POST"})
     *
     * @SWG\Parameter(name="body", in="body", required=true,
     *     @SWG\Schema(
     *          @SWG\Property(property="firstName", type="string", example="testuser"),
     *          @SWG\Property(property="lastName", type="string", example="testuser"),
     *          @SWG\Property(property="email", type="string", example="testuser"),
     *          @SWG\Property(property="username", type="string", example="testuser"),
     *          @SWG\Property(property="planePassword", type="string", example="testuser"),
     *          @SWG\Property(property="address", type="string", example="testuser"),
     *          @SWG\Property(property="phone", type="string", example="+380999999999"),
     *          @SWG\Property(property="gender", type="string", example="testuser"),
     *     )
     * )
     * @SWG\Response(
     *     response=201,
     *     description="User created",
     * )
     * @SWG\Response(
     *     response=400,
     *     description="Invalid fields: ...",
     * )
     * @SWG\Response(
     *     response=406,
     *     description="Empty content.",
     * )
     * @SWG\Tag(name="User")
     *
     * @param Request $request
     *
     * @param ValidatorInterface $validator
     *
     * @param UserPasswordEncoderInterface $encoder
     *
     * @return JsonResponse
     */
    public function signUp(
        Request $request,
        ValidatorInterface $validator,
        UserPasswordEncoderInterface $encoder
    ): JsonResponse {
        $data = json_decode($request->getContent(), false);
        if (!$data || !is_object($data)) {
            return $this->json([
                'code' => Response::HTTP_NOT_ACCEPTABLE,
                'message' => 'Empty content.'.gettype($data)
            ], Response::HTTP_NOT_ACCEPTABLE);
        }

        $user = new User();
        $user
            ->setFirstName($data->firstName)
            ->setLastName($data->lastName)
            ->setEmail($data->email)
            ->setUsername($data->username)
            ->setPassword($encoder->encodePassword($user, $data->planePassword))
            ->setAddress($data->address)
            ->setPhone($data->phone)
            ->setSex($data->gender)
        ;

        $errors = $validator->validate($user);

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

        $this->getDoctrine()->getManager()->persist($user);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse(['message' => 'User created'], Response::HTTP_CREATED);
    }

    /**
     * @Route("/logout", name="user_logout", methods={"POST"})
     *
     * @SWG\Response(
     *     response=204,
     *     description="Lgout success",
     * )
     * @SWG\Response(
     *     response=401,
     *     description="API key is missing or invalid",
     * )
     * @SWG\Response(
     *     response=403,
     *     description="Access token does not have the required scope",
     * )
     * @SWG\Tag(name="User")
     * @Security(name="ApiKeyAuth")
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function logout(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $user->setApiToken(null);
        $this->getDoctrine()->getManager()->flush();

        return new JsonResponse([], Response::HTTP_NO_CONTENT);
    }
}
