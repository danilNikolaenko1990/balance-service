<?php declare(strict_types = 1);

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20180311172619 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->addSql("CREATE SEQUENCE user_transaction_id_seq INCREMENT BY 1 MINVALUE 1 START 1;");

        $this->addSql("CREATE TABLE user_transaction (
              id INT NOT NULL,
              created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
              amount_amount BIGINT NOT NULL, 
              amount_currency VARCHAR(255) NOT NULL,
              PRIMARY KEY(id)
            );");

    }

    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
