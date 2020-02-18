<?php

namespace App\Normalizer;

use App\Entity\Schedule;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;

class ScheduleNormalizer implements NormalizerInterface, SerializerAwareInterface
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
        /** @var Schedule $schedule */
        $schedule = &$object;
        $json = new \ArrayObject([
            'id' => $schedule->getId(),
            'doctorId' => $schedule->getDoctor()->getId(),
            'day' => $schedule->getDay(),
            'startTime' => $schedule->getStartTime()->format('H:i'),
            'endTime' => $schedule->getEndTime()->format('H:i'),
            'enabled' => $schedule->isEnabled()
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
        return $data instanceof Schedule;
    }
}
