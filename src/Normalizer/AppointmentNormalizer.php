<?php

namespace App\Normalizer;

use App\Entity\Appointment;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class AppointmentNormalizer implements NormalizerInterface, SerializerAwareInterface
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
        /** @var Appointment $appointment */
        $appointment = &$object;
        $json = new \ArrayObject([
            'id' => $appointment->getId(),
            'doctor' => $appointment->getSchedule()->getDoctor(),
            'day' => $appointment->getSchedule()->getDay(),
            'startTime' => $appointment->getSchedule()->getStartTime()->format('H:i'),
            'endTime' => $appointment->getSchedule()->getEndTime()->format('H:i'),
            'status' => $appointment->getStatus(),
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
        return $data instanceof Appointment;
    }
}
