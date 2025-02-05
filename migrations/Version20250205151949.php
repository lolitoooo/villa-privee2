<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205151949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE favorites (id SERIAL NOT NULL, user_id INT NOT NULL, villa_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_E46960F5A76ED395 ON favorites (user_id)');
        $this->addSql('CREATE INDEX IDX_E46960F5285D9761 ON favorites (villa_id)');
        $this->addSql('COMMENT ON COLUMN favorites.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE invoice (id SERIAL NOT NULL, reservation_id INT NOT NULL, filename VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, number VARCHAR(20) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_90651744B83297E7 ON invoice (reservation_id)');
        $this->addSql('COMMENT ON COLUMN invoice.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE reservation (id SERIAL NOT NULL, user_id INT NOT NULL, villa_id INT NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, total_price DOUBLE PRECISION NOT NULL, status VARCHAR(255) NOT NULL, stripe_payment_id VARCHAR(255) DEFAULT NULL, invoice_number VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_42C84955A76ED395 ON reservation (user_id)');
        $this->addSql('CREATE INDEX IDX_42C84955285D9761 ON reservation (villa_id)');
        $this->addSql('COMMENT ON COLUMN reservation.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(320) NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(100) NOT NULL, lastname VARCHAR(100) NOT NULL, roles JSON NOT NULL, is_banned BOOLEAN NOT NULL, create_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, update_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, is_verified BOOLEAN NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON "user" (email)');
        $this->addSql('COMMENT ON COLUMN "user".create_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN "user".update_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE villa (id SERIAL NOT NULL, owner_id INT NOT NULL, title VARCHAR(255) NOT NULL, description TEXT NOT NULL, price DOUBLE PRECISION NOT NULL, is_active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, location VARCHAR(255) NOT NULL, bedrooms INT NOT NULL, bathrooms INT NOT NULL, capacity INT NOT NULL, slug VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_44AE1DDA989D9B62 ON villa (slug)');
        $this->addSql('CREATE INDEX IDX_44AE1DDA7E3C61F9 ON villa (owner_id)');
        $this->addSql('COMMENT ON COLUMN villa.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN villa.updated_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE villa_image (id SERIAL NOT NULL, villa_id INT NOT NULL, filename VARCHAR(255) NOT NULL, uploaded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BEF7CF93285D9761 ON villa_image (villa_id)');
        $this->addSql('COMMENT ON COLUMN villa_image.uploaded_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE villa_review (id SERIAL NOT NULL, villa_id INT NOT NULL, author_id INT NOT NULL, content TEXT NOT NULL, rating DOUBLE PRECISION NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_EBEAC596285D9761 ON villa_review (villa_id)');
        $this->addSql('CREATE INDEX IDX_EBEAC596F675F31B ON villa_review (author_id)');
        $this->addSql('COMMENT ON COLUMN villa_review.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE favorites ADD CONSTRAINT FK_E46960F5285D9761 FOREIGN KEY (villa_id) REFERENCES villa (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_90651744B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955285D9761 FOREIGN KEY (villa_id) REFERENCES villa (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE villa ADD CONSTRAINT FK_44AE1DDA7E3C61F9 FOREIGN KEY (owner_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE villa_image ADD CONSTRAINT FK_BEF7CF93285D9761 FOREIGN KEY (villa_id) REFERENCES villa (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE villa_review ADD CONSTRAINT FK_EBEAC596285D9761 FOREIGN KEY (villa_id) REFERENCES villa (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE villa_review ADD CONSTRAINT FK_EBEAC596F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE favorites DROP CONSTRAINT FK_E46960F5A76ED395');
        $this->addSql('ALTER TABLE favorites DROP CONSTRAINT FK_E46960F5285D9761');
        $this->addSql('ALTER TABLE invoice DROP CONSTRAINT FK_90651744B83297E7');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP CONSTRAINT FK_42C84955285D9761');
        $this->addSql('ALTER TABLE villa DROP CONSTRAINT FK_44AE1DDA7E3C61F9');
        $this->addSql('ALTER TABLE villa_image DROP CONSTRAINT FK_BEF7CF93285D9761');
        $this->addSql('ALTER TABLE villa_review DROP CONSTRAINT FK_EBEAC596285D9761');
        $this->addSql('ALTER TABLE villa_review DROP CONSTRAINT FK_EBEAC596F675F31B');
        $this->addSql('DROP TABLE favorites');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE villa');
        $this->addSql('DROP TABLE villa_image');
        $this->addSql('DROP TABLE villa_review');
    }
}
