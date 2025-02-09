<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250209160053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "option" (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, price DOUBLE PRECISION NOT NULL, description VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reservation_option (id SERIAL NOT NULL, reservation_id INT NOT NULL, option_id INT NOT NULL, quantity INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_1277492BB83297E7 ON reservation_option (reservation_id)');
        $this->addSql('CREATE INDEX IDX_1277492BA7C41D6F ON reservation_option (option_id)');
        $this->addSql('ALTER TABLE reservation_option ADD CONSTRAINT FK_1277492BB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation_option ADD CONSTRAINT FK_1277492BA7C41D6F FOREIGN KEY (option_id) REFERENCES "option" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE reservation_option DROP CONSTRAINT FK_1277492BB83297E7');
        $this->addSql('ALTER TABLE reservation_option DROP CONSTRAINT FK_1277492BA7C41D6F');
        $this->addSql('DROP TABLE "option"');
        $this->addSql('DROP TABLE reservation_option');
    }
}
