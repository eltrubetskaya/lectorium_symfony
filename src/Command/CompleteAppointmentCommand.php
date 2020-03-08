<?php

namespace App\Command;

use App\Entity\Schedule;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompleteAppointmentCommand extends Command
{

    private $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;

        parent::__construct();
    }

    protected static $defaultName = 'app:complete-appointment';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = new \DateTime('now');
        $schedule = $this->doctrine->getRepository(Schedule::class)->findBy([
           'enabled' => false
        ]);
        /** @var Schedule $item */
        foreach ($schedule as $item) {
            if ($item->getDay()) {

            }
        }
        $output->write('create a user.');

        return 0;
    }

}