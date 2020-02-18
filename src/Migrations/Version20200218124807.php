<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200218124807 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE schedule_doctor');
        $this->addSql('ALTER TABLE schedule ADD doctor_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB87F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id)');
        $this->addSql('CREATE INDEX IDX_5A3811FB87F4FB17 ON schedule (doctor_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE schedule_doctor (schedule_id INT NOT NULL, doctor_id INT NOT NULL, INDEX IDX_7DE1B775A40BC2D5 (schedule_id), INDEX IDX_7DE1B77587F4FB17 (doctor_id), PRIMARY KEY(schedule_id, doctor_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE schedule_doctor ADD CONSTRAINT FK_7DE1B77587F4FB17 FOREIGN KEY (doctor_id) REFERENCES doctor (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule_doctor ADD CONSTRAINT FK_7DE1B775A40BC2D5 FOREIGN KEY (schedule_id) REFERENCES schedule (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB87F4FB17');
        $this->addSql('DROP INDEX IDX_5A3811FB87F4FB17 ON schedule');
        $this->addSql('ALTER TABLE schedule DROP doctor_id');
    }
}
