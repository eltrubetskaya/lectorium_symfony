<?php

namespace App\Normalizer;

use App\Entity\Doctor;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class DoctorNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * @param mixed $object
     * @param null $format
     * @param array $context
     *
     * @return array|bool|float|int|string
     * @throws ExceptionInterface
     */
    public function normalize($object, $format = null, array $context = [])
    {
        /** @var Doctor $doctor */
        $doctor = &$object;
        $json = new \ArrayObject([
            'id' => $doctor->getId(),
            'fullName' => $doctor->getFullName(),
            'photo' => $doctor->getPhoto(),
            'type' => $doctor->getType(),
            'info' => $doctor->getInfo(),
        ]);

        if (!$this->serializer instanceof NormalizerInterface) {
            throw new LogicException('Cannot normalize attributes because injected serializer is not a normalizer');
        }

        return $this->serializer->normalize($json, $format, array_merge($context, []));
    }

    /**
     * @param mixed $data
     * @param null $format
     *
     * @return bool
     */
    public function supportsNormalization($data, $format = null)
    {
        return $data instanceof Doctor;
    }
}
